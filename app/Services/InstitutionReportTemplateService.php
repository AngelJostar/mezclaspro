<?php

namespace App\Services;

use App\Models\InstitutionReportTemplate;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionReportTemplateService
{
    public const GENERAL = 'institution_general';

    public const HOSPITAL = 'hospital_summary';

    public const HOSPITAL_DETAIL = 'hospital_detail';

    public const DAILY_PATIENT = 'daily_patient';

    public const MONTHLY_SUPPLIES = 'monthly_supplies';

    private array $resolved = [];

    public function all(): array
    {
        return collect(array_keys($this->definitions()))
            ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
            ->all();
    }

    public function get(string $key): array
    {
        $this->assertKnownKey($key);

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $definition = $this->definitions()[$key];
        $stored = InstitutionReportTemplate::query()->where('report_key', $key)->first();
        $defaultColumns = collect($definition['columns'])->keyBy('key');
        $columns = collect($stored?->columns ?? [])
            ->filter(fn ($column) => is_array($column) && $defaultColumns->has($column['key'] ?? null))
            ->unique('key')
            ->map(function (array $column) use ($defaultColumns) {
                $default = $defaultColumns->get($column['key']);

                return [
                    'key' => $column['key'],
                    'label' => trim((string) ($column['label'] ?? '')) ?: $default['label'],
                    'visible' => array_key_exists('visible', $column) ? (bool) $column['visible'] : true,
                    'sample' => $default['sample'],
                ];
            });

        foreach ($definition['columns'] as $column) {
            if (! $columns->contains('key', $column['key'])) {
                $columns->push($column + ['visible' => true]);
            }
        }

        if ($key === self::DAILY_PATIENT) {
            $dailyQuantity = $columns->firstWhere('key', 'cantidad_dia');
            $columns = $columns
                ->reject(fn (array $column) => $column['key'] === 'cantidad_dia')
                ->prepend($dailyQuantity);
        }

        return $this->resolved[$key] = [
            'key' => $key,
            'name' => $stored?->name ?: $definition['name'],
            'title' => $stored?->title ?: $definition['title'],
            'subtitle' => $stored?->subtitle ?? $definition['subtitle'],
            'columns' => $columns->values()->all(),
            'info_boxes' => $this->normalizeFields($stored?->info_boxes ?? $definition['info_boxes']),
            'free_fields' => $this->normalizeFields($stored?->free_fields ?? []),
            'dynamic_columns_note' => $definition['dynamic_columns_note'] ?? null,
        ];
    }

    public function save(string $key, array $data): array
    {
        $this->assertKnownKey($key);
        $allowedKeys = collect($this->definitions()[$key]['columns'])->pluck('key');
        $columns = collect($data['columns'])
            ->filter(fn ($column) => $allowedKeys->contains($column['key'] ?? null))
            ->unique('key')
            ->map(fn ($column) => [
                'key' => $column['key'],
                'label' => trim((string) $column['label']),
                'visible' => (bool) $column['visible'],
            ])
            ->values()
            ->all();

        InstitutionReportTemplate::query()->updateOrCreate(
            ['report_key' => $key],
            [
                'title' => trim((string) $data['title']),
                'subtitle' => trim((string) ($data['subtitle'] ?? '')),
                'columns' => $columns,
                'info_boxes' => $this->normalizeFields($data['info_boxes'] ?? []),
                'free_fields' => $this->normalizeFields($data['free_fields'] ?? []),
            ]
        );

        unset($this->resolved[$key]);

        return $this->get($key);
    }

    public function rename(string $key, string $name): array
    {
        $this->assertKnownKey($key);
        $current = $this->get($key);

        $this->save($key, $current);

        InstitutionReportTemplate::query()
            ->where('report_key', $key)
            ->update(['name' => trim($name)]);

        unset($this->resolved[$key]);

        return $this->get($key);
    }

    public function allowedColumnKeys(string $key): array
    {
        $this->assertKnownKey($key);

        return collect($this->definitions()[$key]['columns'])->pluck('key')->all();
    }

    public function projectRows(string $key, array $rows): array
    {
        $defaultIndexes = collect($this->definitions()[$key]['columns'])
            ->values()
            ->mapWithKeys(fn (array $column, int $index) => [$column['key'] => $index]);

        return collect($rows)->map(function (array $row) use ($key, $defaultIndexes) {
            return collect($this->visibleColumns($key))
                ->map(fn (array $column) => $row[$defaultIndexes[$column['key']]] ?? '')
                ->all();
        })->all();
    }

    public function projectRowsWithTrailing(string $key, array $rows, int $baseColumnCount): array
    {
        $defaultIndexes = collect($this->definitions()[$key]['columns'])
            ->values()
            ->mapWithKeys(fn (array $column, int $index) => [$column['key'] => $index]);

        return collect($rows)->map(function (array $row) use ($key, $defaultIndexes, $baseColumnCount) {
            $projected = collect($this->visibleColumns($key))
                ->map(fn (array $column) => $row[$defaultIndexes[$column['key']]] ?? '')
                ->all();

            return array_merge($projected, array_slice($row, $baseColumnCount));
        })->all();
    }

    public function headingRows(string $key, array $context = [], array $trailingHeadings = []): array
    {
        $headings = array_merge(
            collect($this->visibleColumns($key))->pluck('label')->all(),
            $trailingHeadings
        );
        $columnCount = max(1, count($headings));
        $rows = collect($this->metadataRows($key, $context))
            ->map(fn (array $row) => array_pad(array_slice($row, 0, $columnCount), $columnCount, ''))
            ->all();
        $rows[] = $headings;

        return $rows;
    }

    public function headerRow(string $key, array $context = []): int
    {
        return count($this->metadataRows($key, $context)) + 1;
    }

    public function metadataOffset(string $key, array $context = []): int
    {
        return count($this->metadataRows($key, $context));
    }

    public function visibleColumns(string $key): array
    {
        return collect($this->get($key)['columns'])
            ->where('visible', true)
            ->values()
            ->all();
    }

    public function columnLetter(string $key, string $columnKey): ?string
    {
        $index = collect($this->visibleColumns($key))->search(
            fn (array $column) => $column['key'] === $columnKey
        );

        return $index === false ? null : Coordinate::stringFromColumnIndex($index + 1);
    }

    public function columnLetterFromIndex(int $index): string
    {
        return Coordinate::stringFromColumnIndex(max(1, $index));
    }

    public function styleWorksheet(
        Worksheet $sheet,
        string $key,
        array $context = [],
        string $headerColor = 'D9E5F3',
        string $headerTextColor = '1F3B64',
        int $trailingColumnCount = 0
    ): int {
        $template = $this->get($key);
        $headerRow = $this->headerRow($key, $context);
        $columnCount = max(1, count($this->visibleColumns($key)) + $trailingColumnCount);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $currentRow = 1;

        $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
        $sheet->getStyle("A{$currentRow}:{$lastColumn}{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '172033']],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;

        if (trim((string) $template['subtitle']) !== '') {
            $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
            $sheet->getStyle("A{$currentRow}:{$lastColumn}{$currentRow}")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                'alignment' => ['wrapText' => true, 'vertical' => 'center'],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $currentRow++;
        }

        foreach (array_merge($template['info_boxes'], $template['free_fields']) as $field) {
            if ($lastColumn !== 'A') {
                $sheet->mergeCells("B{$currentRow}:{$lastColumn}{$currentRow}");
            }
            $sheet->getStyle("A{$currentRow}:{$lastColumn}{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                'font' => ['color' => ['rgb' => '334155']],
                'alignment' => ['wrapText' => true, 'vertical' => 'center'],
            ]);
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $currentRow++;
        }

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $headerTextColor], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(30);
        $sheet->freezePane('A'.($headerRow + 1));

        return $headerRow;
    }

    private function metadataRows(string $key, array $context): array
    {
        $template = $this->get($key);
        $rows = [[$this->replaceTokens($template['title'], $context)]];

        if (trim((string) $template['subtitle']) !== '') {
            $rows[] = [$this->replaceTokens($template['subtitle'], $context)];
        }

        foreach (array_merge($template['info_boxes'], $template['free_fields']) as $field) {
            $rows[] = [
                $this->replaceTokens($field['label'], $context),
                $this->replaceTokens($field['value'], $context),
            ];
        }

        $rows[] = [];

        return $rows;
    }

    private function replaceTokens(string $value, array $context): string
    {
        return strtr($value, collect($context)->mapWithKeys(
            fn ($replacement, $token) => ['{{'.$token.'}}' => (string) $replacement]
        )->all());
    }

    private function normalizeFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($field) => is_array($field))
            ->map(fn (array $field) => [
                'label' => trim((string) Arr::get($field, 'label', '')),
                'value' => trim((string) Arr::get($field, 'value', '')),
            ])
            ->filter(fn (array $field) => $field['label'] !== '' || $field['value'] !== '')
            ->values()
            ->all();
    }

    private function assertKnownKey(string $key): void
    {
        abort_unless(array_key_exists($key, $this->definitions()), 404);
    }

    private function definitions(): array
    {
        $billingColumns = [
            $this->column('institucion', 'INSTITUCIÓN', 'Operadora de Hospitales'),
            $this->column('unidad', 'UNIDAD', 'Hospital Central'),
            $this->column('medico', 'NOMBRE DEL MÉDICO', 'Dra. María López'),
            $this->column('paciente', 'NOMBRE DEL PACIENTE', 'Paciente de ejemplo'),
            $this->column('remision', 'NO. DE REMISIÓN', 'R-00125'),
            $this->column('fecha_remision', 'FECHA DE REMISIÓN', '11/08/2026'),
            $this->column('cantidad', 'CANTIDAD', '250 mg'),
            $this->column('descripcion', 'DESCRIPCIÓN', 'Medicamento y presentación'),
            $this->column('pv_unitario', 'P.V. UNITARIO ANTES DE IVA', '$125.00'),
            $this->column('pv_total', 'P.V. TOTAL IVA INCLUIDO', '$31,250.00'),
            $this->column('empresa', 'EMPRESA', 'EMP010101AA1'),
            $this->column('precio_total', 'PRECIO TOTAL', '$31,250.00'),
            $this->column('conciliable', 'CONCILIABLE', 'Sí'),
            $this->column('folio_uuid', 'FOLIO FACTURA UUID', 'UUID-0001'),
            $this->column('folio_interno', 'FOLIO FACTURA INTERNO', 'F-2026-001'),
            $this->column('fecha_facturacion', 'FECHA FACTURACIÓN', '12/08/2026'),
            $this->column('numero_carta', 'NÚMERO CARTA FACTURA', 'CF-001'),
            $this->column('fecha_carta', 'FECHA CARTA FACTURA', '12/08/2026'),
            $this->column('fecha_compensacion', 'FECHA DE COMPENSACIÓN', '15/08/2026'),
        ];
        $generalBillingColumns = array_values(array_filter(
            $billingColumns,
            fn (array $column) => $column['key'] !== 'precio_total'
        ));
        $hospitalBillingColumns = array_values(array_filter(
            $billingColumns,
            fn (array $column) => $column['key'] !== 'precio_total'
        ));

        return [
            self::GENERAL => [
                'name' => 'Reporte de institución',
                'title' => 'Reporte general de institución',
                'subtitle' => 'Relación consolidada de mezclas, remisiones y facturación.',
                'columns' => $generalBillingColumns,
                'info_boxes' => [
                    ['label' => 'Institución', 'value' => '{{institucion}}'],
                    ['label' => 'Fecha de generación', 'value' => '{{fecha_generacion}}'],
                ],
            ],
            self::HOSPITAL => [
                'name' => 'Reporte por hospital',
                'title' => 'Reporte por hospital',
                'subtitle' => 'Relación de solicitudes y facturación agrupada por unidad hospitalaria.',
                'columns' => $hospitalBillingColumns,
                'info_boxes' => [
                    ['label' => 'Institución', 'value' => '{{institucion}}'],
                    ['label' => 'Fecha de generación', 'value' => '{{fecha_generacion}}'],
                ],
            ],
            self::HOSPITAL_DETAIL => [
                'name' => 'Reporte por hospital con detalle',
                'title' => 'Reporte por hospital con detalle',
                'subtitle' => 'Detalle operativo por servicio y producto preparado.',
                'columns' => [
                    $this->column('id', 'ID', '125'),
                    $this->column('remision', 'REMISIÓN', 'R-00125'),
                    $this->column('lote', 'LOTE', 'L110826001'),
                    $this->column('hospital', 'HOSPITAL', 'Hospital Central'),
                    $this->column('paciente', 'PACIENTE', 'Paciente de ejemplo'),
                    $this->column('servicio', 'SERVICIO', 'Oncología'),
                    $this->column('registro', 'REGISTRO', 'EXP-1024'),
                    $this->column('diagnostico', 'DIAGNÓSTICO', 'Diagnóstico registrado'),
                    $this->column('edad', 'EDAD', '48'),
                    $this->column('sexo', 'SEXO', 'F'),
                    $this->column('peso', 'PESO', '67 kg'),
                    $this->column('cama', 'CAMA', '203'),
                    $this->column('sobrellenado', 'SOBRELLENADO', '5 ml'),
                    $this->column('volumen_total', 'VOLUMEN TOTAL', '250 ml'),
                    $this->column('npt', 'NPT', 'Sí'),
                    $this->column('fecha_solicitud', 'FECHA DE SOLICITUD', '11/08/2026'),
                    $this->column('medico', 'NOMBRE DEL MÉDICO', 'Dra. María López'),
                    $this->column('cedula', 'CÉDULA PROFESIONAL', '1234567'),
                    $this->column('observaciones', 'OBSERVACIONES', 'Sin observaciones'),
                    $this->column('estatus', 'ESTATUS', 'Entregada'),
                ],
                'info_boxes' => [
                    ['label' => 'Institución', 'value' => '{{institucion}}'],
                    ['label' => 'Hoja', 'value' => '{{hoja}}'],
                ],
                'dynamic_columns_note' => 'Las columnas de productos se agregan automáticamente después de los campos seleccionados.',
            ],
            self::DAILY_PATIENT => [
                'name' => 'Reporte diario por paciente',
                'title' => 'Reporte diario por paciente',
                'subtitle' => 'Mezclas nutricionales conciliables entregadas, agrupadas por día.',
                'columns' => [
                    $this->column('cantidad_dia', 'CANTIDAD POR DÍA', '1'),
                    $this->column('fecha', 'FECHA', '11/08/2026'),
                    $this->column('lote', 'LOTE', 'L110826001'),
                    $this->column('paciente', 'PACIENTE', 'Paciente de ejemplo'),
                    $this->column('medico', 'MÉDICO', 'Dra. María López'),
                    $this->column('nacimiento', 'FECHA DE NACIMIENTO', '18/04/1984'),
                    $this->column('costo', 'COSTO', '$2,850.00'),
                ],
                'info_boxes' => [
                    ['label' => 'Hospital', 'value' => '{{hospital}}'],
                    ['label' => 'Fecha de generación', 'value' => '{{fecha_generacion}}'],
                ],
            ],
            self::MONTHLY_SUPPLIES => [
                'name' => 'Reporte mensual de insumos por hospital',
                'title' => 'Reporte mensual de insumos por hospital',
                'subtitle' => 'Consumo mensual consolidado de insumos de nutrición parenteral.',
                'columns' => [
                    $this->column('descripcion', 'DESCRIPCIÓN', 'Aminoácidos pediátricos 10%'),
                    $this->column('unidad', 'UNIDAD', 'ML'),
                    $this->column('cantidad', 'CANTIDAD', '438.37'),
                ],
                'info_boxes' => [
                    ['label' => 'Hospital', 'value' => '{{hospital}}'],
                    ['label' => 'Periodo', 'value' => '{{periodo}}'],
                ],
            ],
        ];
    }

    private function column(string $key, string $label, string $sample): array
    {
        return compact('key', 'label', 'sample') + ['visible' => true];
    }
}
