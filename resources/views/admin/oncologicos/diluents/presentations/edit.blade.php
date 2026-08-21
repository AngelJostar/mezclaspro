<x-admin-layout>
    <div class="mt-2 max-w-3xl">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">
            Editar presentacion - {{ $diluent->denominacion_generica ?? '-' }}
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

        <form action="{{ route('admin.oncologicos.diluent_presentations.update', [$diluent, $presentation]) }}"
            method="POST" class="bg-white p-4 rounded shadow space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Central de mezclas</label>
                    <select name="laboratory_id" id="laboratory_id"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" @selected(old('laboratory_id', $presentation->laboratory_id) == $laboratory->id)>
                                {{ $laboratory->nombre }}{{ $laboratory->estado ? ' - ' . $laboratory->estado : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Almacén</label>
                    <select name="warehouse_id" id="warehouse_id"
                        data-selected="{{ old('warehouse_id', $presentation->warehouse_id) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none" required>
                        <option value="">Seleccionar...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Presentacion</label>
                    <input type="text" name="presentacion"
                        value="{{ old('presentacion', $presentation->presentacion) }}" required
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volumen (mL)</label>
                    <input type="number" step="0.01" min="0.01" name="volume_ml"
                        value="{{ old('volume_ml', $presentation->volume_ml) }}" required
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Denominacion comercial</label>
                    <input type="text" name="denominacion_comercial"
                        value="{{ old('denominacion_comercial', $presentation->denominacion_comercial) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <input type="text" name="fabricante" value="{{ old('fabricante', $presentation->fabricante) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                    <input type="text" name="lote" value="{{ old('lote', $presentation->lote) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Caducidad</label>
                    <input type="date" name="caducidad"
                        value="{{ old('caducidad', optional($presentation->caducidad)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso"
                        value="{{ old('fecha_ingreso', optional($presentation->fecha_ingreso)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock actual</label>
                    <input type="number" step="1" min="0" name="stock_actual"
                        value="{{ old('stock_actual', $presentation->stock_actual ?? 0) }}"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                    <p class="text-xs text-gray-500 mt-1">Si cambia, se registra un ajuste en movimientos.</p>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="h-4 w-4"
                        {{ old('is_active', $presentation->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-gray-700">Activo</label>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.oncologicos.diluent_presentations.index', $diluent) }}"
                    class="px-4 py-2 mr-2 border rounded text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Actualizar</button>
            </div>
        </form>
    </div>

    <script>
        const warehousesByLaboratory = @json($laboratories->mapWithKeys(fn ($laboratory) => [
            (string) $laboratory->id => $laboratory->warehouses->map(fn ($warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])->values(),
        ]));
        const laboratorySelect = document.getElementById('laboratory_id');
        const warehouseSelect = document.getElementById('warehouse_id');

        function populateWarehouses(selectedValue = '') {
            const options = warehousesByLaboratory[String(laboratorySelect?.value || '')] || [];
            warehouseSelect.innerHTML = '<option value="">Seleccionar...</option>';

            options.forEach(item => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                option.selected = String(item.id) === String(selectedValue || '');
                warehouseSelect.appendChild(option);
            });
        }

        laboratorySelect?.addEventListener('change', () => populateWarehouses());
        populateWarehouses(warehouseSelect?.dataset.selected || '');
    </script>
</x-admin-layout>
