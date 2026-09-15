<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\User;
use App\Services\Agents\AgentRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunDueAgents extends Command
{
    protected $signature = 'agents:run-due';
    protected $description = 'Ejecuta auditorías de agentes activos según su configuración autorizada.';

    public function handle(AgentRunner $runner): int
    {
        Cache::put('agents:scheduler-heartbeat', now()->toIso8601String(), 300);
        foreach (AiAgent::where('is_active', true)->whereNotNull('configuration')->where(fn ($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()))->cursor() as $agent) {
            if (($agent->configuration['activation'] ?? 'manual') === 'manual') continue;
            $actor = User::find($agent->configured_by);
            if (! $actor?->hasRole('Super Admin') || (isset($actor->is_active) && ! $actor->is_active)) continue;
            try {
                $run = $runner->run($agent, $actor, 'scheduled');
                if ($run) $this->line('Agente '.$agent->id.': '.$run->status.' (#'.$run->id.').');
            } catch (\Throwable $e) {
                Log::warning('Scheduled agent not runnable', ['agent_id' => $agent->id, 'exception' => get_class($e)]);
                $this->warn('Agente '.$agent->id.': revisar configuración o ejecución en curso.');
            }
        }
        return self::SUCCESS;
    }
}
