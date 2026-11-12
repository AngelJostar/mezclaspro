<?php

namespace App\Services\Agents;

use App\Models\{AiAgent, AiAgentDelivery, ConciliationPeriodItem, Hospital, HospitalConciliationSubmission, User};
use App\Services\{ConciliationPeriodData, ConciliationPeriodWriter};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AutomaticConciliation
{
    public function __construct(private ConciliationPeriodData $data, private ConciliationPeriodWriter $writer) {}

    public function run(AiAgent $agent, User $actor, string $trigger = 'manual')
    {
        app(AgentAccess::class)->authorize($actor, $agent, 'can_run');
        abort_unless(!isset($actor->is_active) || $actor->is_active, 403);
        if ($trigger !== 'manual') abort_unless($actor->hasRole('Super Admin'), 403);
        abort_unless($agent->integration_key === OperationalAgents::CONCILIATION, 404);
        $lock = Cache::lock('ai-agent-run:'.$agent->id, 600);
        if (!$lock->get()) throw ValidationException::withMessages(['execution' => 'El agente ya tiene una ejecución en curso.']);
        try {
            $agent->refresh();
            if (!$agent->is_active) throw ValidationException::withMessages(['execution' => 'Activa el agente antes de ejecutarlo.']);
            $owner = User::find($agent->configured_by);
            if (!$owner?->hasRole('Super Admin') || (isset($owner->is_active) && !$owner->is_active)) {
                throw ValidationException::withMessages(['execution' => 'Un superadministrador activo debe guardar la programación antes de ejecutar el agente.']);
            }
            $config = OperationalAgents::validate($agent->configuration ?? []);
            if (app(AgentAccess::class)->isHospitalScoped($actor)) {
                $config['hospital_ids'] = array_values(array_intersect($config['hospital_ids'], $actor->accessibleHospitalIds()));
                if (!$config['hospital_ids']) throw ValidationException::withMessages(['execution' => 'No hay hospitales autorizados para tu usuario en esta programación.']);
            }
            if ($trigger !== 'manual' && $agent->next_run_at?->isFuture()) return null;
            if ($trigger !== 'manual' && $agent->runs()->where('trigger', 'scheduled')->whereIn('status', ['partial', 'failed'])
                ->where('finished_at', '>', now()->subMinutes(15))->exists()) return null;
            $at = $trigger !== 'manual' && $agent->next_run_at
                ? CarbonImmutable::instance($agent->next_run_at) : CarbonImmutable::now(OperationalAgents::TIMEZONE);
            $period = OperationalAgents::period($config, $at);
            $run = $agent->runs()->create(['user_id' => $actor->id, 'trigger' => $trigger, 'status' => 'running',
                'started_at' => now(), 'configuration' => $config + $period]);
            $results = []; $issues = [];
            try {
                foreach ($config['hospital_ids'] as $hospitalId) {
                    $hospital = Hospital::with('instituciones')->find($hospitalId);
                    if (!$hospital || !$hospital->is_active || !$hospital->access_is_active) {
                        $issues[] = ($hospital?->name ?? 'Hospital #'.$hospitalId).': hospital inactivo o sin acceso.'; continue;
                    }
                    foreach ($hospital->instituciones as $institution) {
                        if (!$institution->is_active) { $issues[] = $institution->nombre.': institución inactiva.'; continue; }
                        try {
                            $result = DB::transaction(function () use ($agent, $run, $actor, $config, $period, $hospital, $institution) {
                                // Serialize with manual creation and re-read remittances under the same source locks.
                                \App\Models\Institucion::whereKey($institution->id)->lockForUpdate()->firstOrFail();
                                $this->writer->lockGroup(['hospital_id' => $hospital->id]);
                                $scope = ['ai_agent_id' => $agent->id, 'institution_id' => $institution->id, 'hospital_id' => $hospital->id,
                                    'period_from' => $period['desde'], 'period_to' => $period['hasta']];
                                if ($existing = AiAgentDelivery::where($scope)->first()) return ['hospital' => $hospital->name, 'status' => 'Ya enviado', 'delivery_id' => $existing->id];
                                $filters = ['institucion_id' => $institution->id, 'hospital_id' => $hospital->id] + $period;
                                $assigned = ConciliationPeriodItem::all()->keyBy(fn ($item) => $item->kind.'-'.$item->mixture_id);
                                $sent = HospitalConciliationSubmission::where('hospital_id', $hospital->id)->where('direction', 'sent')->get()
                                    ->flatMap(fn ($submission) => collect($submission->snapshot)->map(fn ($row) => $row['kind'].'-'.$row['id']))->flip();
                                $rows = $this->data->rows($filters)->reject(fn ($row) => $assigned->has($row['key']) || $sent->has($row['key']));
                                if ($rows->isEmpty()) return ['hospital' => $hospital->name, 'status' => 'Sin remisiones nuevas'];
                                $result = $this->writer->create($actor, $filters + ['creation_key' => (string) Str::uuid(), 'creation_action' => 'send',
                                    'selection' => $rows->mapWithKeys(fn ($row) => [$row['key'] => ConciliationPeriodData::candidateVersion($row)])->all()]);
                                $saved = $result['periods']->sole();
                                $submission = HospitalConciliationSubmission::where('hospital_id', $hospital->id)->where('direction', 'sent')
                                    ->where('filters->period_id', $saved->id)->sole();
                                $delivery = AiAgentDelivery::create($scope + ['ai_agent_run_id' => $run->id, 'conciliation_period_id' => $saved->id, 'submission_id' => $submission->id]);
                                if ($config['email_enabled']) {
                                    $addresses = [$config['hospital_emails'][$hospital->id] ?? $hospital->email,
                                        $config['institution_emails'][$institution->id] ?? $institution->account?->email];
                                    foreach (array_unique($addresses) as $email) {
                                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw ValidationException::withMessages(['email' => 'Falta el correo del hospital o de su institución.']);
                                        $delivery->emails()->create(['recipient' => $email]);
                                    }
                                }
                                return ['hospital' => $hospital->name, 'status' => 'Enviada', 'delivery_id' => $delivery->id, 'folio' => $submission->folio(), 'remissions' => $rows->count()];
                            }, 3);
                            $results[] = $result;
                        } catch (\Throwable $e) {
                            report($e);
                            $message = $e instanceof ValidationException ? collect($e->errors())->flatten()->implode(' ')
                                : ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException ? $e->getMessage() : 'No se pudo completar el envío; revisa las remisiones y sus precios.');
                            $issues[] = $hospital->name.': '.$message;
                        }
                    }
                    if ($hospital->instituciones->isEmpty()) $issues[] = $hospital->name.': sin institución relacionada.';
                }
                $run->update(['status' => $issues ? 'partial' : 'completed', 'finished_at' => now(),
                    'result' => ['deliveries' => $results, 'issues' => $issues, 'period' => $period]]);
            } catch (\Throwable $e) {
                report($e);
                $run->update(['status' => 'failed', 'finished_at' => now(), 'result' => ['deliveries' => $results, 'issues' => ['La ejecución se interrumpió. Los envíos completados se conservan sin duplicarse.']]]);
            }
            if ($trigger !== 'manual' || !$agent->next_run_at) {
                // Keep the failed cycle due, retrying after 15 minutes; completed hospitals are deduplicated.
                $agent->next_run_at = ($trigger !== 'manual' && $run->status !== 'completed' ? $at : OperationalAgents::next($config, $at))
                    ->setTimezone(config('app.timezone'));
                $agent->save();
            }
            app(ConciliationDeliveryMailer::class)->pending();
            return $run->fresh();
        } finally { $lock->release(); }
    }
}
