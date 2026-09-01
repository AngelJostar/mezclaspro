<x-admin-layout>

    @php
        $statusFilter = App\Support\SolicitudStatusFilter::normalize(request()->query('estado'));
    @endphp

    <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-medium text-gray-800">Lista de Solicitudes</h1>
            @include('admin.solicitudes._type-selector', ['selectedType' => 'nutricionales'])
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 pb-1">
            <a class="inline-flex items-center gap-2 rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300"
                href="{{ route('admin.nutricionales.solicitudes.create') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Agregar
            </a>
            <a href="{{ route('admin.nutricionales.solicitudes.exportar') }}"
                class="inline-flex items-center gap-2 rounded-full bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                Exportar a Excel
            </a>
        </div>
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

    @include('admin.solicitudes._status-selector', [
        'activeStatus' => $statusFilter,
        'pendingApprovalCount' => $pendingApprovalCount,
        'routePendingCount' => $routePendingCount,
        'deliveryPendingCount' => $deliveryPendingCount,
    ])

    <div class="relative">

        <livewire:nutricionales.solicitudes-table :status-filter="$statusFilter" />

    </div>

    <div class="h-8" aria-hidden="true"></div>

    <livewire:nutricionales.inspeccion-nutricional />




    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            let nutritionRequestsFilterFrame = null;

            function initNutritionRequestsColumnFilters() {
                window.createExcelColumnFilters({
                    tableId: 'nutrition-requests-table',
                    rowSelector: '.js-nutrition-request-filter-row',
                    triggerSelector: '.js-nutrition-request-column-filter',
                    instanceId: 'nutrition-requests',
                    onChange() {
                        document.querySelectorAll('.js-nutrition-request-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });
            }

            function scheduleNutritionRequestsColumnFilters() {
                window.cancelAnimationFrame(nutritionRequestsFilterFrame);
                nutritionRequestsFilterFrame = window.requestAnimationFrame(initNutritionRequestsColumnFilters);
            }

            function registerNutritionRequestsLivewireHook() {
                if (!window.Livewire || window.__nutritionRequestsScrollbarHookRegistered) return;

                window.__nutritionRequestsScrollbarHookRegistered = true;
                window.Livewire.hook('morph.updated', () => {
                    scheduleNutritionRequestsColumnFilters();
                });
            }

            document.addEventListener('DOMContentLoaded', function() {

                initNutritionRequestsColumnFilters();
                registerNutritionRequestsLivewireHook();

                const initDataTable = () => {
                    const table = document.querySelector('#solicitudesTable');
                    if (!table || typeof DataTable === 'undefined') {
                        return;
                    }

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

            document.addEventListener('livewire:init', registerNutritionRequestsLivewireHook, { once: true });
            document.addEventListener('livewire:navigated', () => {
                scheduleNutritionRequestsColumnFilters();
            });
        </script>

        @if (session('swal'))
            <script>
                Swal.fire(@json(session('swal')));
            </script>
        @endif
    @endpush




</x-admin-layout>
