if (typeof window.createExcelColumnFilters !== 'function') {
    window.createExcelColumnFilters = function(config) {
        window.__excelColumnFilterInstances = window.__excelColumnFilterInstances || {};
        window.__excelColumnFilterInstances[config.instanceId]?.destroy?.();

        const table = document.getElementById(config.tableId);
        if (!table) return null;

        const rows = Array.from(table.querySelectorAll(config.rowSelector));
        const triggers = Array.from(table.querySelectorAll(config.triggerSelector));
        const columnIndexes = triggers.map((trigger) => Number(trigger.dataset.column));
        const appliedFilters = new Map();
        const panel = document.createElement('div');
        let activeColumn = null;
        let activeTrigger = null;
        let draftValues = new Set();
        const controller = new AbortController();
        const listenerOptions = { signal: controller.signal };

        const cellValue = (row, columnIndex) => {
            const cell = row.cells[columnIndex];
            if (!cell) return '';

            const controls = Array.from(cell.querySelectorAll('input:not([type="hidden"]), select, textarea'));
            if (controls.length > 0) {
                return controls.map((control) => {
                    if (control.matches('select')) {
                        return Array.from(control.selectedOptions)
                            .map((option) => option.textContent || option.value)
                            .join(', ');
                    }

                    if (control.matches('[type="checkbox"], [type="radio"]')) {
                        return control.checked ? (control.value || 'Si') : 'No';
                    }

                    return control.value;
                }).join(' ').replace(/\s+/g, ' ').trim();
            }

            return String(cell.textContent || '').replace(/\s+/g, ' ').trim();
        };
        const searchableValue = (value) => String(value || '')
            .toLocaleLowerCase('es')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
        const valuesForColumn = (columnIndex) => {
            const values = rows
                .map((row) => cellValue(row, columnIndex))
                .filter((value) => value !== '');

            return [...new Set(values)].sort((a, b) => a.localeCompare(b, 'es', {
                numeric: true,
                sensitivity: 'base',
            }));
        };

        panel.id = `${config.instanceId}-column-filter-panel`;
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

        const searchInput = panel.querySelector('[data-filter-search]');
        const allCheckbox = panel.querySelector('[data-filter-all]');
        const optionsContainer = panel.querySelector('[data-filter-options]');
        const emptyMessage = panel.querySelector('[data-filter-empty]');

        function rowMatches(row) {
            return columnIndexes.every((columnIndex) => {
                const selectedValues = appliedFilters.get(columnIndex);
                return !selectedValues || selectedValues.has(cellValue(row, columnIndex));
            });
        }

        function applyFilters() {
            rows.forEach((row) => {
                row.dataset.columnFilterMatch = rowMatches(row) ? '1' : '0';
            });
            config.onChange?.();
        }

        function updateAllCheckbox() {
            const values = valuesForColumn(activeColumn);
            allCheckbox.checked = values.length > 0 && draftValues.size === values.length;
            allCheckbox.indeterminate = draftValues.size > 0 && draftValues.size < values.length;
        }

        function renderOptions() {
            const values = valuesForColumn(activeColumn);
            optionsContainer.innerHTML = '';

            values.forEach((value) => {
                const label = document.createElement('label');
                const checkbox = document.createElement('input');
                const text = document.createElement('span');

                label.className = 'js-column-filter-option flex cursor-pointer items-start gap-2 rounded px-1 py-1 hover:bg-slate-100';
                label.dataset.searchValue = searchableValue(value);
                checkbox.type = 'checkbox';
                checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500';
                checkbox.checked = draftValues.has(value);
                text.className = 'min-w-0 break-words text-xs leading-5';
                text.textContent = value;

                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        draftValues.add(value);
                    } else {
                        draftValues.delete(value);
                    }
                    updateAllCheckbox();
                });

                label.append(checkbox, text);
                optionsContainer.appendChild(label);
            });

            emptyMessage.classList.toggle('hidden', values.length !== 0);
            updateAllCheckbox();
        }

        function closePanel() {
            panel.classList.add('hidden');
            activeTrigger?.setAttribute('aria-expanded', 'false');
            activeColumn = null;
            activeTrigger = null;
            searchInput.value = '';
        }

        function openPanel(trigger) {
            activeTrigger?.setAttribute('aria-expanded', 'false');
            activeTrigger = trigger;
            activeColumn = Number(trigger.dataset.column);
            const values = valuesForColumn(activeColumn);
            const selectedValues = appliedFilters.get(activeColumn);
            draftValues = new Set(selectedValues || values);

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
        }

        function updateTriggerStates() {
            triggers.forEach((trigger) => {
                const isActive = appliedFilters.has(Number(trigger.dataset.column));
                trigger.classList.toggle('border-blue-400', isActive);
                trigger.classList.toggle('bg-blue-100', isActive);
                trigger.classList.toggle('text-blue-700', isActive);
                trigger.classList.toggle('text-slate-600', !isActive);
                trigger.setAttribute('title', isActive ? 'Filtro aplicado. Editar filtro' : trigger.getAttribute('aria-label'));
            });
        }

        triggers.forEach((trigger) => {
            trigger.addEventListener('pointerdown', (event) => event.stopPropagation(), listenerOptions);
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (activeTrigger === trigger && !panel.classList.contains('hidden')) {
                    closePanel();
                    return;
                }

                openPanel(trigger);
            }, listenerOptions);
        });

        searchInput.addEventListener('input', () => {
            const query = searchableValue(searchInput.value);
            let visibleOptions = 0;

            optionsContainer.querySelectorAll('.js-column-filter-option').forEach((option) => {
                const visible = !query || option.dataset.searchValue.includes(query);
                option.classList.toggle('hidden', !visible);
                if (visible) visibleOptions++;
            });

            emptyMessage.classList.toggle('hidden', visibleOptions !== 0);
        }, listenerOptions);

        allCheckbox.addEventListener('change', () => {
            const values = valuesForColumn(activeColumn);
            draftValues = allCheckbox.checked ? new Set(values) : new Set();
            renderOptions();
        }, listenerOptions);

        panel.querySelector('[data-filter-accept]').addEventListener('click', () => {
            const values = valuesForColumn(activeColumn);

            if (draftValues.size === values.length) {
                appliedFilters.delete(activeColumn);
            } else {
                appliedFilters.set(activeColumn, new Set(draftValues));
            }

            applyFilters();
            updateTriggerStates();
            closePanel();
        }, listenerOptions);

        panel.querySelector('[data-filter-cancel]').addEventListener('click', closePanel, listenerOptions);
        document.addEventListener('click', (event) => {
            if (!panel.contains(event.target) && !event.target.closest(config.triggerSelector)) {
                closePanel();
            }
        }, listenerOptions);
        window.addEventListener('resize', closePanel, listenerOptions);

        applyFilters();

        const instance = {
            matches(row) {
                return row?.dataset.columnFilterMatch !== '0';
            },
            apply: applyFilters,
            destroy() {
                closePanel();
                controller.abort();
                panel.remove();

                if (window.__excelColumnFilterInstances?.[config.instanceId] === instance) {
                    delete window.__excelColumnFilterInstances[config.instanceId];
                }
            },
        };

        window.__excelColumnFilterInstances[config.instanceId] = instance;

        return instance;
    };
}
