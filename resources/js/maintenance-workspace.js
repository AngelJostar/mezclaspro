import '../css/maintenance-workspace.css';

function initializeMaintenanceWorkspace() {
    const root = document.querySelector('[data-maintenance-workspace]');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    let trigger = null;
    const close = dialog => {
        if (dialog.dataset.busy === 'true') return;
        dialog.close();
        dialog.querySelector('form').reset();
        trigger?.focus({ preventScroll: true });
    };
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-maint-open]');
        if (!button) return;
        const kind = button.dataset.maintOpen;
        const dialog = root.querySelector(`[data-maint-dialog="${kind}"]`);
        const form = dialog?.querySelector('form');
        if (!form) return;
        trigger = button;
        form.reset();
        form.dataset.url = button.dataset.url || form.dataset.storeUrl;
        form.dataset.method = button.dataset.url && ['service', 'provider', 'appointment'].includes(kind) ? 'PATCH' : 'POST';
        const record = JSON.parse(button.dataset.record || '{}');
        for (const control of form.elements) {
            const key = control.name?.replace(/\[\]$/, '');
            if (!key || !(key in record)) continue;
            const value = record[key];
            if (control.type === 'checkbox') control.checked = (value || []).map(String).includes(control.value);
            else control.value = typeof value === 'boolean' ? Number(value).toString() : value ?? '';
        }
        if (kind === 'appointment') {
            Array.from(form.elements.provider_id.options).forEach(option => {
                option.disabled = Boolean(option.value) && option.value !== String(record.provider_id ?? '') && !option.dataset.services?.split(',').includes(form.elements.service_id.value);
            });
        }
        form.querySelector('.mw-error').hidden = true;
        dialog.showModal();
    });
    root.querySelectorAll('[data-maint-dialog]').forEach(dialog => {
        const form = dialog.querySelector('form');
        dialog.querySelectorAll('[data-maint-close]').forEach(button => button.addEventListener('click', () => close(dialog)));
        dialog.addEventListener('cancel', event => { event.preventDefault(); close(dialog); });
        form.elements.service_id?.addEventListener('change', () => {
            const option = form.elements.service_id.selectedOptions[0];
            form.elements.frequency.value = option?.dataset.frequency || '';
            if (option?.dataset.laboratory) form.elements.laboratory_id.value = option.dataset.laboratory;
            form.elements.provider_id.value = '';
            Array.from(form.elements.provider_id.options).forEach(option => {
                option.disabled = Boolean(option.value) && !option.dataset.services?.split(',').includes(form.elements.service_id.value);
            });
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (dialog.dataset.busy === 'true' || !form.reportValidity()) return;
            const kind = form.dataset.maintForm;
            const data = new FormData(form);
            const payload = Object.fromEntries(data);
            if (kind === 'service') payload.provider_ids = data.getAll('provider_ids[]');
            if (kind === 'provider') payload.service_ids = data.getAll('service_ids[]');
            const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            if (kind !== 'document') headers['Content-Type'] = 'application/json';
            dialog.dataset.busy = 'true';
            const controls = Array.from(form.elements);
            const previous = controls.map(control => control.disabled);
            controls.forEach(control => { control.disabled = true; });
            const error = form.querySelector('.mw-error');
            error.hidden = true;
            try {
                const response = await fetch(form.dataset.url, { method: form.dataset.method, headers, body: kind === 'document' ? data : JSON.stringify(payload) });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'No fue posible guardar. Intenta de nuevo.');
                const url = new URL(window.location.href);
                if (result.id) url.searchParams.set('seleccion', result.id);
                if (kind === 'appointment') {
                    if (url.searchParams.get('seccion') !== 'calendario' && url.searchParams.has('seccion')) url.searchParams.set('seccion', 'programacion');
                    url.searchParams.set('fecha', result.date);
                    ['q', 'estado', 'tipo'].forEach(key => url.searchParams.delete(key));
                }
                window.location.assign(url.toString());
            } catch (failure) {
                error.textContent = failure.message;
                error.hidden = false;
                error.scrollIntoView({ block: 'nearest' });
            } finally {
                dialog.dataset.busy = 'false';
                controls.forEach((control, index) => { control.disabled = previous[index]; });
            }
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeMaintenanceWorkspace);
else initializeMaintenanceWorkspace();
document.addEventListener('livewire:navigated', initializeMaintenanceWorkspace);
