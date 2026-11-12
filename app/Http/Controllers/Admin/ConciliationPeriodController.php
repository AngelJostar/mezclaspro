<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\ConciliationPeriod;
use App\Models\ConciliationPeriodDecision;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConciliationPeriodController extends Controller
{
    public function __construct(private ConciliationPeriodData $periods) {}

    private function authorizeInternal(Request $request): void
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403, 'Solo el personal de PROMESA Prodifem puede revisar y autorizar cambios de conciliación.');
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
        $createdId = $request->query('creado');
        $created = is_scalar($createdId) ? ConciliationPeriod::find((int) $createdId) : null;
        $createdIds = $created?->creation_batch_key
            ? ConciliationPeriod::where('creation_batch_key', $created->creation_batch_key)->pluck('id') : [$created?->id];
        $createdCount = $groups->whereNotNull('period_id')->whereIn('period_id', $createdIds)->count();
        $createdSentCount = $groups->whereNotNull('period_id')->whereIn('period_id', $createdIds)->whereNotNull('sent_at')->count();
        return view('admin.instituciones.conciliacion.period', compact('groups', 'filters', 'institutions', 'hospitals', 'activeTab', 'tabCounts', 'filterQuery', 'createdCount', 'createdSentCount'));
    }

    private function group(array $filters): array
    {
        $group = $this->periods->groups($filters)->first(fn ($group) => $group['from'] === $filters['desde'] && $group['to'] === $filters['hasta']
            && $group['institution_id'] == $filters['institucion_id'] && $group['hospital_id'] == $filters['hospital_id']
            && ($group['period_id'] ?? null) == ($filters['period_id'] ?? null));
        abort_unless($group, 404, 'No hay remisiones para este periodo. Actualiza el listado.');
        abort_if(collect($group['rows'])->contains(fn ($row) => $row['unavailable'] ?? false), 409, 'Una remisión del periodo dejó de estar disponible. Revisa sus datos antes de continuar.');
        abort_unless(count($group['rows']) && collect($group['rows'])->every(fn ($row) =>
            $row['institution_id'] == $group['institution_id'] && $row['hospital_id'] == $group['hospital_id']),
            409, 'Todas las remisiones del periodo deben pertenecer al mismo hospital e institución. Revisa el periodo antes de continuar.');
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
            'creation_action' => ['sometimes', 'required', Rule::in(['draft', 'send'])],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'institucion_id' => ['required', 'integer', 'exists:clientes,id'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'selection' => ['required', 'array', 'min:1', 'max:10000'],
            'selection.*' => ['required', 'string', 'size:64'],
        ], [
            'institucion_id.required' => 'Selecciona una institución.',
            'desde.required' => 'El campo Desde es obligatorio.',
            'hasta.required' => 'El campo Hasta es obligatorio.',
            'hasta.after_or_equal' => 'La fecha Hasta debe ser igual o posterior a Desde.',
        ]);
        $result = app(\App\Services\ConciliationPeriodWriter::class)->create($request->user(), $data);
        return $this->createdResponse($result['periods'], $result['status']);
    }

    private function createdResponse(Collection $periods, int $status = 200)
    {
        $first = $periods->first();
        return response()->json(['period_id' => $first->id, 'period_ids' => $periods->pluck('id')->values(), 'period_count' => $periods->count(), 'redirect' => route('admin.instituciones.reportes', [
            'seccion' => 'conciliacion', 'modalidad' => 'periodo', 'bandeja' => 'todas',
            'institucion_id' => $first->institution_id, 'hospital_id' => $periods->count() === 1 ? $first->hospital_id : null,
            'desde' => $first->period_from->toDateString(), 'hasta' => $first->period_to->toDateString(),
            'creado' => $first->id,
        ])], $status);
    }

    private function responseFor(array $group)
    {
        return response()->json(['group' => $group, 'html' => view('admin.instituciones.conciliacion._period-detail', compact('group'))->render(),
            'observations_html' => view('admin.instituciones.conciliacion._period-observations', compact('group'))->render()])
            ->header('Cache-Control', 'no-store');
    }

    public function detail(Request $request)
    {
        return $this->responseFor($this->group($this->filters($request, true)));
    }

    public function status(Request $request)
    {
        return response()->json(['groups' => $this->periods->groups($this->filters($request))
            ->map(fn ($group) => \Illuminate\Support\Arr::except($group, ['rows']))->values()])
            ->header('Cache-Control', 'no-store');
    }

    private function lockGroup(array $filters): void
    {
        app(\App\Services\ConciliationPeriodWriter::class)->lockGroup($filters);
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
            $updated = $this->group($filters);
            $this->recordDecision($request, $updated, 'review');
            return $this->responseFor($this->group($filters));
        });
    }

    private function recordDecision(Request $request, array $group, string $action, ?string $requestVersion = null): void
    {
        ConciliationPeriodDecision::firstOrCreate([
            'group_key' => $group['key'], 'version' => $group['review_version'], 'action' => $action,
        ], [
            'institution_id' => $group['institution_id'], 'hospital_id' => $group['hospital_id'],
            'period_from' => $group['from'], 'period_to' => $group['to'],
            'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
            'snapshot' => $group['rows'],
            'request_version' => $requestVersion,
        ]);
    }

    public function authorizeChanges(Request $request)
    {
        $filters = $this->filters($request, true);
        $data = $request->validate(['version' => ['required', 'string', 'size:64']]);
        return DB::transaction(function () use ($request, $filters, $data) {
            $this->lockGroup($filters);
            $group = $this->group($filters);
            if (ConciliationPeriodDecision::where('group_key', $group['key'])->where('action', 'authorize')
                ->where('version', $group['review_version'])->where('request_version', $data['version'])->exists()) {
                return $this->responseFor($group);
            }
            abort_unless(hash_equals($group['review_version'], $data['version']), 409, 'Las remisiones u observaciones cambiaron. Cierra la ventana y vuelve a abrirla antes de autorizar.');
            abort_unless($group['changes_count'], 422, 'Este periodo no tiene cambios por autorizar.');
            abort_if($group['missing_prices'], 422, 'Registra los precios faltantes antes de autorizar los cambios.');
            foreach ($group['rows'] as $row) {
                $requested = $row['client_request']['conciliable'] ?? null;
                if (!is_bool($requested)) continue;
                $origin = $row['kind'] === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
                $billing = InstitutionBilling::firstOrNew(['origen_tipo' => $origin, 'origen_id' => $row['id']]);
                if (!$billing->exists) $billing->fill(['hospital_id' => $group['hospital_id'], 'institucion_id' => $group['institution_id']]);
                $before = $billing->conciliable;
                $billing->conciliable = $requested ? 'Si' : 'No';
                $changed = $billing->isDirty('conciliable');
                $billing->save();
                if ($changed) InstitutionBillingMovement::create([
                    'institution_billing_id' => $billing->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                    'origen_tipo' => $origin, 'origen_id' => $row['id'], 'remision' => $row['remision'],
                    'from_stage' => $billing->workflowStage(), 'to_stage' => $billing->workflowStage(),
                    'details' => ['source' => 'administration_conciliation_authorization', 'field' => 'conciliable',
                        'before' => $before, 'after' => $billing->conciliable, 'submission_id' => $row['client_request']['submission_id'],
                        'reason' => $row['reason'], 'period_from' => $group['from'], 'period_to' => $group['to']],
                ]);
            }
            $this->recordDecision($request, $this->group($filters), 'authorize', $data['version']);
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
                return $this->sentResponse($existing);
            }
            $group = $this->group($filters);
            $this->checkVersion($group, $data['version']);
            abort_if($group['missing_prices'], 422, 'Registra el precio de todas las remisiones antes de enviar el periodo.');
            if ($group['sent_folio']) return response()->json(['folio' => $group['sent_folio'], 'sent_at' => $group['sent_at']]);
            return $this->sentResponse($this->createSubmission($request, $group, $data['submission_key'], $hash));
        });
    }

    private function createSubmission(Request $request, array $group, string $key, string $hash): HospitalConciliationSubmission
    {
        return app(\App\Services\ConciliationPeriodWriter::class)->submit($request->user(), $group, $key, $hash);
    }

    private function sentResponse(HospitalConciliationSubmission $submission)
    {
        return response()->json(['folio' => $submission->folio(), 'sent_at' => $submission->created_at?->format('d/m/Y H:i')]);
    }
}
