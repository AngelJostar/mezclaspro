<?php

namespace App\Services;

use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Oncologicos\MedicineBatch;
use App\Models\Oncologicos\MedicineBatchMovement;
use App\Models\WasteAuthorizationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WasteAuthorizationService
{
    public function requestForBatch(
        MedicineBatch $batch,
        int $quantity,
        string $reason,
        int $requesterId
    ): WasteAuthorizationRequest {
        return DB::transaction(function () use ($batch, $quantity, $reason, $requesterId) {
            $lockedBatch = MedicineBatch::query()
                ->with(['presentation.catalog', 'laboratory', 'warehouse'])
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $availableContainers = max(
                0,
                (int) $lockedBatch->stock_actual - (int) $lockedBatch->stock_reservado
            );
            $pendingContainers = (int) WasteAuthorizationRequest::query()
                ->pending()
                ->where('medicine_batch_id', $lockedBatch->id)
                ->sum('quantity_containers');

            if ($quantity < 1 || $quantity + $pendingContainers > $availableContainers) {
                $this->invalidQuantity(
                    'La cantidad solicitada supera los frascos disponibles, considerando otras solicitudes pendientes.'
                );
            }

            $presentation = $lockedBatch->presentation;
            $volumePerContainer = $this->oncologicVolumePerContainer($presentation);

            if ($volumePerContainer <= 0) {
                $this->invalidQuantity('La presentación no tiene un volumen por frasco configurado.');
            }

            $costPerContainer = $lockedBatch->costo_unitario !== null
                ? (float) $lockedBatch->costo_unitario
                : null;
            $category = strtolower((string) $presentation?->catalog?->catalog_category);

            return WasteAuthorizationRequest::query()->create([
                'domain' => $category === 'antibioticos' ? 'antibiotico' : 'oncologico',
                'medicine_batch_id' => $lockedBatch->id,
                'requested_by' => $requesterId,
                'quantity_containers' => $quantity,
                'quantity_ml' => $quantity * $volumePerContainer,
                'reason' => trim($reason),
                'status' => WasteAuthorizationRequest::STATUS_PENDING,
                'snapshot_product' => $presentation?->catalog?->denominacion ?: 'Producto sin nombre',
                'snapshot_presentation' => $presentation?->presentacion,
                'snapshot_brand' => $presentation?->marca,
                'snapshot_lot' => $lockedBatch->lote,
                'snapshot_laboratory' => $lockedBatch->laboratory?->nombre,
                'snapshot_warehouse' => $lockedBatch->warehouse?->name,
                'snapshot_cost_per_ml' => $costPerContainer !== null
                    ? $costPerContainer / $volumePerContainer
                    : null,
                'snapshot_cost_per_container' => $costPerContainer,
            ]);
        });
    }

    public function requestForNutritionStock(
        MedicineLaboratoryStock $stock,
        int $quantity,
        string $reason,
        int $requesterId
    ): WasteAuthorizationRequest {
        return DB::transaction(function () use ($stock, $quantity, $reason, $requesterId) {
            $lockedStock = MedicineLaboratoryStock::query()
                ->with(['presentation.catalog', 'laboratory', 'warehouse'])
                ->lockForUpdate()
                ->findOrFail($stock->id);
            $volumePerContainer = (float) ($lockedStock->presentation?->presentacion_ml ?? 0);

            if ($volumePerContainer <= 0) {
                $this->invalidQuantity('La presentación no tiene un volumen por frasco configurado.');
            }

            $availableContainers = (int) floor(min(
                (float) $lockedStock->frascos_actuales,
                (float) $lockedStock->stock_ml_actual / $volumePerContainer
            ));
            $pendingContainers = (int) WasteAuthorizationRequest::query()
                ->pending()
                ->where('medicine_laboratory_stock_id', $lockedStock->id)
                ->sum('quantity_containers');

            if ($quantity < 1 || $quantity + $pendingContainers > $availableContainers) {
                $this->invalidQuantity(
                    'La cantidad solicitada supera los frascos enteros disponibles, considerando otras solicitudes pendientes.'
                );
            }

            $presentation = $lockedStock->presentation;

            return WasteAuthorizationRequest::query()->create([
                'domain' => 'nutricional',
                'medicine_laboratory_stock_id' => $lockedStock->id,
                'requested_by' => $requesterId,
                'quantity_containers' => $quantity,
                'quantity_ml' => $quantity * $volumePerContainer,
                'reason' => trim($reason),
                'status' => WasteAuthorizationRequest::STATUS_PENDING,
                'snapshot_product' => $presentation?->catalog?->denominacion_generica ?: 'Producto sin nombre',
                'snapshot_presentation' => $presentation?->presentacion,
                'snapshot_brand' => $presentation?->denominacion_comercial,
                'snapshot_lot' => $lockedStock->lote,
                'snapshot_laboratory' => $lockedStock->laboratory?->nombre,
                'snapshot_warehouse' => $lockedStock->warehouse?->name,
            ]);
        });
    }

    public function approve(
        WasteAuthorizationRequest $authorizationRequest,
        int $reviewerId,
        ?string $reviewNotes = null
    ): WasteAuthorizationRequest {
        return DB::transaction(function () use ($authorizationRequest, $reviewerId, $reviewNotes) {
            $lockedRequest = WasteAuthorizationRequest::query()
                ->lockForUpdate()
                ->findOrFail($authorizationRequest->id);

            $this->ensurePending($lockedRequest);

            if ($lockedRequest->medicine_batch_id) {
                $this->applyBatchWaste($lockedRequest);
            } elseif ($lockedRequest->medicine_laboratory_stock_id) {
                $this->applyNutritionWaste($lockedRequest);
            } else {
                throw ValidationException::withMessages([
                    'request' => 'El inventario relacionado con la solicitud ya no existe.',
                ]);
            }

            $lockedRequest->forceFill([
                'status' => WasteAuthorizationRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $this->nullableText($reviewNotes),
            ])->save();

            return $lockedRequest->refresh();
        });
    }

    public function reject(
        WasteAuthorizationRequest $authorizationRequest,
        int $reviewerId,
        string $reviewNotes
    ): WasteAuthorizationRequest {
        return DB::transaction(function () use ($authorizationRequest, $reviewerId, $reviewNotes) {
            $lockedRequest = WasteAuthorizationRequest::query()
                ->lockForUpdate()
                ->findOrFail($authorizationRequest->id);

            $this->ensurePending($lockedRequest);
            $lockedRequest->forceFill([
                'status' => WasteAuthorizationRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => trim($reviewNotes),
            ])->save();

            return $lockedRequest->refresh();
        });
    }

    private function applyBatchWaste(WasteAuthorizationRequest $authorizationRequest): void
    {
        $batch = MedicineBatch::query()
            ->with('presentation')
            ->lockForUpdate()
            ->find($authorizationRequest->medicine_batch_id);

        if (! $batch) {
            throw ValidationException::withMessages([
                'request' => 'El lote relacionado con la solicitud ya no existe.',
            ]);
        }

        $quantity = (int) $authorizationRequest->quantity_containers;
        $availableContainers = max(0, (int) $batch->stock_actual - (int) $batch->stock_reservado);

        if ($quantity < 1 || $quantity > $availableContainers) {
            $this->invalidQuantity('El lote ya no tiene suficientes frascos disponibles para autorizar esta merma.');
        }

        $stockBefore = (int) $batch->stock_actual;
        $stockAfter = $stockBefore - $quantity;
        $volumePerContainer = (float) $authorizationRequest->quantity_ml / $quantity;
        $stockMlBefore = (float) $batch->stock_ml_actual;

        if ($stockMlBefore <= 0 && $stockBefore > 0) {
            $stockMlBefore = $stockBefore * ($volumePerContainer > 0
                ? $volumePerContainer
                : $this->oncologicVolumePerContainer($batch->presentation));
        }

        $quantityMl = (float) $authorizationRequest->quantity_ml;
        $stockMlAfter = max(0, $stockMlBefore - $quantityMl);

        $batch->update([
            'stock_actual' => $stockAfter,
            'stock_ml_actual' => $stockMlAfter,
            'is_active' => $stockAfter > 0 || $stockMlAfter > 0.0001,
        ]);

        MedicineBatchMovement::query()->create([
            'medicine_batch_id' => $batch->id,
            'laboratory_id' => $batch->laboratory_id,
            'warehouse_id' => $batch->warehouse_id,
            'user_id' => $authorizationRequest->requested_by,
            'movement_type' => 'merma',
            'quantity' => $quantity,
            'quantity_ml' => $quantityMl,
            'stock_actual_before' => $stockBefore,
            'stock_actual_after' => $stockAfter,
            'stock_ml_before' => $stockMlBefore,
            'stock_ml_after' => $stockMlAfter,
            'stock_reservado_before' => $batch->stock_reservado,
            'stock_reservado_after' => $batch->stock_reservado,
            'reference_type' => 'WasteAuthorizationRequest',
            'reference_id' => $authorizationRequest->id,
            'notes' => $authorizationRequest->reason,
        ]);
    }

    private function applyNutritionWaste(WasteAuthorizationRequest $authorizationRequest): void
    {
        $stock = MedicineLaboratoryStock::query()
            ->with('presentation')
            ->lockForUpdate()
            ->find($authorizationRequest->medicine_laboratory_stock_id);

        if (! $stock) {
            throw ValidationException::withMessages([
                'request' => 'El lote nutricional relacionado con la solicitud ya no existe.',
            ]);
        }

        $quantity = (int) $authorizationRequest->quantity_containers;
        $quantityMl = (float) $authorizationRequest->quantity_ml;
        $stockBefore = (float) $stock->stock_ml_actual;
        $containersBefore = (float) $stock->frascos_actuales;
        $volumePerContainer = $quantity > 0 ? $quantityMl / $quantity : 0;
        $availableContainers = $volumePerContainer > 0
            ? (int) floor(min($containersBefore, $stockBefore / $volumePerContainer))
            : 0;

        if ($quantity < 1 || $quantity > $availableContainers || $quantityMl > $stockBefore) {
            $this->invalidQuantity('El lote ya no tiene suficientes frascos enteros para autorizar esta merma.');
        }

        $stockAfter = max(0, $stockBefore - $quantityMl);
        $containersAfter = max(0, $containersBefore - $quantity);

        $stock->update([
            'stock_ml_actual' => $stockAfter,
            'frascos_actuales' => $containersAfter,
            'is_active' => $stockAfter > 0.0001,
        ]);

        MedicineStockMovement::query()->create([
            'medicine_laboratory_stock_id' => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'user_id' => $authorizationRequest->requested_by,
            'tipo' => 'merma',
            'cantidad_ml' => $quantityMl,
            'cantidad_frascos' => $quantity,
            'stock_antes' => $stockBefore,
            'stock_despues' => $stockAfter,
            'frascos_antes' => $containersBefore,
            'frascos_despues' => $containersAfter,
            'reference_type' => 'WasteAuthorizationRequest',
            'reference_id' => $authorizationRequest->id,
            'notes' => $authorizationRequest->reason,
        ]);
    }

    private function ensurePending(WasteAuthorizationRequest $authorizationRequest): void
    {
        if ($authorizationRequest->status !== WasteAuthorizationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'request' => 'Esta solicitud de merma ya fue atendida.',
            ]);
        }
    }

    private function oncologicVolumePerContainer(mixed $presentation): float
    {
        $volume = (float) ($presentation?->volumen_diluyente ?? 0);

        if ($volume <= 0 && strtolower((string) ($presentation?->contenido_unidad ?? '')) === 'ml') {
            $volume = (float) ($presentation?->contenido_valor ?? 0);
        }

        return $volume;
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function invalidQuantity(string $message): never
    {
        throw ValidationException::withMessages(['quantity' => $message]);
    }
}
