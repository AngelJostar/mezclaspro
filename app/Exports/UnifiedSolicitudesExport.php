<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnifiedSolicitudesExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping,
    WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Tipo', 'ID mezcla', 'No. solicitud', 'Hospital', 'Paciente',
            'Fecha y hora de solicitud', 'Fecha y hora programada de entrega',
            'Estado operativo', 'Lote', "Aprobaci\u{00F3}n"];
    }

    public function map($row): array
    {
        $status = str_replace('-', '_', mb_strtolower(trim((string) $row['status'])));
        $statusLabel = match ($status) {
            'aprobada' => 'Aprobada',
            'dispensada' => 'Dispensada',
            'preparada', 'enproceso' => 'Preparada',
            'revisada', 'inspeccionada' => 'Inspeccionada',
            'entregada', 'finalizada' => 'Entregada',
            'cancelada' => 'Cancelada',
            'no_aprobada' => 'No aprobada',
            default => 'Pendiente',
        };
        $approval = match (true) {
            in_array($status, ['aprobada', 'dispensada', 'preparada', 'revisada', 'entregada'], true) => 'Aprobada',
            in_array($status, ['cancelada', 'no_aprobada'], true) => 'Rechazada',
            default => "Sin acci\u{00F3}n",
        };

        return [
            $row['type_label'], $row['id'], $row['request_id'], $row['hospital'], $row['patient'],
            $row['requested_at'] ? Date::dateTimeToExcel($row['requested_at']) : null,
            $row['delivery_at'] ? Date::dateTimeToExcel($row['delivery_at']) : null,
            $statusLabel, $row['lot'], $approval,
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Keep lot identifiers intact and patient text out of Excel formulas.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return ['F' => 'yyyy-mm-dd hh:mm', 'G' => 'yyyy-mm-dd hh:mm'];
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 14, 'C' => 16, 'D' => 36, 'E' => 36,
            'F' => 24, 'G' => 28, 'H' => 20, 'I' => 22, 'J' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:J'.$sheet->getHighestRow());
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getRowDimension(1)->setRowHeight(34);

        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3C88']],
        ]];
    }

    public function title(): string
    {
        return 'Solicitudes';
    }
}
