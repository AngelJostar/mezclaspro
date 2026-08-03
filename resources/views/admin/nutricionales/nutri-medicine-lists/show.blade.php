<x-admin-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">{{ $nutriMedicineList->name }}</h1>
        <p class="text-gray-600">{{ $nutriMedicineList->description }}</p>
        <p class="mt-2 text-sm text-gray-600">
            Marcas en documentos:
            <span class="font-semibold {{ $nutriMedicineList->active_brands ? 'text-green-700' : 'text-gray-500' }}">
                {{ $nutriMedicineList->active_brands ? 'Activas' : 'Inactivas' }}
            </span>
        </p>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Presentaciones y precios</h2>
                <p class="text-sm text-gray-500">
                    Busca por medicamento, presentacion comercial, presentacion o input.
                </p>
            </div>

            <div class="w-full md:w-96">
                <label for="medicineListSearch" class="block text-sm font-medium text-gray-700 mb-1">
                    Buscar
                </label>
                <div class="flex gap-2">
                    <input type="search" id="medicineListSearch"
                        class="w-full rounded border-gray-300"
                        placeholder="Ej. glucosa, aminoacidos, bolsa...">

                    <button type="button" id="clearMedicineListSearch"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm">
                        Limpiar
                    </button>
                </div>
                <div id="medicineListSearchCount" class="text-xs text-gray-500 mt-1"></div>
            </div>
        </div>

        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase text-gray-500">Filtrar por inicial</div>
                    <div class="mt-2 flex flex-wrap gap-1 price-list-alpha-filter">
                        <button type="button" data-alpha=""
                            class="alpha-filter-btn rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">Todos</button>
                        @foreach (range('A', 'Z') as $letter)
                            <button type="button" data-alpha="{{ $letter }}"
                                class="alpha-filter-btn rounded bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100">{{ $letter }}</button>
                        @endforeach
                    </div>
                </div>

                <button type="button" id="btn-sort-alpha"
                    class="rounded bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-900">
                    Ordenar A-Z
                </button>
            </div>
        </div>
    </div>

    @php
        $itemsByCategory = $nutriMedicineList->items->groupBy(function ($item) {
            return $item->presentation->catalog->category->name ?? 'Sin categoría';
        });
    @endphp

    <div class="space-y-6">
        @foreach ($itemsByCategory as $categoryName => $categoryItems)
            <div class="bg-white shadow rounded-lg overflow-hidden medicine-category">
                <div class="bg-blue-50 px-4 py-3 border-b">
                    <h2 class="text-base font-bold text-blue-800 uppercase">
                        {{ $categoryName }}
                    </h2>
                </div>

                @php
                    $itemsByGeneric = $categoryItems->groupBy(function ($item) {
                        return $item->presentation->catalog->id;
                    });
                @endphp

                <div class="divide-y divide-gray-200">
                    @foreach ($itemsByGeneric as $genericItems)
                        @php
                            $firstItem = $genericItems->first();
                            $catalog = $firstItem->presentation->catalog;
                        @endphp

                        <div class="p-4 medicine-catalog"
                            data-search="{{ Str::lower(($catalog->denominacion_generica ?? '') . ' ' . ($catalog->input->description ?? '') . ' ' . $genericItems->map(fn ($item) => ($item->presentation->denominacion_comercial ?? '') . ' ' . ($item->presentation->presentacion ?? '') . ' ' . ($item->presentation->presentacion_ml ?? ''))->implode(' ')) }}">
                            <div class="mb-4">
                                <div class="text-sm text-gray-500">
                                    Medicamento genérico
                                </div>

                                <h3 class="text-lg font-semibold text-gray-800">
                                    {{ $catalog->denominacion_generica ?? '—' }}
                                </h3>

                                <div class="text-xs text-gray-500">
                                    Input:
                                    {{ $catalog->input->description ?? '—' }}
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full border border-gray-200 rounded-lg">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">
                                                Presentación comercial
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">
                                                Presentación
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">
                                                ML
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">
                                                Precio por ml
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($genericItems as $item)
                                            <tr class="medicine-presentation-row"
                                                data-search="{{ Str::lower(($catalog->denominacion_generica ?? '') . ' ' . ($catalog->input->description ?? '') . ' ' . ($item->presentation->denominacion_comercial ?? '') . ' ' . ($item->presentation->presentacion ?? '') . ' ' . ($item->presentation->presentacion_ml ?? '')) }}">
                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                    <div class="font-medium">
                                                        {{ $item->presentation->denominacion_comercial ?? '—' }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                    {{ $item->presentation->presentacion ?? '—' }}
                                                </td>

                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                    {{ $item->presentation->presentacion_ml ?? '—' }} ml
                                                </td>

                                                <td class="px-4 py-3 text-sm font-semibold text-green-700">
                                                    ${{ number_format($item->precio_ml, 4) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div id="medicineListNoResults"
        class="hidden mt-6 rounded border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
        No se encontraron presentaciones con esa busqueda.
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('medicineListSearch');
                const clear = document.getElementById('clearMedicineListSearch');
                const count = document.getElementById('medicineListSearchCount');
                const noResults = document.getElementById('medicineListNoResults');
                const categories = Array.from(document.querySelectorAll('.medicine-category'));
                const catalogs = Array.from(document.querySelectorAll('.medicine-catalog'));
                let selectedAlpha = '';

                const normalize = (value) => String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                const catalogName = (catalog) => {
                    const title = catalog.querySelector('h3');
                    return normalize(title?.textContent || '');
                };

                const sortCatalogsAlphabetically = () => {
                    categories.forEach((category) => {
                        const container = category.querySelector('.divide-y');

                        if (!container) {
                            return;
                        }

                        Array.from(container.querySelectorAll(':scope > .medicine-catalog'))
                            .sort((a, b) => catalogName(a).localeCompare(catalogName(b), 'es'))
                            .forEach((catalog) => container.appendChild(catalog));
                    });

                    applyFilter();
                };

                const applyFilter = () => {
                    const term = normalize(input.value);
                    let visibleCatalogs = 0;
                    let visibleRows = 0;

                    catalogs.forEach((catalog) => {
                        const rows = Array.from(catalog.querySelectorAll('.medicine-presentation-row'));
                        const catalogText = normalize(catalog.dataset.search);
                        const firstLetter = catalogName(catalog).charAt(0).toUpperCase();
                        const alphaVisible = selectedAlpha === '' || firstLetter === selectedAlpha;
                        let catalogHasVisibleRows = false;

                        rows.forEach((row) => {
                            const rowText = normalize(row.dataset.search);
                            const visible = alphaVisible && (term === '' || rowText.includes(term) || catalogText.includes(term));

                            row.classList.toggle('hidden', !visible);

                            if (visible) {
                                catalogHasVisibleRows = true;
                                visibleRows++;
                            }
                        });

                        catalog.classList.toggle('hidden', !catalogHasVisibleRows);

                        if (catalogHasVisibleRows) {
                            visibleCatalogs++;
                        }
                    });

                    categories.forEach((category) => {
                        const hasVisibleCatalogs = Array.from(category.querySelectorAll('.medicine-catalog'))
                            .some((catalog) => !catalog.classList.contains('hidden'));

                        category.classList.toggle('hidden', !hasVisibleCatalogs);
                    });

                    noResults.classList.toggle('hidden', visibleCatalogs > 0 || term === '');
                    count.textContent = term === ''
                        ? (selectedAlpha ? `${visibleRows} presentacion(es) con inicial ${selectedAlpha}.` : '')
                        : `${visibleRows} presentacion(es) encontrada(s).`;
                };

                document.querySelectorAll('.alpha-filter-btn').forEach((button) => {
                    button.addEventListener('click', function() {
                        selectedAlpha = this.dataset.alpha || '';

                        document.querySelectorAll('.alpha-filter-btn').forEach((btn) => {
                            btn.classList.remove('bg-blue-600', 'text-white');
                            btn.classList.add('bg-white', 'text-gray-700', 'ring-1', 'ring-gray-200');
                        });

                        this.classList.add('bg-blue-600', 'text-white');
                        this.classList.remove('bg-white', 'text-gray-700', 'ring-1', 'ring-gray-200');

                        applyFilter();
                    });
                });

                document.getElementById('btn-sort-alpha')?.addEventListener('click', sortCatalogsAlphabetically);

                input.addEventListener('input', applyFilter);

                clear.addEventListener('click', function() {
                    input.value = '';
                    input.focus();
                    applyFilter();
                });

                sortCatalogsAlphabetically();
            });
        </script>
    @endpush
</x-admin-layout>
