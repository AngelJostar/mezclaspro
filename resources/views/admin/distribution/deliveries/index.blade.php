<x-admin-layout>
    <div x-data="{ selectedMixtures: null }" @keydown.escape.window="selectedMixtures = null">
        <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
            <span>Distribuci&oacute;n</span>
            <span class="mx-1" aria-hidden="true">/</span>
            <span class="font-medium text-blue-700">Programaci&oacute;n de entregas</span>
        </nav>

        <div class="mt-1 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Programaci&oacute;n de entregas</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Asigna hospitales con mezclas disponibles a rutas de distribuci&oacute;n.
                </p>
            </div>
        </div>

        <section class="mt-4 pb-4" aria-labelledby="delivery-laboratory-carousel-title">
            <div class="mb-3">
                <h2 id="delivery-laboratory-carousel-title" class="text-sm font-semibold text-slate-800">
                    Selecciona una central
                </h2>
                <p class="text-xs text-slate-500">
                    Consulta todas las centrales o selecciona una central espec&iacute;fica.
                </p>
            </div>

            @if ($laboratories->isNotEmpty())
                <div class="flex items-center gap-2">
                    <button type="button" id="delivery-laboratory-previous"
                        class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-slate-300 bg-white text-2xl leading-none text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Central anterior" aria-label="Central anterior">
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>

                    <div id="delivery-laboratory-carousel"
                        data-disable-sticky-x
                        class="flex min-w-0 flex-1 snap-x items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                        <a href="{{ route('admin.distribution.deliveries.index', request()->except(['page', 'laboratory_id', 'warehouse_id'])) }}"
                            data-delivery-laboratory-card
                            @if (! $selectedLaboratory) data-selected-laboratory aria-current="true" @endif
                            class="block w-64 flex-none snap-start rounded border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-cyan-300 {{ ! $selectedLaboratory ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50' }}">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded bg-white text-sm text-cyan-800 shadow-sm">
                                    <i class="fa-solid fa-building-circle-check" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">Todas</span>
                                    <span class="mt-0.5 block truncate text-[11px] font-medium text-cyan-800">
                                        Todas las centrales
                                    </span>
                                    <span class="mt-1 block line-clamp-2 text-xs leading-4 text-slate-500">
                                        Programaci&oacute;n consolidada
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
                                $isSelectedLaboratory = $selectedLaboratory?->is($laboratory) ?? false;
                            @endphp
                            <a href="{{ route('admin.distribution.deliveries.index', array_merge(request()->except(['page', 'warehouse_id']), ['laboratory_id' => $laboratory->id])) }}"
                                data-delivery-laboratory-card
                                @if ($isSelectedLaboratory) data-selected-laboratory aria-current="true" @endif
                                class="block w-64 flex-none snap-start rounded border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-cyan-300 {{ $isSelectedLaboratory ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50' }}">
                                <div class="flex items-start gap-2">
                                    <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded bg-white text-sm text-cyan-800 shadow-sm">
                                        <i class="fa-solid fa-building" aria-hidden="true"></i>
                                    </span>
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
                                    <span>{{ $laboratory->routes_count }} {{ $laboratory->routes_count === 1 ? 'ruta' : 'rutas' }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <button type="button" id="delivery-laboratory-next"
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

            <form method="GET" action="{{ route('admin.distribution.deliveries.index') }}"
                class="mt-3 flex !w-auto flex-wrap items-end gap-2">
                @if ($selectedLaboratory)
                    <input type="hidden" name="laboratory_id" value="{{ $selectedLaboratory->id }}">
                @endif
                @if ($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                @if ($status !== 'all')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div>
                    <label for="delivery-date-from" class="mb-1 block text-xs font-semibold text-slate-600">Del</label>
                    <input id="delivery-date-from" name="date_from" type="date" value="{{ $deliveryDateFrom }}"
                        class="h-10 w-40 rounded-md border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                </div>
                <div>
                    <label for="delivery-date-to" class="mb-1 block text-xs font-semibold text-slate-600">Al</label>
                    <input id="delivery-date-to" name="date_to" type="date" value="{{ $deliveryDateTo }}"
                        class="h-10 w-40 rounded-md border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                </div>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                    Aplicar rango
                </button>
            </form>
        </section>

        <section class="mt-4 border border-slate-200 bg-white" aria-labelledby="distribution-hospitals-heading">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 lg:flex-row lg:items-end lg:justify-between">
                <h2 id="distribution-hospitals-heading" class="text-base font-semibold text-slate-900">
                    Hospitales en proceso de distribuci&oacute;n
                </h2>

                <form method="GET" action="{{ route('admin.distribution.deliveries.index') }}"
                    class="grid w-full gap-2 sm:grid-cols-[minmax(14rem,1fr)_12rem_auto] lg:!w-[42rem] lg:!max-w-[42rem]">
                    @if ($selectedLaboratory)
                        <input type="hidden" name="laboratory_id" value="{{ $selectedLaboratory->id }}">
                    @endif
                    <input type="hidden" name="date_from" value="{{ $deliveryDateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $deliveryDateTo }}">

                    <label class="sr-only" for="hospital-search">Buscar hospital</label>
                    <input id="hospital-search" name="search" type="search" value="{{ $search }}"
                        placeholder="Buscar hospital..."
                        class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">

                    <label class="sr-only" for="distribution-status">Estatus de distribuci&oacute;n</label>
                    <select id="distribution-status" name="status"
                        class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="all" @selected($status === 'all')>Todos los estatus</option>
                        <option value="pending" @selected($status === 'pending')>Pendientes</option>
                        <option value="ready" @selected($status === 'ready')>Listos para ruta</option>
                        <option value="scheduled" @selected($status === 'scheduled')>Programados</option>
                        <option value="sent" @selected($status === 'sent')>En ruta</option>
                    </select>

                    <button type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-3 text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
                        title="Aplicar filtros" aria-label="Aplicar filtros">
                        <i class="fa-solid fa-filter" aria-hidden="true"></i>
                        <span class="ml-2 sm:hidden">Filtrar</span>
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1120px] table-fixed text-left text-sm text-slate-600">
                    <thead class="border-b border-slate-300 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th data-force-column-filter scope="col" class="w-[21%] px-4 py-3">Hospital</th>
                            <th data-force-column-filter scope="col" class="w-[13%] px-4 py-3">Fecha de entrega</th>
                            <th data-force-column-filter scope="col" class="w-[13%] px-4 py-3">N&uacute;mero de ruta</th>
                            <th data-force-column-filter scope="col" class="w-[14%] px-4 py-3 text-center">
                                <span class="block">Cantidad de mezclas</span>
                                <span class="block">por entregar</span>
                            </th>
                            <th scope="col" class="w-[13%] px-4 py-3 text-center">Ver mezclas</th>
                            <th scope="col" class="w-[14%] px-4 py-3 text-center">Mandar a ruta</th>
                            <th data-force-column-filter scope="col" class="w-[12%] px-4 py-3 text-center">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($distributionHospitals as $hospital)
                            @php
                                $statusMeta = match ($hospital['status']) {
                                    'sent' => ['En ruta', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
                                    'scheduled' => ['Programada', 'border-violet-200 bg-violet-50 text-violet-700'],
                                    'ready' => ['Lista para ruta', 'border-amber-200 bg-amber-50 text-amber-700'],
                                    default => ['Pendiente', 'border-blue-200 bg-blue-50 text-blue-700'],
                                };
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4 font-semibold text-slate-900">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-regular fa-hospital text-slate-400" aria-hidden="true"></i>
                                        {{ $hospital['hospital'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-medium text-slate-600">
                                    {{ \Carbon\Carbon::parse($hospital['delivery_date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-4 font-medium text-slate-500">{{ $hospital['route_code'] }}</td>
                                <td class="px-4 py-4 text-center font-semibold text-slate-900">{{ $hospital['mixtures_count'] }}</td>
                                <td class="px-4 py-4 text-center">
                                    <button type="button" @click="selectedMixtures = @js($hospital['modal'])"
                                        class="inline-flex h-9 items-center justify-center rounded-md border border-blue-600 bg-white px-3 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                        Ver mezclas
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if ($hospital['warehouse_id'] && $hospital['route_id'] && $hospital['status'] !== 'sent')
                                        <form method="POST" action="{{ route('admin.distribution.deliveries.send') }}" class="!w-auto">
                                            @csrf
                                            @method('PATCH')
                                            @if ($selectedLaboratory)
                                                <input type="hidden" name="laboratory_id" value="{{ $selectedLaboratory->id }}">
                                            @endif
                                            <input type="hidden" name="hospital_id" value="{{ $hospital['hospital_id'] }}">
                                            <input type="hidden" name="date" value="{{ $hospital['delivery_date'] }}">
                                            <input type="hidden" name="date_from" value="{{ $deliveryDateFrom }}">
                                            <input type="hidden" name="date_to" value="{{ $deliveryDateTo }}">
                                            <button type="submit"
                                                class="inline-flex h-9 items-center justify-center rounded-md bg-blue-700 px-3 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                                Mandar a ruta
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled
                                            class="inline-flex h-9 cursor-not-allowed items-center justify-center rounded-md bg-slate-200 px-3 text-xs font-semibold text-slate-500"
                                            title="{{ match (true) {
                                                $hospital['status'] === 'sent' => 'El hospital ya esta en ruta',
                                                ! $hospital['warehouse_id'] => 'La central no tiene un origen de surtido activo',
                                                default => 'Asigna el hospital a una ruta en el catalogo',
                                            } }}">
                                            {{ match (true) {
                                                $hospital['status'] === 'sent' => 'En ruta',
                                                ! $hospital['warehouse_id'] => 'Central no disponible',
                                                default => 'Sin ruta asignada',
                                            } }}
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded border px-2 py-1 text-xs font-semibold {{ $statusMeta[1] }}">
                                        {{ $statusMeta[0] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($distributionHospitals->isEmpty())
                    <div class="border-t border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                        <i class="fa-regular fa-calendar-xmark mb-3 block text-2xl text-slate-300" aria-hidden="true"></i>
                        @if ($deliveryDateFrom === $deliveryDateTo)
                            No hay hospitales con mezclas disponibles para el {{ \Carbon\Carbon::parse($deliveryDateFrom)->format('d/m/Y') }}.
                        @else
                            No hay hospitales con mezclas disponibles del {{ \Carbon\Carbon::parse($deliveryDateFrom)->format('d/m/Y') }}
                            al {{ \Carbon\Carbon::parse($deliveryDateTo)->format('d/m/Y') }}.
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>
                    @if ($distributionHospitals->total() > 0)
                        Mostrando {{ $distributionHospitals->firstItem() }} a {{ $distributionHospitals->lastItem() }} de {{ $distributionHospitals->total() }} hospitales
                    @else
                        Mostrando 0 hospitales
                    @endif
                </p>

                @if ($distributionHospitals->hasPages())
                    {{ $distributionHospitals->links() }}
                @endif
            </div>
        </section>

        <div x-cloak x-show="selectedMixtures" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/55 p-3 sm:p-6"
            role="dialog" aria-modal="true" aria-labelledby="mixtures-modal-title" @click.self="selectedMixtures = null">
            <template x-if="selectedMixtures">
                <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-md bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 id="mixtures-modal-title" class="text-xl font-semibold text-slate-900">Mezclas a entregar</h2>
                            <p class="mt-2 text-sm text-slate-600">
                                <span class="font-medium" x-text="selectedMixtures.hospital"></span>
                                <span class="mx-2" aria-hidden="true">&bull;</span>
                                Ruta: <span x-text="selectedMixtures.route"></span>
                                <span class="mx-2" aria-hidden="true">&bull;</span>
                                Fecha: <span x-text="selectedMixtures.date"></span>
                                <span class="mx-2" aria-hidden="true">&bull;</span>
                                <span x-text="`${selectedMixtures.count} ${selectedMixtures.count === 1 ? 'mezcla' : 'mezclas'}`"></span>
                            </p>
                        </div>
                        <button type="button" @click="selectedMixtures = null"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                            title="Cerrar" aria-label="Cerrar">
                            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                            <span class="text-xl leading-none" aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="overflow-auto">
                        <table class="min-w-[760px] w-full text-left text-sm text-slate-700" data-disable-column-filters>
                            <thead class="sticky top-0 border-b border-slate-200 bg-slate-50 text-xs text-slate-700">
                                <tr>
                                    <th class="w-[17%] px-4 py-3">Folio</th>
                                    <th class="w-[20%] px-4 py-3">Paciente</th>
                                    <th class="w-[25%] px-4 py-3">Medicamento / Mezcla</th>
                                    <th class="w-[18%] px-4 py-3">&Aacute;rea o Servicio</th>
                                    <th class="w-[10%] px-4 py-3">Hora programada</th>
                                    <th class="w-[10%] px-4 py-3 text-center">Estatus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <template x-for="mixture in selectedMixtures.mixtures" :key="mixture.folio">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">
                                            <a :href="mixture.url" class="text-blue-700 hover:underline" x-text="mixture.folio"></a>
                                        </td>
                                        <td class="px-4 py-3" x-text="mixture.patient"></td>
                                        <td class="px-4 py-3 font-medium text-slate-900" x-text="mixture.mixture"></td>
                                        <td class="px-4 py-3" x-text="mixture.service"></td>
                                        <td class="px-4 py-3" x-text="mixture.time"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700"
                                                x-text="mixture.status"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 px-5 py-4">
                        <p class="text-sm font-medium text-slate-600"
                            x-text="`${selectedMixtures.count} ${selectedMixtures.count === 1 ? 'mezcla en total' : 'mezclas en total'}`"></p>
                        <button type="button" @click="selectedMixtures = null"
                            class="inline-flex h-10 min-w-32 items-center justify-center rounded-md border border-blue-600 bg-white px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Cerrar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    @push('js')
        <script>
            (() => {
                const carousel = document.getElementById('delivery-laboratory-carousel');
                const previous = document.getElementById('delivery-laboratory-previous');
                const next = document.getElementById('delivery-laboratory-next');

                if (!carousel || !previous || !next) return;

                const updateNavigation = () => {
                    const maximumScroll = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
                    previous.disabled = carousel.scrollLeft <= 1;
                    next.disabled = carousel.scrollLeft >= maximumScroll - 1;
                };

                const move = (direction) => carousel.scrollBy({ left: direction * 300, behavior: 'smooth' });
                previous.addEventListener('click', () => move(-1));
                next.addEventListener('click', () => move(1));
                carousel.addEventListener('scroll', updateNavigation, { passive: true });
                window.addEventListener('resize', updateNavigation);

                const selectedCard = carousel.querySelector('[data-selected-laboratory]');
                selectedCard?.scrollIntoView({ block: 'nearest', inline: 'center' });
                requestAnimationFrame(updateNavigation);
            })();
        </script>
    @endpush
</x-admin-layout>
