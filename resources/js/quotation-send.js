function initQuotationSend() {
    const dialog = document.querySelector('[data-quotation-send-dialog]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const form = dialog.querySelector('form');
    const find = selector => dialog.querySelector(selector);
    const email = form.elements.namedItem('email');
    const phone = form.elements.namedItem('phone');
    const note = form.elements.namedItem('note');
    const summary = find('[data-send-summary]');
    const error = find('[data-send-error]');
    const status = find('[data-send-status]');
    const submit = find('[data-send-submit]');
    const label = find('[data-send-submit-label]');
    let quotation;
    let opener;
    let sending = false;
    let sentEmail = '';
    const isEmail = () => form.elements.namedItem('channel').value === 'email';
    const clearMessages = () => { error.hidden = true; status.hidden = true; };
    const showError = message => { error.textContent = message; error.hidden = false; error.focus(); };

    function updateChannel() {
        const mail = isEmail();
        email.disabled = !mail || sending;
        email.required = mail;
        phone.disabled = mail || sending;
        phone.required = !mail;
        find('[data-send-email-field]').hidden = !mail;
        find('[data-send-phone-field]').hidden = mail;
        label.textContent = sending ? 'Enviando...' : mail ? 'Enviar correo' : 'Abrir WhatsApp';
        submit.disabled = sending || (mail && sentEmail !== '' && sentEmail === email.value.trim());
    }
    function setSending(value) {
        sending = value;
        form.setAttribute('aria-busy', String(value));
        form.querySelectorAll('[name=channel], [data-send-close]').forEach(control => { control.disabled = value; });
        note.disabled = value;
        updateChannel();
    }
    dialog.querySelectorAll('[data-send-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('cancel', event => { if (sending) event.preventDefault(); });
    dialog.addEventListener('close', () => opener?.focus());
    form.querySelectorAll('[name=channel]').forEach(radio => radio.addEventListener('change', () => {
        clearMessages();
        updateChannel();
    }));
    email.addEventListener('input', () => { clearMessages(); updateChannel(); });
    phone.addEventListener('input', () => phone.setCustomValidity(''));

    document.querySelectorAll('[data-quotation-send]').forEach(button => button.addEventListener('click', () => {
        quotation = JSON.parse(button.dataset.quotationSend);
        form.action = quotation.url;
        opener = button;
        form.reset();
        clearMessages();
        sentEmail = '';
        phone.setCustomValidity('');
        summary.value = quotation.summary;
        find('#quotation-send-title').textContent = `Enviar ${quotation.folio}`;
        setSending(false);
        dialog.showModal();
        email.focus();
    }));

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending || !quotation) return;
        clearMessages();
        if (!isEmail()) {
            const rawPhone = phone.value.trim();
            const number = rawPhone.replace(/[\s()+.-]/g, '');
            if (!/^\+?[\d\s().-]+$/.test(rawPhone) || !/^[1-9]\d{7,14}$/.test(number)) {
                phone.setCustomValidity('Escribe el numero completo con codigo de pais (8 a 15 digitos).');
                phone.reportValidity();
                return;
            }
            const message = [note.value.trim(), quotation.summary].filter(Boolean).join('\n\n');
            window.open(`https://wa.me/${number}?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            status.textContent = 'Mensaje preparado. El envio se confirma en WhatsApp.';
            status.hidden = false;
            return;
        }
        const recipient = email.value.trim();
        if (recipient === sentEmail) return;
        const payload = { email: recipient, note: note.value.trim() };
        setSending(true);
        try {
            const response = await fetch(quotation.url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value },
                body: JSON.stringify(payload),
            });
            if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('No se pudo confirmar el envio. Recarga la pagina y verifica tu sesion.');
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const messages = Object.values(data.errors || {}).flat().join(' ');
                throw new Error(messages || (response.status === 419 ? 'La sesion vencio. Recarga la pagina antes de enviar.'
                    : response.status === 429 ? 'Demasiados intentos. Espera un minuto antes de enviar.'
                        : data.message || 'No se pudo confirmar el envio. Revisa la conexion antes de volver a intentar.'));
            }
            sentEmail = recipient;
            status.textContent = data.message;
            status.hidden = false;
        } catch (exception) {
            showError(exception instanceof TypeError ? 'No se pudo confirmar el envio. Revisa la conexion antes de volver a intentar.' : exception.message);
        } finally {
            setSending(false);
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initQuotationSend);
else initQuotationSend();
