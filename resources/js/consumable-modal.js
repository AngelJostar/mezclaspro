import { createIcons, Box, X, Check, Plus, Trash2, Info, RotateCw } from 'lucide';
import '../css/consumable-modal.css';

function initializeConsumableModal() {
    const modal = document.querySelector('[data-consumable-modal]');
    if (!modal || modal.dataset.ready) return;
    modal.dataset.ready = 'true';
    const host = modal.querySelector('[data-consumable-form-host]');
    const loading = modal.querySelector('[data-consumable-loading]');
    const loadError = modal.querySelector('[data-consumable-load-error]');
    const icons = () => createIcons({ icons: { Box, X, Check, Plus, Trash2, Info, RotateCw }, nameAttr: 'data-consumable-icon', root: modal });
    let controller;
    let trigger;
    let activeUrl;
    let saving = false;
    icons();

    modal.addEventListener('click', event => {
        if (!saving && event.target.closest('[data-close-consumable]')) modal.close();
    });
    modal.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    modal.addEventListener('close', () => {
        controller?.abort();
        host.replaceChildren();
        modal.removeAttribute('aria-busy');
        document.documentElement.classList.remove('diluent-modal-open');
        trigger?.focus({ preventScroll: true });
    });

    function configureForm(form) {
        const list = form.querySelector('[data-consumable-presentations]');
        const template = form.querySelector('[data-consumable-presentation-template]');
        const save = form.querySelector('[data-save-consumable]');
        const fields = form.querySelector('[data-consumable-fields]');
        const errors = form.querySelector('[data-consumable-errors]');
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
                const node = [...form.querySelectorAll('[data-field-error]')].find(item => item.dataset.fieldError === name);
                if (node) {
                    node.textContent = messages.join(' ');
                    node.hidden = false;
                    node.closest('.diluent-field')?.querySelector('input')?.setAttribute('aria-invalid', 'true');
                }
            }
            errors.focus();
        }
        function renumber() {
            [...list.children].forEach((row, index) => {
                row.querySelectorAll('[data-presentation-field]').forEach(input => {
                    const key = input.dataset.presentationField;
                    const id = `consumable-presentation-${index}-${key}`;
                    input.name = `presentations[${index}][${key}]`;
                    input.id = id;
                    input.setAttribute('aria-describedby', `${id}-error`);
                    input.previousElementSibling.htmlFor = id;
                    const error = input.nextElementSibling;
                    error.id = `${id}-error`;
                    error.dataset.fieldError = `presentations.${index}.${key}`;
                });
                const remove = row.querySelector('[data-remove-presentation]');
                remove.disabled = list.children.length === 1;
                remove.setAttribute('aria-label', `Eliminar presentaci\u00f3n ${index + 1}`);
                remove.title = remove.disabled ? 'Se requiere al menos una presentaci\u00f3n' : `Eliminar presentaci\u00f3n ${index + 1}`;
            });
        }
        renumber();
        form.querySelector('[data-add-presentation]').addEventListener('click', () => {
            if (saving) return;
            list.append(template.content.cloneNode(true));
            clearErrors();
            renumber();
            icons();
            list.lastElementChild.querySelector('input').focus();
        });
        list.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-presentation]');
            if (!remove || saving || list.children.length === 1) return;
            const row = remove.closest('[data-consumable-presentation]');
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            clearErrors();
            renumber();
            next.querySelector('input').focus();
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving || !form.reportValidity()) return;
            clearErrors();
            const body = new FormData(form);
            saving = true;
            fields.disabled = true;
            save.disabled = true;
            modal.setAttribute('aria-busy', 'true');
            modal.querySelectorAll('[data-close-consumable]').forEach(button => { button.disabled = true; });
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
                    save.disabled = false;
                    modal.removeAttribute('aria-busy');
                    modal.querySelectorAll('[data-close-consumable]').forEach(button => { button.disabled = false; });
                    save.querySelector('span').textContent = 'Guardar consumible';
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
            const form = document.querySelector('[data-new-consumable-form]');
            if (!form || request.signal.aborted) throw new Error('load');
            host.replaceChildren(form);
            configureForm(form);
            icons();
            form.elements.namedItem('name').focus();
        } catch (error) {
            if (!request.signal.aborted) loadError.hidden = false;
        } finally {
            if (!request.signal.aborted) { loading.hidden = true; modal.removeAttribute('aria-busy'); }
        }
    }
    modal.querySelector('[data-consumable-retry]').addEventListener('click', loadForm);
    const open = link => {
        if (modal.open) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        url.searchParams.set('modal', '1');
        trigger = link;
        activeUrl = url.href;
        modal.showModal();
        document.documentElement.classList.add('diluent-modal-open');
        loadForm();
    };
    const onClick = event => {
        const link = event.target.closest('a[data-new-consumable="true"]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        open(link);
    };
    document.addEventListener('click', onClick);
    const current = new URL(window.location.href);
    const link = document.querySelector('a[data-new-consumable="true"]');
    if (current.searchParams.get('nuevo_consumible') === '1' && link) {
        open(link);
        current.searchParams.delete('nuevo_consumible');
        window.history.replaceState(window.history.state, '', current);
    }
    document.addEventListener('livewire:navigating', () => {
        controller?.abort();
        if (modal.open) modal.close();
        document.removeEventListener('click', onClick);
    }, { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeConsumableModal);
else initializeConsumableModal();
document.addEventListener('livewire:navigated', initializeConsumableModal);
