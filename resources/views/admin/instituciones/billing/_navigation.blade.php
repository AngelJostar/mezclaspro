@php
    $billingNavigationSections = \App\Support\AdministrationNavigation::billingSections(auth()->user());
@endphp

<nav aria-label="Secciones de facturación" class="mb-5 flex flex-wrap items-center gap-2">
    @foreach ($billingNavigationSections as $key => $section)
        @php
            $billingQuery = request()->only($key === 'movements'
                ? ['search', 'date_from', 'date_to']
                : ['institucion_id', 'hospital_id', 'search', 'date_from', 'date_to', 'billing_status', 'facturacion_status', 'conciliable_filter']);
        @endphp
        <a href="{{ route($section['route'], $billingQuery) }}"
            @if ($billingSection === $key) aria-current="page" @endif
            @class([
                'inline-flex min-h-9 items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition',
                'border-emerald-600 bg-emerald-600 text-white' => $billingSection === $key,
                'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' => $billingSection !== $key,
            ])>
            {{ $section['label'] }}
            @if ($key === 'pending' && isset($billingDueCounts))
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full border border-amber-300 bg-amber-100 px-1 text-xs text-amber-800"
                    title="Vencimientos amarillos" aria-label="{{ $billingDueCounts['yellow'] }} solicitudes con vencimiento amarillo">{{ $billingDueCounts['yellow'] }}</span>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full border border-red-300 bg-red-100 px-1 text-xs text-red-700"
                    title="Vencimientos rojos" aria-label="{{ $billingDueCounts['red'] }} solicitudes con vencimiento rojo">{{ $billingDueCounts['red'] }}</span>
            @endif
        </a>
    @endforeach
</nav>
