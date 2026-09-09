<x-admin-layout>
    @php
        $inventoryTitle = match ($category ?? '') {
            'oncologicos' => 'Subalmacén oncológico',
            'antibioticos' => 'Subalmacén de antibióticos',
            default => 'Subalmacén de medicamentos',
        };
    @endphp
    <div class="mt-2 mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                {{ $inventoryTitle }} (lotes, caducidades y stock)
            </h1>

            <div class="text-sm text-gray-600 mt-1">
                Central de mezclas:
                <span class="font-semibold text-gray-800">
                    {{ $laboratory->nombre ?? '—' }}
                </span>
                @if (!empty($laboratory->estado))
                    <span class="text-gray-500">· {{ $laboratory->estado }}</span>
                @endif
                <span class="mx-1 text-gray-300">|</span>
                Almacén: <span class="font-semibold text-gray-800">{{ $warehouse->name }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratoryId, 'warehouse_id' => $warehouseId]) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                Volver al almacén
            </a>

            <a href="{{ route('admin.oncologicos.inventory.ingresoForm', [
                'laboratory_id' => $laboratoryId,
                'warehouse_id' => $warehouseId,
                'category' => $category ?? '',
            ]) }}"
                class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Agregar producto
            </a>

            <a href="{{ route('admin.oncologicos.inventory.exportar', [
                'laboratory_id' => $laboratoryId,
                'warehouse_id' => $warehouseId,
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

    @if (session('success'))
        <div role="status" class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" action="{{ route('admin.oncologicos.inventory.index') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">
            <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
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

                <a href="{{ route('admin.oncologicos.inventory.index', ['laboratory_id' => $laboratoryId, 'warehouse_id' => $warehouseId, 'category' => $category ?? '']) }}"
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
                                @role('Super Admin')
                                    <th class="px-4 py-3 text-center">Editar</th>
                                @endrole
                                <th class="px-4 py-3 text-center">Remanente</th>
                                <th class="px-4 py-3 text-center">Merma remanente</th>
                                <th class="px-4 py-3 text-center">Merma de frasco</th>
                                <th class="px-4 py-3 text-center">Movimientos</th>
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
                                                        data-edit-url="{{ route('admin.oncologicos.inventory.editBatch', $batch->batch_id) }}"
                                                        data-loss-url="{{ route('admin.oncologicos.inventory.registrarMerma', $batch->batch_id) }}"
                                                        data-movimientos-url="{{ route('admin.oncologicos.inventory.movimientos', $batch->batch_id) }}"
                                                        data-caducidad="{{ $batch->caducidad ? \Carbon\Carbon::parse($batch->caducidad)->format('d/m/Y') : '—' }}"
                                                        data-fecha-ingreso="{{ $batch->fecha_ingreso ? \Carbon\Carbon::parse($batch->fecha_ingreso)->format('d/m/Y') : '—' }}"
                                                        data-stock-inicial="{{ number_format((float) $batch->stock_inicial, 2) }}"
                                                        data-stock-actual="{{ number_format((float) $batch->stock_actual, 2, '.', '') }}"
                                                        data-stock-reservado="{{ number_format((float) $batch->stock_reservado, 2, '.', '') }}"
                                                        data-ml-per-bottle="{{ number_format((float) ($batch->volumen_diluyente ?? 0), 4, '.', '') }}"
                                                        data-remanente-ml="{{ $batch->remanente_ml }}"
                                                        data-remanente-mg="{{ $batch->remanente_mg }}"
                                                        data-remanente-merma-url="{{ route('admin.oncologicos.inventory.descartarRemanente', $batch->batch_id) }}">
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

                                    @role('Super Admin')
                                        <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                            @if ($firstBatch)
                                                <x-table-action-link href="{{ route('admin.oncologicos.inventory.editBatch', $firstBatch->batch_id) }}"
                                                    class="edit-link">Editar</x-table-action-link>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endrole

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        @if ($firstBatch)
                                            <span class="font-semibold text-amber-700 selected-remainder">
                                                {{ $firstBatch->remanente_mg === null ? 'Sin concentracion' : number_format($firstBatch->remanente_mg, 2, '.', '').' mg' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">0.00 mg</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        @if ($firstBatch)
                                            @php($hasRemainder = (float) $firstBatch->remanente_ml > 0.0001)
                                            <form method="POST" action="{{ route('admin.oncologicos.inventory.descartarRemanente', $firstBatch->batch_id) }}" class="remainder-waste-form inline">
                                                @csrf
                                                <button type="submit" class="remainder-waste-button inline-flex items-center justify-center rounded-full px-3 py-2 text-xs font-semibold {{ $hasRemainder ? 'bg-red-600 text-white hover:bg-red-700' : 'cursor-not-allowed bg-gray-300 text-gray-600' }}" @disabled(! $hasRemainder)>
                                                    Merma
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        @if ($firstBatch)
                                            <form method="POST" action="{{ route('admin.oncologicos.inventory.registrarMerma', $firstBatch->batch_id) }}" class="stock-loss-form inline">
                                                @csrf
                                                <input type="hidden" name="unit" value="frasco">
                                                <input type="hidden" name="quantity">
                                                <input type="hidden" name="notes">
                                                <button type="submit" class="stock-loss-button inline-flex items-center justify-center rounded-full bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                    Solicitar merma
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        @if ($firstBatch)
                                            <x-table-action-link href="{{ route('admin.oncologicos.inventory.movimientos', $firstBatch->batch_id) }}"
                                                variant="gray" class="movimientos-link">Movimientos</x-table-action-link>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                                        <a href="{{ route('admin.oncologicos.inventory.ingresoForm', [
                                            'laboratory_id' => $laboratoryId,
                                            'warehouse_id' => $warehouseId,
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
                                    <td colspan="10" class="px-6 py-6 text-center text-gray-500">
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

                    const movementsLink = row.querySelector('.movimientos-link');
                    if (movementsLink && option.dataset.movimientosUrl) {
                        movementsLink.href = option.dataset.movimientosUrl;
                    }

                    const editLink = row.querySelector('.edit-link');
                    if (editLink && option.dataset.editUrl) editLink.href = option.dataset.editUrl;

                    const lossForm = row.querySelector('.stock-loss-form');
                    if (lossForm && option.dataset.lossUrl) lossForm.action = option.dataset.lossUrl;

                    const remainderMl = Number(option.dataset.remanenteMl || 0);
                    const remainderMg = option.dataset.remanenteMg;
                    const remainderLabel = row.querySelector('.selected-remainder');
                    if (remainderLabel) remainderLabel.textContent = remainderMg === '' || remainderMg == null
                        ? 'Sin concentracion' : Number(remainderMg).toLocaleString('en-US', {
                            minimumFractionDigits: 2, maximumFractionDigits: 2, useGrouping: false
                        }) + ' mg';

                    const remainderForm = row.querySelector('.remainder-waste-form');
                    const remainderButton = row.querySelector('.remainder-waste-button');
                    if (remainderForm && option.dataset.remanenteMermaUrl) remainderForm.action = option.dataset.remanenteMermaUrl;
                    if (remainderButton) {
                        const enabled = remainderMl > 0.0001;
                        remainderButton.disabled = !enabled;
                        remainderButton.classList.toggle('bg-red-600', enabled);
                        remainderButton.classList.toggle('text-white', enabled);
                        remainderButton.classList.toggle('hover:bg-red-700', enabled);
                        remainderButton.classList.toggle('cursor-not-allowed', !enabled);
                        remainderButton.classList.toggle('bg-gray-300', !enabled);
                        remainderButton.classList.toggle('text-gray-600', !enabled);
                    }
                });
            });

            document.querySelectorAll('.remainder-waste-form').forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const button = form.querySelector('.remainder-waste-button');
                    if (!button || button.disabled) return;

                    Swal.fire({
                        title: 'Enviar remanente a merma?',
                        text: 'Esta accion dejara el remanente del lote en 0 mg.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si, enviar a merma',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                    }).then((result) => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });

            document.querySelectorAll('.stock-loss-form').forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const row = form.closest('.presentation-row');
                    const option = row?.querySelector('.lote-select')?.selectedOptions[0];
                    const stock = Number(option?.dataset.stockActual || 0);
                    const reserved = Number(option?.dataset.stockReservado || 0);
                    const available = Math.max(0, Math.floor(stock - reserved));

                    Swal.fire({
                        title: 'Solicitar merma de frascos',
                        html: `
                            <input id="loss-quantity" type="number" min="1" step="1" max="${available}" class="swal2-input" placeholder="Máximo: ${available} frascos">
                            <textarea id="loss-notes" class="swal2-textarea" placeholder="Motivo de la merma"></textarea>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Enviar solicitud',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                        preConfirm: () => {
                            const quantity = Number(document.getElementById('loss-quantity').value);
                            const notes = document.getElementById('loss-notes').value.trim();
                            if (!Number.isInteger(quantity) || quantity < 1 || quantity > available) {
                                Swal.showValidationMessage('Captura un número entero dentro de los frascos disponibles.');
                                return false;
                            }
                            if (!notes) {
                                Swal.showValidationMessage('Indica el motivo de la merma.');
                                return false;
                            }
                            return { quantity, notes };
                        },
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        form.querySelector('[name="quantity"]').value = result.value.quantity;
                        form.querySelector('[name="notes"]').value = result.value.notes;
                        form.submit();
                    });
                });
            });
        </script>
    @endpush


</x-admin-layout>
