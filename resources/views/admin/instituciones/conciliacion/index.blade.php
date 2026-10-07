<x-admin-layout>
    @push('css')
        <style>
            [data-conciliation-inbox] .ht-table td:nth-child(3) { min-width: 130px; }
            [data-conciliation-inbox] .ht-table td:nth-child(4) { min-width: 0; }
            [data-conciliation-inbox] [data-inbox-price]:disabled { opacity: .6; }
            .inbox-conciliable-toggle { display: inline-flex; align-items: center; gap: 8px; padding: 2px; white-space: nowrap; }
            .inbox-conciliable-toggle button { min-width: 38px; height: 26px; padding: 0 10px; border: 0; border-radius: 999px; font-size: 11px; font-weight: 700; line-height: 1; transition: background-color .15s, color .15s; }
            .inbox-conciliable-toggle [data-inbox-conciliable-choice="1"] { background: #d1fae5; color: #047857; }
            .inbox-conciliable-toggle [data-inbox-conciliable-choice="0"] { background: #fee2e2; color: #dc2626; }
            .inbox-conciliable-toggle [data-inbox-conciliable-choice="1"][aria-pressed="true"] { background: #009d71; color: #fff; }
            .inbox-conciliable-toggle [data-inbox-conciliable-choice="0"][aria-pressed="true"] { background: #dc2626; color: #fff; }
            .inbox-conciliable-toggle button:hover:not(:disabled) { filter: brightness(.95); }
            .inbox-conciliable-toggle button:focus-visible { outline: 2px solid #243b7b; outline-offset: 2px; }
            .inbox-conciliable-toggle button:disabled { opacity: .6; cursor: wait; }
            .inbox-conciliation-status { display: inline-flex; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #fef3c7; color: #92400e; white-space: nowrap; }
            .inbox-conciliation-status[data-status="Conciliado"] { background: #d1fae5; color: #047857; }
            .inbox-conciliation-status[data-status="Recibida"] { background: #dbeafe; color: #1d4ed8; }
            .inbox-conciliation-status[data-status="Enviada"] { background: #ede9fe; color: #6d28d9; }
        </style>
        @include('admin.instituciones.conciliacion._layout-style')
    @endpush
    <div class="mt-2 mb-4"><h1 class="text-2xl font-medium text-gray-800">Panel Administrativo</h1></div>
    @include('admin.instituciones.conciliacion._toolbar')
    @include('admin.instituciones.conciliacion._mode-tabs')
    <section class="hospital-tools conciliation-view" data-conciliation-view data-conciliation-inbox aria-label="Conciliación por remisión">
        @include('admin.instituciones.conciliacion._status-tabs')
        <form class="conciliation-filters" method="GET" action="{{ route('admin.instituciones.reportes') }}"
            x-data="{ institution: '{{ $institutionId ?? '' }}' }">
            <input type="hidden" name="seccion" value="conciliacion">
            <input type="hidden" name="modalidad" value="remision">
            <input type="hidden" name="bandeja" value="{{ $activeTab }}">
            @foreach ($table['selected'] as $field => $values)
                @foreach ($values ?: [''] as $value)<input type="hidden" name="columnas[{{ $field }}][]" value="{{ $value }}">@endforeach
            @endforeach
            <input type="hidden" name="orden" value="{{ $table['sort'] }}">
            <input type="hidden" name="direccion" value="{{ $table['direction'] }}">
            <div class="conciliation-field">
                <label for="conciliation-institution">Institución</label>
                <select id="conciliation-institution" name="institucion_id" x-model="institution" @change="$refs.hospital.value = ''">
                    <option value="">Todas las instituciones</option>
                    @foreach ($institutions as $institution)<option value="{{ $institution->id }}" @selected($institutionId === $institution->id)>{{ $institution->nombre }}</option>@endforeach
                </select>
            </div>
            <div class="conciliation-field">
                <label for="conciliation-hospital">Hospital</label>
                <select id="conciliation-hospital" name="hospital_id" x-ref="hospital">
                    <option value="">Todos los hospitales</option>
                    @foreach ($hospitals as $hospital)
                        @php($institutionIds = $hospital->instituciones->modelKeys())
                        <option value="{{ $hospital->id }}" @selected($hospitalId === $hospital->id)
                            @disabled($institutionId && !in_array($institutionId, $institutionIds))
                            @if ($institutionId && !in_array($institutionId, $institutionIds)) hidden @endif
                            :disabled="institution !== '' && !{{ json_encode($institutionIds) }}.includes(Number(institution))"
                            :hidden="institution !== '' && !{{ json_encode($institutionIds) }}.includes(Number(institution))">{{ $hospital->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="conciliation-filter-detail"><div class="conciliation-field"><label for="conciliation-search">Buscar</label><input id="conciliation-search" name="search" value="{{ $search }}" maxlength="150" type="search" placeholder="Paciente, lote o remisión..."></div></div>
            <div class="conciliation-filter-actions">
                <button class="ht-primary" type="submit">Aplicar filtros</button>
                <a class="ht-clear" href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'modalidad' => 'remision']) }}">Limpiar</a>
            </div>
        </form>
        <p class="conciliation-help">Cada fila corresponde a una mezcla. El precio se guarda al salir del campo. Selecciona Sí o No para guardar el estado conciliable.</p>
        <p class="ht-status" data-inbox-status role="status" hidden></p>
        <script type="application/json" id="conciliation-inbox-filters">@json($table)</script>
        <div class="ht-table-scroll" data-sticky-x-position="viewport" data-sticky-x-native data-sticky-x-always-visible tabindex="0" aria-label="Solicitudes de conciliación">
            <table id="conciliation-inbox-table" class="ht-table conciliation-table" data-disable-column-filters data-server-column-filters="conciliation-inbox-filters">
                <thead><tr>
                    @foreach (\App\Support\ConciliationInboxTable::COLUMNS as $field => $label)
                        @php($headerLines = match ($field) {
                            'date' => ['Fecha y hora', 'de solicitud'],
                            'remision' => ['No. de', 'remisión'],
                            'price' => ['Precio de venta', 'total editable'],
                            'conciliation_status' => ['Estatus de', 'conciliación'],
                            default => [$label],
                        })
                        <th data-force-column-filter aria-label="{{ $label }}" aria-sort="{{ $table['sort'] === $field ? ($table['direction'] === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <div class="conciliation-column">
                                <span class="inbox-column-label min-w-0 flex-1">@foreach ($headerLines as $line)<span>{{ $line }} </span>@endforeach</span>
                                <button type="button" data-table-column-trigger data-column="{{ $loop->index }}"
                                    class="js-inbox-column-filter conciliation-column-filter"
                                    title="Filtrar {{ $label }}" aria-label="Filtrar {{ $label }}" aria-expanded="false">
                                    <span aria-hidden="true">&#9660;</span>
                                </button>
                            </div>
                        </th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($submission = $row['submission'])
                        @php($mixture = $row['mixture'])
                        <tr>
                            <td data-column-filter-value="{{ $row['cells']['type'] }}">@if ($mixture) @include('admin.solicitudes._type-badge', ['type' => $mixture['kind']]) @else — @endif</td>
                            <td data-column-filter-value="{{ $row['cells']['patient'] }}">{{ $row['cells']['patient'] }}</td>
                            <td class="ht-nowrap" data-column-filter-value="{{ $row['cells']['date'] }}">{{ $row['cells']['date'] }}</td>
                            <td data-column-filter-value="{{ $row['cells']['view'] }}">@if ($mixture && $mixture['url'])<a class="ht-view" href="{{ $mixture['url'] }}" target="_blank" rel="noopener" aria-label="Ver mezcla {{ $mixture['id'] }}">Ver</a>@else — @endif</td>
                            <td class="ht-nowrap" data-column-filter-value="{{ $row['cells']['remision'] }}">{{ $row['cells']['remision'] }}</td>
                            <td data-column-filter-value="{{ $row['cells']['price'] }}">
                                @if ($mixture && $mixture['can_edit_price'])
                                    <input type="text" inputmode="decimal" data-inbox-price maxlength="20" placeholder="Sin registrar"
                                        value="{{ $mixture['current_price'] }}" data-saved-value="{{ $mixture['current_price'] }}"
                                        data-previous="{{ $mixture['previous_price'] }}" data-mixture="{{ $mixture['kind'] }}-{{ $mixture['id'] }}"
                                        data-url="{{ $mixture['price_url'] }}"
                                        aria-label="Precio de venta total: {{ $row['cells']['patient'] }} · {{ $mixture['reference'] }} · mezcla {{ $mixture['id'] }}">
                                @else {{ $row['cells']['price'] }} @endif
                            </td>
                            <td data-column-filter-value="{{ $row['cells']['conciliable'] }}">
                                @if ($mixture && $mixture['url'])
                                    <div class="inbox-conciliable-toggle" role="group"
                                        aria-label="Conciliable: {{ $mixture['cells']['patient'] ?? 'mezcla' }} · {{ $mixture['reference'] }} · mezcla {{ $mixture['id'] }}">
                                        <input type="checkbox" hidden data-inbox-conciliable data-mixture="{{ $mixture['kind'] }}-{{ $mixture['id'] }}"
                                            data-url="{{ $mixture['conciliable_url'] }}"
                                            data-previous="{{ $mixture['previous_conciliable'] }}" @checked($mixture['current_conciliable'])>
                                        <button type="button" data-inbox-conciliable-choice="1" aria-pressed="{{ $mixture['current_conciliable'] ? 'true' : 'false' }}">Sí</button>
                                        <button type="button" data-inbox-conciliable-choice="0" aria-pressed="{{ $mixture['current_conciliable'] ? 'false' : 'true' }}">No</button>
                                    </div>
                                @else — @endif
                            </td>
                            <td data-column-filter-value="{{ $row['cells']['conciliation_status'] }}" data-inbox-conciliation-status>
                                <span class="inbox-conciliation-status" data-status="{{ $row['cells']['conciliation_status'] }}">{{ $row['cells']['conciliation_status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count(\App\Support\ConciliationInboxTable::COLUMNS) }}" class="ht-empty">No hay solicitudes de conciliación{{ $activeTab !== 'todas' || $search || $institutionId || $hospitalId || $table['selected'] ? ' para los filtros seleccionados' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="conciliation-inbox-total conciliation-footer"><p>Mostrando {{ $rows->count() }} solicitudes en una sola página</p></div>
    </section>
    <script>
        @include('admin.catalogo-listas.partials.column-filter-script')

        function initConciliationColumnFilters() {
            const table = document.getElementById('conciliation-inbox-table');
            if (!table || table.dataset.inboxFiltersReady === 'true') return;
            const config = JSON.parse(document.getElementById('conciliation-inbox-filters').textContent);
            window.createExcelColumnFilters({
                tableId: table.id,
                rowSelector: 'tbody tr:not(:has(td[colspan]))',
                triggerSelector: '.js-inbox-column-filter',
                instanceId: 'conciliation-inbox',
                valuesByColumn: Object.fromEntries(config.columns.map((field, index) => [index, config.options[field]])),
                selectedByColumn: Object.fromEntries(Object.entries(config.selected).map(([field, values]) => [config.columns.indexOf(field), values])),
                cellValue: (row, index) => row.cells[index]?.dataset.columnFilterValue || '',
                onAccept(filters) {
                    const url = new URL(config.url, window.location.href);
                    url.searchParams.delete('page');
                    [...url.searchParams.keys()].filter(key => key.startsWith('columnas[')).forEach(key => url.searchParams.delete(key));
                    filters.forEach((values, index) => {
                        (values.size ? [...values] : ['']).forEach(value => url.searchParams.append(`columnas[${config.columns[index]}][]`, value));
                    });
                    window.location.assign(url.href);
                },
            });
            table.dataset.inboxFiltersReady = 'true';
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initConciliationColumnFilters, { once: true });
        else initConciliationColumnFilters();
        if (!window.conciliationFilterEventsReady) {
            document.addEventListener('livewire:navigated', initConciliationColumnFilters);
            document.addEventListener('livewire:navigating', () => {
                window.__excelColumnFilterInstances?.['conciliation-inbox']?.destroy();
                const table = document.getElementById('conciliation-inbox-table');
                if (table) delete table.dataset.inboxFiltersReady;
            });
            window.conciliationFilterEventsReady = true;
        }
    </script>
</x-admin-layout>
