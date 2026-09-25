<dialog class="quotation-authorize-dialog" data-quotation-authorize-dialog
    aria-labelledby="quotation-authorize-title" aria-describedby="quotation-authorize-description">
    <header class="quotation-authorize-header">
        <div class="quotation-authorize-symbol" aria-hidden="true"><i data-quotation-authorize-icon="file-check-2"></i></div>
        <button type="button" class="quotation-authorize-close" data-authorize-cancel aria-label="Cerrar autorizaci&oacute;n" title="Cerrar">
            <i data-quotation-authorize-icon="x" aria-hidden="true"></i>
        </button>
        <h2 id="quotation-authorize-title">Autorizar cotizaci&oacute;n</h2>
        <p id="quotation-authorize-description">Revisa los datos antes de confirmar.</p>
    </header>
    <div class="quotation-authorize-body">
        <dl class="quotation-authorize-details">
            <div><dt>Folio</dt><dd data-authorize-folio></dd></div>
            <div><dt>Fecha</dt><dd data-authorize-date></dd></div>
            <div class="quotation-authorize-hospital"><dt>Hospital</dt><dd data-authorize-hospital></dd></div>
        </dl>
        <div class="quotation-authorize-amount">
            <p>Importe a autorizar</p>
            <div><strong data-authorize-amount></strong><span>MXN</span></div>
        </div>
        <p class="quotation-authorize-notice"><i data-quotation-authorize-icon="info" aria-hidden="true"></i>La cotizaci&oacute;n cambiar&aacute; a Autorizada.</p>
    </div>
    <footer class="quotation-authorize-footer">
        <button type="button" class="quotation-authorize-button quotation-authorize-cancel" data-authorize-cancel autofocus>Cancelar</button>
        <button type="button" class="quotation-authorize-button quotation-authorize-confirm" data-authorize-confirm>
            <i data-quotation-authorize-icon="check" aria-hidden="true"></i><span data-authorize-label>Autorizar cotizaci&oacute;n</span>
        </button>
    </footer>
</dialog>
