@php
    $laboratories = $laboratories ?? collect();
    $laboratoryId = (string) old('laboratory_id', $selectedLaboratoryId ?? '');
    $warehouseId = (string) old('warehouse_id', $selectedWarehouseId ?? '');
    $warehousesByLaboratory = $laboratories->mapWithKeys(fn ($laboratory) => [
        (string) $laboratory->id => $laboratory->warehouses->map(fn ($warehouse) => [
            'id' => $warehouse->id, 'name' => $warehouse->name,
        ])->values(),
    ]);
    $initialWarehouses = $warehousesByLaboratory->get($laboratoryId, collect());
    $fields = [
        ['name' => 'generic_description', 'label' => 'Nombre genérico del diluyente', 'placeholder' => 'Ej. Cloruro de sodio 0.9%'],
        ['name' => 'commercial_name', 'label' => 'Nombre comercial', 'placeholder' => 'Escribe el nombre comercial'],
        ['name' => 'concentration', 'label' => 'Volumen', 'unit' => 'mL', 'min' => '0.0001', 'step' => '0.0001'],
        ['name' => 'presentation', 'label' => 'Presentación', 'placeholder' => 'Ej. Bolsa 500 mL'],
        ['name' => 'stability_hours', 'label' => 'Estabilidad reconstituido (horas)', 'placeholder' => 'Indica las horas', 'unit' => 'h', 'min' => '1', 'max' => '8760', 'step' => '1'],
        ['name' => 'manufacturer', 'label' => 'Fabricante', 'placeholder' => 'Escribe el fabricante', 'optional' => true],
    ];
@endphp
<form method="POST" action="{{ route('admin.catalogo-listas.products.store', ['category' => 'insumos']) }}" data-new-diluent-form>
    @csrf
    <script type="application/json" data-diluent-locations>@json($warehousesByLaboratory)</script>
    <div class="diluent-modal-body">
        <div class="diluent-form-errors" data-diluent-errors role="alert" tabindex="-1" hidden></div>
        <fieldset data-diluent-fields>
            <legend>Datos del diluyente</legend>
            <div class="diluent-form-grid">
                @foreach ($fields as $field)
                    <div class="diluent-field">
                        <label for="{{ $field['name'] }}">{{ $field['label'] }} @unless($field['optional'] ?? false)<span aria-hidden="true">*</span>@endunless</label>
                        <div @class(['diluent-unit-input' => isset($field['unit'])])>
                            <input id="{{ $field['name'] }}" name="{{ $field['name'] }}"
                                type="{{ isset($field['unit']) ? 'number' : 'text' }}"
                                value="{{ old($field['name'], $field['name'] === 'concentration' ? '0' : '') }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                @required(!($field['optional'] ?? false))
                                @if(isset($field['unit'])) min="{{ $field['min'] }}" step="{{ $field['step'] }}" @if(isset($field['max'])) max="{{ $field['max'] }}" @endif @else maxlength="255" @endif
                                aria-describedby="{{ $field['name'] }}-error">
                            @isset($field['unit'])<span>{{ $field['unit'] }}</span>@endisset
                        </div>
                        <p id="{{ $field['name'] }}-error" class="diluent-field-error" data-field-error="{{ $field['name'] }}" hidden></p>
                    </div>
                @endforeach
            </div>
            <section class="diluent-warehouse-section" aria-labelledby="diluent-warehouse-heading">
                <h3 id="diluent-warehouse-heading">Asignación a almacén</h3>
                <p>Selecciona la central y su subalmacén de insumos.</p>
                <div class="diluent-form-grid">
                    <div class="diluent-field">
                        <label for="supply_laboratory_id">Central <span aria-hidden="true">*</span></label>
                        <select id="supply_laboratory_id" name="laboratory_id" required @disabled($laboratories->isEmpty()) aria-describedby="laboratory_id-error">
                            <option value="">Selecciona una central</option>
                            @foreach ($laboratories as $laboratory)
                                <option value="{{ $laboratory->id }}" @selected($laboratoryId === (string) $laboratory->id)>{{ $laboratory->nombre }}</option>
                            @endforeach
                        </select>
                        <p id="laboratory_id-error" class="diluent-field-error" data-field-error="laboratory_id" hidden></p>
                    </div>
                    <div class="diluent-field">
                        <label for="supply_warehouse_id">Subalmacén de insumos <span aria-hidden="true">*</span></label>
                        <select id="supply_warehouse_id" name="warehouse_id" required @disabled($initialWarehouses->isEmpty()) aria-describedby="warehouse_id-error">
                            <option value="">Selecciona un subalmacén</option>
                            @foreach ($initialWarehouses as $warehouse)
                                <option value="{{ $warehouse['id'] }}" @selected($warehouseId === (string) $warehouse['id'])>{{ $warehouse['name'] }}</option>
                            @endforeach
                        </select>
                        <p id="warehouse_id-error" class="diluent-field-error" data-field-error="warehouse_id" hidden></p>
                    </div>
                </div>
                <p data-diluent-no-warehouse @if($initialWarehouses->isNotEmpty()) hidden @endif>No hay subalmacenes activos disponibles para esta central.</p>
            </section>
        </fieldset>
    </div>
    <footer class="diluent-modal-footer">
        <p><span aria-hidden="true">*</span> Campos obligatorios</p>
        <div>
            <button type="button" class="diluent-cancel" data-close-diluent>Cancelar</button>
            <button type="submit" class="diluent-save" data-save-diluent @disabled($initialWarehouses->isEmpty())>
                <i data-diluent-icon="check" aria-hidden="true"></i><span>Guardar diluyente</span>
            </button>
        </div>
    </footer>
</form>
