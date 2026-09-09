<dialog class="diluent-modal consumable-modal" data-consumable-modal aria-labelledby="consumable-modal-title">
    <header class="diluent-modal-header">
        <span class="diluent-modal-symbol" aria-hidden="true"><i data-consumable-icon="box"></i></span>
        <div>
            <h2 id="consumable-modal-title">Nuevo consumible</h2>
            <p>Registra el insumo gen&eacute;rico y sus presentaciones.</p>
        </div>
        <button type="button" class="diluent-modal-close" data-close-consumable aria-label="Cerrar nuevo consumible" title="Cerrar">
            <i data-consumable-icon="x" aria-hidden="true"></i>
        </button>
    </header>
    <div class="diluent-modal-loading" data-consumable-loading role="status" hidden>Cargando formulario...</div>
    <div class="diluent-modal-load-error" data-consumable-load-error hidden>
        <p role="alert">No se pudo cargar el formulario. Intenta de nuevo.</p>
        <button type="button" data-consumable-retry><i data-consumable-icon="rotate-cw" aria-hidden="true"></i> Reintentar</button>
    </div>
    <div class="consumable-form-host" data-consumable-form-host></div>
</dialog>
