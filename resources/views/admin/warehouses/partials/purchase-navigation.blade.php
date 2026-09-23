@php
    $purchaseTabs = \App\Support\PurchaseNavigation::sections(auth()->user(), $selectedLaboratory?->id);
    $stockTypeQuery = ($stockType ?? 'todas') === 'todas' ? [] : ['tipo' => $stockType];
    $stockViewQuery = in_array($stockView ?? 'stock', ['automated', 'history'], true) ? $stockView : null;
@endphp

<section class="mb-5 border-b border-gray-200 pb-5" aria-labelledby="purchase-central-title" data-purchase-navigation>
    <h2 id="purchase-central-title" class="mb-3 text-sm font-semibold text-gray-800">Selecciona una central de mezclas</h2>
    @if ($laboratories->isNotEmpty())
        <div class="flex items-center gap-2">
            <button type="button" data-purchase-direction="-1" aria-label="Central anterior" title="Central anterior"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                <i data-purchase-icon="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
            </button>
            <nav class="request-selector-scroll flex min-w-0 flex-1 snap-x gap-3 overflow-x-auto pb-2" aria-label="Centrales de compras" data-purchase-carousel data-disable-sticky-x>
                @foreach ($laboratories as $central)
                    @php
                        $centralSelected = $selectedLaboratory?->id === $central->id;
                        $centralUrl = match ($section) {
                            'new' => route('admin.oncologicos.laboratory.purchase-orders.create', $central),
                            'minimum-stock' => route('admin.purchases.minimum-stock', array_filter(['laboratory_id' => $central->id, 'view' => $stockViewQuery]) + $stockTypeQuery),
                            default => route('admin.warehouses.purchase-orders.index', ['section' => $section, 'laboratory_id' => $central->id]),
                        };
                    @endphp
                    <a href="{{ $centralUrl }}" aria-label="Seleccionar central {{ $central->nombre }}"
                        @if ($centralSelected) aria-current="page" @endif
                        class="flex w-56 shrink-0 snap-start flex-col rounded border p-3 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-cyan-600 {{ $centralSelected ? 'border-cyan-500 bg-cyan-50' : 'border-gray-200 bg-white hover:border-cyan-400' }}"
                        style="width:14rem;max-width:100%;min-height:9rem">
                        <span class="flex items-start gap-2">
                            <i data-purchase-icon="building-2" class="mt-0.5 h-5 w-5 shrink-0 text-cyan-700" aria-hidden="true"></i>
                            <span class="min-w-0 break-words text-sm font-semibold text-gray-900">{{ $central->nombre }}</span>
                        </span>
                        <span class="mt-2 flex-1 break-words text-xs leading-4 text-gray-500">{{ $central->direccion ?: 'Direccion sin registrar' }}</span>
                        <span class="mt-2 text-xs text-gray-600">{{ $central->active_warehouses_count }} {{ $central->active_warehouses_count === 1 ? 'almacen activo' : 'almacenes activos' }}</span>
                        <span class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 {{ $central->activo ? 'text-green-700' : 'text-red-700' }}">
                                <span class="h-2 w-2 rounded-full {{ $central->activo ? 'bg-green-500' : 'bg-red-500' }}" aria-hidden="true"></span>
                                {{ $central->activo ? 'Activa' : 'Inactiva' }}
                            </span>
                            @if ($centralSelected)
                                <span class="inline-flex items-center gap-1 text-cyan-700"><i data-purchase-icon="check" class="h-3 w-3" aria-hidden="true"></i>Seleccionada</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </nav>
            <button type="button" data-purchase-direction="1" aria-label="Central siguiente" title="Central siguiente"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-gray-200 bg-white text-blue-900 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                <i data-purchase-icon="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
            </button>
        </div>
    @else
        <p class="text-sm text-gray-500">No hay centrales de mezclas registradas.</p>
    @endif

    @if ($section === 'minimum-stock')
        @include('admin.solicitudes._type-selector', [
            'selectedType' => $stockType ?? 'todas',
            'canViewNutrition' => true,
            'canViewOncology' => true,
            'typeSelectorLabel' => 'Tipo de producto',
            'typeSelectorLabels' => ['oncologicos' => 'Oncologicos'],
            'typeSelectorRoute' => 'admin.purchases.minimum-stock',
            'typeSelectorQuery' => array_filter([
                'laboratory_id' => $selectedLaboratory?->id,
                'view' => $stockViewQuery,
            ]),
        ])
    @endif

    <nav class="mt-3 flex flex-wrap items-start gap-2" aria-label="Filtros de compras">
        <div class="flex min-w-0 flex-wrap gap-2 sm:flex-1">
            @if ($section === 'minimum-stock')
                @foreach (['stock' => 'Stock minimo', 'automated' => 'OC Automatizadas', 'history' => 'OC Automatizadas Historial'] as $key => $label)
                    <a href="{{ route('admin.purchases.minimum-stock', array_filter(['laboratory_id' => $selectedLaboratory?->id, 'view' => $key === 'stock' ? null : $key]) + $stockTypeQuery) }}"
                        @if (($stockView ?? 'stock') === $key) aria-current="page" @endif
                        class="inline-flex min-h-9 max-w-full items-center justify-center rounded px-4 py-2 text-center text-sm font-semibold text-white transition {{ ($stockView ?? 'stock') === $key ? 'bg-blue-900 hover:bg-blue-800' : 'bg-teal-600 hover:bg-teal-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            @else
            @foreach ($purchaseTabs as $key => $tab)
                @continue(in_array($key, ['mine', 'new', 'minimum-stock'], true))
                <a href="{{ $tab['url'] }}" @if ($section === $key) aria-current="page" @endif
                    class="inline-flex min-h-9 items-center justify-center rounded px-4 py-2 text-sm font-semibold text-white transition {{ $section === $key ? 'bg-blue-900 hover:bg-blue-800' : 'bg-teal-600 hover:bg-teal-700' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
            @endif
        </div>
        @isset($purchaseTabs['new'])
            <a href="{{ $purchaseTabs['new']['url'] }}" data-purchase-order-popup aria-haspopup="dialog" @if ($section === 'new') aria-current="page" @endif
                class="ml-auto inline-flex min-h-9 shrink-0 items-center justify-center rounded bg-yellow-400 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-yellow-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600 {{ $section === 'new' ? 'ring-2 ring-yellow-600' : '' }}">
                {{ $purchaseTabs['new']['label'] }}
            </a>
        @endisset
    </nav>
</section>
