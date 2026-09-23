<dialog class="quotation-send-dialog" data-quotation-send-dialog aria-labelledby="quotation-send-title">
    <form method="POST" data-quotation-send-form>
        @csrf
        <header class="quotation-modal-header">
            <h2 id="quotation-send-title">Enviar cotizacion</h2>
            <button type="button" class="quotation-icon-button" data-send-close aria-label="Cerrar envio" title="Cerrar">
                <i data-quotation-icon="x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="quotation-modal-body quotation-send-body">
            <fieldset class="quotation-send-channels">
                <legend class="sr-only">Medio de envio</legend>
                <label><input type="radio" name="channel" value="email" checked><span><i data-quotation-icon="mail" aria-hidden="true"></i>Correo</span></label>
                <label><input type="radio" name="channel" value="whatsapp"><span><i data-quotation-icon="message-circle" aria-hidden="true"></i>WhatsApp</span></label>
            </fieldset>
            <label class="quotation-field" data-send-email-field>Correo del destinatario *
                <input type="email" name="email" maxlength="254" autocomplete="email" required>
            </label>
            <label class="quotation-field" data-send-phone-field hidden>Telefono con codigo de pais *
                <input type="tel" name="phone" maxlength="30" autocomplete="tel" placeholder="+52 5512345678" disabled>
            </label>
            <label class="quotation-field">Mensaje (opcional)
                <textarea name="note" rows="3" maxlength="2000"></textarea>
            </label>
            <label class="quotation-field">Cotizacion
                <textarea data-send-summary rows="9" readonly></textarea>
            </label>
            <p role="alert" tabindex="-1" data-send-error hidden></p>
            <p role="status" data-send-status hidden></p>
        </div>
        <footer class="quotation-modal-footer">
            <div>
                <button type="button" class="quotation-button quotation-outline" data-send-close>Cancelar</button>
                <button type="submit" class="quotation-button quotation-primary" data-send-submit>
                    <i data-quotation-icon="send" aria-hidden="true"></i><span data-send-submit-label>Enviar correo</span>
                </button>
            </div>
        </footer>
    </form>
</dialog>
