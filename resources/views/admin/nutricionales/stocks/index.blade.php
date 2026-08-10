<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                Inventario nutricional
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
            <a href="{{ route('admin.nutricionales.stocks.selectLaboratory') }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                Cambiar laboratorio
            </a>

            <a href="{{ route('admin.nutricionales.stocks.ingreso', ['laboratory_id' => $laboratoryId]) }}"
                class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                Ingresar lote
            </a>

            <button type="button" id="btn-guardar-activos"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Guardar selección del día
            </button>

            <a href="{{ route('admin.nutricionales.stocks.exportar', [
                'laboratory_id' => $laboratoryId,
                'q' => $q ?? request('q'),
                'stock' => $stockFilter ?? request('stock'),
            ]) }}"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fa-solid fa-file-excel mr-1"></i>
                Exportar Excel
            </a>
        </div>
    </div>

    @if ($errors->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errors->first('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" action="{{ route('admin.nutricionales.stocks.index') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ $q ?? request('q') }}"
                    placeholder="Genérico, comercial, input, presentación o lote..."
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Solo con stock</label>
                <select name="stock" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1" {{ ($stockFilter ?? request('stock')) == '1' ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ ($stockFilter ?? request('stock')) == '0' ? 'selected' : '' }}>No
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Buscar
                </button>

                <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $laboratoryId]) }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <form id="active-form" method="POST" action="{{ route('admin.nutricionales.stocks.saveActivePresentations') }}">
        @csrf
        <input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">
    </form>

    <div class="space-y-6">
        @forelse ($catalogs as $catalog)
            @php
                $activeSelection = $activeSelections[$catalog->id] ?? null;

                $totalPiezasProducto = $catalog->presentations
                    ->flatMap(function ($presentation) {
                        return $presentation->stocks ?? collect();
                    })
                    ->sum(function ($stock) {
                        return (float) $stock->frascos_actuales;
                    });

                $totalMlProducto = $catalog->presentations
                    ->flatMap(function ($presentation) {
                        return $presentation->stocks ?? collect();
                    })
                    ->sum(function ($stock) {
                        return (float) $stock->stock_ml_actual;
                    });
            @endphp

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">
                                {{ $catalog->denominacion_generica }}
                            </h2>

                            <div class="text-sm text-gray-600 mt-1">
                                Input:
                                <span class="font-medium text-gray-800">
                                    {{ $catalog->input->description ?? '—' }}
                                </span>

                                @if ($catalog->category)
                                    <span class="mx-2 text-gray-400">·</span>
                                    Categoría:
                                    <span class="font-medium text-gray-800">
                                        {{ $catalog->category->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-600">
                            Selecciona la presentación activa del día
                        </div>

                        <div class="text-sm text-right">
                            <div class="text-gray-500">
                                Total de piezas del producto
                            </div>

                            <div class="font-bold text-lg text-gray-800">
                                {{ number_format($totalPiezasProducto, 2) }} frascos
                            </div>

                            <div class="text-xs text-green-700 font-semibold">
                                {{ number_format($totalMlProducto, 2) }} ml
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center">Usar hoy</th>
                                <th class="px-4 py-3">Presentación comercial</th>
                                <th class="px-4 py-3">Presentación</th>
                                <th class="px-4 py-3 text-center">ML presentación</th>
                                <th class="px-4 py-3">Lote</th>
                                <th class="px-4 py-3">Detalle del lote</th>
                                <th class="px-4 py-3 text-center">Stock lote seleccionado</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($catalog->presentations as $presentation)
                                @php
                                    $isPieceBasedPresentation =
                                        (int) ($catalog->category->id ?? 0) === 6 ||
                                        str_contains(\\Illuminate\\Support\\Str::lower((string) ($catalog->denominacion_generica ?? '')), 'bolsa eva');

                                    $stocks = ($presentation->stocks ?? collect())
                                        ->where('nutrition_medicine_presentation_id', $presentation->id)
                                        ->values();

                                    $hasStock = $stocks->contains(function ($stock) {
                                        return (bool) $stock->is_active &&
                                            (((float) $stock->frascos_actuales > 0) || ((float) $stock->stock_ml_actual > 0));
                                    });

                                    $firstStock = $stocks->first();

                                    $isSelectedToday =
                                        $activeSelection &&
                                        (int) $activeSelection->nutrition_medicine_presentation_id ===
                                            (int) $presentation->id;
                                @endphp

                                <tr class="bg-white border-b last:border-b-0 presentation-row"
                                    data-presentation-id="{{ $presentation->id }}">

                                    <td class="px-4 py-3 text-center align-top">
                                        <input type="radio" form="active-form"
                                            name="active_presentations[{{ $catalog->id }}]"
                                            value="{{ $presentation->id }}"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            {{ $isSelectedToday ? 'checked' : '' }}
                                            {{ !$hasStock ? 'disabled' : '' }}>

                                        @if (!$hasStock)
                                            <div class="text-xs text-red-500 mt-1">
                                                Sin stock
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 font-medium text-gray-800 align-top">
                                        {{ $presentation->denominacion_comercial }}
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        {{ $presentation->presentacion ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($presentation->presentacion_ml)
                                            {{ number_format($presentation->presentacion_ml, 2) }}
                                            {{ $isPieceBasedPresentation ? 'ml c/u' : 'ml' }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top min-w-[190px]">
                                        @if ($stocks->count())
                                            <select class="lote-select w-full rounded border-gray-300 text-sm">
                                                @foreach ($stocks as $stock)
                                                    <option value="{{ $stock->id }}"
                                                        data-edit-url="{{ route('admin.nutricionales.stocks.edit', $stock->id) }}"
                                                        data-merma-url="{{ route('admin.nutricionales.stocks.merma', $stock->id) }}"
                                                        data-movimientos-url="{{ route('admin.nutricionales.stocks.movimientos', $stock->id) }}"
                                                        data-caducidad="{{ isset($stock->caducidad) ? \Carbon\Carbon::parse($stock->caducidad)->format('d/m/Y') : '—' }}"
                                                        data-fecha-ingreso="{{ isset($stock->fecha_ingreso) ? \Carbon\Carbon::parse($stock->fecha_ingreso)->format('d/m/Y') : '—' }}"
                                                        data-frascos-iniciales="{{ number_format((float) $stock->frascos_iniciales, 2) }}"
                                                        data-frascos-actuales="{{ number_format((float) $stock->frascos_actuales, 2) }}"
                                                        data-stock-inicial="{{ number_format((float) $stock->stock_ml_inicial, 2) }}"
                                                        data-stock-actual="{{ number_format((float) $stock->stock_ml_actual, 2) }}">
                                                        {{ $stock->lote }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-gray-400 text-xs">Sin lotes</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top min-w-[280px]">
                                        @if ($firstStock)
                                            <div class="text-xs text-gray-700 lote-info">
                                                <div>
                                                    <span class="font-semibold">Caducidad:</span>
                                                    <span class="info-caducidad">
                                                        {{ isset($firstStock->caducidad) ? \Carbon\Carbon::parse($firstStock->caducidad)->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="font-semibold">Ingreso:</span>
                                                    <span class="info-fecha-ingreso">
                                                        {{ isset($firstStock->fecha_ingreso) ? \Carbon\Carbon::parse($firstStock->fecha_ingreso)->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="font-semibold">Frascos:</span>
                                                    <span class="info-frascos-actuales">
                                                        {{ number_format((float) $firstStock->frascos_actuales, 2) }}
                                                    </span>
                                                    /
                                                    <span class="text-gray-500">Iniciales:</span>
                                                    <span class="info-frascos-iniciales">
                                                        {{ number_format((float) $firstStock->frascos_iniciales, 2) }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="font-semibold">
                                                        {{ $isPieceBasedPresentation ? 'Capacidad actual:' : 'ML actual:' }}
                                                    </span>
                                                    <span class="text-green-700 font-semibold info-stock-actual">
                                                        {{ number_format((float) $firstStock->stock_ml_actual, 2) }}
                                                    </span>
                                                    ml
                                                    <span class="text-gray-500">/ Inicial:</span>
                                                    <span class="info-stock-inicial">
                                                        {{ number_format((float) $firstStock->stock_ml_inicial, 2) }}
                                                    </span>
                                                    ml
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">Sin información</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($firstStock)
                                            @if ($isPieceBasedPresentation)
                                                <div class="font-semibold text-green-700 selected-frascos">
                                                    {{ number_format((float) $firstStock->frascos_actuales, 2) }} piezas
                                                </div>
                                                <div class="text-xs text-gray-500 selected-stock-ml">
                                                    {{ number_format((float) $firstStock->stock_ml_actual, 2) }} ml de capacidad
                                                </div>
                                            @else
                                                <div class="font-semibold text-green-700 selected-stock-ml">
                                                    {{ number_format((float) $firstStock->stock_ml_actual, 2) }} ml
                                                </div>
                                                <div class="text-xs text-gray-500 selected-frascos">
                                                    {{ number_format((float) $firstStock->frascos_actuales, 2) }} frascos
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-gray-400 text-xs">
                                                Sin stock
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($isSelectedToday)
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                                                Activa hoy
                                            </span>
                                        @elseif ($hasStock)
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

                                    <td class="px-4 py-3 text-right align-top">
                                        <x-row-actions>
                                            @if ($firstStock)
                                                @role('Super Admin')
                                                    <a href="{{ route('admin.nutricionales.stocks.edit', $firstStock->id) }}"
                                                        class="action-edit">
                                                        Editar
                                                    </a>
                                                @endrole

                                                <a href="{{ route('admin.nutricionales.stocks.merma', $firstStock->id) }}"
                                                    class="action-merma action-danger">
                                                    Merma
                                                </a>

                                                <a href="{{ route('admin.nutricionales.stocks.movimientos', $firstStock->id) }}"
                                                    class="action-movimientos">
                                                    Movimientos
                                                </a>
                                            @endif

                                            <a href="{{ route('admin.nutricionales.stocks.ingreso', [
                                                'laboratory_id' => $laboratoryId,
                                                'presentation_id' => $presentation->id,
                                            ]) }}"
                                                class="">
                                                Ingresar lote
                                            </a>
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-6 text-center text-gray-500">
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
            (function() {
                const activeForm = document.getElementById('active-form');
                const btnGuardarActivos = document.getElementById('btn-guardar-activos');

                btnGuardarActivos?.addEventListener('click', () => {
                    Swal.fire({
                        title: '¿Guardar selección del día?',
                        text: 'Se actualizarán las presentaciones activas por medicamento genérico.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            activeForm.submit();
                        }
                    });
                });

                document.querySelectorAll('.lote-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const row = this.closest('.presentation-row');
                        const option = this.selectedOptions[0];

                        if (!row || !option) return;

                        const caducidad = row.querySelector('.info-caducidad');
                        const fechaIngreso = row.querySelector('.info-fecha-ingreso');
                        const frascosIniciales = row.querySelector('.info-frascos-iniciales');
                        const frascosActuales = row.querySelector('.info-frascos-actuales');
                        const stockInicial = row.querySelector('.info-stock-inicial');
                        const stockActual = row.querySelector('.info-stock-actual');

                        const selectedStockMl = row.querySelector('.selected-stock-ml');
                        const selectedFrascos = row.querySelector('.selected-frascos');

                        const actionEdit = row.querySelector('.action-edit');
                        const actionMerma = row.querySelector('.action-merma');
                        const actionMovimientos = row.querySelector('.action-movimientos');

                        if (caducidad) caducidad.textContent = option.dataset.caducidad || '—';
                        if (fechaIngreso) fechaIngreso.textContent = option.dataset.fechaIngreso || '—';
                        if (frascosIniciales) frascosIniciales.textContent = option.dataset
                            .frascosIniciales || '0.00';
                        if (frascosActuales) frascosActuales.textContent = option.dataset.frascosActuales ||
                            '0.00';
                        if (stockInicial) stockInicial.textContent = option.dataset.stockInicial || '0.00';
                        if (stockActual) stockActual.textContent = option.dataset.stockActual || '0.00';

                        if (selectedStockMl) {
                            selectedStockMl.textContent = (option.dataset.stockActual || '0.00') + ' ml';
                        }

                        if (selectedFrascos) {
                            selectedFrascos.textContent = (option.dataset.frascosActuales || '0.00') +
                                ' frascos';
                        }

                        if (actionEdit) actionEdit.href = option.dataset.editUrl;
                        if (actionMerma) actionMerma.href = option.dataset.mermaUrl;
                        if (actionMovimientos) actionMovimientos.href = option.dataset.movimientosUrl;
                    });
                });
            })();
        </script>
    @endpush
</x-admin-layout>
