<x-admin-layout>
    @php
        $statusClasses = [
            \App\Models\DistributionRoute::STATUS_PENDING => 'border-blue-200 bg-blue-50 text-blue-700',
            \App\Models\DistributionRoute::STATUS_IN_ROUTE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\DistributionRoute::STATUS_DELAYED => 'border-amber-300 bg-amber-50 text-amber-700',
            \App\Models\DistributionRoute::STATUS_COMPLETED => 'border-gray-200 bg-gray-100 text-gray-600',
        ];
        $createRouteParameters = $selectedLaboratory ? ['laboratory_id' => $selectedLaboratory->id] : [];
        $previewRoutes = [
            [
                'name' => 'Ruta Norte Matutina',
                'code' => 'R-CDMX-001',
                'hospitals' => ['ANGELES METROPOLITANO', 'HOSPITAL SAN DIEGO', 'CENTRO DE MEZCLAS PRODIFEM'],
                'schedule' => '07:00 - 11:00',
                'messengers' => ['Luis M.', 'Fernanda R.'],
                'status' => \App\Models\DistributionRoute::STATUS_IN_ROUTE,
                'status_label' => 'En ruta',
                'tracking' => 'Entrega en curso',
            ],
            [
                'name' => 'Ruta Poniente Vespertina',
                'code' => 'R-CDMX-002',
                'hospitals' => ['ANGELES LOMAS', 'ANGELES PEDREGAL'],
                'schedule' => '12:30 - 16:30',
                'messengers' => ['Carlos T.'],
                'status' => \App\Models\DistributionRoute::STATUS_PENDING,
                'status_label' => 'Pendiente',
                'tracking' => 'Lista para salida',
            ],
            [
                'name' => 'Ruta Sur Prioritaria',
                'code' => 'R-CDMX-003',
                'hospitals' => ['ANGELES ACOXPA', 'HOSPITAL ANGELES UNIVERSIDAD', 'CBTA'],
                'schedule' => '17:00 - 20:00',
                'messengers' => ['Miriam C.', 'Jorge P.'],
                'status' => \App\Models\DistributionRoute::STATUS_DELAYED,
                'status_label' => 'Con retraso',
                'tracking' => 'Demora de 15 min',
            ],
        ];
        $showPreviewRoutes = $routes->isEmpty() && $selectedLaboratory;
    @endphp

    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6" x-data="{ showQrNotice: true }">
        <header class="flex flex-col gap-5 border-b border-gray-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500" aria-label="Ruta de navegación">
                    <span>Distribución</span>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Rutas de entrega</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950 md:text-3xl">Rutas de distribución</h1>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.distribution.create', $createRouteParameters) }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Crear nueva ruta
                </a>
                <a href="{{ route('admin.distribution.couriers') }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-md border-2 border-blue-600 px-4 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    Catálogo de mensajeros
                </a>
            </div>
        </header>

        @if ($laboratories->isNotEmpty())
            <section class="mt-5 border-b border-gray-200 pb-5" aria-label="Centrales de mezclas">
                <div class="flex items-center gap-2">
                    <button id="distribution-central-previous" type="button"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:border-blue-300 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Central anterior" aria-label="Mostrar la central anterior">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </button>

                    <div id="distribution-central-carousel" class="flex min-w-0 flex-1 snap-x gap-3 overflow-x-auto pb-2">
                        @foreach ($laboratories as $laboratory)
                            @php
                                $isSelectedCentral = (int) $selectedLaboratory?->id === (int) $laboratory->id;
                                $centralRouteCount = (int) $routeCountsByLaboratory->get($laboratory->id, 0);
                                $centralQuery = array_merge(
                                    request()->except(['laboratory_id', 'page']),
                                    ['laboratory_id' => $laboratory->id],
                                );
                            @endphp
                            <a href="{{ route('admin.distribution.index', $centralQuery) }}"
                                @if ($isSelectedCentral) aria-current="true" @endif
                                class="flex min-h-36 w-56 shrink-0 snap-start flex-col rounded-md border p-3 transition {{ $isSelectedCentral ? 'border-cyan-500 bg-cyan-50 shadow-sm' : 'border-gray-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/40' }}">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-blue-50 text-blue-700">
                                        <i class="fa-solid fa-flask-vial" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-bold text-gray-950">{{ $laboratory->nombre }}</h3>
                                        <p class="mt-1 line-clamp-3 text-xs leading-5 text-gray-500">
                                            {{ $laboratory->direccion ?: ($laboratory->estado ?: 'Sin dirección registrada') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-auto flex items-end justify-between gap-2 border-t border-gray-200 pt-3 text-xs">
                                    <span class="font-semibold text-gray-700">
                                        {{ $centralRouteCount }} {{ $centralRouteCount === 1 ? 'ruta' : 'rutas' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                        Activa
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <button id="distribution-central-next" type="button"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:border-blue-300 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Central siguiente" aria-label="Mostrar la central siguiente">
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </section>
        @else
            <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                No hay centrales de mezclas activas disponibles para consultar sus rutas.
            </div>
        @endif

        <div x-show="showQrNotice" x-transition class="mt-5 flex items-center gap-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status">
            <i class="fa-solid fa-qrcode text-xl" aria-hidden="true"></i>
            <p class="min-w-0 flex-1 font-medium">El mensajero debe escanear el código QR para iniciar la ruta asignada.</p>
            <button type="button" @click="showQrNotice = false"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-blue-700 hover:bg-blue-100"
                title="Cerrar aviso" aria-label="Cerrar aviso">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        @if ($showPreviewRoutes)
            <section class="mt-5 rounded-lg border border-dashed border-cyan-300 bg-cyan-50/60 p-5">
                <div class="flex flex-col gap-3 border-b border-cyan-200 pb-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Vista simulada</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-950">Asi se vera una operacion con rutas activas</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Esta es una previsualizacion temporal para mostrar el comportamiento del modulo mientras aun no hay rutas reales registradas.
                        </p>
                    </div>
                    <div class="grid gap-2 text-sm text-gray-700 sm:grid-cols-3">
                        <div class="rounded-md border border-cyan-200 bg-white px-3 py-2 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rutas simuladas</p>
                            <p class="mt-1 text-xl font-bold text-gray-950">{{ count($previewRoutes) }}</p>
                        </div>
                        <div class="rounded-md border border-cyan-200 bg-white px-3 py-2 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hospitales cubiertos</p>
                            <p class="mt-1 text-xl font-bold text-gray-950">{{ collect($previewRoutes)->pluck('hospitals')->flatten()->unique()->count() }}</p>
                        </div>
                        <div class="rounded-md border border-cyan-200 bg-white px-3 py-2 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mensajeros</p>
                            <p class="mt-1 text-xl font-bold text-gray-950">{{ collect($previewRoutes)->pluck('messengers')->flatten()->unique()->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.9fr)]">
                    <div class="space-y-3">
                        @foreach ($previewRoutes as $index => $previewRoute)
                            <article class="rounded-lg border border-cyan-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyan-100 text-sm font-bold text-cyan-800">
                                                {{ $index + 1 }}
                                            </span>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-950">{{ $previewRoute['name'] }}</h3>
                                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $previewRoute['code'] }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($previewRoute['hospitals'] as $hospitalName)
                                                <span class="inline-flex rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                    {{ $hospitalName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-start gap-2 lg:items-end">
                                        <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$previewRoute['status']] ?? $statusClasses[\App\Models\DistributionRoute::STATUS_PENDING] }}">
                                            {{ $previewRoute['status_label'] }}
                                        </span>
                                        <span class="text-sm font-semibold text-gray-700">{{ $previewRoute['schedule'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $previewRoute['tracking'] }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
                                    @foreach ($previewRoute['messengers'] as $messengerName)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-800">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-cyan-700 text-[11px] text-white">
                                                {{ mb_strtoupper(collect(explode(' ', $messengerName))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('')) }}
                                            </span>
                                            {{ $messengerName }}
                                        </span>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="rounded-lg border border-cyan-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-cyan-700">Flujo esperado</h3>
                        <ol class="mt-4 space-y-4">
                            <li class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-bold text-white">1</span>
                                <div>
                                    <p class="font-semibold text-gray-900">Crear ruta</p>
                                    <p class="text-sm text-gray-600">Se asigna nombre, horario, hospitales cubiertos y mensajeros responsables.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-bold text-white">2</span>
                                <div>
                                    <p class="font-semibold text-gray-900">Generar QR</p>
                                    <p class="text-sm text-gray-600">El mensajero escanea el codigo para abrir la ruta e iniciar el recorrido.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-bold text-white">3</span>
                                <div>
                                    <p class="font-semibold text-gray-900">Seguimiento en tiempo real</p>
                                    <p class="text-sm text-gray-600">La central puede ver estatus, hospitales cubiertos y mensajeros asignados.</p>
                                </div>
                            </li>
                        </ol>

                        <div class="mt-5 rounded-lg border border-dashed border-blue-200 bg-blue-50 p-4 text-center">
                            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-2xl border-2 border-dashed border-blue-300 bg-white text-blue-700">
                                <i class="fa-solid fa-qrcode text-4xl" aria-hidden="true"></i>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-gray-900">QR de ejemplo</p>
                            <p class="mt-1 text-xs text-gray-500">Se mostrara aqui cuando captures una ruta real.</p>
                        </div>
                    </aside>
                </div>
            </section>
        @endif

        <div class="mt-5 overflow-x-auto border-x border-b border-gray-200">
            <table id="distribution-routes-table" class="w-full min-w-[1450px] text-left text-sm text-gray-700">
                <thead class="bg-white text-xs font-semibold uppercase text-gray-600">
                    <tr class="border-b border-gray-200">
                        <x-filterable-table-header column="0" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" scope="col">Ruta</x-filterable-table-header>
                        <x-filterable-table-header column="1" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" scope="col">Hospitales cubiertos</x-filterable-table-header>
                        <x-filterable-table-header column="2" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" scope="col">Horario</x-filterable-table-header>
                        <x-filterable-table-header column="3" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" scope="col">Mensajeros asignados</x-filterable-table-header>
                        <x-filterable-table-header column="4" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" align="center" scope="col">QR de ruta</x-filterable-table-header>
                        <x-filterable-table-header column="5" trigger-class="js-distribution-column-filter"
                            sort-class="js-distribution-column-sort" align="center" scope="col">Estatus</x-filterable-table-header>
                        <th class="px-4 py-3 text-center">Seguimiento</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($routes as $distributionRoute)
                        @php
                            $hospitalNames = $distributionRoute->hospitals
                                ->map(fn ($hospital) => $hospital->short_name ?: $hospital->name)
                                ->filter()
                                ->join(' | ');
                            $messengerNames = $distributionRoute->messengers
                                ->map(fn ($messenger) => trim($messenger->name.' '.$messenger->lastname))
                                ->filter()
                                ->join(' | ');
                            $routeSchedule = $distributionRoute->scheduleLabel();
                            $routeStatus = $statuses[$distributionRoute->status] ?? $distributionRoute->status;
                        @endphp
                        <tr class="js-distribution-filter-row align-middle hover:bg-gray-50/70">
                            <td class="px-4 py-4" data-filter-value="{{ $distributionRoute->name }} | {{ $distributionRoute->code }}"
                                data-sort-value="{{ $distributionRoute->name }}">
                                <a href="{{ route('admin.distribution.show', $distributionRoute) }}" class="font-bold text-gray-950 hover:text-blue-700">
                                    {{ $distributionRoute->name }}
                                </a>
                                <p class="mt-1 font-medium text-gray-500">{{ $distributionRoute->code }}</p>
                            </td>
                            <td class="max-w-[330px] px-4 py-4" data-filter-value="{{ $hospitalNames ?: 'Sin hospitales' }}"
                                data-sort-value="{{ $hospitalNames }}">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($distributionRoute->hospitals->take(2) as $hospital)
                                        <span class="inline-flex max-w-[240px] truncate rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ $hospital->short_name ?: $hospital->name }}
                                        </span>
                                    @endforeach
                                    @if ($distributionRoute->hospitals->count() > 2)
                                        <span class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                            +{{ $distributionRoute->hospitals->count() - 2 }} hospital{{ $distributionRoute->hospitals->count() - 2 === 1 ? '' : 'es' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 font-medium text-gray-700"
                                data-filter-value="{{ $routeSchedule }}" data-sort-value="{{ $routeSchedule }}">{{ $routeSchedule }}</td>
                            <td class="px-4 py-4" data-filter-value="{{ $messengerNames ?: 'Sin mensajeros' }}"
                                data-sort-value="{{ $messengerNames }}">
                                <div class="space-y-2">
                                    @foreach ($distributionRoute->messengers as $messenger)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-800">
                                                {{ mb_strtoupper(mb_substr($messenger->name, 0, 1).mb_substr($messenger->lastname ?: $messenger->name, 0, 1)) }}
                                            </span>
                                            <span class="whitespace-nowrap font-medium text-gray-800">{{ trim($messenger->name.' '.$messenger->lastname) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center" data-filter-value="{{ $distributionRoute->code }}"
                                data-sort-value="{{ $distributionRoute->code }}">
                                <img src="{{ route('admin.distribution.qr', $distributionRoute) }}" alt="Código QR de {{ $distributionRoute->name }}"
                                    class="mx-auto h-20 w-20" width="80" height="80">
                                <div class="mt-1 flex items-center justify-center gap-2 text-xs font-medium text-gray-500">
                                    <span>{{ $distributionRoute->code }}</span>
                                    <a href="{{ route('admin.distribution.qr', [$distributionRoute, 'download' => 1]) }}"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded text-blue-700 hover:bg-blue-50"
                                        title="Descargar QR" aria-label="Descargar QR de {{ $distributionRoute->name }}">
                                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center" data-filter-value="{{ $routeStatus }}"
                                data-sort-value="{{ $routeStatus }}">
                                <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$distributionRoute->status] ?? $statusClasses[\App\Models\DistributionRoute::STATUS_PENDING] }}">
                                    {{ $routeStatus }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <a href="{{ route('admin.distribution.show', $distributionRoute) }}"
                                    class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border-2 border-blue-600 px-3 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    Ver en tiempo real
                                </a>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <a href="{{ route('admin.distribution.edit', $distributionRoute) }}"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md border-2 border-blue-600 px-4 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        @if ($showPreviewRoutes)
                            @foreach ($previewRoutes as $previewRoute)
                                <tr class="align-middle bg-gradient-to-r from-slate-50 to-white opacity-90">
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-gray-950">{{ $previewRoute['name'] }}</div>
                                        <p class="mt-1 font-medium text-gray-500">{{ $previewRoute['code'] }}</p>
                                        <span class="mt-2 inline-flex rounded-md bg-amber-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800">
                                            Vista previa
                                        </span>
                                    </td>
                                    <td class="max-w-[330px] px-4 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach (array_slice($previewRoute['hospitals'], 0, 2) as $hospitalName)
                                                <span class="inline-flex max-w-[240px] truncate rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                                    {{ $hospitalName }}
                                                </span>
                                            @endforeach
                                            @if (count($previewRoute['hospitals']) > 2)
                                                <span class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                                    +{{ count($previewRoute['hospitals']) - 2 }} hospitales
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 font-medium text-gray-700">
                                        {{ $previewRoute['schedule'] }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="space-y-2">
                                            @foreach ($previewRoute['messengers'] as $messengerName)
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-800">
                                                        {{ mb_strtoupper(collect(explode(' ', $messengerName))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('')) }}
                                                    </span>
                                                    <span class="whitespace-nowrap font-medium text-gray-800">{{ $messengerName }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-xl border-2 border-dashed border-blue-200 bg-blue-50 text-blue-700">
                                            <i class="fa-solid fa-qrcode text-3xl" aria-hidden="true"></i>
                                        </div>
                                        <div class="mt-1 text-xs font-medium text-gray-500">{{ $previewRoute['code'] }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$previewRoute['status']] ?? $statusClasses[\App\Models\DistributionRoute::STATUS_PENDING] }}">
                                            {{ $previewRoute['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600">
                                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                            {{ $previewRoute['tracking'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 text-sm font-semibold text-gray-500">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                            Simulado
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="8" class="border-t border-gray-200 px-6 py-5 text-center">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        Vista previa de ejemplo. Aún no hay rutas reales capturadas para esta central.
                                    </span>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.distribution.create', $createRouteParameters) }}"
                                            class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                            Crear primera ruta real
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @else
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-xl text-blue-700">
                                    <i class="fa-solid fa-route" aria-hidden="true"></i>
                                </span>
                                <p class="mt-3 font-semibold text-gray-900">No hay rutas para la central seleccionada.</p>
                                <p class="mt-1 text-sm text-gray-500">Crea la primera ruta y asigna sus hospitales y mensajeros.</p>
                                <a href="{{ route('admin.distribution.create', $createRouteParameters) }}"
                                    class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    Crear nueva ruta
                                </a>
                            </td>
                        </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($routes->hasPages())
            <div class="mt-4">{{ $routes->links() }}</div>
        @endif
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                const centralCarousel = document.getElementById('distribution-central-carousel');
                const previousCentralButton = document.getElementById('distribution-central-previous');
                const nextCentralButton = document.getElementById('distribution-central-next');

                if (centralCarousel && previousCentralButton && nextCentralButton) {
                    const updateCentralNavigation = () => {
                        const maxScroll = Math.max(0, centralCarousel.scrollWidth - centralCarousel.clientWidth);

                        previousCentralButton.disabled = centralCarousel.scrollLeft <= 2;
                        nextCentralButton.disabled = centralCarousel.scrollLeft >= maxScroll - 2;
                    };
                    const selectedCentral = centralCarousel.querySelector('[aria-current="true"]');

                    if (selectedCentral) {
                        centralCarousel.scrollLeft = Math.max(
                            0,
                            selectedCentral.offsetLeft - ((centralCarousel.clientWidth - selectedCentral.clientWidth) / 2),
                        );
                    }

                    previousCentralButton.addEventListener('click', () => {
                        centralCarousel.scrollBy({ left: -300, behavior: 'smooth' });
                    });
                    nextCentralButton.addEventListener('click', () => {
                        centralCarousel.scrollBy({ left: 300, behavior: 'smooth' });
                    });
                    centralCarousel.addEventListener('scroll', updateCentralNavigation, { passive: true });
                    window.addEventListener('resize', updateCentralNavigation);
                    requestAnimationFrame(updateCentralNavigation);
                }

                window.createExcelColumnFilters({
                    tableId: 'distribution-routes-table',
                    rowSelector: '.js-distribution-filter-row',
                    triggerSelector: '.js-distribution-column-filter',
                    instanceId: 'distribution-routes',
                    onChange() {
                        document.querySelectorAll('.js-distribution-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });

                const table = document.getElementById('distribution-routes-table');
                const tbody = table?.tBodies[0];
                const sortButtons = Array.from(table?.querySelectorAll('.js-distribution-column-sort') || []);
                let activeSortColumn = null;
                let activeSortDirection = 'asc';

                const sortableValue = (row, column) => String(
                    row.cells[column]?.dataset.sortValue ?? row.cells[column]?.textContent ?? ''
                ).trim().toLocaleLowerCase('es');

                sortButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const column = Number(button.dataset.sortColumn);
                        const direction = activeSortColumn === column && activeSortDirection === 'asc'
                            ? 'desc'
                            : 'asc';
                        const rows = Array.from(tbody?.querySelectorAll('.js-distribution-filter-row') || []);

                        rows.sort((leftRow, rightRow) => {
                            const result = sortableValue(leftRow, column).localeCompare(
                                sortableValue(rightRow, column),
                                'es',
                                { numeric: true, sensitivity: 'base' },
                            );

                            return direction === 'asc' ? result : -result;
                        });

                        rows.forEach((row) => tbody?.appendChild(row));
                        activeSortColumn = column;
                        activeSortDirection = direction;

                        sortButtons.forEach((sortButton) => {
                            const isActive = sortButton === button;
                            const icon = sortButton.querySelector('[data-sort-icon]');
                            const label = sortButton.dataset.sortLabel || 'columna';

                            sortButton.classList.toggle('border-blue-400', isActive);
                            sortButton.classList.toggle('bg-blue-100', isActive);
                            sortButton.classList.toggle('text-blue-700', isActive);
                            sortButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            sortButton.setAttribute('aria-label', isActive
                                ? `Ordenar ${label} ${direction === 'asc' ? 'descendente' : 'ascendente'}`
                                : `Ordenar ${label}`);

                            if (icon) {
                                icon.className = isActive
                                    ? `fa-solid ${direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'}`
                                    : 'fa-solid fa-sort';
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
