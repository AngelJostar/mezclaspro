@if ($showProviderForm)
    <div class="agent-modal-overlay" wire:key="openai-form">
        <section class="agent-modal agent-provider-modal" role="dialog" aria-modal="true" aria-labelledby="openai-title" x-data="{ key: '', model: @js($providerModel), saving: false, showKey: false }" x-trap.inert.noscroll="true" @keydown.escape.stop="$wire.toggleProviderForm()">
            <header class="agent-modal-header"><h3 id="openai-title">OpenAI</h3><button type="button" class="agent-edit" wire:click="toggleProviderForm" aria-label="Cerrar OpenAI">×</button></header>
            <form @submit.prevent="saving = true; showKey = false; $wire.saveOpenAi(key, model).then(saved => { if (saved === true) key = ''; }).finally(() => { showKey = false; saving = false; })">
                <div class="agent-form-fields">
                    <p>Clave API: {{ $providerConfigured ? 'configurada' : 'sin configurar' }}</p>
                    <label for="openai-key">Clave API nueva</label>
                    <div>
                        <div class="agent-secret-field">
                            <input id="openai-key" x-model="key" type="password" :type="showKey ? 'text' : 'password'" autocomplete="new-password" spellcheck="false" maxlength="503" aria-invalid="{{ $errors->has('providerKey') ? 'true' : 'false' }}" aria-describedby="openai-key-error">
                            <button type="button" class="agent-secret-toggle" @click="showKey = !showKey" aria-controls="openai-key" :aria-pressed="showKey" :aria-label="showKey ? 'Ocultar clave API' : 'Mostrar clave API'" :title="showKey ? 'Ocultar clave API' : 'Mostrar clave API'">
                                <span x-show="!showKey" aria-hidden="true"><span wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="eye"></i></span></span>
                                <span x-show="showKey" x-cloak aria-hidden="true"><span wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="eye-off"></i></span></span>
                            </button>
                        </div>
                        <p id="openai-key-error" class="agent-error" role="alert">{{ $errors->first('providerKey') }}</p>
                    </div>
                    <label for="openai-model">Modelo</label>
                    <div>
                        <input id="openai-model" x-model="model" type="text" required maxlength="100" aria-invalid="{{ $errors->has('providerModel') ? 'true' : 'false' }}" aria-describedby="openai-model-error">
                        <p id="openai-model-error" class="agent-error" role="alert">{{ $errors->first('providerModel') }}</p>
                    </div>
                    <p class="agent-muted">Clave cifrada de tu proyecto de OpenAI; no se vincula mediante la sesion de ChatGPT. Las consultas consumen la cuota API del proyecto. Los agentes operativos envian hallazgos; la validacion clinica envia parametros de formulacion sin identificadores directos. El chat del agente envia los mensajes que escribas: no incluyas datos identificables de pacientes. Respuestas con store: false, lo cual no equivale a retencion cero. Revisa las condiciones de datos de tu organizacion antes de activar el uso clinico.</p>
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
