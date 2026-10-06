<section class="clinical-agent-chat chat-library" aria-labelledby="clinical-chat-title-{{ $agentId }}" x-data x-on:clinical-chat-updated="$nextTick(() => { if ($refs.history) $refs.history.scrollTop = $refs.history.scrollHeight; $refs.draft?.focus(); })">
<style>
.chat-library{margin-top:28px;color:#102e57;font-size:15px}.chat-library .chat-top{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:18px}.chat-library .chat-top h4{font-size:28px;font-weight:700;margin:0}.chat-library .chat-subtitle{font-size:17px;color:#798ba7;margin:3px 0}.chat-library .chat-button{border:1px solid #b9cbe3;border-radius:7px;padding:12px 18px;background:#0a315e;color:white;font:inherit;font-weight:600;cursor:pointer}.chat-library .chat-button.teal{background:#0ca1ab;border-color:#0ca1ab}.chat-library .chat-search{width:100%;border:1px solid #ccdcf3;border-radius:7px;padding:13px 16px;font:inherit;margin:18px 0;color:#17355c}.chat-library .chat-table-wrap{overflow:auto;border:1px solid #d6e3f7;border-radius:9px}.chat-library .chat-table{width:100%;border-collapse:collapse;text-align:left;font-size:15px}.chat-library .chat-table th{background:#f0f6ff;padding:18px;font-weight:700;white-space:nowrap}.chat-library .chat-table td{padding:20px 18px;border-top:1px solid #dce7f7;color:#526684}.chat-library .chat-table td:first-child{color:#102e57}.chat-library .chat-overlay{position:fixed;inset:0;z-index:100;background:#1b2c4c99;display:flex;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(2px)}.chat-library .chat-modal{background:white;border-radius:14px;width:min(1250px,100%);height:min(820px,90dvh);display:flex;flex-direction:column;padding:24px;box-shadow:0 24px 80px #162b5040}.chat-library .chat-modal-header{display:flex;align-items:center;gap:20px;border-bottom:1px solid #dce7f7;padding-bottom:20px}.chat-library .chat-modal-header h3{font-size:24px;font-weight:700}.chat-library .chat-modal-header .chat-button{margin-left:auto}.chat-library .chat-close{border:0;background:none;font-size:30px;color:#526684;cursor:pointer}.chat-library .chat-layout{display:grid;grid-template-columns:280px minmax(0,1fr);min-height:0;flex:1}.chat-library .chat-sidebar{border-right:1px solid #dce7f7;padding:20px 18px 0 0;overflow:auto}.chat-library .chat-sidebar h4{font-size:20px;font-weight:700}.chat-library .chat-history-choice{display:block;width:100%;text-align:left;background:white;border:0;border-bottom:1px solid #e6edf7;padding:16px 12px;color:#17355c;border-radius:5px;font:inherit;cursor:pointer}.chat-library .chat-history-choice[aria-pressed=true]{background:#eaf2ff;border-left:4px solid #1764b5}.chat-library .chat-history-choice small{display:block;color:#7b8da8;margin-top:5px}.chat-library .chat-main{display:flex;flex-direction:column;min-height:0;min-width:0;padding:20px 0 0 24px}.chat-library .chat-current-title{font-size:19px;font-weight:700;padding-bottom:12px;border-bottom:1px solid #e0e9f6}.chat-library .clinical-chat-history{flex:1;max-height:none;min-height:0;overflow:auto;border:0;padding:18px 8px 12px 0}.chat-library .clinical-chat-message{font-size:15px;padding:14px 18px;border-radius:12px;max-width:85%}.chat-library .clinical-chat-message header{font-size:12px;gap:10px}.chat-library .clinical-chat-question{margin-left:auto;background:#e4f8f5;border-color:#c4ede9}.chat-library .clinical-chat-answer{background:#edf3ff;border-color:#d7e5ff;margin-top:12px}.chat-library .clinical-chat-composer{padding-top:12px;border-top:1px solid #e0e9f6}.chat-library .clinical-chat-composer textarea{min-height:65px;font-size:15px}.chat-library .clinical-chat-send{background:#0ca1ab;font-size:15px;padding:12px 18px}.chat-library .agent-muted{font-size:13px}.chat-library .clinical-chat-composer label{font-size:13px}.chat-library .clinical-chat-turn{margin-bottom:20px}
@media(max-width:800px){.chat-library .chat-layout{grid-template-columns:180px minmax(0,1fr)}.chat-library .chat-modal{padding:16px}.chat-library .chat-main{padding-left:14px}.chat-library .chat-top h4{font-size:23px}}@media(max-width:600px){.chat-library .chat-sidebar{display:none}.chat-library .chat-layout{grid-template-columns:1fr}.chat-library .chat-main{padding-left:0}.chat-library .chat-overlay{padding:8px}.chat-library .chat-modal-header{flex-wrap:wrap}.chat-library .chat-top{align-items:flex-start;flex-direction:column}}
</style>
<header class="chat-top"><div><h4 id="clinical-chat-title-{{ $agentId }}">Conversación con el agente</h4><p class="chat-subtitle">Soporte químico y clínico</p></div><button type="button" class="chat-button teal" wire:click="newConversation" wire:loading.attr="disabled">＋ Nueva conversación</button></header>
<p class="agent-muted">ⓘ Orientación sujeta a revisión profesional. Este chat no valida ni autoriza solicitudes.</p>
<input class="chat-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar conversación" aria-label="Buscar conversación">
<div class="chat-table-wrap"><table class="chat-table"><thead><tr><th>Conversación</th><th>Creada</th><th>Último mensaje</th><th>Mensajes</th><th>Acción</th></tr></thead><tbody>
@forelse($conversations as $conversation)
<tr wire:key="chat-row-{{ $conversation->id }}"><td>◯ {{ $conversation->chat_title }}</td><td>{{ $conversation->created_at->format('d/m/Y · H:i') }}</td><td>{{ count($conversation->messages) ? $conversation->updated_at->format('d/m/Y · H:i') : 'Sin mensajes' }}</td><td>{{ count($conversation->messages) * 2 }}</td><td><button type="button" class="chat-button" wire:click="selectConversation({{ $conversation->id }})" wire:loading.attr="disabled">Abrir conversación</button></td></tr>
@empty<tr><td colspan="5">No hay conversaciones que mostrar.</td></tr>@endforelse
</tbody></table></div><p class="agent-muted" style="margin-top:16px">Selecciona una conversación para continuar o inicia una nueva.</p>
@if($showChat)
<div class="chat-overlay" x-trap.inert.noscroll="true" @keydown.escape.prevent.stop="$wire.closeChat()">
<section class="chat-modal" role="dialog" aria-modal="true" aria-labelledby="chat-modal-title" tabindex="-1" x-init="$nextTick(() => { $el.focus(); if ($refs.history) $refs.history.scrollTop = $refs.history.scrollHeight; })">
<header class="chat-modal-header"><div><h3 id="chat-modal-title">Conversación con el agente</h3><p class="chat-subtitle">Soporte químico y clínico</p></div><button type="button" class="chat-button" wire:click="newConversation" wire:loading.attr="disabled">＋ Nueva conversación</button><button class="chat-close" type="button" wire:click="closeChat" aria-label="Cerrar conversación">×</button></header>
<div class="chat-layout"><aside class="chat-sidebar"><h4>Historial</h4><input class="chat-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar conversaciones…" aria-label="Buscar en el historial">
@foreach($conversations as $conversation)<button class="chat-history-choice" type="button" wire:click="selectConversation({{ $conversation->id }})" aria-pressed="{{ $conversationId === $conversation->id ? 'true' : 'false' }}">{{ $conversation->chat_title }}<small>{{ $conversation->created_at->format('d/m/Y') }}</small></button>@endforeach
</aside><div class="chat-main"><h4 class="chat-current-title">{{ $conversations->firstWhere('id', $conversationId)?->chat_title ?? 'Conversación seleccionada' }}</h4>
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

</div></div></section></div>
@endif
</section>