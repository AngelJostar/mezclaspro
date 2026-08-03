<x-admin-layout>
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Nueva lista nutricional</h1>

    <form action="{{ route('admin.nutricionales.nutri-medicine-lists.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded border-gray-300"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activa</label>
                    <select name="is_active" class="w-full rounded border-gray-300">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center">
                <input type="hidden" name="active_brands" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="active_brands" value="1" class="sr-only peer"
                        {{ old('active_brands', false) ? 'checked' : '' }}>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                               after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                               after:bg-white after:border-gray-300 after:border after:rounded-full
                               after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Activar marcas en documentos</span>
                </label>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full rounded border-gray-300">{{ old('description') }}</textarea>
            </div>

            <div class="mt-6 border rounded-lg p-4 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-800">Distribuidor</h2>
                <p class="text-xs text-gray-500 mb-4">
                    Opcional. Estos datos se usarán para la segunda hoja de remisión nutricional.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del distribuidor</label>
                        <input type="text" name="distributor_name" value="{{ old('distributor_name') }}"
                            class="w-full rounded border-gray-300" placeholder="Ej. Distribuidora ABC">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="distributor_address" value="{{ old('distributor_address') }}"
                            class="w-full rounded border-gray-300" placeholder="Calle, número, colonia, ciudad">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo (opcional)</label>
                        <input type="file" name="distributor_logo" accept="image/*"
                            class="w-full rounded border-gray-300 bg-white">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Presentaciones y precios</h2>

            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
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
                <div id="medicineListSearchCount" class="text-xs text-gray-500 mt-2"></div>
            </div>

            @php
                $rowIndex = 0;

                $catalogsByCategory = $catalogs->groupBy(function ($catalog) {
                    return $catalog->category->name ?? 'Sin categoría';
                });
            @endphp

            <div class="space-y-6">
                @foreach ($catalogsByCategory as $categoryName => $categoryCatalogs)
                    <div class="border border-gray-200 rounded-lg overflow-hidden medicine-category">
                        <div class="bg-blue-50 px-4 py-3 border-b">
                            <h3 class="text-base font-bold text-blue-800 uppercase">
                                {{ $categoryName }}
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-200">
                            @foreach ($categoryCatalogs as $catalog)
                                <div class="p-4 medicine-catalog"
                                    data-search="{{ Str::lower(($catalog->denominacion_generica ?? '') . ' ' . ($catalog->input->description ?? '') . ' ' . $catalog->presentations->pluck('denominacion_comercial')->implode(' ') . ' ' . $catalog->presentations->pluck('presentacion')->implode(' ')) }}">
                                    <div class="mb-3">
                                        <div class="text-sm text-gray-500">
                                            Medicamento genérico
                                        </div>

                                        <h4 class="text-lg font-semibold text-gray-800">
                                            {{ $catalog->denominacion_generica }}
                                        </h4>

                                        <div class="text-xs text-gray-500">
                                            Input: {{ $catalog->input->description ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border border-gray-200 rounded-lg">
                                            <thead class="bg-gray-100">
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

                                            <tbody>
                                                @forelse ($catalog->presentations as $presentation)
                                                    <tr class="border-b last:border-b-0 medicine-presentation-row"
                                                        data-search="{{ Str::lower(($catalog->denominacion_generica ?? '') . ' ' . ($catalog->input->description ?? '') . ' ' . ($presentation->denominacion_comercial ?? '') . ' ' . ($presentation->presentacion ?? '') . ' ' . ($presentation->presentacion_ml ?? '')) }}">
                                                        <td class="px-4 py-3 text-sm text-gray-800">
                                                            <input type="hidden"
                                                                name="items[{{ $rowIndex }}][nutrition_medicine_presentation_id]"
                                                                value="{{ $presentation->id }}">

                                                            <div class="font-medium">
                                                                {{ $presentation->denominacion_comercial }}
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-3 text-sm text-gray-800">
                                                            {{ $presentation->presentacion ?? '—' }}
                                                        </td>

                                                        <td class="px-4 py-3 text-sm text-gray-800">
                                                            {{ $presentation->presentacion_ml ?? '—' }} ml
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <input type="number" step="0.0001" min="0"
                                                                name="items[{{ $rowIndex }}][precio_ml]"
                                                                value="{{ old('items.' . $rowIndex . '.precio_ml', '') }}"
                                                                class="w-full rounded border-gray-300" required>
                                                        </td>
                                                    </tr>

                                                    @php
                                                        $rowIndex++;
                                                    @endphp
                                                @empty
                                                    <tr>
                                                        <td colspan="4"
                                                            class="px-4 py-4 text-center text-sm text-gray-500">
                                                            Este medicamento no tiene presentaciones registradas.
                                                        </td>
                                                    </tr>
                                                @endforelse
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
                No se encontraron presentaciones con esa inicial.
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                Guardar
            </button>
        </div>
    </form>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
                    const title = catalog.querySelector('h4');
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
                    let visibleCatalogs = 0;
                    let visibleRows = 0;

                    catalogs.forEach((catalog) => {
                        const rows = Array.from(catalog.querySelectorAll('.medicine-presentation-row'));
                        const firstLetter = catalogName(catalog).charAt(0).toUpperCase();
                        const alphaVisible = selectedAlpha === '' || firstLetter === selectedAlpha;

                        rows.forEach((row) => {
                            row.classList.toggle('hidden', !alphaVisible);

                            if (alphaVisible) {
                                visibleRows++;
                            }
                        });

                        catalog.classList.toggle('hidden', !alphaVisible);

                        if (alphaVisible) {
                            visibleCatalogs++;
                        }
                    });

                    categories.forEach((category) => {
                        const hasVisibleCatalogs = Array.from(category.querySelectorAll('.medicine-catalog'))
                            .some((catalog) => !catalog.classList.contains('hidden'));

                        category.classList.toggle('hidden', !hasVisibleCatalogs);
                    });

                    noResults.classList.toggle('hidden', visibleCatalogs > 0 || selectedAlpha === '');
                    count.textContent = selectedAlpha ? `${visibleRows} presentacion(es) con inicial ${selectedAlpha}.` : '';
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

                sortCatalogsAlphabetically();
            });
        </script>
    @endpush
</x-admin-layout>
