<x-admin-layout>
    @php
        $sortUrl = function (string $column) use ($sort, $direction): string {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('admin.distribution.routes.index', array_merge(request()->query(), [
                'sort' => $column,
                'direction' => $nextDirection,
                'page' => null,
            ]));
        };

        $sortSymbol = fn (string $column): string => $sort !== $column
            ? '&#8597;'
            : ($direction === 'asc' ? '&#8593;' : '&#8595;');
    @endphp

    <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
        <span>Distribuci&oacute;n</span>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="font-medium text-blue-700">Cat&aacute;logo de rutas</span>
    </nav>

    <div class="mt-1 flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">Rutas de distribuci&oacute;n</h1>

        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            <a href="{{ route('admin.distribution.routes.create') }}" data-route-type-open
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Crear nueva ruta</span>
            </a>
            <a href="{{ route('admin.distribution.messengers.index') }}"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-blue-600 bg-white px-5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                <span>Cat&aacute;logo de mensajeros</span>
            </a>
        </div>
    </div>

    <section class="mt-4 pb-4" aria-labelledby="route-laboratory-carousel-title">
        <div class="mb-3">
            <h2 id="route-laboratory-carousel-title" class="text-sm font-semibold text-slate-800">
                Selecciona una central
            </h2>
            <p class="text-xs text-slate-500">
                Consulta todas las centrales o selecciona una central espec&iacute;fica.
            </p>
        </div>

        @if ($laboratories->isNotEmpty())
            <div class="flex items-center gap-2">
                <button type="button" id="route-laboratory-previous"
                    class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-slate-300 bg-white text-2xl leading-none text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Central anterior" aria-label="Central anterior">
                    <span aria-hidden="true">&lsaquo;</span>
                </button>

                <div id="route-laboratory-carousel" data-disable-sticky-x
                    class="flex min-w-0 flex-1 snap-x items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                    <a href="{{ route('admin.distribution.routes.index', request()->except(['page', 'laboratory_id'])) }}"
                        data-route-laboratory-card
                        @if (! $selectedLaboratory) data-selected-laboratory aria-current="true" @endif
                        class="block w-64 flex-none snap-start rounded border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-cyan-300 {{ ! $selectedLaboratory ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50' }}">
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
                            $isSelectedLaboratory = $selectedLaboratory?->is($laboratory) ?? false;
                        @endphp
                        <a href="{{ route('admin.distribution.routes.index', array_merge(request()->except(['page']), ['laboratory_id' => $laboratory->id])) }}"
                            data-route-laboratory-card
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
                                <span>{{ $laboratory->routes_count }} {{ $laboratory->routes_count === 1 ? 'ruta' : 'rutas' }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <button type="button" id="route-laboratory-next"
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

    <section class="mt-2" aria-labelledby="distribution-routes-heading">
        <div class="border-b border-slate-200 pb-3">
            <h2 id="distribution-routes-heading" class="text-base font-semibold text-slate-900">Listado de rutas</h2>
        </div>

        <div class="mt-3 overflow-x-auto border border-slate-200">
            <table class="min-w-[1180px] table-fixed text-left text-sm text-slate-600">
                <thead class="border-b border-slate-300 bg-slate-50 text-xs uppercase text-slate-700">
                    <tr>
                        <th data-force-column-filter scope="col" class="w-[8%] px-4 py-3">
                            <a href="{{ $sortUrl('id') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por n&uacute;mero de ruta" aria-label="Ordenar por n&uacute;mero de ruta">
                                <span class="min-w-0 flex-1"># Ruta</span>
                                <span class="text-base leading-none {{ $sort === 'id' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('id') !!}</span>
                            </a>
                        </th>
                        <th data-force-column-filter scope="col" class="w-[20%] px-4 py-3">
                            <a href="{{ $sortUrl('name') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por nombre de la ruta" aria-label="Ordenar por nombre de la ruta">
                                <span class="min-w-0 flex-1">Nombre de la ruta</span>
                                <span class="text-base leading-none {{ $sort === 'name' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('name') !!}</span>
                            </a>
                        </th>
                        <th data-force-column-filter scope="col" class="w-[12%] px-4 py-3">
                            <a href="{{ $sortUrl('route_type') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por tipo de ruta" aria-label="Ordenar por tipo de ruta">
                                <span class="min-w-0 flex-1">Tipo de ruta</span>
                                <span class="text-base leading-none {{ $sort === 'route_type' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('route_type') !!}</span>
                            </a>
                        </th>
                        <th data-force-column-filter scope="col" class="w-[12%] px-4 py-3">
                            <a href="{{ $sortUrl('code') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por c&oacute;digo QR" aria-label="Ordenar por c&oacute;digo QR">
                                <span class="min-w-0 flex-1">C&oacute;digo QR</span>
                                <span class="text-base leading-none {{ $sort === 'code' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('code') !!}</span>
                            </a>
                        </th>
                        <th data-force-column-filter scope="col" class="w-[24%] px-4 py-3">
                            <a href="{{ $sortUrl('hospitals') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por hospitales en ruta" aria-label="Ordenar por hospitales en ruta">
                                <span class="min-w-0 flex-1">Hospitales en ruta</span>
                                <span class="text-base leading-none {{ $sort === 'hospitals' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('hospitals') !!}</span>
                            </a>
                        </th>
                        <th data-force-column-filter scope="col" class="w-[8%] px-4 py-3">
                            <a href="{{ $sortUrl('stops') }}" class="inline-flex w-full items-center gap-2 hover:text-blue-700"
                                title="Ordenar por n&uacute;mero de paradas" aria-label="Ordenar por n&uacute;mero de paradas">
                                <span class="min-w-0 flex-1"># Paradas</span>
                                <span class="text-base leading-none {{ $sort === 'stops' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true">{!! $sortSymbol('stops') !!}</span>
                            </a>
                        </th>
                        <th scope="col" class="w-[8%] px-4 py-3 text-center">Editar</th>
                        @role('Super Admin')
                            <th scope="col" class="w-[8%] px-4 py-3 text-center">Eliminar</th>
                        @endrole
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach ($distributionRoutes as $distributionRoute)
                        @php
                            $statusMeta = match ($distributionRoute->status) {
                                'active' => ['En ruta', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                                'completed' => ['Completada', 'border-blue-200 bg-blue-50 text-blue-700', 'bg-blue-500'],
                                'paused' => ['Pausada', 'border-amber-200 bg-amber-50 text-amber-700', 'bg-amber-500'],
                                'cancelled' => ['Cancelada', 'border-red-200 bg-red-50 text-red-700', 'bg-red-500'],
                                default => ['Programada', 'border-indigo-200 bg-indigo-50 text-indigo-700', 'bg-indigo-500'],
                            };
                            $messengerNames = $distributionRoute->messengers
                                ->map(fn ($messenger) => trim($messenger->name . ' ' . $messenger->lastname))
                                ->filter()
                                ->join(', ');
                            $routeTypeMeta = $distributionRoute->route_type === 'dron'
                                ? ['Dron', 'D', 'border-violet-200 bg-violet-50 text-violet-700']
                                : ['Vehicular', 'V', 'border-blue-200 bg-blue-50 text-blue-700'];
                        @endphp
                        <tr id="route-{{ $distributionRoute->id }}" class="align-top hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-md bg-blue-50 px-2 font-semibold text-blue-700">
                                    {{ $distributionRoute->id }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ $distributionRoute->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $distributionRoute->code }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded border px-2 py-1 font-medium {{ $statusMeta[1] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta[2] }}"></span>
                                        {{ $statusMeta[0] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-slate-500">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        {{ substr($distributionRoute->schedule_start, 0, 5) }} - {{ substr($distributionRoute->schedule_end, 0, 5) }}
                                    </span>
                                </div>
                                @if ($messengerNames !== '')
                                    <p class="mt-2 text-xs text-slate-500">
                                        <i class="fa-solid fa-motorcycle mr-1" aria-hidden="true"></i>{{ $messengerNames }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-2 rounded-md border px-2.5 py-1.5 text-xs font-semibold {{ $routeTypeMeta[2] }}">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-white/80 text-[10px] font-bold"
                                        aria-hidden="true">{{ $routeTypeMeta[1] }}</span>
                                    {{ $routeTypeMeta[0] }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <button type="button" data-route-qr-open
                                    data-route-name="{{ $distributionRoute->name }}"
                                    data-route-code="{{ $distributionRoute->code }}"
                                    data-route-schedule="{{ substr($distributionRoute->schedule_start, 0, 5) }} - {{ substr($distributionRoute->schedule_end, 0, 5) }}"
                                    data-route-stops="{{ $distributionRoute->hospitals_count }}"
                                    data-route-qr-url="{{ route('admin.distribution.routes.qr', $distributionRoute) }}"
                                    class="inline-flex h-9 items-center gap-2 rounded-md border border-blue-600 bg-white px-3 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                    Ver QR
                                </button>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-800">
                                    <i class="fa-solid fa-hospital mr-1 text-slate-400" aria-hidden="true"></i>
                                    {{ $distributionRoute->hospitals_count }} {{ $distributionRoute->hospitals_count === 1 ? 'hospital' : 'hospitales' }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    {{ $distributionRoute->hospitals->pluck('name')->join(', ') ?: 'Sin hospitales asignados' }}
                                </p>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-900">
                                {{ $distributionRoute->hospitals_count }}
                                <span class="block text-xs font-normal text-slate-500">paradas</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <button type="button" data-route-modal-edit
                                    data-route-id="{{ $distributionRoute->id }}"
                                    data-route-name="{{ $distributionRoute->name }}"
                                    data-route-type="{{ $distributionRoute->route_type ?: 'vehicular' }}"
                                    data-route-update-url="{{ route('admin.distribution.routes.update', $distributionRoute) }}"
                                    data-route-hospital-ids='@json($distributionRoute->hospitals->pluck('id')->values())'
                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-blue-700 px-3 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                    title="Editar {{ $distributionRoute->name }}"
                                    aria-label="Editar {{ $distributionRoute->name }}">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    <span>Editar</span>
                                </button>
                            </td>
                            @role('Super Admin')
                                <td class="px-4 py-4 text-center">
                                    <form method="POST" action="{{ route('admin.distribution.routes.destroy', $distributionRoute) }}"
                                        class="!w-auto" data-delete-route data-route-name="{{ $distributionRoute->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-red-600 bg-white px-3 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300"
                                            title="Eliminar {{ $distributionRoute->name }}"
                                            aria-label="Eliminar {{ $distributionRoute->name }}">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            <span>Eliminar</span>
                                        </button>
                                    </form>
                                </td>
                            @endrole
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($distributionRoutes->isEmpty())
                <div class="border-t border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">
                    <i class="fa-solid fa-route mb-3 block text-2xl text-slate-300" aria-hidden="true"></i>
                    No hay rutas de distribuci&oacute;n registradas.
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 px-1 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>
                @if ($distributionRoutes->total() > 0)
                    Mostrando {{ $distributionRoutes->firstItem() }} a {{ $distributionRoutes->lastItem() }} de {{ $distributionRoutes->total() }} rutas
                @else
                    Mostrando 0 rutas
                @endif
            </p>

            @if ($distributionRoutes->hasPages())
                {{ $distributionRoutes->links() }}
            @endif
        </div>
    </section>

    @push('modals')
        @include('admin.distribution.routes._type-modal')
        @include('admin.distribution.routes._create-modal')
        @include('admin.distribution.routes._qr-modal')
    @endpush

    @push('js')
        <script>
            (() => {
                const carousel = document.getElementById('route-laboratory-carousel');
                const previous = document.getElementById('route-laboratory-previous');
                const next = document.getElementById('route-laboratory-next');

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

                document.querySelectorAll('[data-delete-route]').forEach((form) => {
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const routeName = form.dataset.routeName || 'esta ruta';
                        let confirmed = false;

                        if (window.Swal) {
                            const result = await Swal.fire({
                                title: 'Eliminar ruta?',
                                text: `Se eliminara ${routeName}. Esta accion no se puede deshacer.`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Si, eliminar',
                                cancelButtonText: 'Cancelar',
                                reverseButtons: true,
                                customClass: {
                                    confirmButton: 'swal-button-confirm',
                                    cancelButton: 'swal-button-cancel',
                                },
                            });

                            confirmed = result.isConfirmed;
                        } else {
                            confirmed = window.confirm(`Se eliminara ${routeName}. Esta accion no se puede deshacer.`);
                        }

                        if (confirmed) {
                            form.submit();
                        }
                    });
                });
            })();
        </script>
    @endpush
</x-admin-layout>
