import { createIcons, X } from 'lucide';

function initializePersonnelEditor() {
    const modal = document.querySelector('[data-personnel-edit-modal]');
    if (!modal || modal.dataset.ready) return;
    modal.dataset.ready = 'true';
    createIcons({ icons: { X }, nameAttr: 'data-personnel-icon', root: modal });

    const form = modal.querySelector('form');
    const fields = modal.querySelector('[data-personnel-edit-fields]');
    const loading = modal.querySelector('[data-personnel-edit-loading]');
    const errors = modal.querySelector('[data-personnel-edit-errors]');
    const save = modal.querySelector('[data-personnel-edit-save]');
    const legacyJobs = modal.querySelector('[data-personnel-edit-legacy-jobs]');
    let loadController;
    let saving = false;

    function clearErrors() {
        errors.hidden = true;
        errors.replaceChildren();
        form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
    }

    function showErrors(messages, fieldErrors = {}) {
        clearErrors();
        const list = document.createElement('ul');
        messages.forEach(message => {
            const item = document.createElement('li');
            item.textContent = message;
            list.append(item);
        });
        errors.append(list);
        errors.hidden = false;
        Object.keys(fieldErrors).forEach(name => {
            const input = form.elements.namedItem(name);
            if (input instanceof HTMLElement) input.setAttribute('aria-invalid', 'true');
        });
        errors.focus();
    }

    function close() {
        if (saving) return;
        loadController?.abort();
        modal.close();
    }

    modal.querySelectorAll('[data-close-personnel-edit]').forEach(button => button.addEventListener('click', close));
    modal.addEventListener('cancel', event => {
        if (saving) event.preventDefault();
        else loadController?.abort();
    });

    document.querySelectorAll('[data-personnel-edit-url]').forEach(button => {
        button.addEventListener('click', async () => {
            loadController?.abort();
            const controller = new AbortController();
            loadController = controller;
            form.reset();
            form.removeAttribute('action');
            form.querySelectorAll('[data-personnel-current-option]').forEach(option => option.remove());
            legacyJobs.replaceChildren();
            legacyJobs.hidden = true;
            modal.querySelector('[data-personnel-edit-name]').textContent = '';
            clearErrors();
            fields.hidden = true;
            fields.disabled = true;
            save.disabled = true;
            loading.hidden = false;
            modal.showModal();

            try {
                const response = await fetch(button.dataset.personnelEditUrl, {
                    headers: { Accept: 'application/json' }, signal: controller.signal,
                });
                if (!response.ok) throw new Error('No se pudo cargar la informacion del personal. Cierra la ventana e intenta de nuevo.');
                const data = await response.json();
                if (controller.signal.aborted) return;
                form.action = data.action;
                modal.querySelector('[data-personnel-edit-name]').textContent = data.name;
                modal.querySelector('[data-personnel-edit-cv]').textContent = data.cv_name ? `CV actual: ${data.cv_name}` : 'Sin CV adjunto';

                const laboratory = form.elements.namedItem('laboratory_id');
                if (data.laboratory && !Array.from(laboratory.options).some(option => option.value === String(data.laboratory.id))) {
                    const option = new Option(data.laboratory.name, data.laboratory.id);
                    option.dataset.personnelCurrentOption = 'true';
                    laboratory.add(option);
                }

                Object.entries(data.fields).forEach(([name, value]) => {
                    if (name !== 'positions') form.elements.namedItem(name).value = value ?? '';
                });
                const checkboxes = Array.from(form.querySelectorAll('[name="positions[]"]'));
                (data.fields.positions || []).forEach(position => {
                    let checkbox = checkboxes.find(input => input.value === position);
                    if (!checkbox) {
                        const label = document.createElement('label');
                        checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'positions[]';
                        checkbox.value = position;
                        const text = document.createElement('span');
                        text.textContent = position;
                        label.append(checkbox, text);
                        legacyJobs.append(label);
                        legacyJobs.hidden = false;
                    }
                    checkbox.checked = true;
                });
                fields.hidden = false;
                fields.disabled = false;
                save.disabled = false;
                form.elements.namedItem('first_name').focus();
            } catch (error) {
                if (error.name !== 'AbortError') showErrors([error.message]);
            } finally {
                if (!controller.signal.aborted) loading.hidden = true;
            }
        });
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving || save.disabled || !form.reportValidity()) return;
        if (!form.querySelector('[name="positions[]"]:checked')) {
            showErrors(['Selecciona al menos un puesto.']);
            return;
        }
        clearErrors();
        const body = new FormData(form);
        saving = true;
        fields.disabled = true;
        save.disabled = true;
        save.textContent = 'Guardando...';
        modal.querySelectorAll('[data-close-personnel-edit]').forEach(button => button.disabled = true);
        let saved = false;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body, headers: { Accept: 'application/json' },
            });
            if (response.status === 422) {
                const data = await response.json();
                showErrors(Object.values(data.errors).flat(), data.errors);
                return;
            }
            if (!response.ok) throw new Error('No se pudieron guardar los cambios. Revisa tu conexion y vuelve a intentar.');
            const data = await response.json();
            saved = true;
            document.dispatchEvent(new CustomEvent('personnel-general-updated', { detail: data }));
            window.location.reload();
        } catch (error) {
            showErrors([error.message]);
        } finally {
            if (!saved) {
                saving = false;
                fields.disabled = false;
                save.disabled = false;
                save.textContent = 'Guardar cambios';
                modal.querySelectorAll('[data-close-personnel-edit]').forEach(button => button.disabled = false);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', initializePersonnelEditor);
document.addEventListener('livewire:navigated', initializePersonnelEditor);
