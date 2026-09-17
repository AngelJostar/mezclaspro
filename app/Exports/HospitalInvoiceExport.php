<?php

namespace App\Exports;

use App\Services\HospitalInvoiceLedger;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithCustomValueBinder, WithStyles, WithColumnWidths};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HospitalInvoiceExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder, WithStyles, WithColumnWidths
{
    public function __construct(private Collection $rows) {}
    public function collection(): Collection { return $this->rows; }
    public function headings(): array { return ['Factura', 'Emision', 'Vencimiento', 'Importe MXN', 'Pagos aplicados MXN', 'Saldo MXN', 'Estado de pago', 'Dias vencida', 'Aclaracion']; }
    public function map($row): array
    {
        return [$row['folio'], $row['date']?->format('Y-m-d'), $row['due']?->format('Y-m-d'),
            $row['total'] === null ? null : $row['total'] / 100, $row['paid'] / 100, $row['balance'] === null ? null : $row['balance'] / 100,
            ['pending' => 'Pendiente', 'partial' => 'Pago parcial', 'paid' => 'Pagada', 'unknown' => 'Sin importe'][$row['state']],
            $row['days_overdue'], HospitalInvoiceLedger::CLARIFICATIONS[$row['clarification']] ?? 'Sin aclaracion'];
    }
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
    public function columnWidths(): array { return ['A'=>25, 'B'=>18, 'C'=>18, 'D'=>22, 'E'=>24, 'F'=>22, 'G'=>20, 'H'=>15, 'I'=>24]; }
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I'.$sheet->getHighestRow());
        $sheet->getStyle('D2:F'.max(2, $sheet->getHighestRow()))->getNumberFormat()->setFormatCode('"$"#,##0.00');
        return [1 => ['font' => ['bold' => true]]];
    }
}
