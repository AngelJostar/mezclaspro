function initializeConciliationPeriod() {
    const root = document.querySelector('[data-conciliation-period]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    const groups = new Map(JSON.parse(root.querySelector('[data-period-groups]').textContent).map(group => [group.key, group]));
    const dialog = root.querySelector('[data-period-dialog]');
    const detail = root.querySelector('[data-period-detail]');
    const feedback = root.querySelector('[data-period-feedback]');
    const error = root.querySelector('[data-period-error]');
    const accept = root.querySelector('[data-period-accept]');
    const loading = root.querySelector('[data-period-loading]');
    const money = cents => cents === null ? 'Sin registrar' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cents / 100) + ' MXN';
    const scope = group => ({ institucion_id: group.institution_id, hospital_id: group.hospital_id, desde: group.from, hasta: group.to,
        ...(group.period_id ? { period_id: group.period_id } : {}) });
    let active = null;
    let choices = {};
    let saving = false;
    let requestNumber = 0;
    const activeTab = root.dataset.activeTab || 'todas';
    const refreshRows = () => {
        const rows = [...root.querySelectorAll('[data-period-row]')];
        rows.forEach(row => row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0'
            || (activeTab !== 'todas' && groups.get(row.dataset.periodRow).bucket !== activeTab)));
        const visible = rows.filter(row => !row.classList.contains('hidden')).length;
        const empty = root.querySelector('[data-period-filter-empty]');
        if (empty) empty.hidden = visible > 0;
        root.querySelector('[data-period-visible-count]').textContent = visible;
    };
    const columnFilters = window.createExcelColumnFilters?.({
        tableId: 'conciliation-period-table', rowSelector: 'tbody tr[data-period-row]', triggerSelector: '.cp-filter', instanceId: 'conciliation-period',
        onChange: refreshRows,
    });
    const announce = (message, failed = false) => {
        feedback.textContent = message;
        feedback.classList.toggle('cp-error', failed);
        feedback.hidden = false;
    };
    async function api(url, data) {
        const response = await fetch(url, {
            method: data ? 'POST' : 'GET', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            ...(data ? { body: JSON.stringify(data) } : {}),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo guardar. Inténtalo nuevamente.');
        return result;
    }
    const updateRow = group => {
        const previous = groups.get(group.key);
        if (previous?.bucket !== group.bucket) {
            for (const [bucket, delta] of [[previous?.bucket, -1], [group.bucket, 1]]) {
                const counter = root.querySelector(`[data-conciliation-count="${bucket}"]`);
                if (counter) counter.textContent = Math.max(0, Number(counter.textContent) + delta);
            }
        }
        groups.set(group.key, group);
        const row = root.querySelector(`[data-period-row="${group.key}"]`);
        row.querySelector('[data-period-total]').textContent = money(group.total_cents);
        row.querySelector('[data-period-new]').textContent = money(group.new_cents);
        row.querySelector('[data-period-new-cell]').dataset.filterValue = money(group.new_cents);
        row.querySelector('[data-period-send-cell]').dataset.filterValue = group.sent_folio ? 'Enviada' : 'Pendiente de envío';
        row.querySelector('[data-period-folio]').textContent = group.sent_folio || (group.missing_prices ? 'Revisar precios faltantes' : '');
        const send = row.querySelector('[data-period-send]');
        send.textContent = group.sent_folio ? 'Enviada' : 'Enviar';
        send.disabled = !!group.sent_folio || !!group.missing_prices;
        columnFilters?.apply();
        refreshRows();
    };
    const updateSummary = () => {
        const sum = rows => rows.some(row => row.amount_cents === null) ? null : rows.reduce((total, row) => total + row.amount_cents, 0);
        const excluded = active.rows.filter(row => !choices[row.key]);
        detail.querySelector('[data-period-excluded]').textContent = money(sum(excluded));
        detail.querySelector('[data-period-summary-new]').textContent = money(sum(active.rows.filter(row => choices[row.key])));
        detail.querySelector('[data-period-excluded-count]').textContent = `${excluded.length} remisiones marcadas No.`;
    };
    const busy = value => {
        saving = value;
        dialog.setAttribute('aria-busy', String(value));
        dialog.querySelectorAll('button').forEach(button => { button.disabled = value; });
        accept.textContent = value ? 'Guardando…' : 'Aceptar y continuar';
    };
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => { requestNumber++; active = null; choices = {}; });
    root.addEventListener('click', async event => {
        const close = event.target.closest('[data-period-close]');
        if (close && !saving) { dialog.close(); return; }
        const choice = event.target.closest('[data-period-choice]');
        if (choice && active && !saving) {
            const row = choice.closest('[data-period-item]');
            choices[row.dataset.periodItem] = choice.dataset.periodChoice === '1';
            row.querySelectorAll('[data-period-choice]').forEach(button => button.setAttribute('aria-pressed', String(button === choice)));
            updateSummary();
            return;
        }
        const open = event.target.closest('[data-period-open]');
        if (open) {
            const group = groups.get(open.closest('[data-period-row]').dataset.periodRow);
            const sequence = ++requestNumber;
            detail.replaceChildren();
            error.hidden = true;
            loading.hidden = false;
            accept.disabled = true;
            dialog.showModal();
            dialog.querySelector('h2').focus();
            try {
                const result = await api(root.dataset.detailUrl + '?' + new URLSearchParams(scope(group)));
                if (sequence !== requestNumber || !dialog.open) return;
                active = result.group;
                choices = Object.fromEntries(active.rows.map(row => [row.key, row.conciliable]));
                detail.innerHTML = result.html;
                updateRow(active);
                accept.disabled = false;
            } catch (failure) {
                if (sequence !== requestNumber) return;
                error.textContent = failure.message;
                error.hidden = false;
            } finally { if (sequence === requestNumber) loading.hidden = true; }
            return;
        }
        const send = event.target.closest('[data-period-send]');
        if (send && !send.disabled) {
            const group = groups.get(send.closest('[data-period-row]').dataset.periodRow);
            send.disabled = true;
            send.textContent = 'Enviando…';
            try {
                const result = await api(root.dataset.sendUrl, { ...scope(group), version: group.version, submission_key: group.submission_key });
                updateRow({ ...group, sent_folio: result.folio, bucket: 'enviadas' });
                announce(`Conciliación ${result.folio} enviada a ${group.hospital}.`);
            } catch (failure) {
                send.disabled = false;
                send.textContent = 'Enviar';
                announce(failure.message, true);
            }
        }
    });
    accept.addEventListener('click', async () => {
        if (!active || saving) return;
        busy(true);
        error.hidden = true;
        try {
            const result = await api(root.dataset.acceptUrl, { ...scope(active), version: active.version, choices });
            updateRow(result.group);
            dialog.close();
            announce('Revisión guardada. El nuevo monto está actualizado; puedes enviar el periodo desde su fila.');
        } catch (failure) {
            error.textContent = failure.message;
            error.hidden = false;
        } finally { busy(false); }
    });
    const institution = root.querySelector('#cp-institution');
    const hospital = root.querySelector('#cp-hospital');
    const syncHospitals = () => {
        [...hospital.options].forEach(option => {
            if (!option.value) return;
            option.hidden = option.disabled = !!institution.value && !JSON.parse(option.dataset.institutions).includes(Number(institution.value));
        });
    };
    institution.addEventListener('change', () => { hospital.value = ''; syncHospitals(); });
    syncHospitals();
    document.addEventListener('livewire:navigating', () => { columnFilters?.destroy(); dialog.close(); }, { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeConciliationPeriod);
else initializeConciliationPeriod();
document.addEventListener('livewire:navigated', initializeConciliationPeriod);
