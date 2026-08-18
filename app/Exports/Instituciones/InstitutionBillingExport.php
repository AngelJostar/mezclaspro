<?php

namespace App\Exports\Instituciones;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InstitutionBillingExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return [
            'INSTITUCION',
            'Unidad',
            'Nombre del Medico',
            'Nombre del Paciente',
            'No. de remision',
            'Fecha de remision',
            'Cantidad',
            'Cantidad de Frascos',
            'Descripcion',
            'P.V. unitario antes de IVA',
            'P.V. total IVA Incluido',
            'Empresa',
            'Precio Total',
            'Coinciliable',
            'Folio Factura UUID',
            'Folio Factura Interno',
            'Fecha Facturacion',
            'Numero Carta Factura',
            'Fecha Carta Factura',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
