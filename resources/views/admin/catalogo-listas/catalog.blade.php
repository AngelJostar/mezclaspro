@php
    $showCategoryColumn = $category === 'todos';
    $columnOffset = $showCategoryColumn ? 1 : 0;
    $isSuppliesCatalog = $category === 'insumos';
    $canEditGenericMedication = auth()->user()?->hasRole('Super Admin');
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
                    Catalogo - {{ $categories[$category]['label'] }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $isSuppliesCatalog
                        ? 'Consulta los insumos registrados por central y subalmacen de insumos.'
                        : 'Consulta productos, presentaciones y precios de compra registrados.' }}
                </p>
            </div>

            <div class="flex w-full items-center gap-2 md:w-auto">
                <input type="search" id="catalogSearch"
                    class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm md:w-80"
                    placeholder="{{ $isSuppliesCatalog ? 'Buscar insumo...' : 'Buscar producto...' }}">

                @unless ($showCategoryColumn)
                    <x-table-action-link href="{{ route('admin.catalogo-listas.products.create', ['category' => $category]) }}" variant="green" icon="fa-solid fa-plus"
                        class="shrink-0">
                        {{ $isSuppliesCatalog ? 'Nuevo insumo' : 'Nuevo producto' }}
                    </x-table-action-link>
                @endunless
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
            @if ($isSuppliesCatalog)
                <table class="min-w-[1550px] divide-y divide-gray-200 text-xs" id="catalogTable">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <x-filterable-table-header column="0" trigger-class="js-catalog-column-filter">Insumo</x-filterable-table-header>
                            <x-filterable-table-header column="1" trigger-class="js-catalog-column-filter" align="center">Volumen</x-filterable-table-header>
                            <x-filterable-table-header column="2" trigger-class="js-catalog-column-filter">Presentacion</x-filterable-table-header>
                            <x-filterable-table-header column="3" trigger-class="js-catalog-column-filter">Nombre comercial</x-filterable-table-header>
                            <x-filterable-table-header column="4" trigger-class="js-catalog-column-filter">Fabricante</x-filterable-table-header>
                            <x-filterable-table-header column="5" trigger-class="js-catalog-column-filter">Central</x-filterable-table-header>
                            <x-filterable-table-header column="6" trigger-class="js-catalog-column-filter">Subalmacen de insumos</x-filterable-table-header>
                            <x-filterable-table-header column="7" trigger-class="js-catalog-column-filter">Lote</x-filterable-table-header>
                            <x-filterable-table-header column="8" trigger-class="js-catalog-column-filter" align="center">Caducidad</x-filterable-table-header>
                            <x-filterable-table-header column="9" trigger-class="js-catalog-column-filter" align="right">Existencia</x-filterable-table-header>
                            <x-filterable-table-header column="10" trigger-class="js-catalog-column-filter" align="center">Estado</x-filterable-table-header>
                            @if ($canEditGenericMedication)
                                <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">Editar</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($rows as $row)
                            <tr class="catalog-row hover:bg-gray-50"
                                data-search="{{ Str::lower(($row->product ?? '') . ' ' . ($row->dose ?? '') . ' ' . ($row->presentation ?? '') . ' ' . ($row->commercial_name ?? '') . ' ' . ($row->manufacturer ?? '') . ' ' . ($row->central ?? '') . ' ' . ($row->warehouse ?? '') . ' ' . ($row->lot ?? '')) }}">
                                <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">{{ $row->product }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-center font-semibold tabular-nums text-gray-700">{{ $row->dose }}</td>
                                <td class="max-w-xs px-3 py-2 text-gray-700">{{ $row->presentation ?: '-' }}</td>
                                <td class="max-w-xs px-3 py-2 text-gray-700">{{ $row->commercial_name ?: '-' }}</td>
                                <td class="max-w-xs px-3 py-2 text-gray-700">{{ $row->manufacturer ?: '-' }}</td>
                                <td class="max-w-sm px-3 py-2 text-gray-700">{{ $row->central ?: '-' }}</td>
                                <td class="max-w-xs px-3 py-2 font-semibold text-gray-700">{{ $row->warehouse ?: '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-700">{{ $row->lot ?: '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-center text-gray-600">
                                    {{ $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums text-gray-700">
                                    {{ number_format((float) $row->stock_actual, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-center">
                                    @if ($row->is_active)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700">Activo</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[11px] font-bold text-gray-600">Inactivo</span>
                                    @endif
                                </td>
                                @if ($canEditGenericMedication)
                                    <td class="px-3 py-2 text-center">
                                        @if ($row->edit_url !== '#')
                                            <x-table-action-link href="{{ $row->edit_url }}" icon="fa-solid fa-pen">
                                                Editar
                                            </x-table-action-link>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 11 + ($canEditGenericMedication ? 1 : 0) }}" class="px-3 py-8 text-center text-sm text-gray-500">
                                    No hay insumos registrados para esta categoria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
            <table class="min-w-[1500px] divide-y divide-gray-200 text-xs" id="catalogTable">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        @if ($showCategoryColumn)
                            <x-filterable-table-header column="0" trigger-class="js-catalog-column-filter">Categoría</x-filterable-table-header>
                        @endif
                        <x-filterable-table-header :column="$columnOffset" trigger-class="js-catalog-column-filter">Producto</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 1" trigger-class="js-catalog-column-filter" align="center">Dosis</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 2" trigger-class="js-catalog-column-filter">Presentacion</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 3" trigger-class="js-catalog-column-filter">Denominacion Comercial</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 4" trigger-class="js-catalog-column-filter" align="center">Estabilidad Reconstituido</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 5" trigger-class="js-catalog-column-filter" align="right">Precio compra mas bajo</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 6" trigger-class="js-catalog-column-filter" align="center">Fecha</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 7" trigger-class="js-catalog-column-filter" align="right">Ultimo precio de compra</x-filterable-table-header>
                        <x-filterable-table-header :column="$columnOffset + 8" trigger-class="js-catalog-column-filter" align="center">Fecha</x-filterable-table-header>
                        @if ($canEditGenericMedication)
                            <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">Editar</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($rows as $row)
                        <tr class="catalog-row hover:bg-gray-50"
                            data-search="{{ Str::lower(($row->category_label ?? '') . ' ' . ($row->product ?? '') . ' ' . ($row->presentation ?? '') . ' ' . ($row->commercial_name ?? '') . ' ' . ($row->stability_hours ?? 'sin capturar')) }}">
                            @if ($showCategoryColumn)
                                <td class="whitespace-nowrap px-3 py-2">
                                    <span @class([
                                        'inline-flex rounded border px-2 py-1 font-semibold',
                                        'border-indigo-200 bg-indigo-50 text-indigo-700' => $row->category_key === 'oncologicos',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $row->category_key === 'nutricionales',
                                        'border-rose-200 bg-rose-50 text-rose-700' => $row->category_key === 'antibioticos',
                                        'border-amber-200 bg-amber-50 text-amber-700' => $row->category_key === 'insumos',
                                    ])>
                                        {{ $row->category_label }}
                                    </span>
                                </td>
                            @endif
                            <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">
                                {{ $row->product }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center font-semibold tabular-nums text-gray-700">
                                {{ $row->dose }}
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">
                                {{ $row->presentation ?: '-' }}
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">
                                {{ $row->commercial_name ?: '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center font-semibold tabular-nums">
                                @if ($row->stability_hours !== null && (int) $row->stability_hours > 0)
                                    <span class="text-gray-700">{{ number_format((int) $row->stability_hours) }} h</span>
                                @else
                                    <span class="inline-flex rounded border border-amber-200 bg-amber-50 px-2 py-1 text-amber-700">
                                        Sin capturar
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                {{ $row->lowest_price !== null ? '$' . number_format((float) $row->lowest_price, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-center text-gray-600">
                                {{ $row->lowest_date ? \Carbon\Carbon::parse($row->lowest_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                {{ $row->last_price !== null ? '$' . number_format((float) $row->last_price, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-center text-gray-600">
                                {{ $row->last_date ? \Carbon\Carbon::parse($row->last_date)->format('d/m/Y') : '-' }}
                            </td>
                            @if ($canEditGenericMedication)
                                <td class="px-3 py-2 text-center">
                                    @if ($row->edit_url !== '#')
                                        <x-table-action-link href="{{ $row->edit_url }}" icon="fa-solid fa-pen">
                                            Editar
                                        </x-table-action-link>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($showCategoryColumn ? 10 : 9) + ($canEditGenericMedication ? 1 : 0) }}" class="px-3 py-8 text-center text-sm text-gray-500">
                                No hay productos registrados para esta categoria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('catalogSearch');
                const rows = Array.from(document.querySelectorAll('.catalog-row'));

                const normalize = (value) => String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                const applyFilters = () => {
                    const term = normalize(input?.value);

                    rows.forEach((row) => {
                        const matchesSearch = term === '' || normalize(row.dataset.search).includes(term);
                        const matchesColumns = row.dataset.columnFilterMatch !== '0';

                        row.classList.toggle('hidden', !matchesSearch || !matchesColumns);
                    });
                };

                window.createExcelColumnFilters({
                    tableId: 'catalogTable',
                    rowSelector: '.catalog-row',
                    triggerSelector: '.js-catalog-column-filter',
                    instanceId: 'catalog',
                    onChange: applyFilters,
                });

                input?.addEventListener('input', applyFilters);
                applyFilters();
            });
        </script>
    @endpush
</x-admin-layout>
