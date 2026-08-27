@php
    $selectedType = $selectedType ?? 'todas';
    $canViewNutrition = $canViewNutrition ?? auth()->user()?->can('nutricionales_solicitudes_index');
    $canViewOncology = $canViewOncology ?? auth()->user()?->can('oncologicos_solicitudes_index');
    $selectorId = 'request-type-selector-' . $selectedType;
    $activeStatus = App\Support\SolicitudStatusFilter::normalize(request()->query('estado'));
    $typeQuery = $activeStatus === App\Support\SolicitudStatusFilter::ALL
        ? []
        : ['estado' => $activeStatus];
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
            'label' => 'Oncologicas',
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
@endphp

@once
    <style>
        .request-type-selector__item--active {
            background-color: #2f4382 !important;
            border-color: #2f4382 !important;
            color: #ffffff !important;
        }
    </style>
@endonce

<nav class="mt-4 flex max-w-3xl items-center gap-2" aria-label="Tipo de solicitudes">
    <button type="button"
        onclick="document.getElementById('{{ $selectorId }}').scrollBy({ left: -240, behavior: 'smooth' })"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50"
        title="Anterior" aria-label="Tipo anterior">
        <span class="text-lg leading-none" aria-hidden="true">&lsaquo;</span>
    </button>

    <div id="{{ $selectorId }}"
        class="request-selector-scroll flex min-w-0 gap-2 overflow-x-auto scroll-smooth pb-1"
        data-disable-sticky-x>
        @foreach ($requestTypes as $requestType)
            @php($isSelected = $selectedType === $requestType['key'])
            <a href="{{ $requestType['route'] }}"
                @if ($isSelected) aria-current="page" @endif
                @class([
                    'flex h-11 min-w-32 shrink-0 items-center justify-center rounded-md border px-4 text-sm font-semibold transition',
                    'request-type-selector__item--active' => $isSelected,
                    'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50' => !$isSelected,
                ])>
                {{ $requestType['label'] }}
            </a>
        @endforeach
    </div>

    <button type="button"
        onclick="document.getElementById('{{ $selectorId }}').scrollBy({ left: 240, behavior: 'smooth' })"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50"
        title="Siguiente" aria-label="Tipo siguiente">
        <span class="text-lg leading-none" aria-hidden="true">&rsaquo;</span>
    </button>
</nav>
