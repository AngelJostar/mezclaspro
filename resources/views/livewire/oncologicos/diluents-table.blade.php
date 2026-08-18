<div>
    <form wire:submit.prevent="aplicarBusqueda" class="p-4">
        <div class="flex items-center gap-2">
            <input
                type="text"
                wire:model.defer="buscar"
                placeholder="Buscar diluyente..."
                class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-1/3 p-2"
            >
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-800">
                Buscar
            </button>
        </div>
    </form>

    <table class="min-w-full text-sm">
        <thead class="bg-gray-100 text-gray-600">
            <tr>
                <th class="px-4 py-3 text-left cursor-pointer" wire:click="sortBy('denominacion_generica')">
                    Denominación genérica
                    <span class="{{ $sortField === 'denominacion_generica' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                        {!! $sortField === 'denominacion_generica' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                    </span>
                </th>
                <th class="px-4 py-3 text-center">Editar</th>
                <th class="px-4 py-3 text-center">Presentaciones</th>
                <th class="px-4 py-3 text-center">Eliminar</th>
            </tr>
        </thead>

        <tbody class="divide-y">
            @forelse ($diluents as $d)
                <tr>
                    <td class="px-4 py-3">{{ $d->denominacion_generica }}</td>

                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <x-table-action-link href="{{ route('admin.oncologicos.diluents.edit', $d) }}" icon="fa-solid fa-pen">
                            Editar
                        </x-table-action-link>
                    </td>

                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <x-table-action-link href="{{ route('admin.oncologicos.diluent_presentations.index', $d) }}" icon="fa-solid fa-layer-group">
                            Presentaciones
                        </x-table-action-link>
                    </td>

                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <form action="{{ route('admin.oncologicos.diluents.destroy', $d) }}"
                            method="POST" class="inline-block form-eliminar-diluent">
                            @csrf
                            @method('DELETE')
                            <x-table-action-button type="submit" variant="red" icon="fa-solid fa-trash">
                                Eliminar
                            </x-table-action-button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-6 text-center text-gray-500" colspan="4">
                        No hay diluyentes.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $diluents->links() }}
    </div>
</div>
