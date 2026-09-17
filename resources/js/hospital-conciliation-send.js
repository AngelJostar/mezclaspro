export function initializeConciliationSend(root, filters, validate) {
    const trigger = root.querySelector('[data-send-conciliation]');
    if (!trigger) return;
    const dialog = root.querySelector('[data-conciliation-dialog]');
    const form = dialog.querySelector('form');
    const confirm = form.querySelector('[data-confirm-send]');
    const error = dialog.querySelector('[data-send-error]');
    const loading = dialog.querySelector('[data-send-loading]');
    const retry = dialog.querySelector('[data-retry-preview]');
    const success = dialog.querySelector('[data-send-success]');
    const title = dialog.querySelector('h2');
    const feedback = root.querySelector('[data-tools-status]');
    const cancel = [...dialog.querySelectorAll('[data-cancel-send]')];
    const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    let body, key, fingerprint, payload, sending = false, previewRequest;
    const close = () => { if (!sending) dialog.close(); };
    cancel.forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('cancel', event => { if (sending) event.preventDefault(); });
    dialog.addEventListener('close', () => { previewRequest?.abort(); trigger.focus(); });

    function render(summary) {
        const value = path => path.split('.').reduce((item, part) => item[part], summary);
        dialog.querySelectorAll('[data-summary]').forEach(node => { node.textContent = value(node.dataset.summary); });
        dialog.querySelectorAll('[data-money]').forEach(node => {
            const cents = value(node.dataset.money);
            node.textContent = cents === null ? 'Sin registrar' : money.format(cents / 100) + ' MXN';
        });
        dialog.querySelectorAll('[data-no-note]').forEach(node => { node.hidden = !summary.no.count; });
        dialog.querySelectorAll('[data-missing-amounts]').forEach(node => { node.hidden = !summary.total.missing_amounts; });
        dialog.querySelector('[data-included-label]').textContent = summary.yes.count + (summary.yes.count === 1 ? ' mezcla incluida' : ' mezclas incluidas');
        dialog.querySelector('[data-no-description]').textContent = summary.no.count === 1
            ? 'La mezcla no conciliable se adjunta con su motivo para seguimiento; no forma parte del monto conciliable.'
            : 'Las ' + summary.no.count + ' mezclas no conciliables se adjuntan con sus motivos para seguimiento; no forman parte del monto conciliable.';
        dialog.querySelector('[data-no-confirm]').textContent = summary.no.count === 1
            ? 'Se adjuntó 1 mezcla no conciliable con su motivo.' : 'Se adjuntaron ' + summary.no.count + ' mezclas no conciliables con sus motivos.';
    }

    async function readResponse(response) {
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo completar la solicitud.');
        return result;
    }

    function showError(failure) {
        error.textContent = failure instanceof SyntaxError || failure instanceof TypeError
            ? 'No se pudo confirmar la operación. Revisa tu conexión y sesión; puedes reintentar sin duplicar la solicitud.' : failure.message;
        error.hidden = false;
    }

    async function preview() {
        previewRequest?.abort();
        const controller = new AbortController();
        previewRequest = controller;
        form.hidden = true; success.hidden = true; error.hidden = true; retry.hidden = true; loading.hidden = false;
        title.textContent = 'Confirmar envío de conciliación';
        dialog.querySelector('[data-confirm-symbol]').hidden = false;
        dialog.querySelector('[data-success-symbol]').hidden = true;
        dialog.classList.remove('is-success');
        dialog.setAttribute('aria-busy', 'true');
        try {
            const query = new URLSearchParams(body);
            query.delete('confirmation_token');
            const result = await readResponse(await fetch(form.dataset.previewUrl + '?' + query, {
                headers: { Accept: 'application/json' }, signal: controller.signal, cache: 'no-store',
            }));
            if (!dialog.open || controller.signal.aborted) return;
            const next = query.toString() + '&' + result.confirmation_token;
            if (next !== fingerprint) { key = crypto.randomUUID(); payload = null; }
            fingerprint = next;
            body.set('confirmation_token', result.confirmation_token);
            render(result.summary);
            const reasons = form.querySelector('[data-reason-inputs]');
            reasons.replaceChildren();
            result.summary.non_conciliable.forEach(item => {
                const label = document.createElement('label');
                label.textContent = item.label + ' · Motivo *';
                const input = document.createElement('textarea');
                input.name = 'reasons[' + item.key + ']'; input.required = true; input.maxLength = 2000;
                input.value = payload?.get(input.name) || item.reason || '';
                input.rows = 2;
                label.append(input); reasons.append(label);
            });
            form.querySelector('[data-send-reasons]').hidden = !result.summary.no.count;
            form.hidden = false;
        } catch (failure) {
            if (failure.name !== 'AbortError') { showError(failure); retry.hidden = false; }
        } finally {
            if (previewRequest === controller) { loading.hidden = true; dialog.removeAttribute('aria-busy'); }
        }
    }

    trigger.addEventListener('click', () => {
        if (!validate()) return;
        if (root.querySelector('[data-conciliable]:disabled')) {
            feedback.textContent = 'Espera a que se guarden los cambios de conciliación antes de enviar.';
            feedback.classList.add('ht-error'); feedback.hidden = false;
            return;
        }
        body = new URLSearchParams(new FormData(filters));
        dialog.showModal(); title.focus();
        preview();
    });
    retry.addEventListener('click', preview);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending || !body || !form.reportValidity()) return;
        const next = new URLSearchParams(body);
        for (const [name, value] of new FormData(form)) next.set(name, value);
        if (payload && payload.toString() !== next.toString()) key = crypto.randomUUID();
        payload = next;
        const requestBody = new URLSearchParams(payload);
        requestBody.set('submission_key', key);
        sending = true; confirm.disabled = true;
        const inputs = [...form.querySelectorAll('textarea')];
        [...cancel, ...inputs].forEach(node => { node.disabled = true; });
        confirm.querySelector('[data-confirm-label]').textContent = 'Enviando...';
        form.setAttribute('aria-busy', 'true'); error.hidden = true;
        try {
            const result = await readResponse(await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: requestBody }));
            render(result.summary);
            title.textContent = 'Conciliación enviada con éxito';
            dialog.querySelector('[data-confirm-symbol]').hidden = true;
            dialog.querySelector('[data-success-symbol]').hidden = false;
            dialog.classList.add('is-success');
            dialog.querySelector('[data-send-folio]').textContent = result.folio;
            form.hidden = true; success.hidden = false;
            feedback.textContent = result.message;
            feedback.classList.remove('ht-error'); feedback.hidden = false;
            key = null; fingerprint = null; payload = null;
            dialog.scrollTop = 0; title.focus();
        } catch (failure) { showError(failure); error.scrollIntoView({ block: 'nearest' }); }
        finally {
            sending = false; confirm.disabled = false;
            [...cancel, ...inputs].forEach(node => { node.disabled = false; });
            confirm.querySelector('[data-confirm-label]').textContent = 'Confirmar y enviar';
            form.removeAttribute('aria-busy');
        }
    });
}
