<x-admin-layout>
    @php
        $volumePerContainer = (float) ($stock->presentation->presentacion_ml ?? 0);
        $totalContainers = $volumePerContainer > 0
            ? max(0, (int) floor(min(
                (float) $stock->frascos_actuales,
                (float) $stock->stock_ml_actual / $volumePerContainer
            )))
            : 0;
        $availableContainers = max(0, $totalContainers - (int) ($pendingContainers ?? 0));
    @endphp

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Solicitar merma de frasco</h1>

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

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
        <p>
            <strong>Stock actual:</strong>
            {{ number_format($totalContainers) }} {{ $totalContainers === 1 ? 'frasco' : 'frascos' }}
            <span class="text-sm text-gray-500">({{ number_format((float) $stock->stock_ml_actual, 2) }} mL)</span>
        </p>
        @if (($pendingContainers ?? 0) > 0)
            <p class="text-amber-700">
                <strong>Pendientes de autorización:</strong>
                {{ number_format((int) $pendingContainers) }}
                {{ (int) $pendingContainers === 1 ? 'frasco' : 'frascos' }}
            </p>
        @endif
    </div>

    <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <p><strong>Destino:</strong> Superadministrador → Reporte de mermas → Solicitudes de Merma.</p>
        <p class="mt-1">El stock no se descuenta al enviar la solicitud. Se actualiza automáticamente cuando el Superadministrador la autoriza.</p>
    </div>

    <form action="{{ route('admin.nutricionales.stocks.registrarMerma', $stock) }}" method="POST">
        @csrf

        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de frascos enteros</label>
                <input
                    type="number"
                    step="1"
                    min="1"
                    max="{{ $availableContainers }}"
                    name="quantity"
                    value="{{ old('quantity') }}"
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
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded disabled:cursor-not-allowed disabled:bg-gray-300"
                    @disabled($availableContainers < 1)>
                    Enviar solicitud
                </button>
            </div>
        </div>
    </form>
</x-admin-layout>
