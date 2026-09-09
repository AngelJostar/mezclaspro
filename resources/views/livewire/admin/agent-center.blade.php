<section class="agent-center" aria-labelledby="agent-center-title"
    x-data="{
        previous: false, next: false,
        updateNavigation() {
            const track = this.$refs.agentTrack;
            this.previous = track.scrollLeft > 2;
            this.next = track.scrollLeft < track.scrollWidth - track.clientWidth - 2;
        },
        move(direction) {
            this.$refs.agentTrack.scrollBy({ left: direction * 240, behavior: 'smooth' });
        }
    }"
    x-init="$nextTick(() => updateNavigation())" @resize.window.debounce.100ms="updateNavigation()"
    @agent-saved.window="$nextTick(() => { updateNavigation(); $refs.agentTrack.querySelector('[aria-pressed=true][data-agent-select]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' }); })">
    <div class="agent-center-heading">
        <h2 id="agent-center-title">Centro de agentes</h2>
        <span class="agent-count">{{ $agents->count() }}</span>
    </div>

    <div class="agent-carousel">
        <button type="button" class="agent-carousel-arrow" @click="move(-1)" :disabled="!previous" disabled
            aria-label="Agentes anteriores" title="Agentes anteriores">
            <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="chevron-left"></i></span>
        </button>
        <div class="agent-carousel-track" x-ref="agentTrack" @scroll.debounce.50ms="updateNavigation()"
            data-disable-sticky-x role="group" aria-label="Seleccionar agente">
            <button type="button" class="agent-choice" wire:click="selectAgent('all')"
                aria-pressed="{{ $selection === 'all' ? 'true' : 'false' }}"
                aria-expanded="{{ $selection === 'all' ? 'true' : 'false' }}" aria-controls="agent-information">
                <span class="agent-choice-icon" aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="layout-grid"></i></span>
                <span class="agent-choice-name">Todos</span>
            </button>
            @foreach ($agents as $agent)
                <button type="button" class="agent-choice" wire:key="agent-button-{{ $agent->id }}"
                    wire:click="selectAgent('{{ $agent->id }}')" data-agent-select="{{ $agent->id }}"
                    aria-pressed="{{ $selection === (string) $agent->id ? 'true' : 'false' }}"
                    aria-expanded="{{ $selection === 'all' || $selection === (string) $agent->id ? 'true' : 'false' }}"
                    aria-controls="agent-information" title="{{ $agent->name }}">
                    <span class="agent-choice-icon" aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="bot"></i></span>
                    <span class="agent-choice-name">{{ $agent->name }}</span>
                </button>
            @endforeach
            <button type="button" class="agent-choice agent-create" wire:click="openCreate" aria-haspopup="dialog">
                <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="plus"></i></span>
                <span class="agent-choice-name">Crear nuevo</span>
            </button>
        </div>
        <button type="button" class="agent-carousel-arrow" @click="move(1)" :disabled="!next"
            aria-label="Agentes siguientes" title="Agentes siguientes">
            <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="chevron-right"></i></span>
        </button>
    </div>

    @if ($status)
        <p class="agent-status" role="status">{{ $status }}</p>
    @endif
    <div id="agent-information" aria-live="polite">
        @if ($selection !== '')
            @forelse ($agents->filter(fn ($agent) => $selection === 'all' || $selection === (string) $agent->id) as $agent)
                <article class="agent-information" wire:key="agent-information-{{ $agent->id }}" data-agent-information="{{ $agent->id }}">
                    <div class="agent-information-heading">
                        <h3>{{ $agent->name }}</h3>
                        <button type="button" class="agent-edit" wire:click="editAgent({{ $agent->id }})"
                            aria-label="Editar agente {{ $agent->name }}" title="Editar agente">
                            <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="pencil"></i></span>
                        </button>
                    </div>
                    <dl class="agent-information-grid">
                        <div><dt>Descripción</dt><dd>{{ $agent->description ?? 'Sin descripción' }}</dd></div>
                        <div><dt>Instrucciones</dt><dd class="agent-instructions" tabindex="0">{{ $agent->instructions ?? 'Sin instrucciones' }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="agent-empty">No hay agentes registrados.</p>
            @endforelse
        @endif
    </div>

    @if ($showAgentForm)
        <div class="agent-modal-overlay" wire:key="agent-form-{{ $editingAgentId ?? 'new' }}">
            <section class="agent-modal" role="dialog" aria-modal="true" aria-labelledby="agent-form-title"
                x-data x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.agentName.focus())"
                @keydown.escape.prevent.stop="$wire.closeAgentForm()">
                <header class="agent-modal-header">
                    <h3 id="agent-form-title">{{ $editingAgentId ? 'Editar agente' : 'Crear nuevo agente' }}</h3>
                    <button type="button" class="agent-edit" wire:click="closeAgentForm" aria-label="Cerrar formulario de agente" title="Cerrar">
                        <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="x"></i></span>
                    </button>
                </header>
                <form wire:submit="saveAgent">
                    <div class="agent-form-fields">
                        <div>
                            <label for="agent-name">Nombre</label>
                            <input id="agent-name" type="text" wire:model="agentName" x-ref="agentName" required maxlength="120"
                                aria-invalid="{{ $errors->has('agentName') ? 'true' : 'false' }}" aria-describedby="agent-name-error">
                            <p id="agent-name-error" class="agent-error" role="alert">@error('agentName'){{ $message }}@enderror</p>
                        </div>
                        <div>
                            <label for="agent-description">Descripción</label>
                            <textarea id="agent-description" wire:model="agentDescription" rows="2" maxlength="1000"
                                aria-invalid="{{ $errors->has('agentDescription') ? 'true' : 'false' }}" aria-describedby="agent-description-error"></textarea>
                            <p id="agent-description-error" class="agent-error" role="alert">@error('agentDescription'){{ $message }}@enderror</p>
                        </div>
                        <div>
                            <label for="agent-instructions">Instrucciones</label>
                            <textarea id="agent-instructions" wire:model="agentInstructions" rows="6" maxlength="20000"
                                aria-invalid="{{ $errors->has('agentInstructions') ? 'true' : 'false' }}" aria-describedby="agent-instructions-error"></textarea>
                            <p id="agent-instructions-error" class="agent-error" role="alert">@error('agentInstructions'){{ $message }}@enderror</p>
                        </div>
                    </div>
                    <footer class="agent-modal-actions">
                        <button type="button" class="agent-cancel" wire:click="closeAgentForm" wire:loading.attr="disabled" wire:target="saveAgent">Cancelar</button>
                        <button type="submit" class="agent-save" wire:loading.attr="disabled" wire:target="saveAgent">
                            <span wire:loading.remove wire:target="saveAgent">Guardar</span>
                            <span wire:loading wire:target="saveAgent">Guardando...</span>
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    @endif
</section>
