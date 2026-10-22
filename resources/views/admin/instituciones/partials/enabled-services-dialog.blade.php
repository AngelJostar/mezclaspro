<dialog class="hospital-field-dialog enabled-services-dialog" data-enabled-services-dialog aria-labelledby="enabled-services-title" aria-describedby="enabled-services-name">
    <form data-enabled-services-form>
        @csrf
        <header>
            <span class="enabled-services-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 5h6m4 0h8M3 12h12m4 0h2M3 19h3m4 0h11"/><circle cx="11" cy="5" r="2"/><circle cx="17" cy="12" r="2"/><circle cx="8" cy="19" r="2"/></svg></span>
            <div class="enabled-services-heading">
                <h2 id="enabled-services-title" data-services-title>Servicios habilitados</h2>
                <p id="enabled-services-name" data-services-name></p>
                <p class="enabled-services-description" data-services-description></p>
            </div>
            <button type="button" data-services-close class="hospital-field-cross" aria-label="Cerrar" title="Cerrar">&times;</button>
        </header>
        <div class="hospital-field-body">
            <p data-services-loading role="status">Cargando configuración…</p>
            <p data-services-error role="alert" class="mb-3 text-red-700" hidden></p>
            <button type="button" data-services-retry class="hospital-field-secondary" hidden>Reintentar</button>
            <label class="enabled-services-search" data-services-search-wrap hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg>
                <input type="search" data-services-search placeholder="Buscar hospital" aria-label="Buscar hospital" autocomplete="off">
            </label>
            <div data-services-fields></div>
            <div class="enabled-services-summary" data-services-summary hidden aria-live="polite"><span data-services-count></span><span data-services-enabled-count></span></div>
            <p class="enabled-services-no-results" data-services-no-results hidden>No se encontraron hospitales con ese nombre.</p>
            <div class="enabled-services-help" data-services-help-wrap hidden><span aria-hidden="true">ⓘ</span><p data-services-help></p></div>
        </div>
        <footer>
            <button type="button" data-services-close class="hospital-field-secondary">Cerrar</button>
            <button type="submit" data-services-save class="hospital-field-primary" disabled>Guardar cambios</button>
        </footer>
    </form>
</dialog>
