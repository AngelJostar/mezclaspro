<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ClinicalSource;
use App\Services\Agents\AgentAccess;
use App\Services\Agents\AgentConfiguration;
use App\Services\Agents\AgentRunner;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $clinicalManuals = collect();
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

            if ($agent->integration_key === ClinicalEvidence::KEY) {
                $clinicalManuals = ClinicalSource::current()
                    ->where('category', 'nutricionales')
                    ->where('is_manual', true)
                    ->whereIn('manual_type', ['npt_adulto', 'npt_pediatrico'])
                    ->orderByDesc('approved_at')
                    ->orderByDesc('id')
                    ->get()
                    ->unique('manual_type')
                    ->keyBy('manual_type');
            }
        }
        return view('admin.agents.index', compact('agents', 'agent', 'permissions', 'configuration', 'hospitalScoped', 'findings', 'runs', 'clinicalManuals'));
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
        if (!\App\Services\Agents\OperationalAgents::handles($agent)) $agent->configured_by = $request->user()->id;
        $agent->source_fingerprint = null;
        $agent->save();
        return redirect()->route('admin.agents.show', $agent)->with('status', 'Información e instrucciones del agente actualizadas.');
    }

    public function manual(Request $request, ClinicalSource $source, AgentAccess $access)
    {
        abort_unless($access->canViewValidationRules($request->user()), 403);
        abort_unless(
            $source->is_manual
                && !$source->superseded_at
                && in_array($source->manual_type, ['npt_adulto', 'npt_pediatrico'], true)
                && $source->file_path
                && Storage::disk('local')->exists($source->file_path),
            404
        );

        return Storage::disk('local')->download($source->file_path, $source->file_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function run(Request $request, AiAgent $agent, AgentAccess $access, AgentRunner $runner)
    {
        $access->authorize($request->user(), $agent, 'can_run');
        if (\App\Services\Agents\OperationalAgents::handles($agent)) {
            return redirect()->route('admin.agents.show', $agent)->with('status', 'Utiliza los controles de este agente para seleccionar el periodo o los datos del paciente.');
        }
        $run = $runner->run($agent, $request->user());
        $status = match ($run?->status) {
            'completed' => 'Ejecución completada. Consulta los resultados.',
            'partial' => 'Ejecución parcial. Revisa las limitaciones indicadas en los resultados.',
            default => 'No se pudo completar la ejecución. Revisa los resultados.',
        };
        return redirect()->route('admin.agents.show', $agent)->with('status', $status);
    }
}
