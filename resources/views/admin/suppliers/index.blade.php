<x-admin-layout>
    @php
        $statusLabels = \App\Models\Supplier::statuses();
        $statusClasses = [
            \App\Models\Supplier::STATUS_ACTIVE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\Supplier::STATUS_REVIEW => 'border-amber-200 bg-amber-50 text-amber-700',
            \App\Models\Supplier::STATUS_INACTIVE => 'border-gray-200 bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500" aria-label="Ruta de navegacion">
                    <span>Compras</span>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Proveedores</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950">Catalogo de proveedores</h1>
                <p class="mt-1 text-sm text-gray-500">Consulta y administra los proveedores registrados.</p>
            </div>

            <a href="{{ route('admin.suppliers.create') }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Alta de proveedor
            </a>
        </header>

        <div class="flex items-center justify-end py-3 text-sm font-semibold text-gray-500">
            {{ number_format($suppliers->total()) }} {{ $suppliers->total() === 1 ? 'proveedor' : 'proveedores' }}
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table id="supplier-catalog-table" class="w-full min-w-[1500px] text-left text-sm text-gray-700">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center">
                            <input type="checkbox" aria-label="Seleccionar todos los proveedores"
                                class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                        </th>
                        <x-filterable-table-header column="1" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Proveedor</x-filterable-table-header>
                        <x-filterable-table-header column="2" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">RFC</x-filterable-table-header>
                        <x-filterable-table-header column="3" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Contacto</x-filterable-table-header>
                        <x-filterable-table-header column="4" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Telefono</x-filterable-table-header>
                        <x-filterable-table-header column="5" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Correo</x-filterable-table-header>
                        <x-filterable-table-header column="6" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Categoria</x-filterable-table-header>
                        <x-filterable-table-header column="7" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Ubicacion</x-filterable-table-header>
                        <x-filterable-table-header column="8" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" scope="col">Estatus</x-filterable-table-header>
                        <x-filterable-table-header column="9" trigger-class="js-supplier-column-filter"
                            sort-class="js-supplier-column-sort" sort-type="date"
                            scope="col">Actualizacion</x-filterable-table-header>
                        <th class="px-3 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($suppliers as $supplier)
                        @php
                            $supplierCategory = $supplier->category ?: 'Sin categoria';
                            $supplierLocation = $supplier->location ?: ($supplier->address ?: '—');
                            $supplierStatus = $statusLabels[$supplier->status] ?? $supplier->status;
                        @endphp
                        <tr class="js-supplier-filter-row hover:bg-gray-50/80">
                            <td class="px-3 py-3 text-center">
                                <input type="checkbox" aria-label="Seleccionar {{ $supplier->name }}"
                                    class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                            </td>
                            <td class="max-w-[240px] px-3 py-3 font-semibold text-gray-950"
                                data-filter-value="{{ $supplier->name }}" data-sort-value="{{ $supplier->name }}">
                                {{ $supplier->name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3" data-filter-value="{{ $supplier->rfc ?: '—' }}"
                                data-sort-value="{{ $supplier->rfc }}">{{ $supplier->rfc ?: '—' }}</td>
                            <td class="px-3 py-3" data-filter-value="{{ $supplier->contact_name ?: '—' }}"
                                data-sort-value="{{ $supplier->contact_name }}">{{ $supplier->contact_name ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3" data-filter-value="{{ $supplier->phone ?: '—' }}"
                                data-sort-value="{{ $supplier->phone }}">{{ $supplier->phone ?: '—' }}</td>
                            <td class="max-w-[220px] px-3 py-3" data-filter-value="{{ $supplier->email ?: '—' }}"
                                data-sort-value="{{ $supplier->email }}">
                                @if ($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}" class="text-blue-700 hover:underline">{{ $supplier->email }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3" data-filter-value="{{ $supplierCategory }}"
                                data-sort-value="{{ $supplierCategory }}">
                                <span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                    {{ $supplierCategory }}
                                </span>
                            </td>
                            <td class="max-w-[220px] px-3 py-3" data-filter-value="{{ $supplierLocation }}"
                                data-sort-value="{{ $supplierLocation }}">{{ $supplierLocation }}</td>
                            <td class="px-3 py-3" data-filter-value="{{ $supplierStatus }}"
                                data-sort-value="{{ $supplierStatus }}">
                                <span class="inline-flex whitespace-nowrap rounded-md border px-2 py-1 text-xs font-semibold {{ $statusClasses[$supplier->status] ?? $statusClasses[\App\Models\Supplier::STATUS_INACTIVE] }}">
                                    {{ $supplierStatus }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3"
                                data-filter-value="{{ $supplier->updated_at?->isToday() ? 'Hoy' : ($supplier->updated_at?->isYesterday() ? 'Ayer' : ($supplier->updated_at?->format('d/m/Y') ?: '—')) }}"
                                data-sort-value="{{ $supplier->updated_at?->format('Y-m-d') }}">
                                @if ($supplier->updated_at?->isToday())
                                    Hoy
                                @elseif ($supplier->updated_at?->isYesterday())
                                    Ayer
                                @else
                                    {{ $supplier->updated_at?->format('d/m/Y') ?: '—' }}
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.suppliers.show', $supplier) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-800 hover:bg-blue-50"
                                        title="Ver proveedor" aria-label="Ver {{ $supplier->name }}">
                                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('admin.suppliers.edit', $supplier) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-800 hover:bg-blue-50"
                                        title="Editar proveedor" aria-label="Editar {{ $supplier->name }}">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </a>
                                    <details class="relative">
                                        <summary class="inline-flex h-8 w-8 cursor-pointer list-none items-center justify-center rounded-md text-gray-600 hover:bg-gray-100"
                                            title="Mas acciones" aria-label="Mas acciones para {{ $supplier->name }}">
                                            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                        </summary>
                                        <div class="absolute right-0 z-20 mt-1 w-36 rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="block rounded px-3 py-2 text-xs hover:bg-gray-50">Consultar ficha</a>
                                            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="block rounded px-3 py-2 text-xs hover:bg-gray-50">Editar datos</a>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-14 text-center text-gray-500">
                                <i class="fa-solid fa-truck-field mb-3 block text-2xl text-gray-300" aria-hidden="true"></i>
                                No se encontraron proveedores con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="mt-4">{{ $suppliers->links() }}</div>
        @endif
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                window.createExcelColumnFilters({
                    tableId: 'supplier-catalog-table',
                    rowSelector: '.js-supplier-filter-row',
                    triggerSelector: '.js-supplier-column-filter',
                    instanceId: 'supplier-catalog',
                    onChange() {
                        document.querySelectorAll('.js-supplier-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });

                const table = document.getElementById('supplier-catalog-table');
                const tbody = table?.tBodies[0];
                const sortButtons = Array.from(table?.querySelectorAll('.js-supplier-column-sort') || []);
                let activeSortColumn = null;
                let activeSortDirection = 'asc';

                const sortableValue = (row, column, type) => {
                    const cell = row.cells[column];
                    const rawValue = String(cell?.dataset.sortValue ?? cell?.textContent ?? '').trim();

                    if (!rawValue) return null;
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
                        const rows = Array.from(tbody?.querySelectorAll('.js-supplier-filter-row') || []);

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
