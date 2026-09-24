<?php

namespace App\Services;

use App\Models\RequestQuotation;
use App\Support\QuotationDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class RequestQuotationPdf
{
    public static function filename(RequestQuotation $quotation): string
    {
        return $quotation->folio.'.pdf';
    }

    public function data(RequestQuotation $quotation): array
    {
        // Export only the saved commercial amounts, never clinical data or signatures.
        $lines = array_map(static fn (array $line) => [
            'description' => $line['description'] ?? '',
            'mixture_number' => $line['mixture_number'] ?? null,
            'presentation' => $line['presentation'] ?? '',
            'quantity' => isset($line['quantity']) ? (float) $line['quantity'] * ($line['mixtures'] ?? 1) : null,
            'unit' => match ($line['unit'] ?? '') {
                'frasco' => 'Frasco', 'ml' => 'mL', 'mg' => 'mg', 'servicio' => 'Servicio', default => $line['unit'] ?? '',
            },
            'unit_price' => $line['unit_price'] ?? null,
            'vat' => $line['vat'] ?? null,
            'total' => $line['total'] ?? null,
        ], $quotation->pricing_snapshot['lines'] ?? []);

        return [
            'issuer' => QuotationDocument::issuer(true),
            'folio' => $quotation->folio,
            'hospital' => $quotation->hospital?->name ?? 'Sin hospital',
            'institution' => $quotation->institution?->nombre ?? '',
            'document' => QuotationDocument::metadata(['total' => $quotation->total], $quotation->created_at),
            'lines' => $lines,
            'total' => $quotation->total,
            'vat' => array_sum(array_column($lines, 'vat')),
        ];
    }

    public function render(RequestQuotation $quotation): string
    {
        return Pdf::loadView('admin.solicitudes.quotations.pdf', $this->data($quotation))
            ->setPaper('letter', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->output();
    }
}
