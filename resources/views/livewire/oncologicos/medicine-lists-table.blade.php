<div>
    <form wire:submit.prevent="aplicarBusqueda">
        <div class="mb-4 flex items-center gap-2">
            <input
                type="text"
                wire:model.defer="buscar"
                placeholder="Buscar por ID, lista, hospital, tipo de cobro..."
                class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-1/3 p-2"
            >
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-800">
                Buscar
            </button>
        </div>
    </form>

    <div class="relative overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('id')">
                        ID
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('name')">
                        Nombre de la lista
                        <span class="{{ $sortField === 'name' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3">
                        Presentaciones configuradas
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('charge_by')">
                        Tipo de cobro
                        <span class="{{ $sortField === 'charge_by' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'charge_by' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('active_brands')">
                        Marcas
                        <span class="{{ $sortField === 'active_brands' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'active_brands' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('hospital_name')">
                        Hospital
                        <span class="{{ $sortField === 'hospital_name' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'hospital_name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 text-center">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($listas as $lista)
                    <tr class="bg-white border-b last:border-b-0">
                        <td class="px-6 py-2 text-gray-500">
                            {{ $lista->id }}
                        </td>

                        <td class="px-6 py-2">
                            <div class="font-semibold text-gray-800">
                                {{ $lista->name }}
                            </div>
                            @if ($lista->description)
                                <div class="text-xs text-gray-500">
                                    {{ \Illuminate\Support\Str::limit($lista->description, 80) }}
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-2">
                            {{ $lista->presentations?->count() ?? 0 }}
                        </td>

                        <td class="px-6 py-2">
                            @if ($lista->charge_by === 'mg')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    Cobro por mg
                                </span>
                            @elseif ($lista->charge_by === 'frasco')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Cobro por frasco
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-2">
                            @if ($lista->active_brands)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    Marcas activas
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                    Solo genéricos
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-2">
                            {{ $lista->hospital->name ?? '—' }}
                        </td>

                        <td class="px-6 py-2 text-center">
                            <x-row-actions>
                            <a href="{{ route('admin.oncologicos.medicines.edit', $lista->id) }}"
                                class="">
                                Editar
                            </a>

                            <form id="delete-form-{{ $lista->id }}"
                                action="{{ route('admin.oncologicos.medicines.destroy', $lista->id) }}"
                                method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                    class="btn-eliminar action-danger"
                                    data-id="{{ $lista->id }}">
                                    Eliminar
                                </button>
                            </form>

                            <a href="{{ route('admin.oncologicos.medicines.exportar', $lista) }}" target="_blank"
                                class="">
                                <i class="fa-solid fa-file-excel pr-1"></i> Exportar
                            </a>
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Tu hospital aún no tiene una lista de precios configurada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $listas->links() }}
    </div>
</div>
