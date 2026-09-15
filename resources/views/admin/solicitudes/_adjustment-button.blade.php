@php
    $adjustmentState = $adjustment?->status ?? 'none';
    $hasVersion = in_array($adjustmentState, ['requested', 'authorized', 'approved', 'declined'], true);
    $adjustmentColors = match ($adjustmentState) {
        'requested', 'authorized' => 'bg-amber-400 text-white hover:bg-amber-500',
        'approved' => 'bg-green-600 text-white',
        'declined' => 'bg-red-100 text-red-800',
        default => 'bg-gray-200 text-gray-500',
    };
@endphp
@if ($hasVersion && ! ($readOnly ?? false))
    <a href="{{ route('admin.solicitudes.ajustes.show', ['adjustment' => $adjustment, 'approval_popup' => 1]) }}"
        data-approval-popup="adjustment-{{ $adjustment->id }}"
        class="inline-flex min-h-8 w-28 items-center justify-center whitespace-normal rounded-full px-3 py-2 text-xs font-semibold leading-tight {{ $adjustmentColors }}">
        {{ $adjustment->label }}
    </a>
@else
    <button type="button" disabled class="inline-flex min-h-8 w-28 cursor-not-allowed items-center justify-center gap-1 whitespace-normal rounded-full px-3 py-2 text-xs font-semibold leading-tight {{ $adjustmentColors }}">
        @unless ($hasVersion)<i data-adjustment-icon="lock-keyhole" class="h-3 w-3 shrink-0" aria-hidden="true"></i>@endunless
        {{ $adjustment?->label ?? 'Sin Ajustes' }}
    </button>
@endif
