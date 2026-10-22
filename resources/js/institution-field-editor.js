import '../css/hospital-field-editor.css';

function initializeInstitutionFieldEditor() {
    const dialog = document.querySelector('[data-institution-field-dialog]');
    if (!dialog || dialog.dataset.ready) return;
    dialog.dataset.ready = 'true';
    const get = key => dialog.querySelector(`[data-institution-field-${key}]`);
    const form = dialog.querySelector('form');
    const input = get('input');
    const save = get('save');
    const scroll = document.querySelector('#institutions-table')?.parentElement;
    const scrollKey = `institution-field-scroll:${location.pathname}${location.search}`;
    let trigger, controller, version, busy = false;
    try {
        const stored = JSON.parse(sessionStorage.getItem(scrollKey));
        sessionStorage.removeItem(scrollKey);
        if (stored && scroll) requestAnimationFrame(() => { scroll.scrollLeft = stored.x; window.scrollTo(0, stored.y); });
    } catch { /* Scroll restoration is optional. */ }

    const read = async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo guardar. Inténtalo de nuevo.');
        return data;
    };
    const showError = message => { get('error').textContent = message; get('error').hidden = false; };
    async function load() {
        controller?.abort();
        controller = new AbortController();
        const { signal } = controller;
        get('loading').hidden = false;
        get('input-wrap').hidden = true;
        get('error').hidden = get('retry').hidden = true;
        save.disabled = true;
        try {
            const data = await read(await fetch(trigger.dataset.url, { headers: { Accept: 'application/json' }, signal, cache: 'no-store' }));
            if (signal.aborted) return;
            version = data.version;
            input.value = data.value;
            get('input-wrap').hidden = false;
            save.disabled = false;
            input.focus();
        } catch (failure) {
            if (signal.aborted) return;
            showError(failure.message);
            get('retry').hidden = false;
        } finally { if (!signal.aborted) get('loading').hidden = true; }
    }
    document.querySelectorAll('[data-institution-field-edit]').forEach(button => button.addEventListener('click', () => {
        trigger = button;
        version = null;
        form.reset();
        get('title').textContent = `Editar ${button.dataset.label.toLocaleLowerCase('es')}`;
        get('context').textContent = button.dataset.context;
        get('label').textContent = button.dataset.field === 'password' ? 'Nueva contraseña' : button.dataset.label;
        get('error').hidden = get('retry').hidden = get('loading').hidden = true;
        get('input-wrap').hidden = false;
        const password = button.dataset.field === 'password';
        input.type = password ? 'password' : 'text';
        input.autocomplete = password ? 'new-password' : 'off';
        input.value = password ? '' : (button.dataset.value || '');
        get('reveal').hidden = !password;
        get('reveal').textContent = 'Mostrar contraseña';
        save.disabled = false;
        dialog.showModal();
        if (button.dataset.load === 'true') load();
        else input.focus();
    }));
    get('reveal').addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
        get('reveal').textContent = input.type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña';
    });
    get('retry').addEventListener('click', load);
    dialog.querySelectorAll('[data-institution-field-close]').forEach(button => button.addEventListener('click', () => { if (!busy) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    dialog.addEventListener('close', () => { controller?.abort(); input.value = ''; trigger?.focus(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || save.disabled || !form.reportValidity()) return;
        const payload = trigger.dataset.load === 'true' ? { value: input.value, version } : { [trigger.dataset.field]: input.value };
        const token = form.querySelector('[name="_token"]').value;
        busy = true;
        get('error').hidden = true;
        dialog.querySelectorAll('button, input').forEach(control => { control.disabled = true; });
        save.textContent = 'Guardando…';
        try {
            await read(await fetch(trigger.dataset.url, { method: 'PATCH', headers: {
                Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token,
            }, body: JSON.stringify(payload) }));
            input.value = '';
            try { sessionStorage.setItem(scrollKey, JSON.stringify({ x: scroll?.scrollLeft || 0, y: window.scrollY })); } catch { /* Optional. */ }
            window.location.reload();
        } catch (failure) {
            showError(failure.message);
            busy = false;
            dialog.querySelectorAll('button, input').forEach(control => { control.disabled = false; });
            save.textContent = 'Guardar cambios';
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeInstitutionFieldEditor);
document.addEventListener('livewire:navigated', initializeInstitutionFieldEditor);
