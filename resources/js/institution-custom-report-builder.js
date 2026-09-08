const builderModal = document.getElementById('custom-report-builder-modal');
const builderCreateButton = document.getElementById('custom-report-create');
const builderTemplatesData = document.getElementById('custom-report-templates-data');
const builderParametersData = document.getElementById('custom-report-parameters-data');
const builderSourcesData = document.getElementById('custom-report-sources-data');

if (builderModal && builderCreateButton && builderTemplatesData && builderParametersData && builderSourcesData) {
    let savedTemplates = JSON.parse(builderTemplatesData.textContent || '[]');
    const parameterGroups = JSON.parse(builderParametersData.textContent || '[]');
    const dataSources = JSON.parse(builderSourcesData.textContent || '{}');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const storeUrl = builderModal.dataset.storeUrl;
    const updateUrlTemplate = builderModal.dataset.updateUrl;
    const publishUrlTemplate = builderModal.dataset.publishUrl;
    const deleteUrlTemplate = builderModal.dataset.deleteUrl;
    const gridElement = document.getElementById('custom-report-grid');
    const library = document.getElementById('custom-report-library');
    const templateList = document.getElementById('custom-report-template-list');
    const templateCount = document.getElementById('custom-report-count');
    const modalTitle = document.getElementById('custom-report-builder-title');
    const reportName = document.getElementById('custom-report-name');
    const reportSource = document.getElementById('custom-report-source');
    const reportDescription = document.getElementById('custom-report-description');
    const selectedCellLabel = document.getElementById('custom-selected-cell');
    const cellValue = document.getElementById('custom-cell-value');
    const cellValueWrap = document.getElementById('custom-cell-value-wrap');
    const cellParameter = document.getElementById('custom-cell-parameter');
    const cellParameterWrap = document.getElementById('custom-cell-parameter-wrap');
    const cellRepeatWrap = document.getElementById('custom-cell-repeat-wrap');
    const fontFamily = document.getElementById('custom-cell-font');
    const fontSize = document.getElementById('custom-cell-font-size');
    const textColor = document.getElementById('custom-cell-color');
    const backgroundColor = document.getElementById('custom-cell-background');
    const gridSize = document.getElementById('custom-report-grid-size');
    const saveError = document.getElementById('custom-report-save-error');
    const saveButton = document.getElementById('custom-report-save');
    const publishButton = document.getElementById('custom-report-publish');
    const parameterMap = new Map(
        parameterGroups.flatMap((group) => group.parameters.map((parameter) => [parameter.key, parameter]))
    );
    let state = null;
    let selected = { row: 0, column: 0 };

    const clone = (value) => JSON.parse(JSON.stringify(value));
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    function defaultStyle(overrides = {}) {
        return {
            background: '#FFFFFF',
            color: '#1F2937',
            font_family: 'Arial',
            font_size: 11,
            bold: false,
            italic: false,
            underline: false,
            align: 'left',
            ...overrides,
        };
    }

    function emptyCell(row, column) {
        return {
            row,
            column,
            type: 'text',
            value: '',
            parameter: null,
            repeat_direction: 'none',
            style: defaultStyle(),
        };
    }

    function cellKey(row, column) {
        return `${row}:${column}`;
    }

    function columnName(index) {
        let value = index + 1;
        let name = '';

        while (value > 0) {
            const remainder = (value - 1) % 26;
            name = String.fromCharCode(65 + remainder) + name;
            value = Math.floor((value - 1) / 26);
        }

        return name;
    }

    function selectedCoordinate() {
        return `${columnName(selected.column)}${selected.row + 1}`;
    }

    function ensureGridCells() {
        for (let row = 0; row < state.layout.rows; row += 1) {
            for (let column = 0; column < state.layout.columns; column += 1) {
                const key = cellKey(row, column);
                if (!state.cells[key]) state.cells[key] = emptyCell(row, column);
            }
        }
    }

    function setProposalCell(row, column, type, value, parameter, style = {}, repeatDirection = 'none') {
        state.cells[cellKey(row, column)] = {
            row,
            column,
            type,
            value,
            parameter,
            repeat_direction: type === 'parameter' ? repeatDirection : 'none',
            style: defaultStyle(style),
        };
    }

    function newTemplateState() {
        state = {
            id: null,
            is_published: false,
            name: '',
            description: '',
            data_source: Object.keys(dataSources)[0] ?? 'instituciones',
            layout: { version: 1, rows: 8, columns: 7 },
            cells: {},
        };
        ensureGridCells();

        for (let column = 0; column < state.layout.columns; column += 1) {
            setProposalCell(0, column, 'text', column === 0 ? 'NUEVO REPORTE' : '', null, {
                background: '#2D3D78',
                color: '#FFFFFF',
                font_size: 14,
                bold: true,
                align: 'center',
            });
        }

        const labelStyle = { background: '#EAF2F8', color: '#1F3B64', bold: true };
        setProposalCell(1, 0, 'text', 'Institución', null, labelStyle);
        setProposalCell(1, 1, 'parameter', '', 'institution.name');
        setProposalCell(1, 3, 'text', 'Generado', null, labelStyle);
        setProposalCell(1, 4, 'parameter', '', 'system.generated_at');

        const headings = ['Fecha', 'Hospital', 'Paciente', 'Tipo', 'Estado', 'Remisión', 'Lote'];
        const parameters = [
            'request.requested_at',
            'hospital.name',
            'request.patient',
            'request.type',
            'request.status',
            'mixture.remission',
            'mixture.lot',
        ];
        headings.forEach((heading, column) => {
            setProposalCell(3, column, 'text', heading, null, {
                background: '#D9E5F3',
                color: '#1F3B64',
                bold: true,
                align: 'center',
            });
            setProposalCell(4, column, 'parameter', '', parameters[column], {}, 'vertical');
        });

        return state;
    }

    function stateFromTemplate(template) {
        state = {
            id: template.id,
            is_published: Boolean(template.is_published),
            name: template.name ?? '',
            description: template.description ?? '',
            data_source: template.data_source ?? (Object.keys(dataSources)[0] ?? 'instituciones'),
            layout: {
                version: 1,
                rows: Number(template.layout?.rows || 8),
                columns: Number(template.layout?.columns || 7),
            },
            cells: {},
        };

        (template.layout?.cells ?? []).forEach((cell) => {
            const normalized = {
                ...emptyCell(Number(cell.row), Number(cell.column)),
                ...clone(cell),
                style: defaultStyle(cell.style ?? {}),
            };
            state.cells[cellKey(normalized.row, normalized.column)] = normalized;
        });
        ensureGridCells();

        return state;
    }

    function currentCell() {
        return state.cells[cellKey(selected.row, selected.column)];
    }

    function populateSelects() {
        reportSource.innerHTML = Object.entries(dataSources)
            .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
            .join('');
        cellParameter.innerHTML = '<option value="">Seleccionar parámetro</option>' + parameterGroups
            .map((group) => `
                <optgroup label="${escapeHtml(group.label)}">
                    ${group.parameters.map((parameter) => `
                        <option value="${escapeHtml(parameter.key)}">${escapeHtml(parameter.label)}</option>
                    `).join('')}
                </optgroup>
            `).join('');
    }

    function renderLibrary() {
        templateCount.textContent = String(savedTemplates.length);
        library.classList.toggle('hidden', savedTemplates.length === 0);
        templateList.innerHTML = savedTemplates.map((template) => `
            <div class="inline-flex h-9 max-w-full items-stretch overflow-hidden rounded-md border border-slate-300 bg-white">
                <button type="button" data-custom-template-edit="${template.id}"
                    class="flex min-w-0 items-center gap-2 px-3 text-left text-xs text-slate-700 hover:bg-blue-50">
                    <span class="truncate font-semibold">${escapeHtml(template.name)}</span>
                    <span class="hidden truncate text-[10px] text-slate-400 sm:inline">${escapeHtml(template.data_source_label)}</span>
                    ${template.is_published ? '<span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">En reportes</span>' : ''}
                </button>
                <button type="button" data-custom-template-delete="${template.id}"
                    class="inline-flex w-9 shrink-0 items-center justify-center border-l border-slate-200 text-red-600 hover:bg-red-50"
                    title="Eliminar ${escapeHtml(template.name)}" aria-label="Eliminar ${escapeHtml(template.name)}">
                    <span aria-hidden="true" class="text-lg leading-none">&times;</span>
                </button>
            </div>
        `).join('');
    }

    function renderPublishButton() {
        if (!publishButton || !state) return;

        if (state.is_published) {
            publishButton.textContent = 'Quitar de reportes';
            publishButton.className = 'inline-flex h-10 items-center gap-2 rounded-md bg-amber-500 px-4 text-sm font-semibold text-white hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60';
            return;
        }

        publishButton.textContent = 'Agregar a reportes';
        publishButton.className = 'inline-flex h-10 items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-600 disabled:cursor-wait disabled:opacity-60';
    }

    function openBuilder(template = null) {
        selected = { row: 0, column: 0 };
        template ? stateFromTemplate(template) : newTemplateState();
        modalTitle.textContent = template ? 'Editar reporte personalizado' : 'Crear nuevo reporte';
        reportName.value = state.name;
        reportSource.value = state.data_source;
        reportDescription.value = state.description;
        saveError.textContent = '';
        builderModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        renderGrid();
        renderInspector();
        renderPublishButton();
        window.setTimeout(() => reportName.focus(), 50);
    }

    function closeBuilder() {
        builderModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        saveError.textContent = '';
        state = null;
    }

    function cellDisplay(cell) {
        if (cell.type === 'parameter') {
            const parameter = parameterMap.get(cell.parameter);
            return parameter ? `{${parameter.label}}` : '{Seleccionar parámetro}';
        }

        if (cell.value !== '') return cell.value;
        return cell.type === 'free' ? '[Campo libre]' : '';
    }

    function renderGrid() {
        ensureGridCells();
        const header = Array.from({ length: state.layout.columns }, (_, column) => `
            <th scope="col" class="sticky top-0 z-10 h-8 min-w-[150px] border border-slate-300 bg-slate-100 px-2 text-center text-xs font-semibold text-slate-600">
                ${columnName(column)}
            </th>
        `).join('');
        const rows = Array.from({ length: state.layout.rows }, (_, row) => {
            const cells = Array.from({ length: state.layout.columns }, (_, column) => {
                const cell = state.cells[cellKey(row, column)];
                const isSelected = row === selected.row && column === selected.column;
                const typeClass = cell.type === 'parameter'
                    ? 'text-blue-800'
                    : cell.type === 'free' ? 'text-emerald-800' : '';
                const placeholderClass = cellDisplay(cell) === '' ? 'text-slate-300' : '';
                const style = [
                    `background:${cell.style.background}`,
                    `color:${cell.style.color}`,
                    `font-family:${cell.style.font_family}`,
                    `font-size:${cell.style.font_size}px`,
                    `font-weight:${cell.style.bold ? '700' : '400'}`,
                    `font-style:${cell.style.italic ? 'italic' : 'normal'}`,
                    `text-decoration:${cell.style.underline ? 'underline' : 'none'}`,
                    `text-align:${cell.style.align}`,
                ].join(';');

                return `
                    <td class="relative h-12 min-w-[150px] border border-slate-300 p-0 ${isSelected ? 'outline outline-2 -outline-offset-2 outline-blue-600' : ''}"
                        data-grid-cell data-row="${row}" data-column="${column}">
                        <div data-cell-editor data-row="${row}" data-column="${column}"
                            ${cell.type === 'parameter' ? '' : 'contenteditable="true"'}
                            class="flex h-full min-h-12 w-full items-center overflow-hidden px-2 py-1 text-xs leading-tight outline-none ${typeClass} ${placeholderClass}"
                            style="${style}">${escapeHtml(cellDisplay(cell))}</div>
                        <span class="pointer-events-none absolute bottom-0.5 right-1 text-[8px] font-semibold uppercase text-slate-400">
                            ${cell.type === 'parameter'
                                ? cell.repeat_direction === 'vertical'
                                    ? '↓ Vertical'
                                    : cell.repeat_direction === 'horizontal' ? '→ Horizontal' : 'Dato'
                                : cell.type === 'free' ? 'Libre' : ''}
                        </span>
                    </td>
                `;
            }).join('');

            return `
                <tr>
                    <th scope="row" class="sticky left-0 z-[5] h-12 w-10 min-w-10 border border-slate-300 bg-slate-100 text-center text-xs font-semibold text-slate-600">${row + 1}</th>
                    ${cells}
                </tr>
            `;
        }).join('');

        gridElement.innerHTML = `
            <table class="border-collapse table-fixed" role="grid" aria-label="Plantilla de reporte" data-disable-column-filters>
                <thead><tr><th class="sticky left-0 top-0 z-20 h-8 w-10 min-w-10 border border-slate-300 bg-slate-200"></th>${header}</tr></thead>
                <tbody>${rows}</tbody>
            </table>
        `;
        gridSize.textContent = `${state.layout.rows} renglones por ${state.layout.columns} columnas`;
        document.querySelectorAll('[data-grid-action="remove-row"]').forEach((button) => {
            button.disabled = state.layout.rows <= 1;
        });
        document.querySelectorAll('[data-grid-action="remove-column"]').forEach((button) => {
            button.disabled = state.layout.columns <= 1;
        });
    }

    function renderInspector() {
        const cell = currentCell();
        selectedCellLabel.textContent = selectedCoordinate();
        cellValue.value = cell.value;
        cellParameter.value = cell.parameter ?? '';
        fontFamily.value = cell.style.font_family;
        fontSize.value = cell.style.font_size;
        textColor.value = cell.style.color;
        backgroundColor.value = cell.style.background;
        cellParameterWrap.classList.toggle('hidden', cell.type !== 'parameter');
        cellParameterWrap.classList.toggle('block', cell.type === 'parameter');
        cellRepeatWrap.classList.toggle('hidden', cell.type !== 'parameter');
        cellValueWrap.classList.toggle('hidden', cell.type === 'parameter');
        document.querySelectorAll('[data-cell-type]').forEach((button) => {
            const active = button.dataset.cellType === cell.type;
            button.classList.toggle('bg-blue-900', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-white', !active);
            button.classList.toggle('text-slate-600', !active);
        });
        document.querySelectorAll('[data-style-toggle]').forEach((button) => {
            const active = Boolean(cell.style[button.dataset.styleToggle]);
            button.classList.toggle('bg-blue-100', active);
            button.classList.toggle('text-blue-900', active);
        });
        document.querySelectorAll('[data-cell-align]').forEach((button) => {
            const active = button.dataset.cellAlign === cell.style.align;
            button.classList.toggle('bg-blue-100', active);
            button.classList.toggle('text-blue-900', active);
        });
        document.querySelectorAll('[data-cell-repeat]').forEach((button) => {
            const active = button.dataset.cellRepeat === (cell.repeat_direction ?? 'none');
            button.classList.toggle('bg-blue-900', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-white', !active);
            button.classList.toggle('text-slate-600', !active);
        });
    }

    function selectCell(row, column) {
        selected = { row, column };
        renderGrid();
        renderInspector();
    }

    function remapCells(mapper) {
        const remapped = {};
        Object.values(state.cells).forEach((cell) => {
            const position = mapper(cell.row, cell.column);
            if (!position) return;

            const next = { ...cell, row: position.row, column: position.column };
            remapped[cellKey(next.row, next.column)] = next;
        });
        state.cells = remapped;
        ensureGridCells();
    }

    function addRow() {
        if (state.layout.rows >= 40) return showError('La plantilla admite hasta 40 renglones.');
        const insertAt = selected.row + 1;
        state.layout.rows += 1;
        remapCells((row, column) => ({ row: row >= insertAt ? row + 1 : row, column }));
        selected.row = insertAt;
        renderGrid();
        renderInspector();
    }

    function addColumn() {
        if (state.layout.columns >= 20) return showError('La plantilla admite hasta 20 columnas.');
        const insertAt = selected.column + 1;
        state.layout.columns += 1;
        remapCells((row, column) => ({ row, column: column >= insertAt ? column + 1 : column }));
        selected.column = insertAt;
        renderGrid();
        renderInspector();
    }

    function removeRow() {
        if (state.layout.rows <= 1) return;
        const removeAt = selected.row;
        state.layout.rows -= 1;
        remapCells((row, column) => {
            if (row === removeAt) return null;
            return { row: row > removeAt ? row - 1 : row, column };
        });
        selected.row = Math.min(selected.row, state.layout.rows - 1);
        renderGrid();
        renderInspector();
    }

    function removeColumn() {
        if (state.layout.columns <= 1) return;
        const removeAt = selected.column;
        state.layout.columns -= 1;
        remapCells((row, column) => {
            if (column === removeAt) return null;
            return { row, column: column > removeAt ? column - 1 : column };
        });
        selected.column = Math.min(selected.column, state.layout.columns - 1);
        renderGrid();
        renderInspector();
    }

    function updateSelectedCell(update, rerender = true) {
        const cell = currentCell();
        update(cell);
        saveError.textContent = '';
        if (rerender) renderGrid();
        renderInspector();
    }

    function showError(message) {
        saveError.textContent = message;
    }

    function payload() {
        return {
            name: reportName.value.trim(),
            description: reportDescription.value.trim(),
            data_source: reportSource.value,
            layout: {
                version: 1,
                rows: state.layout.rows,
                columns: state.layout.columns,
                cells: Object.values(state.cells)
                    .sort((left, right) => left.row - right.row || left.column - right.column),
            },
        };
    }

    function validateTemplate(data) {
        if (!data.name) {
            showError('Escribe el nombre del reporte.');
            reportName.focus();
            return false;
        }

        const hasContent = data.layout.cells.some((cell) => cell.value.trim() !== '' || cell.parameter || cell.type === 'free');
        if (!hasContent) {
            showError('Agrega al menos un texto, campo libre o parámetro a la plantilla.');
            return false;
        }

        return true;
    }

    function upsertTemplate(template) {
        const index = savedTemplates.findIndex((item) => item.id === template.id);
        if (index >= 0) savedTemplates[index] = template;
        else savedTemplates.unshift(template);
    }

    async function persistTemplate(data) {
        const response = await fetch(state.id ? updateUrlTemplate.replace('__ID__', state.id) : storeUrl, {
            method: state.id ? 'PUT' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });
        const responsePayload = await response.json();
        if (!response.ok) {
            const validationMessage = Object.values(responsePayload.errors ?? {})[0]?.[0];
            throw new Error(validationMessage || responsePayload.message || 'No fue posible guardar la plantilla.');
        }

        upsertTemplate(responsePayload.template);
        stateFromTemplate(responsePayload.template);

        return responsePayload;
    }

    async function saveTemplate() {
        const data = payload();
        const wasEditing = Boolean(state.id);
        if (!validateTemplate(data)) return;

        saveButton.disabled = true;
        const original = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Guardando';
        saveError.textContent = '';

        try {
            const responsePayload = await persistTemplate(data);
            renderLibrary();
            closeBuilder();
            window.Swal?.fire({
                icon: 'success',
                title: wasEditing ? 'Plantilla actualizada' : 'Plantilla creada',
                text: responsePayload.message,
                confirmButtonText: 'Aceptar',
            });
        } catch (error) {
            showError(error.message);
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = original;
        }
    }

    async function publishTemplate() {
        const data = payload();
        if (!validateTemplate(data)) return;

        saveButton.disabled = true;
        publishButton.disabled = true;
        const original = publishButton.textContent;
        publishButton.textContent = state.is_published ? 'Quitando...' : 'Agregando...';
        saveError.textContent = '';

        try {
            const saved = await persistTemplate(data);
            const isPublishing = !saved.template.is_published;
            const response = await fetch(publishUrlTemplate.replace('__ID__', saved.template.id), {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ is_published: isPublishing }),
            });
            const responsePayload = await response.json();
            if (!response.ok) {
                const validationMessage = Object.values(responsePayload.errors ?? {})[0]?.[0];
                throw new Error(validationMessage || responsePayload.message || 'No fue posible actualizar los reportes.');
            }

            upsertTemplate(responsePayload.template);
            stateFromTemplate(responsePayload.template);
            renderLibrary();
            closeBuilder();

            if (window.Swal) {
                await window.Swal.fire({
                    icon: 'success',
                    title: isPublishing ? 'Reporte agregado' : 'Reporte retirado',
                    text: responsePayload.message,
                    confirmButtonText: 'Aceptar',
                });
            }

            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            saveButton.disabled = false;
            publishButton.disabled = false;
            publishButton.textContent = original;
            renderPublishButton();
        }
    }

    async function deleteTemplate(id) {
        const template = savedTemplates.find((item) => item.id === id);
        if (!template) return;

        const confirmed = window.Swal
            ? (await window.Swal.fire({
                icon: 'warning',
                title: 'Eliminar plantilla',
                text: `Se eliminará “${template.name}”.`,
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#DC2626',
            })).isConfirmed
            : window.confirm(`¿Eliminar la plantilla “${template.name}”?`);
        if (!confirmed) return;

        try {
            const response = await fetch(deleteUrlTemplate.replace('__ID__', id), {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            const responsePayload = await response.json();
            if (!response.ok) throw new Error(responsePayload.message || 'No fue posible eliminar la plantilla.');

            savedTemplates = savedTemplates.filter((item) => item.id !== id);
            renderLibrary();
            window.Swal?.fire({
                icon: 'success',
                title: 'Plantilla eliminada',
                text: responsePayload.message,
                confirmButtonText: 'Aceptar',
            });
        } catch (error) {
            window.Swal?.fire({
                icon: 'error',
                title: 'No se pudo eliminar',
                text: error.message,
                confirmButtonText: 'Aceptar',
            });
        }
    }

    populateSelects();
    renderLibrary();
    builderCreateButton.addEventListener('click', () => openBuilder());
    document.querySelectorAll('[data-custom-report-close]').forEach((button) => {
        button.addEventListener('click', closeBuilder);
    });
    builderModal.addEventListener('click', (event) => {
        if (event.target === builderModal) closeBuilder();
    });
    templateList.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-custom-template-delete]');
        if (remove) deleteTemplate(Number(remove.dataset.customTemplateDelete));
    });
    document.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-custom-template-edit]');
        if (!edit) return;

        const template = savedTemplates.find((item) => item.id === Number(edit.dataset.customTemplateEdit));
        if (template) openBuilder(template);
    });
    gridElement.addEventListener('click', (event) => {
        const target = event.target.closest('[data-grid-cell]');
        if (target) selectCell(Number(target.dataset.row), Number(target.dataset.column));
    });
    gridElement.addEventListener('input', (event) => {
        const editor = event.target.closest('[data-cell-editor]');
        if (!editor) return;

        const row = Number(editor.dataset.row);
        const column = Number(editor.dataset.column);
        const cell = state.cells[cellKey(row, column)];
        if (cell.type !== 'parameter') {
            cell.value = editor.textContent.replace('[Campo libre]', '').slice(0, 500);
            if (row === selected.row && column === selected.column) cellValue.value = cell.value;
        }
    });
    document.querySelectorAll('[data-grid-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const actions = {
                'add-row': addRow,
                'add-column': addColumn,
                'remove-row': removeRow,
                'remove-column': removeColumn,
            };
            actions[button.dataset.gridAction]?.();
        });
    });
    document.querySelectorAll('[data-cell-type]').forEach((button) => {
        button.addEventListener('click', () => updateSelectedCell((cell) => {
            cell.type = button.dataset.cellType;
            if (cell.type === 'parameter' && !cell.parameter) {
                cell.parameter = parameterGroups[0]?.parameters[0]?.key ?? null;
            }
            if (cell.type !== 'parameter') {
                cell.parameter = null;
                cell.repeat_direction = 'none';
            }
        }));
    });
    cellValue.addEventListener('input', () => updateSelectedCell((cell) => {
        cell.value = cellValue.value.slice(0, 500);
    }));
    cellParameter.addEventListener('change', () => updateSelectedCell((cell) => {
        cell.parameter = cellParameter.value || null;
    }));
    document.querySelectorAll('[data-cell-repeat]').forEach((button) => {
        button.addEventListener('click', () => updateSelectedCell((cell) => {
            cell.repeat_direction = button.dataset.cellRepeat;
        }));
    });
    fontFamily.addEventListener('change', () => updateSelectedCell((cell) => {
        cell.style.font_family = fontFamily.value;
    }));
    fontSize.addEventListener('change', () => updateSelectedCell((cell) => {
        cell.style.font_size = Math.min(36, Math.max(8, Number(fontSize.value) || 11));
    }));
    textColor.addEventListener('input', () => updateSelectedCell((cell) => {
        cell.style.color = textColor.value.toUpperCase();
    }));
    backgroundColor.addEventListener('input', () => updateSelectedCell((cell) => {
        cell.style.background = backgroundColor.value.toUpperCase();
    }));
    document.querySelectorAll('[data-style-toggle]').forEach((button) => {
        button.addEventListener('click', () => updateSelectedCell((cell) => {
            const property = button.dataset.styleToggle;
            cell.style[property] = !cell.style[property];
        }));
    });
    document.querySelectorAll('[data-cell-align]').forEach((button) => {
        button.addEventListener('click', () => updateSelectedCell((cell) => {
            cell.style.align = button.dataset.cellAlign;
        }));
    });
    reportName.addEventListener('input', () => {
        if (state) state.name = reportName.value;
        saveError.textContent = '';
    });
    reportSource.addEventListener('change', () => {
        if (state) state.data_source = reportSource.value;
    });
    reportDescription.addEventListener('input', () => {
        if (state) state.description = reportDescription.value;
    });
    saveButton.addEventListener('click', saveTemplate);
    publishButton.addEventListener('click', publishTemplate);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !builderModal.classList.contains('hidden')) closeBuilder();
    });
}
