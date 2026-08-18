<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Instituciones\InstitutionBillingExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\InstitutionBilling;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InstitucionBillingController extends Controller
{
    protected function resolveConfiguredOncoMixingServiceTotal($lista, float $billingTotal, float $medicationTotal): float
    {
        if ($lista && (bool) ($lista->has_mixing_service ?? false)) {
            return round((float) ($lista->mixing_service_price ?? 0), 2);
        }

        return $billingTotal > 0 ? max($billingTotal - $medicationTotal, 0) : 0.0;
    }

    public function index(Request $request)
    {
        $institucionId = $request->query('institucion_id');
        $hospitalId = $request->query('hospital_id');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $billingStatus = $request->query('billing_status');
        $facturacionStatus = trim((string) $request->query('facturacion_status', ''));
        $conciliableFilter = trim((string) $request->query('conciliable_filter', ''));
        $sortBy = trim((string) $request->query('sort_by', 'fecha'));
        $sortDir = trim((string) $request->query('sort_dir', 'desc'));

        if (!in_array($sortBy, ['institucion', 'unidad', 'medico', 'paciente', 'remision', 'fecha'], true)) {
            $sortBy = 'fecha';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

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
            $sortBy,
            $sortDir
        );

        $summary = $this->buildSummaryFromCollection($records);
        $paginatedRecords = $this->paginateCollection($records, 15, $request);

        return view('admin.instituciones.billing.index', [
            'institucionId' => $institucionId,
            'hospitalId' => $hospitalId,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'billingStatus' => $billingStatus,
            'facturacionStatus' => $facturacionStatus,
            'conciliableFilter' => $conciliableFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'tableFilters' => [],
            'instituciones' => $instituciones,
            'hospitals' => $hospitals,
            'summary' => $summary,
            'records' => $paginatedRecords,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'institucion_id' => ['required', 'integer', 'exists:clientes,id'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'origen_tipo' => ['required', 'string', 'in:oncologica_mezcla,nutricional_solicitud'],
            'origen_id' => ['required', 'integer'],
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
        ]);

        $precioTotal = null;

        if ($data['origen_tipo'] === 'oncologica_mezcla') {
            $mezcla = Mezcla::with([
                'solicitud.user.medicineList',
                'medicamentos.medicamentoOnco.catalog',
                'medicamentos.presentacionesUsadas.batch.presentation',
                'infusor',
            ])->find($data['origen_id']);

            $precioTotal = $mezcla
                ? $this->formatBillingMoney($this->resolveOncoBillingTotal($mezcla))
                : null;
        } else {
            $solicitud = NutricionalSolicitud::with(['input'])->find($data['origen_id']);

            $precioTotal = $solicitud
                ? $this->formatBillingMoney($this->resolveNutriBillingTotal($solicitud))
                : null;
        }

        InstitutionBilling::updateOrCreate(
            [
                'origen_tipo' => $data['origen_tipo'],
                'origen_id' => $data['origen_id'],
            ],
            [
                'institucion_id' => $data['institucion_id'],
                'hospital_id' => $data['hospital_id'] ?: null,
                'precio_total' => $precioTotal,
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

        return redirect()->route('admin.instituciones.billing.index', [
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
        $sortBy = trim((string) $request->query('sort_by', 'fecha'));
        $sortDir = trim((string) $request->query('sort_dir', 'desc'));

        if (!in_array($sortBy, ['institucion', 'unidad', 'medico', 'paciente', 'remision', 'fecha'], true)) {
            $sortBy = 'fecha';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (!in_array($billingStatus, ['con', 'sin'], true)) {
            $billingStatus = '';
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
            $sortBy,
            $sortDir
        );

        $rows = $this->buildBillingExportRows($records);

        $fileName = 'facturacion_instituciones_general_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InstitutionBillingExport($rows), $fileName);
    }

    protected function buildMergedRecords($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '', string $sortBy = 'fecha', string $sortDir = 'desc')
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

        $merged = $oncoRecords
            ->concat($nutriRecords)
            ->values();

        return $this->sortMergedRecords($merged, $sortBy, $sortDir);
    }

    protected function sortMergedRecords($records, string $sortBy, string $sortDir)
    {
        $valueResolver = function ($item) use ($sortBy) {
            return match ($sortBy) {
                'institucion' => $item['institucion']?->nombre ?: '',
                'unidad' => $item['hospital']?->name ?: '',
                'medico' => $item['medico'] ?? '',
                'paciente' => $item['patient_name'] ?? '',
                'remision' => $item['record']?->remision ?: '',
                default => (string) ($item['sort_date'] ?? 0),
            };
        };

        $sorted = $sortBy === 'fecha'
            ? ($sortDir === 'asc'
                ? $records->sortBy(fn ($item) => $item['sort_date'] ?? 0)
                : $records->sortByDesc(fn ($item) => $item['sort_date'] ?? 0))
            : $records->sortBy(function ($item) use ($valueResolver) {
                return mb_strtolower(trim((string) $valueResolver($item)));
            }, SORT_NATURAL, $sortDir === 'desc');

        return $sorted->values();
    }

    protected function buildOncoQuery($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '')
    {
        return Mezcla::query()
            ->with([
                'solicitud.hospital.instituciones',
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
            $record->loadMissing([
                'billing',
                'solicitud.hospital.instituciones',
                'solicitud.user.medicineList',
                'medicamentos.medicamentoOnco.catalog',
                'medicamentos.presentacionesUsadas.batch.presentation',
                'infusor',
            ]);

            $hospital = $record->solicitud?->hospital;
            $institucionActual = $institucionId
                ? $hospital?->instituciones?->firstWhere('id', (int) $institucionId)
                : $hospital?->instituciones?->first();
            $patientName = $record->solicitud?->nombre_paciente ?? '?';
            $servicio = $record->solicitud?->servicio ?: '?';
            $medico = $record->solicitud?->nombre_medico ?: '?';
            $registro = $record->solicitud?->registro_paciente ?: '?';
            $fechaModel = $record->solicitud?->fecha_entrega;
            $estado = $record->estado ?: ($record->solicitud?->estado ?? '?');
            $origenTipo = 'oncologica_mezcla';
            $tipoTexto = 'Mezcla oncol?gica';
            $viewRoute = route('admin.oncologicos.mezclas.show', $record);
            $viewLabel = 'Ver mezcla';
            $precioTotalSistema = $this->resolveOncoBillingTotal($record);
        } else {
            $record->loadMissing([
                'billing',
                'user.hospital.instituciones',
                'solicitud_patient',
                'solicitud_detail',
                'input',
            ]);

            $hospital = $record->user?->hospital;
            $institucionActual = $institucionId
                ? $hospital?->instituciones?->firstWhere('id', (int) $institucionId)
                : $hospital?->instituciones?->first();
            $patientName = trim(($record->solicitud_patient?->nombre_paciente ?? '') . ' ' . ($record->solicitud_patient?->apellidos_paciente ?? ''));
            $patientName = $patientName !== '' ? $patientName : '?';
            $servicio = $record->solicitud_patient?->servicio ?: '?';
            $medico = $record->solicitud_detail?->nombre_medico ?: '?';
            $registro = $record->solicitud_patient?->registro ?: '?';
            $fechaModel = $record->solicitud_detail?->fecha_hora_entrega;
            $estado = $record->estado ?: '?';
            $origenTipo = 'nutricional_solicitud';
            $tipoTexto = 'Solicitud nutricional';
            $viewRoute = route('admin.nutricionales.solicitudes.show', $record);
            $viewLabel = 'Ver solicitud';
            $precioTotalSistema = $this->resolveNutriBillingTotal($record);
        }

        $fecha = $fechaModel ? Carbon::parse($fechaModel)->format('d/m/Y H:i') : '?';
        $billing = $record->billing;
        $precioTotalFinal = $billing?->precio_total ?: $this->formatBillingMoney($precioTotalSistema);

        return [
            'record' => $record,
            'type' => $type,
            'tipo_texto' => $tipoTexto,
            'hospital' => $hospital,
            'institucion' => $institucionActual,
            'empresa' => $institucionActual?->razon_social ?: ($institucionActual?->nombre ?: '?'),
            'patient_name' => $patientName,
            'servicio' => $servicio,
            'medico' => $medico,
            'registro' => $registro,
            'fecha' => $fecha,
            'sort_date' => $fechaModel ? Carbon::parse($fechaModel)->timestamp : 0,
            'estado' => $estado,
            'origen_tipo' => $origenTipo,
            'billing' => $billing,
            'precio_total_sistema' => $precioTotalSistema,
            'precio_total_final' => $precioTotalFinal,
            'view_route' => $viewRoute,
            'view_label' => $viewLabel,
            'origen_label' => $tipoTexto,
            'export_row' => [
                $institucionActual?->nombre ?: '?',
                $hospital?->name ?: '?',
                $medico,
                $patientName,
                $record->remision ?: '?',
                $fecha,
                '1',
                $tipoTexto,
                $precioTotalFinal,
                $precioTotalFinal,
                $institucionActual?->razon_social ?: ($institucionActual?->nombre ?: '?'),
                $precioTotalFinal,
                $billing?->conciliable ?: '?',
                $billing?->folio_factura_uuid ?: '?',
                $billing?->folio_interno ?: '?',
                $billing?->fecha_facturacion ?: '?',
                $billing?->numero_carta_factura ?: '?',
                $billing?->fecha_carta_factura ?: '?',
            ],
        ];
    }

    protected function resolveOncoBillingTotal(Mezcla $mezcla): float
    {
        $solicitud = $mezcla->solicitud;
        $lista = $solicitud?->user?->medicineList;
        $listaCharge = $lista->charge_by ?? 'frasco';

        $cfgPorPresentacion = collect();
        if ($lista) {
            $cfgPorPresentacion = DB::table('medicine_list_presentation')
                ->where('medicine_list_id', $lista->id)
                ->get()
                ->keyBy('medicine_presentation_id');
        }

        $medicationTotal = 0.0;
        foreach ($mezcla->medicamentos as $med) {
            [, , $subtotal] = $this->resolveOncoPricingForBilling($med, $cfgPorPresentacion, $listaCharge);
            $medicationTotal += $subtotal;
        }

        $mezclaUsaInfusor = (bool) ($mezcla->set_infusion ?? false) || !empty($mezcla->infusor_id);
        $requierePorRegla = collect($mezcla->medicamentos)->contains(function ($med) {
            if (!is_null($med->requires_infusor_snapshot)) {
                return (int) $med->requires_infusor_snapshot === 1;
            }

            return (int) (optional(optional($med->medicamentoOnco)->catalog)->requires_infusor ?? 0) === 1;
        });

        $infusorTotal = 0.0;
        if ($mezclaUsaInfusor && $requierePorRegla && !empty($mezcla->infusor)) {
            $infusorTotal = (float) ($mezcla->infusor->precio ?? 0);
        }

        $billingTotal = $this->parseBillingMoney($mezcla->billing?->precio_total);
        $serviceTotal = $this->resolveConfiguredOncoMixingServiceTotal($lista, $billingTotal, $medicationTotal + $infusorTotal);

        return round($medicationTotal + $infusorTotal + $serviceTotal, 2);
    }

    protected function resolveNutriBillingTotal(NutricionalSolicitud $solicitud): float
    {
        return round((float) collect($solicitud->input ?? [])->sum(fn ($input) => (float) ($input->precio_ml ?? 0)), 2);
    }

    protected function buildBillingExportRows(Collection $records): array
    {
        $rows = [];

        foreach ($records as $item) {
            if (($item['type'] ?? '') === 'onco') {
                $rows = array_merge($rows, $this->buildOncoBillingRows($item));
                continue;
            }

            $rows = array_merge($rows, $this->buildNutriBillingRows($item));
        }

        return $rows;
    }

    protected function buildOncoBillingRows(array $item): array
    {
        /** @var Mezcla $mezcla */
        $mezcla = $item['record'];
        $mezcla->loadMissing([
            'billing',
            'solicitud.hospital',
            'solicitud.user.medicineList',
            'medicamentos.medicamentoOnco.catalog',
            'medicamentos.presentacionesUsadas.batch.presentation',
        ]);

        $solicitud = $mezcla->solicitud;
        $institucion = $item['institucion'];
        $billing = $mezcla->billing;
        $lista = $solicitud?->user?->medicineList;
        $listaCharge = $lista->charge_by ?? 'frasco';

        $cfgPorPresentacion = collect();
        if ($lista) {
            $cfgPorPresentacion = DB::table('medicine_list_presentation')
                ->where('medicine_list_id', $lista->id)
                ->get()
                ->keyBy('medicine_presentation_id');
        }

        $rows = [];
        $medicationTotal = 0.0;

        foreach ($mezcla->medicamentos as $med) {
            [$cantidad, $precioUnitario, $subtotal] = $this->resolveOncoPricingForBilling($med, $cfgPorPresentacion, $listaCharge);
            $medicationTotal += $subtotal;

            $descripcion = $med->denominacion_snapshot
                ?? optional(optional($med->medicamentoOnco)->catalog)->denominacion
                ?? $med->nombre_medicamento
                ?? 'Medicamento oncológico';

            $rows[] = $this->makeBillingExportRow(
                institucion: $institucion?->nombre ?: '—',
                unidad: $item['hospital']?->name ?: '—',
                medico: $item['medico'] ?? '—',
                paciente: $item['patient_name'] ?? '—',
                remision: $mezcla->remision ?: '—',
                fechaRemision: $item['fecha'] ?? '—',
                cantidad: $this->formatBillingNumber($cantidad, 2),
                descripcion: $descripcion,
                pvUnitario: $this->formatBillingMoney($precioUnitario),
                pvTotal: $this->formatBillingMoney($subtotal),
                empresa: $item['empresa'] ?? '—',
                precioTotal: $billing?->precio_total ?: $this->formatBillingMoney($subtotal),
                conciliable: $billing?->conciliable ?: '—',
                folioUuid: $billing?->folio_factura_uuid ?: '—',
                folioInterno: $billing?->folio_interno ?: '—',
                fechaFacturacion: $billing?->fecha_facturacion ?: '—',
                numeroCartaFactura: $billing?->numero_carta_factura ?: '—',
                fechaCartaFactura: $billing?->fecha_carta_factura ?: '—',
            );
        }

        $billingTotal = $this->parseBillingMoney($billing?->precio_total);
        $serviceTotal = $this->resolveConfiguredOncoMixingServiceTotal($lista, $billingTotal, $medicationTotal);

        if ($serviceTotal > 0) {
            $rows[] = $this->makeBillingExportRow(
                institucion: $institucion?->nombre ?: '—',
                unidad: $item['hospital']?->name ?: '—',
                medico: $item['medico'] ?? '—',
                paciente: $item['patient_name'] ?? '—',
                remision: $mezcla->remision ?: '—',
                fechaRemision: $item['fecha'] ?? '—',
                cantidad: '1',
                descripcion: 'Servicio de Mezclado',
                pvUnitario: $this->formatBillingMoney($serviceTotal),
                pvTotal: $this->formatBillingMoney($serviceTotal),
                empresa: $item['empresa'] ?? '—',
                precioTotal: $billing?->precio_total ?: $this->formatBillingMoney($medicationTotal + $serviceTotal),
                conciliable: $billing?->conciliable ?: '—',
                folioUuid: $billing?->folio_factura_uuid ?: '—',
                folioInterno: $billing?->folio_interno ?: '—',
                fechaFacturacion: $billing?->fecha_facturacion ?: '—',
                numeroCartaFactura: $billing?->numero_carta_factura ?: '—',
                fechaCartaFactura: $billing?->fecha_carta_factura ?: '—',
            );
        }

        return $rows;
    }

    protected function buildNutriBillingRows(array $item): array
    {
        /** @var NutricionalSolicitud $solicitud */
        $solicitud = $item['record'];
        $solicitud->loadMissing([
            'billing',
            'input',
        ]);

        $billing = $solicitud->billing;
        $billingTotal = $this->parseBillingMoney($billing?->precio_total);

        if ($billingTotal <= 0) {
            $billingTotal = round((float) collect($solicitud->input ?? [])->sum(fn ($input) => (float) ($input->precio_ml ?? 0)), 2);
        }

        return [[
            $item['institucion']?->nombre ?: '—',
            $item['hospital']?->name ?: '—',
            $item['medico'] ?? '—',
            $item['patient_name'] ?? '—',
            $solicitud->remision ?: '—',
            $item['fecha'] ?? '—',
            '1',
            'Nutricion parenteral',
            $this->formatBillingMoney($billingTotal),
            $this->formatBillingMoney($billingTotal),
            $item['empresa'] ?? '—',
            $billing?->precio_total ?: $this->formatBillingMoney($billingTotal),
            $billing?->conciliable ?: '—',
            $billing?->folio_factura_uuid ?: '—',
            $billing?->folio_interno ?: '—',
            $billing?->fecha_facturacion ?: '—',
            $billing?->numero_carta_factura ?: '—',
            $billing?->fecha_carta_factura ?: '—',
        ]];
    }

    protected function resolveOncoPricingForBilling($med, Collection $cfgPorPresentacion, string $listaCharge): array
    {
        $cantidad = 0.0;
        $precioUnit = 0.0;
        $subtotal = 0.0;
        $presentaciones = $med->presentacionesUsadas ?? collect();

        if ($presentaciones->isEmpty()) {
            $unidadCobro = $listaCharge === 'mg' ? 'mg' : 'frasco';
            $cantidad = $unidadCobro === 'mg' ? (float) ($med->dosis ?? 0) : 1.0;

            if ($unidadCobro === 'mg') {
                $precioUnit = (float) ($med->precio_mg_snapshot ?? 0);
                $subtotal = $cantidad * $precioUnit;
            }

            return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
        }

        $first = $presentaciones->first();
        $presentationId =
            optional($first->batch)->medicine_presentation_id
            ?? optional(optional($first->batch)->presentation)->id
            ?? optional($first->presentation)->id;

        $cfg = $presentationId ? $cfgPorPresentacion->get($presentationId) : null;
        $chargeBy = $cfg->charge_by ?? $listaCharge;

        if ($chargeBy === 'frasco') {
            foreach ($presentaciones as $pu) {
                $unidades = (float) ($pu->unidades_usadas ?? 0);
                if ($unidades <= 0) {
                    $unidades = 1;
                }

                if (!is_null($pu->subtotal)) {
                    $cantidad += $unidades;
                    $subtotal += (float) $pu->subtotal;
                    continue;
                }

                if (!is_null($pu->precio_frasco_snapshot)) {
                    $cantidad += $unidades;
                    $subtotal += ((float) $pu->precio_frasco_snapshot * $unidades);
                    continue;
                }

                $pid =
                    optional($pu->batch)->medicine_presentation_id
                    ?? optional(optional($pu->batch)->presentation)->id
                    ?? optional($pu->presentation)->id;

                $cfgPres = $pid ? $cfgPorPresentacion->get($pid) : null;
                $precioFrasco = (float) ($cfgPres->precio ?? 0);

                $cantidad += $unidades;
                $subtotal += ($precioFrasco * $unidades);
            }

            $precioUnit = $cantidad > 0 ? $subtotal / $cantidad : 0.0;

            return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
        }

        $cantidad = (float) ($med->dosis ?? 0);
        $precioUnit = (float) ($cfg->precio_mg_override ?? $med->precio_mg_snapshot ?? 0);
        $subtotal = $cantidad * $precioUnit;

        return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
    }

    protected function makeBillingExportRow(
        string $institucion,
        string $unidad,
        string $medico,
        string $paciente,
        string $remision,
        string $fechaRemision,
        string $cantidad,
        string $descripcion,
        string $pvUnitario,
        string $pvTotal,
        string $empresa,
        string $precioTotal,
        string $conciliable,
        string $folioUuid,
        string $folioInterno,
        string $fechaFacturacion,
        string $numeroCartaFactura,
        string $fechaCartaFactura
    ): array {
        return [
            $institucion,
            $unidad,
            $medico,
            $paciente,
            $remision,
            $fechaRemision,
            $cantidad,
            $descripcion,
            $pvUnitario,
            $pvTotal,
            $empresa,
            $precioTotal,
            $conciliable,
            $folioUuid,
            $folioInterno,
            $fechaFacturacion,
            $numeroCartaFactura,
            $fechaCartaFactura,
        ];
    }

    protected function formatBillingMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    protected function formatBillingNumber(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', ',');
    }

    protected function parseBillingMoney($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : 0.0;
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
