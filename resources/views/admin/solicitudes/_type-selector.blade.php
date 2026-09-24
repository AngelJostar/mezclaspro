@php
    $selectedType = $selectedType ?? 'todas';
    $canViewNutrition = $canViewNutrition ?? auth()->user()?->can('nutricionales_solicitudes_index');
    $canViewOncology = $canViewOncology ?? auth()->user()?->can('oncologicos_solicitudes_index');
    $selectorId = 'request-type-selector-' . $selectedType;
    if (isset($typeSelectorQuery)) {
        $typeQuery = $typeSelectorQuery;
    } else {
        $activeStatus = App\Support\SolicitudStatusFilter::normalize(request()->query('estado'));
        $typeQuery = $activeStatus === App\Support\SolicitudStatusFilter::ALL
            ? []
            : ['estado' => $activeStatus];
    }
    $requestTypes = collect([
        [
            'key' => 'todas',
            'label' => 'Todas',
            'route' => route('admin.solicitudes.index', $typeQuery),
            'visible' => $canViewNutrition || $canViewOncology,
        ],
        [
            'key' => 'nutricionales',
            'label' => 'Nutricionales',
            'route' => route('admin.nutricionales.solicitudes.index', $typeQuery),
            'visible' => $canViewNutrition,
        ],
        [
            'key' => 'oncologicos',
            'label' => $typeSelectorLabels['oncologicos'] ?? 'Oncologicas',
            'route' => route('admin.oncologicos.solicitudes.index', $typeQuery),
            'visible' => $canViewOncology,
        ],
        [
            'key' => 'antibioticos',
            'label' => 'Antibioticos',
            'route' => route('admin.antibioticos.solicitudes.index', $typeQuery),
            'visible' => $canViewOncology,
        ],
    ])->where('visible')->values();

    if (isset($typeSelectorRoute)) {
        $requestTypes = $requestTypes->map(fn (array $type) => array_replace($type, [
            'route' => route($typeSelectorRoute, array_merge($typeQuery, $type['key'] === 'todas' ? [] : ['tipo' => $type['key']])),
        ]));
    }
@endphp

<nav class="mt-4 flex max-w-4xl items-center gap-2" aria-label="{{ $typeSelectorLabel ?? 'Tipo de solicitudes' }}" data-request-type-selector>
    <button type="button"
        onclick="document.getElementById('{{ $selectorId }}').scrollBy({ left: -240, behavior: 'smooth' })"
        class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:bg-gray-50"
        title="Anterior" aria-label="Tipo anterior">
        <i data-request-navigation-icon="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
    </button>

    <div id="{{ $selectorId }}"
        class="request-selector-scroll flex min-w-0 gap-2 overflow-x-auto scroll-smooth py-1"
        data-disable-sticky-x>
        @foreach ($requestTypes as $requestType)
            @php($isSelected = $selectedType === $requestType['key'])
            <a href="{{ $requestType['route'] }}"
                @if ($isSelected) aria-current="page" @endif
                @class([
                    'flex h-16 w-48 shrink-0 items-center gap-3 rounded-lg border px-4 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-600',
                    'border-cyan-500 bg-cyan-50 text-gray-900 shadow-sm ring-1 ring-cyan-300' => $isSelected,
                    'border-gray-200 bg-white text-gray-700 hover:border-cyan-300 hover:bg-gray-50' => !$isSelected,
                ])>
                <span aria-hidden="true"
                    @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-50 text-xs font-bold',
                        'text-cyan-700' => $isSelected,
                        'text-gray-500' => !$isSelected,
                    ])>{{ Str::upper(Str::substr($requestType['label'], 0, 1)) }}</span>
                <span class="min-w-0">
                    <span class="block text-sm font-bold">{{ $requestType['label'] }}</span>
                    @if ($isSelected)
                        <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-cyan-700">
                            <i data-request-navigation-icon="check" class="h-3 w-3 shrink-0" aria-hidden="true"></i>
                            Seleccionada
                        </span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    <button type="button"
        onclick="document.getElementById('{{ $selectorId }}').scrollBy({ left: 240, behavior: 'smooth' })"
        class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:bg-gray-50"
        title="Siguiente" aria-label="Tipo siguiente">
        <i data-request-navigation-icon="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
    </button>
</nav>
