<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Instituciones\InstitutionBillingExpandedExport;
use App\Exports\Instituciones\InstitutionBillingExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\InstitutionBilling;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Services\InstitutionBillingDueDateService;
use App\Services\InstitutionBillingPendingSummaryService;
use App\Services\InstitutionBillingPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class InstitucionBillingController extends Controller
{
    private const NUTRITION_BILLING_DESCRIPTION = 'Medicamento de nutricion parenteral';

    public function __construct(
        private InstitutionBillingPricingService $pricing,
        private InstitutionBillingDueDateService $dueDates,
        private ?InstitutionBillingPendingSummaryService $pendingBilling = null
    ) {
    }

    public function index(Request $request)
    {
        return $this->renderIndex($request, 'pending');
    }

    public function history(Request $request)
    {
        return $this->renderIndex($request, 'history');
    }

    protected function renderIndex(Request $request, string $billingSection)
    {
        $institucionId = $request->query('institucion_id');
        $hospitalId = $request->query('hospital_id');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $billingStatus = $request->query('billing_status');
        $facturacionStatus = trim((string) $request->query('facturacion_status', ''));
        $conciliableFilter = trim((string) $request->query('conciliable_filter', ''));

        if (!in_array($billingStatus, ['con', 'sin'], true)) {
            $billingStatus = '';
        }

        $instituciones = Institucion::with(['hospitals' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('nombre')->get();

        $hospitals = collect();
        if ($institucionId) {
            $hospitals = Hospital::whereHas('instituciones', function ($query) use ($institucionId) {
                $query->where('clientes.id', $institucionId);
            })->orderBy('name')->get();
        }

        $records = $this->buildMergedRecords(
            $institucionId,
            $hospitalId,
            $search,
            $dateFrom,
            $dateTo,
            $billingStatus,
            $facturacionStatus,
            $conciliableFilter,
            $billingSection
        );

        $summary = $this->buildSummaryFromCollection($records);
        $paginatedRecords = $this->paginateCollection($records, 200, $request);

        return view('admin.instituciones.billing.index', [
            'institucionId' => $institucionId,
            'hospitalId' => $hospitalId,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'billingStatus' => $billingStatus,
            'facturacionStatus' => $facturacionStatus,
            'conciliableFilter' => $conciliableFilter,
            'instituciones' => $instituciones,
            'hospitals' => $hospitals,
            'summary' => $summary,
            'billingDueCounts' => $this->pendingBilling?->counts() ?? ['yellow' => 0, 'red' => 0],
            'records' => $paginatedRecords,
            'billingSection' => $billingSection,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'institucion_id' => ['required', 'integer', 'exists:clientes,id'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'origen_tipo' => ['required', 'string', 'in:oncologica_mezcla,nutricional_solicitud'],
            'origen_id' => ['required', 'integer'],
            'precio_total' => ['nullable', 'string', 'max:255'],
            'conciliable' => ['nullable', 'string', 'max:255'],
            'folio_factura_uuid' => ['nullable', 'string', 'max:255'],
            'folio_interno' => ['nullable', 'string', 'max:255'],
            'fecha_facturacion' => ['nullable', 'string', 'max:255'],
            'estatus_facturacion' => ['nullable', 'string', 'max:255'],
            'numero_carta_factura' => ['nullable', 'string', 'max:255'],
            'fecha_carta_factura' => ['nullable', 'string', 'max:255'],
            'institucion_filter' => ['nullable', 'integer'],
            'hospital_filter' => ['nullable', 'integer'],
            'search_filter' => ['nullable', 'string'],
            'date_from_filter' => ['nullable', 'string'],
            'date_to_filter' => ['nullable', 'string'],
            'billing_status_filter' => ['nullable', 'string'],
            'facturacion_status_filter' => ['nullable', 'string'],
            'conciliable_filter_value' => ['nullable', 'string'],
            'billing_section' => ['nullable', 'string', 'in:pending,history'],
        ]);

        if (mb_strtolower(trim((string) ($data['estatus_facturacion'] ?? ''))) === 'completado') {
            $request->validate([
                'folio_factura_uuid' => ['required', 'string', 'max:255'],
                'folio_interno' => ['required', 'string', 'max:255'],
                'fecha_facturacion' => ['required', 'string', 'max:255'],
                'numero_carta_factura' => ['required', 'string', 'max:255'],
                'fecha_carta_factura' => ['required', 'string', 'max:255'],
            ], [
                '*.required' => 'Completa todos los datos de facturacion antes de concluir.',
            ]);
        }

        InstitutionBilling::updateOrCreate(
            [
                'origen_tipo' => $data['origen_tipo'],
                'origen_id' => $data['origen_id'],
            ],
            [
                'institucion_id' => $data['institucion_id'],
                'hospital_id' => $data['hospital_id'] ?: null,
                'precio_total' => $data['precio_total'] ?? null,
                'conciliable' => $data['conciliable'] ?? null,
                'folio_factura_uuid' => $data['folio_factura_uuid'] ?? null,
                'folio_interno' => $data['folio_interno'] ?? null,
                'fecha_facturacion' => $data['fecha_facturacion'] ?? null,
                'estatus_facturacion' => $data['estatus_facturacion'] ?? null,
                'numero_carta_factura' => $data['numero_carta_factura'] ?? null,
                'fecha_carta_factura' => $data['fecha_carta_factura'] ?? null,
            ]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Facturación actualizada correctamente.',
            ]);
        }

        $redirectRoute = ($data['billing_section'] ?? 'pending') === 'history'
            ? 'admin.instituciones.billing.history'
            : 'admin.instituciones.billing.index';

        return redirect()->route($redirectRoute, [
            'institucion_id' => $data['institucion_filter'] ?: null,
            'hospital_id' => $data['hospital_filter'] ?: null,
            'search' => $data['search_filter'] ?: null,
            'date_from' => $data['date_from_filter'] ?: null,
            'date_to' => $data['date_to_filter'] ?: null,
            'billing_status' => $data['billing_status_filter'] ?: null,
            'facturacion_status' => $data['facturacion_status_filter'] ?: null,
            'conciliable_filter' => $data['conciliable_filter_value'] ?: null,
        ]);
    }

    public function exportarExcel(Request $request)
    {
        $institucionId = $request->query('institucion_id');
        $hospitalId = $request->query('hospital_id');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $billingStatus = $request->query('billing_status');
        $facturacionStatus = trim((string) $request->query('facturacion_status', ''));
        $conciliableFilter = trim((string) $request->query('conciliable_filter', ''));
        $billingSection = $request->query('section') === 'history' ? 'history' : 'pending';

        if (!in_array($billingStatus, ['con', 'sin'], true)) {
            $billingStatus = '';
        }

        $rows = $this->buildMergedRecords(
            $institucionId,
            $hospitalId,
            $search,
            $dateFrom,
            $dateTo,
            $billingStatus,
            $facturacionStatus,
            $conciliableFilter,
            $billingSection
        )->map(function ($item) {
            return $item['export_row'];
        })->values()->all();

        $fileName = 'facturacion_' . $billingSection . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InstitutionBillingExport($rows), $fileName);
    }

    public function exportarExcelAmpliado(Request $request)
    {
        $data = $request->validate([
            'selected_records' => ['required', 'array', 'min:1', 'max:200'],
            'selected_records.*' => ['required', 'string', 'regex:/^(oncologica_mezcla|nutricional_solicitud):[1-9][0-9]*$/'],
            'section' => ['nullable', 'string', 'in:pending,history'],
            'institucion_id' => ['nullable', 'integer'],
            'hospital_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
            'date_from' => ['nullable', 'string'],
            'date_to' => ['nullable', 'string'],
            'billing_status' => ['nullable', 'string'],
            'facturacion_status' => ['nullable', 'string'],
            'conciliable_filter' => ['nullable', 'string'],
        ]);

        $billingStatus = in_array($data['billing_status'] ?? '', ['con', 'sin'], true)
            ? $data['billing_status']
            : '';
        $billingSection = ($data['section'] ?? 'pending') === 'history' ? 'history' : 'pending';
        $selected = array_fill_keys($data['selected_records'], true);

        $records = $this->buildMergedRecords(
            $data['institucion_id'] ?? null,
            $data['hospital_id'] ?? null,
            trim((string) ($data['search'] ?? '')),
            $data['date_from'] ?? null,
            $data['date_to'] ?? null,
            $billingStatus,
            trim((string) ($data['facturacion_status'] ?? '')),
            trim((string) ($data['conciliable_filter'] ?? '')),
            $billingSection
        )->filter(function ($item) use ($selected) {
            $key = $item['origen_tipo'] . ':' . $item['record']->id;

            return isset($selected[$key]);
        });

        $rows = $this->buildExpandedExportRows($records);
        $fileName = 'facturacion_ampliada_' . $billingSection . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InstitutionBillingExpandedExport($rows), $fileName);
    }

    protected function buildMergedRecords($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '', string $billingSection = 'pending')
    {
        $oncoRecords = $this->buildOncoQuery($institucionId, $hospitalId, $search, $dateFrom, $dateTo, $billingStatus, $facturacionStatus, $conciliableFilter)
            ->get()
            ->map(function ($record) use ($institucionId) {
                return $this->transformRecord($record, 'onco', $institucionId);
            });

        $nutriRecords = $this->buildNutriQuery($institucionId, $hospitalId, $search, $dateFrom, $dateTo, $billingStatus, $facturacionStatus, $conciliableFilter)
            ->get()
            ->map(function ($record) use ($institucionId) {
                return $this->transformRecord($record, 'nutri', $institucionId);
            });

        return $oncoRecords
            ->concat($nutriRecords)
            ->filter(function ($item) use ($billingSection) {
                $status = mb_strtolower(trim((string) ($item['billing']?->estatus_facturacion ?? '')));
                $isCompleted = $status === 'completado';

                return $billingSection === 'history' ? $isCompleted : ! $isCompleted;
            })
            ->sortByDesc(function ($item) {
                return $item['sort_date'] ?? 0;
            })
            ->values();
    }

    protected function buildOncoQuery($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '')
    {
        return Mezcla::query()
            ->with([
                'solicitud.hospital.instituciones',
                'solicitud.hospital.oncoMedicineList',
                'medicamentos.medicamentoOnco.catalog.presentations',
                'medicamentos.presentacionesUsadas.batch.presentation',
                'infusor',
                'billing',
            ])
            ->whereHas('solicitud.hospital.instituciones', function ($query) use ($institucionId) {
                if ($institucionId) {
                    $query->where('clientes.id', $institucionId);
                }
            })
            ->when($hospitalId, function ($query) use ($hospitalId) {
                $query->whereHas('solicitud.hospital', function ($subquery) use ($hospitalId) {
                    $subquery->where('hospitals.id', $hospitalId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('lote', 'like', '%' . $search . '%')
                        ->orWhere('remision', 'like', '%' . $search . '%')
                        ->orWhereHas('solicitud', function ($solicitudQuery) use ($search) {
                            $solicitudQuery->where('nombre_paciente', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereHas('solicitud', function ($subquery) use ($dateFrom) {
                    $subquery->whereDate('fecha_entrega', '>=', $dateFrom);
                });
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereHas('solicitud', function ($subquery) use ($dateTo) {
                    $subquery->whereDate('fecha_entrega', '<=', $dateTo);
                });
            })
            ->when($billingStatus === 'con', function ($query) {
                $query->whereHas('billing');
            })
            ->when($billingStatus === 'sin', function ($query) {
                $query->whereDoesntHave('billing');
            })
            ->when($facturacionStatus !== '', function ($query) use ($facturacionStatus) {
                $query->whereHas('billing', function ($subquery) use ($facturacionStatus) {
                    $subquery->where('estatus_facturacion', $facturacionStatus);
                });
            })
            ->when($conciliableFilter !== '', function ($query) use ($conciliableFilter) {
                $query->whereHas('billing', function ($subquery) use ($conciliableFilter) {
                    $subquery->where('conciliable', $conciliableFilter);
                });
            })
            ->latest('id');
    }

    protected function buildNutriQuery($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '')
    {
        return NutricionalSolicitud::query()
            ->with([
                'user.hospital.instituciones',
                'solicitud_patient',
                'solicitud_detail',
                'input.input.nutritionMedicineCatalog',
                'input.presentation',
                'billing',
            ])
            ->whereHas('user.hospital.instituciones', function ($query) use ($institucionId) {
                if ($institucionId) {
                    $query->where('clientes.id', $institucionId);
                }
            })
            ->when($hospitalId, function ($query) use ($hospitalId) {
                $query->whereHas('user.hospital', function ($subquery) use ($hospitalId) {
                    $subquery->where('hospitals.id', $hospitalId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('lote', 'like', '%' . $search . '%')
                        ->orWhere('remision', 'like', '%' . $search . '%')
                        ->orWhereHas('solicitud_patient', function ($patientQuery) use ($search) {
                            $patientQuery->where('nombre_paciente', 'like', '%' . $search . '%')
                                ->orWhere('apellidos_paciente', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereHas('solicitud_detail', function ($subquery) use ($dateFrom) {
                    $subquery->whereDate('fecha_hora_entrega', '>=', $dateFrom);
                });
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereHas('solicitud_detail', function ($subquery) use ($dateTo) {
                    $subquery->whereDate('fecha_hora_entrega', '<=', $dateTo);
                });
            })
            ->when($billingStatus === 'con', function ($query) {
                $query->whereHas('billing');
            })
            ->when($billingStatus === 'sin', function ($query) {
                $query->whereDoesntHave('billing');
            })
            ->when($facturacionStatus !== '', function ($query) use ($facturacionStatus) {
                $query->whereHas('billing', function ($subquery) use ($facturacionStatus) {
                    $subquery->where('estatus_facturacion', $facturacionStatus);
                });
            })
            ->when($conciliableFilter !== '', function ($query) use ($conciliableFilter) {
                $query->whereHas('billing', function ($subquery) use ($conciliableFilter) {
                    $subquery->where('conciliable', $conciliableFilter);
                });
            })
            ->latest('id');
    }

    protected function buildSummaryFromCollection($records): array
    {
        $total = $records->count();
        $withBilling = 0;
        $withoutBilling = 0;
        $conciliables = 0;
        $pendientes = 0;

        foreach ($records as $item) {
            $billing = $item['billing'] ?? null;

            if ($billing) {
                $withBilling++;

                $conciliableValue = trim((string) ($billing->conciliable ?? ''));
                $conciliableLower = function_exists('mb_strtolower')
                    ? mb_strtolower($conciliableValue)
                    : strtolower($conciliableValue);

                if (in_array($conciliableLower, ['si', 'sí', 'yes', 'true', '1', 'conciliado', 'conciliable'], true)) {
                    $conciliables++;
                }

                $statusValue = trim((string) ($billing->estatus_facturacion ?? ''));
                $statusLower = function_exists('mb_strtolower')
                    ? mb_strtolower($statusValue)
                    : strtolower($statusValue);

                if ($statusValue === '' || in_array($statusLower, ['pendiente', 'en revision', 'en revisión', 'por facturar'], true)) {
                    $pendientes++;
                }
            } else {
                $withoutBilling++;
                $pendientes++;
            }
        }

        return [
            'total' => $total,
            'with_billing' => $withBilling,
            'without_billing' => $withoutBilling,
            'conciliables' => $conciliables,
            'pendientes' => $pendientes,
        ];
    }

    protected function transformRecord($record, string $type, $institucionId): array
    {
        if ($type === 'onco') {
            $hospital = $record->solicitud?->hospital;
            $institucionActual = $institucionId
                ? $hospital?->instituciones?->firstWhere('id', (int) $institucionId)
                : $hospital?->instituciones?->first();
            $patientName = $record->solicitud?->nombre_paciente ?? '—';
            $servicio = $record->solicitud?->servicio ?: '—';
            $medico = $record->solicitud?->nombre_medico ?: '—';
            $registro = $record->solicitud?->registro_paciente ?: '—';
            $fechaModel = $record->solicitud?->fecha_entrega;
            $estado = $record->estado ?: ($record->solicitud?->estado ?? '—');
            $origenTipo = 'oncologica_mezcla';
            $tipoTexto = $record->solicitud?->tipo_solicitud === 'antibioticos'
                ? 'Mezcla antibiótica'
                : 'Mezcla oncológica';
            $viewRoute = route('admin.oncologicos.mezclas.remision', $record->solicitud);
            $viewLabel = 'Ver mezcla';
            $pricing = $this->pricing->priceOncoMix($record);
        } else {
            $hospital = $record->user?->hospital;
            $institucionActual = $institucionId
                ? $hospital?->instituciones?->firstWhere('id', (int) $institucionId)
                : $hospital?->instituciones?->first();
            $patientName = trim(($record->solicitud_patient?->nombre_paciente ?? '') . ' ' . ($record->solicitud_patient?->apellidos_paciente ?? ''));
            $patientName = $patientName !== '' ? $patientName : '—';
            $servicio = $record->solicitud_patient?->servicio ?: '—';
            $medico = $record->solicitud_detail?->nombre_medico ?: '—';
            $registro = $record->solicitud_patient?->registro ?: '—';
            $fechaModel = $record->solicitud_detail?->fecha_hora_entrega;
            $estado = $record->estado ?: '—';
            $origenTipo = 'nutricional_solicitud';
            $tipoTexto = self::NUTRITION_BILLING_DESCRIPTION;
            $viewRoute = route('admin.nutricionales.solicitudes.remision', $record);
            $viewLabel = 'Ver mezcla';
            $pricing = $this->pricing->priceNutritionRequest($record);
        }

        $fecha = $fechaModel ? Carbon::parse($fechaModel)->format('d/m/Y H:i') : '—';
        $billing = $record->billing;
        $descriptionLines = $type === 'onco'
            ? $pricing['lines']->pluck('description')->filter()->values()->all()
            : [self::NUTRITION_BILLING_DESCRIPTION];
        $quantityLines = $type === 'onco'
            ? $pricing['lines']->map(function ($line) {
                $quantity = rtrim(rtrim(number_format((float) $line['quantity'], 2, '.', ''), '0'), '.');

                return $quantity . ' ' . $line['unit_label'];
            })->all()
            : ['1'];
        $bottleQuantityLines = $type === 'onco'
            ? $pricing['lines']->map(function ($line) {
                $quantity = $line['bottle_quantity'] ?? null;

                if ($quantity === null) {
                    return '—';
                }

                return rtrim(rtrim(number_format((float) $quantity, 2, '.', ''), '0'), '.');
            })->all()
            : ['—'];
        $unitPriceLines = $type === 'onco'
            ? $pricing['lines']->map(function ($line) {
                return $this->pricing->formatMoney((float) $line['unit_price']) . ' / ' . $line['unit_label'];
            })->all()
            : [$this->pricing->formatMoney((float) $pricing['subtotal_before_vat'])];
        $totalPrice = (float) $pricing['total_iva_included'];
        $formattedTotalPrice = $totalPrice > 0 ? $this->pricing->formatMoney($totalPrice) : '—';
        $remision = $record->remision ?: ($type === 'onco' ? $record->solicitud?->remision : null);
        $vencimiento = $this->dueDates->calculate($fechaModel, $billing?->estatus_facturacion);

        return [
            'record' => $record,
            'type' => $type,
            'tipo_texto' => $tipoTexto,
            'hospital' => $hospital,
            'institucion' => $institucionActual,
            'empresa' => $institucionActual?->razon_social ?: ($institucionActual?->nombre ?: '—'),
            'patient_name' => $patientName,
            'servicio' => $servicio,
            'medico' => $medico,
            'registro' => $registro,
            'fecha' => $fecha,
            'sort_date' => $fechaModel ? Carbon::parse($fechaModel)->timestamp : 0,
            'estado' => $estado,
            'origen_tipo' => $origenTipo,
            'billing' => $billing,
            'view_route' => $viewRoute,
            'view_label' => $viewLabel,
            'origen_label' => $tipoTexto,
            'breakdown_lines' => $this->buildBillingBreakdownLines($record, $type, $pricing),
            'description_lines' => $descriptionLines,
            'quantity_lines' => $quantityLines,
            'bottle_quantity_lines' => $bottleQuantityLines,
            'unit_price_lines' => $unitPriceLines,
            'pv_total' => $formattedTotalPrice,
            'computed_total_input' => $totalPrice > 0 ? number_format($totalPrice, 2, '.', '') : '',
            'remision' => $remision ?: '—',
            'vencimiento' => $vencimiento,
            'export_row' => [
                $institucionActual?->nombre ?: '—',
                $hospital?->name ?: '—',
                $medico,
                $patientName,
                $remision ?: '—',
                $fecha,
                implode("\n", $quantityLines),
                implode("\n", $bottleQuantityLines),
                implode("\n", $descriptionLines),
                implode("\n", $unitPriceLines),
                $formattedTotalPrice,
                $institucionActual?->razon_social ?: ($institucionActual?->nombre ?: '—'),
                $billing?->precio_total ?: $formattedTotalPrice,
                $billing?->conciliable ?: '—',
                $billing?->folio_factura_uuid ?: '—',
                $billing?->folio_interno ?: '—',
                $billing?->fecha_facturacion ?: '—',
                $billing?->numero_carta_factura ?: '—',
                $billing?->fecha_carta_factura ?: '—',
            ],
        ];
    }

    protected function buildBillingBreakdownLines($record, string $type, array $pricing): array
    {
        $lines = [];

        foreach ($pricing['lines'] ?? collect() as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $subtotal = (float) ($line['subtotal'] ?? 0);
            $vat = $type === 'onco' ? (float) ($line['vat'] ?? 0) : 0.0;

            $lines[] = [
                'concept_type' => 'Medicamento',
                'description' => (string) ($line['description'] ?? 'Medicamento'),
                'quantity' => $quantity,
                'unit_label' => (string) ($line['unit_label'] ?? ''),
                'unit_price_before_vat' => (float) ($line['unit_price'] ?? 0),
                'subtotal_before_vat' => $subtotal,
                'vat' => $vat,
                'total_with_vat' => $type === 'onco'
                    ? (float) ($line['total_with_vat'] ?? ($subtotal + $vat))
                    : $subtotal,
            ];
        }

        if ($type === 'nutri') {
            foreach ($pricing['supply_lines'] ?? collect() as $line) {
                $quantity = max(1.0, (float) ($line['quantity'] ?? 1));
                $vatParts = $this->pricing->splitIncludedVat((float) ($line['subtotal'] ?? 0));

                $lines[] = [
                    'concept_type' => 'Insumo',
                    'description' => (string) ($line['description'] ?? 'Insumo'),
                    'quantity' => $quantity,
                    'unit_label' => (string) ($line['unit_label'] ?? 'pieza'),
                    'unit_price_before_vat' => round($vatParts['base'] / $quantity, 4),
                    'subtotal_before_vat' => $vatParts['base'],
                    'vat' => $vatParts['vat'],
                    'total_with_vat' => $vatParts['total'],
                ];
            }
        } elseif ((float) ($pricing['supplies_total'] ?? 0) > 0) {
            $lines[] = [
                'concept_type' => 'Insumo',
                'description' => (string) ($record->infusor_nombre ?? 'Insumos'),
                'quantity' => 1.0,
                'unit_label' => 'pieza',
                'unit_price_before_vat' => (float) ($pricing['supplies_base'] ?? 0),
                'subtotal_before_vat' => (float) ($pricing['supplies_base'] ?? 0),
                'vat' => (float) ($pricing['supplies_vat'] ?? 0),
                'total_with_vat' => (float) ($pricing['supplies_total'] ?? 0),
            ];
        }

        if ((float) ($pricing['service_total'] ?? 0) > 0) {
            $lines[] = [
                'concept_type' => 'Servicio',
                'description' => 'Servicio de mezcla',
                'quantity' => 1.0,
                'unit_label' => 'servicio',
                'unit_price_before_vat' => (float) ($pricing['service_base'] ?? 0),
                'subtotal_before_vat' => (float) ($pricing['service_base'] ?? 0),
                'vat' => (float) ($pricing['service_vat'] ?? 0),
                'total_with_vat' => (float) ($pricing['service_total'] ?? 0),
            ];
        }

        if ($lines === []) {
            $total = (float) ($pricing['total_iva_included'] ?? 0);
            $lines[] = [
                'concept_type' => 'Solicitud',
                'description' => (string) ($pricing['description'] ?? 'Solicitud'),
                'quantity' => 1.0,
                'unit_label' => 'solicitud',
                'unit_price_before_vat' => $total,
                'subtotal_before_vat' => $total,
                'vat' => 0.0,
                'total_with_vat' => $total,
            ];
        }

        return $lines;
    }

    protected function buildExpandedExportRows($records): array
    {
        return $records->flatMap(function ($item) {
            $billing = $item['billing'];
            $requestTotal = $this->pricing->parseMoney($item['computed_total_input'] ?? 0);
            $capturedTotal = $this->pricing->parseMoney($billing?->precio_total);
            $expiration = $item['vencimiento'] ?? [];
            $expirationText = in_array($expiration['status'] ?? '', ['yellow', 'red'], true)
                ? ucfirst((string) $expiration['status']) . ' - ' . (int) ($expiration['days'] ?? 0) . ' dias'
                : '';

            return collect($item['breakdown_lines'])->values()->map(function ($line, $index) use ($item, $billing, $requestTotal, $capturedTotal, $expirationText) {
                return [
                    $item['institucion']?->nombre ?: '',
                    $item['hospital']?->name ?: '',
                    $item['medico'] ?: '',
                    $item['patient_name'] ?: '',
                    $item['remision'] === '—' ? '' : $item['remision'],
                    $item['fecha'] === '—' ? '' : $item['fecha'],
                    $item['tipo_texto'] ?: '',
                    $item['servicio'] === '—' ? '' : $item['servicio'],
                    $item['registro'] === '—' ? '' : $item['registro'],
                    $index + 1,
                    $line['concept_type'],
                    $line['description'],
                    round((float) $line['quantity'], 4),
                    $line['unit_label'],
                    round((float) $line['unit_price_before_vat'], 4),
                    round((float) $line['subtotal_before_vat'], 2),
                    round((float) $line['vat'], 2),
                    round((float) $line['total_with_vat'], 2),
                    round($requestTotal, 2),
                    $item['empresa'] === '—' ? '' : $item['empresa'],
                    round($capturedTotal > 0 ? $capturedTotal : $requestTotal, 2),
                    $billing?->conciliable ?: 'Si',
                    $billing?->folio_factura_uuid ?: '',
                    $billing?->folio_interno ?: '',
                    $this->formatExpandedExportDate($billing?->fecha_facturacion),
                    $billing?->numero_carta_factura ?: '',
                    $this->formatExpandedExportDate($billing?->fecha_carta_factura),
                    $billing?->estatus_facturacion ?: '',
                    $expirationText,
                ];
            });
        })->values()->all();
    }

    protected function formatExpandedExportDate($value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function paginateCollection($items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $results = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
