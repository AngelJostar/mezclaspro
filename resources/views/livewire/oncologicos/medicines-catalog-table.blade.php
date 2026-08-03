<div>
    <form wire:submit.prevent="aplicarBusqueda">
        <div class="mb-4 flex items-center gap-2">
            <input type="text" wire:model.defer="buscar"
                placeholder="Buscar por ID, denominación, marca o presentación..."
                class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-1/3 p-2">

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-800">
                Buscar
            </button>
        </div>
    </form>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('id')">
                        ID
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('denominacion')">
                        Medicamento / Presentaciones comerciales
                        <span class="{{ $sortField === 'denominacion' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'denominacion' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3">
                        Estado
                    </th>

                    <th class="px-6 py-3">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($medicamentos as $med)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 align-top">
                            {{ $med->id }}
                        </td>

                        <td class="px-6 py-4 align-top">
                            <div class="font-semibold text-gray-800 dark:text-gray-100">
                                {{ $med->denominacion }}
                            </div>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @forelse ($med->presentations as $presentation)
                                    <div
                                        class="inline-flex flex-col rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs min-w-[170px]">
                                        <span class="font-bold text-gray-800">
                                            {{ $presentation->marca ?: 'Sin marca' }}
                                        </span>

                                        <span class="text-gray-700">
                                            {{ $presentation->presentacion ?: 'Sin presentación' }}
                                        </span>

                                        <span class="text-gray-500">
                                            {{ $presentation->cantidad_medicamento ?? '—' }} mg
                                            @if (!is_null($presentation->volumen_diluyente))
                                                · Diluyente: {{ $presentation->volumen_diluyente }} mL
                                            @endif
                                        </span>

                                        @if ($presentation->is_available)
                                            <span
                                                class="mt-1 inline-flex w-fit px-2 py-0.5 rounded bg-green-100 text-green-700">
                                                Disponible
                                            </span>
                                        @else
                                            <span
                                                class="mt-1 inline-flex w-fit px-2 py-0.5 rounded bg-red-100 text-red-700">
                                                No disponible
                                            </span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-gray-400 text-xs">
                                        Sin presentaciones registradas
                                    </span>
                                @endforelse
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top">
                            @if ($med->state)
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 align-top">
                            <x-row-actions>
                                <a href="{{ route('admin.oncologicos.medicines.catalog.presentations.index', ['catalog' => $med->id]) }}"
                                    class="">
                                    <i class="fas fa-edit mr-1"></i> Presentaciones
                                </a>

                                <a href="{{ route('admin.oncologicos.medicines.catalog.edit', $med->id) }}"
                                    class="">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>

                                <form action="{{ route('admin.oncologicos.medicines.catalog.destroy', $med->id) }}"
                                    method="POST" class="inline-block form-eliminar">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="eliminar-btn action-danger">
                                        <i class="fas fa-trash-alt mr-1"></i> Deshabilitar
                                    </button>
                                </form>
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No hay medicamentos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $medicamentos->links() }}
    </div>
</div>
