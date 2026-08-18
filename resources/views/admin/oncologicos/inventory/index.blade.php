<x-admin-layout>
    @php
        $inventoryTitle = match ($category ?? '') {
            'oncologicos' => 'Inventario oncológico',
            'antibioticos' => 'Inventario de antibióticos',
            default => 'Inventario de medicamentos',
        };
    @endphp
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                {{ $inventoryTitle }} (lotes, caducidades y stock)
            </h1>

            <div class="text-sm text-gray-600 mt-1">
                Laboratorio:
                <span class="font-semibold text-gray-800">
                    {{ $laboratory->nombre ?? '—' }}
                </span>
                @if (!empty($laboratory->estado))
                    <span class="text-gray-500">· {{ $laboratory->estado }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratoryId]) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                Volver al almacén
            </a>

            <a href="{{ route('admin.oncologicos.inventory.exportar', [
                'laboratory_id' => $laboratoryId,
                'q' => $q,
                'stock' => $stock,
                'category' => $category ?? '',
            ]) }}"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fa-solid fa-file-excel mr-1"></i>
                Exportar Excel
            </a>

        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" action="{{ route('admin.oncologicos.inventory.index') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">
            <input type="hidden" name="category" value="{{ $category ?? '' }}">

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Denominación del medicamento, presentación, marca o lote..."
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Solo con stock</label>
                <select name="stock" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1" {{ request('stock') == '1' ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ request('stock') == '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Buscar
                </button>

                <a href="{{ route('admin.oncologicos.inventory.index', ['laboratory_id' => $laboratoryId, 'category' => $category ?? '']) }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        @forelse ($groupedRows as $catalog)
            @php
                $totalFrascosProducto = collect($catalog['presentations'])->sum('stock_total');
                $totalReservadoProducto = collect($catalog['presentations'])->sum('stock_reservado_total');
            @endphp

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">
                                {{ $catalog['denominacion'] }}
                            </h2>

                            <div class="text-sm text-gray-600 mt-1">
                                @if (!empty($catalog['state']))
                                    Estado:
                                    <span class="font-medium text-gray-800">
                                        {{ $catalog['state'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-right">
                            <div class="text-gray-500">
                                Total de piezas del producto
                            </div>

                            <div class="font-bold text-lg text-gray-800">
                                {{ number_format($totalFrascosProducto, 2) }} frascos
                            </div>

                            <div class="text-xs text-gray-500">
                                Reservado: {{ number_format($totalReservadoProducto, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Presentación</th>
                                <th class="px-4 py-3">Marca</th>
                                <th class="px-4 py-3">Lote</th>
                                <th class="px-4 py-3">Detalle del lote</th>
                                <th class="px-4 py-3 text-center">Stock lote seleccionado</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Ingresar lote</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($catalog['presentations'] as $presentation)
                                @php
                                    $batches = collect($presentation['batches']);
                                    $firstBatch = $batches->first();
                                    $hasStock = $batches->contains(fn($batch) => (float) $batch->stock_actual > 0);
                                @endphp

                                <tr class="bg-white border-b last:border-b-0 presentation-row">
                                    <td class="px-4 py-3 font-medium text-gray-800 align-top">
                                        <div>{{ $presentation['presentacion'] ?: '—' }}</div>

                                        <div class="text-xs text-gray-500">
                                            {{ $presentation['contenido_valor'] }}
                                            {{ $presentation['contenido_unidad'] }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        {{ $presentation['marca'] ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 align-top min-w-[190px]">
                                        @if ($batches->count())
                                            <select class="lote-select w-full rounded border-gray-300 text-sm">
                                                @foreach ($batches as $batch)
                                                    <option value="{{ $batch->batch_id }}"
                                                        data-caducidad="{{ $batch->caducidad ? \Carbon\Carbon::parse($batch->caducidad)->format('d/m/Y') : '—' }}"
                                                        data-fecha-ingreso="{{ $batch->fecha_ingreso ? \Carbon\Carbon::parse($batch->fecha_ingreso)->format('d/m/Y') : '—' }}"
                                                        data-stock-inicial="{{ number_format((float) $batch->stock_inicial, 2) }}"
                                                        data-stock-actual="{{ number_format((float) $batch->stock_actual, 2) }}"
                                                        data-stock-reservado="{{ number_format((float) $batch->stock_reservado, 2) }}">
                                                        {{ $batch->lote }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-gray-400 text-xs">Sin lotes</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top min-w-[280px]">
                                        @if ($firstBatch)
                                            <div class="text-xs text-gray-700 lote-info">
                                                <div>
                                                    <span class="font-semibold">Caducidad:</span>
                                                    <span class="info-caducidad">
                                                        {{ $firstBatch->caducidad ? \Carbon\Carbon::parse($firstBatch->caducidad)->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="font-semibold">Ingreso:</span>
                                                    <span class="info-fecha-ingreso">
                                                        {{ $firstBatch->fecha_ingreso ? \Carbon\Carbon::parse($firstBatch->fecha_ingreso)->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="font-semibold">Stock actual:</span>
                                                    <span class="text-green-700 font-semibold info-stock-actual">
                                                        {{ number_format((float) $firstBatch->stock_actual, 2) }}
                                                    </span>
                                                    frascos
                                                </div>

                                                <div>
                                                    <span class="font-semibold">Inicial:</span>
                                                    <span class="info-stock-inicial">
                                                        {{ number_format((float) $firstBatch->stock_inicial, 2) }}
                                                    </span>
                                                    /
                                                    <span class="text-gray-500">Reservado:</span>
                                                    <span class="info-stock-reservado">
                                                        {{ number_format((float) $firstBatch->stock_reservado, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">Sin información</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($firstBatch)
                                            <div class="font-semibold text-green-700 selected-stock">
                                                {{ number_format((float) $firstBatch->stock_actual, 2) }} frascos
                                            </div>
                                        @else
                                            <div class="text-gray-400 text-xs">
                                                Sin stock
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($hasStock)
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                                Disponible
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                                Sin stock
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        <a href="{{ route('admin.oncologicos.inventory.ingresoForm', [
                                            'laboratory_id' => $laboratoryId,
                                            'presentation_id' => $presentation['presentation_id'],
                                            'category' => $category ?? '',
                                        ]) }}"
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                            Ingresar lote
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-6 text-center text-gray-500">
                                        Este medicamento no tiene presentaciones disponibles.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                No hay medicamentos para mostrar con los filtros actuales.
            </div>
        @endforelse
    </div>


    @push('js')
        <script>
            document.querySelectorAll('.lote-select').forEach(select => {
                select.addEventListener('change', function() {
                    const row = this.closest('.presentation-row');
                    const option = this.selectedOptions[0];

                    if (!row || !option) return;

                    row.querySelector('.info-caducidad').textContent = option.dataset.caducidad || '—';
                    row.querySelector('.info-fecha-ingreso').textContent = option.dataset.fechaIngreso || '—';
                    row.querySelector('.info-stock-inicial').textContent = option.dataset.stockInicial ||
                    '0.00';
                    row.querySelector('.info-stock-actual').textContent = option.dataset.stockActual || '0.00';
                    row.querySelector('.info-stock-reservado').textContent = option.dataset.stockReservado ||
                        '0.00';

                    const selectedStock = row.querySelector('.selected-stock');

                    if (selectedStock) {
                        selectedStock.textContent = (option.dataset.stockActual || '0.00') + ' frascos';
                    }
                });
            });
        </script>
    @endpush


</x-admin-layout>
