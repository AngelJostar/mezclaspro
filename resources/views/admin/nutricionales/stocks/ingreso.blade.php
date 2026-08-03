<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                Ingreso de inventario
            </h1>

            <div class="text-sm text-gray-600 mt-1">
                Laboratorio:
                <span class="font-semibold text-gray-800">
                    {{ $laboratory->nombre }}
                </span>
            </div>
        </div>

        <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $laboratoryId]) }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.nutricionales.stocks.registrarIngreso') }}"
        class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">

        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded p-3 text-sm">
            Si el lote ya existe para la misma presentación, se sumarán los frascos al inventario existente.
            Si el lote ya está registrado en otra presentación, el sistema no permitirá guardarlo.
        </div>

        {{-- SELECCIÓN --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Medicamento
                </label>

                <select id="catalog-select" class="w-full rounded border-gray-300">
                    <option value="">Seleccionar...</option>
                    @foreach ($catalogs as $catalog)
                        <option value="{{ $catalog->id }}">
                            {{ $catalog->denominacion_generica }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Presentación
                </label>

                <select id="presentation-select" name="nutrition_medicine_presentation_id"
                    class="w-full rounded border-gray-300" required>
                    <option value="">Seleccionar...</option>
                </select>
            </div>
        </div>

        {{-- LOTE --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lote
                </label>

                <input type="text" name="lote" class="w-full rounded border-gray-300" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Caducidad
                </label>

                <input type="date" name="caducidad" class="w-full rounded border-gray-300" required>
            </div>
        </div>

        {{-- CANTIDAD --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Frascos ingresados
                </label>

                <input type="number" step="0.01" min="0.01" name="frascos_ingresados" id="frascos-input"
                    class="w-full rounded border-gray-300" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    ML por frasco
                </label>

                <input type="text" id="ml-por-frasco" class="w-full rounded border-gray-200 bg-gray-100" readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Total ML
                </label>

                <input type="text" id="ml-total" class="w-full rounded border-gray-200 bg-gray-100" readonly>
            </div>
        </div>

        {{-- FECHA --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Fecha ingreso
            </label>

            <input type="date" name="fecha_ingreso" value="{{ now()->format('Y-m-d') }}"
                class="w-full rounded border-gray-300">
        </div>

        <div class="flex between items-center gap-4">
            <div class="w-1/2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Número de factura
                </label>

                <input type="text" name="numero_factura" class="w-full rounded border-gray-300">
            </div>

            {{-- NOTAS --}}
            <div class="w-1/2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Notas
                </label>

                <textarea name="notes" rows="3" class="w-full rounded border-gray-300"></textarea>
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

            const mlPorFrascoInput = document.getElementById('ml-por-frasco');
            const frascosInput = document.getElementById('frascos-input');
            const mlTotalInput = document.getElementById('ml-total');

            function cargarPresentaciones(catalogId, selectedPresentationId = null) {
                const catalog = catalogs.find(c => c.id == catalogId);

                presentationSelect.innerHTML = '<option value="">Seleccionar...</option>';
                mlPorFrascoInput.value = '';
                mlTotalInput.value = '';

                if (!catalog) return;

                catalog.presentations.forEach(p => {
                    const selected = selectedPresentationId && p.id == selectedPresentationId ? 'selected' : '';

                    presentationSelect.innerHTML += `
                        <option value="${p.id}" data-ml="${p.presentacion_ml}" ${selected}>
                            ${p.denominacion_comercial} - ${p.presentacion ?? ''}
                        </option>
                    `;
                });

                if (selectedPresentationId) {
                    actualizarPresentacion();
                }
            }

            function actualizarPresentacion() {
                const option = presentationSelect.selectedOptions[0];
                const ml = option?.dataset?.ml || 0;

                mlPorFrascoInput.value = ml;
                actualizarML();
            }

            function actualizarML() {
                const frascos = parseFloat(frascosInput.value) || 0;
                const ml = parseFloat(mlPorFrascoInput.value) || 0;

                mlTotalInput.value = (frascos * ml).toFixed(2);
            }

            catalogSelect.addEventListener('change', () => {
                cargarPresentaciones(catalogSelect.value);
            });

            presentationSelect.addEventListener('change', actualizarPresentacion);
            frascosInput.addEventListener('input', actualizarML);

            function precargarDesdeIndex() {
                if (!preselectedPresentationId) return;

                const catalog = catalogs.find(c =>
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
