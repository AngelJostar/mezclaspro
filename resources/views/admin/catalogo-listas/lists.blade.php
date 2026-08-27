@php
    $showCategoryColumn = $category === 'todos';
    $columnOffset = $showCategoryColumn ? 1 : 0;
    $createCategory = $showCategoryColumn ? 'oncologicos' : $category;
@endphp

<x-admin-layout>
    <div class="rounded-xl bg-white p-5 shadow-sm">
        @include('admin.catalogo-listas.partials.section-nav', [
            'categories' => $categories,
            'category' => $category,
            'mode' => $mode,
        ])

        <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Listas de precios - {{ $categories[$category]['label'] }}
                </h2>
                <p class="text-sm text-gray-500">Revisa las listas y sus asignaciones.</p>
            </div>

            <x-table-action-link href="{{ route('admin.catalogo-listas.lists.create', ['category' => $createCategory]) }}"
                variant="green" icon="fa-solid fa-plus">
                Crear nueva lista de precios
            </x-table-action-link>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
            <input type="search" id="priceListSearch"
                class="rounded-lg border-gray-300 text-sm"
                placeholder="Buscar lista...">

            <select id="priceListStatus" class="rounded-lg border-gray-300 text-sm">
                <option value="">Todos los estados</option>
                <option value="asignada">Asignadas</option>
                <option value="sin-asignar">Sin asignar</option>
            </select>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
            <table id="priceListsTable" class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <x-filterable-table-header column="0" trigger-class="js-price-list-column-filter">Nombre de la lista</x-filterable-table-header>
                        @if ($showCategoryColumn)
                            <x-filterable-table-header column="1" trigger-class="js-price-list-column-filter">Categoría</x-filterable-table-header>
                        @endif
                        <x-filterable-table-header :column="1 + $columnOffset" trigger-class="js-price-list-column-filter">Tipo</x-filterable-table-header>
                        <x-filterable-table-header :column="2 + $columnOffset" trigger-class="js-price-list-column-filter">Central</x-filterable-table-header>
                        <x-filterable-table-header :column="3 + $columnOffset" trigger-class="js-price-list-column-filter">Almacén</x-filterable-table-header>
                        <x-filterable-table-header :column="4 + $columnOffset" trigger-class="js-price-list-column-filter">Instituciones asignadas</x-filterable-table-header>
                        <x-filterable-table-header :column="5 + $columnOffset" trigger-class="js-price-list-column-filter">Hospitales asignados</x-filterable-table-header>
                        <x-filterable-table-header :column="6 + $columnOffset" trigger-class="js-price-list-column-filter" align="center">Fecha de creacion</x-filterable-table-header>
                        <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">Ver</th>
                        <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">Editar</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($rows as $row)
                        @php
                            $institutionsText = $row->institutions->isNotEmpty()
                                ? $row->institutions->implode(', ')
                                : '-';
                            $hospitalsText = $row->hospitals->isNotEmpty()
                                ? $row->hospitals->implode(', ')
                                : '-';
                            $assignedState = $row->institutions->isNotEmpty() || $row->hospitals->isNotEmpty()
                                ? 'asignada'
                                : 'sin-asignar';
                        @endphp

                        <tr class="price-list-row hover:bg-gray-50"
                            data-state="{{ $assignedState }}"
                            data-search="{{ Str::lower(($row->category_label ?? '') . ' ' . $row->name . ' ' . $row->central . ' ' . $row->warehouse . ' ' . $institutionsText . ' ' . $hospitalsText) }}">
                            <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">
                                {{ $row->name }}
                            </td>
                            @if ($showCategoryColumn)
                                <td class="whitespace-nowrap px-3 py-2">
                                    <span @class([
                                        'inline-flex rounded border px-2 py-1 font-semibold',
                                        'border-indigo-200 bg-indigo-50 text-indigo-700' => $row->category_key === 'oncologicos',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $row->category_key === 'nutricionales',
                                        'border-rose-200 bg-rose-50 text-rose-700' => $row->category_key === 'antibioticos',
                                    ])>
                                        {{ $row->category_label }}
                                    </span>
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-3 py-2">
                                <span class="inline-flex rounded px-2 py-1 font-semibold {{ $row->is_backup ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $row->is_backup ? 'Respaldo' : 'Principal' }}
                                </span>
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">
                                {{ $row->central }}
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">
                                <span class="font-semibold text-gray-900">{{ $row->warehouse }}</span>
                                @if ($row->is_backup && $row->primary_warehouse)
                                    <span class="mt-0.5 block text-[11px] text-gray-500">Principal: {{ $row->primary_warehouse }}</span>
                                @elseif ($row->backup_warehouse)
                                    <span class="mt-0.5 block text-[11px] text-gray-500">Respaldo: {{ $row->backup_warehouse }}</span>
                                @endif
                            </td>
                            <td class="max-w-sm px-3 py-2 text-gray-700">
                                {{ $institutionsText }}
                            </td>
                            <td class="max-w-sm px-3 py-2 text-gray-700">
                                {{ $hospitalsText }}
                            </td>
                            <td class="px-3 py-2 text-center text-gray-600">
                                {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <x-table-action-link href="{{ $row->view_url }}" variant="gray" icon="fa-solid fa-eye">
                                    Ver
                                </x-table-action-link>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <x-table-action-link href="{{ $row->edit_url }}" icon="fa-solid fa-pen">
                                    Editar
                                </x-table-action-link>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showCategoryColumn ? 10 : 9 }}" class="px-3 py-8 text-center text-sm text-gray-500">
                                No hay listas de precios registradas para esta categoria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('priceListSearch');
                const select = document.getElementById('priceListStatus');
                const rows = Array.from(document.querySelectorAll('.price-list-row'));

                const normalize = (value) => String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                const applyFilters = () => {
                    const term = normalize(input?.value);
                    const state = select?.value || '';

                    rows.forEach((row) => {
                        const matchesTerm = term === '' || normalize(row.dataset.search).includes(term);
                        const matchesState = state === '' || row.dataset.state === state;
                        const matchesColumns = row.dataset.columnFilterMatch !== '0';

                        row.classList.toggle('hidden', !matchesTerm || !matchesState || !matchesColumns);
                    });
                };

                window.createExcelColumnFilters({
                    tableId: 'priceListsTable',
                    rowSelector: '.price-list-row',
                    triggerSelector: '.js-price-list-column-filter',
                    instanceId: 'price-lists',
                    onChange: applyFilters,
                });

                input?.addEventListener('input', applyFilters);
                select?.addEventListener('change', applyFilters);
                applyFilters();
            });
        </script>
    @endpush
</x-admin-layout>
