<?php

namespace App\Exports;

use Carbon\Carbon;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CatalogExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping,
    WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(private string $section, private Collection $rows) {}

    public function collection(): Collection
    {
        if ($this->section !== 'consumibles') {
            return $this->rows;
        }

        return $this->rows->flatMap(fn ($item) => collect(
            $item->catalogPresentations->isEmpty() ? [null] : $item->catalogPresentations
        )->map(fn ($presentation) => (object) [
            'product' => $item->name,
            'presentation' => $presentation?->presentation ?: 'Sin presentaciones',
            'commercial_name' => $presentation?->commercial_name ?: '-',
            'manufacturer' => $presentation?->manufacturer ?: '-',
            'unit' => $item->unit,
        ]))->values();
    }

    private function columns(): array
    {
        if ($this->section === 'consumibles') {
            return ['product' => 'Insumo', 'presentation' => 'Presentacion',
                'commercial_name' => 'Nombre comercial', 'manufacturer' => 'Fabricante', 'unit' => 'Unidad'];
        }

        if ($this->section === 'diluyentes') {
            return ['product' => 'Insumo', 'dose' => 'Volumen', 'presentation' => 'Presentacion',
                'commercial_name' => 'Nombre comercial', 'manufacturer' => 'Fabricante', 'central' => 'Central',
                'warehouse' => 'Subalmacen de insumos', 'lot' => 'Lote', 'expiry_date' => 'Caducidad',
                'stock_actual' => 'Existencia', 'is_active' => 'Estado'];
        }

        return [
            ...($this->section === 'todos' ? ['category_label' => 'Categoria'] : []),
            'product' => 'Producto', 'dose' => 'Dosis', 'presentation' => 'Presentacion',
            'commercial_name' => 'Denominacion comercial', 'stability_hours' => 'Estabilidad reconstituido',
            'lowest_price' => 'Precio compra mas bajo', 'lowest_date' => 'Fecha compra mas baja',
            'last_price' => 'Ultimo precio de compra', 'last_date' => 'Fecha ultima compra',
        ];
    }

    public function headings(): array
    {
        return array_values($this->columns());
    }

    public function map($row): array
    {
        return array_map(function ($key) use ($row) {
            $value = $row->{$key} ?? null;

            return match ($key) {
                'lowest_price', 'last_price', 'stock_actual' => $value === null ? '-' : (float) $value,
                'lowest_date', 'last_date', 'expiry_date' => $value
                    ? Date::dateTimeToExcel(Carbon::parse($value)->startOfDay()) : '-',
                'stability_hours' => (int) $value > 0 ? (int) $value : 'Sin capturar',
                'is_active' => $value ? 'Activo' : 'Inactivo',
                default => $value ?? '-',
            };
        }, array_keys($this->columns()));
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Preserve lot identifiers and never interpret catalog text as an Excel formula.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (array_keys($this->columns()) as $index => $key) {
            $format = match ($key) {
                'lowest_price', 'last_price' => '"$"#,##0.00',
                'stock_actual' => '#,##0.00',
                'stability_hours' => '#,##0" h"',
                'lowest_date', 'last_date', 'expiry_date' => 'dd/mm/yyyy',
                default => null,
            };
            if ($format !== null) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = $format;
            }
        }

        return $formats;
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach (array_keys($this->columns()) as $index => $key) {
            $widths[Coordinate::stringFromColumnIndex($index + 1)] = match ($key) {
                'product', 'presentation', 'commercial_name' => 38,
                'manufacturer', 'central', 'warehouse' => 28,
                'dose', 'stock_actual', 'is_active', 'unit' => 18,
                default => 24,
            };
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.$sheet->getHighestRow());

        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3C88']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]];
    }

    public function title(): string
    {
        return 'Catalogo '.ucfirst($this->section);
    }
}
