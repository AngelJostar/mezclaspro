<?php

namespace App\Exports\Instituciones;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InstitutionBillingExpandedExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return [
            'INSTITUCION',
            'UNIDAD',
            'NOMBRE DEL MEDICO',
            'NOMBRE DEL PACIENTE',
            'NO. DE REMISION',
            'FECHA DE REMISION',
            'TIPO DE MEZCLA',
            'SERVICIO',
            'REGISTRO DEL PACIENTE',
            'RENGLON',
            'TIPO DE CONCEPTO',
            'DESCRIPCION DEL CONCEPTO',
            'CANTIDAD',
            'UNIDAD DE COBRO',
            'P.V. UNITARIO ANTES DE IVA',
            'SUBTOTAL ANTES DE IVA',
            'IVA',
            'TOTAL IVA INCLUIDO DEL CONCEPTO',
            'TOTAL IVA INCLUIDO DE LA SOLICITUD',
            'EMPRESA',
            'PRECIO TOTAL CAPTURADO',
            'CONCILIABLE',
            'FOLIO FACTURA UUID',
            'FOLIO FACTURA INTERNO',
            'FECHA FACTURACION',
            'NUMERO CARTA FACTURA',
            'FECHA CARTA FACTURA',
            'ESTATUS FACTURACION',
            'VENCIMIENTO',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
