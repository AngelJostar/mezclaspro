<x-admin-layout>
    <div class="mx-auto max-w-4xl space-y-5">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Registrar merma oncológica</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $batch->presentation?->catalog?->denominacion }} · Lote {{ $batch->lote }}</p>
        </div>

        <div class="rounded-lg bg-white p-5 text-sm shadow">
            <p><strong>Presentación:</strong> {{ $batch->presentation?->marca }} · {{ $batch->presentation?->presentacion }}</p>
            <p><strong>Stock actual:</strong> {{ number_format((float) $batch->stock_ml_actual, 2) }} mL</p>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.oncologicos.inventory.registrarMerma', $batch) }}"
            class="space-y-4 rounded-lg bg-white p-6 shadow">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">Cantidad a descartar (mL)</label>
                <input type="number" step="0.01" min="0.01" max="{{ (float) $batch->stock_ml_actual }}"
                    name="cantidad_ml" value="{{ old('cantidad_ml') }}" required class="w-full rounded border-gray-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Motivo</label>
                <textarea name="notes" rows="4" required class="w-full rounded border-gray-300">{{ old('notes') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button class="rounded bg-red-600 px-5 py-2 font-semibold text-white hover:bg-red-700">Registrar merma</button>
            </div>
        </form>
    </div>
</x-admin-layout>
