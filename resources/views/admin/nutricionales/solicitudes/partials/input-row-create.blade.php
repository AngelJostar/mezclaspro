@php
    $fieldName = 'i_' . $input->input_id . '_' . $input->unidad;
    $oldValue = old($fieldName);

    $presentations = $input->presentations_disponibles ?? collect();
    $presentation = null;

    if ($input->presentation_precargada_id ?? null) {
        $presentation = $presentations->firstWhere('id', $input->presentation_precargada_id);
    }

    if (!$presentation && $presentations->count() > 0) {
        $presentation = $presentations->first();
    }

    $remainder = $presentation?->remanente_proximo;
    $stock = $presentation && $presentation->stocks->count() > 0
        ? $presentation->stocks->first()
        : $remainder?->nutritionStock;

    $loteValue = old('l_' . $input->input_id, $stock->lote ?? '');

    $caducidadValue = old(
        'c_' . $input->input_id,
        $stock && $stock->caducidad ? \Carbon\Carbon::parse($stock->caducidad)->format('Y-m-d') : '',
    );
@endphp

<div>
    <div class="mb-4 flex flex-col gap-2 w-full border rounded-lg p-3 bg-gray-50">
        <div class="flex flex-col lg:flex-row lg:items-baseline gap-2 w-full">
            <x-label class="mb-2 whitespace-nowrap font-bold">
                {{ $input->description }}:
            </x-label>

            <div class="flex w-full min-w-0">
                <x-input-solicitud type="number" class="w-full" value="{{ $oldValue }}" name="{{ $fieldName }}"
                    id="{{ $fieldName }}" step="0.0001" placeholder="" />

                <span
                    @if ($input->category_id == 4) data-original-unidad="{{ $input->unidad }}" class="unidad-span-electrolitos"
                    @elseif(in_array($input->category_id, [1, 2, 3]))
                        data-original-unidad="{{ $input->unidad }}" class="unidad-span" @endif>
                    {{ $input->unidad }}
                </span>
            </div>
        </div>

        @hasanyrole('Admin|Super Admin')
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Presentación activa
                    </label>
                    <input type="text" class="w-full rounded border-gray-300 bg-gray-100"
                        value="{{ $presentation?->denominacion_comercial ?? 'Sin presentación activa' }}" readonly>
                    @if (($presentation?->remanente_disponible_ml ?? 0) > 0)
                        <p class="mt-1 text-xs font-semibold text-emerald-700">
                            Usar primero remanente: {{ number_format((float) $presentation->remanente_disponible_ml, 2) }} mL
                            @if ($remainder?->usable_until)
                                (vigente hasta {{ $remainder->usable_until->format('d/m/Y H:i') }})
                            @endif
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Lote
                    </label>
                    <input type="text" name="l_{{ $input->input_id }}" class="w-full rounded border-gray-300"
                        value="{{ $loteValue }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Caducidad
                    </label>
                    <input type="date" name="c_{{ $input->input_id }}" class="w-full rounded border-gray-300"
                        value="{{ $caducidadValue }}">
                </div>
            </div>
        @else
            <input type="hidden" name="l_{{ $input->input_id }}" value="{{ $loteValue }}">
            <input type="hidden" name="c_{{ $input->input_id }}" value="{{ $caducidadValue }}">
        @endhasanyrole
    </div>
</div>
