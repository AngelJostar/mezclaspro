<?php

namespace App\Services\Agents;

use App\Models\AiAgent;
use App\Models\AiAgentFinding;
use App\Models\AiAgentRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AgentRunner
{
    public function __construct(private AgentData $data, private AgentRules $rules) {}

    public function run(AiAgent $agent, User $actor, string $trigger = 'manual'): ?AiAgentRun
    {
        abort_unless($actor->hasRole('Super Admin'), 403);
        abort_if(isset($actor->is_active) && ! $actor->is_active, 403);
        $lock = Cache::lock('ai-agent-run:'.$agent->id, 600);
        if (! $lock->get()) throw ValidationException::withMessages(['execution' => 'Este agente ya tiene una ejecución en curso.']);
        try {
            $agent->refresh();
            if (! $agent->is_active) throw ValidationException::withMessages(['execution' => 'Activa el agente antes de ejecutarlo.']);
            if (! $agent->configuration) throw ValidationException::withMessages(['execution' => 'Configura y guarda el alcance y las reglas del agente.']);
            $config = AgentConfiguration::validate($agent->configuration, true);
            if ($trigger !== 'manual' && ($config['activation'] === 'manual' || $agent->next_run_at?->isFuture())) return null;
            $now = CarbonImmutable::now();
            $sources = [];
            $issues = [];
            $coverage = [];
            $required = collect($config['rules'])->flatMap(fn ($rule) => AgentConfiguration::rules()[$rule]['sources'])->unique();
            foreach ($required as $source) {
                if (! in_array($source, $config['sources'])) {
                    $issues[] = AgentConfiguration::SOURCES[$source].': consulta no autorizada.';
                    continue;
                }
                try {
                    $read = $this->data->read($source, $config);
                    $sources[$source] = $read['rows'];
                    $coverage[$source] = count($read['rows']);
                    foreach ($read['issues'] as $issue) $issues[] = AgentConfiguration::SOURCES[$source].': '.$issue;
                } catch (\Throwable $e) {
                    Log::warning('Agent source unavailable', ['agent_id' => $agent->id, 'source' => $source, 'exception' => get_class($e)]);
                    $issues[] = AgentConfiguration::SOURCES[$source].': fuente no disponible; revisar esquema y conexión.';
                }
            }
            $fingerprint = hash('sha256', json_encode([$config, $sources, $issues], JSON_THROW_ON_ERROR));
            if ($trigger !== 'manual' && $config['activation'] === 'changes' && $agent->source_fingerprint === $fingerprint) {
                $agent->next_run_at = now()->addMinute();
                $agent->save();
                return null;
            }
            // Persist interrupted runs separately; never fabricate a successful result after a crash.
            $agent->runs()->where('status', 'running')->where('started_at', '<', now()->subMinutes(10))->update(['status' => 'failed', 'finished_at' => now(), 'result' => json_encode(['issues' => ['Ejecución interrumpida.']])]);
            $run = $agent->runs()->create(['user_id' => $actor->id, 'trigger' => $trigger, 'status' => 'running', 'started_at' => $now, 'configuration' => $config + ['name' => $agent->name, 'instructions' => $agent->instructions, 'engine_version' => 1]]);
            try {
                $found = [];
                foreach ($config['rules'] as $rule) {
                    if ($rule === 'invoicing' && ! array_key_exists('billing', $sources)) continue;
                    if (! array_intersect(AgentConfiguration::rules()[$rule]['sources'], array_keys($sources))) continue;
                    try {
                        $found = array_merge($found, $this->rules->evaluate($rule, $sources, $config, $now));
                    } catch (\Throwable $e) {
                        Log::warning('Agent rule failed', ['agent_id' => $agent->id, 'rule' => $rule, 'exception' => get_class($e)]);
                        $issues[] = AgentConfiguration::rules()[$rule]['label'].': datos incompletos o inválidos.';
                    }
                }
                if (count($found) > 500) {
                    $issues[] = 'Se conservaron los primeros 500 hallazgos de esta ejecución; reduce el alcance para revisar el resto.';
                    $found = array_slice($found, 0, 500);
                }
                $analysis = null;
                if ($config['analysis'] === 'openai') {
                    try {
                        $analysis = app(OpenAiAgentAnalysis::class)->analyze($agent, $found, $issues, $coverage);
                    } catch (\RuntimeException $e) {
                        $issues[] = $e->getMessage();
                    }
                }
                DB::transaction(function () use ($agent, $run, $config, $found, $issues, $coverage, $fingerprint, $analysis) {
                    $stored = 0;
                    foreach ($found as $finding) {
                        if (! in_array('alerts', $config['tools'])) continue;
                        $recordKey = $finding['record']['record_type'].':'.$finding['record']['id'];
                        $key = hash('sha256', $finding['rule'].'|'.$recordKey.'|'.$finding['title']);
                        $alert = AiAgentFinding::firstOrNew(['ai_agent_id' => $agent->id, 'fingerprint' => $key]);
                        $alert->fill(['ai_agent_run_id' => $run->id, 'rule' => $finding['rule'], 'priority' => $finding['priority'], 'evidence' => $finding, 'owner' => $config['owner'], 'last_seen_at' => now()]);
                        // A repeat updates evidence, without undoing a human resolution or dismissal.
                        $alert->save();
                        $stored++;
                    }
                    $run->update(['status' => $issues ? ($coverage ? 'partial' : 'failed') : 'completed', 'finished_at' => now(),
                        'result' => ['engine' => 'Reglas del sistema', 'analysis' => $analysis, 'coverage' => $coverage, 'issues' => $issues, 'findings' => $found, 'alerts' => $stored,
                            'limits' => array_map(fn ($rule) => AgentConfiguration::rules()[$rule]['limit'], $config['rules'])]]);
                    $agent->source_fingerprint = $config['analysis'] === 'openai' && ! $analysis ? null : $fingerprint;
                    $agent->next_run_at = match ($config['activation']) {
                        'hourly' => now()->addHour(), 'daily' => now()->addDay(), 'changes' => now()->addMinutes($issues ? 5 : 1), default => null,
                    };
                    $agent->save();
                });
            } catch (\Throwable $e) {
                Log::error('Agent execution failed', ['run_id' => $run->id, 'exception' => get_class($e)]);
                $run->update(['status' => 'failed', 'finished_at' => now(), 'result' => ['issues' => ['No se pudo completar la ejecución. Consulta el registro técnico.']]]);
            }
            return $run->fresh();
        } finally {
            $lock->release();
        }
    }
}
