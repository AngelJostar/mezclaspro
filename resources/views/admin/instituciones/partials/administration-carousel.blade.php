@php
    $administrationTabs = [
        'reportes' => 'Reportes',
        'conciliacion' => 'Conciliación',
        'facturacion' => 'Facturación',
        'ajustes' => 'Bitácora de ajustes',
        'pagos' => 'Pagos',
    ];
    $billingSections = \App\Support\AdministrationNavigation::billingSections(auth()->user());
    $canViewReports = \App\Support\AdministrationNavigation::canViewReports(auth()->user());
    $administrationTabs = array_filter($administrationTabs, fn ($key) => $key === 'facturacion' ? count($billingSections) > 0 : $canViewReports, ARRAY_FILTER_USE_KEY);
    $administrationQuery = request()->only(['search', 'daily_from', 'daily_to']);
@endphp

<style>
    .administration-carousel__item[aria-current="page"] {
        background-color: #2f4382 !important;
        border-color: #2f4382 !important;
        color: #ffffff !important;
    }
</style>

<nav class="mb-4 flex max-w-3xl items-center gap-2" aria-label="Secciones de administración" data-administration-carousel>
    <button type="button"
        data-carousel-direction="-1"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50"
        title="Anterior" aria-label="Sección anterior">
        <i data-administration-icon="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
    </button>

    <div id="administration-carousel"
        class="request-selector-scroll flex min-w-0 gap-2 overflow-x-auto scroll-smooth pb-1"
        data-disable-sticky-x>
        @foreach ($administrationTabs as $key => $label)
            <a href="{{ $key === 'facturacion'
                ? route(reset($billingSections)['route'], request()->only(['search', 'date_from', 'date_to']))
                : route('admin.instituciones.reportes', array_merge($administrationQuery, $key === 'reportes' ? [] : ['seccion' => $key])) }}"
                @if ($administrationSection === $key) aria-current="page" @endif
                class="administration-carousel__item flex h-11 min-w-32 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:bg-blue-50">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <button type="button"
        data-carousel-direction="1"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50"
        title="Siguiente" aria-label="Sección siguiente">
        <i data-administration-icon="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
    </button>
</nav>
