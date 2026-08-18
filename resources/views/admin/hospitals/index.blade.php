<x-admin-layout>

    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Hospitales</h1>
    </div>

    <form method="GET" action="{{ route('admin.hospitals.index') }}" class="mt-4 mb-4 max-w-sm">
        <label for="institution_id" class="mb-1 block text-sm font-medium text-gray-700">
            Instituci&oacute;n
        </label>
        <select id="institution_id" name="institution_id"
            class="h-10 w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            onchange="this.form.submit()">
            <option value="all" @selected($institutionId === 'all')>Todas</option>
            @foreach ($instituciones as $institucion)
                <option value="{{ $institucion->id }}" @selected((string) $institucion->id === $institutionId)>
                    {{ $institucion->nombre }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="relative overflow-x-auto">
        <table id="hospitalsTable" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span>ID</span>
                            <button type="button" data-column="0"
                                class="js-hospital-column-filter inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                                title="Filtrar ID" aria-label="Filtrar ID" aria-expanded="false">
                                <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
                            </button>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span>Nombre</span>
                            <button type="button" data-column="1"
                                class="js-hospital-column-filter inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                                title="Filtrar Nombre" aria-label="Filtrar Nombre" aria-expanded="false">
                                <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
                            </button>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span>Instituciones</span>
                            <button type="button" data-column="2"
                                class="js-hospital-column-filter inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                                title="Filtrar Instituciones" aria-label="Filtrar Instituciones" aria-expanded="false">
                                <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
                            </button>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span>Direccion</span>
                            <button type="button" data-column="3"
                                class="js-hospital-column-filter inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                                title="Filtrar Direccion" aria-label="Filtrar Direccion" aria-expanded="false">
                                <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
                            </button>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span>Estado</span>
                            <button type="button" data-column="4"
                                class="js-hospital-column-filter inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                                title="Filtrar Estado" aria-label="Filtrar Estado" aria-expanded="false">
                                <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
                            </button>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">Editar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hospitals as $hospital)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $hospital->id }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $hospital->name }}
                        </td>
                        <td class="px-6 py-4">
                            @forelse ($hospital->instituciones as $institucion)
                                <div>{{ $institucion->nombre }}</div>
                            @empty
                                <span class="text-gray-400">Sin institucion</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4">
                            {{ $hospital->adress }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.hospitals.toggle-status', $hospital) }}"
                                class="js-hospital-status-form inline-flex"
                                data-hospital-name="{{ $hospital->name }}"
                                data-next-status="{{ $hospital->is_active ? 'Inactivo' : 'Activo' }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="institution_id" value="{{ $institutionId }}">

                                <button type="submit"
                                    class="inline-flex min-w-[88px] items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-4 {{ $hospital->is_active
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-100'
                                        : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-100' }}"
                                    title="Cambiar a {{ $hospital->is_active ? 'Inactivo' : 'Activo' }}"
                                    aria-label="Cambiar estado de {{ $hospital->name }} a {{ $hospital->is_active ? 'Inactivo' : 'Activo' }}">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $hospital->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $hospital->is_active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.hospitals.edit', $hospital) }}" icon="fa-solid fa-pen">
                                Editar
                            </x-table-action-link>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const hospitalsDataTable = new DataTable('#hospitalsTable', {
                    paging: false,
                    lengthChange: false,
                    info: false,
                    order: [
                        [0, 'desc']
                    ],
                    language: {
                        zeroRecords: "Nada encontrado",
                        infoEmpty: "No hay registros disponibles",
                        infoFiltered: "(filtrado de _MAX_ registros totales)",
                        search: "Buscar:",
                    }
                });

                const hospitalColumnFilters = new Map();
                const filterableHospitalColumns = [0, 1, 2, 3, 4];
                const filterPanel = document.createElement('div');
                let activeFilterColumn = null;
                let activeFilterTrigger = null;
                let draftFilterValues = new Set();

                function normalizeHospitalFilterValue(value) {
                    const container = document.createElement('div');
                    container.innerHTML = String(value ?? '');

                    return (container.textContent || container.innerText || '')
                        .replace(/\s+/g, ' ')
                        .trim();
                }

                const hospitalColumnValues = new Map(filterableHospitalColumns.map((columnIndex) => {
                    const values = hospitalsDataTable
                        .column(columnIndex)
                        .data()
                        .toArray()
                        .map(normalizeHospitalFilterValue)
                        .filter((value) => value !== '');

                    return [columnIndex, [...new Set(values)].sort((a, b) => a.localeCompare(b, 'es', {
                        numeric: true,
                        sensitivity: 'base'
                    }))];
                }));

                const hospitalExcelFilter = function(settings, rowData) {
                    if (settings.nTable?.id !== 'hospitalsTable') return true;

                    return filterableHospitalColumns.every((columnIndex) => {
                        const selectedValues = hospitalColumnFilters.get(columnIndex);
                        if (!selectedValues) return true;

                        return selectedValues.has(normalizeHospitalFilterValue(rowData[columnIndex]));
                    });
                };

                DataTable.ext.search.push(hospitalExcelFilter);

                filterPanel.id = 'hospital-column-filter-panel';
                filterPanel.className = 'fixed z-[100] hidden w-72 overflow-hidden rounded-md border border-slate-300 bg-white text-sm normal-case text-slate-700 shadow-xl';
                filterPanel.innerHTML = `
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
                document.body.appendChild(filterPanel);

                const filterSearch = filterPanel.querySelector('[data-filter-search]');
                const filterAll = filterPanel.querySelector('[data-filter-all]');
                const filterOptions = filterPanel.querySelector('[data-filter-options]');
                const filterEmpty = filterPanel.querySelector('[data-filter-empty]');

                function updateAllCheckbox() {
                    const values = hospitalColumnValues.get(activeFilterColumn) || [];
                    filterAll.checked = values.length > 0 && draftFilterValues.size === values.length;
                    filterAll.indeterminate = draftFilterValues.size > 0 && draftFilterValues.size < values.length;
                }

                function renderFilterOptions() {
                    const values = hospitalColumnValues.get(activeFilterColumn) || [];
                    filterOptions.innerHTML = '';

                    values.forEach((value) => {
                        const label = document.createElement('label');
                        const checkbox = document.createElement('input');
                        const text = document.createElement('span');

                        label.className = 'js-filter-option flex cursor-pointer items-start gap-2 rounded px-1 py-1 hover:bg-slate-100';
                        label.dataset.searchValue = value.toLocaleLowerCase('es');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500';
                        checkbox.checked = draftFilterValues.has(value);
                        checkbox.dataset.filterValue = value;
                        text.className = 'min-w-0 break-words text-xs leading-5';
                        text.textContent = value;

                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) {
                                draftFilterValues.add(value);
                            } else {
                                draftFilterValues.delete(value);
                            }
                            updateAllCheckbox();
                        });

                        label.append(checkbox, text);
                        filterOptions.appendChild(label);
                    });

                    updateAllCheckbox();
                }

                function closeHospitalFilterPanel() {
                    filterPanel.classList.add('hidden');
                    activeFilterTrigger?.setAttribute('aria-expanded', 'false');
                    activeFilterColumn = null;
                    activeFilterTrigger = null;
                    filterSearch.value = '';
                }

                function openHospitalFilterPanel(trigger) {
                    activeFilterTrigger?.setAttribute('aria-expanded', 'false');
                    activeFilterTrigger = trigger;
                    activeFilterColumn = Number(trigger.dataset.column);
                    const allValues = hospitalColumnValues.get(activeFilterColumn) || [];
                    const appliedValues = hospitalColumnFilters.get(activeFilterColumn);
                    draftFilterValues = new Set(appliedValues || allValues);

                    filterSearch.value = '';
                    renderFilterOptions();
                    filterEmpty.classList.add('hidden');
                    filterPanel.classList.remove('hidden');
                    trigger.setAttribute('aria-expanded', 'true');

                    const rect = trigger.getBoundingClientRect();
                    const panelWidth = 288;
                    const left = Math.min(Math.max(8, rect.right - panelWidth), window.innerWidth - panelWidth - 8);
                    const top = Math.min(rect.bottom + 6, window.innerHeight - filterPanel.offsetHeight - 8);
                    filterPanel.style.left = `${left}px`;
                    filterPanel.style.top = `${Math.max(8, top)}px`;
                    window.setTimeout(() => filterSearch.focus(), 0);
                }

                function updateHospitalFilterButtons() {
                    document.querySelectorAll('.js-hospital-column-filter').forEach((button) => {
                        const isActive = hospitalColumnFilters.has(Number(button.dataset.column));
                        button.classList.toggle('bg-blue-100', isActive);
                        button.classList.toggle('text-blue-700', isActive);
                        button.classList.toggle('text-slate-600', !isActive);
                        button.setAttribute('title', isActive ? 'Filtro aplicado. Editar filtro' : button.getAttribute('aria-label'));
                    });
                }

                document.querySelectorAll('.js-hospital-column-filter').forEach((trigger) => {
                    trigger.addEventListener('pointerdown', (event) => event.stopPropagation());
                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        if (activeFilterTrigger === trigger && !filterPanel.classList.contains('hidden')) {
                            closeHospitalFilterPanel();
                            return;
                        }

                        openHospitalFilterPanel(trigger);
                    });
                });

                filterSearch.addEventListener('input', () => {
                    const query = filterSearch.value.trim().toLocaleLowerCase('es');
                    let visibleOptions = 0;

                    filterOptions.querySelectorAll('.js-filter-option').forEach((option) => {
                        const visible = !query || option.dataset.searchValue.includes(query);
                        option.classList.toggle('hidden', !visible);
                        if (visible) visibleOptions++;
                    });

                    filterEmpty.classList.toggle('hidden', visibleOptions !== 0);
                });

                filterAll.addEventListener('change', () => {
                    const values = hospitalColumnValues.get(activeFilterColumn) || [];
                    draftFilterValues = filterAll.checked ? new Set(values) : new Set();
                    renderFilterOptions();
                });

                filterPanel.querySelector('[data-filter-accept]').addEventListener('click', () => {
                    const values = hospitalColumnValues.get(activeFilterColumn) || [];

                    if (draftFilterValues.size === values.length) {
                        hospitalColumnFilters.delete(activeFilterColumn);
                    } else {
                        hospitalColumnFilters.set(activeFilterColumn, new Set(draftFilterValues));
                    }

                    hospitalsDataTable.draw();
                    updateHospitalFilterButtons();
                    closeHospitalFilterPanel();
                });

                filterPanel.querySelector('[data-filter-cancel]').addEventListener('click', closeHospitalFilterPanel);

                document.addEventListener('click', (event) => {
                    if (!filterPanel.contains(event.target) && !event.target.closest('.js-hospital-column-filter')) {
                        closeHospitalFilterPanel();
                    }
                });

                window.addEventListener('resize', closeHospitalFilterPanel);

                document.addEventListener('submit', function(event) {
                    const form = event.target.closest('.js-hospital-status-form');

                    if (!form || form.dataset.confirmed === '1') return;

                    event.preventDefault();

                    const hospitalName = form.dataset.hospitalName || 'este hospital';
                    const nextStatus = form.dataset.nextStatus || '';
                    const message = `El hospital "${hospitalName}" cambiara a ${nextStatus}.`;

                    if (!window.Swal) {
                        if (window.confirm(`${message}\n\n¿Deseas continuar?`)) {
                            form.dataset.confirmed = '1';
                            form.submit();
                        }

                        return;
                    }

                    Swal.fire({
                        title: '¿Cambiar estado del hospital?',
                        text: message,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: `Confirmar ${nextStatus}`,
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: nextStatus === 'Activo' ? '#059669' : '#dc2626',
                        cancelButtonColor: '#64748b',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'swal-button-confirm',
                            cancelButton: 'swal-button-cancel'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = '1';
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush

</x-admin-layout>
