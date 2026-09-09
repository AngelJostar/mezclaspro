<table class="w-full min-w-[800px] divide-y divide-gray-200 text-xs" id="catalogTable">
    <thead class="bg-gray-50 text-gray-700">
        <tr>
            <x-filterable-table-header column="0" trigger-class="js-catalog-column-filter">Insumo</x-filterable-table-header>
            <x-filterable-table-header column="1" trigger-class="js-catalog-column-filter">Presentacion</x-filterable-table-header>
            <x-filterable-table-header column="2" trigger-class="js-catalog-column-filter">Nombre comercial</x-filterable-table-header>
            <x-filterable-table-header column="3" trigger-class="js-catalog-column-filter">Fabricante</x-filterable-table-header>
            <x-filterable-table-header column="4" trigger-class="js-catalog-column-filter">Unidad</x-filterable-table-header>
            <th class="px-3 py-2 text-center font-bold uppercase">Editar</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 bg-white">
        @forelse ($consumables as $item)
            @foreach ($item->catalogPresentations->isEmpty() ? [null] : $item->catalogPresentations as $presentation)
                <tr class="catalog-row hover:bg-gray-50"
                    data-search="{{ Str::lower(implode(' ', [$item->name, $presentation?->presentation, $presentation?->commercial_name, $presentation?->manufacturer, $item->unit])) }}">
                    <td class="px-3 py-2 font-semibold text-gray-900">{{ $item->name }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $presentation?->presentation ?: 'Sin presentaciones' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $presentation?->commercial_name ?: '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $presentation?->manufacturer ?: '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $item->unit }}</td>
                    <td class="px-3 py-2 text-center">
                        <x-table-action-link href="{{ route('admin.consumables.catalog.edit', $item) }}" icon="fa-solid fa-pen">Editar</x-table-action-link>
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">No hay consumibles registrados.</td></tr>
        @endforelse
    </tbody>
</table>
