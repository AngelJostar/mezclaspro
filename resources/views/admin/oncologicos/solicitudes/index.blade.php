<x-admin-layout>

    @php
        $requestType = $requestType ?? 'oncologicos';
        $isAntibiotic = $requestType === 'antibioticos';
    @endphp

    <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-medium text-gray-800">
                Lista de Solicitudes {{ $isAntibiotic ? 'de Antibioticos' : 'Oncologicas' }}
            </h1>
            @include('admin.solicitudes._type-selector', ['selectedType' => $requestType])
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 pb-1">
            <a class="inline-flex items-center gap-2 rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300"
                href="{{ route('admin.oncologicos.solicitudes.create', ['tipo_solicitud' => $requestType]) }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Agregar
            </a>

            @unless ($isAntibiotic)
                <a href="{{ route('admin.oncologicos.solicitudes.exportar') }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-full bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    Exportar a Excel
                </a>
            @endunless
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

    {{-- MENSAJES SWEETALERT --}}
    @if (session('swal'))
        @push('js')
            <script>
                Swal.fire(@json(session('swal')));
            </script>
        @endpush
    @endif

    <div class="relative">
        <livewire:oncologicos.solicitudes-table :request-type="$requestType" />
    </div>

    <div id="oncology-requests-fixed-scrollbar"
        class="hidden fixed bottom-0 z-50 border-t border-gray-300 bg-white/95 py-1 shadow-[0_-4px_12px_rgba(15,23,42,0.15)]">
        <div class="js-oncology-requests-fixed-scrollbar overflow-x-auto">
            <div id="oncology-requests-fixed-scrollbar-spacer" class="h-1"></div>
        </div>
    </div>

    <div class="h-8" aria-hidden="true"></div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            let oncologyRequestsScrollbarFrame = null;
            let oncologyRequestsScrollbarCleanup = null;
            let oncologyRequestsFilterFrame = null;

            function initOncologyRequestsColumnFilters() {
                window.createExcelColumnFilters({
                    tableId: 'oncology-requests-table',
                    rowSelector: '.js-oncology-request-filter-row',
                    triggerSelector: '.js-oncology-request-column-filter',
                    instanceId: 'oncology-requests',
                    onChange() {
                        document.querySelectorAll('.js-oncology-request-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });
            }

            function scheduleOncologyRequestsColumnFilters() {
                window.cancelAnimationFrame(oncologyRequestsFilterFrame);
                oncologyRequestsFilterFrame = window.requestAnimationFrame(initOncologyRequestsColumnFilters);
            }

            function initOncologyRequestsFixedScrollbar() {
                oncologyRequestsScrollbarCleanup?.();

                const tableScroll = document.getElementById('oncology-requests-table-scroll');
                const fixedWrapper = document.getElementById('oncology-requests-fixed-scrollbar');
                const fixedScroll = fixedWrapper?.querySelector('.js-oncology-requests-fixed-scrollbar');
                const spacer = document.getElementById('oncology-requests-fixed-scrollbar-spacer');

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

                oncologyRequestsScrollbarCleanup = () => {
                    controller.abort();
                    resizeObserver.disconnect();
                };

                updateFixedScrollbar();
                window.requestAnimationFrame(updateFixedScrollbar);
            }

            function scheduleOncologyRequestsFixedScrollbar() {
                window.cancelAnimationFrame(oncologyRequestsScrollbarFrame);
                oncologyRequestsScrollbarFrame = window.requestAnimationFrame(initOncologyRequestsFixedScrollbar);
            }

            function registerOncologyRequestsLivewireHook() {
                if (!window.Livewire || window.__oncologyRequestsScrollbarHookRegistered) return;

                window.__oncologyRequestsScrollbarHookRegistered = true;
                window.Livewire.hook('morph.updated', () => {
                    scheduleOncologyRequestsFixedScrollbar();
                    scheduleOncologyRequestsColumnFilters();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initOncologyRequestsFixedScrollbar();
                initOncologyRequestsColumnFilters();
                registerOncologyRequestsLivewireHook();
            });
            document.addEventListener('livewire:init', registerOncologyRequestsLivewireHook, { once: true });
            document.addEventListener('livewire:navigated', () => {
                scheduleOncologyRequestsFixedScrollbar();
                scheduleOncologyRequestsColumnFilters();
            });
        </script>
    @endpush

</x-admin-layout>
