<x-admin-layout>

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

    <div class="relative">

        <livewire:nutricionales.solicitudes-table />

    </div>

    <div id="nutrition-requests-fixed-scrollbar"
        class="hidden fixed bottom-0 z-50 border-t border-gray-300 bg-white/95 py-1 shadow-[0_-4px_12px_rgba(15,23,42,0.15)]">
        <div class="js-nutrition-requests-fixed-scrollbar overflow-x-auto">
            <div id="nutrition-requests-fixed-scrollbar-spacer" class="h-1"></div>
        </div>
    </div>

    <div class="h-8" aria-hidden="true"></div>

    <livewire:nutricionales.inspeccion-nutricional />




    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            let nutritionRequestsScrollbarFrame = null;
            let nutritionRequestsScrollbarCleanup = null;
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

            function initNutritionRequestsFixedScrollbar() {
                nutritionRequestsScrollbarCleanup?.();

                const tableScroll = document.getElementById('nutrition-requests-table-scroll');
                const fixedWrapper = document.getElementById('nutrition-requests-fixed-scrollbar');
                const fixedScroll = fixedWrapper?.querySelector('.js-nutrition-requests-fixed-scrollbar');
                const spacer = document.getElementById('nutrition-requests-fixed-scrollbar-spacer');

                if (!tableScroll || !fixedWrapper || !fixedScroll || !spacer) {
                    return;
                }

                const controller = new AbortController();
                const options = { signal: controller.signal };
                let syncing = false;

                function updateFixedScrollbar() {
                    const hasHorizontalScroll = tableScroll.scrollWidth > tableScroll.clientWidth + 1;
                    const tableRect = tableScroll.getBoundingClientRect();

                    fixedWrapper.style.left = `${tableRect.left + tableScroll.clientLeft}px`;
                    fixedWrapper.style.width = `${tableScroll.clientWidth}px`;
                    fixedWrapper.classList.toggle('hidden', !hasHorizontalScroll);
                    spacer.style.width = `${tableScroll.scrollWidth}px`;
                    fixedScroll.scrollLeft = tableScroll.scrollLeft;
                }

                tableScroll.addEventListener('scroll', () => {
                    if (syncing) return;
                    syncing = true;
                    fixedScroll.scrollLeft = tableScroll.scrollLeft;
                    syncing = false;
                }, options);

                fixedScroll.addEventListener('scroll', () => {
                    if (syncing) return;
                    syncing = true;
                    tableScroll.scrollLeft = fixedScroll.scrollLeft;
                    syncing = false;
                }, options);

                window.addEventListener('resize', updateFixedScrollbar, options);
                window.addEventListener('scroll', updateFixedScrollbar, { ...options, passive: true });

                const resizeObserver = new ResizeObserver(updateFixedScrollbar);
                resizeObserver.observe(tableScroll);
                resizeObserver.observe(tableScroll.firstElementChild || tableScroll);

                nutritionRequestsScrollbarCleanup = () => {
                    controller.abort();
                    resizeObserver.disconnect();
                };

                updateFixedScrollbar();
                window.requestAnimationFrame(updateFixedScrollbar);
            }

            function scheduleNutritionRequestsFixedScrollbar() {
                window.cancelAnimationFrame(nutritionRequestsScrollbarFrame);
                nutritionRequestsScrollbarFrame = window.requestAnimationFrame(initNutritionRequestsFixedScrollbar);
            }

            function registerNutritionRequestsLivewireHook() {
                if (!window.Livewire || window.__nutritionRequestsScrollbarHookRegistered) return;

                window.__nutritionRequestsScrollbarHookRegistered = true;
                window.Livewire.hook('morph.updated', () => {
                    scheduleNutritionRequestsFixedScrollbar();
                    scheduleNutritionRequestsColumnFilters();
                });
            }

            document.addEventListener('DOMContentLoaded', function() {

                initNutritionRequestsFixedScrollbar();
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
                scheduleNutritionRequestsFixedScrollbar();
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
