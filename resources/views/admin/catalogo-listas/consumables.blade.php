<x-admin-layout>
    @include('admin.catalogo-listas.partials.section-nav', ['categories' => $categories, 'category' => $category, 'mode' => $mode])
    <div class="rounded-lg border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold">Catálogo de consumibles</h2><p class="text-sm text-gray-500">Administra los insumos y sus presentaciones. Los lotes se registran desde almacenes.</p></div><a href="{{ route('admin.consumables.catalog.create') }}" class="rounded bg-green-600 px-4 py-2 text-sm font-bold text-white">Agregar consumible</a></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Insumo</th><th class="p-3 text-left">Presentación</th><th class="p-3 text-left">Nombre comercial</th><th class="p-3 text-left">Fabricante</th><th class="p-3 text-left">Unidad</th><th class="p-3 text-center">Editar</th></tr></thead><tbody>
            @forelse($consumables as $consumable)
                @if($consumable->catalogPresentations->isEmpty())
                    <tr class="border-t"><td class="p-3 font-semibold">{{ $consumable->name }}</td><td colspan="3" class="p-3 text-gray-500">Sin presentaciones</td><td class="p-3">{{ $consumable->unit }}</td><td class="p-3 text-center"><a href="{{ route('admin.consumables.catalog.edit', $consumable) }}" class="rounded bg-blue-900 px-3 py-2 text-xs font-bold text-white">Editar</a></td></tr>
                @else
                    @foreach($consumable->catalogPresentations as $presentation)
                        <tr class="border-t"><td class="p-3 font-semibold">{{ $consumable->name }}</td><td class="p-3">{{ $presentation->presentation }}</td><td class="p-3">{{ $presentation->commercial_name ?: '—' }}</td><td class="p-3">{{ $presentation->manufacturer ?: '—' }}</td><td class="p-3">{{ $consumable->unit }}</td><td class="p-3 text-center"><a href="{{ route('admin.consumables.catalog.edit', $consumable) }}" class="rounded bg-blue-900 px-3 py-2 text-xs font-bold text-white">Editar</a></td></tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="6" class="p-6 text-center text-gray-500">No hay consumibles registrados.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</x-admin-layout>
