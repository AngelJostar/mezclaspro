<dialog class="adjustment-proposal" data-adjustment-proposal aria-labelledby="adjustment-proposal-title">
    <form data-proposal-form>
        <header class="adjustment-proposal-header">
            <div>
                <h2 id="adjustment-proposal-title">Propuesta de ajuste de mezcla #{{ $proposalId }}</h2>
                <p>Hospital: {{ $proposalHospital }}</p>
            </div>
            <button type="button" data-proposal-close class="proposal-icon-button" title="Cerrar propuesta" aria-label="Cerrar propuesta">
                <i data-proposal-icon="x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="adjustment-proposal-body">
            <dl class="proposal-patient">
                <div><dt>Paciente</dt><dd>{{ $proposalPatient }}</dd></div>
                <div><dt>Servicio</dt><dd>{{ $proposalService }}</dd></div>
                <div><dt>M&eacute;dico</dt><dd>{{ $proposalDoctor }}</dd></div>
            </dl>
            <h3>Mezcla &middot; Propuesta de ajuste</h3>
            <div class="proposal-table-scroll" data-disable-sticky-x>
                <table class="proposal-table" data-disable-column-filters>
                    <colgroup><col class="proposal-field-column"><col class="proposal-original-column"><col class="proposal-value-column"></colgroup>
                    <thead><tr><th scope="col">Campo</th><th scope="col">Solicitud original</th><th scope="col">Ajuste Prodifem</th></tr></thead>
                    <tbody data-proposal-fields></tbody>
                </table>
            </div>
            <button type="button" data-proposal-add class="proposal-secondary" hidden>
                <i data-proposal-icon="plus" aria-hidden="true"></i> Agregar medicamento
            </button>
            <label class="proposal-reason" for="proposal-reason">Motivo del ajuste *</label>
            <textarea id="proposal-reason" name="adjustment_description" rows="3" required minlength="5" maxlength="4000"
                placeholder="Motivo y justificaci&oacute;n de los cambios propuestos">{{ old('adjustment_description') }}</textarea>
            <p data-proposal-error role="alert" class="proposal-error" hidden></p>
        </div>
        <footer class="adjustment-proposal-footer">
            <button type="button" data-proposal-close class="proposal-secondary">Cancelar</button>
            <button type="submit" class="proposal-send">Enviar propuesta al hospital</button>
        </footer>
    </form>
</dialog>
