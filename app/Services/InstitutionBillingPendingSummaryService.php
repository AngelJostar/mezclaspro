<?php

namespace App\Services;

use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;

class InstitutionBillingPendingSummaryService
{
    public function __construct(
        private InstitutionBillingDueDateService $dueDates
    ) {
    }

    /**
     * @return array{yellow: int, red: int}
     */
    public function counts(): array
    {
        $oncologicas = Mezcla::query()
            ->select(['id', 'solicitud_id'])
            ->with([
                'solicitud:id,hospital_id,fecha_entrega',
                'billing:id,origen_tipo,origen_id,estatus_facturacion',
            ])
            ->whereHas('solicitud.hospital.instituciones')
            ->get()
            ->map(fn ($mezcla) => [
                'delivery_date' => $mezcla->solicitud?->fecha_entrega,
                'billing_status' => $mezcla->billing?->estatus_facturacion,
            ]);

        $nutricionales = NutricionalSolicitud::query()
            ->select(['id', 'user_id', 'solicitud_detail_id'])
            ->with([
                'solicitud_detail:id,fecha_hora_entrega',
                'billing:id,origen_tipo,origen_id,estatus_facturacion',
            ])
            ->whereHas('user.hospital.instituciones')
            ->get()
            ->map(fn ($solicitud) => [
                'delivery_date' => $solicitud->solicitud_detail?->fecha_hora_entrega,
                'billing_status' => $solicitud->billing?->estatus_facturacion,
            ]);

        return $this->summarize($oncologicas->concat($nutricionales));
    }

    /**
     * @param  iterable<array{delivery_date: mixed, billing_status?: string|null}>  $records
     * @return array{yellow: int, red: int}
     */
    public function summarize(iterable $records): array
    {
        $counts = [
            'yellow' => 0,
            'red' => 0,
        ];

        foreach ($records as $record) {
            $expiration = $this->dueDates->calculate(
                $record['delivery_date'] ?? null,
                $record['billing_status'] ?? null
            );

            if (isset($counts[$expiration['status']])) {
                $counts[$expiration['status']]++;
            }
        }

        return $counts;
    }
}
