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
use Maatwebsite\Excel\Facades\Excel;

class InstitucionBillingController extends Controller
{
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
            $conciliableFilter
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
        ]);

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
            $conciliableFilter
        )->map(function ($item) {
            return $item['export_row'];
        })->values()->all();

        $fileName = 'facturacion_instituciones_general_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InstitutionBillingExport($rows), $fileName);
    }

    protected function buildMergedRecords($institucionId, $hospitalId, string $search, $dateFrom, $dateTo, string $billingStatus, string $facturacionStatus = '', string $conciliableFilter = '')
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
            $tipoTexto = 'Mezcla oncológica';
            $viewRoute = route('admin.oncologicos.mezclas.show', $record);
            $viewLabel = 'Ver mezcla';
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
            $tipoTexto = 'Solicitud nutricional';
            $viewRoute = route('admin.nutricionales.solicitudes.show', $record);
            $viewLabel = 'Ver solicitud';
        }

        $fecha = $fechaModel ? Carbon::parse($fechaModel)->format('d/m/Y H:i') : '—';
        $billing = $record->billing;

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
            'export_row' => [
                $institucionActual?->nombre ?: '—',
                $hospital?->name ?: '—',
                $medico,
                $patientName,
                $record->remision ?: '—',
                $fecha,
                '1',
                $tipoTexto,
                $billing?->precio_total ?: '—',
                $billing?->precio_total ?: '—',
                $institucionActual?->razon_social ?: ($institucionActual?->nombre ?: '—'),
                $billing?->precio_total ?: '—',
                $billing?->conciliable ?: '—',
                $billing?->folio_factura_uuid ?: '—',
                $billing?->folio_interno ?: '—',
                $billing?->fecha_facturacion ?: '—',
                $billing?->numero_carta_factura ?: '—',
                $billing?->fecha_carta_factura ?: '—',
            ],
        ];
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
