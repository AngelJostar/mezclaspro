import { createIcons, Syringe, X, Check, RotateCw } from 'lucide';
import '../css/diluent-modal.css';

function initializeDiluentModal() {
    const modal = document.querySelector('[data-diluent-modal]');
    if (!modal || modal.dataset.ready) return;
    modal.dataset.ready = 'true';
    const host = modal.querySelector('[data-diluent-form-host]');
    const loading = modal.querySelector('[data-diluent-loading]');
    const loadError = modal.querySelector('[data-diluent-load-error]');
    const icons = () => createIcons({ icons: { Syringe, X, Check, RotateCw }, nameAttr: 'data-diluent-icon', root: modal });
    let controller;
    let trigger;
    let activeUrl;
    let saving = false;
    icons();

    const close = () => { if (!saving) modal.close(); };
    modal.addEventListener('click', event => { if (event.target.closest('[data-close-diluent]')) close(); });
    modal.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    modal.addEventListener('close', () => {
        controller?.abort();
        host.replaceChildren();
        modal.removeAttribute('aria-busy');
        document.documentElement.classList.remove('diluent-modal-open');
        trigger?.focus({ preventScroll: true });
    });

    function configureForm(form) {
        const locations = JSON.parse(form.querySelector('[data-diluent-locations]').textContent);
        const laboratory = form.elements.namedItem('laboratory_id');
        const warehouse = form.elements.namedItem('warehouse_id');
        const save = form.querySelector('[data-save-diluent]');
        const fields = form.querySelector('[data-diluent-fields]');
        const errors = form.querySelector('[data-diluent-errors]');
        function clearErrors() {
            errors.hidden = true;
            errors.replaceChildren();
            form.querySelectorAll('[data-field-error]').forEach(node => { node.hidden = true; node.textContent = ''; });
            form.querySelectorAll('[aria-invalid]').forEach(node => node.removeAttribute('aria-invalid'));
        }
        function showErrors(message, fieldErrors = {}) {
            errors.textContent = message;
            errors.hidden = false;
            for (const [name, messages] of Object.entries(fieldErrors)) {
                form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true');
                const node = [...form.querySelectorAll('[data-field-error]')].find(item => item.dataset.fieldError === name);
                if (node) { node.textContent = messages.join(' '); node.hidden = false; }
            }
            errors.focus();
        }
        laboratory.addEventListener('change', () => {
            const available = locations[laboratory.value] || [];
            warehouse.replaceChildren(new Option('Selecciona un subalmac\u00e9n', ''));
            available.forEach(item => warehouse.add(new Option(item.name, item.id)));
            if (available.length) warehouse.value = String(available[0].id);
            warehouse.disabled = available.length === 0;
            save.disabled = available.length === 0;
            form.querySelector('[data-diluent-no-warehouse]').hidden = available.length > 0;
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving || save.disabled || !form.reportValidity()) return;
            clearErrors();
            const body = new FormData(form);
            saving = true;
            fields.disabled = true;
            save.disabled = true;
            modal.setAttribute('aria-busy', 'true');
            modal.querySelectorAll('[data-close-diluent]').forEach(button => { button.disabled = true; });
            save.querySelector('span').textContent = 'Guardando...';
            let saved = false;
            try {
                const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (response.status === 422) {
                    const data = await response.json();
                    showErrors('Revisa los campos indicados.', data.errors);
                    return;
                }
                if (response.status === 419 || response.status === 401 || response.redirected) {
                    throw new Error('Tu sesi\u00f3n ha caducado. Recarga la p\u00e1gina para continuar.');
                }
                if (!response.ok) throw new Error('No se pudo confirmar el guardado. Revisa el cat\u00e1logo antes de intentar de nuevo.');
                const data = await response.json();
                const destination = new URL(data.redirect, window.location.href);
                if (destination.origin !== window.location.origin) throw new Error('No se pudo abrir el cat\u00e1logo.');
                saved = true;
                window.location.assign(destination.href);
            } catch (error) {
                showErrors(error.message);
            } finally {
                if (!saved) {
                    saving = false;
                    fields.disabled = false;
                    save.disabled = !warehouse.value;
                    modal.removeAttribute('aria-busy');
                    modal.querySelectorAll('[data-close-diluent]').forEach(button => { button.disabled = false; });
                    save.querySelector('span').textContent = 'Guardar diluyente';
                }
            }
        });
    }

    async function loadForm() {
        controller?.abort();
        const request = new AbortController();
        controller = request;
        host.replaceChildren();
        loading.hidden = false;
        loadError.hidden = true;
        modal.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(activeUrl, { headers: { Accept: 'text/html' }, signal: request.signal });
            if (!response.ok || response.redirected) throw new Error('load');
            const document = new DOMParser().parseFromString(await response.text(), 'text/html');
            const form = document.querySelector('[data-new-diluent-form]');
            if (!form || request.signal.aborted) throw new Error('load');
            host.replaceChildren(form);
            configureForm(form);
            icons();
            form.elements.namedItem('generic_description').focus();
        } catch (error) {
            if (!request.signal.aborted) loadError.hidden = false;
        } finally {
            if (!request.signal.aborted) { loading.hidden = true; modal.removeAttribute('aria-busy'); }
        }
    }
    modal.querySelector('[data-diluent-retry]').addEventListener('click', loadForm);
    const open = (link, options = {}) => {
        if (modal.open) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        url.searchParams.set('modal', '1');
        for (const [key, value] of Object.entries(options)) if (value) url.searchParams.set(key, value);
        trigger = link;
        activeUrl = url.href;
        modal.showModal();
        document.documentElement.classList.add('diluent-modal-open');
        loadForm();
    };
    const onClick = event => {
        const link = event.target.closest('a[data-new-diluent="true"]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        open(link);
    };
    document.addEventListener('click', onClick);
    const current = new URL(window.location.href);
    const link = document.querySelector('a[data-new-diluent="true"]');
    if (current.searchParams.get('nuevo_diluyente') === '1' && link) {
        open(link, { laboratory_id: current.searchParams.get('laboratory_id'), warehouse_id: current.searchParams.get('warehouse_id') });
        current.searchParams.delete('nuevo_diluyente');
        window.history.replaceState(window.history.state, '', current);
    }
    document.addEventListener('livewire:navigating', () => {
        controller?.abort();
        if (modal.open) modal.close();
        document.removeEventListener('click', onClick);
    }, { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeDiluentModal);
else initializeDiluentModal();
document.addEventListener('livewire:navigated', initializeDiluentModal);
