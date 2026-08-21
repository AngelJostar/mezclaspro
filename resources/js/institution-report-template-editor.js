const dataElement = document.getElementById('institution-report-templates-data');
const modal = document.getElementById('report-template-modal');

if (dataElement && modal) {
    const templates = JSON.parse(dataElement.textContent || '{}');
    const updateUrlTemplate = modal.dataset.updateUrl;
    const renameUrlTemplate = modal.dataset.renameUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const actions = document.getElementById('report-format-actions');
    const selectedName = document.getElementById('report-format-selected-name');
    const modalBody = document.getElementById('report-template-modal-body');
    const modalFooter = document.getElementById('report-template-modal-footer');
    const modalTitle = document.getElementById('report-template-modal-title');
    const modalKicker = document.getElementById('report-template-modal-kicker');
    const saveButton = document.getElementById('report-template-save');
    const saveError = document.getElementById('report-template-save-error');
    let selectedKey = Object.keys(templates)[0] ?? null;
    let mode = 'preview';
    let working = null;
    let activeNameEditor = null;

    const clone = (value) => JSON.parse(JSON.stringify(value));
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const resolvePreviewText = (value) => String(value ?? '')
        .replaceAll('{{institucion}}', 'Institución seleccionada')
        .replaceAll('{{hospital}}', 'Hospital seleccionado')
        .replaceAll('{{fecha_generacion}}', new Intl.DateTimeFormat('es-MX').format(new Date()))
        .replaceAll('{{periodo}}', 'Agosto 2026')
        .replaceAll('{{hoja}}', 'Nutrición parenteral');

    function reportNameEditor(key) {
        return document.querySelector(`[data-report-template-name-editor="${key}"]`);
    }

    function renderReportName(key) {
        const editor = reportNameEditor(key);
        const template = templates[key];

        if (!editor || !template) return;

        editor.className = 'flex items-center gap-1 normal-case';
        editor.replaceChildren();

        const label = document.createElement('span');
        label.dataset.reportTemplateNameText = '';
        label.className = 'min-w-0 flex-1 uppercase leading-tight';
        label.textContent = template.name;

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.reportTemplateRename = key;
        button.className = 'inline-flex size-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-blue-700 hover:border-blue-400 hover:bg-blue-50';
        button.title = 'Editar nombre del reporte';
        button.setAttribute('aria-label', `Editar nombre de ${template.name}`);
        button.innerHTML = '<i class="fa-solid fa-pen text-[10px]"></i>';

        editor.append(label, button);
    }

    function startReportNameEdit(key) {
        if (!templates[key]) return;

        if (activeNameEditor && activeNameEditor !== key) {
            renderReportName(activeNameEditor);
        }

        const editor = reportNameEditor(key);
        if (!editor) return;

        activeNameEditor = key;
        editor.className = 'flex min-w-0 items-center gap-1 normal-case';
        editor.replaceChildren();

        const input = document.createElement('input');
        input.type = 'text';
        input.value = templates[key].name;
        input.maxLength = 120;
        input.dataset.reportTemplateNameInput = '';
        input.className = 'h-8 w-0 min-w-0 flex-1 rounded border-slate-300 px-1.5 text-xs font-medium normal-case text-slate-800 focus:border-blue-500 focus:ring-blue-500';

        const save = document.createElement('button');
        save.type = 'button';
        save.dataset.reportTemplateNameSave = key;
        save.className = 'inline-flex size-6 shrink-0 items-center justify-center rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60';
        save.title = 'Guardar nombre';
        save.setAttribute('aria-label', 'Guardar nombre');
        save.innerHTML = '<i class="fa-solid fa-check text-[11px]"></i>';

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.dataset.reportTemplateNameCancel = key;
        cancel.className = 'inline-flex size-6 shrink-0 items-center justify-center rounded bg-red-600 text-white hover:bg-red-700';
        cancel.title = 'Cancelar';
        cancel.setAttribute('aria-label', 'Cancelar edición');
        cancel.innerHTML = '<i class="fa-solid fa-xmark text-[12px]"></i>';

        editor.append(input, save, cancel);
        input.focus();
        input.select();
    }

    async function saveReportName(key) {
        const editor = reportNameEditor(key);
        const input = editor?.querySelector('[data-report-template-name-input]');
        const save = editor?.querySelector('[data-report-template-name-save]');
        const name = input?.value.trim();

        if (!editor || !input || !save) return;

        if (!name) {
            input.setCustomValidity('Escribe el nombre del reporte.');
            input.reportValidity();
            return;
        }

        input.setCustomValidity('');
        input.disabled = true;
        save.disabled = true;

        try {
            const response = await fetch(renameUrlTemplate.replace('__REPORT__', key), {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ name }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'No fue posible guardar el nombre.');

            templates[key] = payload.template;
            activeNameEditor = null;
            renderReportName(key);

            if (selectedKey === key) {
                selectedName.textContent = payload.template.name;
                if (!modal.classList.contains('hidden')) {
                    modalTitle.textContent = payload.template.name;
                }
                if (working) working.name = payload.template.name;
            }

            window.Swal?.fire({
                icon: 'success',
                title: 'Nombre guardado',
                text: payload.message,
                confirmButtonText: 'Aceptar',
            });
        } catch (error) {
            input.disabled = false;
            save.disabled = false;
            window.Swal?.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: error.message,
                confirmButtonText: 'Aceptar',
            });
        }
    }

    function chooseTemplate(key) {
        if (!templates[key]) return;

        selectedKey = key;
        selectedName.textContent = templates[key].name;
        actions.classList.remove('hidden');
        actions.classList.add('flex');
        document.querySelectorAll('[data-report-template-select]').forEach((button) => {
            const selected = button.dataset.reportTemplateSelect === key;
            button.classList.toggle('border-blue-500', selected);
            button.classList.toggle('bg-blue-50', selected);
        });
    }

    function openModal(nextMode) {
        if (!selectedKey || !templates[selectedKey]) return;

        mode = nextMode;
        working = clone(templates[selectedKey]);
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        renderModal();
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        working = null;
        saveError.textContent = '';
    }

    function renderModal() {
        const template = mode === 'edit' ? working : templates[selectedKey];
        modalKicker.textContent = mode === 'edit' ? 'Edición de plantilla' : 'Vista previa de plantilla';
        modalTitle.textContent = template.name;
        modalFooter.classList.toggle('hidden', mode !== 'edit');
        modalFooter.classList.toggle('flex', mode === 'edit');
        document.querySelectorAll('.report-template-mode').forEach((button) => {
            const active = button.dataset.reportTemplateMode === mode;
            button.classList.toggle('bg-blue-900', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-slate-600', !active);
            button.classList.toggle('hover:bg-slate-200', !active);
        });
        saveError.textContent = '';
        modalBody.innerHTML = mode === 'edit' ? editorHtml(template) : previewHtml(template);
    }

    function previewHtml(template) {
        const columns = template.columns.filter((column) => column.visible);
        const infoFields = [...template.info_boxes, ...template.free_fields];

        return `
            <div class="border border-slate-300 bg-white">
                <div class="border-b border-slate-200 px-5 py-5">
                    <h3 class="text-2xl font-semibold text-slate-900">${escapeHtml(resolvePreviewText(template.title))}</h3>
                    ${template.subtitle ? `<p class="mt-1 text-sm text-slate-500">${escapeHtml(resolvePreviewText(template.subtitle))}</p>` : ''}
                </div>
                ${infoFields.length ? `
                    <div class="grid gap-px border-b border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
                        ${infoFields.map((field) => `
                            <div class="bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase text-slate-500">${escapeHtml(resolvePreviewText(field.label))}</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-800">${escapeHtml(resolvePreviewText(field.value) || 'Campo libre')}</p>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full border-collapse text-xs">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>${columns.map((column) => `<th class="whitespace-nowrap border border-slate-300 px-3 py-2 text-left font-semibold">${escapeHtml(column.label)}</th>`).join('')}</tr>
                        </thead>
                        <tbody>
                            <tr>${columns.map((column) => `<td class="whitespace-nowrap border border-slate-200 px-3 py-2 text-slate-600">${escapeHtml(column.sample)}</td>`).join('')}</tr>
                        </tbody>
                    </table>
                    ${template.dynamic_columns_note ? `<p class="mt-3 text-xs text-slate-500"><i class="fa-solid fa-circle-info mr-1 text-blue-600"></i>${escapeHtml(template.dynamic_columns_note)}</p>` : ''}
                </div>
            </div>`;
    }

    function editorHtml(template) {
        return `
            <div class="space-y-6">
                <section>
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Encabezado del reporte</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm text-slate-700">Título
                            <input data-template-property="title" value="${escapeHtml(template.title)}" maxlength="120"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="text-sm text-slate-700">Texto introductorio
                            <textarea data-template-property="subtitle" rows="2" maxlength="500"
                                class="mt-1 w-full resize-y rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">${escapeHtml(template.subtitle)}</textarea>
                        </label>
                    </div>
                </section>

                ${fieldsEditorHtml('Recuadros de información', 'info_boxes', template.info_boxes, 'Agregar recuadro')}

                <section class="border-t border-slate-200 pt-5">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-slate-900">Columnas</h3>
                        <p class="text-xs text-slate-500">Activa, renombra y ordena las columnas que aparecerán en el formato.</p>
                    </div>
                    <div class="divide-y divide-slate-200 border-y border-slate-200">
                        ${template.columns.map((column, index) => `
                            <div class="grid items-center gap-3 py-2 sm:grid-cols-[44px_minmax(180px,1fr)_96px]">
                                <label class="flex justify-center" title="Mostrar columna">
                                    <input type="checkbox" data-column-visible="${index}" ${column.visible ? 'checked' : ''}
                                        class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                </label>
                                <input data-column-label="${index}" value="${escapeHtml(column.label)}" maxlength="80"
                                    class="w-full rounded-md border-slate-300 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <div class="flex justify-end gap-1">
                                    <button type="button" data-column-move="up" data-column-index="${index}" ${index === 0 ? 'disabled' : ''}
                                        class="inline-flex size-9 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-30" title="Subir columna">
                                        <i class="fa-solid fa-arrow-up"></i>
                                    </button>
                                    <button type="button" data-column-move="down" data-column-index="${index}" ${index === template.columns.length - 1 ? 'disabled' : ''}
                                        class="inline-flex size-9 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-30" title="Bajar columna">
                                        <i class="fa-solid fa-arrow-down"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ${template.dynamic_columns_note ? `<p class="mt-2 text-xs text-slate-500">${escapeHtml(template.dynamic_columns_note)}</p>` : ''}
                </section>

                ${fieldsEditorHtml('Campos libres', 'free_fields', template.free_fields, 'Agregar campo libre')}
            </div>`;
    }

    function fieldsEditorHtml(title, group, fields, addLabel) {
        return `
            <section class="border-t border-slate-200 pt-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">${title}</h3>
                        <p class="text-xs text-slate-500">Puedes usar: {{institucion}}, {{hospital}}, {{fecha_generacion}}, {{periodo}} y {{hoja}}.</p>
                    </div>
                    <button type="button" data-field-add="${group}"
                        class="inline-flex items-center gap-2 rounded-md border border-blue-200 px-3 py-2 text-sm font-medium text-blue-800 hover:bg-blue-50">
                        <i class="fa-solid fa-plus"></i>${addLabel}
                    </button>
                </div>
                <div class="space-y-2">
                    ${fields.length ? fields.map((field, index) => `
                        <div class="grid gap-2 sm:grid-cols-[minmax(150px,0.7fr)_minmax(220px,1.3fr)_40px]">
                            <input data-field-group="${group}" data-field-index="${index}" data-field-property="label"
                                value="${escapeHtml(field.label)}" placeholder="Nombre del campo" maxlength="80"
                                class="rounded-md border-slate-300 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <textarea data-field-group="${group}" data-field-index="${index}" data-field-property="value"
                                rows="1" placeholder="Contenido" maxlength="${group === 'free_fields' ? '500' : '250'}"
                                class="resize-y rounded-md border-slate-300 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500">${escapeHtml(field.value)}</textarea>
                            <button type="button" data-field-remove="${group}" data-field-index="${index}"
                                class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Eliminar campo">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `).join('') : '<p class="py-2 text-sm text-slate-400">No hay campos agregados.</p>'}
                </div>
            </section>`;
    }

    async function saveTemplate() {
        saveError.textContent = '';
        saveButton.disabled = true;
        const originalText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando';

        try {
            const response = await fetch(updateUrlTemplate.replace('__REPORT__', selectedKey), {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(working),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'No fue posible guardar el formato.');

            templates[selectedKey] = payload.template;
            working = clone(payload.template);
            mode = 'preview';
            renderModal();
            window.Swal?.fire({
                icon: 'success',
                title: 'Formato guardado',
                text: payload.message,
                confirmButtonText: 'Aceptar',
            });
        } catch (error) {
            saveError.textContent = error.message;
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
        }
    }

    document.querySelectorAll('[data-report-template-select]').forEach((button) => {
        button.addEventListener('click', () => chooseTemplate(button.dataset.reportTemplateSelect));
    });
    document.getElementById('report-template-preview').addEventListener('click', () => openModal('preview'));
    document.getElementById('report-template-edit').addEventListener('click', () => openModal('edit'));
    document.getElementById('report-format-actions-close').addEventListener('click', () => {
        actions.classList.add('hidden');
        actions.classList.remove('flex');
    });
    document.querySelectorAll('[data-report-template-close]').forEach((button) => button.addEventListener('click', closeModal));
    document.querySelectorAll('[data-report-template-mode]').forEach((button) => button.addEventListener('click', () => {
        mode = button.dataset.reportTemplateMode;
        working = clone(templates[selectedKey]);
        renderModal();
    }));
    saveButton.addEventListener('click', saveTemplate);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('click', (event) => {
        const rename = event.target.closest?.('[data-report-template-rename]');
        if (rename) {
            startReportNameEdit(rename.dataset.reportTemplateRename);
            return;
        }

        const save = event.target.closest?.('[data-report-template-name-save]');
        if (save) {
            saveReportName(save.dataset.reportTemplateNameSave);
            return;
        }

        const cancel = event.target.closest?.('[data-report-template-name-cancel]');
        if (cancel) {
            activeNameEditor = null;
            renderReportName(cancel.dataset.reportTemplateNameCancel);
        }
    });
    document.addEventListener('keydown', (event) => {
        const input = event.target.closest?.('[data-report-template-name-input]');

        if (input) {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveReportName(activeNameEditor);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                const key = activeNameEditor;
                activeNameEditor = null;
                if (key) renderReportName(key);
            }

            return;
        }

        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    modalBody.addEventListener('input', (event) => {
        if (event.target.dataset.templateProperty) {
            working[event.target.dataset.templateProperty] = event.target.value;
        }
        if (event.target.dataset.columnLabel !== undefined) {
            working.columns[Number(event.target.dataset.columnLabel)].label = event.target.value;
        }
        if (event.target.dataset.fieldGroup) {
            const group = event.target.dataset.fieldGroup;
            const index = Number(event.target.dataset.fieldIndex);
            working[group][index][event.target.dataset.fieldProperty] = event.target.value;
        }
    });
    modalBody.addEventListener('change', (event) => {
        if (event.target.dataset.columnVisible !== undefined) {
            working.columns[Number(event.target.dataset.columnVisible)].visible = event.target.checked;
        }
    });
    modalBody.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        if (button.dataset.columnMove) {
            const index = Number(button.dataset.columnIndex);
            const target = button.dataset.columnMove === 'up' ? index - 1 : index + 1;
            [working.columns[index], working.columns[target]] = [working.columns[target], working.columns[index]];
            renderModal();
        }
        if (button.dataset.fieldAdd) {
            working[button.dataset.fieldAdd].push({ label: '', value: '' });
            renderModal();
        }
        if (button.dataset.fieldRemove) {
            working[button.dataset.fieldRemove].splice(Number(button.dataset.fieldIndex), 1);
            renderModal();
        }
    });
}
