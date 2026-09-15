<td class="px-2 py-2 text-center">
    <div class="mixture-message-actions"
        @if ($messageTarget)
            data-mixture-message-key="{{ $messageKind }}:{{ $messageTarget->id }}"
            data-mixture-message-url="{{ route('admin.solicitudes.mensajes.show', ['kind' => $messageKind, 'target' => $messageTarget->id]) }}"
        @endif>
        <button type="button" class="mixture-message-history" disabled title="Sin mensajes" aria-label="Sin mensajes">
            <i data-mixture-chat-icon="phone" aria-hidden="true"></i>
            <span class="mixture-message-unread" hidden></span>
        </button>
        @if ($messageTarget && $isHospitalView)
            <button type="button" class="mixture-message-compose" title="Enviar mensaje" aria-label="Enviar mensaje">
                <i data-mixture-chat-icon="send" aria-hidden="true"></i>
            </button>
        @endif
    </div>
</td>
