<x-admin-layout>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div><h1 class="text-2xl font-semibold">Subalmacén de consumibles</h1><p class="text-sm text-slate-500">{{ $warehouse->laboratory->nombre }} · {{ $warehouse->name }}</p></div>
        <div class="flex flex-wrap gap-2">
            <a class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.production-supplies.index') }}">Solicitudes de consumibles</a>
            <a class="rounded bg-purple-600 px-4 py-2 font-semibold text-white" href="{{ route('admin.warehouses.consumables.create', $warehouse) }}">Agregar producto</a>
        </div>
    </div>
    @if(session('success'))<div class="mb-4 rounded bg-green-50 p-3 text-green-800">{{ session('success') }}</div>@endif
    @forelse($lots->getCollection()->groupBy('consumable_item_id') as $itemLots)
        @php($first = $itemLots->first())
        <section class="mb-5 overflow-hidden rounded border bg-white"><div class="flex items-start justify-between border-b p-4"><div><h2 class="text-lg font-semibold">{{ $first->item->name }}</h2><p class="text-sm text-gray-500">{{ $itemLots->count() }} lote(s) registrado(s)</p></div><div class="text-right">Total disponible<br><strong>{{ number_format($itemLots->sum('stock_actual'), 0) }} {{ $first->item->unit }}</strong></div></div><div class="overflow-x-auto"><table class="min-w-[760px] w-full text-sm"><thead class="bg-gray-50 text-left"><tr><th class="p-3">Presentación</th><th class="p-3">Nombre comercial</th><th class="p-3">Fabricante</th><th class="p-3">Lote</th><th class="p-3">Caducidad</th><th class="p-3 text-right">Existencia</th></tr></thead><tbody>@foreach($itemLots as $lot)<tr class="border-t"><td class="p-3">{{ $lot->presentation ?: '—' }}</td><td class="p-3">{{ $lot->brand ?: '—' }}</td><td class="p-3">{{ $lot->manufacturer ?: '—' }}</td><td class="p-3">{{ $lot->lot ?: '—' }}</td><td class="p-3">{{ $lot->expires_at?->format('d/m/Y') ?: '—' }}</td><td class="p-3 text-right font-semibold text-green-700">{{ number_format($lot->stock_actual, 0) }} {{ $lot->item->unit }}</td></tr>@endforeach</tbody></table></div></section>
    @empty
        <div class="rounded border bg-white p-10 text-center text-slate-500">No hay consumibles registrados. Usa <strong>Agregar producto</strong> para registrar un lote.</div>
    @endforelse
    <div class="mt-4">{{ $lots->links() }}</div>
</x-admin-layout>
