<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                Ingreso de inventario oncológico
            </h1>

            <div class="text-sm text-gray-600 mt-1">
                Laboratorio:
                <span class="font-semibold text-gray-800">
                    {{ $laboratory->nombre }}
                </span>
            </div>
        </div>

        <a href="{{ route('admin.oncologicos.inventory.index', ['laboratory_id' => $laboratoryId]) }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.oncologicos.inventory.registrarIngreso') }}"
        class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">

        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded p-3 text-sm">
            Si el lote ya existe para la misma presentación, se sumarán los frascos al inventario existente.
            Si el lote ya está registrado en otra presentación, el sistema no permitirá guardarlo.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Medicamento
                </label>

                <select id="catalog-select" class="w-full rounded border-gray-300">
                    <option value="">Seleccionar...</option>

                    @foreach ($catalogs as $catalogRows)
                        @php
                            $catalog = $catalogRows->first();
                        @endphp

                        <option value="{{ $catalog->catalog_id }}">
                            {{ $catalog->denominacion }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Presentación
                </label>

                <select id="presentation-select" name="medicine_presentation_id"
                    class="w-full rounded border-gray-300" required>
                    <option value="">Seleccionar...</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lote
                </label>

                <input type="text" name="lote" value="{{ old('lote') }}"
                    class="w-full rounded border-gray-300" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Caducidad
                </label>

                <input type="date" name="caducidad" value="{{ old('caducidad') }}"
                    class="w-full rounded border-gray-300" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Frascos ingresados
                </label>

                <input type="number" step="1" min="1" name="frascos_ingresados" id="frascos-input"
                    value="{{ old('frascos_ingresados') }}"
                    class="w-full rounded border-gray-300" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contenido por frasco
                </label>

                <input type="text" id="contenido-por-frasco"
                    class="w-full rounded border-gray-200 bg-gray-100" readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Total de unidades
                </label>

                <input type="text" id="contenido-total"
                    class="w-full rounded border-gray-200 bg-gray-100" readonly>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Fecha ingreso
            </label>

            <input type="date" name="fecha_ingreso"
                value="{{ old('fecha_ingreso', now()->format('Y-m-d')) }}"
                class="w-full rounded border-gray-300">
        </div>

        <div class="flex between items-center gap-4">
            <div class="w-1/2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Número de factura
                </label>

                <input type="text" name="numero_factura" value="{{ old('numero_factura') }}"
                    class="w-full rounded border-gray-300">
            </div>

            <div class="w-1/2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Notas
                </label>

                <textarea name="notes" rows="3" class="w-full rounded border-gray-300">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                Registrar ingreso
            </button>
        </div>
    </form>

    @push('js')
        <script>
            const catalogs = @json($catalogs);
            const preselectedPresentationId = "{{ request('presentation_id') }}";

            const catalogSelect = document.getElementById('catalog-select');
            const presentationSelect = document.getElementById('presentation-select');

            const contenidoPorFrascoInput = document.getElementById('contenido-por-frasco');
            const frascosInput = document.getElementById('frascos-input');
            const contenidoTotalInput = document.getElementById('contenido-total');

            function normalizarCatalogs(rawCatalogs) {
                return Object.values(rawCatalogs).map(rows => {
                    const first = rows[0];

                    return {
                        id: first.catalog_id,
                        denominacion: first.denominacion,
                        presentations: rows
                            .filter((row, index, self) =>
                                index === self.findIndex(r => r.presentation_id === row.presentation_id)
                            )
                            .map(row => ({
                                id: row.presentation_id,
                                presentacion: row.presentacion,
                                marca: row.marca,
                                fabricante: row.fabricante,
                                contenido_valor: row.contenido_valor,
                                contenido_unidad: row.contenido_unidad
                            }))
                    };
                });
            }

            const normalizedCatalogs = normalizarCatalogs(catalogs);

            function cargarPresentaciones(catalogId, selectedPresentationId = null) {
                const catalog = normalizedCatalogs.find(c => c.id == catalogId);

                presentationSelect.innerHTML = '<option value="">Seleccionar...</option>';
                contenidoPorFrascoInput.value = '';
                contenidoTotalInput.value = '';

                if (!catalog) return;

                catalog.presentations.forEach(p => {
                    const selected = selectedPresentationId && p.id == selectedPresentationId ? 'selected' : '';

                    const contenido = p.contenido_valor
                        ? `${p.contenido_valor} ${p.contenido_unidad ?? ''}`
                        : '';

                    presentationSelect.innerHTML += `
                        <option
                            value="${p.id}"
                            data-contenido-valor="${p.contenido_valor ?? 0}"
                            data-contenido-unidad="${p.contenido_unidad ?? ''}"
                            ${selected}
                        >
                            ${p.presentacion ?? ''}
                            ${p.marca ? ' - ' + p.marca : ''}
                            ${p.fabricante ? ' - ' + p.fabricante : ''}
                            ${contenido ? ' - ' + contenido : ''}
                        </option>
                    `;
                });

                if (selectedPresentationId) {
                    actualizarPresentacion();
                }
            }

            function actualizarPresentacion() {
                const option = presentationSelect.selectedOptions[0];

                const valor = parseFloat(option?.dataset?.contenidoValor || 0);
                const unidad = option?.dataset?.contenidoUnidad || '';

                contenidoPorFrascoInput.value = valor ? `${valor} ${unidad}` : '';
                actualizarTotal();
            }

            function actualizarTotal() {
                const frascos = parseFloat(frascosInput.value) || 0;
                const option = presentationSelect.selectedOptions[0];

                const valor = parseFloat(option?.dataset?.contenidoValor || 0);
                const unidad = option?.dataset?.contenidoUnidad || '';

                contenidoTotalInput.value = valor
                    ? `${(frascos * valor).toFixed(2)} ${unidad}`
                    : '';
            }

            catalogSelect.addEventListener('change', () => {
                cargarPresentaciones(catalogSelect.value);
            });

            presentationSelect.addEventListener('change', actualizarPresentacion);
            frascosInput.addEventListener('input', actualizarTotal);

            function precargarDesdeIndex() {
                if (!preselectedPresentationId) return;

                const catalog = normalizedCatalogs.find(c =>
                    c.presentations.some(p => p.id == preselectedPresentationId)
                );

                if (!catalog) return;

                catalogSelect.value = catalog.id;
                cargarPresentaciones(catalog.id, preselectedPresentationId);
            }

            precargarDesdeIndex();
        </script>
    @endpush
</x-admin-layout>
