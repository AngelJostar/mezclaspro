<x-admin-layout>
    <div class="mt-2 mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">Subalmacén de insumos</h1>
            <p class="mt-1 text-sm text-gray-600">
                Central de mezclas: <strong>{{ $warehouse->laboratory?->nombre }}</strong>
                <span class="mx-1 text-gray-300">|</span>
                Almacén: <strong>{{ $warehouse->name }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $warehouse->laboratory_id, 'warehouse_id' => $warehouse->id]) }}"
                class="inline-flex items-center justify-center rounded bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                Volver a almacenes
            </a>
            <a href="{{ route('admin.oncologicos.diluents.create', ['laboratory_id' => $warehouse->laboratory_id, 'warehouse_id' => $warehouse->id]) }}"
                class="inline-flex items-center justify-center gap-2 rounded bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Agregar producto
            </a>
            <a href="{{ route('admin.oncologicos.diluents.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                <i class="fa-solid fa-list" aria-hidden="true"></i>
                Catálogo de insumos
            </a>
        </div>
    </div>

    <section class="border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center gap-x-8 gap-y-2 border-b border-gray-100 pb-4 text-sm text-gray-600">
            <span><strong class="text-lg text-gray-900">{{ (int) ($summary->batches_count ?? 0) }}</strong> lotes activos</span>
            <span><strong class="text-lg text-gray-900">{{ number_format((float) ($summary->stock_total ?? 0), 0) }}</strong> piezas disponibles</span>
        </div>

        <form method="GET" action="{{ route('admin.warehouses.supplies.index', $warehouse) }}" class="mb-4 flex flex-col gap-2 sm:flex-row">
            <label for="supplies-search" class="sr-only">Buscar insumo</label>
            <input id="supplies-search" type="search" name="search" value="{{ $search }}"
                placeholder="Buscar insumo, presentación o lote..."
                class="w-full rounded border-gray-300 text-sm sm:max-w-xl">
            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Buscar</button>
            <a href="{{ route('admin.warehouses.supplies.index', $warehouse) }}"
                class="rounded bg-gray-100 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-200">Limpiar</a>
        </form>

        <div class="overflow-x-auto border border-gray-200">
            <table class="min-w-[1050px] w-full border-collapse text-left text-sm text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="border border-gray-200 px-3 py-2">Insumo</th>
                        <th class="border border-gray-200 px-3 py-2">Presentación</th>
                        <th class="border border-gray-200 px-3 py-2">Nombre comercial</th>
                        <th class="border border-gray-200 px-3 py-2">Fabricante</th>
                        <th class="border border-gray-200 px-3 py-2">Lote</th>
                        <th class="border border-gray-200 px-3 py-2">Caducidad</th>
                        <th class="border border-gray-200 px-3 py-2 text-right">Existencia</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Estado</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplies as $supply)
                        <tr class="bg-white hover:bg-blue-50/40">
                            <td class="border border-gray-200 px-3 py-2 font-semibold text-gray-900">{{ $supply->diluent?->denominacion_generica ?: '—' }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $supply->presentacion ?: '—' }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $supply->denominacion_comercial ?: '—' }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $supply->fabricante ?: '—' }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $supply->lote ?: '—' }}</td>
                            <td class="whitespace-nowrap border border-gray-200 px-3 py-2">{{ $supply->caducidad?->format('d/m/Y') ?: '—' }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right font-semibold">{{ number_format((float) $supply->stock_actual, 0) }} piezas</td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $supply->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                    {{ $supply->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <a href="{{ route('admin.oncologicos.diluent_presentations.edit', [$supply->diluent_id, $supply]) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded bg-blue-900 text-white hover:bg-blue-800"
                                    title="Editar insumo" aria-label="Editar insumo">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-gray-200 px-4 py-8 text-center text-gray-500">
                                Este almacén no tiene insumos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $supplies->links() }}</div>
    </section>
</x-admin-layout>
