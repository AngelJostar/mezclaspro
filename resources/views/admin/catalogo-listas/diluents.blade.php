<x-admin-layout>
    @include('admin.catalogo-listas.partials.section-nav', [
        'categories' => $categories,
        'category' => $category,
        'mode' => $mode,
    ])

    <div class="rounded-lg border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">Catálogo de diluyentes</h2>
                <p class="text-sm text-gray-500">
                    Consulta los genéricos y sus presentaciones. Los lotes se registran desde almacenes.
                </p>
            </div>
            <a href="{{ route('admin.oncologicos.diluents.create') }}" class="rounded bg-green-600 px-4 py-2 text-sm font-bold text-white">
                Nuevo diluyente
            </a>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left">Insumo</th>
                        <th class="p-3 text-left">Presentación</th>
                        <th class="p-3 text-left">Nombre comercial</th>
                        <th class="p-3 text-left">Fabricante</th>
                        <th class="p-3 text-center">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($diluents->isEmpty())
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No hay diluyentes registrados.</td>
                        </tr>
                    @else
                        @foreach ($diluents as $diluent)
                            @if ($diluent->catalogPresentations->isEmpty())
                                <tr class="border-t">
                                    <td class="p-3 font-semibold">{{ $diluent->denominacion_generica }}</td>
                                    <td colspan="3" class="p-3 text-gray-500">Sin presentaciones</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('admin.oncologicos.diluents.edit', $diluent) }}" class="rounded bg-blue-900 px-3 py-2 text-xs font-bold text-white">Editar</a>
                                    </td>
                                </tr>
                            @else
                                @foreach ($diluent->catalogPresentations as $presentation)
                                    <tr class="border-t">
                                        <td class="p-3 font-semibold">{{ $diluent->denominacion_generica }}</td>
                                        <td class="p-3">{{ $presentation->presentation }}</td>
                                        <td class="p-3">{{ $presentation->commercial_name ?: '—' }}</td>
                                        <td class="p-3">{{ $presentation->manufacturer ?: '—' }}</td>
                                        <td class="p-3 text-center">
                                            <a href="{{ route('admin.oncologicos.diluents.edit', [$diluent, 'presentation_id' => $presentation->id]) }}" class="rounded bg-blue-900 px-3 py-2 text-xs font-bold text-white">Editar</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
