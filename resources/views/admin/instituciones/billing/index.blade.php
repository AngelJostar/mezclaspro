<x-admin-layout>
    <style>
        #billing-table-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #billing-table-scroll::-webkit-scrollbar {
            display: none;
        }
    </style>

    @php
        $isHistory = $billingSection === 'history';
        $isReceivable = $billingSection === 'receivable';
        $billingListRoute = match ($billingSection) {
            'history' => route('admin.instituciones.billing.history'),
            'receivable' => route('admin.instituciones.billing.receivable'),
            default => route('admin.instituciones.billing.index'),
        };
        $billingSectionLabel = match ($billingSection) {
            'history' => 'Historial',
            'receivable' => 'Por Cobrar',
            default => 'Pendiente',
        };
        $billingSectionDescription = match ($billingSection) {
            'history' => 'Consulta las solicitudes cuya facturacion ya fue concluida.',
            'receivable' => 'Consulta las remisiones facturadas que estan listas para cobrar.',
            default => 'Administra las solicitudes pendientes de concluir su facturacion.',
        };
    @endphp

    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Facturacion / {{ $billingSectionLabel }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $billingSectionDescription }}
        </p>
    </div>

    <div id="billing-toast"
        class="pointer-events-none hidden"
        style="position:fixed; right:20px; bottom:20px; z-index:9999; min-width:260px; max-width:360px; border-radius:14px; border:1px solid #334155; background:#020617; color:#ffffff; padding:14px 16px; box-shadow:0 12px 30px rgba(15,23,42,.32); opacity:0; transform:translateY(8px); transition:opacity .25s ease, transform .25s ease;">
        <div class="flex items-start gap-3">
            <div id="billing-toast-icon" class="mt-0.5 text-sm"></div>
            <div>
                <p id="billing-toast-title" class="text-sm font-semibold"></p>
                <p id="billing-toast-message" class="mt-1 text-sm"></p>
            </div>
        </div>
    </div>

    @if ($isReceivable)
        <div id="billing-observations-modal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="billing-observations-title" aria-hidden="true">
            <button type="button" class="absolute inset-0 bg-slate-900/50" onclick="closeBillingObservations()"
                aria-label="Cerrar observaciones"></button>

            <div class="relative z-10 w-full max-w-xl rounded-lg border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 id="billing-observations-title" class="text-base font-semibold text-slate-900">Observaciones</h2>
                        <p id="billing-observations-remision" class="mt-0.5 text-xs text-slate-500"></p>
                    </div>
                    <button type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                        onclick="closeBillingObservations()" title="Cerrar" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-4">
                    <textarea id="billing-observations-editor" rows="7" maxlength="5000"
                        class="w-full resize-y rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Escribe una observación sobre esta remisión..."></textarea>
                    <p id="billing-observations-counter" class="mt-1 text-right text-xs text-slate-400">0 / 5000</p>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <button type="button"
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        onclick="closeBillingObservations()">Cancelar</button>
                    <button id="billing-observations-save" type="button"
                        class="rounded-md bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
                        onclick="saveBillingObservations()">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5"
        data-billing-section="{{ $billingSection }}"
        x-data="billingModule(@js($instituciones->mapWithKeys(fn($institucion) => [
            $institucion->id => $institucion->hospitals->map(fn($hospital) => [
                'id' => $hospital->id,
                'name' => $hospital->name,
            ])->values(),
        ])), @js($institucionId), @js($hospitalId))">
        <div class="mb-3 grid max-w-2xl grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="flex min-h-[58px] items-center justify-between gap-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Pendientes en amarillo</p>
                    <p class="mt-1 text-xs leading-4 text-amber-800">
                        Pendiente de facturar que ya pasó al próximo mes calendario.
                    </p>
                </div>
                <p class="text-lg font-semibold leading-none text-amber-800">{{ $billingDueCounts['yellow'] }}</p>
            </div>

            <div class="flex min-h-[58px] items-center justify-between gap-3 rounded-md border border-red-200 bg-red-50 px-3 py-1.5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700">Pendientes en rojo</p>
                    <p class="mt-1 text-xs leading-4 text-red-800">
                        Pendiente de facturar en donde ya vencieron los 15 días del siguiente mes calendario.
                    </p>
                </div>
                <p class="text-lg font-semibold leading-none text-red-700">{{ $billingDueCounts['red'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ $billingListRoute }}"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-9 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Institucion</label>
                <select name="institucion_id" x-model="selectedInstitucion" @change="handleInstitucionChange()"
                    class="w-full rounded-lg border-slate-200">
                    <option value="">Todas</option>
                    @foreach ($instituciones as $institucion)
                        <option value="{{ $institucion->id }}" @selected((string) $institucionId === (string) $institucion->id)>
                            {{ $institucion->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hospital</label>
                <select name="hospital_id" x-model="selectedHospital" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    <template x-for="hospital in availableHospitals()" :key="hospital.id">
                        <option :value="String(hospital.id)" x-text="hospital.name"></option>
                    </template>
                </select>
            </div>

            <div class="xl:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Paciente, lote o folio..."
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Facturacion</label>
                <select name="billing_status" class="w-full rounded-lg border-slate-200">
                    <option value="">Todas</option>
                    <option value="con" @selected($billingStatus === 'con')>Con facturacion</option>
                    <option value="sin" @selected($billingStatus === 'sin')>Sin facturacion</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estatus facturacion</label>
                <select name="facturacion_status" class="w-full rounded-lg border-slate-200">
                    @if ($isHistory)
                        <option value="">Concluidas</option>
                    @else
                        <option value="">Todos</option>
                        <option value="Pendiente" @selected($facturacionStatus === 'Pendiente')>Pendiente</option>
                        <option value="Por facturar" @selected($facturacionStatus === 'Por facturar')>Por facturar</option>
                        <option value="En revisión" @selected($facturacionStatus === 'En revisión')>En revisión</option>
                        <option value="Facturado" @selected($facturacionStatus === 'Facturado')>Facturado</option>
                        <option value="Pagado" @selected($facturacionStatus === 'Pagado')>Pagado</option>
                        <option value="Cancelado" @selected($facturacionStatus === 'Cancelado')>Cancelado</option>
                        <option value="Rechazado" @selected($facturacionStatus === 'Rechazado')>Rechazado</option>
                        <option value="No conciliado" @selected($facturacionStatus === 'No conciliado')>No conciliado</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Conciliable</label>
                <select name="conciliable_filter" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    <option value="Si" @selected($conciliableFilter === 'Si')>Si</option>
                    <option value="No" @selected($conciliableFilter === 'No')>No</option>
                    <option value="Conciliado" @selected($conciliableFilter === 'Conciliado')>Conciliado</option>
                    <option value="No conciliable" @selected($conciliableFilter === 'No conciliable')>No conciliable</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-azul-prodifem px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>
                    Filtrar
                </button>
            </div>

            <div class="flex items-end gap-2">
                <a href="{{ $billingListRoute }}"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Limpiar
                </a>
            </div>

            <div class="flex flex-wrap items-end justify-end gap-2 xl:col-span-7 xl:col-start-3">
                <button id="billing-invoice-select-all" type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 transition hover:bg-blue-50">
                    <i class="fa-regular fa-square-check mr-2"></i>
                    Seleccionar todo
                </button>
                <button id="billing-expanded-export-button" type="submit"
                    form="billing-expanded-export-form" disabled
                    class="inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-slate-400 px-4 py-2.5 text-sm font-semibold text-white transition">
                    <i class="fa-solid fa-download mr-2"></i>
                    Descargar Excel
                </button>
                <a href="{{ route('admin.instituciones.billing.export', array_filter(['section' => $billingSection, 'institucion_id' => $institucionId, 'hospital_id' => $hospitalId, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'billing_status' => $billingStatus, 'facturacion_status' => $facturacionStatus, 'conciliable_filter' => $conciliableFilter])) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                    <i class="fa-solid fa-file-excel mr-2"></i>
                    Exportar a Excel
                </a>
            </div>
        </form>

        <form id="billing-expanded-export-form" method="POST"
            action="{{ route('admin.instituciones.billing.expanded-export') }}"
            class="hidden">
            @csrf
            <input type="hidden" name="section" value="{{ $billingSection }}">
            <input type="hidden" name="institucion_id" value="{{ $institucionId }}">
            <input type="hidden" name="hospital_id" value="{{ $hospitalId }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">
            <input type="hidden" name="billing_status" value="{{ $billingStatus }}">
            <input type="hidden" name="facturacion_status" value="{{ $facturacionStatus }}">
            <input type="hidden" name="conciliable_filter" value="{{ $conciliableFilter }}">

        </form>

        <div id="billing-table-scroll" class="overflow-x-auto border border-slate-300">
            <table id="billing-requests-table-{{ $billingSection }}"
                class="w-full border-collapse text-xs text-left text-slate-800 {{ $isReceivable ? 'min-w-[3300px]' : 'min-w-[3010px]' }}">
                <thead class="bg-slate-100 uppercase text-slate-700">
                    <tr>
                        <x-filterable-table-header column="0" trigger-class="js-billing-column-filter" compact
                            class="sticky left-0 z-30 min-w-[120px] border border-slate-300 bg-slate-100 shadow-[3px_0_5px_rgba(15,23,42,0.12)]">
                            No. de remision
                        </x-filterable-table-header>
                        <x-filterable-table-header column="1" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Institucion</x-filterable-table-header>
                        <x-filterable-table-header column="2" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Unidad</x-filterable-table-header>
                        <x-filterable-table-header column="3" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Nombre del Medico</x-filterable-table-header>
                        <x-filterable-table-header column="4" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Nombre del Paciente</x-filterable-table-header>
                        <x-filterable-table-header column="5" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Fecha de Remision</x-filterable-table-header>
                        <x-filterable-table-header column="6" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Cantidad</x-filterable-table-header>
                        <x-filterable-table-header column="7" trigger-class="js-billing-column-filter" compact
                            class="w-[110px] min-w-[110px] max-w-[110px] border border-slate-300">
                            <span class="block leading-tight">Cantidad de</span>
                            <span class="block leading-tight">Frascos</span>
                        </x-filterable-table-header>
                        <x-filterable-table-header column="8" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Descripcion</x-filterable-table-header>
                        <x-filterable-table-header column="9" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">P.V. unitario antes de IVA</x-filterable-table-header>
                        <x-filterable-table-header column="10" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">P.V. total IVA Incluido</x-filterable-table-header>
                        <x-filterable-table-header column="11" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Empresa</x-filterable-table-header>
                        <x-filterable-table-header column="12" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Precio Total</x-filterable-table-header>
                        <x-filterable-table-header column="13" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Conciliable</x-filterable-table-header>
                        <x-filterable-table-header column="14" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Folio Factura UUID</x-filterable-table-header>
                        <x-filterable-table-header column="15" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Folio Factura Interno</x-filterable-table-header>
                        <x-filterable-table-header column="16" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Fecha Facturacion</x-filterable-table-header>
                        <x-filterable-table-header column="17" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Numero Carta Factura</x-filterable-table-header>
                        <x-filterable-table-header column="18" trigger-class="js-billing-column-filter" compact
                            class="border border-slate-300">Fecha Carta Factura</x-filterable-table-header>
                        <th class="border border-slate-300 px-2 py-1 text-center font-semibold whitespace-nowrap">Facturar</th>
                        <x-filterable-table-header column="20" trigger-class="js-billing-column-filter" compact
                            align="center" class="border border-slate-300">Vencimiento</x-filterable-table-header>
                        @if ($isReceivable)
                            <th class="min-w-[165px] border border-slate-300 px-2 py-1 text-center font-semibold whitespace-nowrap">
                                Fecha de compensación
                            </th>
                            <th class="min-w-[110px] border border-slate-300 px-2 py-1 text-center font-semibold whitespace-nowrap">
                                Observaciones
                            </th>
                        @endif
                        <th class="min-w-[190px] border border-slate-300 px-2 py-1 font-semibold whitespace-nowrap">
                            @if (! $isHistory)
                                <div class="flex items-center justify-center gap-2 normal-case">
                                    <input id="billing-select-all" type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                                        title="Seleccionar todas las solicitudes disponibles"
                                        aria-label="Seleccionar todas las solicitudes disponibles">
                                    <button id="billing-conclude-selected" type="button" disabled
                                        class="inline-flex h-7 items-center justify-center rounded-sm bg-slate-400 px-2 py-1 text-xs font-semibold text-white transition cursor-not-allowed"
                                        onclick="confirmSelectedBillingConclusions()">
                                        Concluir selección
                                    </button>
                                </div>
                            @else
                                Concluir
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $item)
                        @php
                            $record = $item['record'];
                            $hospital = $item['hospital'];
                            $institucionActual = $item['institucion'];
                            $patientName = $item['patient_name'];
                            $fecha = $item['fecha'];
                            $origenTipo = $item['origen_tipo'];
                            $billing = $item['billing'];
                            $formId = 'billing-form-' . $origenTipo . '-' . $record->id;
                            $facturacionCompletada = mb_strtolower(trim((string) ($billing?->estatus_facturacion ?? ''))) === 'completado';
                            $facturacionListaParaConcluir = $billing?->hasReceivableInvoiceData() ?? false;
                            $vencimientoFiltro = match ($item['vencimiento']['status']) {
                                'red' => 'Rojo',
                                'yellow' => 'Amarillo',
                                default => 'Sin Color',
                            };
                        @endphp
                        <tr class="js-billing-filter-row group bg-white align-middle hover:bg-blue-50/40">
                            <td class="sticky left-0 z-20 min-w-[120px] border border-slate-200 bg-white px-2 py-1 whitespace-nowrap shadow-[3px_0_5px_rgba(15,23,42,0.10)] group-hover:bg-blue-50">
                                {{ $item['remision'] }}
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[260px] max-w-[260px]">
                                <div class="truncate font-medium leading-tight text-slate-800">
                                    {{ $institucionActual?->nombre ?: '—' }}
                                    @if ($institucionActual?->razon_social)
                                        <span class="font-normal text-slate-500"> | {{ $institucionActual->razon_social }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[180px] max-w-[180px] truncate">{{ $hospital?->name ?: '—' }}</td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[220px] max-w-[220px] truncate">{{ $item['medico'] }}</td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[220px] max-w-[220px] truncate">{{ $patientName }}</td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap min-w-[140px]">{{ $fecha }}</td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[100px] text-center">
                                @foreach ($item['quantity_lines'] as $quantityLine)
                                    <div class="whitespace-nowrap">{{ $quantityLine }}</div>
                                @endforeach
                            </td>
                            <td class="w-[110px] min-w-[110px] max-w-[110px] border border-slate-200 px-2 py-1 text-center">
                                @foreach ($item['bottle_quantity_lines'] as $bottleQuantityLine)
                                    <div class="whitespace-nowrap">{{ $bottleQuantityLine }}</div>
                                @endforeach
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[300px]">
                                <div class="space-y-0.5">
                                    @foreach ($item['description_lines'] as $descriptionLine)
                                        <div class="whitespace-nowrap font-medium text-slate-700">{{ $descriptionLine }}</div>
                                    @endforeach
                                    <a href="{{ $item['view_route'] }}" target="_blank"
                                        class="inline-block whitespace-nowrap font-medium text-blue-700 hover:text-blue-800">
                                        {{ $item['view_label'] }}
                                    </a>
                                </div>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[170px]">
                                @foreach ($item['unit_price_lines'] as $unitPriceLine)
                                    <div class="whitespace-nowrap font-medium text-slate-700">{{ $unitPriceLine }}</div>
                                @endforeach
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[150px] whitespace-nowrap font-medium text-slate-800">{{ $item['pv_total'] }}</td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[220px] max-w-[220px] truncate">{{ $item['empresa'] }}</td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[130px]">
                                <input type="text" form="{{ $formId }}" name="precio_total" value="{{ $billing?->precio_total ?? $item['computed_total_input'] }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[160px]">
                                @php
                                    $conciliableActual = trim((string) ($billing?->conciliable ?? ''));
                                    $conciliableSeleccionado = in_array($conciliableActual, ['No', 'No conciliable'], true) ? 'No' : 'Si';
                                @endphp

                                <select form="{{ $formId }}" name="conciliable" class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                                    <option value="Si" @selected($conciliableSeleccionado === 'Si')>Si</option>
                                    <option value="No" @selected($conciliableSeleccionado === 'No')>No</option>
                                </select>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[190px]">
                                <input type="text" form="{{ $formId }}" name="folio_factura_uuid" value="{{ $billing?->folio_factura_uuid }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[160px]">
                                <input type="text" form="{{ $formId }}" name="folio_interno" value="{{ $billing?->folio_interno }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[150px]">
                                <input type="text" form="{{ $formId }}" name="fecha_facturacion" value="{{ $billing?->fecha_facturacion }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[170px]">
                                <input type="text" form="{{ $formId }}" name="numero_carta_factura" value="{{ $billing?->numero_carta_factura }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[150px]">
                                <input type="text" form="{{ $formId }}" name="fecha_carta_factura" value="{{ $billing?->fecha_carta_factura }}"
                                    class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">

                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.instituciones.billing.store') }}" class="js-inline-billing-form hidden">
                                    @csrf
                                    <input type="hidden" name="institucion_filter" value="{{ $institucionId }}">
                                    <input type="hidden" name="hospital_filter" value="{{ $hospitalId }}">
                                    <input type="hidden" name="search_filter" value="{{ $search }}">
                                    <input type="hidden" name="date_from_filter" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to_filter" value="{{ $dateTo }}">
                                    <input type="hidden" name="billing_status_filter" value="{{ $billingStatus }}">
                                    <input type="hidden" name="facturacion_status_filter" value="{{ $facturacionStatus }}">
                                    <input type="hidden" name="conciliable_filter_value" value="{{ $conciliableFilter }}">
                                    <input type="hidden" name="billing_section" value="{{ $billingSection }}">
                                    <input type="hidden" name="institucion_id" value="{{ $institucionActual?->id }}">
                                    <input type="hidden" name="hospital_id" value="{{ $hospital?->id }}">
                                    <input type="hidden" name="origen_tipo" value="{{ $origenTipo }}">
                                    <input type="hidden" name="origen_id" value="{{ $record->id }}">
                                    <input type="hidden" name="estatus_facturacion" value="{{ $billing?->estatus_facturacion }}">
                                </form>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[90px] text-center">
                                <input type="checkbox"
                                    form="billing-expanded-export-form"
                                    name="selected_records[]"
                                    value="{{ $origenTipo }}:{{ $record->id }}"
                                    class="js-billing-invoice-select h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    title="Incluir esta solicitud en el Excel ampliado"
                                    aria-label="Incluir esta solicitud en el Excel ampliado">
                            </td>
                            <td data-billing-expiration-for="{{ $formId }}" data-filter-value="{{ $vencimientoFiltro }}"
                                class="border border-slate-200 px-2 py-1 min-w-[100px] text-center">
                                @if ($item['vencimiento']['status'] === 'yellow')
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-full border border-amber-300 bg-amber-100 px-2 font-bold text-amber-800"
                                        title="{{ $item['vencimiento']['days'] }} dias de retraso en facturacion"
                                        aria-label="{{ $item['vencimiento']['days'] }} dias de retraso en facturacion">
                                        {{ $item['vencimiento']['days'] }}
                                    </span>
                                @elseif ($item['vencimiento']['status'] === 'red')
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-full border border-red-300 bg-red-100 px-2 font-bold text-red-700"
                                        title="{{ $item['vencimiento']['days'] }} dias de retraso en facturacion"
                                        aria-label="{{ $item['vencimiento']['days'] }} dias de retraso en facturacion">
                                        {{ $item['vencimiento']['days'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            @if ($isReceivable)
                                <td class="min-w-[165px] border border-slate-200 px-2 py-1 text-center">
                                    <input type="date" form="{{ $formId }}" name="fecha_compensacion"
                                        value="{{ $billing?->fecha_compensacion }}"
                                        class="js-billing-autosave-field h-7 w-full rounded-sm border-slate-300 px-2 py-1 text-xs">
                                </td>
                                <td class="min-w-[110px] border border-slate-200 px-2 py-1 text-center">
                                    <textarea id="{{ $formId }}-observaciones" form="{{ $formId }}" name="observaciones"
                                        data-billing-observations-field-for="{{ $formId }}" class="hidden">{{ $billing?->observaciones }}</textarea>
                                    <button type="button" data-billing-observations-for="{{ $formId }}"
                                        data-billing-remision="{{ $item['remision'] }}"
                                        class="js-billing-observations-button inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-lg transition hover:bg-slate-50 {{ filled(trim((string) $billing?->observaciones)) ? 'text-amber-500' : 'text-slate-400' }}"
                                        onclick="openBillingObservations(this)"
                                        title="{{ filled(trim((string) $billing?->observaciones)) ? 'Editar observaciones' : 'Agregar observaciones' }}"
                                        aria-label="{{ filled(trim((string) $billing?->observaciones)) ? 'Editar observaciones' : 'Agregar observaciones' }}">
                                        <span class="text-base font-black leading-none" aria-hidden="true">&#9998;</span>
                                    </button>
                                </td>
                            @endif
                            <td class="min-w-[190px] border border-slate-200 px-2 py-1 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($isHistory)
                                        <button type="button"
                                            data-billing-history-actions
                                            data-billing-move-url="{{ route('admin.instituciones.billing.move', $billing) }}"
                                            data-billing-remision="{{ $item['remision'] }}"
                                            class="inline-flex h-7 min-w-[96px] items-center justify-center gap-2 rounded-sm bg-emerald-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            onclick="openBillingHistoryActions(this)"
                                            aria-haspopup="menu" aria-expanded="false">
                                            <span>Concluida</span>
                                            <span aria-hidden="true">&#9662;</span>
                                        </button>
                                    @else
                                        <input type="checkbox"
                                            class="js-billing-conclusion-select h-4 w-4 shrink-0 rounded border-slate-300 text-blue-700 focus:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-40"
                                            data-billing-select-for="{{ $formId }}"
                                            @disabled($facturacionCompletada || ! $facturacionListaParaConcluir)
                                            title="Seleccionar esta solicitud"
                                            aria-label="Seleccionar esta solicitud">
                                        <button type="button"
                                            data-billing-conclude-for="{{ $formId }}"
                                            data-billing-completed="{{ $facturacionCompletada ? '1' : '0' }}"
                                            @disabled($facturacionCompletada || ! $facturacionListaParaConcluir)
                                            class="inline-flex h-7 min-w-[86px] items-center justify-center rounded-sm px-2 py-1 text-xs font-semibold text-white transition {{ $facturacionCompletada ? 'cursor-default bg-emerald-600' : ($facturacionListaParaConcluir ? 'bg-azul-prodifem hover:bg-blue-800' : 'cursor-not-allowed bg-slate-400') }}"
                                            onclick="confirmBillingConclusion(document.getElementById('{{ $formId }}'), this)">
                                            Concluir
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isReceivable ? 24 : 22 }}" class="border border-slate-200 px-2 py-6 text-center text-slate-400">
                                No se encontraron registros para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="billing-fixed-scrollbar"
            class="hidden fixed bottom-0 z-50 border-t border-slate-300 bg-white/95 py-1 shadow-[0_-4px_12px_rgba(15,23,42,0.15)]">
            <div class="js-billing-fixed-scrollbar overflow-x-auto">
                <div id="billing-fixed-scrollbar-spacer" class="h-1"></div>
            </div>
        </div>

        <div class="mb-7 mt-3 flex flex-col gap-3 text-xs text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>
                Mostrando {{ $records->firstItem() ?? 0 }} a {{ $records->lastItem() ?? 0 }} de {{ $records->total() }} registros
            </p>

            <div>
                {{ $records->links() }}
            </div>
        </div>
    </div>

    @if ($isHistory)
        <div id="billing-history-actions-menu"
            class="fixed z-[120] hidden w-56 overflow-hidden rounded-md border border-slate-300 bg-white p-1 text-sm shadow-xl"
            role="menu" aria-hidden="true">
            <button type="button" role="menuitem" data-history-destination="receivable"
                class="flex w-full items-center rounded px-3 py-2 text-left font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800"
                onclick="confirmBillingHistoryMove('receivable')">
                Cambiar a Por Cobrar
            </button>
            <button type="button" role="menuitem" data-history-destination="pending"
                class="flex w-full items-center rounded px-3 py-2 text-left font-medium text-slate-700 hover:bg-amber-50 hover:text-amber-800"
                onclick="confirmBillingHistoryMove('pending')">
                Cambiar a Pendientes
            </button>
        </div>
    @endif

    <script>
        @include('admin.catalogo-listas.partials.column-filter-script')

        function initBillingColumnFilters() {
            const billingSection = document.querySelector('[data-billing-section]')?.dataset.billingSection || 'pending';

            window.createExcelColumnFilters({
                tableId: `billing-requests-table-${billingSection}`,
                rowSelector: '.js-billing-filter-row',
                triggerSelector: '.js-billing-column-filter',
                instanceId: `billing-requests-${billingSection}`,
                valuesByColumn: {
                    20: ['Sin Color', 'Rojo', 'Amarillo'],
                },
                swatchesByColumn: {
                    20: {
                        'Sin Color': 'border-slate-300 bg-white',
                        'Rojo': 'border-red-300 bg-red-100',
                        'Amarillo': 'border-amber-300 bg-amber-100',
                    },
                },
                onChange() {
                    document.querySelectorAll('.js-billing-filter-row').forEach((row) => {
                        row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                    });
                },
            });
        }

        function billingModule(hospitalMap, initialInstitucion, initialHospital) {
            return {
                hospitalMap: hospitalMap || {},
                selectedInstitucion: initialInstitucion ? String(initialInstitucion) : '',
                selectedHospital: initialHospital ? String(initialHospital) : '',
                availableHospitals() {
                    if (!this.selectedInstitucion) {
                        const allHospitals = Object.values(this.hospitalMap || {}).flat();
                        const unique = [];
                        const seen = new Set();

                        allHospitals.forEach((hospital) => {
                            const key = String(hospital.id);
                            if (!seen.has(key)) {
                                seen.add(key);
                                unique.push(hospital);
                            }
                        });

                        return unique.sort((a, b) => a.name.localeCompare(b.name, 'es'));
                    }

                    return this.hospitalMap[this.selectedInstitucion] || [];
                },
                handleInstitucionChange() {
                    const availableIds = this.availableHospitals().map((hospital) => String(hospital.id));
                    if (this.selectedHospital && !availableIds.includes(this.selectedHospital)) {
                        this.selectedHospital = '';
                    }
                },
                init() {
                    this.handleInstitucionChange();
                }
            }
        }

        let billingToastTimer = null;
        let activeBillingObservationsButton = null;
        let activeBillingObservationsField = null;
        let billingObservationsSaving = false;
        let activeBillingHistoryActionButton = null;
        let billingHistoryMoveSaving = false;

        function closeBillingHistoryActions() {
            if (billingHistoryMoveSaving) return;

            const menu = document.getElementById('billing-history-actions-menu');
            if (!menu) return;

            menu.classList.add('hidden');
            menu.setAttribute('aria-hidden', 'true');
            activeBillingHistoryActionButton?.setAttribute('aria-expanded', 'false');
            activeBillingHistoryActionButton = null;
        }

        function openBillingHistoryActions(button) {
            const menu = document.getElementById('billing-history-actions-menu');
            if (!menu || !button || billingHistoryMoveSaving) return;

            if (activeBillingHistoryActionButton === button && !menu.classList.contains('hidden')) {
                closeBillingHistoryActions();
                return;
            }

            activeBillingHistoryActionButton?.setAttribute('aria-expanded', 'false');
            activeBillingHistoryActionButton = button;
            button.setAttribute('aria-expanded', 'true');
            menu.classList.remove('hidden');
            menu.setAttribute('aria-hidden', 'false');

            const rect = button.getBoundingClientRect();
            const menuWidth = 224;
            const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);
            const top = Math.min(rect.bottom + 6, window.innerHeight - menu.offsetHeight - 8);
            menu.style.left = `${left}px`;
            menu.style.top = `${Math.max(8, top)}px`;
        }

        async function confirmBillingHistoryMove(destination) {
            const button = activeBillingHistoryActionButton;
            const menu = document.getElementById('billing-history-actions-menu');
            if (!button || !menu || billingHistoryMoveSaving) return;

            const destinationLabel = destination === 'pending' ? 'Pendientes' : 'Por Cobrar';
            const cleanupMessage = destination === 'pending'
                ? 'Se eliminarán los datos de factura y la fecha de compensación.'
                : 'Se conservarán los datos de factura y se eliminará la fecha de compensación.';
            const confirmationMessage = `La remisión ${button.dataset.billingRemision || '—'} cambiará a ${destinationLabel}. ${cleanupMessage}`;
            let confirmed = false;

            if (window.Swal) {
                const result = await Swal.fire({
                    title: '¿Estás seguro?',
                    text: confirmationMessage,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#243b7b',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    focusCancel: true,
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm(`¿Estás seguro?\n${confirmationMessage}`);
            }

            if (!confirmed) return;

            billingHistoryMoveSaving = true;
            menu.querySelectorAll('button').forEach((option) => {
                option.disabled = true;
                option.classList.add('cursor-wait', 'opacity-60');
            });
            button.disabled = true;
            button.classList.add('cursor-wait', 'opacity-70');

            const formData = new FormData();
            formData.append('destination', destination);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(button.dataset.billingMoveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = Object.values(payload.errors || {}).flat()[0];
                    throw new Error(validationMessage || payload.message || 'No se pudo mover la remisión.');
                }

                showBillingToast('success', payload.message || `La remisión se movió a ${destinationLabel}.`);
                window.setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                billingHistoryMoveSaving = false;
                button.disabled = false;
                button.classList.remove('cursor-wait', 'opacity-70');
                menu.querySelectorAll('button').forEach((option) => {
                    option.disabled = false;
                    option.classList.remove('cursor-wait', 'opacity-60');
                });
                showBillingToast('error', error.message || 'No se pudo mover la remisión.');
            }
        }

        function initBillingHistoryActions() {
            const menu = document.getElementById('billing-history-actions-menu');
            if (!menu || menu.dataset.actionsBound === '1') return;

            menu.dataset.actionsBound = '1';
            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target) && !event.target.closest('[data-billing-history-actions]')) {
                    closeBillingHistoryActions();
                }
            });
            window.addEventListener('resize', closeBillingHistoryActions);
            document.getElementById('billing-table-scroll')?.addEventListener('scroll', closeBillingHistoryActions);
        }

        function updateBillingObservationsCounter() {
            const editor = document.getElementById('billing-observations-editor');
            const counter = document.getElementById('billing-observations-counter');
            if (!editor || !counter) return;

            counter.textContent = `${editor.value.length} / 5000`;
        }

        function syncBillingObservationsButton(button, value) {
            if (!button) return;

            const hasObservations = String(value || '').trim() !== '';
            button.classList.toggle('text-amber-500', hasObservations);
            button.classList.toggle('text-slate-400', !hasObservations);
            button.title = hasObservations ? 'Editar observaciones' : 'Agregar observaciones';
            button.setAttribute('aria-label', button.title);
        }

        function openBillingObservations(button) {
            const modal = document.getElementById('billing-observations-modal');
            const editor = document.getElementById('billing-observations-editor');
            const remision = document.getElementById('billing-observations-remision');
            const formId = button?.dataset.billingObservationsFor;
            const field = formId ? document.getElementById(`${formId}-observaciones`) : null;
            if (!modal || !editor || !field || billingObservationsSaving) return;

            activeBillingObservationsButton = button;
            activeBillingObservationsField = field;
            editor.value = field.value || '';
            remision.textContent = `Remisión ${button.dataset.billingRemision || '—'}`;
            updateBillingObservationsCounter();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => editor.focus(), 0);
        }

        function closeBillingObservations() {
            if (billingObservationsSaving) return;

            const modal = document.getElementById('billing-observations-modal');
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            activeBillingObservationsButton?.focus();
            activeBillingObservationsButton = null;
            activeBillingObservationsField = null;
        }

        async function saveBillingObservations() {
            const editor = document.getElementById('billing-observations-editor');
            const saveButton = document.getElementById('billing-observations-save');
            const field = activeBillingObservationsField;
            const form = field?.form;
            if (!editor || !saveButton || !field || !form || billingObservationsSaving) return;

            billingObservationsSaving = true;
            saveButton.disabled = true;
            saveButton.classList.add('cursor-wait', 'opacity-70');
            saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Guardando';

            field.value = editor.value.trim();
            form.dataset.autosaveVersion = String(Number(form.dataset.autosaveVersion || 0) + 1);
            form.dataset.dirty = '1';
            setBillingAutosaveState(form, 'pending');

            const idle = await waitForBillingFormIdle(form);
            const saved = idle && (form.dataset.dirty === '0' || await submitInlineBilling(form));

            billingObservationsSaving = false;
            saveButton.disabled = false;
            saveButton.classList.remove('cursor-wait', 'opacity-70');
            saveButton.textContent = 'Guardar';

            if (!saved) return;

            syncBillingObservationsButton(activeBillingObservationsButton, field.value);
            showBillingToast('success', 'Las observaciones se guardaron correctamente.');
            closeBillingObservations();
        }

        function initBillingObservations() {
            const editor = document.getElementById('billing-observations-editor');
            if (editor && editor.dataset.observationsBound !== '1') {
                editor.dataset.observationsBound = '1';
                editor.addEventListener('input', updateBillingObservationsCounter);
            }

            if (document.documentElement.dataset.billingObservationsEscapeBound !== '1') {
                document.documentElement.dataset.billingObservationsEscapeBound = '1';
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') closeBillingObservations();
                });
            }
        }

        function showBillingToast(type, message) {
            const toast = document.getElementById('billing-toast');
            const title = document.getElementById('billing-toast-title');
            const body = document.getElementById('billing-toast-message');
            const icon = document.getElementById('billing-toast-icon');

            if (!toast || !title || !body || !icon) return;

            if (billingToastTimer) {
                window.clearTimeout(billingToastTimer);
            }

            const isSuccess = type === 'success';

            toast.className = 'pointer-events-none';
            toast.style.position = 'fixed';
            toast.style.right = '20px';
            toast.style.bottom = '20px';
            toast.style.top = 'auto';
            toast.style.left = 'auto';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '260px';
            toast.style.maxWidth = '360px';
            toast.style.borderRadius = '14px';
            toast.style.border = '1px solid #334155';
            toast.style.background = '#020617';
            toast.style.color = '#ffffff';
            toast.style.padding = '14px 16px';
            toast.style.boxShadow = '0 12px 30px rgba(15,23,42,.32)';
            toast.style.transition = 'opacity .25s ease, transform .25s ease';

            icon.innerHTML = isSuccess
                ? '<i class="fa-solid fa-circle-check text-emerald-400"></i>'
                : '<i class="fa-solid fa-circle-exclamation text-rose-400"></i>';
            title.textContent = isSuccess ? 'Guardado' : 'No se pudo guardar';
            body.textContent = message;

            toast.classList.remove('hidden');
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            billingToastTimer = window.setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';

                window.setTimeout(() => {
                    toast.classList.add('hidden');
                }, 250);
            }, 2600);
        }

        const billingAutosaveTimers = new WeakMap();
        const billingFeedbackTimers = new WeakMap();

        function billingFieldsFor(form) {
            if (!form?.id) return [];

            return Array.from(document.querySelectorAll(`.js-billing-autosave-field[form="${form.id}"]`));
        }

        function setBillingAutosaveState(form, state) {
            const fields = billingFieldsFor(form);
            const colors = {
                idle: ['', ''],
                pending: ['#f59e0b', '#fffbeb'],
                saving: ['#2563eb', '#eff6ff'],
                saved: ['#10b981', '#ecfdf5'],
                error: ['#ef4444', '#fff1f2'],
            };
            const [borderColor, backgroundColor] = colors[state] || colors.idle;

            fields.forEach((field) => {
                field.style.borderColor = borderColor;
                field.style.backgroundColor = backgroundColor;
                field.setAttribute('aria-busy', state === 'saving' ? 'true' : 'false');
            });

            const previousTimer = billingFeedbackTimers.get(form);
            if (previousTimer) {
                window.clearTimeout(previousTimer);
            }

            if (state === 'saved') {
                billingFeedbackTimers.set(form, window.setTimeout(() => {
                    setBillingAutosaveState(form, 'idle');
                }, 1200));
            }
        }

        function submitInlineBilling(form) {
            if (!form) return Promise.resolve(false);

            const queuedTimer = billingAutosaveTimers.get(form);
            if (queuedTimer) {
                window.clearTimeout(queuedTimer);
                billingAutosaveTimers.delete(form);
            }

            if (form.dataset.submitting === '1') {
                form.dataset.resubmit = '1';
                return Promise.resolve(false);
            }

            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const submittedVersion = Number(form.dataset.autosaveVersion || 0);

            form.dataset.submitting = '1';
            form.dataset.resubmit = '0';
            setBillingAutosaveState(form, 'saving');

            return fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw new Error(payload.message || 'No se pudo guardar el cambio.');
                    }

                    return response.json().catch(() => ({}));
                })
                .then(() => {
                    const row = form.closest('tr');
                    const currentVersion = Number(form.dataset.autosaveVersion || 0);

                    if (currentVersion === submittedVersion) {
                        form.dataset.dirty = '0';
                        setBillingAutosaveState(form, 'saved');

                        if (form.dataset.conclusionPending === '1' && formData.get('estatus_facturacion') === 'Completado') {
                            completeBillingConclusionUi(form);
                        } else if (document.querySelector('[data-billing-section]')?.dataset.billingSection === 'pending' && billingReadyToConclude(form)) {
                            showBillingToast('success', 'La remision paso a Por Cobrar.');
                            window.setTimeout(() => window.location.reload(), 700);
                        } else if (document.querySelector('[data-billing-section]')?.dataset.billingSection === 'receivable' && !billingReadyToConclude(form)) {
                            showBillingToast('success', 'La remision regreso a Pendiente.');
                            window.setTimeout(() => window.location.reload(), 700);
                        }
                    }

                    if (row) {
                        row.dataset.billingSaved = '1';
                    }

                    return true;
                })
                .catch((error) => {
                    form.dataset.dirty = '1';
                    setBillingAutosaveState(form, 'error');
                    resetBillingConclusionUi(form);
                    console.error(error);
                    if (form.dataset.bulkConclusion !== '1') {
                        showBillingToast('error', error.message || 'Ocurrio un error al guardar.');
                    }

                    return false;
                })
                .finally(() => {
                    form.dataset.submitting = '0';
                    const currentVersion = Number(form.dataset.autosaveVersion || 0);

                    if (form.dataset.resubmit === '1' || currentVersion > submittedVersion) {
                        form.dataset.resubmit = '0';
                        submitInlineBilling(form);
                    }
                });
        }

        function billingConclusionButton(form) {
            if (!form?.id) return null;

            return document.querySelector(`[data-billing-conclude-for="${form.id}"]`);
        }

        function billingSelectionCheckbox(form) {
            if (!form?.id) return null;

            return document.querySelector(`[data-billing-select-for="${form.id}"]`);
        }

        function updateBillingBulkControls() {
            const masterCheckbox = document.getElementById('billing-select-all');
            const bulkButton = document.getElementById('billing-conclude-selected');
            const availableCheckboxes = Array.from(document.querySelectorAll('.js-billing-conclusion-select'))
                .filter((checkbox) => !checkbox.disabled);
            const selectedCheckboxes = availableCheckboxes.filter((checkbox) => checkbox.checked);

            if (masterCheckbox) {
                masterCheckbox.disabled = availableCheckboxes.length === 0 || bulkButton?.dataset.running === '1';
                masterCheckbox.checked = availableCheckboxes.length > 0
                    && selectedCheckboxes.length === availableCheckboxes.length;
                masterCheckbox.indeterminate = selectedCheckboxes.length > 0
                    && selectedCheckboxes.length < availableCheckboxes.length;
            }

            if (!bulkButton || bulkButton.dataset.running === '1') return;

            const hasSelection = selectedCheckboxes.length > 0;
            bulkButton.disabled = !hasSelection;
            bulkButton.className = hasSelection
                ? 'inline-flex h-7 items-center justify-center rounded-sm bg-azul-prodifem px-2 py-1 text-xs font-semibold text-white transition hover:bg-blue-800'
                : 'inline-flex h-7 cursor-not-allowed items-center justify-center rounded-sm bg-slate-400 px-2 py-1 text-xs font-semibold text-white transition';
            bulkButton.textContent = 'Concluir selección';
        }

        function syncBillingSelectionCheckbox(form) {
            const checkbox = billingSelectionCheckbox(form);
            const button = billingConclusionButton(form);
            if (!checkbox) return;

            const completed = button?.dataset.billingCompleted === '1';
            const pending = form?.dataset.conclusionPending === '1';
            checkbox.disabled = completed || pending || !billingReadyToConclude(form);

            if (checkbox.disabled) {
                checkbox.checked = false;
            }

            updateBillingBulkControls();
        }

        function billingReadyToConclude(form) {
            const requiredFields = [
                'folio_interno',
                'fecha_facturacion',
                'numero_carta_factura',
                'fecha_carta_factura',
            ];

            return requiredFields.every((name) => {
                const field = billingFieldsFor(form).find((candidate) => candidate.name === name);
                return field && String(field.value || '').trim() !== '';
            });
        }

        function syncBillingConclusionButton(form) {
            const button = billingConclusionButton(form);
            if (!button) return;

            if (button.dataset.billingCompleted === '1' || form?.dataset.conclusionPending === '1') {
                syncBillingSelectionCheckbox(form);
                return;
            }

            const ready = billingReadyToConclude(form);
            button.disabled = !ready;
            button.className = ready
                ? 'inline-flex h-7 min-w-[86px] items-center justify-center rounded-sm bg-azul-prodifem px-2 py-1 text-xs font-semibold text-white transition hover:bg-blue-800'
                : 'inline-flex h-7 min-w-[86px] cursor-not-allowed items-center justify-center rounded-sm bg-slate-400 px-2 py-1 text-xs font-semibold text-white transition';
            button.textContent = 'Concluir';
            syncBillingSelectionCheckbox(form);
        }

        function completeBillingConclusionUi(form) {
            const button = billingConclusionButton(form);
            const checkbox = billingSelectionCheckbox(form);
            const expiration = document.querySelector(`[data-billing-expiration-for="${form.id}"]`);
            const isBulkConclusion = form.dataset.bulkConclusion === '1';
            form.dataset.conclusionPending = '0';
            delete form.dataset.previousBillingStatus;

            if (button) {
                button.dataset.billingCompleted = '1';
                button.disabled = true;
                button.className = 'inline-flex h-7 min-w-[86px] cursor-default items-center justify-center rounded-sm bg-emerald-600 px-2 py-1 text-xs font-semibold text-white transition';
                button.innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i>Concluida';
            }

            if (expiration) {
                expiration.innerHTML = '<span class="text-slate-400">&mdash;</span>';
                expiration.dataset.filterValue = 'Sin Color';

                const billingSection = document.querySelector('[data-billing-section]')?.dataset.billingSection || 'pending';
                window.__excelColumnFilterInstances?.[`billing-requests-${billingSection}`]?.apply?.();
            }

            if (checkbox) {
                checkbox.checked = false;
                checkbox.disabled = true;
            }

            updateBillingBulkControls();

            if (!isBulkConclusion) {
                showBillingToast('success', 'La fila se concluyo correctamente.');

                const billingSection = document.querySelector('[data-billing-section]')?.dataset.billingSection;
                if (billingSection === 'pending' || billingSection === 'receivable') {
                    window.setTimeout(() => window.location.reload(), 700);
                }
            }
        }

        function resetBillingConclusionUi(form) {
            if (form?.dataset.conclusionPending !== '1') return;

            const button = billingConclusionButton(form);
            const statusInput = form.querySelector('input[name="estatus_facturacion"]');

            if (statusInput && Object.prototype.hasOwnProperty.call(form.dataset, 'previousBillingStatus')) {
                statusInput.value = form.dataset.previousBillingStatus;
            }

            delete form.dataset.previousBillingStatus;
            form.dataset.conclusionPending = '0';

            if (button) {
                syncBillingConclusionButton(form);
            }
        }

        function prepareBillingConclusion(form, button, isBulkConclusion = false) {
            const statusInput = form?.querySelector('input[name="estatus_facturacion"]');
            if (!form || !button || !statusInput || !billingReadyToConclude(form)) return false;

            form.dataset.previousBillingStatus = statusInput.value || '';
            form.dataset.bulkConclusion = isBulkConclusion ? '1' : '0';
            statusInput.value = 'Completado';
            form.dataset.autosaveVersion = String(Number(form.dataset.autosaveVersion || 0) + 1);
            form.dataset.dirty = '1';
            form.dataset.conclusionPending = '1';

            button.disabled = true;
            button.classList.add('cursor-wait', 'opacity-70');
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Concluyendo';
            syncBillingSelectionCheckbox(form);

            return true;
        }

        function waitForBillingFormIdle(form) {
            return new Promise((resolve) => {
                let attempts = 0;
                const timer = window.setInterval(() => {
                    attempts++;

                    if (form.dataset.submitting !== '1' || attempts >= 200) {
                        window.clearInterval(timer);
                        resolve(form.dataset.submitting !== '1');
                    }
                }, 50);
            });
        }

        async function concludeSelectedBillingRow(checkbox) {
            const form = document.getElementById(checkbox.dataset.billingSelectFor || '');
            if (!form || checkbox.disabled || !billingReadyToConclude(form)) return false;

            const isIdle = await waitForBillingFormIdle(form);
            const button = billingConclusionButton(form);

            if (!isIdle || !button || !prepareBillingConclusion(form, button, true)) return false;

            const saved = await submitInlineBilling(form);
            form.dataset.bulkConclusion = '0';

            if (!saved) {
                syncBillingConclusionButton(form);
            }

            return saved;
        }

        async function confirmSelectedBillingConclusions() {
            const bulkButton = document.getElementById('billing-conclude-selected');
            const selected = Array.from(document.querySelectorAll('.js-billing-conclusion-select:checked'))
                .filter((checkbox) => !checkbox.disabled);

            if (!bulkButton || bulkButton.disabled || selected.length === 0) return;

            let confirmed = false;
            const message = `Se concluirán ${selected.length} remisiones seleccionadas y se enviarán al Historial.`;

            if (window.Swal) {
                const result = await Swal.fire({
                    title: '¿Concluir selección?',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#243b7b',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    focusCancel: true,
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm(`¿Concluir selección?\n${message}`);
            }

            if (!confirmed) return;

            bulkButton.dataset.running = '1';
            bulkButton.disabled = true;
            bulkButton.className = 'inline-flex h-7 cursor-wait items-center justify-center rounded-sm bg-azul-prodifem px-2 py-1 text-xs font-semibold text-white opacity-80';

            let completed = 0;
            let failed = 0;

            for (let index = 0; index < selected.length; index += 5) {
                const batch = selected.slice(index, index + 5);
                const results = await Promise.all(batch.map(concludeSelectedBillingRow));
                completed += results.filter(Boolean).length;
                failed += results.filter((result) => !result).length;
                bulkButton.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i>${completed + failed}/${selected.length}`;
            }

            bulkButton.dataset.running = '0';
            updateBillingBulkControls();

            if (failed > 0) {
                showBillingToast('error', `${completed} remisiones enviadas al Historial y ${failed} sin concluir.`);
            } else {
                showBillingToast('success', `${completed} remisiones enviadas al Historial.`);
            }

            const billingSection = document.querySelector('[data-billing-section]')?.dataset.billingSection;
            if (completed > 0 && (billingSection === 'pending' || billingSection === 'receivable')) {
                window.setTimeout(() => window.location.reload(), 900);
            }
        }

        async function confirmBillingConclusion(form, button) {
            if (!form || !button || button.disabled) return;

            if (!billingReadyToConclude(form)) {
                syncBillingConclusionButton(form);
                showBillingToast('error', 'Completa todos los datos de facturacion antes de concluir.');
                return;
            }

            let confirmed = false;

            if (window.Swal) {
                const result = await Swal.fire({
                    title: '¿Concluir?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#243b7b',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    focusCancel: true,
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm('¿Concluir?');
            }

            if (!confirmed) return;

            if (prepareBillingConclusion(form, button)) {
                submitInlineBilling(form);
            }
        }

        function queueBillingAutosave(field, delay = 650, markChanged = true) {
            const form = field?.form;
            if (!form) return;

            syncBillingConclusionButton(form);

            if (markChanged) {
                form.dataset.autosaveVersion = String(Number(form.dataset.autosaveVersion || 0) + 1);
                form.dataset.dirty = '1';
                setBillingAutosaveState(form, 'pending');
            }

            const previousTimer = billingAutosaveTimers.get(form);
            if (previousTimer) {
                window.clearTimeout(previousTimer);
            }

            billingAutosaveTimers.set(form, window.setTimeout(() => {
                billingAutosaveTimers.delete(form);
                submitInlineBilling(form);
            }, delay));
        }

        function initBillingAutosave() {
            document.querySelectorAll('.js-billing-autosave-field').forEach((field) => {
                if (field.dataset.autosaveBound === '1') return;
                field.dataset.autosaveBound = '1';

                if (field.matches('select')) {
                    field.addEventListener('change', () => queueBillingAutosave(field, 0));
                    return;
                }

                field.addEventListener('input', () => queueBillingAutosave(field));
                field.addEventListener('blur', () => {
                    if (field.form?.dataset.dirty === '1') {
                        queueBillingAutosave(field, 0, false);
                    }
                });
            });

            document.querySelectorAll('.js-inline-billing-form').forEach((form) => {
                syncBillingConclusionButton(form);
            });
        }

        function initBillingBulkConclusion() {
            const masterCheckbox = document.getElementById('billing-select-all');

            if (masterCheckbox && masterCheckbox.dataset.selectionBound !== '1') {
                masterCheckbox.dataset.selectionBound = '1';
                masterCheckbox.addEventListener('change', () => {
                    document.querySelectorAll('.js-billing-conclusion-select').forEach((checkbox) => {
                        if (!checkbox.disabled) {
                            checkbox.checked = masterCheckbox.checked;
                        }
                    });
                    updateBillingBulkControls();
                });
            }

            document.querySelectorAll('.js-billing-conclusion-select').forEach((checkbox) => {
                if (checkbox.dataset.selectionBound === '1') return;
                checkbox.dataset.selectionBound = '1';
                checkbox.addEventListener('change', updateBillingBulkControls);
            });

            updateBillingBulkControls();
        }

        function billingInvoiceExportCheckboxes() {
            return Array.from(document.querySelectorAll('.js-billing-invoice-select'));
        }

        function updateBillingInvoiceExportControls() {
            const checkboxes = billingInvoiceExportCheckboxes();
            const selected = checkboxes.filter((checkbox) => checkbox.checked);
            const selectAllButton = document.getElementById('billing-invoice-select-all');
            const exportButton = document.getElementById('billing-expanded-export-button');
            const allSelected = checkboxes.length > 0 && selected.length === checkboxes.length;

            if (selectAllButton) {
                selectAllButton.disabled = checkboxes.length === 0;
                selectAllButton.className = checkboxes.length > 0
                    ? 'inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-800 transition hover:bg-blue-50'
                    : 'inline-flex cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400';
                selectAllButton.innerHTML = allSelected
                    ? '<i class="fa-regular fa-square-minus mr-2"></i>Quitar seleccion'
                    : '<i class="fa-regular fa-square-check mr-2"></i>Seleccionar todo';
            }

            if (exportButton) {
                const hasSelection = selected.length > 0;
                exportButton.disabled = !hasSelection;
                exportButton.className = hasSelection
                    ? 'inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700'
                    : 'inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-slate-400 px-4 py-2 text-sm font-semibold text-white transition';
                exportButton.innerHTML = `<i class="fa-solid fa-download mr-2"></i>Descargar Excel${hasSelection ? ` (${selected.length})` : ''}`;
            }
        }

        function initBillingInvoiceExport() {
            const selectAllButton = document.getElementById('billing-invoice-select-all');

            if (selectAllButton && selectAllButton.dataset.selectionBound !== '1') {
                selectAllButton.dataset.selectionBound = '1';
                selectAllButton.addEventListener('click', () => {
                    const checkboxes = billingInvoiceExportCheckboxes();
                    const shouldSelect = !checkboxes.every((checkbox) => checkbox.checked);

                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = shouldSelect;
                    });
                    updateBillingInvoiceExportControls();
                });
            }

            billingInvoiceExportCheckboxes().forEach((checkbox) => {
                if (checkbox.dataset.invoiceSelectionBound === '1') return;
                checkbox.dataset.invoiceSelectionBound = '1';
                checkbox.addEventListener('change', updateBillingInvoiceExportControls);
            });

            updateBillingInvoiceExportControls();
        }

        function flushPendingBillingAutosaves() {
            document.querySelectorAll('.js-inline-billing-form').forEach((form) => {
                if (form.dataset.dirty !== '1' || form.dataset.submitting === '1') return;
                navigator.sendBeacon(form.action, new FormData(form));
            });
        }

        function initBillingFixedScrollbar() {
            const tableScroll = document.getElementById('billing-table-scroll');
            const fixedWrapper = document.getElementById('billing-fixed-scrollbar');
            const fixedScroll = fixedWrapper?.querySelector('.js-billing-fixed-scrollbar');
            const spacer = document.getElementById('billing-fixed-scrollbar-spacer');

            if (!tableScroll || !fixedWrapper || !fixedScroll || !spacer) return;

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
            });

            fixedScroll.addEventListener('scroll', () => {
                if (syncing) return;
                syncing = true;
                tableScroll.scrollLeft = fixedScroll.scrollLeft;
                syncing = false;
            });

            window.addEventListener('resize', updateFixedScrollbar);
            updateFixedScrollbar();
            window.requestAnimationFrame(updateFixedScrollbar);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initBillingFixedScrollbar();
            initBillingColumnFilters();
            initBillingAutosave();
            initBillingBulkConclusion();
            initBillingInvoiceExport();
            initBillingObservations();
            initBillingHistoryActions();
        });
        document.addEventListener('livewire:navigated', () => {
            initBillingFixedScrollbar();
            initBillingColumnFilters();
            initBillingAutosave();
            initBillingBulkConclusion();
            initBillingInvoiceExport();
            initBillingObservations();
            initBillingHistoryActions();
        });
        window.addEventListener('pagehide', flushPendingBillingAutosaves);
    </script>
</x-admin-layout>
