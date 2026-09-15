<dialog class="mixture-chat" data-mixture-chat aria-labelledby="mixture-chat-title"
    data-summary-url="{{ route('admin.solicitudes.mensajes.summary') }}">
    <div class="mixture-chat-shell">
        <header class="mixture-chat-header">
            <div class="mixture-chat-heading">
                <h2 id="mixture-chat-title">Mensajes</h2>
                <p data-chat-hospital></p>
                <p data-chat-patient></p>
            </div>
            <button type="button" class="mixture-chat-icon" data-chat-close aria-label="Cerrar mensajes" title="Cerrar mensajes">
                <i data-mixture-chat-icon="x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="mixture-chat-scroll" data-chat-scroll>
            <button type="button" class="mixture-chat-older" data-chat-older hidden>Mensajes anteriores</button>
            <p class="mixture-chat-empty" data-chat-empty role="status">Cargando mensajes...</p>
            <ol class="mixture-chat-messages" data-chat-messages aria-label="Historial de mensajes"></ol>
        </div>
        <form class="mixture-chat-form" data-chat-form>
            <label class="sr-only" for="mixture-chat-body">Mensaje</label>
            <textarea id="mixture-chat-body" name="body" rows="3" maxlength="4000" placeholder="Escribe un mensaje..." required></textarea>
            <p class="mixture-chat-error" data-chat-error role="alert" hidden></p>
            <div class="mixture-chat-footer">
                <span data-chat-status role="status"></span>
                <button type="submit" class="mixture-chat-send" disabled><i data-mixture-chat-icon="send" aria-hidden="true"></i>Enviar mensaje</button>
            </div>
        </form>
    </div>
</dialog>
