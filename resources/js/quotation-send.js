function initQuotationSend() {
    const dialog = document.querySelector('[data-quotation-send-dialog]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const form = dialog.querySelector('form');
    const find = selector => dialog.querySelector(selector);
    const email = form.elements.namedItem('email');
    const phone = form.elements.namedItem('phone');
    const note = form.elements.namedItem('note');
    const download = find('[data-send-download]');
    const fileStatus = find('[data-send-file-status]');
    const retry = find('[data-send-retry]');
    const error = find('[data-send-error]');
    const status = find('[data-send-status]');
    const submit = find('[data-send-submit]');
    const label = find('[data-send-submit-label]');
    let quotation;
    let opener;
    let sending = false;
    let sentEmail = '';
    let pdfUrl = null;
    let pdfRequest = null;
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
        label.textContent = sending ? 'Enviando...' : mail ? 'Enviar correo' : 'Descargar PDF y abrir WhatsApp';
        submit.disabled = sending || (!mail && !pdfUrl) || (mail && sentEmail !== '' && sentEmail === email.value.trim());
    }
    function releasePdf() {
        pdfRequest?.abort();
        pdfRequest = null;
        if (pdfUrl) URL.revokeObjectURL(pdfUrl);
        pdfUrl = null;
    }
    async function preparePdf() {
        if (pdfUrl || pdfRequest) return;
        const request = new AbortController();
        pdfRequest = request;
        retry.hidden = true;
        fileStatus.textContent = 'Preparando PDF...';
        updateChannel();
        try {
            const response = await fetch(quotation.pdf_url, {
                credentials: 'same-origin', cache: 'no-store', signal: request.signal,
                headers: { Accept: 'application/pdf' },
            });
            if (!response.ok || response.redirected || !response.headers.get('content-type')?.includes('application/pdf')) {
                throw new Error('No se pudo preparar el PDF. Verifica tu sesion y vuelve a intentar.');
            }
            const blob = await response.blob();
            if (await blob.slice(0, 5).text() !== '%PDF-') throw new Error('El archivo recibido no es un PDF valido.');
            if (pdfRequest !== request || !dialog.open) return;
            pdfUrl = URL.createObjectURL(blob);
            download.href = pdfUrl;
            fileStatus.textContent = 'Documento PDF';
        } catch (exception) {
            if (pdfRequest !== request || request.signal.aborted) return;
            fileStatus.textContent = 'PDF no disponible';
            retry.hidden = false;
            showError(exception instanceof TypeError ? 'No se pudo descargar el PDF. Revisa tu conexion.' : exception.message);
        } finally {
            if (pdfRequest === request) { pdfRequest = null; updateChannel(); }
        }
    }
    retry.addEventListener('click', () => { clearMessages(); preparePdf(); });
    function setSending(value) {
        sending = value;
        form.setAttribute('aria-busy', String(value));
        form.querySelectorAll('[name=channel], [data-send-close]').forEach(control => { control.disabled = value; });
        note.disabled = value;
        updateChannel();
    }
    dialog.querySelectorAll('[data-send-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('cancel', event => { if (sending) event.preventDefault(); });
    dialog.addEventListener('close', () => { releasePdf(); opener?.focus(); });
    form.querySelectorAll('[name=channel]').forEach(radio => radio.addEventListener('change', () => {
        clearMessages();
        updateChannel();
        if (!isEmail()) preparePdf();
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
        releasePdf();
        retry.hidden = true;
        fileStatus.textContent = 'Documento PDF';
        find('[data-send-filename]').textContent = quotation.filename;
        download.href = quotation.pdf_url;
        download.download = quotation.filename;
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
            if (!pdfUrl) return;
            const rawPhone = phone.value.trim();
            const number = rawPhone.replace(/[\s()+.-]/g, '');
            if (!/^\+?[\d\s().-]+$/.test(rawPhone) || !/^[1-9]\d{7,14}$/.test(number)) {
                phone.setCustomValidity('Escribe el numero completo con codigo de pais (8 a 15 digitos).');
                phone.reportValidity();
                return;
            }
            download.click();
            const message = [note.value.trim(), `Cotizacion ${quotation.folio}`].filter(Boolean).join('\n\n');
            window.open(`https://wa.me/${number}?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            status.textContent = `Descarga iniciada. Adjunta ${quotation.filename} en el chat de WhatsApp y confirma el envio alli.`;
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
