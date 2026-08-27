<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($laboratories->isNotEmpty())
            <section class="border-b border-gray-200 pb-4" aria-labelledby="warehouse-laboratory-carousel-title">
                <div class="mb-3">
                    <h2 id="warehouse-laboratory-carousel-title" class="text-sm font-semibold text-gray-800">Selecciona un laboratorio</h2>
                    <p class="text-xs text-gray-500">Los almacenes e inventarios se muestran para el laboratorio seleccionado.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="warehouse-laboratory-previous"
                        class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-300 bg-white text-2xl leading-none text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Laboratorio anterior" aria-label="Laboratorio anterior">
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>

                    <div id="warehouse-laboratory-carousel" class="flex min-w-0 flex-1 snap-x items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                        @foreach ($laboratories as $laboratory)
                            @php
                                $isSelectedLaboratory = $selectedLaboratory?->id === $laboratory->id;
                            @endphp
                            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratory->id]) }}"
                                class="block w-56 flex-none snap-start rounded border p-3 transition {{ $isSelectedLaboratory ? 'border-cyan-500 bg-cyan-50' : 'border-gray-200 bg-white hover:border-gray-400' }}"
                                style="width: 14rem;"
                                @if ($isSelectedLaboratory) aria-current="true" @endif>
                                <div class="flex items-start gap-2">
                                    <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded bg-cyan-50 text-sm text-cyan-800">
                                        <i class="fa-solid fa-flask-vial" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $laboratory->nombre }}</span>
                                        <span class="mt-1 block line-clamp-2 text-xs leading-4 text-gray-500">{{ $laboratory->direccion ?: 'Direccion sin registrar' }}</span>
                                    </span>
                                </div>
                                <span class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                                    <i class="fa-solid fa-warehouse text-cyan-700" aria-hidden="true"></i>
                                    {{ $laboratory->warehouses_count }} {{ $laboratory->warehouses_count === 1 ? 'almacen' : 'almacenes' }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <button type="button" id="warehouse-laboratory-next"
                        class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-300 bg-white text-2xl leading-none text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Laboratorio siguiente" aria-label="Laboratorio siguiente">
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </div>
            </section>

            <section class="mt-5" aria-labelledby="warehouse-list-title">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="warehouse-list-title" class="text-base font-semibold text-gray-900">Almacenes del laboratorio</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.warehouses.create', ['laboratory_id' => $selectedLaboratory->id]) }}"
                            class="inline-flex items-center justify-center gap-2 rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Crear nuevo almacen
                        </a>
                        <a href="{{ route('admin.warehouses.purchase-orders.index', ['laboratory_id' => $selectedLaboratory->id]) }}"
                            class="inline-flex items-center justify-center gap-2 rounded bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                            Ordenes de compra
                        </a>
                    </div>
                </div>

                <div class="mt-3">
                    @if ($warehouses->isNotEmpty())
                        <div class="mt-2 flex gap-3 overflow-x-auto pb-2">
                            @foreach ($warehouses as $warehouse)
                                @php
                                    $isSelectedWarehouse = $selectedWarehouse?->id === $warehouse->id;
                                @endphp
                                <article
                                    class="w-64 flex-none rounded border p-3 transition {{ $isSelectedWarehouse ? 'border-blue-600 bg-blue-50' : 'border-gray-200 bg-white hover:border-blue-300' }}"
                                    @if ($isSelectedWarehouse) aria-current="true" @endif>
                                    <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $selectedLaboratory->id, 'warehouse_id' => $warehouse->id]) }}"
                                        class="block">
                                        <span class="flex items-start gap-3">
                                            <span class="inline-flex h-9 w-9 flex-none items-center justify-center rounded bg-gray-100 text-gray-700">
                                                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-gray-900">{{ $warehouse->name }}</span>
                                                <span class="mt-1 block line-clamp-2 text-xs leading-5 text-gray-500">{{ $warehouse->address ?: 'Direccion sin registrar' }}</span>
                                            </span>
                                        </span>
                                    </a>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $warehouse->is_active ? 'text-green-700' : 'text-red-700' }}">
                                            <span class="h-2 w-2 rounded-full {{ $warehouse->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                            {{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        <span class="flex items-center gap-2">
                                            @can('oncologicos_laboratory_destroy')
                                                <form method="POST" action="{{ route('admin.warehouses.destroy', $warehouse) }}"
                                                    class="!w-auto" data-delete-warehouse data-warehouse-name="{{ $warehouse->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded border border-red-600 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endcan

                                            <a href="{{ route('admin.warehouses.edit', $warehouse) }}"
                                                class="inline-flex items-center gap-1.5 rounded bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                Editar
                                            </a>
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-2 border border-dashed border-gray-300 px-5 py-8 text-center">
                            <p class="text-sm text-gray-600">Este laboratorio todavia no tiene almacenes registrados.</p>
                        </div>
                    @endif
                </div>
            </section>

            @php
                $inventoryModules = [
                    [
                        'key' => 'oncologicos',
                        'title' => 'Inventario oncologico',
                        'description' => 'Medicamentos, lotes, caducidades y existencias.',
                        'icon' => 'fa-capsules',
                        'color' => 'text-blue-800 bg-blue-50',
                        'url' => route('admin.oncologicos.inventory.index', ['laboratory_id' => $selectedLaboratory->id, 'category' => 'oncologicos']),
                    ],
                    [
                        'key' => 'antibioticos',
                        'title' => 'Inventario de antibioticos',
                        'description' => 'Antibioticos, lotes, caducidades y existencias.',
                        'icon' => 'fa-prescription-bottle-medical',
                        'color' => 'text-red-800 bg-red-50',
                        'url' => route('admin.oncologicos.inventory.index', ['laboratory_id' => $selectedLaboratory->id, 'category' => 'antibioticos']),
                    ],
                    [
                        'key' => 'nutricionales',
                        'title' => 'Inventario nutricional',
                        'description' => 'Insumos, presentaciones y existencias por frasco.',
                        'icon' => 'fa-droplet',
                        'color' => 'text-green-800 bg-green-50',
                        'url' => route('admin.nutricionales.stocks.index', ['laboratory_id' => $selectedLaboratory->id]),
                    ],
                ];
            @endphp

            <section class="mt-6 border-t border-gray-200 pt-5" aria-labelledby="warehouse-inventory-title">
                <div>
                    <h2 id="warehouse-inventory-title" class="text-base font-semibold text-gray-900">Inventarios de centrales de mezclas</h2>
                    <p class="mt-1 text-sm text-gray-500">Consulta las existencias del laboratorio seleccionado por tipo de mezcla.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
                    @foreach ($inventoryModules as $module)
                        @php($summary = $inventorySummary[$module['key']] ?? null)
                        <a href="{{ $module['url'] }}" class="group rounded border border-gray-200 p-4 hover:border-blue-400 hover:bg-gray-50">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded {{ $module['color'] }}">
                                    <i class="fa-solid {{ $module['icon'] }}" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gray-900">{{ $module['title'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-gray-500">{{ $module['description'] }}</span>
                                </span>
                                <i class="fa-solid fa-chevron-right mt-1 text-xs text-gray-400 group-hover:text-blue-700" aria-hidden="true"></i>
                            </div>
                            <div class="mt-4 flex items-center gap-5 border-t border-gray-100 pt-3 text-xs text-gray-600">
                                <span><strong class="text-gray-900">{{ (int) ($summary->batches_count ?? 0) }}</strong> lotes</span>
                                <span><strong class="text-gray-900">{{ number_format((float) ($summary->stock_total ?? 0), 0) }}</strong> frascos</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @else
            <div class="mt-6 border border-dashed border-gray-300 px-6 py-12 text-center">
                <p class="text-sm font-medium text-gray-700">Primero registra un laboratorio para crear sus almacenes.</p>
                <a href="{{ route('admin.oncologicos.laboratory.create') }}"
                    class="mt-4 inline-flex items-center justify-center gap-2 rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Nuevo laboratorio
                </a>
            </div>
        @endif
    </div>

    @push('js')
        <script>
            (() => {
                const carousel = document.getElementById('warehouse-laboratory-carousel');
                const previous = document.getElementById('warehouse-laboratory-previous');
                const next = document.getElementById('warehouse-laboratory-next');

                if (!carousel || !previous || !next) return;

                const updateNavigation = () => {
                    const maximumScroll = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
                    previous.disabled = carousel.scrollLeft <= 1;
                    next.disabled = carousel.scrollLeft >= maximumScroll - 1;
                };

                const move = (direction) => carousel.scrollBy({ left: direction * 300, behavior: 'smooth' });
                previous.addEventListener('click', () => move(-1));
                next.addEventListener('click', () => move(1));
                carousel.addEventListener('scroll', updateNavigation, { passive: true });
                window.addEventListener('resize', updateNavigation);
                requestAnimationFrame(updateNavigation);

                document.querySelectorAll('[data-delete-warehouse]').forEach((form) => {
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const warehouseName = form.dataset.warehouseName || 'este almacen';
                        const result = await Swal.fire({
                            title: '¿Eliminar almacén?',
                            text: `Se eliminará ${warehouseName}. Esta acción no se puede deshacer.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                            customClass: {
                                confirmButton: 'warehouse-delete-confirm',
                                cancelButton: 'warehouse-delete-cancel',
                            },
                        });

                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            })();
        </script>
    @endpush
</x-admin-layout>
