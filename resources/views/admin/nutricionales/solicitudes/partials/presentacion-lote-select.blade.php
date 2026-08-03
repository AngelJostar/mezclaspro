<div class="flex w-[18%]">
    <x-label class="mb-2 whitespace-nowrap font-bold">Presentación:</x-label>

    <select name="p_{{ $input->input_id }}"
        id="p_{{ $input->input_id }}"
        data-input-id="{{ $input->input_id }}"
        class="presentation-select w-full rounded border-gray-300">
        <option value="">Sin presentación</option>

        @foreach (($input->presentations_disponibles ?? collect()) as $presentation)
            <option value="{{ $presentation->id }}"
                @selected((string) $row['presentationId'] === (string) $presentation->id)>
                {{ $presentation->denominacion_comercial }} — {{ $presentation->presentacion }}
            </option>
        @endforeach
    </select>
</div>

<div class="flex w-[12%]">
    <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>

    <select name="l_{{ $input->input_id }}"
        id="l_{{ $input->input_id }}"
        data-input-id="{{ $input->input_id }}"
        data-selected-lote="{{ $row['loteValue'] }}"
        class="lote-select w-full rounded border-gray-300">
        <option value="">Seleccione lote</option>
    </select>
</div>

<div class="flex w-[15%]">
    <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>

    <x-input-solicitud type="date"
        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
        id="c_{{ $input->input_id }}"
        name="c_{{ $input->input_id }}"
        value="{{ $row['caducidadValue'] }}"
        readonly />
</div>
