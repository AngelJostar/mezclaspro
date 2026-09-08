<dialog class="workflow-modal" data-workflow-modal aria-labelledby="workflow-modal-title">
    <div class="workflow-modal-header">
        <h2 id="workflow-modal-title">Aprobaci&oacute;n de mezcla</h2>
        <button type="button" data-workflow-modal-close title="Cerrar ventana" aria-label="Cerrar ventana">
            <i data-workflow-icon="x" aria-hidden="true"></i>
        </button>
    </div>
    <div class="workflow-modal-body">
        <iframe data-workflow-frame title="Formato de mezcla" src="about:blank"></iframe>
        <div class="workflow-modal-loading" data-workflow-loading role="status" hidden>Cargando formato...</div>
        <div class="workflow-modal-error" data-workflow-error role="alert" hidden>
            <span>No se pudo cargar el formato.</span>
            <button type="button" data-workflow-retry>Reintentar</button>
        </div>
    </div>
</dialog>
