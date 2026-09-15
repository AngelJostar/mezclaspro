<?php

namespace App\Livewire\Admin;

use App\Models\AiAgent;
use App\Models\AiAgentFinding;
use App\Models\AiAgentProviderSetting;
use App\Models\Institucion;
use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use App\Services\Agents\AgentConfiguration;
use App\Services\Agents\AgentRunner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AgentCenter extends Component
{
    #[Locked]
    public string $selection = '';
    #[Locked]
    public bool $showAgentForm = false;
    #[Locked]
    public ?int $editingAgentId = null;
    #[Locked]
    public string $status = '';

    public string $agentName = '';
    public string $agentDescription = '';
    public string $agentInstructions = '';
    public array $agentConfig = [];
    #[Locked]
    public bool $showProviderForm = false;
    #[Locked]
    public ?int $reviewingFindingId = null;
    #[Locked]
    public int $historyLimit = 5;
    #[Locked]
    public int $findingLimit = 20;
    public string $findingFilter = 'open';
    public string $reviewStatus = 'review';
    public string $reviewReason = '';

    private function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->hasRole('Super Admin'), 403);
    }

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function selectAgent(string $selection): void
    {
        $this->authorizeAccess();
        if ($selection !== 'all') {
            abort_unless(ctype_digit($selection), 404);
            AiAgent::findOrFail((int) $selection);
        }
        $this->selection = $this->selection === $selection ? '' : $selection;
        $this->reset('historyLimit', 'findingLimit');
        $this->resetValidation();
    }

    public function openCreate(): void
    {
        $this->authorizeAccess();
        $this->reset('editingAgentId', 'agentName', 'agentDescription', 'agentInstructions', 'status');
        $this->resetValidation();
        $this->showAgentForm = true;
        $this->agentConfig = AgentConfiguration::defaults();
    }

    public function setAgentActive(int $id, bool $active): void
    {
        $this->authorizeAccess();
        $agent = AiAgent::findOrFail($id);
        $agent->is_active = $active;
        $agent->save();

        $this->status = '';
    }

    public function editAgent(int $id): void
    {
        $this->authorizeAccess();
        $agent = AiAgent::findOrFail($id);
        $this->editingAgentId = $agent->id;
        $this->agentName = $agent->name;
        $this->agentDescription = $agent->description ?? '';
        $this->agentInstructions = $agent->instructions ?? '';
        $this->agentConfig = $agent->configuration ?? AgentConfiguration::defaults($agent);
        $this->status = '';
        $this->resetValidation();
        $this->showAgentForm = true;
    }

    public function closeAgentForm(): void
    {
        $this->authorizeAccess();
        $this->showAgentForm = false;
        $this->resetValidation();
    }

    public function saveAgent(): void
    {
        $this->authorizeAccess();
        abort_unless($this->showAgentForm, 403);
        $this->agentName = trim($this->agentName);
        $this->agentDescription = trim($this->agentDescription);
        $this->agentInstructions = trim($this->agentInstructions);
        $this->validate([
            'agentName' => ['required', 'string', 'max:120', Rule::unique('ai_agents', 'name')->ignore($this->editingAgentId)],
            'agentDescription' => ['nullable', 'string', 'max:1000'],
            'agentInstructions' => ['nullable', 'string', 'max:20000'],
        ], [
            'agentName.required' => 'Escribe el nombre del agente.',
            'agentName.max' => 'El nombre no debe superar los 120 caracteres.',
            'agentName.unique' => 'Ya existe un agente con este nombre.',
            'agentDescription.max' => 'La descripción no debe superar los 1000 caracteres.',
            'agentInstructions.max' => 'Las instrucciones no deben superar los 20000 caracteres.',
        ]);

        $agent = $this->editingAgentId ? AiAgent::findOrFail($this->editingAgentId) : new AiAgent();
        try {
            $configuration = AgentConfiguration::validate($this->agentConfig, ($this->agentConfig['activation'] ?? 'manual') !== 'manual');
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['agentConfig' => collect($e->errors())->flatten()->implode(' ')]);
        }
        $agent->fill([
            'name' => $this->agentName,
            'description' => $this->agentDescription !== '' ? $this->agentDescription : null,
            'instructions' => $this->agentInstructions !== '' ? $this->agentInstructions : null,
        ]);
        if (! $agent->exists) $agent->created_by = Auth::id();
        $agent->configuration = $configuration;
        $agent->configured_by = Auth::id();
        $agent->source_fingerprint = null;
        $agent->next_run_at = $configuration['activation'] === 'manual' ? null : now();
        $agent->save();

        $this->selection = (string) $agent->id;
        $this->showAgentForm = false;
        $this->status = $this->editingAgentId ? 'Agente actualizado.' : 'Agente creado.';
        $this->dispatch('agent-saved', id: $agent->id);
    }

    public function runAgent(int $id): void
    {
        $this->authorizeAccess();
        $this->resetValidation();
        $run = app(AgentRunner::class)->run(AiAgent::findOrFail($id), Auth::user());
        $this->selection = (string) $id;
        $this->status = 'Ejecución #'.$run->id.': '.\App\Models\AiAgentRun::statusLabels()[$run->status].'.';
    }

    public function loadMore(string $type): void
    {
        $this->authorizeAccess();
        if ($type === 'runs') $this->historyLimit = min(500, $this->historyLimit + 10);
        if ($type === 'findings') $this->findingLimit = min(500, $this->findingLimit + 20);
    }

    public function reviewFinding(int $id): void
    {
        $this->authorizeAccess();
        $finding = AiAgentFinding::findOrFail($id);
        $this->reviewingFindingId = $id;
        $this->reviewStatus = $finding->status;
        $this->reviewReason = '';
        $this->resetValidation();
    }

    public function closeReview(): void
    {
        $this->authorizeAccess();
        $this->reviewingFindingId = null;
    }

    public function saveReview(): void
    {
        $this->authorizeAccess();
        abort_unless($this->reviewingFindingId, 403);
        $this->reviewReason = trim($this->reviewReason);
        $this->validate(['reviewStatus' => ['required', Rule::in(array_keys(AiAgentFinding::statusLabels()))], 'reviewReason' => 'required|string|min:5|max:2000']);
        DB::transaction(function () {
            $finding = AiAgentFinding::lockForUpdate()->findOrFail($this->reviewingFindingId);
            $finding->update(['status' => $this->reviewStatus, 'resolution' => $this->reviewReason, 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
            DB::table('ai_agent_finding_events')->insert(['ai_agent_finding_id' => $finding->id, 'user_id' => Auth::id(), 'status' => $this->reviewStatus, 'justification' => $this->reviewReason, 'created_at' => now()]);
        });
        $this->closeReview();
    }

    public function toggleProviderForm(): void
    {
        $this->authorizeAccess();
        $this->showProviderForm = ! $this->showProviderForm;
        $this->resetValidation();
    }

    public function saveOpenAi(string $key, string $model): void
    {
        $this->authorizeAccess();
        abort_unless(request()->isSecure() || in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']), 403, 'La clave requiere HTTPS fuera del equipo local.');
        abort_unless($this->showProviderForm, 403);
        $key = trim($key);
        $model = trim($model);
        if (($key !== '' && ! preg_match('/^sk-[A-Za-z0-9_-]{16,500}$/D', $key)) || ! preg_match('/^[a-zA-Z0-9._:-]{1,100}$/D', $model)) {
            throw ValidationException::withMessages(['provider' => 'La clave API o el identificador de modelo no tienen un formato válido.']);
        }
        $settings = AiAgentProviderSetting::firstOrNew(['id' => 1]);
        if ($key !== '') $settings->api_key = $key;
        $settings->model = $model;
        $settings->updated_by = Auth::id();
        $settings->save();
        $this->showProviderForm = false;
        $this->status = 'Configuración de OpenAI guardada.';
    }

    public function removeOpenAiKey(): void
    {
        $this->authorizeAccess();
        AiAgentProviderSetting::whereKey(1)->update(['api_key' => null, 'updated_by' => Auth::id()]);
        $this->status = 'Clave guardada eliminada.';
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.admin.agent-center', [
            'agents' => AiAgent::query()->orderBy('name')->get(),
            'configOptions' => AgentConfiguration::class,
            'institutions' => $this->showAgentForm ? Institucion::orderBy('nombre')->get(['id', 'nombre']) : collect(),
            'laboratories' => $this->showAgentForm ? Laboratory::orderBy('nombre')->get(['id', 'nombre']) : collect(),
            'warehouses' => $this->showAgentForm ? Warehouse::orderBy('name')->get(['id', 'name', 'laboratory_id']) : collect(),
            'providerConfigured' => (bool) config('services.openai.api_key') || AiAgentProviderSetting::whereNotNull('api_key')->exists(),
            'providerModel' => AiAgentProviderSetting::value('model') ?: config('services.openai.model'),
            'schedulerSeen' => Cache::get('agents:scheduler-heartbeat'),
        ]);
    }
}
