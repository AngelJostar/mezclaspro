<?php

namespace App\Livewire\Admin;

use App\Models\{AiAgent, AiAgentDelivery, AiAgentRun, Hospital};
use App\Services\Agents\{AgentAccess, AutomaticConciliation, OperationalAgents, PatientNutritionComparison};
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OperationalAgentPanel extends Component
{
    #[Locked] public int $agentId;
    #[Locked] public ?int $reportId = null;
    #[Locked] public string $status = '';
    #[Locked] public bool $manageAccess = false;
    #[Locked] public string $editingSection = 'information';
    public array $settings = [];
    public array $patient = [];
    public string $hospitalSearch = '';
    public int $historyLimit = 20;
    public bool $editingDescription = false;
    public string $description = '';
    public string $instructions = '';

    public function editDescription(string $section = 'information'): void
    {
        $agent = $this->agent('can_edit');
        $this->description = $agent->description ?? ''; $this->instructions = $agent->instructions ?? '';
        $this->editingSection = $section === 'instructions' ? 'instructions' : 'information';
        $this->resetValidation();
        $this->editingDescription = true;
    }

    public function saveDescription(): void
    {
        $agent = $this->agent('can_edit');
        $field = $this->editingSection === 'instructions' ? 'instructions' : 'description';
        $data = $this->validate([$field => $field === 'instructions' ? 'nullable|string|max:20000' : 'nullable|string|max:1000']);
        $agent->update([$field => $data[$field]]); $this->editingDescription = false; $this->status = 'Información del agente actualizada.';
    }

    private function agent(string $permission = 'can_view'): AiAgent
    {
        $agent = AiAgent::findOrFail($this->agentId);
        abort_unless(OperationalAgents::handles($agent), 404);
        app(AgentAccess::class)->authorize(auth()->user(), $agent, $permission);
        return $agent;
    }

    public function mount(int $agentId, bool $manageAccess = false): void
    {
        $this->agentId = $agentId;
        $this->manageAccess = $manageAccess && auth()->user()->hasRole('Super Admin');
        $agent = $this->agent();
        $this->settings = array_replace(OperationalAgents::defaults(), auth()->user()->hasRole('Super Admin') ? ($agent->configuration ?? []) : []);
        $this->patient = ['hospital_id' => '', 'registry' => '', 'first_name' => '', 'last_name' => '', 'birth_date' => '',
            'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => now()->toDateString()];
        if ($agent->integration_key === OperationalAgents::CONCILIATION && auth()->user()->hasRole('Super Admin')) {
            foreach (Hospital::with('instituciones.account')->get() as $hospital) {
                $this->settings['hospital_emails'][$hospital->id] ??= $hospital->email ?? '';
                foreach ($hospital->instituciones as $institution) $this->settings['institution_emails'][$institution->id] ??= $institution->account?->email ?? '';
            }
        }
    }

    public function selectAllHospitals(bool $selected): void
    {
        $this->agent(); abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        $this->settings['hospital_ids'] = $selected ? Hospital::where('is_active', true)->where('access_is_active', true)->whereHas('instituciones')->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }

    public function saveSettings(): void
    {
        $agent = $this->agent(); abort_unless(auth()->user()->hasRole('Super Admin') && $agent->integration_key === OperationalAgents::CONCILIATION, 403);
        $this->resetValidation();
        try {
            $config = OperationalAgents::validate($this->settings);
            foreach (Hospital::with('instituciones')->whereIn('id', $config['hospital_ids'])->get() as $hospital) {
                if (!$hospital->is_active || !$hospital->access_is_active || $hospital->instituciones->isEmpty()) {
                    throw ValidationException::withMessages(['settings' => $hospital->name.': selecciona un hospital activo con institución.']);
                }
                if ($config['email_enabled']) {
                    if (empty($config['hospital_emails'][$hospital->id])) throw ValidationException::withMessages(['settings' => 'Falta el correo de '.$hospital->name.'.']);
                    foreach ($hospital->instituciones as $institution) {
                        if (empty($config['institution_emails'][$institution->id])) throw ValidationException::withMessages(['settings' => 'Falta el correo de '.$institution->nombre.'.']);
                    }
                }
            }
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['settings' => collect($e->errors())->flatten()->implode(' ')]);
        }
        $agent->configuration = $config;
        $agent->configured_by = auth()->id();
        $agent->next_run_at = OperationalAgents::next($config)->setTimezone(config('app.timezone'));
        $agent->save();
        $this->status = 'Programación guardada. '.($agent->is_active ? 'El agente está activo.' : 'Activa el agente para iniciar los envíos programados.');
        $this->dispatch('operational-agent-changed');
    }

    public function toggleActive(): void
    {
        $agent = $this->agent(); abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        if (!$agent->is_active && $agent->integration_key === OperationalAgents::CONCILIATION) {
            OperationalAgents::validate($agent->configuration ?? []);
            if (!$agent->configured_by) throw ValidationException::withMessages(['settings' => 'Guarda primero la programación y los hospitales.']);
            $agent->next_run_at = OperationalAgents::next($agent->configuration)->setTimezone(config('app.timezone'));
        }
        $agent->is_active = !$agent->is_active;
        $agent->save();
        $this->status = $agent->is_active ? 'Agente activado.' : 'Agente desactivado.';
        $this->dispatch('operational-agent-changed');
    }

    public function sendPreviousPeriod(): void
    {
        $this->resetValidation();
        $run = app(AutomaticConciliation::class)->run($this->agent('can_run'), auth()->user());
        $this->status = 'Ejecución #'.$run->id.': '.AiAgentRun::statusLabels()[$run->status].'. Consulta el seguimiento por hospital.';
    }

    public function analyzePatient(): void
    {
        $this->resetValidation();
        $run = app(PatientNutritionComparison::class)->run($this->agent('can_run'), auth()->user(), $this->patient);
        $this->reportId = $run->id;
        $this->status = 'Análisis completado. El informe y el PDF están disponibles abajo.';
    }

    public function retryEmail(int $id): void
    {
        $agent = $this->agent();
        abort_unless(auth()->user()->hasRole('Super Admin') && $agent->integration_key === OperationalAgents::CONCILIATION, 403);
        if (!$agent->is_active || !($agent->configuration['email_enabled'] ?? false)) {
            throw ValidationException::withMessages(['settings' => 'Activa el agente y guarda las notificaciones por correo en ON antes de reintentar.']);
        }
        $mail = \App\Models\AiAgentDeliveryEmail::whereHas('delivery', fn($q) => $q->where('ai_agent_id', $agent->id))->findOrFail($id);
        if ($mail->status === 'sent') return;
        $mail->update(['attempts' => 0, 'next_attempt_at' => null]);
        app(\App\Services\Agents\ConciliationDeliveryMailer::class)->send($mail->id);
        $this->status = $mail->fresh()->status === 'sent' ? 'Correo enviado.' : 'El correo sigue pendiente. Consulta el detalle del error.';
    }

    public function openReport(int $id): void
    {
        $this->agent();
        $run = AiAgentRun::where('ai_agent_id', $this->agentId)->findOrFail($id);
        PatientNutritionComparison::authorizeReport(auth()->user(), $run);
        $this->reportId = $id;
    }

    public function render()
    {
        $agent = $this->agent(); $access = app(AgentAccess::class); $user = auth()->user();
        $isSuper = $user->hasRole('Super Admin'); $scoped = $access->isHospitalScoped($user);
        $hospitals = Hospital::with('instituciones')->when($scoped, fn ($q) => $q->whereIn('id', $user->accessibleHospitalIds()))->orderBy('name')->get();
        $deliveryQuery = AiAgentDelivery::with('submission', 'emails')->where('ai_agent_id', $agent->id);
        $runsQuery = $agent->runs();
        if ($scoped) {
            $deliveryQuery->whereIn('hospital_id', $user->accessibleHospitalIds());
            $runsQuery->whereIn('result->nutrition_report->hospital_id', $user->accessibleHospitalIds());
        }
        $limit = max(20, min(500, $this->historyLimit));
        $report = $this->reportId ? PatientNutritionComparison::authorizeReport($user, AiAgentRun::where('ai_agent_id', $agent->id)->findOrFail($this->reportId)) : null;
        return view('livewire.admin.operational-agent-panel', [
            'agent' => $agent, 'isSuper' => $isSuper, 'canRun' => $access->permissions($user, $agent)['can_run'], 'canEdit' => $access->permissions($user, $agent)['can_edit'], 'hospitals' => $hospitals,
            'deliveries' => $deliveryQuery->latest('id')->limit($limit)->get(), 'runs' => $runsQuery->latest('id')->limit($limit)->get(),
            'report' => $report, 'schedulerSeen' => Cache::get('agents:scheduler-heartbeat'),
            'permissions' => $access->permissions($user, $agent),
            'authorizedUsers' => $isSuper && $this->manageAccess ? $access->effectiveUsers($agent) : collect(),
        ]);
    }
}
