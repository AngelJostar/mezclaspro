const instances = new Map();
let sequence = 0;
let scanTimer = null;

const normalize = (value) => String(value || '')
    .replace(/\s+/g, ' ')
    .trim();

const searchable = (value) => normalize(value)
    .toLocaleLowerCase('es')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

const commandHeadings = [
    'accion',
    'acciones',
    'aprobar / editar',
    'cambiar estatus',
    'cancelar',
    'concluir',
    'descargar',
    'deshabilitar',
    'editar',
    'eliminar',
    'entregar',
    'entregada',
    'exportar',
    'facturar',
    'guardar',
    'inspeccion',
    'inspeccionar',
    'movimientos',
    'no aprobar',
    'preparada',
    'presentaciones',
    'reporte',
    'solicitud completa',
    'ver',
];

function getRows(table) {
    if (window.jQuery?.fn?.dataTable?.isDataTable?.(table)) {
        return window.jQuery(table).DataTable().rows().nodes().toArray();
    }

    return Array.from(table.tBodies).flatMap((tbody) => Array.from(tbody.rows));
}

function controlValue(control) {
    if (control.matches('select')) {
        return Array.from(control.selectedOptions)
            .map((option) => option.textContent || option.value)
            .join(', ');
    }

    if (control.matches('[type="checkbox"], [type="radio"]')) {
        return control.checked ? (control.value || 'Si') : 'No';
    }

    return control.value;
}

function cellValue(row, columnIndex) {
    const cell = row.cells[columnIndex];
    if (!cell) return '';

    const controls = Array.from(cell.querySelectorAll('input:not([type="hidden"]), select, textarea'));
    if (controls.length > 0) {
        return normalize(controls.map(controlValue).join(' '));
    }

    return normalize(cell.textContent);
}

function valuesForColumn(table, columnIndex) {
    const values = getRows(table)
        .map((row) => cellValue(row, columnIndex))
        .filter(Boolean);

    return [...new Set(values)].sort((left, right) => left.localeCompare(right, 'es', {
        numeric: true,
        sensitivity: 'base',
    }));
}

function isCommandColumn(table, header, columnIndex) {
    const heading = searchable(header.textContent);
    if (!heading) return true;
    if (commandHeadings.some((command) => heading === command || heading.startsWith(`${command} `))) {
        return true;
    }

    const populatedCells = getRows(table)
        .map((row) => row.cells[columnIndex])
        .filter((cell) => cell && normalize(cell.textContent));

    if (populatedCells.length === 0) return true;

    const commandCells = populatedCells.filter((cell) => {
        const interactive = cell.querySelector('a, button, input[type="button"], input[type="submit"]');
        const plainText = normalize(cell.cloneNode(true).textContent);
        return interactive && plainText.length <= 40;
    });

    return commandCells.length / populatedCells.length >= 0.8;
}

function createTrigger(header, columnIndex, triggerClass) {
    const label = normalize(header.textContent);
    const wrapper = document.createElement('div');
    const content = document.createElement('span');
    const trigger = document.createElement('button');

    while (header.firstChild) content.appendChild(header.firstChild);

    wrapper.className = 'flex items-center gap-2';
    content.className = 'min-w-0 flex-1';
    trigger.type = 'button';
    trigger.dataset.column = String(columnIndex);
    trigger.className = `${triggerClass} inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-200 hover:text-slate-800`;
    trigger.title = `Filtrar ${label}`;
    trigger.setAttribute('aria-label', `Filtrar ${label}`);
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML = '<span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>';

    wrapper.append(content, trigger);
    header.appendChild(wrapper);
    header.classList.add('whitespace-nowrap');

    return trigger;
}

function createPanel(instanceId) {
    const panel = document.createElement('div');
    panel.id = `${instanceId}-panel`;
    panel.className = 'fixed z-[100] hidden w-72 overflow-hidden rounded-md border border-slate-300 bg-white text-sm normal-case text-slate-700 shadow-xl';
    panel.innerHTML = `
        <div class="border-b border-slate-200 p-2">
            <input type="search" data-filter-search placeholder="Buscar (Todos)"
                class="h-9 w-full rounded-sm border-slate-300 px-2 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="max-h-60 overflow-y-auto p-2">
            <label class="flex cursor-pointer items-center gap-2 border-b border-slate-100 px-1 pb-2 font-semibold">
                <input type="checkbox" data-filter-all class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                <span>(Todos)</span>
            </label>
            <div data-filter-options class="mt-1 space-y-0.5"></div>
            <p data-filter-empty class="hidden px-1 py-4 text-center text-xs text-slate-400">Sin coincidencias</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 p-2">
            <button type="button" data-filter-accept class="rounded border border-slate-800 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-100">Aceptar</button>
            <button type="button" data-filter-cancel class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">Cancelar</button>
        </div>
    `;
    document.body.appendChild(panel);

    return panel;
}

function enhanceTable(table) {
    if (instances.has(table)) {
        instances.get(table).refresh();
        return;
    }
    if (table.dataset.disableColumnFilters !== undefined) return;
    if (table.querySelector('thead [data-column]')) return;

    const headerRows = table.tHead?.rows;
    if (!headerRows?.length) return;

    const headerRow = headerRows[headerRows.length - 1];
    const headers = Array.from(headerRow.cells);
    const rows = getRows(table);
    if (!headers.length || !rows.length) return;
    if (headers.some((header) => header.colSpan > 1 || header.rowSpan > 1)) return;
    if (!rows.some((row) => row.cells.length >= headers.length)) return;

    const instanceId = `automatic-table-filter-${++sequence}`;
    const triggerClass = `js-${instanceId}`;
    const filterableColumns = headers
        .map((header, columnIndex) => ({ header, columnIndex }))
        .filter(({ header, columnIndex }) => !isCommandColumn(table, header, columnIndex));

    if (!filterableColumns.length) return;

    const triggers = filterableColumns.map(({ header, columnIndex }) => (
        createTrigger(header, columnIndex, triggerClass)
    ));
    const panel = createPanel(instanceId);
    const controller = new AbortController();
    const listenerOptions = { signal: controller.signal };
    const appliedFilters = new Map();
    const searchInput = panel.querySelector('[data-filter-search]');
    const allCheckbox = panel.querySelector('[data-filter-all]');
    const optionsContainer = panel.querySelector('[data-filter-options]');
    const emptyMessage = panel.querySelector('[data-filter-empty]');
    let activeColumn = null;
    let activeTrigger = null;
    let draftValues = new Set();

    table.dataset.automaticColumnFilters = 'true';

    const rowMatches = (row) => filterableColumns.every(({ columnIndex }) => {
        const selectedValues = appliedFilters.get(columnIndex);
        return !selectedValues || selectedValues.has(cellValue(row, columnIndex));
    });

    const applyFilters = () => {
        getRows(table).forEach((row) => {
            row.classList.toggle('automatic-column-filter-hidden', !rowMatches(row));
        });
    };

    const updateAllCheckbox = () => {
        const values = valuesForColumn(table, activeColumn);
        allCheckbox.checked = values.length > 0 && draftValues.size === values.length;
        allCheckbox.indeterminate = draftValues.size > 0 && draftValues.size < values.length;
    };

    const renderOptions = () => {
        const values = valuesForColumn(table, activeColumn);
        optionsContainer.innerHTML = '';

        values.forEach((value) => {
            const label = document.createElement('label');
            const checkbox = document.createElement('input');
            const text = document.createElement('span');

            label.className = 'js-column-filter-option flex cursor-pointer items-start gap-2 rounded px-1 py-1 hover:bg-slate-100';
            label.dataset.searchValue = searchable(value);
            checkbox.type = 'checkbox';
            checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500';
            checkbox.checked = draftValues.has(value);
            text.className = 'min-w-0 break-words text-xs leading-5';
            text.textContent = value;

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) draftValues.add(value);
                else draftValues.delete(value);
                updateAllCheckbox();
            });

            label.append(checkbox, text);
            optionsContainer.appendChild(label);
        });

        emptyMessage.classList.toggle('hidden', values.length !== 0);
        updateAllCheckbox();
    };

    const closePanel = () => {
        panel.classList.add('hidden');
        activeTrigger?.setAttribute('aria-expanded', 'false');
        activeColumn = null;
        activeTrigger = null;
        searchInput.value = '';
    };

    const openPanel = (trigger) => {
        activeTrigger?.setAttribute('aria-expanded', 'false');
        activeTrigger = trigger;
        activeColumn = Number(trigger.dataset.column);
        const values = valuesForColumn(table, activeColumn);
        draftValues = new Set(appliedFilters.get(activeColumn) || values);

        searchInput.value = '';
        renderOptions();
        panel.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');

        const rect = trigger.getBoundingClientRect();
        const panelWidth = 288;
        const left = Math.min(Math.max(8, rect.right - panelWidth), window.innerWidth - panelWidth - 8);
        const top = Math.min(rect.bottom + 6, window.innerHeight - panel.offsetHeight - 8);
        panel.style.left = `${left}px`;
        panel.style.top = `${Math.max(8, top)}px`;
        window.setTimeout(() => searchInput.focus(), 0);
    };

    const updateTriggerStates = () => {
        triggers.forEach((trigger) => {
            const active = appliedFilters.has(Number(trigger.dataset.column));
            trigger.classList.toggle('border-blue-400', active);
            trigger.classList.toggle('bg-blue-100', active);
            trigger.classList.toggle('text-blue-700', active);
        });
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('pointerdown', (event) => event.stopPropagation(), listenerOptions);
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (activeTrigger === trigger && !panel.classList.contains('hidden')) closePanel();
            else openPanel(trigger);
        }, listenerOptions);
    });

    searchInput.addEventListener('input', () => {
        const query = searchable(searchInput.value);
        let visibleOptions = 0;
        optionsContainer.querySelectorAll('.js-column-filter-option').forEach((option) => {
            const visible = !query || option.dataset.searchValue.includes(query);
            option.classList.toggle('hidden', !visible);
            if (visible) visibleOptions++;
        });
        emptyMessage.classList.toggle('hidden', visibleOptions !== 0);
    }, listenerOptions);

    allCheckbox.addEventListener('change', () => {
        draftValues = allCheckbox.checked ? new Set(valuesForColumn(table, activeColumn)) : new Set();
        renderOptions();
    }, listenerOptions);

    panel.querySelector('[data-filter-accept]').addEventListener('click', () => {
        const values = valuesForColumn(table, activeColumn);
        if (draftValues.size === values.length) appliedFilters.delete(activeColumn);
        else appliedFilters.set(activeColumn, new Set(draftValues));
        applyFilters();
        updateTriggerStates();
        closePanel();
    }, listenerOptions);

    panel.querySelector('[data-filter-cancel]').addEventListener('click', closePanel, listenerOptions);
    document.addEventListener('click', (event) => {
        if (!panel.contains(event.target) && !event.target.closest(`.${triggerClass}`)) closePanel();
    }, listenerOptions);
    window.addEventListener('resize', closePanel, listenerOptions);

    instances.set(table, {
        refresh: applyFilters,
        destroy() {
            controller.abort();
            panel.remove();
            instances.delete(table);
        },
    });
}

function scanTables() {
    instances.forEach((instance, table) => {
        if (!table.isConnected) instance.destroy();
    });

    document.querySelectorAll('.admin-page table').forEach(enhanceTable);
}

function scheduleScan() {
    window.clearTimeout(scanTimer);
    scanTimer = window.setTimeout(scanTables, 80);
}

window.addEventListener('load', () => {
    scanTables();
    new MutationObserver(scheduleScan).observe(document.querySelector('.admin-page') || document.body, {
        childList: true,
        subtree: true,
    });
});
