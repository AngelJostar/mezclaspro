<x-admin-layout>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">Subalmacén de diluyentes</h1>
            <p class="mt-1 text-sm text-gray-600">Central de mezclas: <strong>{{ $warehouse->laboratory?->nombre }}</strong><span class="mx-1 text-gray-300">|</span>Almacén: <strong>{{ $warehouse->name }}</strong></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $warehouse->laboratory_id, 'warehouse_id' => $warehouse->id]) }}" class="rounded bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">Volver a almacenes</a>
            <a href="{{ route('admin.warehouses.supplies.create', $warehouse) }}" class="rounded bg-purple-600 px-4 py-2 text-sm font-semibold text-white">Agregar producto</a>
            <a href="{{ route('admin.warehouses.consumables.index', $warehouse) }}" class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Consumibles</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.warehouses.supplies.index', $warehouse) }}" class="mb-4 rounded border border-gray-200 bg-white p-4">
        <div class="flex flex-col gap-2 sm:flex-row">
            <input type="search" name="search" value="{{ $search }}" placeholder="Denominación, presentación, nombre comercial o lote..." class="w-full rounded border-gray-300 text-sm">
            <button class="rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Buscar</button>
            <a href="{{ route('admin.warehouses.supplies.index', $warehouse) }}" class="rounded bg-gray-100 px-4 py-2 text-center text-sm font-semibold text-gray-700">Limpiar</a>
        </div>
    </form>

    @forelse ($supplies->groupBy('diluent_id') as $diluentLots)
        @php($firstLot = $diluentLots->first())
        <section class="mb-5 overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
            <div class="flex items-start justify-between border-b border-gray-200 p-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $firstLot->diluent?->denominacion_generica ?: 'Diluyente' }}</h2>
                    <p class="text-sm text-gray-500">{{ $diluentLots->count() }} lote(s) registrado(s)</p>
                </div>
                <div class="text-right text-sm text-gray-600">Total disponible<br><strong class="text-lg text-gray-900">{{ number_format($diluentLots->sum('stock_actual'), 0) }} piezas</strong></div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="p-3 text-left">Presentación</th><th class="p-3 text-left">Nombre comercial</th><th class="p-3 text-left">Fabricante</th><th class="p-3 text-left">Lote</th><th class="p-3 text-left">Caducidad</th><th class="p-3 text-right">Stock del lote</th><th class="p-3 text-center">Estado</th></tr></thead>
                    <tbody>
                        @foreach ($diluentLots as $lot)
                            <tr class="border-t"><td class="p-3">{{ $lot->presentacion }}</td><td class="p-3">{{ $lot->denominacion_comercial ?: '—' }}</td><td class="p-3">{{ $lot->fabricante ?: '—' }}</td><td class="p-3">{{ $lot->lote }}</td><td class="p-3">{{ $lot->caducidad?->format('d/m/Y') ?: '—' }}</td><td class="p-3 text-right font-semibold text-green-700">{{ number_format($lot->stock_actual, 0) }} piezas</td><td class="p-3 text-center"><span class="rounded-full px-2 py-1 text-xs {{ $lot->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $lot->is_active ? 'Disponible' : 'Inactivo' }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <section class="rounded border border-gray-200 bg-white p-10 text-center text-gray-500">No hay lotes de diluyentes en este almacén. Usa <strong>Agregar producto</strong> para registrar el primero.</section>
    @endforelse
</x-admin-layout>
