<dialog class="hospital-field-dialog" data-institution-account-dialog aria-labelledby="institution-account-title">
    <form method="POST">
        @csrf
        <header>
            <div><h2 id="institution-account-title">Crear usuario de institución</h2><p data-account-context></p></div>
            <button type="button" data-account-close class="hospital-field-cross" aria-label="Cerrar">×</button>
        </header>
        <div class="hospital-field-body">
            <p class="mb-4 text-sm">Perfil Institución · Solo consulta de los hospitales relacionados.</p>
            <p data-account-error role="alert" class="mb-3 text-red-700" hidden></p>
            <label for="institution-account-username" class="mb-2 block text-sm font-medium">Usuario</label>
            <input id="institution-account-username" name="username" required maxlength="255" autocomplete="off"
                pattern="[A-Za-z0-9._@-]+" class="mb-4 w-full rounded-md border-gray-300 text-sm">
            <label for="institution-account-password" class="mb-2 block text-sm font-medium">Contraseña</label>
            <input id="institution-account-password" name="password" type="password" required minlength="8" maxlength="255"
                autocomplete="new-password" class="w-full rounded-md border-gray-300 text-sm">
            <p class="mt-2 text-xs text-gray-500">Mínimo 8 caracteres.</p>
        </div>
        <footer>
            <button type="button" data-account-close class="hospital-field-secondary">Cancelar</button>
            <button type="submit" class="hospital-field-primary">Crear acceso</button>
        </footer>
    </form>
</dialog>
@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-institution-account-dialog]');
    const form = dialog.querySelector('form');
    const error = dialog.querySelector('[data-account-error]');
    let saving = false;
    document.querySelectorAll('[data-institution-account-create]').forEach(button => button.addEventListener('click', () => {
        form.reset(); error.hidden = true; form.action = button.dataset.url;
        dialog.querySelector('[data-account-context]').textContent = button.dataset.name;
        dialog.showModal(); form.elements.username.focus();
    }));
    dialog.querySelectorAll('[data-account-close]').forEach(button => button.addEventListener('click', () => { if (!saving) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (saving) return;
        saving = true; error.hidden = true;
        const submit = form.querySelector('[type=submit]'); submit.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'No se pudo crear el acceso.');
            form.reset(); window.location.reload();
        } catch (failure) { error.textContent = failure.message; error.hidden = false; }
        finally { saving = false; submit.disabled = false; }
    });
});
</script>
@endpush
