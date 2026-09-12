<form method="POST" action="{{ route('admin.consumables.catalog.store') }}" data-new-consumable-form>
    @csrf
    <div class="diluent-modal-body">
        <div class="diluent-form-errors" data-consumable-errors role="alert" tabindex="-1" hidden></div>
        <fieldset data-consumable-fields>
            <legend>Datos del consumible</legend>
            <div class="consumable-details-grid">
                <div class="diluent-field">
                    <label for="consumable-name">Denominaci&oacute;n gen&eacute;rica</label>
                    <input id="consumable-name" name="name" type="text" required maxlength="255"
                        placeholder="Ej. Jeringa desechable" aria-describedby="consumable-name-error">
                    <p id="consumable-name-error" class="diluent-field-error" data-field-error="name" hidden></p>
                </div>
                <div class="diluent-field">
                    <label for="consumable-unit">Unidad</label>
                    <input id="consumable-unit" type="text" value="Pieza" readonly aria-readonly="true">
                    <input name="unit" type="hidden" value="pieza">
                </div>
            </div>
            <section class="consumable-presentations" aria-labelledby="consumable-presentations-title">
                <div class="consumable-presentations-header">
                    <div>
                        <h3 id="consumable-presentations-title">Presentaciones</h3>
                        <p>Agrega las presentaciones comerciales de este consumible.</p>
                    </div>
                    <button type="button" class="consumable-add" data-add-presentation>
                        <i data-consumable-icon="plus" aria-hidden="true"></i> Agregar presentaci&oacute;n
                    </button>
                </div>
                <p class="diluent-field-error" data-field-error="presentations" hidden></p>
                <div class="consumable-presentation-list" data-consumable-presentations>
                    @include('admin.catalogo-listas.partials.consumable-presentation-fields')
                </div>
                <template data-consumable-presentation-template>
                    @include('admin.catalogo-listas.partials.consumable-presentation-fields')
                </template>
                <p class="consumable-inventory-note"><i data-consumable-icon="info" aria-hidden="true"></i> Los lotes se administran desde almacenes.</p>
            </section>
        </fieldset>
    </div>
    <footer class="diluent-modal-footer consumable-modal-footer">
        <div>
            <button type="button" class="diluent-cancel" data-close-consumable>Cancelar</button>
            <button type="submit" class="diluent-save" data-save-consumable>
                <i data-consumable-icon="check" aria-hidden="true"></i><span>Guardar consumible</span>
            </button>
        </div>
    </footer>
</form>
