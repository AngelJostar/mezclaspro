<?php

namespace App\Livewire\Admin;

use App\Models\AiAgent;
use App\Models\AiAgentProviderSetting;
use App\Models\ClinicalAgentConversation;
use App\Services\Clinical\ClinicalEvidence;
use App\Services\Clinical\OpenAiClinicalChat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class ClinicalAgentChat extends Component
{
    #[Locked]
    public int $agentId;
    #[Locked]
    public ?int $conversationId = null;
    #[Locked]
    public int $revision = 0;
    #[Reactive]
    public bool $active = false;
    #[Reactive]
    public bool $configured = false;
    public string $draft = '';

    private function authorizeAccess(): AiAgent
    {
        abort_unless(Auth::user()?->hasRole('Super Admin'), 403);
        $agent = AiAgent::where('integration_key', ClinicalEvidence::KEY)->find($this->agentId);
        abort_unless($agent, 404);
        return $agent;
    }

    private function conversations()
    {
        return ClinicalAgentConversation::where('user_id', Auth::id())->where('agent_id', $this->agentId);
    }

    private function conversation(): ?ClinicalAgentConversation
    {
        return $this->conversationId ? $this->conversations()->findOrFail($this->conversationId) : null;
    }

    public function mount(int $agentId): void
    {
        $this->agentId = $agentId;
        $this->authorizeAccess();
        $conversation = $this->conversations()->latest('id')->first();
        $this->conversationId = $conversation?->id;
        $this->revision = count($conversation?->messages ?? []);
    }

    public function selectConversation(int $id): void
    {
        $this->authorizeAccess();
        $conversation = $this->conversations()->find($id);
        abort_unless($conversation, 404);
        $this->conversationId = $conversation->id;
        $this->revision = count($conversation->messages);
        $this->reset('draft');
        $this->resetValidation();
        $this->dispatch('clinical-chat-updated');
    }

    public function newConversation(): void
    {
        $this->authorizeAccess();
        if ($this->conversation() && !$this->conversation()->messages) {
            $this->reset('draft');
            $this->resetValidation();
            return;
        }
        $conversation = $this->conversations()->create(['user_id' => Auth::id(), 'agent_id' => $this->agentId, 'messages' => []]);
        $this->selectConversation($conversation->id);
    }

    public function send(): void
    {
        $agent = $this->authorizeAccess();
        $this->resetValidation();
        $this->draft = trim($this->draft);
        $this->validate(['draft' => 'required|string|max:4000'], [
            'draft.required' => 'Escribe un mensaje.', 'draft.max' => 'El mensaje no debe superar los 4000 caracteres.',
        ]);
        if (!$agent->is_active) { $this->addError('chat', 'Activa el agente para enviar mensajes.'); return; }
        $lock = Cache::lock('clinical-chat-send:'.Auth::id().':'.$this->agentId, 90);
        if (!$lock->get()) { $this->addError('chat', 'Ya hay una consulta en proceso. Espera la respuesta.'); return; }
        try {
            $conversation = $this->conversation() ?? $this->conversations()->latest('id')->first();
            $history = $conversation?->messages ?? [];
            if (count($history) !== $this->revision) {
                $this->conversationId = $conversation?->id;
                $this->revision = count($history);
                $this->addError('chat', 'La conversacion cambio en otra ventana. Revisa la respuesta antes de enviar de nuevo.');
                return;
            }
            if (count($history) >= 20) { $this->addError('chat', 'Esta conversacion alcanzo 20 consultas. Inicia una nueva conversacion.'); return; }
            try {
                $answer = app(OpenAiClinicalChat::class)->reply($agent, $history, $this->draft, Auth::id());
            } catch (\App\Services\Clinical\ClinicalChatUnavailable $e) {
                $this->addError('chat', $e->getMessage());
                return;
            } catch (\Throwable $e) {
                $this->addError('chat', 'No fue posible completar la consulta. Tu mensaje se conserva para reintentar.');
                return;
            }
            $history[] = ['question' => $this->draft, 'response' => $answer, 'at' => now()->format('d/m/Y H:i')];
            $conversation ??= new ClinicalAgentConversation(['user_id' => Auth::id(), 'agent_id' => $this->agentId]);
            $conversation->messages = $history;
            $conversation->save();
            $this->conversationId = $conversation->id;
            $this->revision = count($history);
            $this->reset('draft');
            $this->dispatch('clinical-chat-updated');
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        $agent = $this->authorizeAccess();
        return view('livewire.admin.clinical-agent-chat', [
            'active' => $agent->is_active,
            'configured' => (bool) config('services.openai.api_key') || AiAgentProviderSetting::whereNotNull('api_key')->exists(),
            'messages' => $this->conversation()?->messages ?? [],
            'conversations' => $this->conversations()->latest('id')->limit(50)->get(['id', 'created_at']),
        ]);
    }
}
