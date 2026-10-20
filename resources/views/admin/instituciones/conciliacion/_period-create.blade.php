<dialog class="cp-dialog cp-create-dialog" data-period-create-dialog aria-labelledby="cp-create-title"
    data-candidates-url="{{ route('admin.instituciones.conciliacion-periodos.candidates') }}"
    data-store-url="{{ route('admin.instituciones.conciliacion-periodos.store') }}">
    <header><h2 id="cp-create-title" tabindex="-1">Crear periodo</h2><button type="button" class="cp-close" data-create-close aria-label="Cerrar Crear periodo">×</button></header>
    <p class="cp-subtitle">Filtra y selecciona las remisiones que deseas integrar.</p>
    <form data-create-filters class="cp-create-filters">
        <label><span>Institución <span class="cp-required" aria-hidden="true">*</span></span><select name="institution" required aria-required="true"><option value="">Selecciona una institución</option></select></label>
        <label>Hospital<select name="hospital"><option value="">Todos los hospitales</option></select></label>
        <label><span>Desde <span class="cp-required" aria-hidden="true">*</span></span><input type="date" name="from" required aria-required="true"></label>
        <label><span>Hasta <span class="cp-required" aria-hidden="true">*</span></span><input type="date" name="to" required aria-required="true"></label>
        <button type="button" class="cp-secondary" data-create-clear>Limpiar filtros</button>
    </form>
    <div class="cp-create-results"><h3>Remisiones existentes</h3><span data-create-count role="status">Cargando remisiones…</span></div>
    <div class="cp-create-scroll" tabindex="0" aria-label="Remisiones existentes">
        <table class="ht-table cp-create-table" id="conciliation-period-create-table" data-disable-column-filters>
            <thead><tr>
                @foreach (['No. de remisión', 'Institución', 'Hospital', 'Paciente', 'Fecha de remisión', 'Descripción', 'Monto total (IVA incluido)'] as $label)
                    <th><div class="conciliation-column"><span>{{ $label }}</span><button type="button" class="cp-create-filter conciliation-column-filter" data-table-column-trigger data-column="{{ $loop->index }}" aria-label="Filtrar {{ $label }}" title="Filtrar {{ $label }}" aria-expanded="false" disabled><span aria-hidden="true">&#9660;</span></button></div></th>
                @endforeach
                <th scope="col"><input type="checkbox" class="cp-create-bulk" data-create-toggle-all aria-controls="conciliation-period-create-table" aria-label="Seleccionar todas las remisiones filtradas disponibles" title="Seleccionar todas las remisiones filtradas disponibles" disabled></th>
            </tr></thead>
            <tbody data-create-rows></tbody>
        </table>
    </div>
    <p class="cp-note">Puedes seleccionar varios hospitales de la misma institución. Se creará un periodo por cada hospital con remisiones seleccionadas.</p>
    <div class="cp-create-summary" aria-live="polite">
        <div><span>Periodo</span><strong data-create-range>Sin definir</strong></div>
        <div><strong data-create-selected>0 remisiones seleccionadas</strong></div>
        <div><strong data-create-period-count>0 periodos</strong></div>
        <div><span>Total con IVA</span><strong data-create-total>$0.00 MXN</strong></div>
    </div>
    <p data-create-error role="alert" class="cp-error" hidden></p>
    <button type="button" class="cp-secondary" data-create-refresh hidden>Actualizar remisiones</button>
    <footer><button type="button" class="cp-secondary" data-create-close>Cancelar</button><p>La selección se conserva al cambiar los filtros.</p><button type="button" class="cp-primary" data-create-submit disabled>Crear periodo</button></footer>
</dialog>
