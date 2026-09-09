<div class="consumable-presentation" data-consumable-presentation>
    @foreach ([
        'presentation' => ['Presentaci&oacute;n', 'Ej. Caja con 100 piezas'],
        'commercial_name' => ['Nombre comercial', 'Escribe el nombre comercial'],
        'manufacturer' => ['Fabricante', 'Escribe el fabricante'],
    ] as $name => [$label, $placeholder])
        <div class="diluent-field">
            <label for="consumable-presentation-0-{{ $name }}">{!! $label !!}</label>
            <input type="text" id="consumable-presentation-0-{{ $name }}" name="presentations[0][{{ $name }}]"
                data-presentation-field="{{ $name }}" maxlength="255" placeholder="{{ $placeholder }}"
                @required($name === 'presentation') aria-describedby="consumable-presentation-0-{{ $name }}-error">
            <p id="consumable-presentation-0-{{ $name }}-error" class="diluent-field-error" data-field-error="presentations.0.{{ $name }}" hidden></p>
        </div>
    @endforeach
    <button type="button" class="consumable-remove" data-remove-presentation aria-label="Eliminar presentaci&oacute;n" title="Eliminar presentaci&oacute;n">
        <i data-consumable-icon="trash-2" aria-hidden="true"></i>
    </button>
</div>
