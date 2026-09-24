<dialog class="quotation-capture" data-quotation-dialog aria-labelledby="quotation-capture-title"
    data-selected-type="{{ $selectedType }}" data-options-url="{{ route('admin.solicitudes.cotizacion.options') }}">
    <form method="POST" action="{{ route('admin.solicitudes.cotizacion.store') }}" enctype="multipart/form-data" data-quotation-form>
        @csrf
        <header class="quotation-modal-header">
            <h2 id="quotation-capture-title">Nueva cotizacion</h2>
            <label class="quotation-field quotation-type-selector"><span>Tipo de mezcla *</span><select name="category" required>
                <option value="">Seleccionar tipo de mezcla</option>
                @foreach (['oncologicos' => 'Oncologica', 'nutricionales' => 'Nutricional', 'antibioticos' => 'Antibioticos'] as $type => $label)
                    <option value="{{ $type }}" @disabled(!in_array($type === 'antibioticos' ? 'oncologicos' : $type, $createTypes))>{{ $label }}</option>
                @endforeach
            </select></label>
            <button type="button" data-quotation-close class="quotation-icon-button" title="Cerrar" aria-label="Cerrar cotizacion"><i data-quotation-icon="x"></i></button>
        </header>
        <div class="quotation-modal-body">
            <div data-quotation-errors role="alert" tabindex="-1" hidden></div>
            <section data-quotation-pending role="status" hidden><h3>Antibioticos</h3><p>Formato de cotizacion pendiente.</p></section>
            <fieldset data-quotation-content>
            <section>
                <h3><i data-quotation-icon="file-text"></i>Informacion general</h3>
                <div class="quotation-grid">
                    @include('admin.solicitudes.quotations._field', ['name' => 'scheduled_date', 'label' => 'Fecha de programacion', 'type' => 'date', 'required' => true])
                    <label class="quotation-field"><span>Hospital / Centro *</span><select name="hospital_id" required>
                        <option value="">Seleccionar hospital</option>
                        @foreach ($hospitals as $hospital)<option value="{{ $hospital->id }}" @selected((string) $filters['hospital_id'] === (string) $hospital->id || $hospitals->count() === 1)>{{ $hospital->name }}</option>@endforeach
                    </select></label>
                    <label class="quotation-field"><span>Institucion *</span><select name="institution_id" required><option value="">Seleccionar institucion</option></select></label>
                    <label class="quotation-field"><span>Lista de precios</span><input data-quotation-price-list readonly></label>
                    @if ($isSalesperson)
                        <label class="quotation-field"><span>Vendedor</span><input data-quotation-seller-name value="{{ trim(auth()->user()->name.' '.auth()->user()->lastname) }}" readonly></label>
                    @else
                        <label class="quotation-field"><span>Vendedor</span><select name="seller_id">
                            <option value="">Sin asignar</option>
                            @foreach ($sellers as $seller)<option value="{{ $seller->id }}">{{ trim($seller->name.' '.$seller->lastname) }}</option>@endforeach
                        </select></label>
                    @endif
                    @include('admin.solicitudes.quotations._field', ['name' => 'service', 'label' => 'Servicio', 'required' => true, 'max' => 100])
                    @foreach (['floor' => 'Piso', 'bed' => 'Cama', 'record_number' => 'Registro / Cedula del paciente'] as $name => $label)
                        @include('admin.solicitudes.quotations._field', ['name' => $name, 'label' => $label, 'max' => 50])
                    @endforeach
                </div>
                <p data-quotation-catalog-status role="status"></p>
            </section>
            <fieldset data-quotation-capture-fields>
                <section>
                    <h3><i data-quotation-icon="user-round"></i>Datos del paciente</h3>
                    <div class="quotation-grid quotation-patient-grid">
                        @include('admin.solicitudes.quotations._field', ['name' => 'patient_name', 'label' => 'Nombre completo del paciente', 'required' => true])
                        <label class="quotation-field"><span>Sexo<span data-oncology-required hidden> *</span></span><select name="sex"><option value="">Seleccionar sexo</option><option value="F">Femenino</option><option value="M">Masculino</option></select></label>
                        <label class="quotation-field"><span>Edad (anos)</span><input data-quotation-age readonly></label>
                        @include('admin.solicitudes.quotations._field', ['name' => 'birth_date', 'label' => 'Fecha de nacimiento', 'type' => 'date', 'required' => true])
                        @include('admin.solicitudes.quotations._field', ['name' => 'weight_kg', 'label' => 'Peso (kg)', 'type' => 'number', 'required' => true, 'max' => 500])
                        <fieldset class="quotation-grid quotation-oncology-patient" data-quotation-type="oncologicos" disabled hidden>
                            @include('admin.solicitudes.quotations._field', ['name' => 'height_cm', 'label' => 'Talla (cm)', 'type' => 'number', 'required' => true, 'max' => 300])
                            @include('admin.solicitudes.quotations._field', ['name' => 'body_surface_m2', 'label' => 'Superficie corporal (m2)', 'type' => 'number', 'max' => 10])
                        </fieldset>
                        @include('admin.solicitudes.quotations._field', ['name' => 'diagnosis', 'label' => 'Diagnostico', 'type' => 'textarea', 'wide' => true, 'max' => 255])
                    </div>
                </section>
                <fieldset data-quotation-type="oncologicos" disabled hidden>
                    <section>
                        <h3><i data-quotation-icon="table-2"></i>Tabla de medicamentos</h3>
                        <p id="quotation-medication-status" data-quotation-medication-status role="status" hidden></p>
                        <div class="quotation-medication-scroll" tabindex="0" aria-label="Medicamentos de la cotizacion">
                            <table class="quotation-medication-table" data-disable-column-filters>
                                <colgroup><col class="quotation-col-number"><col class="quotation-col-drug"><col class="quotation-col-dose"><col span="3" class="quotation-col-diluent"><col class="quotation-col-volume"><col span="2" class="quotation-col-quantity"><col span="3" class="quotation-col-delivery"><col class="quotation-col-action"></colgroup>
                                <thead>
                                    <tr><th rowspan="2" scope="col">#</th><th rowspan="2" scope="col">Medicamento*</th><th rowspan="2" scope="col">Dosis*</th><th colspan="3" scope="colgroup">Diluyente</th><th rowspan="2" scope="col">Volumen de dilucion<br>total (ml)*</th><th rowspan="2" scope="col">No. de bolos por dia*</th><th rowspan="2" scope="col">Tiempo de infusion (min)*</th><th colspan="3" scope="colgroup">Fecha de entrega*</th><th rowspan="2" scope="col">Accion</th></tr>
                                    <tr><th scope="col"><abbr title="Cloruro de sodio / Solucion salina">CS</abbr></th><th scope="col"><abbr title="Dextrosa / Solucion glucosada">DX</abbr></th><th scope="col">Otro</th><th scope="col">1</th><th scope="col">2</th><th scope="col">3</th></tr>
                                </thead><tbody data-quotation-medications></tbody>
                            </table>
                        </div>
                        <datalist id="quotation-medicines"></datalist>
                        <button type="button" class="quotation-button quotation-outline" data-quotation-add><i data-quotation-icon="plus"></i>Agregar medicamento</button>
                    </section>
                </fieldset>
                <fieldset data-quotation-type="nutricionales" disabled hidden>
                    <section>
                        <h3><i data-quotation-icon="droplets"></i>Administracion de la mezcla</h3>
                        <div class="quotation-grid">
                            <label class="quotation-field"><span>Via de administracion *</span><select name="administration_route" required><option value="Central">Central</option><option value="Periferica">Periferica</option></select></label>
                            @include('admin.solicitudes.quotations._field', ['name' => 'infusion_hours', 'label' => 'Tiempo de infusion (h)', 'type' => 'number', 'required' => true, 'max' => 168])
                            @include('admin.solicitudes.quotations._field', ['name' => 'infusion_rate', 'label' => 'Velocidad de infusion (ml/h)', 'type' => 'number', 'max' => 100000])
                            @include('admin.solicitudes.quotations._field', ['name' => 'overfill_ml', 'label' => 'Sobrellenado (ml)', 'type' => 'number', 'min' => 0, 'max' => 10000])
                            <label class="quotation-field"><span>Volumen total (ml)</span><input data-quotation-volume readonly></label>
                            <label class="quotation-field"><span>NPT *</span><select name="npt" required><option value="">Seleccionar</option><option value="ADULT">ADULTO</option><option value="INF">PEDIATRICO</option></select></label>
                        </div>
                    </section>
                    <section><h3><i data-quotation-icon="table-2"></i>Componentes de la nutricion parenteral</h3><div data-quotation-components></div></section>
                </fieldset>
                <section>
                    <h3><i data-quotation-icon="stethoscope"></i>Entrega y responsable medico</h3>
                    <div class="quotation-grid">
                        <fieldset data-quotation-type="nutricionales" disabled hidden>
                            @include('admin.solicitudes.quotations._field', ['name' => 'delivery_at', 'label' => 'Fecha y hora de entrega', 'type' => 'datetime-local', 'required' => true])
                        </fieldset>
                        <fieldset data-quotation-type="oncologicos" disabled hidden>
                            <label class="quotation-field"><span>Manera de entrega</span><select name="delivery_method"><option value="">Seleccionar</option><option>Entrega en hospital</option><option>Recoleccion en central</option></select></label>
                        </fieldset>
                        @include('admin.solicitudes.quotations._field', ['name' => 'doctor_name', 'label' => 'Nombre del medico', 'required' => true])
                        @include('admin.solicitudes.quotations._field', ['name' => 'doctor_license', 'label' => 'Cedula profesional', 'required' => true, 'max' => 100])
                        <fieldset data-quotation-type="oncologicos" class="quotation-wide" disabled hidden>
                            <label class="quotation-field quotation-upload"><span><i data-quotation-icon="upload"></i>Firma y cedula (opcional)</span><input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf"><small>JPG, PNG o PDF. Maximo 5 MB.</small></label>
                            <a data-quotation-existing-attachment hidden>Descargar adjunto actual</a>
                        </fieldset>
                        @include('admin.solicitudes.quotations._field', ['name' => 'observations', 'label' => 'Indicaciones, observaciones y vias de administracion', 'type' => 'textarea', 'wide' => true])
                    </div>
                </section>
            </fieldset>
            </fieldset>
        </div>
        <footer class="quotation-modal-footer">
            <span data-quotation-save-status role="status">* Campos obligatorios</span>
            <div><button type="button" class="quotation-button quotation-outline" data-quotation-close>Cancelar</button>
                <button type="submit" name="action" value="save" class="quotation-button quotation-outline" disabled>Guardar borrador</button>
                <button type="submit" name="action" value="send" class="quotation-button quotation-primary" disabled>Enviar a revision</button></div>
        </footer>
        <dialog class="quotation-row-picker" data-quotation-row-picker aria-labelledby="quotation-row-picker-title">
            <header><h3 id="quotation-row-picker-title"></h3><button type="button" class="quotation-icon-button" data-picker-cancel aria-label="Cerrar selector" title="Cerrar"><i data-quotation-icon="x"></i></button></header>
            <label class="quotation-field" data-picker-date-field><span>Fecha y hora de entrega</span><input type="datetime-local" data-picker-date disabled></label>
            <label class="quotation-field" data-picker-diluent-field hidden><span>Diluyente</span><select data-picker-diluent disabled></select></label>
            <footer><button type="button" class="quotation-button quotation-outline" data-picker-clear>Borrar</button><button type="button" class="quotation-button quotation-outline" data-picker-cancel>Cancelar</button><button type="button" class="quotation-button quotation-primary" data-picker-apply>Aplicar</button></footer>
        </dialog>
    </form>
</dialog>
<template id="quotation-medication-row">
    <tr>
        <td data-row-number></td>
        <td><input data-drug list="quotation-medicines" placeholder="Buscar medicamento" required aria-label="Medicamento" aria-describedby="quotation-medication-status"><input type="hidden" data-key="presentation_id"></td>
        <td><div class="quotation-unit-input"><input data-key="dose_mg" type="number" min="0.0001" max="1000000" step="any" required aria-label="Dosis en mg"><span aria-hidden="true">mg</span></div></td>
        <td class="quotation-diluent-cell"><input type="hidden" data-key="diluent_id"><input type="checkbox" data-diluent-choice="CS" aria-label="Diluyente CS" disabled></td>
        <td class="quotation-diluent-cell"><input type="checkbox" data-diluent-choice="DX" aria-label="Diluyente DX" disabled></td>
        <td class="quotation-diluent-cell"><input type="checkbox" data-diluent-choice="Otro" aria-label="Otro diluyente" disabled></td>
        <td><div class="quotation-unit-input"><input data-key="dilution_ml" type="number" min="0.0001" max="100000" step="any" required aria-label="Volumen de dilucion en ml"><span aria-hidden="true">ml</span></div></td>
        <td><input data-key="boluses_per_day" type="number" min="1" max="99" step="1" required aria-label="Bolos por dia"></td>
        <td><div class="quotation-unit-input"><input data-key="infusion_minutes" type="number" min="0.0001" max="10080" step="any" required aria-label="Tiempo de infusion en minutos"><span aria-hidden="true">min</span></div></td>
        @foreach ([1, 2, 3] as $delivery)
            <td class="quotation-delivery-cell"><input type="hidden" data-delivery><button type="button" class="quotation-icon-button quotation-delivery-button" data-delivery-button aria-label="Entrega {{ $delivery }}" title="Entrega {{ $delivery }}: sin fecha" aria-haspopup="dialog"><i data-quotation-icon="calendar-days"></i></button></td>
        @endforeach
        <td><button type="button" data-remove class="quotation-icon-button" title="Eliminar medicamento" aria-label="Eliminar medicamento"><i data-quotation-icon="trash-2"></i></button></td>
    </tr>
</template>
