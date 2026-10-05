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
            'Hospital',
            'Nombre del Medico',
            'Nombre del Paciente',
            'No. de remision',
            'Fecha de remision',
            'Cantidad',
            'Unidad',
            'Descripcion',
            'Precio unitario IVA incluido',
            'P.V. total IVA Incluido',
            'Empresa',
            'Precio de venta total editable',
            'Coinciliable',
            'Folio Factura UUID',
            'Folio Factura Interno',
            'Fecha de factura',
            'Numero Carta Factura',
            'Fecha Carta Factura',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
