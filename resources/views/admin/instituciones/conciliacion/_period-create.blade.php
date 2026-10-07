<dialog class="cp-dialog cp-create-dialog" data-period-create-dialog aria-labelledby="cp-create-title"
    data-candidates-url="{{ route('admin.instituciones.conciliacion-periodos.candidates') }}"
    data-store-url="{{ route('admin.instituciones.conciliacion-periodos.store') }}">
    <header><h2 id="cp-create-title" tabindex="-1">Crear periodo</h2><button type="button" class="cp-close" data-create-close aria-label="Cerrar Crear periodo">×</button></header>
    <p class="cp-subtitle">Filtra y selecciona las remisiones que deseas integrar.</p>
    <form data-create-filters class="cp-create-filters">
        <label>Institución<select name="institution"><option value="">Todas las instituciones</option></select></label>
        <label>Hospital<select name="hospital"><option value="">Todos los hospitales</option></select></label>
        <label>Desde<input type="date" name="from"></label>
        <label>Hasta<input type="date" name="to"></label>
        <label class="cp-create-search">Buscar por remisión o paciente<input type="search" name="search" placeholder="Buscar por remisión o paciente"></label>
        <label>Estado<select name="status"><option value="">Todas</option></select></label>
        <div class="cp-create-filter-actions"><button type="submit" class="cp-primary">Aplicar filtros</button><button type="button" class="cp-create-clear" data-create-clear>Limpiar</button></div>
    </form>
    <div class="cp-create-results"><h3>Remisiones existentes</h3><span data-create-count role="status">Cargando remisiones…</span></div>
    <div class="cp-create-scroll" tabindex="0" aria-label="Remisiones existentes">
        <table class="ht-table cp-create-table" data-disable-column-filters>
            <thead><tr><th>No. de remisión</th><th>Institución</th><th>Hospital</th><th>Paciente</th><th>Fecha de remisión</th><th>Descripción</th><th>Monto total<br>(IVA incluido)</th><th>Seleccionar</th></tr></thead>
            <tbody data-create-rows></tbody>
        </table>
    </div>
    <div class="cp-create-selection"><label><input type="checkbox" data-create-all disabled> Seleccionar todas las filtradas disponibles</label><button type="button" class="cp-create-clear" data-create-reset>Quitar selección</button></div>
    <p class="cp-note">Cada periodo corresponde a una institución y un hospital. Las fechas del periodo abarcan las remisiones seleccionadas.</p>
    <div class="cp-create-summary" aria-live="polite">
        <div><span>Periodo</span><strong data-create-range>Sin seleccionar</strong></div>
        <div><strong data-create-selected>0 remisiones seleccionadas</strong></div>
        <div><span>Total con IVA</span><strong data-create-total>$0.00 MXN</strong></div>
    </div>
    <p data-create-error role="alert" class="cp-error" hidden></p>
    <button type="button" class="cp-secondary" data-create-refresh hidden>Actualizar remisiones</button>
    <footer><button type="button" class="cp-secondary" data-create-close>Cancelar</button><p>La selección se conserva al cambiar los filtros.</p><button type="button" class="cp-primary" data-create-submit disabled>Crear periodo</button></footer>
</dialog>
