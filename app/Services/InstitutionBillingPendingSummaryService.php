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
                'billing:id,origen_tipo,origen_id,estatus_facturacion,folio_interno,fecha_facturacion,numero_carta_factura,fecha_carta_factura',
            ])
            ->whereHas('solicitud.hospital.instituciones')
            ->get()
            ->map(fn ($mezcla) => [
                'delivery_date' => $mezcla->solicitud?->fecha_entrega,
                'billing' => $mezcla->billing,
            ]);

        $nutricionales = NutricionalSolicitud::query()
            ->select(['id', 'user_id', 'solicitud_detail_id'])
            ->with([
                'solicitud_detail:id,fecha_hora_entrega',
                'billing:id,origen_tipo,origen_id,estatus_facturacion,folio_interno,fecha_facturacion,numero_carta_factura,fecha_carta_factura',
            ])
            ->whereHas('user.hospital.instituciones')
            ->get()
            ->map(fn ($solicitud) => [
                'delivery_date' => $solicitud->solicitud_detail?->fecha_hora_entrega,
                'billing' => $solicitud->billing,
            ]);

        return $this->summarize($oncologicas->concat($nutricionales));
    }

    /**
     * @param  iterable<array{delivery_date: mixed, billing?: mixed}>  $records
     * @return array{yellow: int, red: int}
     */
    public function summarize(iterable $records): array
    {
        $counts = [
            'yellow' => 0,
            'red' => 0,
        ];

        foreach ($records as $record) {
            $billing = $record['billing'] ?? null;

            if ($billing?->hasReceivableInvoiceData()) {
                continue;
            }

            $expiration = $this->dueDates->calculate(
                $record['delivery_date'] ?? null,
                $billing?->estatus_facturacion ?? ($record['billing_status'] ?? null)
            );

            if (isset($counts[$expiration['status']])) {
                $counts[$expiration['status']]++;
            }
        }

        return $counts;
    }
}
