<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                Editar lote de inventario
            </h1>

            <div class="mt-1 text-sm text-gray-600">
                Central de mezclas:
                <span class="font-semibold text-gray-800">
                    {{ $stock->laboratory->nombre ?? '-' }}
                </span>
            </div>
        </div>

        <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $stock->laboratory_id, 'warehouse_id' => $stock->warehouse_id]) }}"
            class="rounded bg-gray-100 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-200">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('duplicate_stock'))
        @php($duplicateStock = session('duplicate_stock'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-amber-800">
                        Lote duplicado detectado
                    </h3>

                    <p class="mt-1 text-sm">
                        Ya existe un registro con el lote
                        <span class="font-semibold">{{ $duplicateStock['lote'] ?? $stock->lote }}</span>
                        para esta misma presentación y almacén.
                    </p>

                    <div class="mt-2 space-y-1 text-sm text-amber-800">
                        <div>
                            Presentación existente:
                            <span class="font-semibold">{{ $duplicateStock['presentation_name'] ?? 'Presentación' }}</span>
                        </div>
                        <div>
                            Existencias actuales:
                            <span class="font-semibold">{{ number_format((float) ($duplicateStock['target_frascos'] ?? 0), 2) }} frascos</span>
                            /
                            <span class="font-semibold">{{ number_format((float) ($duplicateStock['target_stock_ml'] ?? 0), 2) }} ml</span>
                        </div>
                        <div>
                            Caducidad del registro existente:
                            <span class="font-semibold">{{ $duplicateStock['target_caducidad'] ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST"
                    action="{{ route('admin.nutricionales.stocks.mergeDuplicate', $stock->id) }}"
                    class="flex-shrink-0">
                    @csrf
                    <input type="hidden" name="target_stock_id" value="{{ $duplicateStock['target_stock_id'] ?? '' }}">
                    <input type="hidden" name="notes"
                        value="Fusion manual solicitada desde la pantalla de edicion del lote {{ $stock->lote }}.">

                    <button type="submit"
                        class="inline-flex items-center rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        Fusionar con lote existente
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="mb-4 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-2 text-lg font-semibold text-gray-800">
            {{ $stock->presentation->catalog->denominacion_generica ?? 'Medicamento' }}
        </h2>

        <div class="text-sm text-gray-600">
            Presentación comercial:
            <span class="font-semibold text-gray-800">
                {{ $stock->presentation->denominacion_comercial ?? '-' }}
            </span>
        </div>

        <div class="text-sm text-gray-600">
            Presentación:
            <span class="font-semibold text-gray-800">
                {{ $stock->presentation->presentacion ?? '-' }}
            </span>
        </div>

        <div class="text-sm text-gray-600">
            ML por presentación:
            <span class="font-semibold text-gray-800" id="presentacion-ml-text">
                {{ number_format((float) ($stock->presentation->presentacion_ml ?? 0), 2) }} ml
            </span>
        </div>
    </div>

    <form id="deplete-stock-form" method="POST" action="{{ route('admin.nutricionales.stocks.deplete', $stock->id) }}"
        onsubmit="return confirm('Se dará de baja total este lote y su stock quedará en cero. ¿Deseas continuar?');"
        class="hidden">
        @csrf
        <input type="hidden" name="notes" value="Baja total manual ejecutada desde edición de lote.">
    </form>

    <form method="POST" action="{{ route('admin.nutricionales.stocks.update', $stock->id) }}"
        class="space-y-6 rounded-lg bg-white p-6 shadow">
        @csrf
        @method('PUT')

        <input type="hidden" id="presentacion_ml" value="{{ (float) ($stock->presentation->presentacion_ml ?? 0) }}">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Lote
                </label>

                <input type="text" name="lote" value="{{ old('lote', $stock->lote) }}"
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Caducidad
                </label>

                <input type="date" name="caducidad"
                    value="{{ old('caducidad', optional($stock->caducidad)->format('Y-m-d')) }}"
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Fecha de ingreso
                </label>

                <input type="date" name="fecha_ingreso"
                    value="{{ old('fecha_ingreso', optional($stock->fecha_ingreso)->format('Y-m-d')) }}"
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Estado
                </label>

                <select name="is_active" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" {{ old('is_active', (int) $stock->is_active) == 1 ? 'selected' : '' }}>
                        Activo
                    </option>
                    <option value="0" {{ old('is_active', (int) $stock->is_active) == 0 ? 'selected' : '' }}>
                        Inactivo
                    </option>
                </select>
            </div>
        </div>

        <div class="border-t pt-5">
            <h3 class="mb-3 text-base font-semibold text-gray-800">
                Existencias
            </h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Frascos iniciales
                    </label>

                    <input type="number" step="0.01" min="0" name="frascos_iniciales" id="frascos_iniciales"
                        value="{{ old('frascos_iniciales', number_format((float) $stock->frascos_iniciales, 2, '.', '')) }}"
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Frascos actuales
                    </label>

                    <input type="number" step="0.01" min="0" name="frascos_actuales" id="frascos_actuales"
                        value="{{ old('frascos_actuales', number_format((float) $stock->frascos_actuales, 2, '.', '')) }}"
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded border bg-gray-50 p-4">
                    <div class="text-sm text-gray-500">
                        Stock inicial calculado
                    </div>
                    <div class="text-xl font-semibold text-gray-800" id="stock_ml_inicial_preview">
                        {{ number_format((float) $stock->stock_ml_inicial, 2) }} ml
                    </div>
                </div>

                <div class="rounded border bg-gray-50 p-4">
                    <div class="text-sm text-gray-500">
                        Stock actual calculado
                    </div>
                    <div class="text-xl font-semibold text-green-700" id="stock_ml_actual_preview">
                        {{ number_format((float) $stock->stock_ml_actual, 2) }} ml
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                Los ml se calculan automáticamente con base en los frascos y los ml de la presentación.
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
                Nota del ajuste
            </label>

            <textarea name="notes" rows="3"
                placeholder="Ejemplo: Correccion administrativa de inventario por Super Admin"
                class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
        </div>

        <div class="flex flex-col gap-3 border-t pt-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold">
                    Baja total del lote
                </div>
                <div class="mt-1">
                    Si este registro ya no debe seguir disponible, puedes dejar su inventario en cero desde aquí.
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="submit" form="deplete-stock-form"
                    class="w-full rounded bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700 sm:w-auto">
                    Dar de baja total
                </button>

                <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $stock->laboratory_id, 'warehouse_id' => $stock->warehouse_id]) }}"
                    class="text-center rounded bg-gray-100 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-200">
                    Cancelar
                </a>

                <button type="submit" class="rounded bg-green-600 px-6 py-2 font-bold text-white hover:bg-green-700">
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>

    @push('js')
        <script>
            (function() {
                const presentacionMl = parseFloat(document.getElementById('presentacion_ml')?.value || 0);
                const frascosIniciales = document.getElementById('frascos_iniciales');
                const frascosActuales = document.getElementById('frascos_actuales');
                const previewInicial = document.getElementById('stock_ml_inicial_preview');
                const previewActual = document.getElementById('stock_ml_actual_preview');

                function toFloat(value) {
                    const n = parseFloat(value);
                    return Number.isNaN(n) ? 0 : n;
                }

                function actualizarPreview() {
                    const inicial = toFloat(frascosIniciales.value) * presentacionMl;
                    const actual = toFloat(frascosActuales.value) * presentacionMl;

                    previewInicial.textContent = inicial.toFixed(2) + ' ml';
                    previewActual.textContent = actual.toFixed(2) + ' ml';
                }

                frascosIniciales?.addEventListener('input', actualizarPreview);
                frascosActuales?.addEventListener('input', actualizarPreview);

                actualizarPreview();
            })();
        </script>
    @endpush
</x-admin-layout>
