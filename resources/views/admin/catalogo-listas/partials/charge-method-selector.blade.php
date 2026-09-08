@php
    $unitOptionValue = $unitOptionValue ?? 'mg';
    $unitOptionLabel = $unitOptionLabel ?? 'Miligramo';
    $unitOptionTitle = mb_strtolower((string) $unitOptionLabel);
    $initialChargeBy = strtolower((string) ($initialChargeBy ?? 'frasco'));
@endphp

<div class="grid min-w-40 grid-cols-2" role="radiogroup"
    aria-label="Método de cobro para {{ $productName }} - {{ $presentationName }}">
    <label class="flex h-8 cursor-pointer items-center justify-center border-r border-gray-200"
        title="Cobrar esta presentación por frasco">
        <input type="radio" name="{{ $fieldName }}[charge_by]" value="frasco"
            data-charge-by-option="frasco"
            class="charge-method-input peer sr-only" @checked($initialChargeBy === 'frasco')>
        <span class="inline-flex h-6 w-6 items-center justify-center rounded border border-gray-300 bg-white text-transparent transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus:ring-2 peer-focus:ring-emerald-200">
            <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
        </span>
        <span class="sr-only">Cobrar por frasco</span>
    </label>

    <label class="flex h-8 cursor-pointer items-center justify-center"
        title="Cobrar esta presentación por {{ $unitOptionTitle }}">
        <input type="radio" name="{{ $fieldName }}[charge_by]" value="{{ $unitOptionValue }}"
            data-charge-by-option="{{ $unitOptionValue }}"
            class="charge-method-input peer sr-only" @checked($initialChargeBy === $unitOptionValue)>
        <span class="inline-flex h-6 w-6 items-center justify-center rounded border border-gray-300 bg-white text-transparent transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus:ring-2 peer-focus:ring-emerald-200">
            <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
        </span>
        <span class="sr-only">Cobrar por {{ $unitOptionTitle }}</span>
    </label>
</div>
