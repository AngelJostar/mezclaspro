<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Instituciones</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
            href="{{ route('admin.instituciones.create') }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar
        </a>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Id</th>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th scope="col" class="px-6 py-3">Razon social</th>
                    <th scope="col" class="px-6 py-3"></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($instituciones as $institucion)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $institucion->id }}
                        </th>

                        <td class="px-6 py-4">
                            {{ $institucion->nombre }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $institucion->razon_social }}
                        </td>

                        <td class="px-6 py-4">
                            <x-row-actions>
                                <a href="{{ route('admin.instituciones.edit', $institucion) }}">
                                    <i class="fa-solid fa-pen pr-1"></i> Editar
                                </a>

                                <a href="{{ route('admin.instituciones.hospitals', $institucion) }}">
                                    <i class="fa-solid fa-hospital pr-1"></i> Hospitales
                                </a>

                                <form action="{{ route('admin.instituciones.destroy', $institucion) }}" method="POST"
                                    onsubmit="return confirm('Seguro que deseas eliminar esta institucion?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="action-danger">
                                        <i class="fa-solid fa-trash pr-1"></i> Eliminar
                                    </button>
                                </form>

                                <a href="{{ route('admin.instituciones.exportarMezclasOnco', $institucion) }}"
                                    target="_blank">
                                    <i class="fa-solid fa-file-excel pr-1"></i> Reporte
                                </a>
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-6 text-center text-gray-500">
                            No hay instituciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $instituciones->links() }}
        </div>
    </div>
</x-admin-layout>
