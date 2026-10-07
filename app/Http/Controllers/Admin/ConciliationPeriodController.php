<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\ConciliationPeriod;
use App\Models\ConciliationPeriodItem;
use App\Models\HospitalConciliationSubmission;
use App\Models\Institucion;
use App\Models\InstitutionBilling;
use App\Models\InstitutionBillingMovement;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Services\ConciliationPeriodData;
use App\Services\HospitalConciliationSummary;
use App\Support\AdministrationNavigation;
use App\Support\ConciliationInboxTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConciliationPeriodController extends Controller
{
    public function __construct(private ConciliationPeriodData $periods) {}

    private function authorizeInternal(Request $request): void
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        abort_unless(AdministrationNavigation::canViewReports($request->user()), 403);
    }

    private function filters(Request $request, bool $required = false): array
    {
        $this->authorizeInternal($request);
        $presence = $required ? 'required' : 'nullable';
        $filters = $request->validate([
            'institucion_id' => [$presence, 'integer', 'exists:clientes,id'],
            'hospital_id' => [$presence, 'integer', 'exists:hospitals,id'],
            'desde' => [$presence, 'date_format:Y-m-d'],
            'hasta' => [$presence, 'date_format:Y-m-d', 'after_or_equal:desde'],
            'period_id' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);
        $filters['desde'] = $filters['desde'] ?? now('America/Mexico_City')->subMonthNoOverflow()->startOfMonth()->toDateString();
        $filters['hasta'] = $filters['hasta'] ?? now('America/Mexico_City')->toDateString();
        foreach (['institucion_id', 'hospital_id'] as $field) $filters[$field] = !empty($filters[$field]) ? (int) $filters[$field] : null;
        abort_if($filters['desde'] > $filters['hasta'], 422, 'La fecha final debe ser posterior a la inicial.');
        if (!empty($filters['hospital_id']) && !empty($filters['institucion_id'])) {
            abort_unless(Hospital::whereKey($filters['hospital_id'])->whereHas('instituciones', fn ($q) => $q->where('clientes.id', $filters['institucion_id']))->exists(), 422, 'El hospital no pertenece a la institución seleccionada.');
        }
        return $filters;
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $activeTab = $request->validate(['bandeja' => ['nullable', Rule::in(array_keys(ConciliationInboxTable::TABS))]])['bandeja'] ?? 'todas';
        $groups = $this->periods->groups($filters);
        $tabCounts = ['todas' => $groups->count()];
        foreach (['recibidas', 'enviadas', 'pendientes'] as $tab) $tabCounts[$tab] = $groups->where('bucket', $tab)->count();
        if ($activeTab !== 'todas') $groups = $groups->where('bucket', $activeTab)->values();
        $filterQuery = $filters + ['seccion' => 'conciliacion', 'modalidad' => 'periodo'];
        $institutions = Institucion::orderBy('nombre')->get(['id', 'nombre']);
        $hospitals = Hospital::with('instituciones:id,nombre')->orderBy('name')->get(['id', 'name']);
        return view('admin.instituciones.conciliacion.period', compact('groups', 'filters', 'institutions', 'hospitals', 'activeTab', 'tabCounts', 'filterQuery'));
    }

    private function group(array $filters): array
    {
        $group = $this->periods->groups($filters)->first(fn ($group) => $group['from'] === $filters['desde'] && $group['to'] === $filters['hasta']
            && ($group['period_id'] ?? null) == ($filters['period_id'] ?? null));
        abort_unless($group, 404, 'No hay remisiones para este periodo. Actualiza el listado.');
        abort_if(collect($group['rows'])->contains(fn ($row) => $row['unavailable'] ?? false), 409, 'Una remisión del periodo dejó de estar disponible. Revisa sus datos antes de continuar.');
        return $group;
    }

    public function candidates(Request $request)
    {
        $this->authorizeInternal($request);
        return response()->json(['rows' => $this->periods->candidates()])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $this->authorizeInternal($request);
        $data = $request->validate([
            'creation_key' => ['required', 'uuid'],
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'institucion_id' => ['required', 'integer', 'exists:clientes,id'],
            'selection' => ['required', 'array', 'min:1', 'max:10000'],
            'selection.*' => ['required', 'string', 'size:64'],
        ]);
        ksort($data['selection']);
        $hash = HospitalConciliationSummary::signature([$request->user()->id, $data['hospital_id'], $data['institucion_id'], $data['selection']]);
        return DB::transaction(function () use ($request, $data, $hash) {
            $this->lockGroup($data);
            $existing = ConciliationPeriod::where('creation_key', $data['creation_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'Esta operación corresponde a otra selección. Vuelve a abrir Crear periodo.');
                return $this->createdResponse($existing);
            }
            $rows = $this->periods->rows($data)->keyBy('key');
            $selected = collect();
            foreach ($data['selection'] as $key => $version) {
                $row = $rows->get($key);
                abort_unless($row && hash_equals(ConciliationPeriodData::candidateVersion($row), $version), 409,
                    'Una remisión cambió o no pertenece a la institución y hospital seleccionados. Actualiza las remisiones y revisa la selección.');
                abort_if(ConciliationPeriodItem::where('kind', $row['kind'])->where('mixture_id', $row['id'])->exists(), 409,
                    'Una remisión seleccionada ya está incluida en otro periodo. Actualiza las remisiones.');
                $selected->push($row);
            }
            $period = ConciliationPeriod::create([
                'creation_key' => $data['creation_key'], 'request_hash' => $hash,
                'institution_id' => $data['institucion_id'], 'hospital_id' => $data['hospital_id'],
                'period_from' => substr($selected->min('date'), 0, 10), 'period_to' => substr($selected->max('date'), 0, 10),
                'created_by' => $request->user()->id,
            ]);
            $period->items()->createMany($selected->map(fn ($row) => ['kind' => $row['kind'], 'mixture_id' => $row['id'], 'snapshot' => $row])->all());
            return $this->createdResponse($period, 201);
        });
    }

    private function createdResponse(ConciliationPeriod $period, int $status = 200)
    {
        return response()->json(['period_id' => $period->id, 'redirect' => route('admin.instituciones.reportes', [
            'seccion' => 'conciliacion', 'modalidad' => 'periodo', 'bandeja' => 'todas',
            'institucion_id' => $period->institution_id, 'hospital_id' => $period->hospital_id,
            'desde' => $period->period_from->toDateString(), 'hasta' => $period->period_to->toDateString(),
            'creado' => $period->id,
        ])], $status);
    }

    private function responseFor(array $group)
    {
        return response()->json(['group' => $group, 'html' => view('admin.instituciones.conciliacion._period-detail', compact('group'))->render()])
            ->header('Cache-Control', 'no-store');
    }

    public function detail(Request $request)
    {
        return $this->responseFor($this->group($this->filters($request, true)));
    }

    // Use the same source-row locks as individual billing edits, then read a fresh version.
    private function lockGroup(array $filters): void
    {
        Hospital::whereKey($filters['hospital_id'])->lockForUpdate()->firstOrFail();
        Solicitud::where('hospital_id', $filters['hospital_id'])->orderBy('id')->lockForUpdate()->get(['id']);
        Mezcla::whereHas('solicitud', fn ($q) => $q->where('hospital_id', $filters['hospital_id']))->orderBy('id')->lockForUpdate()->get(['id']);
        InstitutionBilling::where('hospital_id', $filters['hospital_id'])->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function checkVersion(array $group, string $version): void
    {
        abort_unless(hash_equals($group['version'], $version), 409, 'Las remisiones cambiaron. Cierra el detalle y vuelve a abrirlo para revisar los importes actuales.');
    }

    public function accept(Request $request)
    {
        $filters = $this->filters($request, true);
        $data = $request->validate(['version' => ['required', 'string', 'size:64'], 'choices' => ['required', 'array'], 'choices.*' => ['required', 'boolean']]);
        return DB::transaction(function () use ($request, $filters, $data) {
            $this->lockGroup($filters);
            $group = $this->group($filters);
            $this->checkVersion($group, $data['version']);
            $keys = array_column($group['rows'], 'key');
            abort_unless(count($data['choices']) === count($keys) && !array_diff(array_keys($data['choices']), $keys), 422, 'La selección debe contener todas las remisiones de este periodo.');
            foreach ($group['rows'] as $row) {
                $value = (bool) $data['choices'][$row['key']];
                if ($value === $row['conciliable']) continue;
                $origin = $row['kind'] === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
                $billing = InstitutionBilling::firstOrNew(['origen_tipo' => $origin, 'origen_id' => $row['id']]);
                if (!$billing->exists) $billing->fill(['hospital_id' => $group['hospital_id'], 'institucion_id' => $group['institution_id']]);
                $before = $billing->conciliable;
                $billing->conciliable = $value ? 'Si' : 'No';
                $billing->save();
                InstitutionBillingMovement::create([
                    'institution_billing_id' => $billing->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                    'origen_tipo' => $origin, 'origen_id' => $row['id'], 'remision' => $row['remision'],
                    'from_stage' => $billing->workflowStage(), 'to_stage' => $billing->workflowStage(),
                    'details' => ['source' => 'administration_conciliation_period', 'field' => 'conciliable', 'before' => $before, 'after' => $billing->conciliable,
                        'period_from' => $group['from'], 'period_to' => $group['to']],
                ]);
            }
            return $this->responseFor($this->group($filters));
        });
    }

    public function send(Request $request)
    {
        $filters = $this->filters($request, true);
        $data = $request->validate(['version' => ['required', 'string', 'size:64'], 'submission_key' => ['required', 'uuid']]);
        return DB::transaction(function () use ($request, $filters, $data) {
            $this->lockGroup($filters);
            $hash = HospitalConciliationSummary::signature([$request->user()->id, $filters, $data['version']]);
            $existing = HospitalConciliationSubmission::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash ?? '', $hash), 409, 'Este envío corresponde a otro resumen. Actualiza el listado.');
                return response()->json(['folio' => $existing->folio()]);
            }
            $group = $this->group($filters);
            $this->checkVersion($group, $data['version']);
            abort_if($group['missing_prices'], 422, 'Registra el precio de todas las remisiones antes de enviar el periodo.');
            if ($group['sent_folio']) return response()->json(['folio' => $group['sent_folio']]);
            $submission = HospitalConciliationSubmission::create([
                'submission_key' => $data['submission_key'], 'request_hash' => $hash, 'direction' => 'sent',
                'hospital_id' => $group['hospital_id'], 'hospital_name' => $group['hospital'],
                'submitted_by' => $request->user()->id, 'sender_name' => $request->user()->name,
                'period_from' => $group['from'], 'period_to' => $group['to'],
                'filters' => ['institucion_id' => $group['institution_id'], 'period_content_hash' => $group['version'], 'modalidad' => 'periodo']
                    + (isset($group['period_id']) ? ['period_id' => $group['period_id']] : []),
                'mixture_count' => count($group['rows']), 'conciliable_count' => collect($group['rows'])->where('conciliable', true)->count(),
                'snapshot' => ConciliationPeriodData::snapshot($group),
            ]);
            return response()->json(['folio' => $submission->folio()]);
        });
    }
}
