<dialog class="hospital-field-dialog" data-hospital-field-dialog aria-labelledby="hospital-field-title" aria-describedby="hospital-field-hospital">
    <form data-hospital-field-form>
        @csrf
        <header>
            <div>
                <h2 id="hospital-field-title" data-hospital-field-title>Editar información</h2>
                <p id="hospital-field-hospital" data-hospital-field-hospital></p>
            </div>
            <button type="button" data-hospital-field-close class="hospital-field-cross" aria-label="Cerrar" title="Cerrar">&times;</button>
        </header>
        <div class="hospital-field-body">
            <p data-hospital-field-loading role="status">Cargando información…</p>
            <p data-hospital-field-error role="alert" hidden></p>
            <button type="button" class="hospital-field-secondary" data-hospital-field-retry hidden>Reintentar</button>
            <label class="hospital-field-search" data-hospital-field-search-wrap hidden>
                Buscar
                <input type="search" data-hospital-field-search placeholder="Buscar por nombre" autocomplete="off">
            </label>
            <fieldset data-hospital-field-options></fieldset>
            <p data-hospital-field-empty hidden>No hay opciones disponibles.</p>
        </div>
        <footer>
            <button type="button" class="hospital-field-secondary" data-hospital-field-close>Cerrar</button>
            <button type="submit" class="hospital-field-primary" data-hospital-field-save disabled>Guardar cambios</button>
        </footer>
    </form>
</dialog>
