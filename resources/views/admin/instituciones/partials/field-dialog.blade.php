<dialog class="hospital-field-dialog" data-institution-field-dialog aria-labelledby="institution-field-title" aria-describedby="institution-field-context">
    <form>
        @csrf
        <header>
            <div>
                <h2 id="institution-field-title" data-institution-field-title></h2>
                <p id="institution-field-context" data-institution-field-context></p>
            </div>
            <button type="button" data-institution-field-close class="hospital-field-cross" aria-label="Cerrar" title="Cerrar">&times;</button>
        </header>
        <div class="hospital-field-body">
            <p data-institution-field-loading role="status" hidden>Cargando…</p>
            <p data-institution-field-error role="alert" class="mb-3 text-red-700" hidden></p>
            <button type="button" data-institution-field-retry class="hospital-field-secondary" hidden>Reintentar</button>
            <div data-institution-field-input-wrap>
                <label for="institution-field-value" data-institution-field-label class="mb-2 block text-sm font-medium"></label>
                <input id="institution-field-value" data-institution-field-input required maxlength="255" class="w-full rounded-md border-gray-300 text-sm">
                <button type="button" data-institution-field-reveal class="mt-2 text-sm underline" hidden>Mostrar contraseña</button>
            </div>
        </div>
        <footer>
            <button type="button" data-institution-field-close class="hospital-field-secondary">Cerrar</button>
            <button type="submit" data-institution-field-save class="hospital-field-primary">Guardar cambios</button>
        </footer>
    </form>
</dialog>
