<x-admin-layout>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            Listas nutricionales
        </h1>

        <a href="{{ route('admin.nutricionales.nutri-medicine-lists.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Nueva lista
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Nombre</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Descripción</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Presentaciones</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Marcas</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Estatus</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Editar</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Ver</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Eliminar</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($lists as $list)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">
                                {{ $list->name }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            {{ $list->description ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs font-semibold">
                                {{ $list->items_count }} presentaciones
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            @if($list->active_brands)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">
                                    Activas
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                    Inactivas
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            @if($list->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">
                                    Activa
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">
                                    Inactiva
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.nutricionales.nutri-medicine-lists.edit', $list) }}" icon="fa-solid fa-pen">
                                Editar
                            </x-table-action-link>
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.nutricionales.nutri-medicine-lists.show', $list) }}" icon="fa-solid fa-eye">
                                Ver
                            </x-table-action-link>
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">

                            <form action="{{ route('admin.nutricionales.nutri-medicine-lists.destroy', $list) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                @method('DELETE')

                                <x-table-action-button type="submit"
                                        onclick="return confirm('¿Deseas eliminar esta lista?')"
                                        variant="red"
                                        icon="fa-solid fa-trash">
                                    Eliminar
                                </x-table-action-button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                            No hay listas nutricionales registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $lists->links() }}
    </div>
</x-admin-layout>
