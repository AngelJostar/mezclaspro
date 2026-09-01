@php
    $activeStatus = App\Support\SolicitudStatusFilter::normalize($activeStatus ?? request()->query('estado'));
    $pendingApprovalCount = $pendingApprovalCount ?? null;
    $routePendingCount = $routePendingCount ?? null;
    $deliveryPendingCount = $deliveryPendingCount ?? null;
    $baseQuery = request()->except(['estado', 'page', 'buscar']);
@endphp

<nav class="mt-3 flex max-w-full flex-wrap gap-2 pb-1" aria-label="Estado de las solicitudes">
    @foreach (App\Support\SolicitudStatusFilter::options() as $statusKey => $statusLabel)
        @php
            $statusQuery = $baseQuery;

            if ($statusKey !== App\Support\SolicitudStatusFilter::ALL) {
                $statusQuery['estado'] = $statusKey;
            }

            $statusUrl = request()->url()
                . ($statusQuery === [] ? '' : '?' . Illuminate\Support\Arr::query($statusQuery));
            $isActiveStatus = $activeStatus === $statusKey;
        @endphp

        <a href="{{ $statusUrl }}"
            @if ($isActiveStatus) aria-current="page" @endif
            @class([
                'flex h-8 shrink-0 items-center justify-center whitespace-nowrap rounded-md border px-4 text-xs font-semibold transition',
                'border-emerald-600 bg-emerald-600 text-white shadow-sm' => $isActiveStatus,
                'border-gray-200 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50' => !$isActiveStatus,
            ])>
            {{ $statusLabel }}
            @if ($statusKey === App\Support\SolicitudStatusFilter::PENDING && $pendingApprovalCount !== null)
                <span
                    class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-300 px-1.5 text-[11px] font-bold leading-none text-yellow-900 ring-1 ring-yellow-400">
                    {{ number_format((int) $pendingApprovalCount) }}
                </span>
            @endif
            @if ($statusKey === App\Support\SolicitudStatusFilter::IN_ROUTE && $routePendingCount !== null)
                <span
                    class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-300 px-1.5 text-[11px] font-bold leading-none text-yellow-900 ring-1 ring-yellow-400">
                    {{ number_format((int) $routePendingCount) }}
                </span>
            @endif
            @if ($statusKey === App\Support\SolicitudStatusFilter::DELIVERED && $deliveryPendingCount !== null)
                <span
                    class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-300 px-1.5 text-[11px] font-bold leading-none text-yellow-900 ring-1 ring-yellow-400">
                    {{ number_format((int) $deliveryPendingCount) }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
