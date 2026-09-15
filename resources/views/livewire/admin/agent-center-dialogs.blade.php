@if ($showProviderForm)
    <div class="agent-modal-overlay" wire:key="openai-form">
        <section class="agent-modal agent-provider-modal" role="dialog" aria-modal="true" aria-labelledby="openai-title" x-data="{ key: '', model: @js($providerModel), saving: false }" x-trap.inert.noscroll="true" @keydown.escape.stop="$wire.toggleProviderForm()">
            <header class="agent-modal-header"><h3 id="openai-title">OpenAI</h3><button type="button" class="agent-edit" wire:click="toggleProviderForm" aria-label="Cerrar OpenAI">×</button></header>
            <form @submit.prevent="saving = true; $wire.saveOpenAi(key, model).finally(() => { key = ''; saving = false; })">
                <div class="agent-form-fields">
                    <p>Clave API: {{ $providerConfigured ? 'configurada' : 'sin configurar' }}</p>
                    <label for="openai-key">Clave API nueva</label><input id="openai-key" x-model="key" type="password" autocomplete="new-password" maxlength="503">
                    <label for="openai-model">Modelo</label><input id="openai-model" x-model="model" type="text" required maxlength="100">
                    <p class="agent-muted">Clave cifrada. Solo superadministradores. Se envían a OpenAI el objetivo, las instrucciones y los hallazgos calculados; no expedientes ni credenciales. Respuestas sin almacenamiento de aplicación en OpenAI (store: false).</p>
                    @error('provider')<p class="agent-error" role="alert">{{ $message }}</p>@enderror
                    @if ($providerConfigured)<button type="button" class="agent-command" wire:click="removeOpenAiKey" wire:confirm="¿Eliminar la clave guardada en la base de datos?">Eliminar clave guardada</button>@endif
                </div>
                <footer class="agent-modal-actions"><button type="button" class="agent-cancel" wire:click="toggleProviderForm">Cancelar</button><button type="submit" class="agent-save" :disabled="saving">Guardar conexión</button></footer>
            </form>
        </section>
    </div>
@endif
@if ($reviewingFindingId)
    <div class="agent-modal-overlay" wire:key="finding-review-{{ $reviewingFindingId }}">
        <section class="agent-modal agent-provider-modal" role="dialog" aria-modal="true" aria-labelledby="review-title" x-data x-trap.inert.noscroll="true" @keydown.escape.stop="$wire.closeReview()">
            <header class="agent-modal-header"><h3 id="review-title">Seguimiento #{{ $reviewingFindingId }}</h3><button type="button" class="agent-edit" wire:click="closeReview" aria-label="Cerrar seguimiento">×</button></header>
            <form wire:submit="saveReview">
                <div class="agent-form-fields">
                    <label for="review-status">Estado</label><select id="review-status" wire:model="reviewStatus">@foreach (\App\Models\AiAgentFinding::statusLabels() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    <label for="review-reason">Justificación y evidencia de seguimiento</label><textarea id="review-reason" required minlength="5" maxlength="2000" wire:model="reviewReason" rows="3"></textarea>
                    @error('reviewReason')<p class="agent-error" role="alert">{{ $message }}</p>@enderror
                    @error('reviewStatus')<p class="agent-error" role="alert">{{ $message }}</p>@enderror
                    <details><summary>Historial de seguimiento</summary>@foreach (\Illuminate\Support\Facades\DB::table('ai_agent_finding_events')->where('ai_agent_finding_id', $reviewingFindingId)->latest('id')->get() as $event)<p>{{ $event->created_at }} · Usuario #{{ $event->user_id }} · {{ \App\Models\AiAgentFinding::statusLabels()[$event->status] }}: {{ $event->justification }}</p>@endforeach</details>
                </div>
                <footer class="agent-modal-actions"><button type="button" class="agent-cancel" wire:click="closeReview">Cancelar</button><button type="submit" class="agent-save" wire:loading.attr="disabled">Guardar seguimiento</button></footer>
            </form>
        </section>
    </div>
@endif
