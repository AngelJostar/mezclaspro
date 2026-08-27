<?php

namespace App\Exports\Instituciones;

use App\Models\Institucion;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Services\InstitutionReportTemplateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitucionHospitalDetalleExport implements WithMultipleSheets
{
    use Exportable;

    public const GENERAL_HEADERS = [
        'ID',
        'Remisión',
        'Lote',
        'Hospital',
        'Paciente',
        'Servicio',
        'Registro',
        'Diagnóstico',
        'Edad',
        'Sexo',
        'Peso',
        'Cama',
        'Sobrellenado',
        'Volumen total',
        'NPT',
        'Fecha de solicitud',
        'Nombre del médico',
        'Cédula profesional',
        'Observaciones',
        'Estatus',
    ];

    public function __construct(private int $institucionId, private ?array $hospitalIds = null) {}

    public function sheets(): array
    {
        $institucion = Institucion::findOrFail($this->institucionId);
        $hospitalIds = $this->hospitalIds ?? $institucion->hospitals()->pluck('hospitals.id')->all();

        $nutriColumns = $this->buildNutriColumns($hospitalIds);
        $oncoColumns = $this->buildOncoColumns($hospitalIds);

        return [
            new InstitucionHospitalDetalleSheet(
                'Nutricion Parenteral',
                $nutriColumns,
                $this->buildNutriRows($hospitalIds, $nutriColumns),
                ['institucion' => $institucion->nombre, 'hoja' => 'Nutricion Parenteral']
            ),
            new InstitucionHospitalDetalleSheet(
                'Oncologicos',
                $oncoColumns,
                $this->buildOncoRows($hospitalIds, $oncoColumns, true),
                ['institucion' => $institucion->nombre, 'hoja' => 'Oncologicos']
            ),
            new InstitucionHospitalDetalleSheet(
                'Antibioticos',
                $oncoColumns,
                $this->buildOncoRows($hospitalIds, $oncoColumns, false),
                ['institucion' => $institucion->nombre, 'hoja' => 'Antibioticos']
            ),
        ];
    }

    private function buildNutriColumns(array $hospitalIds): array
    {
        if (empty($hospitalIds)) {
            return [];
        }

        return DB::table('hospitals as h')
            ->join('nutri_medicine_list_items as nli', 'nli.nutri_medicine_list_id', '=', 'h.nutri_medicine_list_id')
            ->join('nutrition_medicine_presentations as nmp', 'nmp.id', '=', 'nli.nutrition_medicine_presentation_id')
            ->join('nutrition_medicines_catalog as nmc', 'nmc.id', '=', 'nmp.nutrition_medicine_catalog_id')
            ->join('inputs as i', 'i.id', '=', 'nmc.input_id')
            ->whereIn('h.id', $hospitalIds)
            ->where('nmp.is_available', 1)
            ->get(['i.description', 'i.orden_enum'])
            ->map(function ($row) {
                return [
                    'description' => trim((string) ($row->description ?? '')),
                    'orden_enum' => $row->orden_enum === null ? PHP_INT_MAX : (int) $row->orden_enum,
                ];
            })
            ->filter(fn($row) => $row['description'] !== '')
            ->unique('description')
            ->sortBy([
                ['orden_enum', 'asc'],
                ['description', 'asc'],
            ])
            ->pluck('description')
            ->values()
            ->all();
    }

    private function buildOncoColumns(array $hospitalIds): array
    {
        if (empty($hospitalIds)) {
            return [];
        }

        return DB::table('hospitals as h')
            ->join('medicine_list_presentation as mlp', 'mlp.medicine_list_id', '=', 'h.onco_medicine_list_id')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->join('medicines_catalog as mc', 'mc.id', '=', 'mp.catalog_id')
            ->whereIn('h.id', $hospitalIds)
            ->where('mp.is_available', 1)
            ->get(['mc.denominacion'])
            ->map(fn($row) => trim((string) ($row->denominacion ?? '')))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function buildNutriRows(array $hospitalIds, array $columns): array
    {
        if (empty($hospitalIds)) {
            return [];
        }

        $solicitudes = NutricionalSolicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',
            'input.input',
            'input.presentation.catalog',
        ])
            ->whereHas('user', function ($query) use ($hospitalIds) {
                $query->whereIn('hospital_id', $hospitalIds);
            })
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($solicitudes as $solicitud) {
            $base = $this->baseNutriRow($solicitud);
            $detailMap = array_fill_keys($columns, '');
            $sums = [];

            foreach ($solicitud->input as $input) {
                $label = trim((string) ($input?->input?->description ?? ''));
                if ($label === '' || !array_key_exists($label, $detailMap)) {
                    continue;
                }

                $value = $input->valor_sobrellenado;
                if ($value === null || (float) $value === 0.0) {
                    $value = $input->valor_ml;
                }
                if ($value === null || (float) $value === 0.0) {
                    $value = $input->valor;
                }
                if ($value === null || (float) $value === 0.0) {
                    continue;
                }

                $sums[$label] = ($sums[$label] ?? 0.0) + (float) $value;
            }

            foreach ($sums as $label => $value) {
                $detailMap[$label] = $this->formatNumber($value, 3);
            }

            $rows[] = array_merge($base, array_values($detailMap));
        }

        return $rows;
    }

    private function buildOncoRows(array $hospitalIds, array $columns, bool $onlyOnco): array
    {
        if (empty($hospitalIds)) {
            return [];
        }

        $mezclas = Mezcla::with([
            'solicitud.hospital',
            'medicamentos.medicamentoOnco.catalog',
        ])
            ->whereHas('solicitud', function ($query) use ($hospitalIds) {
                $query->whereIn('hospital_id', $hospitalIds);
            })
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($mezclas as $mezcla) {
            $servicio = (string) ($mezcla->solicitud?->servicio ?? '');
            $isOnco = $this->isOncoService($servicio);

            if ($onlyOnco !== $isOnco) {
                continue;
            }

            $base = $this->baseOncoRow($mezcla);
            $detailMap = array_fill_keys($columns, '');
            $sums = [];

            foreach ($mezcla->medicamentos as $medicamento) {
                $label = trim((string) (
                    $medicamento->denominacion_snapshot
                    ?? optional(optional($medicamento->medicamentoOnco)->catalog)->denominacion
                    ?? $medicamento->nombre_medicamento
                    ?? ''
                ));

                if ($label === '' || !array_key_exists($label, $detailMap)) {
                    continue;
                }

                $value = (float) ($medicamento->dosis ?? 0);
                if ($value <= 0) {
                    $value = (float) ($medicamento->dosis_ml ?? 0);
                }
                if ($value <= 0) {
                    continue;
                }

                $sums[$label] = ($sums[$label] ?? 0.0) + $value;
            }

            foreach ($sums as $label => $value) {
                $detailMap[$label] = $this->formatNumber($value, 2);
            }

            $rows[] = array_merge($base, array_values($detailMap));
        }

        return $rows;
    }

    private function baseNutriRow(NutricionalSolicitud $solicitud): array
    {
        $detail = $solicitud->solicitud_detail;
        $patient = $solicitud->solicitud_patient;

        return [
            $solicitud->id,
            $solicitud->remision ?: '',
            $solicitud->lote ?: '',
            $solicitud->user?->hospital?->name ?: '',
            trim(($patient?->nombre_paciente ?? '') . ' ' . ($patient?->apellidos_paciente ?? '')),
            $patient?->servicio ?: '',
            $patient?->registro ?: '',
            $patient?->diagnostico ?: '',
            $patient?->edad ?: '',
            $patient?->sexo ?: '',
            $patient?->peso ?: '',
            '',
            $detail?->sobrellenado_ml ?? '',
            $detail?->volumen_total ?? '',
            $detail?->npt ?? '',
            optional($solicitud->created_at)?->format('d/m/Y') ?: '',
            $detail?->nombre_medico ?: '',
            $detail?->cedula ?: '',
            $detail?->observaciones ?: '',
            $this->normalizeStatus($solicitud->estado),
        ];
    }

    private function baseOncoRow(Mezcla $mezcla): array
    {
        $solicitud = $mezcla->solicitud;

        return [
            $mezcla->id,
            $mezcla->remision ?: '',
            $mezcla->lote ?: '',
            $solicitud?->hospital?->name ?: '',
            $solicitud?->nombre_paciente ?: '',
            $solicitud?->servicio ?: '',
            $solicitud?->registro_paciente ?: '',
            $solicitud?->diagnostico ?: '',
            $solicitud?->edad ?: '',
            $solicitud?->sexo ?: '',
            $solicitud?->peso ?: '',
            $solicitud?->cama ?: '',
            '',
            $mezcla->volumen_dilucion ?: '',
            '',
            optional($solicitud?->created_at)?->format('d/m/Y') ?: '',
            $solicitud?->nombre_medico ?: '',
            $solicitud?->cedula_medico ?: '',
            $solicitud?->observaciones ?: '',
            $this->normalizeStatus($mezcla->estado ?: $solicitud?->estado),
        ];
    }

    private function isOncoService(?string $servicio): bool
    {
        $normalized = mb_strtolower(trim((string) $servicio), 'UTF-8');

        return str_contains($normalized, 'onco');
    }

    private function normalizeStatus(?string $status): string
    {
        $status = trim((string) $status);
        if ($status === '') {
            return '';
        }

        return mb_convert_case($status, MB_CASE_TITLE, 'UTF-8');
    }

    private function formatNumber(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }
}

class InstitucionHospitalDetalleSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $sheetTitle,
        private array $dynamicHeadings,
        private array $rows,
        private array $context
    ) {}

    public function array(): array
    {
        return app(InstitutionReportTemplateService::class)->projectRowsWithTrailing(
            InstitutionReportTemplateService::HOSPITAL_DETAIL,
            $this->rows,
            count(InstitucionHospitalDetalleExport::GENERAL_HEADERS)
        );
    }

    public function headings(): array
    {
        return app(InstitutionReportTemplateService::class)->headingRows(
            InstitutionReportTemplateService::HOSPITAL_DETAIL,
            $this->context,
            $this->dynamicHeadings
        );
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet): array
    {
        $templates = app(InstitutionReportTemplateService::class);
        $generalCount = count($templates->visibleColumns(InstitutionReportTemplateService::HOSPITAL_DETAIL));
        $headerRow = $templates->styleWorksheet(
            $sheet,
            InstitutionReportTemplateService::HOSPITAL_DETAIL,
            $this->context,
            trailingColumnCount: count($this->dynamicHeadings)
        );
        $highestColumn = $sheet->getHighestColumn();

        $generalEnd = $templates->columnLetterFromIndex($generalCount);
        $sheet->getStyle("A{$headerRow}:{$generalEnd}{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '163A5F'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'CFEAF5'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        if (! empty($this->dynamicHeadings)) {
            $dynamicStart = $templates->columnLetterFromIndex($generalCount + 1);
            $sheet->getStyle("{$dynamicStart}{$headerRow}:{$highestColumn}{$headerRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1F1F1F'],
                    'size' => 9,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'D9D9D9'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }

        if (!empty($this->rows)) {
            $dataStart = $headerRow + 1;
            $dataEnd = $headerRow + count($this->rows);
            $sheet->getStyle("A{$dataStart}:{$highestColumn}{$dataEnd}")->getAlignment()->setVertical('center');
        }

        return [];
    }
}
