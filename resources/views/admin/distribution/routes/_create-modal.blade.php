@php
    $isEditingInitially = $editingRoute !== null;
    $initialHospitalIds = $errors->any()
        ? collect(old('hospital_ids', []))->map(fn ($id) => (int) $id)->values()
        : collect($editingRoute?->hospitals?->pluck('id') ?? [])->map(fn ($id) => (int) $id)->values();
    $initialRouteName = old('name', $editingRoute?->name ?? '');
    $initialRouteType = old('route_type', $editingRoute?->route_type ?? 'vehicular');
    $initialRouteType = in_array($initialRouteType, ['vehicular', 'dron'], true) ? $initialRouteType : 'vehicular';
    $initialUpdateUrl = $editingRoute
        ? route('admin.distribution.routes.update', $editingRoute)
        : '';
    $openRouteModal = $isEditingInitially || $errors->any();
    $initialRouteMode = [
        'mode' => $isEditingInitially ? 'edit' : 'create',
        'route_id' => $editingRoute?->id,
        'name' => $initialRouteName,
        'route_type' => $initialRouteType,
        'update_url' => $initialUpdateUrl,
        'hospital_ids' => $initialHospitalIds,
    ];
@endphp

<div id="route-create-modal" class="fixed inset-0 z-[80] hidden items-center justify-center p-3 sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="route-create-title" aria-hidden="true"
    data-open-on-load="{{ $openRouteModal ? 'true' : 'false' }}">
    <button type="button" class="absolute inset-0 cursor-default bg-slate-950/55 backdrop-blur-[1px]"
        data-route-modal-close aria-label="Cerrar ventana"></button>

    <section class="relative flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <h2 id="route-create-title" class="text-xl font-semibold text-slate-900">
                    {{ $isEditingInitially ? 'Editar ruta' : 'Crear nueva ruta' }}
                </h2>
                <p id="route-create-subtitle" class="mt-1 text-sm text-slate-500">
                    {{ $isEditingInitially ? 'Modifica el nombre, los hospitales o el orden del recorrido.' : 'Selecciona uno a uno los hospitales que formar&aacute;n parte del recorrido.' }}
                </p>
            </div>
            <button type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-400"
                data-route-modal-close title="Cerrar" aria-label="Cerrar">
                <span class="text-xl leading-none" aria-hidden="true">&times;</span>
            </button>
        </header>

        <form id="route-create-form" method="POST"
            action="{{ $isEditingInitially ? $initialUpdateUrl : route('admin.distribution.routes.store') }}"
            data-create-action="{{ route('admin.distribution.routes.store') }}"
            class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input id="route-form-method" type="hidden" name="_method" value="PATCH" @disabled(! $isEditingInitially)>
            <input id="route-form-id" type="hidden" name="route_id" value="{{ $editingRoute?->id }}">
            <input id="route-form-type" type="hidden" name="route_type" value="{{ $initialRouteType }}">

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 sm:px-6">
                @if ($errors->any())
                    <div role="alert" class="mb-4 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold">Revisa la informaci&oacute;n de la ruta.</p>
                            <p class="mt-0.5">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="route-name" class="mb-1 block text-sm font-semibold text-slate-700">Nombre de la ruta *</label>
                        <input id="route-name" name="name" type="text" value="{{ $initialRouteName }}" required maxlength="255"
                            placeholder="Ej. Ruta Norte 01"
                            class="h-10 w-full rounded-md border-slate-300 px-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="relative">
                        <label for="route-hospital-search" class="mb-1 block text-sm font-semibold text-slate-700">Buscar hospital</label>
                        <input id="route-hospital-search" type="search" autocomplete="off"
                            placeholder="Escribe el nombre del hospital..." role="combobox" aria-autocomplete="list"
                            aria-controls="route-hospital-suggestions" aria-expanded="false"
                            class="h-10 w-full rounded-md border-slate-300 px-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <div id="route-hospital-suggestions"
                            class="absolute left-0 right-0 top-full z-[1000] mt-1 hidden max-h-64 overflow-y-auto rounded-md border border-slate-200 bg-white shadow-xl"
                            role="listbox"></div>
                        <p id="route-search-help" class="mt-1 text-xs text-slate-500">Busca por nombre o direcci&oacute;n.</p>
                    </div>
                </div>

                <div class="mt-4 grid min-h-0 gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="relative min-h-[25rem] overflow-hidden rounded-md border border-slate-200 bg-slate-100">
                        <div id="route-hospital-map" class="h-[clamp(25rem,56vh,35rem)] w-full" aria-label="Mapa para seleccionar hospitales"></div>

                        <div id="route-map-loading" class="pointer-events-none absolute inset-0 z-[500] flex items-center justify-center bg-slate-100 text-sm text-slate-500">
                            <span class="inline-flex items-center gap-2">
                                <span class="route-map-spinner" aria-hidden="true"></span>
                                Cargando mapa...
                            </span>
                        </div>

                        <div id="route-map-error" class="absolute inset-x-4 top-4 z-[600] hidden rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            El mapa no pudo cargarse. Puedes seleccionar hospitales desde el buscador.
                        </div>

                        <div class="pointer-events-none absolute bottom-4 left-4 z-[600] rounded-md border border-slate-200 bg-white/95 px-3 py-2 text-xs text-slate-700 shadow-md backdrop-blur-sm">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>Disponible</span>
                            <span class="mt-1.5 flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>Asignado a otra ruta</span>
                            <span id="route-coordinate-legend" class="mt-1.5 hidden flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Sin coordenadas registradas</span>
                        </div>
                    </div>

                    <aside class="flex min-h-[25rem] flex-col rounded-md border border-slate-200 bg-slate-50/60" aria-labelledby="selected-hospitals-title">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <h3 id="selected-hospitals-title" class="text-sm font-semibold text-slate-900">Hospitales seleccionados</h3>
                            <span id="route-selected-count" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-blue-100 px-2 text-xs font-bold text-blue-700">0</span>
                        </div>

                        <div id="route-selected-empty" class="flex flex-1 flex-col items-center justify-center px-5 py-10 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-xl text-blue-500">
                                <span class="font-semibold" aria-hidden="true">H</span>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-slate-700">A&uacute;n no seleccionas hospitales</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Agr&eacute;galos desde el mapa o mediante la b&uacute;squeda.</p>
                        </div>

                        <ol id="route-selected-list" class="hidden min-h-0 flex-1 overflow-y-auto p-2" aria-label="Orden de hospitales"></ol>

                        <div class="shrink-0 border-t border-slate-200 p-3">
                            <p class="flex items-start gap-2 rounded-md bg-blue-50 px-3 py-2 text-xs leading-5 text-blue-700">
                                Arrastra los hospitales o usa las flechas para cambiar el orden.
                            </p>
                            <button id="route-optimize-order" type="button" disabled
                                class="mt-2 inline-flex h-9 w-full items-center justify-center gap-2 rounded-md border border-blue-200 bg-white px-3 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400">
                                Optimizar orden
                            </button>
                        </div>
                    </aside>
                </div>

                <div class="mt-4 flex items-start gap-2 rounded-md border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">
                    <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
                    <p id="route-selection-guidance">Solo puedes seleccionar hospitales disponibles en azul. Los puntos grises ya pertenecen a otra ruta. Las ubicaciones sin coordenadas registradas se muestran de forma aproximada.</p>
                </div>

                <div id="route-hospital-inputs"></div>
                @error('hospital_ids')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('hospital_ids.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-6">
                <button type="button" data-route-modal-close
                    class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancelar
                </button>
                <button id="route-save-button" type="submit" disabled
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed disabled:bg-blue-200">
                    <span>{{ $isEditingInitially ? 'Guardar cambios' : 'Guardar ruta' }}</span>
                </button>
            </footer>
        </form>
    </section>
</div>

<script id="route-hospital-data" type="application/json">{!! json_encode($routeHospitals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script id="route-initial-hospital-data" type="application/json">{!! json_encode($initialHospitalIds, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script id="route-initial-mode-data" type="application/json">{!! json_encode($initialRouteMode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
