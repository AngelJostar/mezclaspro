<?php

namespace App\Exports;

use App\Support\HospitalAdjustmentLogTable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HospitalAdjustmentLogExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping,
    WithCustomValueBinder, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection { return $this->rows; }

    public function headings(): array
    {
        return [...array_map(fn ($field) => implode(' ', HospitalAdjustmentLogTable::COLUMNS[$field]), HospitalAdjustmentLogTable::fields()), 'Version'];
    }

    public function map($row): array
    {
        $cells = HospitalAdjustmentLogTable::values($row);
        return [...array_map(fn ($field) => $cells[$field], HospitalAdjustmentLogTable::fields()), $row['version']->id];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Hospital-provided names and adjustment reasons must remain text, never formulas.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 14, 'C' => 16, 'D' => 40, 'E' => 36, 'F' => 36, 'G' => 24, 'H' => 30,
            'I' => 20, 'J' => 18, 'K' => 22, 'L' => 24, 'M' => 55, 'N' => 26, 'O' => 14];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:O'.$sheet->getHighestRow());
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getRowDimension(1)->setRowHeight(30);
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '303F7C']]]];
    }

    public function title(): string { return 'Bitacora de ajustes'; }
}
