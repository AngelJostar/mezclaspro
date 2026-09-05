<x-admin-layout>
    @php
        $canManageWarehouse = auth()->user()->can('oncologicos_laboratory_edit');
        $canReceive = $canManageWarehouse;
        $isRequested = $supplyRequest->status === 'requested';
        $isSupplyReady = in_array($supplyRequest->status, ['approved', 'partially_approved']);
        $isSupplied = $supplyRequest->status === 'supplied';
        $canAct = ($isRequested || $isSupplyReady) ? $canManageWarehouse : ($isSupplied && $canReceive);
        // Use the explicit route key: this view is also used after redirects and
        // must not depend on implicit model parameter resolution.
        $action = null;
        if ($canAct) {
            $requestRouteKey = ['productionSupplyRequest' => $supplyRequest->getKey()];
            $action = $isRequested
                ? route('admin.production-supplies.approve', $requestRouteKey)
                : ($isSupplied
                    ? route('admin.production-supplies.receive', $requestRouteKey)
                    : route('admin.production-supplies.supply', $requestRouteKey));
        }
    @endphp
    <div class="flex items-start justify-between"><div><a class="text-sm text-blue-700" href="{{ route('admin.production-supplies.index') }}">← Solicitudes</a><h1 class="mt-2 text-2xl font-semibold">{{ $supplyRequest->folio }}</h1><p class="text-sm text-slate-500">Producción · {{ $supplyRequest->warehouse?->laboratory?->nombre ?: 'Central no disponible' }} · {{ $supplyRequest->warehouse?->name ?: 'Almacén no disponible' }}</p></div><span class="rounded bg-slate-100 px-3 py-2 text-sm font-semibold">{{ $supplyRequest->statusLabel() }}</span></div>
    @if(session('success'))<div class="mt-4 rounded bg-green-50 p-3 text-green-800">{{ session('success') }}</div>@endif
    <div class="mt-5 rounded border bg-white p-5"><p><b>Solicitó:</b> {{ trim(($supplyRequest->requester?->name ?? '').' '.($supplyRequest->requester?->lastname ?? '')) ?: 'Usuario no disponible' }} · {{ $supplyRequest->requested_at?->format('d/m/Y H:i') ?: '—' }}</p><p class="mt-2"><b>Observaciones:</b> {{ $supplyRequest->observations ?: '—' }}</p><div class="mt-4 grid gap-3 border-t pt-4 text-sm md:grid-cols-3"><div><b>Aprobó</b><p>{{ $supplyRequest->approver ? trim($supplyRequest->approver->name.' '.$supplyRequest->approver->lastname) : 'Pendiente' }}</p></div><div><b>Surtió</b><p>{{ $supplyRequest->supplier ? trim($supplyRequest->supplier->name.' '.$supplyRequest->supplier->lastname) : 'Pendiente' }}</p></div><div><b>Recibió</b><p>{{ $supplyRequest->receiver ? trim($supplyRequest->receiver->name.' '.$supplyRequest->receiver->lastname) : 'Pendiente' }}</p></div></div></div>
    @if($canAct)<form id="supply-request-action" method="POST" action="{{ $action }}">@csrf @method('PATCH')@endif
    <div class="mt-4 overflow-x-auto rounded border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-3">Insumo / lote</th><th class="p-3">Solicitada</th><th class="p-3">Aprobada</th><th class="p-3">Surtida</th><th class="p-3">Recibida</th><th class="p-3">Disponible</th></tr></thead><tbody>
        @foreach($supplyRequest->lines as $line)
            @php($stockLot = $line->consumableLot ?: $line->supply)
            <tr class="border-t"><td class="p-3 font-medium">@if($line->consumableLot){{ $line->consumableLot->item?->name }} · {{ $line->consumableLot->presentation }}<br><span class="text-xs text-slate-500">Lote {{ $line->consumableLot->lot ?: '—' }}</span>@else{{ $line->supply?->diluent?->denominacion_generica }} · {{ $line->supply?->presentacion }}<br><span class="text-xs text-slate-500">Lote {{ $line->supply?->lote ?: '—' }}</span>@endif</td><td class="p-3">{{ $line->requested_quantity }}</td><td class="p-3">@if($isRequested && $canManageWarehouse)<input name="lines[{{ $line->id }}][approved_quantity]" type="number" min="0" step="0.01" value="{{ $line->requested_quantity }}" class="w-24 rounded border-slate-300">@else{{ $line->approved_quantity ?? '—' }}@endif</td><td class="p-3">@if($isSupplyReady && $canManageWarehouse)<input name="lines[{{ $line->id }}][supplied_quantity]" type="number" min="0" step="0.01" value="{{ $line->approved_quantity }}" class="w-24 rounded border-slate-300">@else{{ $line->supplied_quantity ?? '—' }}@endif</td><td class="p-3">@if($isSupplied && $canReceive)<div class="flex items-center gap-2"><input name="lines[{{ $line->id }}][received_quantity]" type="number" min="0" step="0.01" value="{{ $line->supplied_quantity }}" class="w-24 rounded border-slate-300">@if($loop->first)<button type="submit" class="rounded bg-green-700 px-3 py-2 text-xs font-semibold text-white">Confirmar recepción</button>@endif</div>@else{{ $line->received_quantity ?? '—' }}@endif</td><td class="p-3">{{ $stockLot?->stock_actual ?? '—' }}</td></tr>
        @endforeach
    </tbody></table></div>
    @if($canAct)<div class="flex flex-wrap gap-2 p-4">@if($isRequested)<input name="resolution_notes" class="min-w-56 flex-1 rounded border-slate-300" placeholder="Notas de aprobación"><button class="rounded bg-blue-700 px-4 py-2 text-white">Aprobar</button>@elseif($isSupplyReady)<button class="rounded bg-amber-600 px-4 py-2 text-white">Registrar surtido y salida</button>@endif</div></form>@endif
    @if($isRequested && $canManageWarehouse)<form class="flex gap-2 px-4 pb-4" method="POST" action="{{ route('admin.production-supplies.reject', $supplyRequest) }}">@csrf @method('PATCH')<input required name="resolution_notes" class="min-w-56 flex-1 rounded border-slate-300" placeholder="Motivo de rechazo"><button class="rounded bg-red-600 px-4 py-2 text-white">Rechazar</button></form>@endif
</x-admin-layout>
