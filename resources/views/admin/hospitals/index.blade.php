<x-admin-layout>

    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Hospitales</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
            href="{{ route('admin.hospitals.create') }}"><i class="fa-solid fa-plus pr-1"></i> Agregar</a>
    </div>

    <div class="relative overflow-x-auto">
        <table id="hospitalsTable" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th scope="col" class="px-6 py-3">Instituciones</th>
                    <th scope="col" class="px-6 py-3">Direccion</th>
                    <th scope="col" class="px-6 py-3">Estado</th>
                    <th scope="col" class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hospitals as $hospital)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $hospital->id }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $hospital->name }}
                        </td>
                        <td class="px-6 py-4">
                            @forelse ($hospital->instituciones as $institucion)
                                <div>{{ $institucion->nombre }}</div>
                            @empty
                                <span class="text-gray-400">Sin institucion</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4">
                            {{ $hospital->adress }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($hospital->is_active)
                                <div class="flex items-center">
                                    <div class="h-2.5 w-2.5 rounded-full bg-green-500 me-2"></div> Activo
                                </div>
                            @else
                                <div class="flex items-center">
                                    <div class="h-2.5 w-2.5 rounded-full bg-red-500 me-2"></div> Inactivo
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-row-actions>
                                <a href="{{ route('admin.hospitals.edit', $hospital) }}">
                                    <i class="fa-solid fa-pen pr-1"></i> Editar
                                </a>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new DataTable('#hospitalsTable', {
                    order: [
                        [0, 'desc']
                    ],
                    language: {
                        lengthMenu: "Mostrar _MENU_ registros por pagina",
                        zeroRecords: "Nada encontrado",
                        info: "Mostrando pagina _PAGE_ de _PAGES_",
                        infoEmpty: "No hay registros disponibles",
                        infoFiltered: "(filtrado de _MAX_ registros totales)",
                        search: "Buscar:",
                    }
                });
            });
        </script>
    @endpush

</x-admin-layout>
