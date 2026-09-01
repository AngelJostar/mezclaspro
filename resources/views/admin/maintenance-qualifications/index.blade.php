<x-admin-layout>
    @php
        $selectedLaboratoryId = (int) ($selectedLaboratory?->id ?? 0);
        $currency = fn ($amount): string => $amount === null ? '-' : '$'.number_format((float) $amount, 2);
        $plainNumber = function ($number): string {
            if ($number === null || $number === '') {
                return '-';
            }

            $formatted = number_format((float) $number, 2);

            return str_ends_with($formatted, '.00') ? substr($formatted, 0, -3) : $formatted;
        };
        $filterButtonClass = 'inline-flex h-5 w-5 flex-none items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-500';
    @endphp

    <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
        <span>Administraci&oacute;n</span>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="font-medium text-emerald-700">Mantenimiento y Calificaciones</span>
    </nav>

    <div class="mt-1 flex flex-col gap-4 border-b border-slate-200 pb-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-950">Mantenimiento y Calificaciones</h1>
            <div class="mt-4">
                <a href="{{ route('admin.maintenance-qualifications.catalog', array_filter(['laboratory_id' => $selectedLaboratoryId ?: null])) }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-emerald-700 px-5 text-sm font-semibold shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                    style="background-color: #047857; color: #ffffff;">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    <span>Cat&aacute;logo y cotizaciones</span>
                </a>
            </div>
        </div>
    </div>

    @if ($calendar['error'])
        <div class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            {{ $calendar['error'] }}
        </div>
    @endif

    <section class="mt-4 pb-4" aria-labelledby="maintenance-laboratory-carousel-title">
        <div class="mb-3">
            <h2 id="maintenance-laboratory-carousel-title" class="text-sm font-semibold text-slate-800">
                Selecciona una central
            </h2>
            <p class="text-xs text-slate-500">
                Consulta todas las centrales o selecciona una central espec&iacute;fica.
            </p>
        </div>

        @if ($laboratories->isNotEmpty())
            <div class="flex items-center gap-2">
                <button type="button" id="maintenance-laboratory-previous"
                    class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-slate-300 bg-white text-2xl leading-none text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Central anterior" aria-label="Central anterior">
                    <span aria-hidden="true">&lsaquo;</span>
                </button>

                <div id="maintenance-laboratory-carousel" data-disable-sticky-x
                    class="flex min-w-0 flex-1 snap-x items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                    <a href="{{ route('admin.maintenance-qualifications.index', request()->except(['page', 'laboratory_id'])) }}"
                        @if ($selectedLaboratoryId === 0) data-selected-laboratory aria-current="true" @endif
                        class="block w-64 flex-none snap-start rounded border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-cyan-300 {{ $selectedLaboratoryId === 0 ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50' }}">
                        <div class="flex items-start gap-2">
                            <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded bg-white text-sm font-bold text-cyan-800 shadow-sm"
                                aria-hidden="true">T</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900">Todas</span>
                                <span class="mt-0.5 block truncate text-[11px] font-medium text-cyan-800">
                                    Todas las centrales
                                </span>
                                <span class="mt-1 block line-clamp-2 text-xs leading-4 text-slate-500">
                                    Cat&aacute;logo consolidado
                                </span>
                            </span>
                        </div>
                        <span class="mt-2 flex items-center justify-between gap-2 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Activas
                            </span>
                            <span>{{ $laboratories->count() }} {{ $laboratories->count() === 1 ? 'central' : 'centrales' }}</span>
                        </span>
                    </a>

                    @foreach ($laboratories as $laboratory)
                        @php
                            $isSelectedLaboratory = $selectedLaboratoryId === (int) $laboratory->id;
                        @endphp
                        <a href="{{ route('admin.maintenance-qualifications.index', array_merge(request()->except(['page']), ['laboratory_id' => $laboratory->id])) }}"
                            @if ($isSelectedLaboratory) data-selected-laboratory aria-current="true" @endif
                            class="block w-64 flex-none snap-start rounded border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-cyan-300 {{ $isSelectedLaboratory ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50' }}">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded bg-white text-sm font-bold text-cyan-800 shadow-sm"
                                    aria-hidden="true">C</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $laboratory->nombre }}</span>
                                    <span class="mt-0.5 block truncate text-[11px] font-medium text-cyan-800">
                                        {{ $laboratory->estado ?: 'Sin estado registrado' }}
                                    </span>
                                    <span class="mt-1 block line-clamp-2 text-xs leading-4 text-slate-500">
                                        {{ $laboratory->direccion ?: 'Sin direcci&oacute;n registrada' }}
                                    </span>
                                </span>
                            </div>
                            <span class="mt-2 flex items-center justify-between gap-2 text-xs text-slate-600">
                                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Activa
                                </span>
                                <span>{{ $calendar['service_count'] }} servicios</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <button type="button" id="maintenance-laboratory-next"
                    class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-slate-300 bg-white text-2xl leading-none text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Central siguiente" aria-label="Central siguiente">
                    <span aria-hidden="true">&rsaquo;</span>
                </button>
            </div>
        @else
            <p class="border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                No hay centrales activas disponibles.
            </p>
        @endif
    </section>

    <section class="mt-2" aria-labelledby="maintenance-calendar-heading">
        <h2 id="maintenance-calendar-heading" class="sr-only">Calendario anual</h2>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm" data-disable-sticky-x>
            <div class="grid min-w-[1170px] gap-1.5" style="grid-template-columns: repeat(13, minmax(0, 1fr));">
                <article class="flex h-10 items-center justify-between gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold leading-3 text-slate-950">TOTAL ANUAL</p>
                        <p class="truncate text-[10px] font-semibold leading-3 text-emerald-700">{{ $currency($calendar['year_total']) }}</p>
                    </div>
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded bg-emerald-100 px-1 text-[10px] font-semibold leading-none text-emerald-800"
                        title="Servicios programados" aria-label="{{ $calendar['scheduled_count'] }} servicios programados">
                        {{ $calendar['scheduled_count'] }}
                    </span>
                </article>
                @foreach ($calendar['monthly_summary'] as $month)
                    <article class="flex h-10 items-center justify-between gap-1 rounded-md border border-slate-200 bg-white px-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold leading-3 text-slate-950">{{ $month['label'] }}</p>
                            <p class="truncate text-[10px] font-semibold leading-3 text-emerald-700">{{ $currency($month['total']) }}</p>
                        </div>
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded bg-slate-100 px-1 text-[10px] font-semibold leading-none text-slate-600">
                            {{ $month['count'] }}
                        </span>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mt-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap items-center justify-end gap-3">
                <div class="flex flex-none items-center gap-2">
                    <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200 bg-white text-base text-emerald-700 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        title="A&ntilde;o anterior" aria-label="A&ntilde;o anterior">
                        <span class="text-2xl font-bold leading-none" aria-hidden="true">&larr;</span>
                    </button>
                    <button type="button"
                        class="inline-flex h-10 min-w-36 items-center justify-center gap-3 rounded-lg border border-slate-200 bg-white px-4 text-xl font-semibold text-slate-950 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        aria-label="A&ntilde;o {{ $calendar['year'] }}">
                        <span>{{ $calendar['year'] }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-600" aria-hidden="true"></i>
                    </button>
                    <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200 bg-white text-base text-emerald-700 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        title="A&ntilde;o siguiente" aria-label="A&ntilde;o siguiente">
                        <span class="text-2xl font-bold leading-none" aria-hidden="true">&rarr;</span>
                    </button>
                </div>

                <div class="flex flex-none flex-wrap items-center justify-end gap-2">
                    @if ($selectedLaboratory)
                        <span class="inline-flex h-9 items-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 px-3 text-xs font-semibold text-cyan-800">
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            {{ $selectedLaboratory->nombre }}
                        </span>
                    @endif

                    <details class="relative">
                        <summary class="flex h-9 cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                            <span>Filtros</span>
                            <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                        </summary>
                        <div class="absolute right-0 z-20 mt-2 w-56 rounded-lg border border-slate-200 bg-white p-2 text-sm shadow-xl">
                            <a href="{{ route('admin.maintenance-qualifications.index', request()->except(['page', 'only'])) }}"
                                class="block rounded px-3 py-2 font-medium {{ ($activeFilter ?? 'all') === 'all' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                Todos los servicios
                            </a>
                            <a href="{{ route('admin.maintenance-qualifications.index', array_merge(request()->except(['page']), ['only' => 'scheduled'])) }}"
                                class="block rounded px-3 py-2 font-medium {{ ($activeFilter ?? 'all') === 'scheduled' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                Servicios programados
                            </a>
                        </div>
                    </details>

                    <details class="relative">
                        <summary class="flex h-9 cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                            <span>Exportar</span>
                            <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                        </summary>
                        <div class="absolute right-0 z-20 mt-2 w-52 rounded-lg border border-slate-200 bg-white p-2 text-sm shadow-xl">
                            <a href="{{ route('admin.maintenance-qualifications.source') }}"
                                class="block rounded px-3 py-2 font-medium text-slate-700 hover:bg-slate-50">
                                Excel fuente
                            </a>
                            <button type="button" onclick="window.print()"
                                class="block w-full rounded px-3 py-2 text-left font-medium text-slate-700 hover:bg-slate-50">
                                Imprimir vista
                            </button>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto" data-disable-sticky-x>
                <table class="min-w-[1760px] table-fixed text-left text-sm text-slate-700">
                    <thead class="bg-white text-[11px] uppercase text-slate-950">
                        <tr class="border-b border-slate-200">
                            <th scope="col" class="w-16 px-3 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="font-bold">ID</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar ID">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-32 px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold">Frecuencia</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar frecuencia">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-64 px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold">Servicio</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar servicio">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-24 px-3 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="font-bold">Cantidad</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar cantidad">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-64 px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold">Identificaci&oacute;n</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar identificacion">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-32 px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold">Proveedor</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar proveedor">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-36 px-3 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="font-bold leading-4">Precio unitario</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar precio unitario">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="w-32 border-r border-slate-200 px-3 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="font-bold">&Uacute;nica OC</span>
                                    <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar unica ocasion">
                                        <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                            @foreach ($calendar['months'] as $month)
                                <th scope="col" class="w-24 px-3 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold">{{ $month['label'] }}</span>
                                        <button type="button" class="{{ $filterButtonClass }}" aria-label="Filtrar {{ $month['label'] }}">
                                            <i class="fa-solid fa-filter text-[9px]" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($calendar['rows'] as $row)
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-5 py-6 text-center font-semibold text-slate-950">{{ $row['id'] }}</td>
                                <td class="px-5 py-6">
                                    <span class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold uppercase text-emerald-700">
                                        {{ $row['frequency'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-6 font-bold leading-5 text-slate-950">{{ $row['service'] }}</td>
                                <td class="px-5 py-6 text-center text-slate-600">{{ $plainNumber($row['quantity']) }}</td>
                                <td class="px-5 py-6 leading-5 text-slate-600">{{ $row['identification'] ?: '-' }}</td>
                                <td class="px-5 py-6 font-medium text-slate-600">{{ $row['provider'] }}</td>
                                <td class="px-5 py-6 text-right font-medium text-slate-600">{{ $currency($row['unit_price']) }}</td>
                                <td class="border-r border-slate-200 px-5 py-6 text-right font-medium text-slate-600">{{ $currency($row['one_time']) }}</td>
                                @foreach ($calendar['months'] as $month)
                                    @php($amount = $row['months'][$month['key']] ?? null)
                                    <td class="px-5 py-6 text-center {{ $amount !== null ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-slate-400' }}">
                                        {{ $amount !== null ? $currency($amount) : '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 8 + count($calendar['months']) }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No hay servicios capturados en el calendario.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="maintenance-service-catalog" class="mt-6 scroll-mt-24" aria-labelledby="maintenance-service-catalog-heading">
        <div class="flex flex-col gap-2 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 id="maintenance-service-catalog-heading" class="text-base font-semibold text-slate-950">
                    Cat&aacute;logo de servicios y mantenimientos
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $calendar['provider_count'] }} proveedores registrados en el calendario {{ $calendar['year'] }}.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="overflow-x-auto border border-slate-200">
                <table class="min-w-[1100px] table-fixed text-left text-xs text-slate-700">
                    <thead class="border-b border-slate-300 bg-slate-50 text-[11px] uppercase text-slate-700">
                        <tr>
                            <th scope="col" class="w-14 px-3 py-3">ID</th>
                            <th scope="col" class="w-80 px-3 py-3">Servicio</th>
                            <th scope="col" class="w-28 px-3 py-3">Frecuencia</th>
                            <th scope="col" class="w-24 px-3 py-3 text-center">Cantidad</th>
                            <th scope="col" class="w-64 px-3 py-3">Identificaci&oacute;n</th>
                            <th scope="col" class="w-32 px-3 py-3">Proveedor</th>
                            <th scope="col" class="w-28 px-3 py-3 text-right">Precio unitario</th>
                            <th scope="col" class="w-28 px-3 py-3 text-right">Total {{ $calendar['year'] }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($calendar['rows'] as $row)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-3 py-3 font-semibold text-slate-950">{{ $row['id'] }}</td>
                                <td class="px-3 py-3 font-semibold text-slate-950">{{ $row['service'] }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $row['frequency'] }}</td>
                                <td class="px-3 py-3 text-center">{{ $plainNumber($row['quantity']) }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $row['identification'] ?: '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-700">{{ $row['provider'] }}</td>
                                <td class="px-3 py-3 text-right">{{ $currency($row['unit_price']) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-emerald-700">{{ $currency($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No hay servicios para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <aside class="space-y-4">
                <section class="border border-slate-200 bg-white">
                    <h3 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-950">Proveedores</h3>
                    <div class="divide-y divide-slate-100">
                        @forelse (array_slice($calendar['provider_summary'], 0, 8) as $provider)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                <span class="min-w-0 truncate text-slate-700">{{ $provider['label'] }}</span>
                                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $provider['count'] }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-slate-500">Sin proveedores.</p>
                        @endforelse
                    </div>
                </section>

                <section class="border border-slate-200 bg-white">
                    <h3 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-950">Frecuencias</h3>
                    <div class="divide-y divide-slate-100">
                        @forelse ($calendar['frequency_summary'] as $frequency)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                <span class="min-w-0 truncate text-slate-700">{{ $frequency['label'] }}</span>
                                <span class="rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">{{ $frequency['count'] }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-slate-500">Sin frecuencias.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </section>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const carousel = document.getElementById('maintenance-laboratory-carousel');
                const previous = document.getElementById('maintenance-laboratory-previous');
                const next = document.getElementById('maintenance-laboratory-next');

                if (!carousel || !previous || !next) return;

                const updateNavigation = () => {
                    const maxScroll = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
                    previous.disabled = carousel.scrollLeft <= 2;
                    next.disabled = carousel.scrollLeft >= maxScroll - 2;
                };

                const selectedCard = carousel.querySelector('[data-selected-laboratory]');
                if (selectedCard) {
                    carousel.scrollLeft = Math.max(
                        0,
                        selectedCard.offsetLeft - ((carousel.clientWidth - selectedCard.clientWidth) / 2)
                    );
                }

                previous.addEventListener('click', () => carousel.scrollBy({ left: -300, behavior: 'smooth' }));
                next.addEventListener('click', () => carousel.scrollBy({ left: 300, behavior: 'smooth' }));
                carousel.addEventListener('scroll', updateNavigation, { passive: true });
                window.addEventListener('resize', updateNavigation);
                requestAnimationFrame(updateNavigation);
            });
        </script>
    @endpush
</x-admin-layout>
