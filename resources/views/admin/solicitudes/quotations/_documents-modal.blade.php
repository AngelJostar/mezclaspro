<dialog class="quotation-documents-dialog" data-quotation-documents-dialog aria-labelledby="quotation-documents-title">
    <form enctype="multipart/form-data">
        @csrf
        <header class="quotation-modal-header">
            <h2 id="quotation-documents-title">Solicitud</h2>
            <button type="button" class="quotation-icon-button" data-doc-close aria-label="Cerrar solicitud" title="Cerrar"><i data-quotation-document-icon="x"></i></button>
        </header>
        <div class="quotation-modal-body quotation-documents-body">
            <p data-doc-error class="quotation-doc-error" role="alert" tabindex="-1" hidden></p>
            <p data-doc-status role="status"></p>
            <button type="button" class="quotation-button quotation-outline" data-doc-retry hidden><i data-quotation-document-icon="rotate-cw"></i>Reintentar</button>
            <div data-doc-upload hidden>
                <div class="quotation-doc-actions">
                    <button type="button" class="quotation-button quotation-outline" data-doc-choose><i data-quotation-document-icon="paperclip"></i>Adjuntar archivo</button>
                    <button type="button" class="quotation-button quotation-outline" data-doc-camera><i data-quotation-document-icon="camera"></i>Tomar foto</button>
                </div>
                <input type="file" data-doc-file accept=".jpg,.jpeg,.png,.webp,.pdf" aria-label="Archivo de solicitud" hidden>
                <input type="file" data-doc-camera-file accept="image/jpeg,image/png,image/webp" capture="environment" aria-label="Foto de solicitud" hidden>
                <p class="quotation-doc-hint">JPG, PNG, WEBP o PDF. M&aacute;ximo 5 MB por archivo.</p>
                <div class="quotation-doc-camera" data-doc-camera-panel hidden>
                    <video data-doc-video autoplay muted playsinline aria-label="Camara de la solicitud"></video>
                    <div class="quotation-doc-actions">
                        <button type="button" class="quotation-button quotation-primary" data-doc-capture><i data-quotation-document-icon="camera"></i>Capturar foto</button>
                        <button type="button" class="quotation-button quotation-outline" data-doc-stop-camera>Cancelar foto</button>
                    </div>
                </div>
                <div class="quotation-doc-preview" data-doc-preview hidden>
                    <img data-doc-image alt="Vista previa de la solicitud seleccionada" hidden>
                    <div class="quotation-doc-selected"><span data-doc-filename></span><button type="button" class="quotation-icon-button" data-doc-clear title="Quitar archivo seleccionado" aria-label="Quitar archivo seleccionado"><i data-quotation-document-icon="x"></i></button></div>
                </div>
            </div>
            <section class="quotation-doc-saved" aria-labelledby="quotation-doc-saved-title">
                <h3 id="quotation-doc-saved-title">Soportes guardados</h3>
                <p data-doc-empty hidden>Sin archivos adjuntos.</p>
                <ul data-doc-list></ul>
            </section>
        </div>
        <footer class="quotation-modal-footer">
            <button type="button" class="quotation-button quotation-outline" data-doc-close>Cerrar</button>
            <button type="submit" class="quotation-button quotation-primary" data-doc-save disabled><i data-quotation-document-icon="save"></i><span data-doc-save-label>Guardar</span></button>
        </footer>
    </form>
</dialog>
