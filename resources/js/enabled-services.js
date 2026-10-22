import '../css/enabled-services.css';

function initializeEnabledServices() {
    const dialog = document.querySelector('[data-enabled-services-dialog]');
    if (!dialog || dialog.dataset.ready) return;
    dialog.dataset.ready = 'true';
    const get = (key) => dialog.querySelector(`[data-services-${key}]`);
    const form = dialog.querySelector('form');
    const save = get('save');
    let trigger, controller, state, busy = false;

    const read = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo completar la operación. Inténtalo de nuevo.');
        return data;
    };
    const error = (message) => { get('error').textContent = message; get('error').hidden = false; };
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    function refreshSummary() {
        if (!state) return;
        const query = normalize(get('search').value.trim());
        let visible = 0;
        get('fields').querySelectorAll('[data-hospital-name]').forEach(row => {
            row.hidden = !normalize(row.dataset.hospitalName).includes(query);
            if (!row.hidden) visible++;
        });
        const total = state.hospitals.length;
        get('count').textContent = query ? `${visible} de ${total} hospitales` : `${total} ${total === 1 ? 'hospital' : 'hospitales'}`;
        const enabled = state.hospitals.filter(hospital => state.services.some(service => hospital.services[service.key])).length;
        get('enabled-count').textContent = `${enabled} ${enabled === 1 ? 'habilitado' : 'habilitados'}`;
        get('no-results').hidden = !query || visible > 0 || state.scope !== 'institution';
        for (const service of state.services) {
            const values = state.hospitals.map(hospital => hospital.services[service.key]);
            get('fields').querySelectorAll('[data-bulk-key]').forEach(button => {
                if (button.dataset.bulkKey !== service.key) return;
                const enabled = button.dataset.bulkValue === 'true';
                button.setAttribute('aria-pressed', String(values.length ? values.every(value => value === enabled) : service.value === enabled));
            });
        }
    }
    function paintSwitch(button, enabled) {
        button.setAttribute('aria-checked', String(enabled));
        button.querySelector('[data-switch-text]').textContent = enabled ? 'Sí' : 'No';
        const label = button.parentElement?.querySelector('.enabled-service-state');
        if (label) label.textContent = enabled ? 'Habilitada' : 'Inhabilitada';
    }
    function serviceSwitch(hospital, service) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'enabled-service-switch';
        button.dataset.serviceKey = service.key;
        button.setAttribute('role', 'switch');
        button.setAttribute('aria-label', `${service.label} de ${hospital.name}`);
        const thumb = document.createElement('span');
        thumb.className = 'enabled-service-thumb';
        thumb.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.dataset.switchText = '';
        button.append(thumb, text);
        paintSwitch(button, hospital.services[service.key]);
        button.addEventListener('click', () => {
            hospital.services[service.key] = !hospital.services[service.key];
            paintSwitch(button, hospital.services[service.key]);
            refreshSummary();
        });
        return button;
    }
    function switchCell(hospital, service) {
        const wrap = document.createElement('div');
        wrap.className = 'enabled-service-control';
        const label = document.createElement('span');
        label.className = 'enabled-service-state';
        label.textContent = hospital.services[service.key] ? 'Habilitada' : 'Inhabilitada';
        wrap.append(serviceSwitch(hospital, service), label);
        return wrap;
    }
    function renderHospital(data) {
        const list = document.createElement('ul');
        list.className = 'enabled-services-list';
        list.setAttribute('aria-label', 'Servicios del hospital');
        for (const service of data.services) {
            const item = document.createElement('li');
            const label = document.createElement('span');
            label.textContent = service.label;
            item.append(label, serviceSwitch(data.hospitals[0], service));
            list.append(item);
        }
        get('fields').append(list);
    }
    function renderTable(data) {
        const wrap = document.createElement('div');
        wrap.className = 'enabled-services-table-wrap';
        const table = document.createElement('table');
        table.className = 'enabled-services-table';
        table.dataset.disableColumnFilters = '';
        const head = table.createTHead().insertRow();
        const hospitalHeading = document.createElement('th');
        hospitalHeading.scope = 'col';
        hospitalHeading.textContent = 'Hospital';
        head.append(hospitalHeading);
        for (const service of data.services) {
            const th = document.createElement('th');
            th.scope = 'col';
            const title = document.createElement('span');
            title.textContent = service.label;
            th.append(title);
            if (data.scope === 'institution') {
                const controls = document.createElement('div');
                controls.className = 'enabled-services-all';
                const allLabel = document.createElement('span');
                allLabel.className = 'enabled-services-all-label';
                allLabel.textContent = 'Todos';
                controls.append(allLabel);
                for (const enabled of [true, false]) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = enabled ? 'SÍ' : 'NO';
                    button.setAttribute('aria-label', enabled ? 'Habilitar todos' : 'Inhabilitar todos');
                    button.title = `${enabled ? 'Habilitar' : 'Inhabilitar'} todos los hospitales, incluidos los ocultos por la búsqueda`;
                    button.dataset.bulkKey = service.key;
                    button.dataset.bulkValue = String(enabled);
                    button.className = enabled ? 'enable-all' : 'disable-all';
                    button.addEventListener('click', () => {
                        service.value = enabled;
                        data.hospitals.forEach(hospital => { hospital.services[service.key] = enabled; });
                        table.querySelectorAll('[role="switch"]').forEach(toggle => {
                            if (toggle.dataset.serviceKey === service.key) paintSwitch(toggle, enabled);
                        });
                        refreshSummary();
                    });
                    controls.append(button);
                }
                th.append(controls);
            }
            head.append(th);
        }
        const body = table.createTBody();
        for (const hospital of data.hospitals) {
            const row = body.insertRow();
            row.dataset.hospitalName = hospital.name;
            const cell = row.insertCell();
            const name = document.createElement('span');
            name.className = 'enabled-hospital-name';
            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('fill', 'none'); icon.setAttribute('stroke', 'currentColor'); icon.setAttribute('stroke-width', '1.5'); icon.setAttribute('aria-hidden', 'true');
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', 'M7 21V3h10v18M7 8H3v13h18V8h-4M10 7h4m-2-2v4M10 12h1m2 0h1m-4 3h1m2 0h1m-4 6v-3h4v3');
            icon.append(path);
            const text = document.createElement('span'); text.textContent = hospital.name;
            name.append(icon, text); cell.append(name);
            for (const service of data.services) {
                row.insertCell().append(switchCell(hospital, service));
            }
        }
        if (!data.hospitals.length) {
            const cell = body.insertRow().insertCell();
            cell.colSpan = data.services.length + 1;
            cell.textContent = data.empty_message || 'Esta institución aún no tiene hospitales. La selección general se aplicará a los nuevos hospitales.';
        }
        wrap.append(table);
        get('fields').append(wrap);
    }
    async function load() {
        controller?.abort();
        controller = new AbortController();
        const signal = controller.signal;
        save.disabled = true;
        get('fields').replaceChildren();
        get('help').textContent = '';
        get('description').textContent = '';
        get('search').value = '';
        get('search-wrap').hidden = get('summary').hidden = get('no-results').hidden = get('help-wrap').hidden = true;
        get('error').hidden = get('retry').hidden = true;
        get('loading').hidden = false;
        try {
            const data = await read(await fetch(trigger.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal }));
            if (signal.aborted) return;
            state = data;
            get('title').textContent = data.title || 'Servicios habilitados';
            get('name').textContent = data.name;
            dialog.dataset.scope = data.scope;
            dialog.dataset.kind = data.title === 'Mezclas habilitadas' ? 'mixtures' : 'services';
            get('description').textContent = data.title === 'Mezclas habilitadas' ? 'Administra los tipos de mezclas disponibles por hospital.' : 'Administra qué hospitales pueden solicitar cotizaciones.';
            if (data.scope === 'hospital') renderHospital(data);
            else renderTable(data);
            get('help').textContent = 'Los cambios se aplican al pulsar Guardar cambios.';
            get('help-wrap').hidden = false;
            get('search-wrap').hidden = get('summary').hidden = data.scope !== 'institution';
            refreshSummary();
            save.disabled = data.can_save === false;
            (data.scope === 'institution' ? get('search') : get('fields').querySelector('[role="switch"], button'))?.focus({ preventScroll: true });
        } catch (failure) {
            if (signal.aborted) return;
            error(failure.message);
            get('retry').hidden = false;
        } finally { if (!signal.aborted) get('loading').hidden = true; }
    }
    document.querySelectorAll('[data-enabled-services-open]').forEach(button => button.addEventListener('click', () => {
        trigger = button;
        get('title').textContent = button.dataset.title || 'Servicios habilitados';
        get('name').textContent = '';
        dialog.showModal();
        load();
    }));
    dialog.querySelectorAll('[data-services-close]').forEach(button => button.addEventListener('click', () => { if (!busy) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    dialog.addEventListener('close', () => { controller?.abort(); trigger?.focus(); });
    get('retry').addEventListener('click', load);
    get('search').addEventListener('input', refreshSummary);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || save.disabled) return;
        const services = state.scope === 'hospital' ? state.hospitals[0].services
            : Object.fromEntries(state.services.map(service => [service.key, service.value]));
        const payload = { services, version: state.version };
        if (state.scope === 'institution') payload.hospital_services = state.hospitals.map(({ id, services }) => ({ id, services }));
        busy = true;
        get('error').hidden = true;
        dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = true; });
        save.textContent = 'Guardando…';
        try {
            await read(await fetch(trigger.dataset.url, { method: 'PATCH', headers: {
                Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            }, body: JSON.stringify(payload) }));
            try {
                const institutions = document.querySelector('#institutions-table')?.parentElement;
                if (institutions) sessionStorage.setItem(`institution-field-scroll:${location.pathname}${location.search}`, JSON.stringify({ x: institutions.scrollLeft, y: window.scrollY }));
                else sessionStorage.setItem(`hospital-field-scroll:${location.pathname}${location.search}`, String(document.querySelector('[data-hospital-field-table-scroll]')?.scrollLeft || 0));
            } catch { /* Optional scroll restoration. */ }
            window.location.reload();
        } catch (failure) {
            error(failure.message);
            busy = false;
            dialog.querySelectorAll('button, input, select').forEach(control => { control.disabled = false; });
            save.textContent = 'Guardar cambios';
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeEnabledServices);
document.addEventListener('livewire:navigated', initializeEnabledServices);
