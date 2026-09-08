@php
    $locatedHospitals = $mapHospitals->filter(fn ($hospital) => $hospital['latitude'] !== null && $hospital['longitude'] !== null);
    $unlocatedHospitals = $mapHospitals->reject(fn ($hospital) => $hospital['latitude'] !== null && $hospital['longitude'] !== null);
    $estimatedCount = $locatedHospitals->where('estimated', true)->count();
@endphp

<section class="institution-hospital-map-section" aria-labelledby="institution-hospital-map-title" data-institution-hospital-map data-color-mode="status">
    <div class="institution-hospital-map-heading">
        <div>
            <h2 id="institution-hospital-map-title">Mapa de hospitales</h2>
            <p>{{ $locatedHospitals->count() }} de {{ $totalHospitals }} hospitales ubicados</p>
        </div>
        @if ($locatedHospitals->isNotEmpty())
            <div class="institution-hospital-map-tools">
                <label class="sr-only" for="institution-map-hospital">Localizar hospital</label>
                <select id="institution-map-hospital" data-map-hospital>
                    <option value="">Todos los hospitales</option>
                    @foreach ($locatedHospitals as $mapHospital)
                        <option value="{{ $mapHospital['id'] }}">{{ $mapHospital['name'] }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="institution-map-color-mode">Colorear hospitales por</label>
                <select id="institution-map-color-mode" data-map-color-mode>
                    <option value="status">Activos e inactivos</option>
                    <option value="route">Ruta asignada</option>
                </select>
                <button type="button" data-map-fit title="Mostrar todos los hospitales" aria-label="Mostrar todos los hospitales">
                    <i data-institution-map-icon="locate-fixed" aria-hidden="true"></i>
                </button>
            </div>
        @endif
    </div>

    @if ($mapHospitals->isNotEmpty())
        <div class="institution-hospital-map-frame">
            <div id="institution-hospital-map" class="institution-hospital-map" aria-label="Ubicaciones de hospitales de {{ $institucion->nombre }}"></div>
            <div class="institution-hospital-map-loading" data-map-loading role="status">Cargando mapa...</div>
            <div class="institution-hospital-map-legend" aria-label="Leyenda del mapa">
                <span data-map-legend="status"><i class="map-dot is-active" aria-hidden="true"></i> Activo</span>
                <span data-map-legend="status"><i class="map-dot is-inactive" aria-hidden="true"></i> Inactivo</span>
                <span data-map-legend="route" hidden><i class="map-dot is-route-assigned" aria-hidden="true"></i> Ruta asignada</span>
                <span data-map-legend="route" hidden><i class="map-dot is-route-unassigned" aria-hidden="true"></i> Sin asignar</span>
                @if ($estimatedCount > 0)
                    <span><i class="map-dot is-estimated" aria-hidden="true"></i> Ubicaci&oacute;n aproximada ({{ $estimatedCount }})</span>
                @endif
            </div>
        </div>
        <p class="institution-hospital-map-warning" data-map-error role="status" hidden>No se pudo cargar el mapa base. Revisa la conexi&oacute;n a internet.</p>
        <script type="application/json" data-map-hospitals>@json($mapHospitals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
        <noscript><p class="institution-hospital-map-warning">Activa JavaScript para visualizar el mapa.</p></noscript>
    @else
        <p class="institution-hospital-map-empty">Esta instituci&oacute;n no tiene hospitales registrados.</p>
    @endif

    @if ($unlocatedHospitals->isNotEmpty())
        <details class="institution-hospital-map-missing">
            <summary>Sin ubicaci&oacute;n identificable ({{ $unlocatedHospitals->count() }})</summary>
            <ul>
                @foreach ($unlocatedHospitals as $mapHospital)
                    <li>{{ $mapHospital['name'] }} <span>Coordenadas pendientes</span></li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
