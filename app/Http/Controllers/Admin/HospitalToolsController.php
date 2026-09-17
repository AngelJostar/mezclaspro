<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HospitalAdjustmentLogExport;
use App\Exports\HospitalConciliationExport;
use App\Exports\HospitalInvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\InstitutionBilling;
use App\Models\InstitutionBillingMovement;
use App\Models\MixtureAdjustment;
use App\Models\HospitalInvoiceAccount;
use App\Models\HospitalInvoicePayment;
use App\Models\HospitalConciliationSubmission;
use App\Services\HospitalInvoiceLedger;
use App\Services\HospitalConciliationSummary;
use App\Support\HospitalConciliationTable;
use App\Support\HospitalAdjustmentLogTable;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class HospitalToolsController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.hospital.herramientas', $this->listData($request));
    }

    public function exportInvoices(Request $request)
    {
        $data = $this->listData($request, 'facturacion', false);
        return Excel::download(new HospitalInvoiceExport($data['rows']), 'facturacion_hospital.xlsx');
    }

    public function exportConciliation(Request $request)
    {
        $data = $this->listData($request, 'conciliacion', false);
        return Excel::download(new HospitalConciliationExport($data['rows']), 'conciliacion_del_periodo.xlsx');
    }

    public function previewConciliation(Request $request, HospitalConciliationSummary $summary)
    {
        $data = $this->listData($request, 'conciliacion', false);
        abort_if($data['rows']->isEmpty(), 422, 'No hay mezclas para enviar en el periodo y filtros seleccionados.');
        $snapshot = $summary->snapshot($data['rows']);
        return response()->json([
            'confirmation_token' => $this->conciliationToken($request, $data, $snapshot),
            'summary' => $summary::summarize($snapshot, $request->user()->hospital->name, $data['from'], $data['to']),
        ])->header('Cache-Control', 'no-store');
    }

    private function conciliationToken(Request $request, array $data, array $snapshot): string
    {
        return HospitalConciliationSummary::signature([$request->user()->id, $request->user()->hospital_id, $data['filterQuery'], $snapshot]);
    }

    public function sendConciliation(Request $request, HospitalConciliationSummary $summary)
    {
        $data = $this->listData($request, 'conciliacion', false);
        $input = $request->validate([
            'submission_key' => ['required', 'uuid'], 'confirmation_token' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'reasons' => ['sometimes', 'array'], 'reasons.*' => ['nullable', 'string', 'max:2000'],
        ]);
        $user = $request->user();
        $reasons = array_map(fn ($value) => trim($value ?? ''), $input['reasons'] ?? []);
        $hash = $summary::signature([$user->id, $user->hospital_id, $data['filterQuery'], $input['confirmation_token'], $reasons]);
        $submission = HospitalConciliationSubmission::where('submission_key', $request->input('submission_key'))->first();
        if (! $submission) {
            abort_if($data['rows']->isEmpty(), 422, 'No hay mezclas para enviar en el periodo y filtros seleccionados.');
            $snapshot = $summary->snapshot($data['rows']);
            abort_unless(hash_equals($this->conciliationToken($request, $data, $snapshot), $input['confirmation_token']), 409,
                'Las mezclas o sus importes cambiaron. Cierra esta ventana y revisa el resumen actualizado antes de enviar.');
            $allowed = [];
            foreach ($snapshot as &$row) {
                if ($row['conciliable']) continue;
                $key = $row['kind'].'-'.$row['id'];
                $allowed[] = $key;
                $row['reason'] = $reasons[$key] ?? '';
                if ($row['reason'] === '') throw \Illuminate\Validation\ValidationException::withMessages([
                    'reasons.'.$key => 'Indica el motivo de la mezcla no conciliable #'.$row['id'].'.',
                ]);
            }
            unset($row);
            abort_if(array_diff(array_keys($reasons), $allowed), 422, 'Los motivos no corresponden a las mezclas del resumen.');
            // Store the submitted values, not references to a report that can change later.
            $submission = HospitalConciliationSubmission::firstOrCreate(['submission_key' => $request->input('submission_key')], [
                'hospital_id' => $user->hospital_id, 'submitted_by' => $user->id,
                'hospital_name' => $user->hospital->name, 'sender_name' => $user->name,
                'period_from' => $data['from'] ?: null, 'period_to' => $data['to'] ?: null, 'filters' => $data['filterQuery'],
                'mixture_count' => $data['rows']->count(), 'conciliable_count' => $data['rows']->where('conciliable', true)->count(),
                'snapshot' => $snapshot, 'request_hash' => $hash,
            ]);
        }
        abort_unless($submission->hospital_id === (int) $user->hospital_id && $submission->submitted_by === (int) $user->id
            && $submission->request_hash === $hash, 409, 'El identificador de envio ya se utilizo con otros datos.');

        return response()->json([
            'folio' => $submission->folio(),
            'summary' => $submission->summary(), 'status' => 'Enviada · Pendiente de revisión',
            'message' => 'Solicitud '.$submission->folio().' enviada a Prodifem. Disponible en Administración → Conciliación.',
        ], $submission->wasRecentlyCreated ? 201 : 200);
    }

    public function statement(Request $request)
    {
        $data = $this->listData($request, 'facturacion', false);
        $data['hospital'] = $request->user()->hospital;
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.hospital.invoice-statement', $data)
            ->setPaper('a4', 'landscape')->download('estado_de_cuenta.pdf');
    }

    public function invoiceDetail(Request $request, string $invoice)
    {
        return view('admin.hospital.invoice-detail', ['invoice' => $this->invoice($request, $invoice)]);
    }

    public function invoiceDocument(Request $request, string $invoice, string $format)
    {
        $row = $this->invoice($request, $invoice);
        $path = $row['account']?->{$format.'_path'};
        // Only private invoice files can be served, after checking hospital and mixture permissions.
        abort_unless($path && str_starts_with($path, 'hospital-invoices/') && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        return $disk->download($path, 'factura.'.$format, ['Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/xml', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function reportPayment(Request $request, string $invoice)
    {
        $row = $this->invoice($request, $invoice);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference' => ['required', 'string', 'max:150'], 'notes' => ['nullable', 'string', 'max:2000'],
            'submission_key' => ['required', 'uuid'],
        ]);
        return DB::transaction(function () use ($request, $row, $data, $invoice) {
            $account = HospitalInvoiceAccount::firstOrCreate(['invoice_key' => $invoice], [
                'hospital_id' => $request->user()->hospital_id, 'institucion_id' => $row['billing']->institucion_id,
            ]);
            $account = HospitalInvoiceAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $existing = HospitalInvoicePayment::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless($existing->account_id === $account->id && $existing->submitted_by === $request->user()->id, 409);
                abort_unless(HospitalInvoiceLedger::cents($existing->amount) === HospitalInvoiceLedger::cents($data['amount'])
                    && $existing->paid_at->toDateString() === $data['paid_at'] && $existing->reference === trim($data['reference'])
                    && ($existing->notes ?? '') === ($data['notes'] ?? ''), 409, 'El identificador del reporte ya se uso con otros datos.');
                return response()->json(['message' => 'El pago ya fue reportado. Está pendiente de validación.']);
            }
            $fresh = $this->invoice($request, $invoice);
            $amount = HospitalInvoiceLedger::cents($data['amount']);
            abort_unless($amount && $fresh['balance'] !== null && $amount <= $fresh['balance'] - $fresh['pending_reports'], 422, 'El importe supera el saldo disponible o ya existe un reporte pendiente.');
            $payment = $account->payments()->create([
                'submission_key' => $data['submission_key'], 'amount' => $data['amount'], 'paid_at' => $data['paid_at'],
                'reference' => trim($data['reference']), 'notes' => $data['notes'] ?? null,
                'status' => 'pending', 'submitted_by' => $request->user()->id,
            ]);
            InstitutionBillingMovement::create([
                'institution_billing_id' => $row['billing']->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                'origen_tipo' => $row['billing']->origen_tipo, 'origen_id' => $row['billing']->origen_id,
                'from_stage' => $row['billing']->workflowStage(), 'to_stage' => $row['billing']->workflowStage(),
                'details' => ['source' => 'hospital_payment_reported', 'payment_id' => $payment->id, 'folio' => $row['folio'],
                    'importe' => $payment->amount, 'referencia' => $payment->reference, 'estado' => 'Pendiente de validacion'],
            ]);
            return response()->json(['message' => 'Pago reportado. Pendiente de validación por Prodifem.'], 201);
        });
    }

    private function invoice(Request $request, string $key): array
    {
        $copy = Request::create($request->url(), 'GET', ['tab' => 'facturacion', 'desde' => '', 'hasta' => '', 'tramite_estado' => 'all']);
        $copy->setUserResolver(fn () => $request->user());
        $data = $this->listData($copy, 'facturacion', false);
        return $data['rows']->firstWhere('key', $key) ?? abort(404);
    }

    public function exportAdjustments(Request $request)
    {
        $data = $this->listData($request, 'ajustes', false);

        return Excel::download(new HospitalAdjustmentLogExport($data['rows']), 'bitacora_de_ajustes.xlsx');
    }

    public function adjustmentHistory(Request $request, string $kind, int $target)
    {
        $model = $this->query($request, $kind)->findOrFail($target);
        $row = $this->row($model, $kind);
        $versions = MixtureAdjustment::where('hospital_id', $request->user()->hospital_id)
            ->where('kind', $kind)->where('target_id', $target)->orderByDesc('id')->get();
        abort_if($versions->isEmpty(), 404);
        $actors = \App\Models\User::whereIn('id', $versions->flatMap(fn ($version) => [
            $version->requested_by, $version->authorized_by, $version->approved_by, $version->cancelled_by,
        ])->filter()->unique())->get(['id', 'name'])->keyBy('id');

        return view('admin.hospital.adjustment-history', compact('row', 'versions', 'actors'));
    }

    private function listData(Request $request, ?string $forcedTab = null, bool $paginate = true): array
    {
        $user = $request->user();
        abort_unless($user->hospital_id && ($user->can('nutricionales_solicitudes_index') || $user->can('oncologicos_solicitudes_index')), 403);
        $tableFields = ($forcedTab ?? $request->input('tab')) === 'ajustes'
            ? HospitalAdjustmentLogTable::fields() : HospitalConciliationTable::fields();
        $filters = $request->validate([
            'tab' => ['sometimes', Rule::in(['conciliacion', 'facturacion', 'ajustes'])],
            'periodo' => ['sometimes', Rule::in(['dia', 'mes', 'anio'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'todo_historial' => ['sometimes', 'boolean'],
            'ajuste_estado' => ['sometimes', Rule::in(['todos', 'requested', 'authorized', 'approved', 'rejected'])],
            'pago_estado' => ['sometimes', Rule::in(array_keys(HospitalInvoiceLedger::STATES))],
            'conciliacion_estado' => ['sometimes', Rule::in(['todas', 'conciliables', 'no_conciliables'])],
            'tramite_estado' => ['sometimes', Rule::in(['all', ...array_keys(HospitalInvoiceLedger::DOCUMENT_STATES)])],
            'aclaracion' => ['sometimes', Rule::in(['all', ...array_keys(HospitalInvoiceLedger::CLARIFICATIONS)])],
            'folio' => ['nullable', 'string', 'max:150'],
            'orden' => ['sometimes', Rule::in($tableFields)],
            'direccion' => ['sometimes', Rule::in(['asc', 'desc'])],
            'columnas' => ['sometimes', 'array:'.implode(',', $tableFields)],
            'columnas.*' => ['array', 'max:1000'],
            'columnas.*.*' => ['nullable', 'string', 'max:1000'],
        ]);
        $tab = $forcedTab ?? $filters['tab'] ?? 'conciliacion';
        $period = $filters['periodo'] ?? 'dia';
        $allHistory = $tab === 'ajustes' && $request->boolean('todo_historial', true);
        $adjustmentState = $filters['ajuste_estado'] ?? 'todos';
        $from = $request->exists('desde') ? ($filters['desde'] ?? '') : now()->startOfMonth()->toDateString();
        $to = $request->exists('hasta') ? ($filters['hasta'] ?? '') : now()->toDateString();
        if ($allHistory) $from = $to = '';
        if ($from && $to && $from > $to) {
            throw \Illuminate\Validation\ValidationException::withMessages(['hasta' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }
        if ($period !== 'dia') {
            $from = $from ? Carbon::parse($from)->{'startOf'.($period === 'mes' ? 'Month' : 'Year')}()->toDateString() : '';
            $to = $to ? Carbon::parse($to)->{'endOf'.($period === 'mes' ? 'Month' : 'Year')}()->toDateString() : '';
        }
        $targets = collect();
        foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $kind) {
            if (! $user->can($kind === 'nutricionales' ? 'nutricionales_solicitudes_index' : 'oncologicos_solicitudes_index')) continue;
            $query = $this->query($request, $kind)->with($kind === 'nutricionales'
                ? ['user.hospital.instituciones', 'solicitud_patient', 'solicitud_detail', 'billing', 'adjustment']
                : ['solicitud.hospital.instituciones', 'billing', 'adjustment']);
            if ($tab === 'conciliacion') {
                if ($kind === 'nutricionales') $this->dates($query, $from, $to);
                else $query->whereHas('solicitud', fn ($q) => $this->dates($q, $from, $to));
            }
            foreach ($query->get() as $target) $targets->put($kind.':'.$target->id, $this->row($target, $kind));
        }
        $rows = $targets->values();
        $invoiceData = [];
        if ($tab === 'facturacion') {
            $ledger = app(HospitalInvoiceLedger::class);
            $invoiceData = $ledger->filter($ledger->invoices($rows), $filters, $from, $to);
            $rows = $invoiceData['rows'];
            unset($invoiceData['rows']);
        }
        if ($tab === 'ajustes') {
            $query = MixtureAdjustment::where('hospital_id', $user->hospital_id)->where(function ($query) use ($targets) {
                foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $kind) {
                    $ids = $targets->where('kind', $kind)->pluck('id');
                    $query->orWhere(fn ($q) => $q->where('kind', $kind)->whereIn('target_id', $ids));
                }
            });
            // Filter the latest version of each mixture, without losing its older history.
            $versions = $query->orderByDesc('id')->get()->unique(fn ($version) => $version->kind.':'.$version->target_id);
            $versions = $versions->filter(function ($version) use ($from, $to, $adjustmentState) {
                if ($from && $version->created_at < Carbon::parse($from)->startOfDay()) return false;
                if ($to && $version->created_at > Carbon::parse($to)->endOfDay()) return false;
                return $adjustmentState === 'todos' || ($adjustmentState === 'rejected'
                    ? in_array($version->status, ['declined', 'rejected'], true)
                    : $version->status === $adjustmentState);
            });
            $rows = $versions->map(fn ($adjustment) => array_merge($targets[$adjustment->kind.':'.$adjustment->target_id], [
                'version' => $adjustment,
            ]));
        }
        $rows = $rows->sortByDesc('date')->values();
        $filterQuery = ['tab' => $tab, 'periodo' => $period, 'desde' => $from, 'hasta' => $to];
        if ($tab === 'ajustes') $filterQuery += ['todo_historial' => (int) $allHistory, 'ajuste_estado' => $adjustmentState];
        if ($tab === 'facturacion') $filterQuery += ['tramite_estado' => $filters['tramite_estado'] ?? 'received', 'pago_estado' => $filters['pago_estado'] ?? 'all', 'aclaracion' => $filters['aclaracion'] ?? 'all', 'folio' => $filters['folio'] ?? ''];
        if ($tab === 'conciliacion') {
            $conciliation = HospitalConciliationTable::prepare($rows, $filters);
            $rows = $conciliation['rows'];
            $conciliationState = $filters['conciliacion_estado'] ?? 'todas';
            if ($conciliationState !== 'todas') $rows = $rows->where('conciliable', $conciliationState === 'conciliables')->values();
            unset($conciliation['rows']);
            $invoiceData['conciliation'] = $conciliation;
            $filterQuery += ['orden' => $conciliation['sort'], 'direccion' => $conciliation['direction'], 'columnas' => $conciliation['selected'], 'conciliacion_estado' => $conciliationState];
        }
        if ($tab === 'ajustes') {
            $adjustmentTable = HospitalAdjustmentLogTable::prepare($rows, $filters);
            $rows = $adjustmentTable['rows'];
            unset($adjustmentTable['rows']);
            $invoiceData['adjustmentTable'] = $adjustmentTable;
            $filterQuery += ['orden' => $adjustmentTable['sort'], 'direccion' => $adjustmentTable['direction'], 'columnas' => $adjustmentTable['selected']];
        }
        if ($paginate) {
            $page = min($filters['page'] ?? 1, max(1, (int) ceil($rows->count() / 15)));
            $rows = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, [
                'path' => route('admin.hospital.herramientas'), 'query' => $filterQuery,
            ]);
        }

        return compact('tab', 'period', 'from', 'to', 'rows', 'allHistory', 'adjustmentState', 'filterQuery') + $invoiceData;
    }

    public function conciliable(Request $request, string $kind, int $target)
    {
        $data = $request->validate(['conciliable' => ['required', 'boolean'], 'previous' => ['present', 'nullable', 'string', 'max:255']]);
        return DB::transaction(function () use ($request, $kind, $target, $data) {
            $model = $this->query($request, $kind)->lockForUpdate()->findOrFail($target);
            $origin = $kind === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
            $billing = InstitutionBilling::where('origen_tipo', $origin)->where('origen_id', $target)->lockForUpdate()->first();
            abort_if($billing && (int) $billing->hospital_id !== (int) $request->user()->hospital_id, 404);
            abort_unless(($billing?->conciliable ?? '') === ($data['previous'] ?? ''), 409, 'El estado cambio. Actualiza la pantalla e intenta de nuevo.');
            if (! $billing) {
                $institutions = $request->user()->hospital->instituciones()->pluck('clientes.id');
                abort_unless($institutions->count() === 1, 422, 'Prodifem debe asignar la institucion de facturacion antes de conciliar esta mezcla.');
                $billing = new InstitutionBilling(['origen_tipo' => $origin, 'origen_id' => $target,
                    'hospital_id' => $request->user()->hospital_id, 'institucion_id' => $institutions->first()]);
            }
            $before = $billing->conciliable;
            $billing->conciliable = $data['conciliable'] ? 'Si' : 'No';
            if ($billing->isDirty('conciliable')) {
                $billing->save();
                InstitutionBillingMovement::create([
                    'institution_billing_id' => $billing->id, 'user_id' => $request->user()->id,
                    'user_name' => $request->user()->name, 'origen_tipo' => $origin, 'origen_id' => $target,
                    'remision' => $model->remision, 'from_stage' => $billing->workflowStage(), 'to_stage' => $billing->workflowStage(),
                    'details' => ['source' => 'hospital_conciliation', 'before' => $before, 'after' => $billing->conciliable],
                ]);
            }
            return response()->json(['conciliable' => $billing->conciliable]);
        });
    }

    private function query(Request $request, string $kind)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['Cliente', 'Institucion']) && $user->hospital_id, 403);
        abort_unless($user->can($kind === 'nutricionales' ? 'nutricionales_solicitudes_index' : 'oncologicos_solicitudes_index'), 403);
        return $kind === 'nutricionales'
            ? Solicitud::where('user_id', $user->id)
            : Mezcla::whereHas('solicitud', fn ($q) => $q->where('hospital_id', $user->hospital_id)->where('tipo_solicitud', $kind));
    }

    private function dates($query, string $from, string $to): void
    {
        if ($from) $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        if ($to) $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
    }

    private function row($target, string $kind): array
    {
        $nutrition = $kind === 'nutricionales';
        $parent = $nutrition ? $target : $target->solicitud;
        $hospital = $nutrition ? $target->user?->hospital : $parent->hospital;
        $billing = $target->billing;
        if ($billing && (int) $billing->hospital_id !== (int) $hospital?->id) $billing = null;
        $status = str_replace('-', '_', mb_strtolower(trim((string) ($nutrition ? $target->estado : $target->operational_status))));
        if ($target->currentAdjustment()?->isPending() && ! in_array($status, ['cancelada', 'no_aprobada'])) $status = 'en_ajuste';
        return [
            'kind' => $kind, 'id' => $target->id, 'request_id' => $parent->id, 'target' => $target,
            'hospital' => $hospital?->name ?? 'Sin hospital',
            'institution' => $hospital?->instituciones->pluck('nombre')->filter()->unique()->implode(', ') ?: 'Sin institución',
            'lot' => $target->lote,
            'approval' => match (true) {
                in_array($status, ['aprobada', 'dispensada', 'preparada', 'enproceso', 'revisada', 'inspeccionada', 'entregada', 'finalizada'], true) => 'Aprobada',
                in_array($status, ['cancelada', 'no_aprobada'], true) => 'Rechazada',
                default => 'Pendiente',
            },
            'patient' => $nutrition ? trim($target->solicitud_patient?->nombre_paciente.' '.$target->solicitud_patient?->apellidos_paciente) : $parent->nombre_paciente,
            'date' => $parent->created_at, 'status' => $status, 'billing' => $billing,
            'delivery_date' => $nutrition ? $target->solicitud_detail?->fecha_hora_entrega : ($target->fecha_entrega ?? $parent->fecha_entrega),
            'conciliable' => blank($billing?->conciliable)
                || in_array(Str::lower(Str::ascii(trim($billing->conciliable))), ['si', 'yes', 'true', '1', 'conciliado', 'conciliable'], true),
            'url' => $nutrition ? route('admin.nutricionales.solicitudes.show', $target) : route('admin.oncologicos.mezclas.show', $target),
        ];
    }
}
