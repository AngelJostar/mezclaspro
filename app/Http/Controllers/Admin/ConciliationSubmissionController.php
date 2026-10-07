<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HospitalConciliationExport;
use App\Http\Controllers\Controller;
use App\Models\HospitalConciliationSubmission;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\InstitutionBilling;
use App\Models\InstitutionBillingMovement;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Services\ConciliationInboxData;
use App\Support\AdministrationNavigation;
use App\Support\ConciliationInboxFilters;
use App\Support\ConciliationInboxTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ConciliationSubmissionController extends Controller
{
    public function __construct(private ConciliationInboxData $inbox) {}

    private function authorizeInternal(Request $request): void
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        abort_unless(AdministrationNavigation::canViewReports($request->user()), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeInternal($request);
        $request->validate(['modalidad' => ['sometimes', Rule::in(['periodo', 'remision'])]]);
        if ($request->query('modalidad') === 'periodo') return app(ConciliationPeriodController::class)->index($request);
        $filters = ConciliationInboxFilters::validate($request->query());
        $fields = array_keys(ConciliationInboxTable::COLUMNS);
        $tableFilters = $request->validate([
            'columnas' => ['sometimes', 'array:'.implode(',', $fields)],
            'columnas.*' => ['array', 'max:1000'],
            'columnas.*.*' => ['nullable', 'string', 'max:1000'],
            'orden' => ['sometimes', Rule::in($fields)],
            'direccion' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'bandeja' => ['sometimes', Rule::in(array_keys(ConciliationInboxTable::TABS))],
        ]);
        $search = trim($filters['search'] ?? '');
        $institutionId = !empty($filters['institucion_id']) ? (int) $filters['institucion_id'] : null;
        $hospitalId = !empty($filters['hospital_id']) ? (int) $filters['hospital_id'] : null;
        $institutions = Institucion::orderBy('nombre')->get(['id', 'nombre']);
        $hospitals = Hospital::with('instituciones:id,nombre')->orderBy('name')->get(['id', 'name']);
        $requests = app(InstitucionBillingController::class)->conciliationRequests($institutionId, $hospitalId, $search);
        $hospitalIds = $requests['nutrition']->pluck('hospital_id')->merge($requests['onco']->pluck('solicitud.hospital_id'))->filter()->unique();
        $submissions = HospitalConciliationSubmission::whereIn('hospital_id', $hospitalIds)->latest('id')->get();
        $requestRows = $this->inbox->rows($requests, $submissions);
        $submissionRows = $requestRows->filter(fn ($row) => $row['submission'])
            ->groupBy(fn ($row) => $row['submission']->id)->map(fn ($rows) => $rows->pluck('mixture'));
        $table = ConciliationInboxTable::prepare($requestRows, $tableFilters);
        $allRows = $table['rows'];
        $activeTab = $tableFilters['bandeja'] ?? 'todas';
        $tabCounts = ['todas' => $allRows->count()];
        foreach (['recibidas', 'enviadas', 'pendientes'] as $tab) $tabCounts[$tab] = $allRows->where('bucket', $tab)->count();
        if ($activeTab !== 'todas') $allRows = $allRows->where('bucket', $activeTab)->values();
        unset($table['rows']);
        $filterQuery = ['seccion' => 'conciliacion', 'modalidad' => 'remision'] + $filters + $tableFilters;
        unset($filterQuery['page']);
        $rows = $allRows->values();
        $table += ['columns' => $fields, 'url' => route('admin.instituciones.reportes', $filterQuery)];

        return view('admin.instituciones.conciliacion.index', compact('rows', 'table', 'submissionRows', 'search', 'institutions', 'hospitals', 'institutionId', 'hospitalId', 'activeTab', 'tabCounts', 'filterQuery'));
    }

    public function conciliable(Request $request, HospitalConciliationSubmission $submission, string $kind, int $target)
    {
        $this->authorizeMixture($request, $submission, $kind, $target);
        $data = $request->validate(['conciliable' => ['required', 'boolean'], 'previous' => ['present', 'nullable', 'string', 'max:255']]);

        return $this->updateBillingField($request, $submission, $kind, $target, 'conciliable', $data['conciliable'] ? 'Si' : 'No', $data['previous']);
    }

    public function requestConciliable(Request $request, string $kind, int $target)
    {
        $this->authorizeInternal($request);
        $data = $request->validate(['conciliable' => ['required', 'boolean'], 'previous' => ['present', 'nullable', 'string', 'max:255']]);
        return $this->updateBillingField($request, null, $kind, $target, 'conciliable', $data['conciliable'] ? 'Si' : 'No', $data['previous']);
    }

    public function requestPrice(Request $request, string $kind, int $target)
    {
        $this->authorizeInternal($request);
        $data = $this->priceData($request);
        return $this->updateBillingField($request, null, $kind, $target, 'precio_total', number_format((float) $data['precio_total'], 2, '.', ''), $data['previous']);
    }

    public function price(Request $request, HospitalConciliationSubmission $submission, string $kind, int $target)
    {
        $this->authorizeMixture($request, $submission, $kind, $target);
        $data = $this->priceData($request);

        return $this->updateBillingField($request, $submission, $kind, $target, 'precio_total', number_format((float) $data['precio_total'], 2, '.', ''), $data['previous']);
    }

    private function priceData(Request $request): array
    {
        return $request->validate([
            'precio_total' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'previous' => ['present', 'nullable', 'string', 'max:255'],
        ], ['precio_total.regex' => 'Captura un precio válido, sin comas, mayor o igual a cero y con hasta dos decimales.']);
    }

    private function authorizeMixture(Request $request, HospitalConciliationSubmission $submission, string $kind, int $target): void
    {
        $this->authorizeInternal($request);
        abort_unless(in_array($kind, ['nutricionales', 'oncologicos', 'antibioticos'], true)
            && collect($submission->snapshot)->contains(fn ($row) => $row['kind'] === $kind && (int) $row['id'] === $target), 404);
    }

    private function updateBillingField(Request $request, ?HospitalConciliationSubmission $submission, string $kind, int $target, string $field, string $value, ?string $previous)
    {
        return DB::transaction(function () use ($request, $submission, $kind, $target, $field, $value, $previous) {
            $model = $kind === 'nutricionales' ? Solicitud::lockForUpdate()->findOrFail($target)
                : Mezcla::whereHas('solicitud', fn ($q) => $q->where('tipo_solicitud', $kind))->lockForUpdate()->findOrFail($target);
            $hospitalId = (int) ($kind === 'nutricionales' ? $model->hospital_id : $model->solicitud->hospital_id);
            if ($submission) abort_unless($hospitalId === $submission->hospital_id, 404);
            abort_unless(Hospital::whereKey($hospitalId)->whereHas('instituciones')->exists(), 404);
            $origin = $kind === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
            $billing = InstitutionBilling::where('origen_tipo', $origin)->where('origen_id', $target)->lockForUpdate()->first();
            abort_if($billing && (int) $billing->hospital_id !== $hospitalId, 404);
            abort_unless(($billing?->{$field} ?? '') === ($previous ?? ''), 409, 'El valor cambió. Recarga la pantalla antes de intentarlo nuevamente.');
            if (!$billing) {
                $institutions = Hospital::findOrFail($hospitalId)->instituciones()->pluck('clientes.id');
                abort_unless($institutions->count() === 1, 422, 'Asigna una institución de facturación a esta mezcla antes de conciliarla.');
                $billing = new InstitutionBilling(['origen_tipo' => $origin, 'origen_id' => $target,
                    'hospital_id' => $hospitalId, 'institucion_id' => $institutions->first()]);
            }
            $before = $billing->{$field};
            $billing->{$field} = $value;
            if ($billing->isDirty($field)) {
                $billing->save();
                InstitutionBillingMovement::create([
                    'institution_billing_id' => $billing->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                    'origen_tipo' => $origin, 'origen_id' => $target, 'remision' => $model->remision,
                    'from_stage' => $billing->workflowStage(), 'to_stage' => $billing->workflowStage(),
                    'details' => ['source' => 'administration_conciliation', 'submission_id' => $submission?->id, 'field' => $field, 'before' => $before, 'after' => $billing->{$field}],
                ]);
            }
            return response()->json([
                $field => $billing->{$field},
                'conciliation_status' => ConciliationInboxTable::status($billing->conciliable, ConciliationInboxData::latestSubmission($hospitalId, $kind, $target)),
            ])->header('Cache-Control', 'no-store');
        });
    }

    public function show(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeInternal($request);
        $filters = $request->validate([
            'estado' => ['sometimes', Rule::in(['todas', 'si', 'no'])],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $state = $filters['estado'] ?? 'todas';
        $search = trim($filters['search'] ?? '');
        $summary = $submission->summary();
        $all = collect($submission->snapshot)->map(function ($row) {
            $row['conciliable'] = $row['conciliable'] ?? $row['cells']['conciliable'] !== 'No';
            $row['url'] = in_array($row['kind'], ['nutricionales', 'oncologicos', 'antibioticos'], true) && $row['id'] > 0
                ? route($row['kind'] === 'nutricionales' ? 'admin.nutricionales.solicitudes.show' : 'admin.oncologicos.mezclas.show', $row['id']) : null;
            return $row;
        });
        $institution = $all->pluck('cells.institution')->filter()->unique()->implode(', ') ?: 'Sin institución';
        $needle = Str::lower(Str::ascii($search));
        $rows = $all->filter(fn ($row) => ($state === 'todas' || $row['conciliable'] === ($state === 'si'))
            && ($needle === '' || str_contains(Str::lower(Str::ascii(implode(' ', [
                $row['cells']['request_id'], $row['cells']['id'], $row['cells']['patient'],
            ]))), $needle)))->values();
        $data = compact('submission', 'rows', 'summary', 'institution', 'state', 'search');
        if ($request->expectsJson()) return response()->json([
            'title' => 'Resumen de conciliación · '.$submission->folio(),
            'html' => view('admin.instituciones.conciliacion._summary', $data)->render(),
        ])->header('Cache-Control', 'no-store');
        return view('admin.instituciones.conciliacion.show', $data);
    }

    public function download(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeInternal($request);
        return Excel::download(new HospitalConciliationExport(collect($submission->snapshot), true), $submission->folio().'.xlsx');
    }
}
