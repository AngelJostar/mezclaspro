<?php

namespace App\Livewire\Admin;

use App\Models\AiAgent;
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
    }

    public function openCreate(): void
    {
        $this->authorizeAccess();
        $this->reset('editingAgentId', 'agentName', 'agentDescription', 'agentInstructions', 'status');
        $this->resetValidation();
        $this->showAgentForm = true;
    }

    public function editAgent(int $id): void
    {
        $this->authorizeAccess();
        $agent = AiAgent::findOrFail($id);
        $this->editingAgentId = $agent->id;
        $this->agentName = $agent->name;
        $this->agentDescription = $agent->description ?? '';
        $this->agentInstructions = $agent->instructions ?? '';
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
        $agent->fill([
            'name' => $this->agentName,
            'description' => $this->agentDescription !== '' ? $this->agentDescription : null,
            'instructions' => $this->agentInstructions !== '' ? $this->agentInstructions : null,
        ]);
        if (! $agent->exists) $agent->created_by = Auth::id();
        $agent->save();

        $this->selection = (string) $agent->id;
        $this->showAgentForm = false;
        $this->status = $this->editingAgentId ? 'Agente actualizado.' : 'Agente creado.';
        $this->dispatch('agent-saved', id: $agent->id);
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.admin.agent-center', [
            'agents' => AiAgent::query()->orderBy('name')->get(),
        ]);
    }
}
