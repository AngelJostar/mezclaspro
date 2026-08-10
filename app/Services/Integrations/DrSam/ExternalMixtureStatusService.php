<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\MedicineBatchMovement;
use App\Models\Oncologicos\SolicitudOnco;

class ExternalMixtureStatusService
{
    public function __construct(private DrSamWebhookNotifier $webhooks)
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
            'remission' => $this->remission($request->remision, $request->updated_at),
        ]];
    }

    private function oncology(ExternalMixtureRequest $external): array
    {
        $request = SolicitudOnco::query()->with('mezclas:id,solicitud_id,estado,remision')->find($external->materialized_id);
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
            } elseif ($mixtureStates->contains('aprobada')) {
                $status = 'authorized';
            }
        }

        return [$status, [
            'domain' => 'oncology',
            'source_status' => $request->estado,
            'mixture_statuses' => $mixtureStates->countBy()->all(),
            'inventory' => $this->oncologyInventory($request),
            'remission' => $this->remission($request->remision, $request->updated_at),
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
        return match (strtolower((string) $status)) {
            'aprobada', 'autorizada' => 'authorized',
            'enproceso', 'preparada' => 'preparing',
            'revisada', 'lista' => 'ready',
            'finalizada', 'entregada' => 'delivered',
            'cancelada' => 'cancelled',
            'no_aprobada', 'no-aprobada', 'rechazada' => 'rejected',
            default => 'pending',
        };
    }

    private function remission(?string $number, $updatedAt): array
    {
        return [
            'available' => filled($number),
            'number' => $number,
            'document_type' => 'delivery_remission',
            'issued_at' => $number ? $updatedAt?->toIso8601String() : null,
        ];
    }
}
