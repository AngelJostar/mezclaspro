import '../css/catalog-product-status.css';

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-catalog-status-form], [data-price-list-status-form]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('[role="switch"]');
    if (button.disabled) return;
    const listStatus = form.hasAttribute('data-price-list-status-form');
    const stateField = listStatus ? 'is_active' : 'is_available';
    const message = document.getElementById('catalogStatusMessage');
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || typeof data[stateField] !== 'boolean') {
            throw new Error(response.status === 419
                ? 'La sesion vencio. Recarga la pagina e intenta nuevamente.'
                : (data.message || 'No se pudo guardar el estado. Intenta nuevamente.'));
        }
        const active = data[stateField];
        button.setAttribute('aria-checked', String(active));
        button.title = `${active ? 'Inactivar' : 'Activar'} en ${listStatus ? 'esta lista de precios' : 'el catalogo de productos'}`;
        form.querySelector('[data-status-label]').textContent = active ? 'Activo' : 'Inactivo';
        form.elements[stateField].value = active ? '0' : '1';
        document.dispatchEvent(new Event('catalog-status-updated'));
        if (message) {
            message.textContent = data.message;
            message.dataset.error = 'false';
            message.hidden = false;
        }
    } catch (error) {
        if (message) {
            message.textContent = error.message;
            message.dataset.error = 'true';
            message.hidden = false;
        }
    } finally {
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
});
