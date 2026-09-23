<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithStyles, WithColumnWidths, WithStrictNullComparison};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestQuotationsExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping,
    WithCustomValueBinder, WithColumnFormatting, WithStyles, WithColumnWidths, WithStrictNullComparison
{
    public function __construct(private Collection $rows) {}
    public function collection(): Collection { return $this->rows; }
    public function headings(): array
    {
        return ['Folio', 'Fecha', 'Tipo', 'Institucion', 'Hospital', 'Paciente', 'Vendedor', 'Lista de precios',
            'Total MXN', 'Estado', 'Autorizado por', 'Fecha de autorizacion', 'Solicitud'];
    }
    public function map($row): array
    {
        return [$row->folio, $row->created_at ? Date::dateTimeToExcel($row->created_at) : null,
            $row->category, $row->institution?->nombre, $row->hospital?->name, $row->patient_name,
            $row->seller_name, $row->price_list_name, $row->total === null ? null : (float) $row->total, $row->status_label,
            $row->authorized_at ? trim($row->authorizer?->name.' '.$row->authorizer?->lastname) : null,
            $row->authorized_at ? Date::dateTimeToExcel($row->authorized_at) : null,
            $row->request_id ? 'SOL-'.str_pad((string) $row->request_id, 5, '0', STR_PAD_LEFT) : null];
    }
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
    public function columnFormats(): array { return ['B' => 'dd/mm/yyyy hh:mm', 'I' => '"$"#,##0.00', 'L' => 'dd/mm/yyyy hh:mm']; }
    public function columnWidths(): array { return ['A' => 18, 'B' => 22, 'C' => 18, 'D' => 36, 'E' => 36, 'F' => 36, 'G' => 30, 'H' => 30, 'I' => 18, 'J' => 20, 'K' => 28, 'L' => 22, 'M' => 18]; }
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M'.$sheet->getHighestRow());
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true)->setVertical('top');
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '233C80']]]];
    }
}
