<x-admin-layout>
    <div class="mx-auto max-w-4xl space-y-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Editar lote oncológico</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $batch->presentation?->catalog?->denominacion }} · {{ $batch->presentation?->marca }}</p>
            </div>
            <a href="{{ route('admin.oncologicos.inventory.index', ['laboratory_id' => $batch->laboratory_id, 'warehouse_id' => $batch->warehouse_id]) }}"
                class="rounded bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.oncologicos.inventory.updateBatch', $batch) }}"
            class="space-y-5 rounded-lg bg-white p-6 shadow">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Lote</label>
                    <input name="lote" value="{{ old('lote', $batch->lote) }}" required class="w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Caducidad</label>
                    <input type="date" name="caducidad" value="{{ old('caducidad', optional($batch->caducidad)->format('Y-m-d')) }}" required class="w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', optional($batch->fecha_ingreso)->format('Y-m-d')) }}" class="w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Estado</label>
                    <select name="is_active" class="w-full rounded border-gray-300">
                        <option value="1" @selected(old('is_active', (int) $batch->is_active) === 1)>Activo</option>
                        <option value="0" @selected(old('is_active', (int) $batch->is_active) === 0)>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end">
                <button class="rounded bg-azul-prodifem px-5 py-2 font-semibold text-white">Guardar cambios</button>
            </div>
        </form>
    </div>
</x-admin-layout>
