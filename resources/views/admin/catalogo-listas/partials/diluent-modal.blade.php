<dialog class="diluent-modal" data-diluent-modal aria-labelledby="diluent-modal-title">
    <header class="diluent-modal-header">
        <span class="diluent-modal-symbol" aria-hidden="true"><i data-diluent-icon="syringe"></i></span>
        <div>
            <h2 id="diluent-modal-title">Nuevo diluyente</h2>
            <p>Registra el diluyente y su presentación comercial.</p>
        </div>
        <button type="button" class="diluent-modal-close" data-close-diluent aria-label="Cerrar nuevo diluyente" title="Cerrar">
            <i data-diluent-icon="x" aria-hidden="true"></i>
        </button>
    </header>
    <div class="diluent-modal-loading" data-diluent-loading role="status" hidden>Cargando...</div>
    <div class="diluent-modal-load-error" data-diluent-load-error hidden>
        <p role="alert">No se pudo cargar el formulario. Intenta de nuevo.</p>
        <button type="button" data-diluent-retry><i data-diluent-icon="rotate-cw" aria-hidden="true"></i> Reintentar</button>
    </div>
    <div data-diluent-form-host></div>
</dialog>
