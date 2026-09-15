@php
    $adjustment = $adjustmentTarget?->currentAdjustment();
    $isAdjustedApproval = $adjustment?->status === 'approved' && $approvalStateLabel === 'Aprobada';
    $isAdjustmentPending = $adjustment?->isPending() && ! in_array($estado, ['cancelada', 'no_aprobada'], true);
    if ($isAdjustmentPending) {
        $approvalUrl = route('admin.solicitudes.ajustes.show', ['adjustment' => $adjustment, 'approval_popup' => 1, 'decision' => 1]);
    }
@endphp
<td class="px-2 py-2 text-center">
    @if ($approvalUrl && auth()->user()?->hasAnyRole(['Admin', 'Super Admin']))
        <a href="{{ $approvalUrl }}" data-approval-popup="approval-{{ $adjustmentTarget?->getTable() }}-{{ $adjustmentTarget?->id }}"
            class="inline-flex items-center justify-center rounded-full bg-amber-400 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500">Aprobar</a>
    @elseif ($isAdjustedApproval)
        <a href="{{ route('admin.solicitudes.ajustes.show', ['adjustment' => $adjustment, 'approval_popup' => 1]) }}"
            data-approval-popup="approved-adjustment-{{ $adjustment->id }}"
            class="inline-flex items-center justify-center rounded-full bg-green-600 px-3 py-2 text-xs font-semibold text-white">Aprobada</a>
    @else
        <button type="button" disabled @class([
            'inline-flex cursor-not-allowed items-center justify-center rounded-full px-3 py-2 text-xs font-semibold',
            'bg-green-600 text-white' => $approvalStateLabel === 'Aprobada',
            'bg-red-600 text-white' => $approvalStateLabel === 'Rechazada',
            'bg-gray-300 text-gray-500' => ! in_array($approvalStateLabel, ['Aprobada', 'Rechazada'], true),
        ])>{{ $isAdjustmentPending ? 'Pendiente' : $approvalStateLabel }}</button>
    @endif
</td>
<td class="px-2 py-2 text-center">
    @include('admin.solicitudes._adjustment-button', ['adjustment' => $adjustment])
</td>
