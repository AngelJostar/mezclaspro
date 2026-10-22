<x-admin-layout>
    @php
        $routePrefix = 'admin.maintenance-qualifications.';
        $url = fn ($changes = []) => route($routePrefix.'index', array_filter(array_merge($filters, $changes), fn ($v) => $v !== null && $v !== ''));
        $statusLabels = \App\Models\MaintenanceAppointment::STATUSES;
        $reportLabels = \App\Models\MaintenanceAppointment::REPORT_STATUSES;
        $appointmentRecord = fn ($a) => array_merge($a->only(['service_id', 'provider_id', 'laboratory_id', 'asset', 'location', 'responsible_name', 'frequency', 'status', 'report_notes']), ['starts_at' => $a->starts_at->format('Y-m-d\TH:i'), 'ends_at' => $a->ends_at?->format('Y-m-d\TH:i'), 'next_service_at' => $a->next_service_at?->toDateString()]);
        $serviceRecord = fn ($s) => array_merge($s->only(['service', 'laboratory_id', 'type', 'frequency', 'description', 'applicable_assets', 'deliverables', 'report_required', 'is_active']), ['provider_ids' => $s->serviceProviders->pluck('id')]);
        $providerRecord = fn ($p) => array_merge($p->only(['name', 'specialty', 'contact_name', 'email', 'phone', 'coverage', 'is_active']), ['service_ids' => $p->services->pluck('id')]);
    @endphp
    <div class="maintenance-workspace" data-maintenance-workspace data-index-url="{{ route($routePrefix.'index') }}">
        <p class="mw-breadcrumb">Administración / <span>Mantenimiento y Calificaciones</span></p>
        <div class="mw-heading"><h1>Mantenimiento y Calificaciones</h1><div class="mw-legacy">
            <a href="{{ route($routePrefix.'annual', array_filter(['laboratory_id' => $laboratoryId])) }}">Calendario anual</a>
            <a href="{{ route($routePrefix.'price-list', array_filter(['laboratory_id' => $laboratoryId])) }}">Cotizaciones</a>
        </div></div>
        <section class="mw-centrals" aria-label="Selecciona una central">
            <h2>Selecciona una central</h2>
            <div class="mw-central-list" data-disable-sticky-x>
                <a class="mw-central {{ !$laboratoryId ? 'is-active' : '' }}" href="{{ $url(['laboratory_id' => null, 'seleccion' => null]) }}" @if(!$laboratoryId) aria-current="true" @endif><strong>Todas</strong><span>Catálogo consolidado · {{ $laboratories->count() }} centrales</span></a>
                @foreach($laboratories as $lab)
                    <a class="mw-central {{ $laboratoryId === $lab->id ? 'is-active' : '' }}" href="{{ $url(['laboratory_id' => $lab->id, 'seleccion' => null]) }}" @if($laboratoryId === $lab->id) aria-current="true" @endif><strong>{{ $lab->nombre }}</strong><span>{{ $lab->estado }}</span></a>
                @endforeach
            </div>
        </section>
        <nav class="mw-tabs" aria-label="Vistas de mantenimiento">
            @foreach(['calendario' => ['Calendario', 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z'], 'servicios' => ['Catálogo de servicios', 'M14 2H6a2 2 0 0 0-2 2v16h16V8Zm0 0v6h6M8 12h8M8 16h5'], 'proveedores' => ['Catálogo de proveedores de servicios', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M15 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z'], 'programacion' => ['Programación y reportes', 'M4 12h3v9H4Zm6-9h3v18h-3Zm6 5h3v13h-3Z']] as $key => [$label, $icon])
                <a href="{{ $url(['seccion' => $key, 'q' => null, 'tipo' => null, 'especialidad' => null, 'estado' => null, 'seleccion' => null]) }}" class="{{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="{{ $icon }}"/></svg><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        @if(session('maintenance_message'))<p class="mw-notice" role="status">{{ session('maintenance_message') }}</p>@endif
        <div class="mw-panel">
            @include('admin.maintenance-qualifications.workspace.'.$section)
        </div>
        @include('admin.maintenance-qualifications.workspace.dialogs')
    </div>
</x-admin-layout>
