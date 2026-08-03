<x-admin-layout>

    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Solicitudes</h1>
    </div>

    {{-- ERRORES --}}
    @if ($errors->any())
        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap justify-end mt-4">
        <div class="mb-4">
            <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                href="{{ route('admin.nutricionales.solicitudes.create') }}"><i class="fa-solid fa-plus pr-1"></i>
                Agregar</a>
        </div>
        <div class="mb-4">
            <a href="{{ route('admin.nutricionales.solicitudes.exportar') }}"
                class="text-white bg-green-600 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                <i class="fa-solid fa-file-excel pr-1"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <div class="relative overflow-x-auto">

        <livewire:nutricionales.solicitudes-table />

    </div>

    <livewire:nutricionales.inspeccion-nutricional />




    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const initDataTable = () => {
                    const table = document.querySelector('#solicitudesTable');
                    if (table.classList.contains('dataTable-initialized')) {
                        table.DataTable().destroy();
                    }

                    const dataTable = new DataTable(table, {
                        paging: false, // Desactivar paginación de DataTables
                        searching: true,
                        info: false,
                        order: [
                            [0, 'desc']
                        ], // Ordenar la primera columna (ID) de manera descendente
                        language: {
                            lengthMenu: "Mostrar _MENU_ registros por página",
                            zeroRecords: "Nada encontrado - lo siento",
                            info: "Mostrando página _PAGE_ de _PAGES_",
                            infoEmpty: "No hay registros disponibles",
                            infoFiltered: "(filtrado de _MAX_ registros totales)",
                            search: "Buscar:"
                        },
                    });

                    table.classList.add('dataTable-initialized');
                };

                initDataTable();


            });
        </script>

        @if (session('swal'))
            <script>
                Swal.fire(@json(session('swal')));
            </script>
        @endif
    @endpush




</x-admin-layout>
