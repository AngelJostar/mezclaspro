<?php

namespace App\Services;

use App\Models\InstitutionReportTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    private const CUSTOM_FONT_FAMILIES = [
        'Arial',
        'Calibri',
        'Figtree',
        'Georgia',
        'Tahoma',
        'Times New Roman',
        'Verdana',
    ];

    public function all(): array
    {
        return collect(array_keys($this->definitions()))
            ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
            ->all();
    }

    public function customTemplates(): array
    {
        return InstitutionReportTemplate::query()
            ->where('is_custom', true)
            ->with('creator:id,name,lastname,username')
            ->latest('updated_at')
            ->get()
            ->map(fn (InstitutionReportTemplate $template) => $this->serializeCustomTemplate($template))
            ->all();
    }

    public function publishedCustomTemplates(): array
    {
        return InstitutionReportTemplate::query()
            ->where('is_custom', true)
            ->where('is_published', true)
            ->with('creator:id,name,lastname,username')
            ->orderBy('published_at')
            ->orderBy('id')
            ->get()
            ->map(fn (InstitutionReportTemplate $template) => $this->serializeCustomTemplate($template))
            ->all();
    }

    public function customDataSources(): array
    {
        return [
            'instituciones' => 'Instituciones y hospitales',
            'solicitudes' => 'Solicitudes y mezclas',
            'inventarios' => 'Inventarios y productos',
            'facturacion' => 'Facturación y remisiones',
        ];
    }

    public function catalogParameters(): array
    {
        return [
            [
                'label' => 'Institución',
                'parameters' => [
                    ['key' => 'institution.name', 'label' => 'Nombre de la institución', 'sample' => 'Hospitales Metropolitanos'],
                    ['key' => 'institution.legal_name', 'label' => 'Razón social', 'sample' => 'Operadora Hospitalaria, S.A. de C.V.'],
                    ['key' => 'institution.rfc', 'label' => 'RFC', 'sample' => 'OHO010101AB1'],
                    ['key' => 'institution.phone', 'label' => 'Teléfono', 'sample' => '55 1234 5678'],
                    ['key' => 'institution.hospitals_count', 'label' => 'Número de hospitales', 'sample' => '4'],
                ],
            ],
            [
                'label' => 'Hospital',
                'parameters' => [
                    ['key' => 'hospital.name', 'label' => 'Nombre del hospital', 'sample' => 'Hospital Central'],
                    ['key' => 'hospital.service', 'label' => 'Servicio', 'sample' => 'Oncología médica'],
                    ['key' => 'hospital.address', 'label' => 'Dirección', 'sample' => 'Av. Principal 120'],
                    ['key' => 'hospital.city', 'label' => 'Ciudad', 'sample' => 'Ciudad de México'],
                    ['key' => 'hospital.state', 'label' => 'Estado', 'sample' => 'CDMX'],
                ],
            ],
            [
                'label' => 'Solicitud',
                'parameters' => [
                    ['key' => 'request.id', 'label' => 'Folio de solicitud', 'sample' => 'SOL-00125'],
                    ['key' => 'request.type', 'label' => 'Tipo de mezcla', 'sample' => 'Oncológica'],
                    ['key' => 'request.requested_at', 'label' => 'Fecha de solicitud', 'sample' => '31/08/2026 08:15'],
                    ['key' => 'request.delivery_at', 'label' => 'Fecha de entrega', 'sample' => '31/08/2026 13:30'],
                    ['key' => 'request.status', 'label' => 'Estado operativo', 'sample' => 'Entregada'],
                    ['key' => 'request.patient', 'label' => 'Paciente', 'sample' => 'Paciente de ejemplo'],
                    ['key' => 'request.physician', 'label' => 'Médico tratante', 'sample' => 'Dra. María López'],
                    ['key' => 'request.diagnosis', 'label' => 'Diagnóstico', 'sample' => 'Diagnóstico registrado'],
                    ['key' => 'request.record', 'label' => 'Registro / expediente', 'sample' => 'EXP-1024'],
                ],
            ],
            [
                'label' => 'Mezcla y producto',
                'parameters' => [
                    ['key' => 'mixture.remission', 'label' => 'Número de remisión', 'sample' => 'REM-00125'],
                    ['key' => 'mixture.lot', 'label' => 'Lote de mezcla', 'sample' => 'L31AGO26001'],
                    ['key' => 'mixture.product', 'label' => 'Producto / medicamento', 'sample' => 'Oxaliplatino'],
                    ['key' => 'mixture.presentation', 'label' => 'Presentación', 'sample' => 'Frasco 100 mg'],
                    ['key' => 'mixture.quantity', 'label' => 'Cantidad', 'sample' => '250'],
                    ['key' => 'mixture.unit', 'label' => 'Unidad', 'sample' => 'mg'],
                    ['key' => 'mixture.warehouse', 'label' => 'Almacén de surtido', 'sample' => 'IMSS Norte'],
                ],
            ],
            [
                'label' => 'Facturación',
                'parameters' => [
                    ['key' => 'billing.unit_price', 'label' => 'Precio unitario', 'sample' => '$1,250.00'],
                    ['key' => 'billing.total', 'label' => 'Total IVA incluido', 'sample' => '$3,625.00'],
                    ['key' => 'billing.invoice', 'label' => 'Folio de factura', 'sample' => 'F-2026-001'],
                    ['key' => 'billing.status', 'label' => 'Estado de facturación', 'sample' => 'Por cobrar'],
                ],
            ],
            [
                'label' => 'Sistema',
                'parameters' => [
                    ['key' => 'system.generated_at', 'label' => 'Fecha de generación', 'sample' => now()->format('d/m/Y H:i')],
                    ['key' => 'system.period_from', 'label' => 'Periodo desde', 'sample' => now()->startOfMonth()->format('d/m/Y')],
                    ['key' => 'system.period_to', 'label' => 'Periodo hasta', 'sample' => now()->endOfMonth()->format('d/m/Y')],
                    ['key' => 'system.user_name', 'label' => 'Usuario que genera', 'sample' => 'Usuario administrador'],
                ],
            ],
        ];
    }

    public function allowedCustomParameterKeys(): array
    {
        return collect($this->catalogParameters())
            ->flatMap(fn (array $group) => $group['parameters'])
            ->pluck('key')
            ->all();
    }

    public function createCustom(array $data, ?int $userId): array
    {
        do {
            $key = 'custom_'.Str::lower(Str::random(16));
        } while (InstitutionReportTemplate::query()->where('report_key', $key)->exists());

        $template = InstitutionReportTemplate::query()->create([
            'report_key' => $key,
            'name' => trim((string) $data['name']),
            'is_custom' => true,
            'data_source' => $data['data_source'],
            'title' => trim((string) $data['name']),
            'subtitle' => trim((string) ($data['description'] ?? '')),
            'columns' => [],
            'info_boxes' => [],
            'free_fields' => [],
            'layout' => $this->normalizeCustomLayout($data['layout']),
            'created_by' => $userId,
        ]);

        return $this->serializeCustomTemplate($template->load('creator:id,name,lastname,username'));
    }

    public function updateCustom(InstitutionReportTemplate $template, array $data): array
    {
        abort_unless($template->is_custom, 404);

        $template->update([
            'name' => trim((string) $data['name']),
            'data_source' => $data['data_source'],
            'title' => trim((string) $data['name']),
            'subtitle' => trim((string) ($data['description'] ?? '')),
            'layout' => $this->normalizeCustomLayout($data['layout']),
        ]);

        return $this->serializeCustomTemplate($template->refresh()->load('creator:id,name,lastname,username'));
    }

    public function deleteCustom(InstitutionReportTemplate $template): void
    {
        abort_unless($template->is_custom, 404);
        $template->delete();
    }

    public function publishCustom(InstitutionReportTemplate $template, bool $isPublished): array
    {
        abort_unless($template->is_custom, 404);

        $template->update([
            'is_published' => $isPublished,
            'published_at' => $isPublished
                ? ($template->published_at ?? now())
                : null,
        ]);

        return $this->serializeCustomTemplate(
            $template->refresh()->load('creator:id,name,lastname,username')
        );
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

    private function serializeCustomTemplate(InstitutionReportTemplate $template): array
    {
        $creatorName = trim(implode(' ', array_filter([
            $template->creator?->name,
            $template->creator?->lastname,
        ])));

        return [
            'id' => $template->id,
            'key' => $template->report_key,
            'name' => $template->name ?: $template->title,
            'is_published' => (bool) $template->is_published,
            'description' => $template->subtitle ?? '',
            'data_source' => $template->data_source ?: 'instituciones',
            'data_source_label' => $this->customDataSources()[$template->data_source] ?? 'Datos personalizados',
            'layout' => $this->normalizeCustomLayout($template->layout ?? []),
            'creator' => $creatorName !== '' ? $creatorName : ($template->creator?->username ?: 'Usuario no disponible'),
            'updated_at' => $template->updated_at?->toIso8601String(),
            'published_at' => $template->published_at?->toIso8601String(),
        ];
    }

    private function normalizeCustomLayout(array $layout): array
    {
        $rows = min(40, max(1, (int) ($layout['rows'] ?? 8)));
        $columns = min(20, max(1, (int) ($layout['columns'] ?? 7)));
        $allowedParameters = $this->allowedCustomParameterKeys();

        $cells = collect($layout['cells'] ?? [])
            ->filter(fn ($cell) => is_array($cell))
            ->map(function (array $cell) use ($rows, $columns, $allowedParameters) {
                $row = (int) ($cell['row'] ?? -1);
                $column = (int) ($cell['column'] ?? -1);

                if ($row < 0 || $row >= $rows || $column < 0 || $column >= $columns) {
                    return null;
                }

                $type = in_array($cell['type'] ?? null, ['text', 'free', 'parameter'], true)
                    ? $cell['type']
                    : 'text';
                $parameter = in_array($cell['parameter'] ?? null, $allowedParameters, true)
                    ? $cell['parameter']
                    : null;
                $repeatDirection = in_array($cell['repeat_direction'] ?? null, ['vertical', 'horizontal'], true)
                    ? $cell['repeat_direction']
                    : 'none';
                $style = is_array($cell['style'] ?? null) ? $cell['style'] : [];
                $fontFamily = in_array($style['font_family'] ?? null, self::CUSTOM_FONT_FAMILIES, true)
                    ? $style['font_family']
                    : 'Arial';
                $alignment = in_array($style['align'] ?? null, ['left', 'center', 'right'], true)
                    ? $style['align']
                    : 'left';

                return [
                    'row' => $row,
                    'column' => $column,
                    'type' => $type,
                    'value' => mb_substr((string) ($cell['value'] ?? ''), 0, 500),
                    'parameter' => $type === 'parameter' ? $parameter : null,
                    'repeat_direction' => $type === 'parameter' ? $repeatDirection : 'none',
                    'style' => [
                        'background' => $this->normalizeHexColor($style['background'] ?? null, '#FFFFFF'),
                        'color' => $this->normalizeHexColor($style['color'] ?? null, '#1F2937'),
                        'font_family' => $fontFamily,
                        'font_size' => min(36, max(8, (int) ($style['font_size'] ?? 11))),
                        'bold' => (bool) ($style['bold'] ?? false),
                        'italic' => (bool) ($style['italic'] ?? false),
                        'underline' => (bool) ($style['underline'] ?? false),
                        'align' => $alignment,
                    ],
                ];
            })
            ->filter()
            ->unique(fn (array $cell) => $cell['row'].':'.$cell['column'])
            ->values()
            ->all();

        return [
            'version' => 1,
            'rows' => $rows,
            'columns' => $columns,
            'cells' => $cells,
        ];
    }

    private function normalizeHexColor(mixed $value, string $fallback): string
    {
        $value = strtoupper((string) $value);

        return preg_match('/^#[0-9A-F]{6}$/', $value) ? $value : $fallback;
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
