<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-900">Compras / {{ $sectionMeta['title'] }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $sectionMeta['description'] }}</p>
            </div>
            @if ($section === 'mine')
                <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $selectedLaboratory?->id]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver a almacenes
                </a>
            @else
                <a href="{{ route('admin.warehouses.purchase-orders.index', array_filter(['section' => 'mine', 'laboratory_id' => $selectedLaboratory?->id])) }}"
                    class="inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver a Mis &Oacute;rdenes de Compra
                </a>
            @endif
        </div>

        @if ($selectedLaboratory)
            <div class="mt-6 flex flex-col gap-3 border-y border-gray-200 py-4 sm:flex-row sm:items-end sm:justify-between">
                <form method="GET" action="{{ route('admin.warehouses.purchase-orders.index') }}" class="w-full max-w-xl">
                    <input type="hidden" name="section" value="{{ $section }}">
                    <label for="laboratory_id" class="mb-1 block text-sm font-medium text-gray-700">Central de mezclas</label>
                    <select id="laboratory_id" name="laboratory_id" onchange="this.form.submit()"
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" @selected($selectedLaboratory->id === $laboratory->id)>{{ $laboratory->nombre }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="mt-5 overflow-x-auto border border-gray-200">
                <table id="purchase-orders-table" class="w-full min-w-[1650px] text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <x-filterable-table-header column="0" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col"># OC</x-filterable-table-header>
                            <x-filterable-table-header column="1" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Empresa</x-filterable-table-header>
                            <x-filterable-table-header column="2" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Proveedor</x-filterable-table-header>
                            <x-filterable-table-header column="3" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" sort-type="number" align="right"
                                scope="col">Monto</x-filterable-table-header>
                            <x-filterable-table-header column="4" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Estado</x-filterable-table-header>
                            <x-filterable-table-header column="5" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" sort-type="date" align="center"
                                scope="col">Fecha env&iacute;o</x-filterable-table-header>
                            <x-filterable-table-header column="6" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" sort-type="date" align="center"
                                scope="col">Entrega</x-filterable-table-header>
                            <x-filterable-table-header column="7" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Pago</x-filterable-table-header>
                            <x-filterable-table-header column="8" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Almac&eacute;n receptor</x-filterable-table-header>
                            <x-filterable-table-header column="9" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" scope="col">Subalmac&eacute;n receptor</x-filterable-table-header>
                            <x-filterable-table-header column="10" trigger-class="js-purchase-order-column-filter"
                                sort-class="js-purchase-order-column-sort" align="center"
                                scope="col">Descargar</x-filterable-table-header>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseOrders as $order)
                            @php
                                $normalizedStatus = mb_strtolower(trim((string) $order->status));
                                $statusMeta = match ($normalizedStatus) {
                                    'pagada', 'pagado' => ['Pagada', 'bg-emerald-100 text-emerald-800'],
                                    'pendiente_pago', 'pendiente de pago' => ['Pendiente de pago', 'bg-amber-100 text-amber-800'],
                                    'rechazada', 'rechazado' => ['Rechazada', 'bg-red-100 text-red-800'],
                                    default => ['Enviada', 'bg-blue-100 text-blue-800'],
                                };
                                $company = $order->invoice_to ?: ($order->laboratory?->nombre ?: 'Sin empresa');
                                $paymentLabel = in_array($normalizedStatus, ['pagada', 'pagado'], true)
                                    ? 'Pagado'
                                    : 'Sin pago';
                                $receivingWarehouse = $order->warehouse?->name
                                    ?: ($order->delivery_attention ?: 'Sin almacen asignado');
                            @endphp
                            <tr class="js-purchase-order-filter-row border-t border-gray-200">
                                <td class="px-3 py-2 font-semibold text-gray-900" data-sort-value="{{ $order->folio }}">
                                    {{ $order->folio }}
                                </td>
                                <td class="px-3 py-2" data-sort-value="{{ $company }}">{{ $company }}</td>
                                <td class="px-3 py-2">{{ $order->supplier }}</td>
                                <td class="px-3 py-2 text-right" data-sort-value="{{ (float) $order->total }}">
                                    ${{ number_format((float) $order->total, 2) }}
                                </td>
                                <td class="px-3 py-2" data-filter-value="{{ $statusMeta[0] }}"
                                    data-sort-value="{{ $statusMeta[0] }}">
                                    <span class="inline-flex rounded px-2 py-1 text-xs font-medium {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                                </td>
                                <td class="px-3 py-2 text-center"
                                    data-sort-value="{{ $order->requested_at?->format('Y-m-d') }}">
                                    {{ $order->requested_at?->format('d/m/Y') ?: 'Sin fecha' }}
                                </td>
                                <td class="px-3 py-2 text-center"
                                    data-sort-value="{{ $order->proposed_delivery_at?->format('Y-m-d') }}">
                                    {{ $order->proposed_delivery_at?->format('d/m/Y') ?: 'Sin fecha' }}
                                </td>
                                <td class="px-3 py-2" data-sort-value="{{ $paymentLabel }}">{{ $paymentLabel }}</td>
                                <td class="px-3 py-2" data-sort-value="{{ $receivingWarehouse }}">
                                    {{ $receivingWarehouse }}
                                </td>
                                <td class="px-3 py-2" data-sort-value="{{ $order->inventoryDestinationLabel() }}">
                                    {{ $order->inventoryDestinationLabel() }}
                                </td>
                                <td class="px-3 py-2 text-center" data-filter-value="Descargar" data-sort-value="Descargar">
                                    <a href="{{ route('admin.oncologicos.laboratory.purchase-orders.download', [$selectedLaboratory, $order]) }}"
                                        class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:text-blue-900">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                        Descargar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-3 py-8 text-center text-gray-500">{{ $sectionMeta['empty'] }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-6 text-sm text-gray-600">No hay centrales de mezclas registradas.</p>
        @endif
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                window.createExcelColumnFilters({
                    tableId: 'purchase-orders-table',
                    rowSelector: '.js-purchase-order-filter-row',
                    triggerSelector: '.js-purchase-order-column-filter',
                    instanceId: 'purchase-orders',
                    onChange() {
                        document.querySelectorAll('.js-purchase-order-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });

                const table = document.getElementById('purchase-orders-table');
                const tbody = table?.tBodies[0];
                const sortButtons = Array.from(table?.querySelectorAll('.js-purchase-order-column-sort') || []);
                let activeSortColumn = null;
                let activeSortDirection = 'asc';

                const sortableValue = (row, column, type) => {
                    const cell = row.cells[column];
                    const rawValue = String(cell?.dataset.sortValue ?? cell?.textContent ?? '').trim();

                    if (!rawValue) return null;
                    if (type === 'number') {
                        const number = Number(rawValue.replace(/[^0-9.-]/g, ''));
                        return Number.isNaN(number) ? null : number;
                    }
                    if (type === 'date') {
                        const timestamp = Date.parse(`${rawValue}T00:00:00`);
                        return Number.isNaN(timestamp) ? null : timestamp;
                    }

                    return rawValue.toLocaleLowerCase('es');
                };

                sortButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const column = Number(button.dataset.sortColumn);
                        const type = button.dataset.sortType || 'text';
                        const direction = activeSortColumn === column && activeSortDirection === 'asc'
                            ? 'desc'
                            : 'asc';
                        const rows = Array.from(tbody?.querySelectorAll('.js-purchase-order-filter-row') || []);

                        rows.sort((leftRow, rightRow) => {
                            const left = sortableValue(leftRow, column, type);
                            const right = sortableValue(rightRow, column, type);
                            const leftEmpty = left === null;
                            const rightEmpty = right === null;

                            if (leftEmpty && !rightEmpty) return 1;
                            if (!leftEmpty && rightEmpty) return -1;
                            if (leftEmpty && rightEmpty) return 0;

                            const result = typeof left === 'string'
                                ? left.localeCompare(right, 'es', { numeric: true, sensitivity: 'base' })
                                : left - right;

                            return direction === 'asc' ? result : -result;
                        });

                        rows.forEach((row) => tbody?.appendChild(row));
                        activeSortColumn = column;
                        activeSortDirection = direction;

                        sortButtons.forEach((sortButton) => {
                            const isActive = sortButton === button;
                            const icon = sortButton.querySelector('[data-sort-icon]');
                            const label = sortButton.dataset.sortLabel || 'columna';

                            sortButton.classList.toggle('border-blue-400', isActive);
                            sortButton.classList.toggle('bg-blue-100', isActive);
                            sortButton.classList.toggle('text-blue-700', isActive);
                            sortButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            sortButton.setAttribute('aria-label', isActive
                                ? `Ordenar ${label} ${direction === 'asc' ? 'descendente' : 'ascendente'}`
                                : `Ordenar ${label}`);

                            if (icon) {
                                icon.className = isActive
                                    ? `fa-solid ${direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'}`
                                    : 'fa-solid fa-sort';
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
