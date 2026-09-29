<section class="clinical-agent-chat" aria-labelledby="clinical-chat-title-{{ $agentId }}"
    x-data x-on:clinical-chat-updated="$nextTick(() => { $refs.history.scrollTop = $refs.history.scrollHeight; $refs.draft.focus(); })"
    x-init="$nextTick(() => { $refs.history.scrollTop = $refs.history.scrollHeight; })">
    <header class="clinical-chat-heading">
        <h4 id="clinical-chat-title-{{ $agentId }}">Conversacion con el agente</h4>
        <div class="clinical-chat-tools">
            @if ($conversations->isNotEmpty())
                <select aria-label="Historial de conversaciones" wire:change="selectConversation($event.target.value)" wire:loading.attr="disabled">
                    @foreach ($conversations as $conversation)
                        <option value="{{ $conversation->id }}" @selected($conversationId === $conversation->id)>#{{ $conversation->id }} · {{ $conversation->created_at->format('d/m/Y H:i') }}</option>
                    @endforeach
                </select>
            @endif
            <button type="button" class="agent-command" wire:click="newConversation" wire:loading.attr="disabled" title="Nueva conversacion">
                <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="plus"></i></span>
                Nueva conversacion
            </button>
        </div>
    </header>
    <p class="agent-muted">Orientacion de IA sujeta a revision profesional. Este chat no valida ni autoriza solicitudes.</p>
    <div class="clinical-chat-history" x-ref="history" role="log" aria-label="Mensajes con el agente" aria-live="polite" tabindex="0">
        @forelse ($messages as $index => $turn)
            <div class="clinical-chat-turn" wire:key="clinical-chat-{{ $conversationId }}-{{ $index }}">
                <article class="clinical-chat-message clinical-chat-question">
                    <header><strong>Tu mensaje</strong><time>{{ $turn['at'] }}</time></header>
                    <p>{{ $turn['question'] }}</p>
                </article>
                <article class="clinical-chat-message clinical-chat-answer">
                    <header><strong>Agente IA</strong><span>{{ $turn['response']['model'] }}</span></header>
                    <p>{{ $turn['response']['answer'] }}</p>
                    @if ($turn['response']['citations'])
                        <details class="clinical-chat-sources"><summary>Fuentes citadas ({{ count($turn['response']['citations']) }})</summary>
                            @foreach ($turn['response']['citations'] as $citation)
                                <p><strong>{{ $citation['source_id'] }} · {{ $citation['title'] }}</strong><br>{{ $citation['section'] }} · {{ $citation['reference'] }}<br>{{ $citation['reviewed'] ? 'Revisada al consultar' : 'Pendiente de revision; no es evidencia aprobada' }}</p>
                            @endforeach
                        </details>
                    @else<p class="agent-muted">Sin fuentes citadas para esta respuesta.</p>@endif
                    @if ($turn['response']['limitations'])
                        <details class="clinical-chat-sources"><summary>Limitaciones de la evidencia</summary>
                            @foreach ($turn['response']['limitations'] as $limitation)<p>{{ $limitation }}</p>@endforeach
                        </details>
                    @endif
                </article>
            </div>
        @empty<p class="agent-muted clinical-chat-empty">Sin mensajes en esta conversacion.</p>@endforelse
    </div>
    <form wire:submit="send" class="clinical-chat-composer">
        <label for="clinical-chat-draft-{{ $agentId }}">Mensaje al agente</label>
        <textarea id="clinical-chat-draft-{{ $agentId }}" x-ref="draft" wire:model="draft" rows="3" maxlength="4000" required
            placeholder="Escribe tu consulta..." aria-describedby="clinical-chat-privacy-{{ $agentId }} clinical-chat-errors-{{ $agentId }}"
            wire:loading.attr="readonly" wire:target="send"></textarea>
        <div id="clinical-chat-errors-{{ $agentId }}" role="alert">@error('draft')<p class="agent-error">{{ $message }}</p>@enderror @error('chat')<p class="agent-error">{{ $message }}</p>@enderror</div>
        <div class="clinical-chat-footer">
            <p class="agent-muted" id="clinical-chat-privacy-{{ $agentId }}">Los mensajes se envian a OpenAI. No incluyas nombres, expedientes ni otros identificadores de pacientes.</p>
            <button type="submit" class="clinical-chat-send" wire:loading.attr="disabled" @disabled(!$active || !$configured)>
                <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="send"></i></span>
                <span wire:loading.remove wire:target="send">Enviar mensaje</span><span wire:loading wire:target="send">Consultando...</span>
            </button>
        </div>
        <p wire:loading wire:target="send" role="status" class="agent-muted">El agente esta preparando la respuesta.</p>
        @unless ($active)<p class="agent-error">El agente esta desactivado.</p>@endunless
        @unless ($configured)<p class="agent-error">Falta configurar la conexion con OpenAI.</p>@endunless
    </form>
</section>
