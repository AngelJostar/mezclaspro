<x-admin-layout>
    <div class="mt-2 max-w-3xl">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">
            Nueva presentacion para: {{ $diluent->denominacion_generica ?? '-' }}
        </h1>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.oncologicos.diluent_presentations.store', $diluent) }}" method="POST"
            class="bg-white p-4 rounded shadow space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Laboratorio</label>
                    <select name="laboratory_id" id="laboratory_id"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                        <option value="">General / sin laboratorio</option>
                        @foreach ($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" @selected(old('laboratory_id') == $laboratory->id)>
                                {{ $laboratory->nombre }}{{ $laboratory->estado ? ' - ' . $laboratory->estado : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Presentacion</label>
                    <input type="text" name="presentacion" id="presentacion" value="{{ old('presentacion') }}" required
                        placeholder="Ej. Cloruro de sodio 500 mL"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volumen (mL)</label>
                    <input type="number" step="0.01" min="0.01" name="volume_ml" id="volume_ml" value="{{ old('volume_ml') }}"
                        required class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Denominacion comercial</label>
                    <input type="text" name="denominacion_comercial" id="denominacion_comercial" value="{{ old('denominacion_comercial') }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <input type="text" name="fabricante" id="fabricante" value="{{ old('fabricante') }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                    <input type="text" name="lote" id="lote" value="{{ old('lote') }}" list="existing_diluent_lots"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                    <datalist id="existing_diluent_lots">
                        @foreach ($existingLots as $existingLot)
                            <option value="{{ $existingLot['lote'] }}">
                                {{ $existingLot['laboratory_name'] }} - Stock {{ number_format((float) $existingLot['stock_actual'], 0) }}
                            </option>
                        @endforeach
                    </datalist>
                    <p id="existing_lot_hint" class="hidden text-xs text-blue-700 mt-1"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Caducidad</label>
                    <input type="date" name="caducidad" id="caducidad" value="{{ old('caducidad') }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso" value="{{ old('fecha_ingreso') }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock inicial</label>
                    <input type="number" step="1" min="0" name="stock_actual" value="{{ old('stock_actual', 0) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                    <p class="text-xs text-gray-500 mt-1">Cantidad disponible para este lote.</p>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="h-4 w-4"
                        {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-gray-700">Activo</label>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.oncologicos.diluent_presentations.index', $diluent) }}"
                    class="px-4 py-2 mr-2 border rounded text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar</button>
            </div>
        </form>
    </div>

    <script>
        const existingDiluentLots = @json($existingLots);

        const fields = {
            laboratory: document.getElementById('laboratory_id'),
            lote: document.getElementById('lote'),
            presentacion: document.getElementById('presentacion'),
            volume: document.getElementById('volume_ml'),
            comercial: document.getElementById('denominacion_comercial'),
            fabricante: document.getElementById('fabricante'),
            caducidad: document.getElementById('caducidad'),
            fechaIngreso: document.getElementById('fecha_ingreso'),
            hint: document.getElementById('existing_lot_hint'),
        };

        function normalize(value) {
            return String(value || '').trim().toLowerCase();
        }

        function findExistingLot() {
            const lote = normalize(fields.lote?.value);
            const laboratoryKey = String(fields.laboratory?.value || '');

            if (!lote) return null;

            return existingDiluentLots.find(item =>
                item.lote_key === lote &&
                String(item.laboratory_key || '') === laboratoryKey
            ) || existingDiluentLots.find(item => item.lote_key === lote);
        }

        function fillExistingLot() {
            const item = findExistingLot();

            if (!item) {
                fields.hint?.classList.add('hidden');
                return;
            }

            if (fields.laboratory && item.laboratory_key !== null) fields.laboratory.value = item.laboratory_key || '';
            if (fields.presentacion) fields.presentacion.value = item.presentacion || '';
            if (fields.volume) fields.volume.value = item.volume_ml || '';
            if (fields.comercial) fields.comercial.value = item.denominacion_comercial || '';
            if (fields.fabricante) fields.fabricante.value = item.fabricante || '';
            if (fields.caducidad) fields.caducidad.value = item.caducidad || '';
            if (fields.fechaIngreso) fields.fechaIngreso.value = item.fecha_ingreso || '';

            if (fields.hint) {
                fields.hint.textContent =
                    `Lote existente en ${item.laboratory_name}. Stock actual: ${Number(item.stock_actual || 0).toFixed(0)}. Al guardar se sumara el stock capturado a este registro.`;
                fields.hint.classList.remove('hidden');
            }
        }

        fields.lote?.addEventListener('change', fillExistingLot);
        fields.lote?.addEventListener('blur', fillExistingLot);
        fields.laboratory?.addEventListener('change', fillExistingLot);
    </script>
</x-admin-layout>
