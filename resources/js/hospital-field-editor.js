import '../css/hospital-field-editor.css';

function initializeHospitalFieldEditor() {
    const dialog = document.querySelector('[data-hospital-field-dialog]');
    if (!dialog || dialog.dataset.ready) return;
    dialog.dataset.ready = 'true';
    const find = (key) => dialog.querySelector(`[data-hospital-field-${key}]`);
    const form = find('form');
    const options = find('options');
    const error = find('error');
    const loading = find('loading');
    const save = find('save');
    const retry = find('retry');
    const search = find('search');
    const scroll = document.querySelector('[data-hospital-field-table-scroll]');
    const scrollKey = `hospital-field-scroll:${location.pathname}${location.search}`;
    let trigger, controller, version, busy = false;

    try {
        const position = sessionStorage.getItem(scrollKey);
        if (position !== null && scroll) scroll.scrollLeft = Number(position);
        sessionStorage.removeItem(scrollKey);
    } catch { /* Storage can be disabled by the browser. */ }

    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
    };
    const readResponse = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message
            || (response.status === 419 ? 'La sesión venció. Actualiza la página e inténtalo de nuevo.' : 'No fue posible completar la operación. Inténtalo de nuevo.'));
        return data;
    };

    async function load() {
        controller?.abort();
        controller = new AbortController();
        const signal = controller.signal;
        save.disabled = true;
        options.replaceChildren();
        error.hidden = retry.hidden = find('empty').hidden = find('search-wrap').hidden = true;
        loading.hidden = false;
        search.value = '';
        try {
            const response = await fetch(trigger.dataset.url, { headers: { Accept: 'application/json' }, signal, cache: 'no-store' });
            const data = await readResponse(response);
            if (signal.aborted) return;
            version = data.version;
            find('title').textContent = data.title;
            find('hospital').textContent = data.hospital;
            const legend = document.createElement('legend');
            legend.className = 'sr-only';
            legend.textContent = data.title;
            options.append(legend);
            data.options.forEach((option) => {
                const label = document.createElement('label');
                label.className = 'hospital-field-option';
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.value = String(option.id);
                input.checked = data.selected.map(String).includes(input.value);
                const text = document.createElement('span');
                text.textContent = option.label;
                label.append(input, text);
                options.append(label);
            });
            find('empty').hidden = data.options.length > 0;
            find('search-wrap').hidden = trigger.dataset.field === 'services' || data.options.length === 0;
            save.disabled = false;
            options.querySelector('input')?.focus();
        } catch (failure) {
            if (signal.aborted) return;
            showError(failure.message);
            retry.hidden = false;
        } finally {
            if (!signal.aborted) loading.hidden = true;
        }
    }

    document.querySelectorAll('[data-hospital-field-edit]').forEach((button) => button.addEventListener('click', () => {
        trigger = button;
        find('title').textContent = button.title;
        find('hospital').textContent = '';
        dialog.showModal();
        load();
    }));
    dialog.querySelectorAll('[data-hospital-field-close]').forEach((button) => button.addEventListener('click', () => {
        if (!busy) dialog.close();
    }));
    dialog.addEventListener('cancel', (event) => { if (busy) event.preventDefault(); });
    dialog.addEventListener('close', () => { controller?.abort(); trigger?.focus(); });
    retry.addEventListener('click', load);
    search.addEventListener('input', () => {
        const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        options.querySelectorAll('label').forEach((label) => {
            label.hidden = !normalize(label.textContent).includes(normalize(search.value.trim()));
        });
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || save.disabled) return;
        busy = true;
        error.hidden = true;
        const values = Array.from(options.querySelectorAll('input:checked'), (input) => input.value);
        dialog.querySelectorAll('button, input').forEach((control) => { control.disabled = true; });
        save.textContent = 'Guardando…';
        try {
            const response = await fetch(trigger.dataset.url, {
                method: 'PATCH', headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ values, version }),
            });
            await readResponse(response);
            try { sessionStorage.setItem(scrollKey, String(scroll?.scrollLeft || 0)); } catch { /* Optional scroll restoration. */ }
            window.location.reload();
        } catch (failure) {
            showError(failure.message);
            busy = false;
            dialog.querySelectorAll('button, input').forEach((control) => { control.disabled = false; });
            save.textContent = 'Guardar cambios';
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeHospitalFieldEditor);
document.addEventListener('livewire:navigated', initializeHospitalFieldEditor);
