function initializeClientConciliation() {
    const root = document.querySelector('[data-client-conciliation]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    const dialog = root.querySelector('[data-client-request-dialog]');
    const content = dialog.querySelector('[data-client-request-content]');
    const error = dialog.querySelector('[data-client-request-error]');
    const loading = dialog.querySelector('[data-client-request-loading]');
    const feedback = root.querySelector('[data-client-conciliation-feedback]');
    let trigger, requestNumber = 0, saving = false, key, fingerprint;
    const responseData = async response => {
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo completar la solicitud.');
        return data;
    };
    const showError = failure => { error.textContent = failure instanceof TypeError || failure instanceof SyntaxError ? 'No se pudo confirmar la operación. Puedes reintentar sin duplicar la solicitud.' : failure.message; error.hidden = false; };
    root.addEventListener('click', async event => {
        if (event.target.closest('[data-client-request-close]')) { if (!saving) dialog.close(); return; }
        const button = event.target.closest('[data-client-request-open]');
        if (!button) return;
        trigger = button;
        const current = ++requestNumber;
        content.replaceChildren(); error.hidden = true; loading.hidden = false; key = null; fingerprint = null;
        dialog.showModal(); dialog.querySelector('h2').focus();
        try {
            const result = await responseData(await fetch(button.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store' }));
            if (current === requestNumber && dialog.open) content.innerHTML = result.html;
        } catch (failure) { if (current === requestNumber && dialog.open) showError(failure); }
        finally { if (current === requestNumber) loading.hidden = true; }
    });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => { ++requestNumber; trigger?.focus(); });
    dialog.addEventListener('submit', async event => {
        const form = event.target.closest('[data-client-request-form]');
        if (!form) return;
        event.preventDefault();
        if (saving) return;
        error.hidden = true;
        const requests = {};
        for (const row of form.querySelectorAll('[data-client-change-row]')) {
            const choice = row.querySelector('[data-client-change-choice]').value;
            const input = row.querySelector('[data-client-change-reason]');
            const reason = input.value.trim();
            if (choice === '' && !reason) continue;
            if (!reason) { showError(new Error('Indica el motivo de cada cambio solicitado.')); input.focus(); return; }
            requests[row.dataset.key] = { conciliable: choice === '' ? null : choice === '1', reason };
        }
        if (!Object.keys(requests).length) { showError(new Error('Indica al menos un cambio y su motivo.')); return; }
        const next = JSON.stringify(requests);
        if (fingerprint !== next) { key = crypto.randomUUID(); fingerprint = next; }
        saving = true;
        const controls = [...dialog.querySelectorAll('button, select, textarea')];
        controls.forEach(control => { control.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        try {
            const result = await responseData(await fetch(form.action, { method: 'POST', headers: {
                Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            }, body: JSON.stringify({ submission_key: key, requests }) }));
            feedback.textContent = result.message; feedback.hidden = false;
            dialog.close();
        } catch (failure) { showError(failure); }
        finally { saving = false; controls.forEach(control => { control.disabled = false; }); form.removeAttribute('aria-busy'); }
    });
}
document.addEventListener('DOMContentLoaded', initializeClientConciliation);
document.addEventListener('livewire:navigated', initializeClientConciliation);
