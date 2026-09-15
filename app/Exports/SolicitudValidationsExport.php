<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SolicitudValidationsExport extends DefaultValueBinder implements FromArray, WithHeadings, WithStyles, WithTitle, WithCustomValueBinder
{
    public function __construct(private Collection $rows, private bool $isHospitalView = false) {}

    public function array(): array
    {
        return $this->rows->map(function (array $row) {
            $values = [$row['type_label'], $row['id'], $row['request_id'], $row['hospital'], $row['patient'],
                $row['requested_at']?->format('Y-m-d H:i'), $row['delivery_at']?->format('Y-m-d H:i'),
                $row['status'] === 'cancelada' ? 'Cancelada' : 'No aprobada', $row['lot'], 'Rechazada'];

            return $this->isHospitalView ? $values : [...$values, $row['validation_type'], $row['observations']];
        })->all();
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        $headings = ['Categoría', 'ID mezcla', 'No. solicitud', 'Hospital', 'Paciente',
            'Fecha y hora de solicitud', 'Fecha y hora programada de entrega',
            'Estado operativo', 'Lote', 'Aprobación', 'Tipo', 'Observaciones'];

        return $this->isHospitalView ? array_slice($headings, 0, 10) : $headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $lastColumn = $this->isHospitalView ? 'J' : 'L';
        $sheet->setAutoFilter('A1:'.$lastColumn.$sheet->getHighestRow());
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth(24);
        }
        if (!$this->isHospitalView) $sheet->getColumnDimension('L')->setWidth(48);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);

        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F4382']],
        ]];
    }

    public function title(): string
    {
        return 'Validaciones';
    }
}
