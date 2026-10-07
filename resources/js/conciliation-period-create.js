function initializePeriodCreator() {
    const dialog = document.querySelector('[data-period-create-dialog]');
    const launch = document.querySelector('[data-period-create-open]');
    if (!dialog || !launch || dialog.dataset.ready) return;
    dialog.dataset.ready = 'true';
    const find = selector => dialog.querySelector(selector);
    const form = find('[data-create-filters]');
    const body = find('[data-create-rows]');
    const all = find('[data-create-all]');
    const submit = find('[data-create-submit]');
    const error = find('[data-create-error]');
    const refresh = find('[data-create-refresh]');
    const money = cents => cents === null ? 'Sin registrar' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cents / 100) + ' MXN';
    const date = value => value.slice(0, 10).split('-').reverse().join('/');
    const normalize = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    let rows = [], filtered = [], selection = new Map(), filters = {}, saving = false, loading = false, sequence = 0, creationKey;

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

    function summary() {
        const selected = [...selection.values()];
        const dates = selected.map(row => row.date.slice(0, 10)).sort();
        find('[data-create-range]').textContent = dates.length ? `${date(dates[0])} – ${date(dates.at(-1))}` : 'Sin seleccionar';
        find('[data-create-selected]').textContent = `${selected.length} remisiones seleccionadas`;
        find('[data-create-total]').textContent = money(selected.some(row => row.amount_cents === null) ? null : selected.reduce((sum, row) => sum + row.amount_cents, 0));
        find('[data-create-count]').textContent = loading ? 'Cargando remisiones…' : `${filtered.length} resultados · ${selected.length} seleccionadas`;
        const available = filtered.filter(row => !row.period_id);
        const checked = available.filter(row => selection.has(row.key)).length;
        all.disabled = saving || loading || !available.length;
        all.checked = available.length > 0 && checked === available.length;
        all.indeterminate = checked > 0 && checked < available.length;
        submit.disabled = saving || loading || !selected.length;
        find('[data-create-reset]').disabled = saving || !selected.length;
    }

    function render() {
        filtered = rows.filter(row => (!filters.institution || String(row.institution_id) === filters.institution)
            && (!filters.hospital || String(row.hospital_id) === filters.hospital)
            && (!filters.from || row.date.slice(0, 10) >= filters.from)
            && (!filters.to || row.date.slice(0, 10) <= filters.to)
            && (!filters.status || row.status === filters.status)
            && (!filters.search || normalize(`${row.remision} ${row.patient}`).includes(normalize(filters.search.trim()))));
        const fragment = document.createDocumentFragment();
        for (const row of filtered) {
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
            checkbox.setAttribute('aria-label', `Seleccionar ${row.remision}, ${row.patient}`);
            checkbox.checked = selection.has(row.key);
            checkbox.disabled = saving || !!row.period_id;
            td.append(checkbox);
            if (row.period_id) {
                const note = document.createElement('small');
                note.textContent = `En periodo #${row.period_id}`;
                td.append(note);
            }
            tr.append(td);
            fragment.append(tr);
        }
        if (!filtered.length) {
            const tr = document.createElement('tr'), td = document.createElement('td');
            td.colSpan = 8;
            td.className = 'ht-empty';
            td.textContent = loading ? 'Cargando remisiones…' : 'No hay remisiones que coincidan con los filtros.';
            tr.append(td);
            fragment.append(tr);
        }
        body.replaceChildren(fragment);
        summary();
    }

    function selectRows(candidates, checked) {
        if (saving || loading) return;
        announce();
        if (checked) {
            const recipients = new Set([...selection.values(), ...candidates].map(row => `${row.institution_id}:${row.hospital_id}`));
            if (recipients.size > 1) {
                announce('Selecciona remisiones de una sola institución y hospital. Puedes usar los filtros para elegir el destinatario del periodo.');
                render();
                return;
            }
        }
        candidates.forEach(row => checked ? selection.set(row.key, row) : selection.delete(row.key));
        render();
    }

    async function load() {
        const request = ++sequence;
        loading = true;
        rows = []; selection.clear(); filters = {}; form.reset();
        creationKey = crypto.randomUUID();
        announce(); render();
        try {
            const result = await api(dialog.dataset.candidatesUrl);
            if (request !== sequence || !dialog.open) return;
            rows = result.rows;
            options('institution', new Map(rows.map(row => [row.institution_id, row.institution])), 'Todas las instituciones');
            hospitals();
            options('status', new Map(rows.map(row => [row.status, row.status])), 'Todas');
        } catch (failure) {
            if (request === sequence) announce(failure.message, true);
        } finally {
            if (request === sequence) { loading = false; render(); }
        }
    }

    launch.addEventListener('click', () => { dialog.showModal(); find('h2').focus(); load(); });
    form.elements.institution.addEventListener('change', hospitals);
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (saving || loading) return;
        const next = Object.fromEntries(new FormData(form));
        if (next.from && next.to && next.from > next.to) { announce('La fecha final debe ser posterior a la inicial.'); return; }
        filters = next; announce(); render();
    });
    find('[data-create-clear]').addEventListener('click', () => { if (saving) return; form.reset(); hospitals(); filters = {}; announce(); render(); });
    find('[data-create-reset]').addEventListener('click', () => { if (saving) return; selection.clear(); announce(); render(); });
    body.addEventListener('change', event => {
        const key = event.target.dataset.createChoice;
        if (key) selectRows(rows.filter(row => row.key === key && !row.period_id), event.target.checked);
    });
    all.addEventListener('change', () => selectRows(filtered.filter(row => !row.period_id), all.checked));
    refresh.addEventListener('click', load);
    dialog.querySelectorAll('[data-create-close]').forEach(button => button.addEventListener('click', () => { if (!saving) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => { sequence++; selection.clear(); });
    submit.addEventListener('click', async () => {
        if (saving || loading || !selection.size) return;
        saving = true; announce();
        dialog.setAttribute('aria-busy', 'true');
        dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = true; });
        submit.textContent = 'Creando periodo…';
        try {
            const selected = [...selection.values()];
            const result = await api(dialog.dataset.storeUrl, {
                creation_key: creationKey, institucion_id: selected[0].institution_id, hospital_id: selected[0].hospital_id,
                selection: Object.fromEntries(selected.map(row => [row.key, row.version])),
            });
            window.location.assign(result.redirect);
        } catch (failure) {
            announce(failure.message, true);
            saving = false;
            dialog.setAttribute('aria-busy', 'false');
            dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = false; });
            submit.textContent = 'Crear periodo';
            render();
        }
    });
    document.addEventListener('livewire:navigating', () => { sequence++; if (dialog.open) dialog.close(); }, { once: true });
}

document.addEventListener('DOMContentLoaded', initializePeriodCreator);
document.addEventListener('livewire:navigated', initializePeriodCreator);
if (document.readyState !== 'loading') initializePeriodCreator();
