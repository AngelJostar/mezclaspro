<?php

namespace App\Support;

use App\Models\RequestQuotation;

class QuotationMessage
{
    public static function summary(RequestQuotation $quotation): string
    {
        $money = fn ($value) => $value === null ? 'Sin registrar' : '$'.number_format((float) $value, 2).' MXN';
        $lines = [
            'PROMESA | Cotizacion '.$quotation->folio,
            'Fecha: '.($quotation->created_at?->format('d/m/Y') ?? 'Sin registrar'),
            'Institucion: '.($quotation->institution?->nombre ?? 'Sin registrar'),
            'Hospital: '.($quotation->hospital?->name ?? 'Sin registrar'),
            'Lista de precios: '.($quotation->price_list_name ?: 'Sin registrar'),
            'Estado: '.$quotation->status_label,
        ];

        // Share only the saved commercial breakdown, never clinical records or signatures.
        foreach ($quotation->pricing_snapshot['lines'] ?? [] as $index => $item) {
            $lines[] = '';
            $lines[] = ($index + 1).'. '.trim(($item['description'] ?? '').' '.($item['presentation'] ?? ''));
            $lines[] = 'Cantidad: '.($item['quantity'] ?? '').' '.($item['unit'] ?? '').' | Mezclas: '.($item['mixtures'] ?? 1);
            $lines[] = 'Precio unitario: '.(isset($item['unit_price']) ? '$'.number_format((float) $item['unit_price'], 4).' MXN' : 'Sin registrar');
            $lines[] = 'IVA: '.$money($item['vat'] ?? null).' | Importe: '.$money($item['total'] ?? null);
        }

        $lines[] = '';
        $lines[] = 'Total: '.$money($quotation->total);

        return implode("\n", $lines);
    }
}
