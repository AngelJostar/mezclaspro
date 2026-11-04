<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Services\Agents\AgentAccess;
use App\Services\Agents\AgentConfiguration;
use App\Services\Agents\AgentRunner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentPortalController extends Controller
{
    public function index(Request $request, AgentAccess $access, ?AiAgent $agent = null)
    {
        $user = $request->user();
        if ($agent) $access->authorize($user, $agent);
        $agents = AiAgent::orderBy('name')->get()->filter(fn ($item) => $access->permissions($user, $item)['can_view']);
        $agent ??= $agents->first();
        $permissions = $agent ? $access->permissions($user, $agent) : AgentAccess::NONE;
        $configuration = $agent ? array_replace(AgentConfiguration::defaults($agent), $agent->configuration ?? []) : [];
        $hospitalScoped = $access->isHospitalScoped($user);
        $hospitalIds = $hospitalScoped ? $user->accessibleHospitalIds() : [];
        $findings = null;
        $runs = collect();
        if ($agent) {
            $query = $agent->findings();
            $runQuery = $agent->runs();
            if ($hospitalScoped) {
                // Never expose a global summary or a finding without verifiable hospital ownership.
                $query->whereIn('evidence->record->hospital_id', $hospitalIds);
                $runQuery->where('user_id', $user->id)->whereNotNull('configuration->access_hospital_ids');
            }
            $findings = $query->latest('last_seen_at')->paginate(20)->withQueryString();
            $runs = $runQuery->latest('id')->limit(10)->get();
            if ($hospitalScoped) {
                $runs = $runs->filter(fn ($run) => !array_diff($run->configuration['access_hospital_ids'] ?? [], $hospitalIds));
            }
        }
        return view('admin.agents.index', compact('agents', 'agent', 'permissions', 'configuration', 'hospitalScoped', 'findings', 'runs'));
    }

    public function update(Request $request, AiAgent $agent, AgentAccess $access)
    {
        $access->authorize($request->user(), $agent, 'can_edit');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('ai_agents', 'name')->ignore($agent->id)],
            'description' => 'nullable|string|max:1000', 'instructions' => 'nullable|string|max:20000',
        ]);
        // Delegated editing does not change grants, execution scope, activation or provider credentials.
        $agent->fill($data);
        $agent->configured_by = $request->user()->id;
        $agent->source_fingerprint = null;
        $agent->save();
        return redirect()->route('admin.agents.show', $agent)->with('status', 'Información e instrucciones del agente actualizadas.');
    }

    public function run(Request $request, AiAgent $agent, AgentAccess $access, AgentRunner $runner)
    {
        $access->authorize($request->user(), $agent, 'can_run');
        $run = $runner->run($agent, $request->user());
        $status = match ($run?->status) {
            'completed' => 'Ejecución completada. Consulta los resultados.',
            'partial' => 'Ejecución parcial. Revisa las limitaciones indicadas en los resultados.',
            default => 'No se pudo completar la ejecución. Revisa los resultados.',
        };
        return redirect()->route('admin.agents.show', $agent)->with('status', $status);
    }
}
