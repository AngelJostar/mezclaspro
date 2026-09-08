<x-admin-layout>
    @push('css')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIINfQn2D7F5lC3H8Q1d6rE1y3F1pLl6e5A=" crossorigin="" />
        <style>
            #distribution-live-map { min-height: 520px; }
        </style>
    @endpush

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
                <a class="hover:text-blue-700" href="{{ route('admin.distribution.routes.index') }}">Distribuci&oacute;n</a>
                <span class="mx-1">/</span>
                <span>Monitoreo de ruta</span>
            </nav>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Monitoreo en tiempo real</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $distributionRoute->name }} · {{ $distributionRoute->code }}</p>
        </div>
        <a href="{{ route('admin.distribution.routes.index') }}"
            class="inline-flex h-10 items-center justify-center rounded-md border border-blue-600 bg-white px-4 text-sm font-semibold text-blue-700 hover:bg-blue-50">
            Volver a rutas
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div id="distribution-live-map" aria-label="Mapa de ubicación del mensajero"></div>
        </section>
        <aside class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de monitoreo</p>
                <p id="live-route-status" class="mt-2 text-lg font-semibold text-slate-900">Cargando…</p>
                <p id="live-messenger" class="mt-4 text-sm font-medium text-slate-800">Sin mensajero activo</p>
                <p id="live-location-time" class="mt-1 text-xs leading-5 text-slate-500">Esperando ubicación desde la app.</p>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actualización</p>
                <p class="mt-2 text-sm text-slate-700">El mapa se actualiza cada 10 segundos mientras esta pantalla permanezca abierta.</p>
                <button type="button" id="live-location-refresh"
                    class="mt-4 inline-flex h-9 w-full items-center justify-center rounded-md bg-blue-700 px-3 text-xs font-semibold text-white hover:bg-blue-800">
                    Actualizar ahora
                </button>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paradas</p>
                <ol id="live-stops" class="mt-3 space-y-3 text-sm text-slate-700"></ol>
            </section>
        </aside>
    </div>

    @push('js')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (() => {
                const endpoint = @json(route('admin.distribution.routes.live-location', $distributionRoute));
                const map = L.map('distribution-live-map').setView([19.4326, -99.1332], 11);
                const bounds = L.latLngBounds([]);
                let messengerMarker = null;
                let hasFittedBounds = false;
                let hasFocusedMessenger = false;

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const statusNames = { pending: 'Programada', in_route: 'En ruta', completed: 'Finalizada', delayed: 'Con retraso' };
                const status = document.getElementById('live-route-status');
                const messenger = document.getElementById('live-messenger');
                const locationTime = document.getElementById('live-location-time');
                const stops = document.getElementById('live-stops');

                const dateTime = (value) => value ? new Date(value).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : null;

                const render = (data) => {
                    status.textContent = statusNames[data.route.status] || data.route.status;
                    messenger.textContent = data.messenger?.name || 'Sin mensajero activo';
                    locationTime.textContent = data.location
                        ? `Última ubicación: ${dateTime(data.location.recorded_at)}${data.location.accuracy ? ` · precisión ${Math.round(data.location.accuracy)} m` : ''}`
                        : 'Esperando ubicación desde la app.';

                    stops.innerHTML = '';
                    data.stops.forEach((stop, index) => {
                        const item = document.createElement('li');
                        item.className = 'border-l-2 border-blue-200 pl-3';
                        item.innerHTML = `<span class="font-semibold">${index + 1}. ${stop.name}</span><span class="mt-0.5 block text-xs text-slate-500">${stop.address || 'Sin dirección registrada'}</span>`;
                        stops.appendChild(item);

                        if (Number.isFinite(stop.latitude) && Number.isFinite(stop.longitude)) {
                            const point = [stop.latitude, stop.longitude];
                            L.marker(point).addTo(map).bindPopup(`<strong>${stop.name}</strong><br>${stop.address || ''}`);
                            bounds.extend(point);
                        }
                    });

                    if (data.location) {
                        const point = [data.location.latitude, data.location.longitude];
                        if (messengerMarker) messengerMarker.setLatLng(point);
                        else messengerMarker = L.marker(point, { title: 'Ubicación del mensajero' }).addTo(map).bindPopup('<strong>Mensajero</strong>');
                        bounds.extend(point);
                    }

                    if (data.location && !hasFocusedMessenger) {
                        map.flyTo([data.location.latitude, data.location.longitude], 13, { animate: false });
                        hasFocusedMessenger = true;
                        hasFittedBounds = true;
                    }

                    if (!hasFittedBounds && bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [35, 35], maxZoom: 14 });
                        hasFittedBounds = true;
                    }
                };

                const load = async () => {
                    try {
                        const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error();
                        render(await response.json());
                    } catch {
                        locationTime.textContent = 'No fue posible actualizar la ubicación. Intenta de nuevo.';
                    }
                };

                document.getElementById('live-location-refresh').addEventListener('click', load);
                load();
                window.setInterval(load, 10000);
            })();
        </script>
    @endpush
</x-admin-layout>
