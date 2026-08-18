<x-admin-layout>
    <div class="mt-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold text-gray-800">Infusores</h1>
            <a href="{{ route('admin.oncologicos.infusores.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                + Nuevo Infusor
            </a>
        </div>

        {{-- Mensajes de éxito --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full text-sm text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Nombre genérico</th>
                        <th class="px-4 py-2 text-left">Nombre comercial</th>
                        <th class="px-4 py-2 text-left">Lote</th>
                        <th class="px-4 py-2 text-left">Caducidad</th>
                        <th class="px-4 py-2 text-center">Estado</th>
                        <th class="px-4 py-2 text-center">Editar</th>
                        <th class="px-4 py-2 text-center">Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($infusores as $infusor)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $infusor->id }}</td>
                            <td class="px-4 py-2">{{ $infusor->nombre_generico ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $infusor->nombre_comercial ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $infusor->lote ?? '-' }}</td>
                            <td class="px-4 py-2">
                                {{ $infusor->caducidad ? $infusor->caducidad->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if ($infusor->is_active)
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activo</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <x-table-action-link href="{{ route('admin.oncologicos.infusores.edit', $infusor) }}" icon="fa-solid fa-pen">
                                    Editar
                                </x-table-action-link>
                            </td>

                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                {{-- Eliminar --}}
                                <form action="{{ route('admin.oncologicos.infusores.destroy', $infusor) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar este infusor?');">
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
                            <td colspan="8" class="px-4 py-3 text-center text-gray-500">
                                No hay infusores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-4">
            {{ $infusores->links() }}
        </div>
    </div>
</x-admin-layout>
