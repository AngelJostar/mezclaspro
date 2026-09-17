<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Services\Agents\AgentRunner;
use App\Support\AdministrationNavigation;
use App\Support\ConciliationInboxFilters;
use Illuminate\Http\Request;

class ConciliationAgentController extends Controller
{
    private function agent(Request $request): AiAgent
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        abort_unless(AdministrationNavigation::canViewReports($request->user()), 403);
        return AiAgent::where('integration_key', 'admin_conciliation')->firstOrFail();
    }

    public function show(Request $request)
    {
        $agent = $this->agent($request);
        $filters = ConciliationInboxFilters::validate($request->query());
        $institution = !empty($filters['institucion_id']) ? Institucion::findOrFail($filters['institucion_id'])->nombre : 'Todas las instituciones';
        $hospital = !empty($filters['hospital_id']) ? Hospital::findOrFail($filters['hospital_id'])->name : 'Todos los hospitales';
        $compatible = ($agent->configuration['rules'] ?? []) === ['conciliation'] && ($agent->configuration['sources'] ?? []) === ['conciliations'];
        return response()->json(['html' => view('admin.instituciones.conciliacion._agent', compact('agent', 'filters', 'institution', 'hospital', 'compatible'))->render()])->header('Cache-Control', 'no-store');
    }

    public function run(Request $request, AgentRunner $runner)
    {
        $agent = $this->agent($request);
        abort_unless(($agent->configuration['rules'] ?? []) === ['conciliation'] && ($agent->configuration['sources'] ?? []) === ['conciliations'], 422, 'El agente tiene otras fuentes o reglas. Revisa su configuración en el Centro de agentes.');
        $input = $request->validate(['instructions' => ['nullable', 'string', 'max:2000'], 'filters' => ['present', 'array']]);
        $input['filters'] = ConciliationInboxFilters::validate($input['filters']);
        $run = $runner->run($agent, $request->user(), 'manual', $input);
        return response()->json(['html' => view('admin.instituciones.conciliacion._agent-result', compact('run'))->render()])->header('Cache-Control', 'no-store');
    }
}
