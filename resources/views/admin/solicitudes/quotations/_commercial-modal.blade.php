<dialog class="quote-wizard" data-quote-wizard aria-labelledby="quote-wizard-title"
    data-options-url="{{ route('admin.solicitudes.cotizacion.options') }}"
    data-preview-url="{{ route('admin.solicitudes.cotizacion.preview') }}"
    data-store-url="{{ route('admin.solicitudes.cotizacion.store') }}">
    <form novalidate>
        @csrf
        <header class="qw-header">
            <div><h2 id="quote-wizard-title">Nueva cotizacion</h2><p data-qw-step-label>Categoria de medicamentos</p></div>
            @if (!$isSalesperson)
                <label class="qw-field qw-header-seller" data-qw-seller hidden><span>Vendedor</span><select name="seller_id" disabled><option value="">Sin asignar</option>
                    @foreach ($sellers as $seller)<option value="{{ $seller->id }}">{{ trim($seller->name.' '.$seller->lastname) }}</option>@endforeach
                </select></label>
            @endif
            <span class="qw-category-badge" data-qw-badge hidden></span>
            <button type="button" class="quotation-icon-button" data-qw-close aria-label="Cerrar nueva cotizacion" title="Cerrar"><i data-qw-icon="x"></i></button>
        </header>
        <div class="qw-body">
            <div data-qw-error role="alert" tabindex="-1" hidden></div>
            <fieldset data-qw-step="0" class="qw-categories">
                <legend class="sr-only">Categoria de medicamentos</legend>
                @foreach (['nutricionales' => ['Nutricionales', 'mL', 'droplets'], 'oncologicos' => ['Oncologicos', 'mg', 'flask-conical'], 'antibioticos' => ['Antibioticos', 'mg', 'pill']] as $key => $category)
                    <label class="qw-category">
                        <input type="radio" name="category" value="{{ $key }}" @disabled(!in_array($key, $createTypes))>
                        <span><i data-qw-icon="{{ $category[2] }}"></i><strong>{{ $category[0] }}</strong><small>{{ $category[1] }}</small></span>
                    </label>
                @endforeach
            </fieldset>
            <fieldset data-qw-step="1" hidden disabled>
                <div class="qw-hospital-row">
                    <label class="qw-field"><span>Institucion *</span><select name="institution_id" required>
                        <option value="">Seleccionar institucion</option>
                        @foreach ($institutions as $institution)<option value="{{ $institution->id }}">{{ $institution->nombre }}</option>@endforeach
                    </select></label>
                    <label class="qw-field"><span>Hospital *</span><select name="hospital_id" required>
                        <option value="">Seleccionar hospital</option>
                        @foreach ($hospitals as $hospital)<option value="{{ $hospital->id }}" data-institutions="{{ json_encode($hospital->instituciones->modelKeys()) }}">{{ $hospital->name }}</option>@endforeach
                    </select></label>
                    <label class="qw-switch-field"><span>Sin relacion comercial</span><span class="qw-toggle"><input type="checkbox" name="no_commercial_relationship" role="switch"><span aria-hidden="true"></span><small data-qw-commercial-label>No</small></span></label>
                    <div class="qw-field"><span>Cobro <i data-qw-lock data-qw-icon="lock-keyhole" aria-hidden="true"></i></span>
                        <div class="qw-billing" role="group" aria-label="Modalidad de cobro">
                            <label><input type="radio" name="billing_mode" value="unit" checked disabled><span data-qw-unit-label>Por mg</span></label>
                            <label><input type="radio" name="billing_mode" value="frasco" disabled><span>Por frasco</span></label>
                        </div>
                    </div>
                </div>
                <div class="qw-list" role="status"><i data-qw-icon="file-text"></i><div><strong data-qw-list-name>Sin lista seleccionada</strong><p data-qw-list-status></p></div></div>
                <section class="qw-medications">
                    <div data-qw-mixtures></div>
                    <div class="qw-mixture-actions">
                        <button type="button" class="quotation-button quotation-outline qw-add-mixture" data-qw-add-mixture disabled><i data-qw-icon="plus"></i>Agregar mezcla</button>
                        <div class="qw-total"><span>Gran Total</span><strong data-qw-estimate>$0.00 MXN</strong></div>
                    </div>
                </section>
                <template data-qw-mixture-template>
                    <section class="qw-mixture" data-qw-mixture>
                    <header class="qw-mixture-header"><h3 data-qw-mixture-title>Mezcla 1</h3><button type="button" class="quotation-icon-button" data-qw-remove-mixture title="Eliminar mezcla" aria-label="Eliminar mezcla"><i data-qw-icon="trash-2"></i></button></header>
                    <div class="qw-table-scroll" tabindex="0" role="region" aria-label="Medicamentos seleccionados">
                        <table class="qw-requirement-table" data-disable-column-filters>
                            <caption>Requerimiento</caption>
                            <thead><tr><th scope="col">Medicamento</th><th scope="col" data-qw-requirement-heading>Concentraci&oacute;n solicitada</th><th aria-hidden="true"></th><th aria-hidden="true"></th><th aria-hidden="true"></th></tr></thead>
                            <tbody><tr>
                                <td><input type="text" class="qw-requirement-medicine" data-qw-requirement-medicine maxlength="255" placeholder="Medicamento" autocomplete="off"></td>
                                <td><div class="qw-requirement-quantity"><input type="number" data-qw-requirement-concentration min="0.0001" max="1000000" step="0.0001" inputmode="decimal"><span data-qw-requirement-unit>mg</span></div></td>
                                <td></td><td></td><td></td>
                            </tr></tbody>
                        </table>
                        <table class="qw-table" data-disable-column-filters>
                            <thead><tr><th scope="col">Desglose de medicamentos</th><th scope="col" data-qw-quantity-heading>Concentraci&oacute;n disponible</th><th scope="col">Precio unitario</th><th scope="col">Importe</th><th scope="col"><span class="sr-only">Accion</span></th></tr></thead>
                            <tbody data-qw-items></tbody>
                            <tbody>
                                <tr class="qw-entry-row" data-qw-entry>
                                    <td>
                                        <div class="qw-medication-search">
                                            <button type="button" class="quotation-button quotation-outline" data-qw-add disabled><i data-qw-icon="plus"></i>Agregar medicamento</button>
                                            <div class="qw-autocomplete" data-qw-autocomplete>
                                                <label class="qw-search"><i data-qw-icon="search"></i><input type="search" data-qw-search role="combobox" aria-label="Buscar medicamento" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="qw-medication-results" aria-expanded="false" placeholder="Buscar medicamento" autocomplete="off" disabled></label>
                                                <div class="qw-results" data-qw-results hidden>
                                                    <div data-qw-result-list role="listbox" aria-label="Medicamentos disponibles"></div>
                                                    <p class="qw-result-status" data-qw-result-status role="status" hidden></p>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td></td><td></td><td></td><td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p data-qw-empty class="sr-only">Sin medicamentos agregados.</p>
                    <div class="qw-mixture-total"><span>Total de la mezcla</span><strong data-qw-mixture-total>$0.00 MXN</strong></div>
                    </section>
                </template>
                <section class="qw-extra qw-patient" aria-labelledby="qw-patient-heading">
                    <h3 id="qw-patient-heading">Datos del paciente</h3>
                    <div class="qw-general">
                        <label class="qw-field"><span>Nombre (opcional)</span><input name="patient_name" maxlength="255" autocomplete="off"></label>
                        <label class="qw-field"><span>Apellido paterno (opcional)</span><input name="patient_paternal_surname" maxlength="100" autocomplete="off"></label>
                        <label class="qw-field"><span>Apellido materno (opcional)</span><input name="patient_maternal_surname" maxlength="100" autocomplete="off"></label>
                        <label class="qw-field"><span>ID de la plataforma (opcional)</span><input name="patient_platform_id" maxlength="100" autocomplete="off"></label>
                        <label class="qw-field qw-patient-notes"><span>Observaciones</span><textarea name="observations" maxlength="500" rows="2"></textarea></label>
                    </div>
                </section>
            </fieldset>
            <section data-qw-step="2" hidden>
                @php $issuer = App\Support\QuotationDocument::issuer(); @endphp
                <article class="qw-document" aria-label="Cotizaci&oacute;n">
                    <div class="qw-document-brand">@include('admin.solicitudes.quotations._brand', ['issuer' => $issuer])</div>
                    <div class="qw-document-recipient">
                        <div><p class="qw-document-label">DIRIGIDA A</p><strong data-qw-recipient></strong></div>
                        <div class="qw-document-date"><time data-qw-date></time><p>Moneda: pesos mexicanos (MXN)</p><p data-qw-folio hidden></p></div>
                    </div>
                    <p class="qw-document-intro">Por medio de la presente, ponemos a su consideraci&oacute;n la siguiente cotizaci&oacute;n:</p>
                    <div class="qw-table-scroll" tabindex="0" role="region" aria-label="Revision de importes">
                        <table class="qw-document-table" data-disable-column-filters>
                            <caption class="sr-only">Conceptos cotizados</caption>
                            <thead><tr><th scope="col">Cantidad</th><th scope="col">Unidad</th><th scope="col">Descripci&oacute;n</th><th scope="col">Precio unitario</th><th scope="col">Importe</th></tr></thead>
                            <tbody data-qw-review-lines></tbody>
                        </table>
                    </div>
                    <dl class="qw-document-tax" data-qw-tax-summary hidden><div><dt>Subtotal</dt><dd data-qw-subtotal></dd></div><div><dt>IVA</dt><dd data-qw-tax></dd></div></dl>
                    <div class="qw-document-total"><strong>TOTAL COTIZADO</strong><strong data-qw-total></strong></div>
                    <p class="qw-document-words" data-qw-total-words hidden></p>
                    <section class="qw-document-considerations">
                        <h4>CONSIDERACIONES</h4>
                        <div data-qw-considerations></div>
                        <p data-qw-observations hidden></p>
                        <p>Vigencia y condiciones de pago y entrega: por confirmar.</p>
                    </section>
                    <div class="qw-document-signature"><p>Atentamente,</p><strong>{{ $issuer['brand'] }}</strong></div>
                </article>
                <details class="qw-document-context"><summary>Datos de la cotizaci&oacute;n</summary><dl class="qw-review-meta" data-qw-review-meta></dl></details>
            </section>
        </div>
        <footer class="qw-footer">
            <button type="button" class="quotation-button quotation-outline" data-qw-back>Cancelar</button>
            <span role="status" data-qw-status></span>
            <button type="button" class="quotation-button quotation-outline" data-qw-save hidden>Guardar borrador</button>
            <button type="submit" class="quotation-button quotation-primary" data-qw-next disabled>Continuar</button>
        </footer>
    </form>
</dialog>
