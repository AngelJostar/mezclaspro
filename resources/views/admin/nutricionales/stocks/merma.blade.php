<x-admin-layout>
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Registrar merma</h1>

    <div class="bg-white shadow rounded-lg p-6 mb-6 space-y-1">
        <p><strong>Central de mezclas:</strong> {{ $stock->laboratory->nombre }}</p>
        <p><strong>Almacén:</strong> {{ $stock->warehouse?->name ?: 'Sin almacén asignado' }}</p>
        <p><strong>Medicamento genérico:</strong> {{ $stock->presentation->catalog->denominacion_generica ?? '—' }}</p>
        <p><strong>Presentación comercial:</strong> {{ $stock->presentation->denominacion_comercial ?? '—' }}</p>
        <p><strong>Presentación:</strong> {{ $stock->presentation->presentacion ?? '—' }}</p>
        <p><strong>ML por presentación:</strong> {{ $stock->presentation->presentacion_ml ?? '—' }}</p>
        <p><strong>Lote:</strong> {{ $stock->lote }}</p>
        <p>
            <strong>Caducidad:</strong>
            {{ $stock->caducidad ? \Carbon\Carbon::parse($stock->caducidad)->format('Y-m-d') : '—' }}
        </p>
        <p><strong>Stock actual:</strong> {{ number_format($stock->stock_ml_actual, 2) }} ml</p>
    </div>

    <form action="{{ route('admin.nutricionales.stocks.registrarMerma', $stock) }}" method="POST">
        @csrf

        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad a descartar (ml)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="cantidad_ml"
                    value="{{ old('cantidad_ml') }}"
                    class="w-full rounded border-gray-300"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                <textarea
                    name="notes"
                    rows="4"
                    class="w-full rounded border-gray-300"
                    required
                >{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded">
                    Registrar merma
                </button>
            </div>
        </div>
    </form>
</x-admin-layout>
