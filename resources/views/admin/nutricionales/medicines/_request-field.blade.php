@php
    $requestInput = $medicine->input ?? null;
    $defaultOrder = ((int) \App\Models\Nutricionales\Input::max('orden_enum')) + 1;
@endphp

<div class="md:col-span-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
    <div class="mb-4">
        <h2 class="font-semibold text-gray-800">Configuración en la solicitud nutricional</h2>
        <p class="mt-1 text-sm text-gray-600">
            El nombre del campo será el mismo que la denominación genérica. Ya no es necesario relacionar un input manualmente.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <x-label class="mb-2">Unidad</x-label>
            <x-input name="request_field[unidad]"
                value="{{ old('request_field.unidad', $requestInput?->unidad ?? 'mL') }}" class="w-full" required />
        </div>

        <div>
            <x-label class="mb-2">Aplica para</x-label>
            <x-select name="request_field[tipo_input]" class="w-full" required>
                <option value="ambos" @selected(old('request_field.tipo_input', $requestInput?->tipo_input ?? 'ambos') === 'ambos')>Adulto y pediátrico</option>
                <option value="adulto" @selected(old('request_field.tipo_input', $requestInput?->tipo_input) === 'adulto')>Adulto</option>
                <option value="niño" @selected(old('request_field.tipo_input', $requestInput?->tipo_input) === 'niño')>Pediátrico</option>
            </x-select>
        </div>

        <div>
            <x-label class="mb-2">Orden en la solicitud</x-label>
            <x-input type="number" min="0" name="request_field[orden_enum]"
                value="{{ old('request_field.orden_enum', $requestInput?->orden_enum ?? $defaultOrder) }}"
                class="w-full" required />
        </div>

        <div>
            <x-label class="mb-2">Mostrar en solicitudes</x-label>
            <x-select name="request_field[is_active]" class="w-full" required>
                <option value="1" @selected((string) old('request_field.is_active', $requestInput?->is_active ?? 1) === '1')>Sí</option>
                <option value="0" @selected((string) old('request_field.is_active', $requestInput?->is_active ?? 1) === '0')>No</option>
            </x-select>
        </div>

        <div>
            <x-label class="mb-2">Multiplicador</x-label>
            <x-input type="number" step="0.001" min="0" name="request_field[mult]"
                value="{{ old('request_field.mult', $requestInput?->mult ?? 1) }}" class="w-full" required />
        </div>

        <div>
            <x-label class="mb-2">Divisor</x-label>
            <x-input type="number" step="0.00001" min="0.00001" name="request_field[div]"
                value="{{ old('request_field.div', $requestInput?->div ?? 1) }}" class="w-full" required />
        </div>
    </div>
</div>
