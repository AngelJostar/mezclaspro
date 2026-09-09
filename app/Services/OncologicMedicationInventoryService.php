<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OncologicMedicationInventoryService
{
    public function consumeSelection(
        array $selection,
        int $laboratoryId,
        int $listId,
        int $catalogId,
        int $mixId,
        float $doseMg,
        ?int $userId = null,
        string $chargeBy = 'frasco'
    ): array {
        return DB::transaction(function () use ($selection, $laboratoryId, $listId, $catalogId, $mixId, $doseMg, $userId, $chargeBy) {
            if (!is_finite($doseMg) || $doseMg <= 0 || !$selection) {
                throw new \InvalidArgumentException('La dosis y las presentaciones son obligatorias.');
            }
            $requested = [];
            foreach ($selection as $row) {
                $id = filter_var($row['batch_id'] ?? null, FILTER_VALIDATE_INT);
                $count = filter_var($row['frascos'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 0 || $count === false || $count < 0 || isset($requested[$id])) {
                    throw new \InvalidArgumentException('Selecciona lotes distintos y cantidades enteras de frascos, mayores o iguales a cero.');
                }
                $requested[$id] = $row;
            }
            $batches = DB::table('medicine_batches as mb')
                ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
                ->whereIn('mb.id', array_keys($requested))
                ->where('mb.laboratory_id', $laboratoryId)
                ->where('mp.catalog_id', $catalogId)
                ->where('mp.is_available', 1)
                ->whereExists(function ($query) use ($listId) {
                    $query->selectRaw('1')->from('medicine_list_presentation as mlp')
                        ->whereColumn('mlp.medicine_presentation_id', 'mp.id')->where('mlp.medicine_list_id', $listId);
                })
                ->where(fn ($query) => $query->whereNull('mb.caducidad')->orWhereDate('mb.caducidad', '>=', now()->toDateString()))
                ->orderBy('mb.id')->lockForUpdate()
                ->get(['mb.*', 'mp.cantidad_medicamento', 'mp.volumen_diluyente', 'mp.marca'])
                ->sortByDesc(fn ($batch) => (float) $batch->cantidad_medicamento)->values();
            if ($batches->count() !== count($requested)) {
                throw new \RuntimeException('Hay lotes no disponibles para el medicamento, la lista o la central seleccionados.');
            }
            $brands = $batches->map(fn ($batch) => mb_strtolower(trim((string) $batch->marca))
                ?: 'sin-marca:'.$batch->medicine_presentation_id)->unique();
            if ($brands->count() > 1) {
                throw new \RuntimeException('No puedes combinar marcas diferentes para el mismo medicamento.');
            }

            $pending = $doseMg;
            $allocations = [];
            $seenPools = [];
            foreach ($batches as $batch) {
                $mg = (float) $batch->cantidad_medicamento;
                $ml = (float) $batch->volumen_diluyente;
                $count = (int) $requested[$batch->id]['frascos'];
                if ($mg <= 0 || $ml <= 0) {
                    throw new \RuntimeException('La presentación no tiene contenido en mg y volumen en mL configurados.');
                }
                $available = max(0, (int) $batch->stock_actual - (int) $batch->stock_reservado);
                if ($count > $available) {
                    throw new \RuntimeException("Stock insuficiente para el lote {$batch->lote}. Disponible: {$available}, seleccionado: {$count}.");
                }
                $pool = $batch->medicine_presentation_id.':'.($batch->warehouse_id ?? '');
                $remainderMl = isset($seenPools[$pool]) ? 0 : app(MedicineRemainderService::class)->available(
                    'oncologico', (int) $batch->medicine_presentation_id, $laboratoryId,
                    $batch->warehouse_id ? (int) $batch->warehouse_id : null
                )->sum('current_ml');
                $seenPools[$pool] = true;
                $doseFromRemainder = min($pending, $remainderMl * $mg / $ml);
                $allocations[] = ['batch' => $batch, 'count' => $count, 'dose' => $doseFromRemainder];
                $pending = max(0, $pending - $doseFromRemainder);
            }
            // Split the dose across selected presentations, never ask each one to cover it all.
            foreach ($allocations as &$allocation) {
                $provided = min($pending, $allocation['count'] * (float) $allocation['batch']->cantidad_medicamento);
                $allocation['dose'] += $provided;
                $pending = max(0, $pending - $provided);
            }
            unset($allocation);
            if ($pending > 0.0001) {
                throw new \RuntimeException('Las presentaciones seleccionadas no cubren la dosis. Faltan '.round($pending, 4).' mg.');
            }

            $consumed = [];
            foreach ($allocations as $allocation) {
                if ($allocation['dose'] <= 0) continue;
                $id = (int) $allocation['batch']->id;
                $price = $requested[$id]['precio_frasco'] ?? null;
                $consumed[] = $this->consume($id, $allocation['count'], $laboratoryId, $listId, $catalogId, $mixId,
                    $allocation['dose'], $userId, $price !== null && $price !== '' ? (float) $price : null, $chargeBy);
            }
            if (abs(array_sum(array_column($consumed, 'dose_provided_mg')) - $doseMg) > 0.01) {
                throw new \RuntimeException('El inventario cambió durante la dispensación. Revisa las presentaciones antes de guardar.');
            }
            return $consumed;
        });
    }

    private function fallbackBatch(object $currentBatch, int $laboratoryId, int $listId, int $catalogId): ?object
    {
        $list = DB::table('medicine_lists')->where('id', $listId)
            ->first(['warehouse_id', 'backup_enabled', 'backup_warehouse_id']);
        $backupWarehouseId = (int) ($list?->backup_warehouse_id ?? 0);

        if (! $list || ! $list->backup_enabled || $backupWarehouseId <= 0
            || (int) ($currentBatch->warehouse_id ?? 0) !== (int) ($list->warehouse_id ?? 0)) {
            return null;
        }

        return DB::table('medicine_batches as mb')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->leftJoin('medicine_list_presentation as mlp', function ($join) use ($listId) {
                $join->on('mlp.medicine_presentation_id', '=', 'mp.id')->where('mlp.medicine_list_id', $listId);
            })
            ->where('mb.laboratory_id', $laboratoryId)
            ->where('mb.warehouse_id', $backupWarehouseId)
            ->where('mb.medicine_presentation_id', $currentBatch->medicine_presentation_id)
            ->where('mb.stock_actual', '>', 0)
            ->where('mp.catalog_id', $catalogId)
            ->where('mp.is_available', 1)
            ->where(function ($query) {
                $query->whereNull('mb.caducidad')->orWhereDate('mb.caducidad', '>=', now()->toDateString());
            })
            ->orderByRaw('mb.caducidad IS NULL')
            ->orderBy('mb.caducidad')
            ->orderBy('mb.id')
            ->lockForUpdate()
            ->select([
                'mb.*', 'mp.id as presentation_id', 'mp.presentacion', 'mp.cantidad_medicamento',
                'mp.volumen_diluyente', 'mp.stability_hours', 'mp.legend', 'mp.marca', 'mp.precio_frasco',
                'mlp.precio as precio_lista',
            ])->first();
    }

    public function consume(
        int $batchId,
        int $maximumContainers,
        int $laboratoryId,
        int $listId,
        int $catalogId,
        int $mixId,
        float $requiredDoseMg,
        ?int $userId = null,
        ?float $unitPriceOverride = null,
        string $chargeBy = 'frasco'
    ): object {
        if ($batchId <= 0 || $maximumContainers < 0 || $requiredDoseMg <= 0) {
            throw new \InvalidArgumentException('El lote y la dosis son obligatorios; los frascos no pueden ser negativos.');
        }

        $batch = DB::table('medicine_batches as mb')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->leftJoin('medicine_list_presentation as mlp', function ($join) use ($listId) {
                $join->on('mlp.medicine_presentation_id', '=', 'mp.id')
                    ->where('mlp.medicine_list_id', '=', $listId);
            })
            ->where('mb.id', $batchId)
            ->where('mb.laboratory_id', $laboratoryId)
            ->where('mp.catalog_id', $catalogId)
            ->where('mp.is_available', 1)
            ->where(function ($query) {
                $query->whereNull('mb.caducidad')
                    ->orWhereDate('mb.caducidad', '>=', now()->toDateString());
            })
            ->lockForUpdate()
            ->select([
                'mb.*',
                'mp.id as presentation_id',
                'mp.presentacion',
                'mp.cantidad_medicamento',
                'mp.volumen_diluyente',
                'mp.stability_hours',
                'mp.legend',
                'mp.marca',
                'mp.precio_frasco',
                'mlp.precio as precio_lista',
            ])
            ->first();

        if (!$batch) {
            throw new \RuntimeException('No se encontró el lote para el laboratorio y medicamento correspondientes.');
        }

        if ((int) ($batch->stock_actual ?? 0) <= 0 && $maximumContainers > 0) {
            $fallbackBatch = $this->fallbackBatch($batch, $laboratoryId, $listId, $catalogId);
            if ($fallbackBatch) {
                $batch = $fallbackBatch;
            }
        }

        $containerMl = (float) ($batch->volumen_diluyente ?? 0);
        $containerMg = (float) ($batch->cantidad_medicamento ?? 0);
        if ($containerMl <= 0 || $containerMg <= 0) {
            throw new \RuntimeException('La presentación no tiene contenido en mg y volumen en mL configurados.');
        }

        $mgPerMl = $containerMg / $containerMl;
        $requiredMl = $requiredDoseMg / $mgPerMl;
        $remainderService = app(MedicineRemainderService::class);
        $remainderResult = $remainderService->consumeAvailable(
            'oncologico',
            (int) $batch->presentation_id,
            $laboratoryId,
            $requiredMl,
            'mezcla',
            $mixId,
            $batch->warehouse_id ? (int) $batch->warehouse_id : null,
            $userId
        );

        $remainingMl = (float) $remainderResult['remaining_ml'];
        $containersOpened = $remainingMl > 0.0001 ? (int) ceil($remainingMl / $containerMl) : 0;
        if ($containersOpened > $maximumContainers) {
            throw new \RuntimeException("La selección solo cubre {$maximumContainers} frasco(s); se requieren {$containersOpened}.");
        }

        $stockBefore = (int) ($batch->stock_actual ?? 0);
        $availableStock = max(0, $stockBefore - (int) ($batch->stock_reservado ?? 0));
        if ($availableStock < $containersOpened) {
            throw new \RuntimeException("Stock insuficiente para el lote {$batch->lote}. Disponible: {$availableStock}, requerido: {$containersOpened}.");
        }

        $stockMlBefore = (float) ($batch->stock_ml_actual ?? ($stockBefore * $containerMl));
        $openedMl = $containersOpened * $containerMl;
        $stockAfter = $stockBefore - $containersOpened;
        $stockMlAfter = max(0, $stockMlBefore - $openedMl);

        if ($containersOpened > 0) {
            DB::table('medicine_batches')->where('id', $batch->id)->update([
                'stock_actual' => $stockAfter,
                'stock_ml_actual' => $stockMlAfter,
                'is_active' => $stockAfter > 0,
                'updated_at' => now(),
            ]);

            DB::table('medicine_batch_movements')->insert([
                'medicine_batch_id' => $batch->id,
                'laboratory_id' => $laboratoryId,
                'warehouse_id' => $batch->warehouse_id,
                'user_id' => $userId,
                'movement_type' => 'salida',
                'quantity' => $containersOpened,
                'quantity_ml' => $openedMl,
                'stock_actual_before' => $stockBefore,
                'stock_actual_after' => $stockAfter,
                'stock_ml_before' => $stockMlBefore,
                'stock_ml_after' => $stockMlAfter,
                'stock_reservado_before' => (int) ($batch->stock_reservado ?? 0),
                'stock_reservado_after' => (int) ($batch->stock_reservado ?? 0),
                'reference_type' => 'mezcla',
                'reference_id' => $mixId,
                'notes' => 'Apertura de frasco; el volumen no utilizado queda como remanente.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $toUse = $remainingMl;
            for ($i = 0; $i < $containersOpened; $i++) {
                $usedFromContainer = min($containerMl, $toUse);
                $remainderService->openContainer([
                    'domain' => 'oncologico',
                    'laboratory_id' => $laboratoryId,
                    'warehouse_id' => $batch->warehouse_id,
                    'medicine_presentation_id' => $batch->presentation_id,
                    'medicine_batch_id' => $batch->id,
                    'lote' => $batch->lote,
                    'caducidad' => $batch->caducidad,
                    'stability_hours' => $batch->stability_hours,
                    'reference_type' => 'mezcla',
                    'reference_id' => $mixId,
                    'user_id' => $userId,
                ], $containerMl, $usedFromContainer);
                $toUse = max(0, $toUse - $usedFromContainer);
            }
        }

        $usedMl = min($requiredMl, (float) $remainderResult['consumed_ml'] + $openedMl);
        $doseProvided = $usedMl * $mgPerMl;
        $billableContainers = (int) ceil($usedMl / $containerMl);
        $unitPrice = $unitPriceOverride !== null
            ? $unitPriceOverride
            : (float) ($batch->precio_lista ?? $batch->precio_frasco ?? 0);
        $chargeBy = in_array($chargeBy, ['ml', 'frasco'], true) ? $chargeBy : 'frasco';
        $billableQuantity = $chargeBy === 'ml' ? $usedMl : $billableContainers;

        return (object) [
            'batch_id' => (int) $batch->id,
            'lote' => $batch->lote,
            'caducidad' => $batch->caducidad,
            'presentation_id' => (int) $batch->presentation_id,
            'presentacion' => $batch->presentacion,
            'cantidad_medicamento' => $batch->cantidad_medicamento,
            'volumen_diluyente' => $batch->volumen_diluyente,
            'legend' => $batch->legend,
            'marca' => $batch->marca,
            'precio_frasco' => $chargeBy === 'frasco' ? $unitPrice : null,
            'unit_price' => $unitPrice,
            'charge_by' => $chargeBy,
            'billable_quantity' => round($billableQuantity, 4),
            'subtotal' => $unitPrice * $billableQuantity,
            'billable_containers' => $billableContainers,
            'opened_containers' => $containersOpened,
            'used_ml' => round($usedMl, 4),
            'dose_provided_mg' => round($doseProvided, 4),
            'remainder_used_ml' => (float) $remainderResult['consumed_ml'],
        ];
    }
}
