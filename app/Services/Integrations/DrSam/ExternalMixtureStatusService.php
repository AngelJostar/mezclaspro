<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudInput;
use App\Models\Oncologicos\MedicineBatchMovement;
use App\Models\Oncologicos\SolicitudOnco;
use App\Support\MixtureIntegrationStatus;

class ExternalMixtureStatusService
{
    public function __construct(
        private DrSamWebhookNotifier $webhooks,
        private ExternalMixtureNotificationService $notifications,
    )
    {
    }

    public function refresh(ExternalMixtureRequest $external): ExternalMixtureRequest
    {
        if (! $external->materialized_id) {
            return $external;
        }

        [$status, $details] = $external->materialized_type === 'npt'
            ? $this->npt($external)
            : $this->oncology($external);

        $previousStatus = $external->status;
        $external->update([
            'status' => $status,
            'status_details' => $details,
            'status_checked_at' => now(),
        ]);

        $external = $external->refresh();

        if ($previousStatus !== $external->status) {
            $this->notifications->notify($external);
            $this->webhooks->statusChanged($external);
        }

        return $external;
    }

    private function npt(ExternalMixtureRequest $external): array
    {
        $request = Solicitud::query()->find($external->materialized_id);
        if (! $request) {
            return ['materialization_failed', ['reason' => 'La solicitud NPT materializada ya no existe.']];
        }

        return [$this->canonical($request->estado), [
            'domain' => 'npt',
            'source_status' => $request->estado,
            'inventory' => $this->nptInventory($request),
            'remission' => $this->remission(
                $request->remision,
                $request->updated_at,
                $this->nptRemissionItems($request)
            ),
        ]];
    }

    private function oncology(ExternalMixtureRequest $external): array
    {
        $request = SolicitudOnco::query()->with([
            'mezclas:id,solicitud_id,estado,remision',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',
        ])->find($external->materialized_id);
        if (! $request) {
            return ['materialization_failed', ['reason' => 'La solicitud oncologica materializada ya no existe.']];
        }

        $mixtureStates = $request->mezclas->pluck('estado')->filter()->values();
        $status = $this->canonical($request->estado);

        if (! in_array($status, ['cancelled', 'rejected', 'delivered'], true) && $mixtureStates->isNotEmpty()) {
            if ($mixtureStates->every(fn ($state) => $state === 'entregada')) {
                $status = 'delivered';
            } elseif ($mixtureStates->contains('revisada')) {
                $status = 'ready';
            } elseif ($mixtureStates->contains('preparada')) {
                $status = 'preparing';
            } elseif ($mixtureStates->contains('dispensada')) {
                $status = 'dispensed';
            } elseif ($mixtureStates->contains('aprobada')) {
                $status = 'authorized';
            }
        }

        return [$status, [
            'domain' => 'oncology',
            'source_status' => $request->estado,
            'mixture_statuses' => $mixtureStates->countBy()->all(),
            'inventory' => $this->oncologyInventory($request),
            'remission' => $this->remission(
                $request->remision,
                $request->updated_at,
                $this->oncologyRemissionItems($request)
            ),
        ]];
    }

    private function nptInventory(Solicitud $request): array
    {
        $movements = MedicineStockMovement::query()
            ->where('reference_type', 'Solicitud')
            ->where('reference_id', $request->getKey())
            ->where('tipo', 'salida')
            ->get(['id', 'cantidad_ml', 'created_at']);

        return $this->inventoryDetails(
            $movements->count(),
            (float) $movements->sum('cantidad_ml'),
            'ml',
            $movements->max('created_at')
        );
    }

    private function oncologyInventory(SolicitudOnco $request): array
    {
        $mixtureIds = $request->mezclas->pluck('id');
        $movements = MedicineBatchMovement::query()
            ->where('reference_type', 'mezcla')
            ->whereIn('reference_id', $mixtureIds)
            ->where('movement_type', 'salida')
            ->get(['id', 'quantity', 'created_at']);

        return $this->inventoryDetails(
            $movements->count(),
            (float) $movements->sum('quantity'),
            'units',
            $movements->max('created_at')
        );
    }

    private function inventoryDetails(int $movementCount, float $quantity, string $unit, $consumedAt): array
    {
        $consumed = $movementCount > 0;

        return [
            'policy' => 'consume_on_operational_approval',
            'stage' => $consumed ? 'consumed' : 'validated',
            'validated' => true,
            'reserved' => false,
            'consumed' => $consumed,
            'movement_count' => $movementCount,
            'consumed_quantity' => $quantity,
            'quantity_unit' => $unit,
            'consumed_at' => $consumedAt?->toIso8601String(),
        ];
    }

    private function canonical(?string $status): string
    {
        return MixtureIntegrationStatus::fromCbta($status);
    }

    private function remission(?string $number, $updatedAt, array $items = []): array
    {
        $total = round((float) collect($items)->sum('amount'), 2);

        return [
            'available' => filled($number),
            'number' => $number,
            'document_type' => 'delivery_remission',
            'issued_at' => $number ? $updatedAt?->toIso8601String() : null,
            'currency' => 'MXN',
            'items' => $items,
            'subtotal' => $total,
            'total' => $total,
        ];
    }

    private function nptRemissionItems(Solicitud $request): array
    {
        return SolicitudInput::query()
            ->where('solicitud_id', $request->getKey())
            ->with(['input.nutritionMedicineCatalog', 'presentation.catalog'])
            ->get()
            ->map(function (SolicitudInput $item): array {
                $quantity = (float) ($item->valor_ml ?? $item->valor ?? 0);
                $amount = round((float) ($item->precio_ml ?? 0), 2);
                $catalog = $item->presentation?->catalog
                    ?? $item->input?->nutritionMedicineCatalog;

                return [
                    'product_code' => $catalog?->external_code,
                    'product' => $catalog?->denominacion_generica
                        ?? $item->input?->description
                        ?? 'Producto NPT',
                    'presentation_code' => $item->presentation?->external_code,
                    'presentation' => $item->presentation?->denominacion_comercial,
                    'quantity' => $quantity,
                    'unit' => 'ml',
                    'price_type' => 'Precio por ml',
                    'unit_price' => $quantity > 0 ? round($amount / $quantity, 4) : 0,
                    'amount' => $amount,
                ];
            })
            ->values()
            ->all();
    }

    private function oncologyRemissionItems(SolicitudOnco $request): array
    {
        return $request->mezclas
            ->flatMap(fn ($mixture) => $mixture->medicamentos)
            ->map(function ($medicine): array {
                $chargeBy = $medicine->charge_by === 'mg' ? 'mg' : 'frasco';
                $quantity = $chargeBy === 'mg'
                    ? (float) ($medicine->dosis ?? 0)
                    : (float) $medicine->presentacionesUsadas->sum(
                        fn ($presentation) => (float) ($presentation->unidades_usadas ?? 1)
                    );
                $amount = round((float) $medicine->total(), 2);

                return [
                    'product_code' => null,
                    'product' => $medicine->denominacion_snapshot
                        ?? $medicine->nombre_medicamento
                        ?? 'Medicamento oncológico',
                    'presentation_code' => $medicine->presentacionesUsadas
                        ->map(fn ($used) => $used->batch?->presentation?->external_code)
                        ->filter()->implode(', '),
                    'presentation' => $medicine->presentacionesUsadas
                        ->map(fn ($used) => $used->batch?->presentation?->nombre)
                        ->filter()->implode(', '),
                    'quantity' => $quantity,
                    'unit' => $chargeBy,
                    'price_type' => $chargeBy === 'mg' ? 'Precio por mg' : 'Precio por frasco',
                    'unit_price' => $quantity > 0 ? round($amount / $quantity, 4) : 0,
                    'amount' => $amount,
                ];
            })
            ->values()
            ->all();
    }
}
