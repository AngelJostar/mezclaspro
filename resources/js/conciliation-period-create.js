function initializePeriodCreator() {
    const dialog = document.querySelector('[data-period-create-dialog]');
    const launch = document.querySelector('[data-period-create-open]');
    if (!dialog || !launch || dialog.dataset.ready) return;
    dialog.dataset.ready = 'true';
    const find = selector => dialog.querySelector(selector);
    const form = find('[data-create-filters]');
    const body = find('[data-create-rows]');
    const toggleAll = find('[data-create-toggle-all]');
    const submit = find('[data-create-submit]');
    const error = find('[data-create-error]');
    const refresh = find('[data-create-refresh]');
    const money = cents => cents === null ? 'Sin registrar' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cents / 100) + ' MXN';
    const date = value => value.slice(0, 10).split('-').reverse().join('/');
    let rows = [], filtered = [], selection = new Map(), filters = {}, columnFilters = null, saving = false, loading = false, sequence = 0, creationKey;

    function announce(message = '', retry = false) {
        error.textContent = message;
        error.hidden = !message;
        refresh.hidden = !retry;
    }

    async function api(url, data) {
        const response = await fetch(url, {
            method: data ? 'POST' : 'GET', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            ...(data ? { body: JSON.stringify(data) } : {}),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo completar la operación. Inténtalo nuevamente.');
        return result;
    }

    function options(field, values, first) {
        const select = form.elements[field], current = select.value;
        select.replaceChildren(new Option(first, ''));
        [...values].sort((a, b) => a[1].localeCompare(b[1], 'es')).forEach(([id, label]) => select.add(new Option(label, id)));
        if ([...select.options].some(option => option.value === current)) select.value = current;
    }

    function hospitals() {
        const institution = form.elements.institution.value;
        options('hospital', new Map(rows.filter(row => !institution || String(row.institution_id) === institution).map(row => [row.hospital_id, row.hospital])), 'Todos los hospitales');
    }

    function applyTopFilters() {
        if (saving || loading) return;
        const next = Object.fromEntries(new FormData(form));
        validateDates();
        if (!form.elements.to.validity.valid && next.to) { announce('La fecha final debe ser igual o posterior a la inicial.'); return; }
        filters = next;
        announce();
        refreshFilteredRows();
    }

    function validateDates() {
        const from = form.elements.from.value, to = form.elements.to.value;
        form.elements.to.setCustomValidity(from && to && from > to ? 'La fecha final debe ser igual o posterior a la inicial.' : '');
    }

    function summary() {
        const selected = [...selection.values()];
        const from = form.elements.from.value, to = form.elements.to.value;
        find('[data-create-range]').textContent = from && to && from <= to ? `${date(from)} – ${date(to)}` : 'Sin definir';
        find('[data-create-selected]').textContent = selected.length === 1 ? '1 remisión seleccionada' : `${selected.length} remisiones seleccionadas`;
        const hospitalCount = new Set(selected.map(row => row.hospital_id)).size;
        find('[data-create-period-count]').textContent = hospitalCount === 1 ? '1 periodo' : `${hospitalCount} periodos`;
        find('[data-create-total]').textContent = money(selected.some(row => row.amount_cents === null) ? null : selected.reduce((sum, row) => sum + row.amount_cents, 0));
        find('[data-create-count]').textContent = loading ? 'Cargando remisiones…' : `${filtered.length} resultados · ${selected.length} seleccionadas`;
        const available = filtered.filter(row => !row.period_id);
        const checked = available.filter(row => selection.has(row.key)).length;
        toggleAll.disabled = saving || loading || !available.length;
        toggleAll.checked = available.length > 0 && checked === available.length;
        toggleAll.indeterminate = checked > 0 && checked < available.length;
        toggleAll.title = toggleAll.checked ? 'Deseleccionar todas las remisiones filtradas disponibles' : 'Seleccionar todas las remisiones filtradas disponibles';
        submit.disabled = saving || loading || !selected.length;
        find('[data-create-clear]').disabled = saving || loading;
        dialog.querySelectorAll('.cp-create-filter').forEach(button => { button.disabled = saving || loading || !rows.length; });
    }

    function refreshFilteredRows() {
        const visible = new Set();
        const matchingKeys = new Set(rows.filter(row => (!filters.institution || String(row.institution_id) === filters.institution)
            && (!filters.hospital || String(row.hospital_id) === filters.hospital)
            && (!filters.from || row.date.slice(0, 10) >= filters.from)
            && (!filters.to || row.date.slice(0, 10) <= filters.to)).map(row => row.key));
        body.querySelectorAll('[data-create-row]').forEach(tr => {
            tr.hidden = tr.dataset.columnFilterMatch === '0' || !matchingKeys.has(tr.dataset.createRow);
            if (!tr.hidden) visible.add(tr.dataset.createRow);
        });
        filtered = rows.filter(row => visible.has(row.key));
        find('[data-create-empty]').hidden = filtered.length > 0;
        summary();
    }

    function updateSelection() {
        body.querySelectorAll('[data-create-choice]').forEach(checkbox => {
            checkbox.checked = selection.has(checkbox.dataset.createChoice);
            checkbox.disabled = saving || !!checkbox.dataset.assignedPeriod;
            checkbox.closest('tr').dataset.selected = String(checkbox.checked);
            checkbox.closest('td').dataset.filterValue = checkbox.dataset.assignedPeriod
                ? `En periodo #${checkbox.dataset.assignedPeriod}` : (checkbox.checked ? 'Seleccionada' : 'Sin seleccionar');
        });
        if (columnFilters) columnFilters.apply();
        else refreshFilteredRows();
    }

    function renderRows() {
        const fragment = document.createDocumentFragment();
        for (const row of rows) {
            const tr = document.createElement('tr');
            tr.dataset.createRow = row.key;
            tr.dataset.selected = String(selection.has(row.key));
            for (const text of [row.remision, row.institution, row.hospital, row.patient, date(row.date), row.lines.map(line => line.description).join('; '), money(row.amount_cents)]) {
                const td = document.createElement('td');
                td.textContent = text;
                tr.append(td);
            }
            const td = document.createElement('td'), checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.dataset.createChoice = row.key;
            checkbox.dataset.assignedPeriod = row.period_id || '';
            checkbox.setAttribute('aria-label', `Seleccionar ${row.remision}, ${row.patient}`);
            checkbox.checked = selection.has(row.key);
            checkbox.disabled = saving || !!row.period_id;
            td.append(checkbox);
            if (row.period_id) {
                const note = document.createElement('small');
                note.textContent = `En periodo #${row.period_id}`;
                tr.firstElementChild.append(note);
            }
            tr.append(td);
            fragment.append(tr);
        }
        const tr = document.createElement('tr'), td = document.createElement('td');
        tr.dataset.createEmpty = '';
        td.colSpan = 8;
        td.className = 'ht-empty';
        td.textContent = loading ? 'Cargando remisiones…' : 'No hay remisiones que coincidan con los filtros.';
        tr.append(td);
        fragment.append(tr);
        body.replaceChildren(fragment);
        updateSelection();
    }

    function selectRows(candidates, checked) {
        if (saving || loading) return;
        announce();
        candidates.forEach(row => checked ? selection.set(row.key, row) : selection.delete(row.key));
        updateSelection();
    }

    async function load() {
        const request = ++sequence;
        loading = true;
        columnFilters?.destroy();
        columnFilters = null;
        rows = []; selection.clear(); filters = {}; form.reset(); validateDates();
        creationKey = crypto.randomUUID();
        announce(); renderRows();
        try {
            const result = await api(dialog.dataset.candidatesUrl);
            if (request !== sequence || !dialog.open) return;
            rows = result.rows;
            options('institution', new Map(rows.map(row => [row.institution_id, row.institution])), 'Selecciona una institución');
            hospitals();
        } catch (failure) {
            if (request === sequence) announce(failure.message, true);
        } finally {
            if (request === sequence) {
                loading = false;
                renderRows();
                columnFilters = window.createExcelColumnFilters({
                    tableId: 'conciliation-period-create-table', rowSelector: 'tbody tr[data-create-row]',
                    triggerSelector: '.cp-create-filter', instanceId: 'conciliation-period-create',
                    panelParent: dialog, onChange: refreshFilteredRows,
                });
            }
        }
    }

    launch.addEventListener('click', () => { dialog.showModal(); find('h2').focus(); load(); });
    form.elements.institution.addEventListener('change', hospitals);
    form.addEventListener('change', applyTopFilters);
    form.addEventListener('submit', event => { event.preventDefault(); applyTopFilters(); });
    find('[data-create-clear]').addEventListener('click', () => {
        if (saving || loading) return;
        form.reset(); validateDates(); hospitals(); filters = {}; announce(); columnFilters?.clear();
    });
    body.addEventListener('change', event => {
        const key = event.target.dataset.createChoice;
        if (key) selectRows(rows.filter(row => row.key === key && !row.period_id), event.target.checked);
    });
    toggleAll.addEventListener('change', () => selectRows(filtered.filter(row => !row.period_id), toggleAll.checked));
    refresh.addEventListener('click', load);
    dialog.querySelectorAll('[data-create-close]').forEach(button => button.addEventListener('click', () => { if (!saving) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => { sequence++; selection.clear(); columnFilters?.close(); });
    submit.addEventListener('click', async () => {
        if (saving || loading || !selection.size) return;
        validateDates();
        if (!form.reportValidity()) return;
        const institutionId = Number(form.elements.institution.value);
        const from = form.elements.from.value, to = form.elements.to.value;
        const selected = [...selection.values()];
        if (selected.some(row => Number(row.institution_id) !== institutionId)) {
            announce('Las remisiones seleccionadas deben pertenecer a la institución indicada. Revisa la selección.');
            return;
        }
        if (selected.some(row => row.date.slice(0, 10) < from || row.date.slice(0, 10) > to)) {
            announce('Hay remisiones seleccionadas fuera del rango Desde–Hasta. Ajusta las fechas o quita esas remisiones de la selección.');
            return;
        }
        saving = true; announce();
        columnFilters?.close();
        dialog.setAttribute('aria-busy', 'true');
        dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = true; });
        submit.textContent = 'Creando periodo…';
        try {
            const hospitalIds = [...new Set(selected.map(row => row.hospital_id))];
            const result = await api(dialog.dataset.storeUrl, {
                creation_key: creationKey, institucion_id: institutionId, hospital_id: hospitalIds.length === 1 ? hospitalIds[0] : null,
                desde: from, hasta: to,
                selection: Object.fromEntries(selected.map(row => [row.key, row.version])),
            });
            window.location.assign(result.redirect);
        } catch (failure) {
            announce(failure.message, true);
            saving = false;
            dialog.setAttribute('aria-busy', 'false');
            dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = false; });
            submit.textContent = 'Crear periodo';
            updateSelection();
        }
    });
    document.addEventListener('livewire:navigating', () => { sequence++; if (dialog.open) dialog.close(); columnFilters?.destroy(); }, { once: true });
}

document.addEventListener('DOMContentLoaded', initializePeriodCreator);
document.addEventListener('livewire:navigated', initializePeriodCreator);
if (document.readyState !== 'loading') initializePeriodCreator();
