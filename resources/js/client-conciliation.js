function initializeClientConciliation() {
    const root = document.querySelector('[data-client-conciliation]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    initializeConciliationDetail(root);
    initializeConciliationObservations(root);
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
function initializeConciliationDetail(root) {
    const dialog = root.querySelector('[data-client-detail-dialog]');
    const content = dialog.querySelector('[data-client-detail-content]');
    const loading = dialog.querySelector('[data-client-detail-loading]');
    const error = dialog.querySelector('[data-client-detail-error]');
    const retry = dialog.querySelector('[data-client-detail-retry]');
    const accept = dialog.querySelector('[data-client-detail-accept]');
    const reasonDialog = root.querySelector('[data-client-reason-dialog]');
    const reasonForm = reasonDialog.querySelector('form');
    const reasonInput = reasonDialog.querySelector('textarea');
    const reasonError = reasonDialog.querySelector('[data-client-reason-error]');
    const money = cents => cents === null ? 'Sin registrar' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cents / 100) + ' MXN';
    let trigger, controller, requestNumber = 0, active, choices = {}, notes = {}, saving = false, key, fingerprint, editing;
    const fail = message => { error.textContent = message; error.hidden = false; };
    const updateRow = rowKey => {
        const row = [...content.querySelectorAll('[data-period-item]')].find(item => item.dataset.periodItem === rowKey);
        row.querySelectorAll('[data-period-choice]').forEach(button => button.setAttribute('aria-pressed', String((button.dataset.periodChoice === '1') === choices[rowKey])));
        row.querySelector('[data-client-row-observations]').disabled = !notes[rowKey]?.trim();
        const original = active.group.rows.find(item => item.key === rowKey);
        const changed = original.changed || choices[rowKey] !== original.conciliable || (notes[rowKey] || '').trim() !== (original.reason || '').trim();
        const status = row.querySelector('.cp-status');
        status.textContent = status.dataset.status = changed ? 'Con Cambios' : 'Sin Cambios';
    };
    const openReason = (rowKey, selected, button) => {
        const row = active.group.rows.find(item => item.key === rowKey);
        editing = { rowKey, selected, button };
        reasonDialog.querySelector('h2').textContent = active.canRespond ? (selected ? 'Motivo del cambio' : 'Motivo de No conciliable') : 'Observaciones de la remisión';
        reasonDialog.querySelector('[data-client-reason-remission]').textContent = `Remisión ${row.remision} · ${row.patient}`;
        reasonInput.value = notes[rowKey] || '';
        reasonInput.readOnly = !active.canRespond;
        reasonForm.querySelector('[type="submit"]').hidden = !active.canRespond;
        reasonForm.querySelector('.cp-note').hidden = !active.canRespond;
        reasonError.hidden = true;
        reasonDialog.showModal(); reasonInput.focus();
    };
    reasonDialog.addEventListener('click', event => { if (event.target.closest('[data-client-reason-close]')) reasonDialog.close(); });
    reasonInput.addEventListener('input', () => { reasonError.hidden = true; });
    reasonDialog.addEventListener('close', () => { const button = editing?.button; editing = null; if (dialog.open) button?.focus(); });
    reasonForm.addEventListener('submit', event => {
        event.preventDefault();
        if (!editing || !active?.canRespond || saving) return;
        const reason = reasonInput.value.trim();
        if (!reason) { reasonError.textContent = 'Indica el motivo del cambio.'; reasonError.hidden = false; reasonInput.focus(); return; }
        choices[editing.rowKey] = editing.selected;
        notes[editing.rowKey] = reason;
        updateRow(editing.rowKey); updateSummary();
        reasonDialog.close();
    });
    const updateSummary = () => {
        const sum = rows => rows.some(row => row.amount_cents === null) ? null : rows.reduce((total, row) => total + row.amount_cents, 0);
        const excluded = active.group.rows.filter(row => !choices[row.key]);
        content.querySelector('[data-period-excluded]').textContent = money(sum(excluded));
        content.querySelector('[data-period-summary-new]').textContent = money(sum(active.group.rows.filter(row => choices[row.key])));
        content.querySelector('[data-period-excluded-count]').textContent = `${excluded.length} remisiones marcadas No.`;
    };
    const load = async () => {
        const current = ++requestNumber;
        controller?.abort();
        controller = new AbortController();
        content.replaceChildren();
        active = null; choices = {}; notes = {}; key = null; fingerprint = null; accept.disabled = true;
        error.hidden = true; retry.hidden = true; loading.hidden = false;
        try {
            const response = await fetch(trigger.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal });
            const data = await response.json();
            if (!response.ok) throw new Error();
            if (current === requestNumber && dialog.open) {
                active = data;
                choices = Object.fromEntries(data.group.rows.map(row => [row.key, row.conciliable]));
                notes = Object.fromEntries(data.group.rows.map(row => [row.key, row.reason || '']));
                content.innerHTML = data.html;
                accept.disabled = !data.canRespond;
            }
        } catch (failure) {
            if (failure.name !== 'AbortError' && current === requestNumber && dialog.open) {
                error.textContent = 'No se pudo cargar la conciliación. Inténtalo de nuevo.';
                error.hidden = false; retry.hidden = false;
            }
        } finally { if (current === requestNumber) loading.hidden = true; }
    };
    root.addEventListener('click', event => {
        if (event.target.closest('[data-client-detail-close]')) { if (!saving) dialog.close(); return; }
        if (event.target.closest('[data-client-detail-retry]')) { load(); return; }
        const observations = event.target.closest('[data-client-row-observations]');
        if (observations && active && !saving) {
            const rowKey = observations.closest('[data-period-item]').dataset.periodItem;
            openReason(rowKey, choices[rowKey], observations);
            return;
        }
        const choice = event.target.closest('[data-client-detail-dialog] [data-period-choice]');
        if (choice && active?.canRespond && !saving) {
            const row = choice.closest('[data-period-item]');
            const rowKey = row.dataset.periodItem;
            const selected = choice.dataset.periodChoice === '1';
            const original = active.group.rows.find(item => item.key === rowKey);
            if (!selected || selected !== original.conciliable) {
                openReason(rowKey, selected, choice);
            } else {
                choices[rowKey] = selected; notes[rowKey] = original.reason || '';
                updateRow(rowKey); updateSummary();
            }
            return;
        }
        const button = event.target.closest('[data-client-detail-open]');
        if (!button) return;
        trigger = button;
        dialog.showModal(); dialog.querySelector('h2').focus();
        load();
    });
    accept.addEventListener('click', async () => {
        if (!active?.canRespond || saving || reasonDialog.open) return;
        error.hidden = true;
        const reasons = {};
        for (const row of active.group.rows) {
            const reason = (notes[row.key] || '').trim();
            const changed = choices[row.key] !== row.conciliable;
            if (changed && !reason) { fail('Indica el motivo de cada cambio solicitado.'); return; }
            if (changed || reason !== (row.reason || '').trim()) reasons[row.key] = reason;
        }
        const next = JSON.stringify({ choices, reasons });
        if (fingerprint !== next) { key = crypto.randomUUID(); fingerprint = next; }
        saving = true;
        const controls = [...dialog.querySelectorAll('button, textarea')].map(control => [control, control.disabled]);
        controls.forEach(([control]) => { control.disabled = true; });
        dialog.setAttribute('aria-busy', 'true'); accept.textContent = 'Guardando…';
        try {
            const response = await fetch(active.accept_url, { method: 'POST', credentials: 'same-origin', headers: {
                Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }, body: JSON.stringify({ submission_key: key, version: active.version, choices, reasons }) });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo confirmar la respuesta. Inténtalo de nuevo.');
            const row = trigger.closest('[data-client-submission]');
            const status = row.querySelector('[data-client-submission-status]');
            status.textContent = status.dataset.status = data.status;
            row.querySelector('[data-client-new-amount]').textContent = money(data.summary.yes.amount_cents);
            row.dataset.conciliationDifferences = String(data.summary.no.count > 0 || data.status === 'Con cambios');
            for (const name of ['total', 'yes', 'no']) {
                const cell = row.querySelector(`[data-client-${name}-count]`);
                cell.querySelector('.ht-conciliation-count').textContent = data.summary[name].count;
                cell.dataset.columnFilterValue = data.summary[name].count;
            }
            const feedback = root.querySelector('[data-client-conciliation-feedback]');
            feedback.textContent = data.message; feedback.hidden = false;
            dialog.close();
        } catch (failure) {
            fail(failure instanceof TypeError || failure instanceof SyntaxError ? 'No se pudo confirmar la respuesta. Puedes reintentar sin duplicarla.' : failure.message);
        } finally {
            saving = false; controls.forEach(([control, disabled]) => { control.disabled = disabled; });
            dialog.setAttribute('aria-busy', 'false'); accept.textContent = 'Aceptar y continuar';
        }
    });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => {
        if (reasonDialog.open) reasonDialog.close();
        ++requestNumber; controller?.abort(); trigger?.focus();
    });
}

function initializeConciliationObservations(root) {
    const dialog = root.querySelector('[data-client-observations-dialog]');
    const content = dialog.querySelector('[data-client-observations-content]');
    const error = dialog.querySelector('[data-client-observations-error]');
    const loading = dialog.querySelector('[data-client-observations-loading]');
    const retry = dialog.querySelector('[data-client-observations-retry]');
    let trigger, controller, requestNumber = 0;
    const load = async () => {
        const current = ++requestNumber;
        controller?.abort(); controller = new AbortController();
        content.replaceChildren(); error.hidden = true; retry.hidden = true; loading.hidden = false;
        try {
            const response = await fetch(trigger.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (current !== requestNumber || !dialog.open) return;
            const rows = data.group.rows.filter(row => row.reason?.trim());
            if (!rows.length) content.textContent = 'No hay observaciones registradas para este periodo.';
            for (const row of rows) {
                const section = document.createElement('section'); section.className = 'client-observation';
                const title = document.createElement('h3'); title.textContent = `Remisión ${row.remision} · ${row.patient}`;
                const reason = document.createElement('p'); reason.textContent = row.reason;
                section.append(title, reason); content.append(section);
            }
        } catch (failure) {
            if (failure.name !== 'AbortError' && current === requestNumber && dialog.open) {
                error.textContent = 'No se pudieron cargar las observaciones. Inténtalo de nuevo.';
                error.hidden = false; retry.hidden = false;
            }
        } finally { if (current === requestNumber) loading.hidden = true; }
    };
    root.addEventListener('click', event => {
        if (event.target.closest('[data-client-observations-close]')) { dialog.close(); return; }
        if (event.target.closest('[data-client-observations-retry]')) { load(); return; }
        const button = event.target.closest('[data-client-observations-open]');
        if (!button) return;
        trigger = button; dialog.showModal(); dialog.querySelector('h2').focus(); load();
    });
    dialog.addEventListener('close', () => { ++requestNumber; controller?.abort(); trigger?.focus(); });
}

document.addEventListener('DOMContentLoaded', initializeClientConciliation);
document.addEventListener('livewire:navigated', initializeClientConciliation);
