@php($selection = \App\Services\Clinical\NutritionManual::selection($selectedNpt ?? ''))
<div class="w-full" data-npt-manual-selector>
    <div class="flex items-center gap-2">
        <x-label for="npt-select">NPT:{{ empty($readonlyNpt) ? '*' : '' }}</x-label>
        <x-select class="w-full" name="npt" id="npt-select" required data-clinical-required
            aria-describedby="npt-manual-description" :disabled="!empty($readonlyNpt)">
            <option value="" disabled @selected(!$selection)>Seleccionar NPT</option>
            @foreach (\App\Services\Clinical\NutritionManual::MODES as $mode => $manual)
                <option value="{{ $mode }}" data-manual-label="{{ $manual['label'] }}" @selected(($selectedNpt ?? '') === $mode)>{{ $manual['option'] }}</option>
            @endforeach
        </x-select>
    </div>
    <p id="npt-manual-description" class="mt-2 text-xs text-gray-500" aria-live="polite" data-npt-manual-description>{{ $selection ? 'Manual de soporte: '.$selection['label'].'.' : 'Selecciona NPT para definir el manual de soporte médico.' }}</p>
    @error('npt')<div class="text-red-500 text-sm">{{ $message }}</div>@enderror
</div>
