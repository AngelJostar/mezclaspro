<?php

namespace App\Services;

use App\Models\Oncologicos\Mezcla;

class QuotationPreparationPricing
{
    public function summary(array $snapshot, ?Mezcla $mixture = null): array
    {
        $lines = collect($snapshot['lines'])->filter(fn ($line) => $line['unit'] !== 'servicio')->values()->map(fn ($line) => $line + [
            'unit_label' => $line['unit'], 'bottle_quantity' => $line['unit'] === 'frasco' ? $line['quantity'] : 0,
            'vat_breakdown' => $line['vat'] > 0, 'total_with_vat' => $line['total'], 'presentation_lines' => collect(),
        ]);
        $services = collect($snapshot['lines'])->filter(fn ($line) => $line['unit'] === 'servicio')->values();
        $mixing = $services->filter(fn ($line) => $line['description'] === 'Servicio de mezclado');
        $additional = $services->reject(fn ($line) => $line['description'] === 'Servicio de mezclado')->map(fn ($line) => $line + [
            'concept_type' => 'quotation', 'unit_label' => 'servicio', 'subtotal_before_vat' => $line['subtotal'],
        ])->values();
        $summary = [
            'description' => $lines->pluck('description')->implode(', '), 'lines' => $lines, 'supply_lines' => collect(),
            'medication_total' => round($lines->sum('subtotal'), 2), 'medication_vat' => round($lines->sum('vat'), 2),
            'medication_total_iva_included' => round($lines->sum('total'), 2),
            'service_base' => round($mixing->sum('subtotal'), 2), 'service_vat' => round($mixing->sum('vat'), 2),
            'service_total' => round($mixing->sum('total'), 2),
            'supplies_base' => 0, 'supplies_vat' => 0, 'supplies_total' => 0,
            'additional_charge_lines' => $additional, 'additional_charges_base' => round($additional->sum('subtotal'), 2),
            'additional_charges_vat' => round($additional->sum('vat'), 2),
            'subtotal_before_vat' => round($lines->sum('subtotal') + $services->sum('subtotal'), 2),
            'vat_total' => round($lines->sum('vat') + $services->sum('vat'), 2),
            'total_iva_included' => (float) $snapshot['total'],
        ];
        if ($mixture) {
            $mixture->loadMissing('medicamentos');
            foreach ($mixture->medicamentos->values() as $index => $medicine) {
                $line = $lines[$index];
                foreach ([
                    'denominacion_doc' => $line['description'], 'marca_doc' => $line['presentation'],
                    'unidad_cobro' => $line['unit'], 'cantidad_cobro' => $line['quantity'],
                    'precio_unitario_calculado' => $line['unit_price'], 'subtotal_calculado' => $line['subtotal'],
                    'iva_desglosado_doc' => $line['vat'] > 0, 'iva_calculado' => $line['vat'],
                    'subtotal_iva_incluido' => $line['total'], 'presentation_charge_lines' => collect(), 'warehouse_doc' => '',
                ] as $key => $value) $medicine->setAttribute($key, $value);
            }
            foreach ([
                'infusor_aplica' => false, 'infusor_nombre' => '', 'infusor_precio' => 0, 'infusor_subtotal' => 0,
                'mixing_service_base' => $summary['service_base'], 'mixing_service_vat' => $summary['service_vat'],
                'mixing_service_total' => $summary['service_total'], 'medication_vat' => $summary['medication_vat'],
                'supplies_vat' => 0, 'total_iva_included' => $summary['total_iva_included'],
            ] as $key => $value) $mixture->setAttribute($key, $value);
        }
        return $summary;
    }
}
