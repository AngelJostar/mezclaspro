<x-admin-layout>
    <div class="mb-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 data-workflow-heading class="text-xl font-medium">{{ request()->boolean('decision') ? 'Aprobación de mezcla' : 'Solicitud ajustada' }} #{{ $adjustment->target_id }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <span>Versión de ajuste #{{ $adjustment->id }}</span>
                @include('admin.solicitudes._adjustment-button', ['adjustment' => $adjustment, 'readOnly' => true])
            </div>
        </div>
        <a href="{{ route('admin.solicitudes.index') }}" data-workflow-popup-close
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded border border-gray-300"
            title="Cerrar" aria-label="Cerrar"><i data-adjustment-icon="x" class="h-4 w-4" aria-hidden="true"></i></a>
    </div>
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if (session('success'))
        <p role="status" class="mb-4 text-sm text-green-700">{{ session('success') }}</p>
    @endif
    <section class="mb-5 border-b border-gray-200 pb-4">
        <h2 class="mb-1 text-sm font-semibold">Motivo del ajuste</h2>
        <p class="whitespace-pre-wrap break-words text-sm">{{ $adjustment->description }}</p>
        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-600">
            <div><dt>Solicitado</dt><dd>{{ $adjustment->created_at?->format('d/m/Y H:i') }} · Usuario #{{ $adjustment->requested_by }}</dd></div>
            @if ($adjustment->authorized_at)
                <div><dt>Respuesta del hospital</dt><dd>{{ $adjustment->authorized_at->format('d/m/Y H:i') }} · Usuario #{{ $adjustment->authorized_by }}</dd></div>
            @endif
            @if ($adjustment->approved_at)
                <div><dt>Aprobada con ajuste</dt><dd>{{ $adjustment->approved_at->format('d/m/Y H:i') }} · Usuario #{{ $adjustment->approved_by }}</dd></div>
            @endif
        </dl>
    </section>
    <section aria-labelledby="adjustment-version-title">
        <h2 id="adjustment-version-title" class="mb-3 text-base font-semibold">Nueva versión de la solicitud</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3" data-adjustment-version>
            @foreach ($adjustment->review as $field)
                <div @class(['min-w-0 border-l-4 p-3', 'border-amber-400 bg-amber-50' => $field['changed'], 'border-gray-200 bg-gray-50' => ! $field['changed']])
                    data-adjustment-changed="{{ $field['changed'] ? 'true' : 'false' }}">
                    <div class="mb-1 flex items-start justify-between gap-2 text-xs">
                        <span class="font-semibold">{{ $field['label'] }}</span>
                        @if ($field['changed'])<span class="shrink-0 font-semibold text-amber-800">Modificado</span>@endif
                    </div>
                    <p class="whitespace-pre-wrap break-words text-sm">{{ filled($field['value']) ? $field['value'] : 'Sin dato' }}</p>
                    @if ($field['changed'])
                        <p class="mt-2 whitespace-pre-wrap break-words text-xs text-gray-600">Anterior: {{ filled($field['before']) ? $field['before'] : 'Sin dato' }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    @if ($adjustment->authorized_at)
        <section class="mt-5 border-t border-gray-200 pt-4">
            <h2 class="text-sm font-semibold">{{ $adjustment->status === 'declined' ? 'Ajuste no autorizado por el hospital' : 'Respuesta del hospital' }}</h2>
            <p class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $adjustment->hospital_response ?: 'Ajustes autorizados.' }}</p>
        </section>
    @endif
    @if ($adjustment->central_response)
        <p class="mt-4 whitespace-pre-wrap break-words text-sm text-red-700">{{ $adjustment->central_response }}</p>
    @endif
    @if ($adjustment->isPending() && (int) $target->adjustment_id === (int) $adjustment->id)
        <div class="mt-5 border-t border-gray-200 pt-4">
            @if ($isHospital && $adjustment->status === 'requested')
                <form method="POST" action="{{ route('admin.solicitudes.ajustes.authorize', $adjustment) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="approval_popup" value="{{ request()->boolean('approval_popup') ? 1 : 0 }}">
                    <label class="block text-sm">Respuesta del hospital
                        <textarea name="hospital_response" rows="2" maxlength="2000" class="mt-1 w-full rounded border-gray-300">{{ old('hospital_response') }}</textarea>
                    </label>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-gray-300">Autorizo los ajustes de esta versión de la solicitud.</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded bg-amber-400 px-4 py-2 text-sm font-semibold text-gray-900">Autorizar ajustes</button>
                        <button type="submit" formaction="{{ route('admin.solicitudes.ajustes.decline', $adjustment) }}" formnovalidate class="rounded border border-red-300 px-4 py-2 text-sm text-red-700">No autorizar</button>
                    </div>
                </form>
            @elseif (! $isHospital && request()->boolean('decision'))
                <div data-workflow-approval-actions class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('admin.solicitudes.ajustes.approve', $adjustment) }}">
                        @csrf
                        <input type="hidden" name="approval_popup" value="{{ request()->boolean('approval_popup') ? 1 : 0 }}">
                        <button type="submit" @disabled($adjustment->status !== 'authorized')
                            class="rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">Aprobar mezcla</button>
                    </form>
                    <form method="POST" action="{{ route('admin.solicitudes.ajustes.cancel', $adjustment) }}">
                        @csrf
                        <input type="hidden" name="approval_popup" value="{{ request()->boolean('approval_popup') ? 1 : 0 }}">
                        <button type="submit" class="rounded border border-gray-300 px-4 py-2 text-sm">Cancelar ajuste</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('admin.solicitudes.ajustes.reject', $adjustment) }}" class="mt-4 space-y-2">
                    @csrf
                    <input type="hidden" name="approval_popup" value="{{ request()->boolean('approval_popup') ? 1 : 0 }}">
                    <label class="block text-sm">Motivo de rechazo
                        <textarea name="central_response" required minlength="5" maxlength="2000" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('central_response') }}</textarea>
                    </label>
                    <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white">Rechazar mezcla</button>
                </form>
            @endif
        </div>
    @endif
</x-admin-layout>
