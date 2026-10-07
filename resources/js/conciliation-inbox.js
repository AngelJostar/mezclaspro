function initializeConciliationInbox() {
    const root = document.querySelector('[data-conciliation-inbox]');
    if (!root || root.dataset.switchesReady) return;
    root.dataset.switchesReady = 'true';
    const feedback = root.querySelector('[data-inbox-status]');
    const syncToggle = input => {
        const group = input.closest('.inbox-conciliable-toggle');
        if (!group) return;
        group.setAttribute('aria-busy', String(input.disabled));
        group.querySelectorAll('[data-inbox-conciliable-choice]').forEach(button => {
            button.disabled = input.disabled;
            button.setAttribute('aria-pressed', String((button.dataset.inboxConciliableChoice === '1') === input.checked));
        });
    };
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-inbox-conciliable-choice]');
        if (!button || button.disabled) return;
        const input = button.closest('.inbox-conciliable-toggle').querySelector('[data-inbox-conciliable]');
        const checked = button.dataset.inboxConciliableChoice === '1';
        if (input.disabled || input.checked === checked) return;
        input.checked = checked;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    root.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target.matches('[data-inbox-price]')) {
            event.preventDefault();
            event.target.blur();
        }
    });
    root.addEventListener('change', async event => {
        const input = event.target;
        const isPrice = input.matches('[data-inbox-price]');
        if (!isPrice && !input.matches('[data-inbox-conciliable]')) return;
        const value = input.value.trim();
        if (isPrice && value === input.dataset.savedValue) return;
        if (isPrice && !/^\d{1,10}(?:\.\d{1,2})?$/.test(value)) {
            feedback.textContent = 'Captura un precio válido, sin comas, mayor o igual a cero y con hasta dos decimales.';
            feedback.classList.add('ht-error');
            feedback.hidden = false;
            input.value = input.dataset.savedValue;
            return;
        }
        const checked = input.checked;
        const field = isPrice ? 'precio_total' : 'conciliable';
        const filterField = isPrice ? 'price' : 'conciliable';
        const peers = [...root.querySelectorAll(isPrice ? '[data-inbox-price]' : '[data-inbox-conciliable]')].filter(item => item.dataset.mixture === input.dataset.mixture);
        peers.forEach(item => { item.disabled = true; syncToggle(item); });
        feedback.hidden = true;
        try {
            const response = await fetch(input.dataset.url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ [field]: isPrice ? value : checked, previous: input.dataset.previous || null }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo guardar el cambio.');
            peers.forEach(item => {
                item.dataset.previous = result[field];
                if (isPrice) {
                    item.value = result[field];
                    item.dataset.savedValue = result[field];
                } else {
                    item.checked = checked;
                    syncToggle(item);
                    const statusCell = item.closest('tr').querySelector('[data-inbox-conciliation-status]');
                    const statusBadge = statusCell?.querySelector('.inbox-conciliation-status');
                    if (statusBadge) {
                        statusCell.dataset.columnFilterValue = result.conciliation_status;
                        statusBadge.dataset.status = result.conciliation_status;
                        statusBadge.textContent = result.conciliation_status;
                    }
                }
                item.closest('td').dataset.columnFilterValue = isPrice ? result[field] : (checked ? 'Sí' : 'No');
            });
            feedback.textContent = isPrice ? 'Precio de venta guardado.' : 'Estado conciliable guardado.';
            feedback.classList.remove('ht-error');
            const config = JSON.parse(root.querySelector('#conciliation-inbox-filters')?.textContent || '{}');
            if (config.selected?.[filterField] || config.sort === filterField
                || (!isPrice && (config.selected?.conciliation_status || config.sort === 'conciliation_status'))) {
                // Recalculate matching rows and pagination after changing the filtered value.
                window.location.reload();
            }
        } catch (error) {
            if (isPrice) input.value = input.dataset.savedValue;
            else input.checked = !checked;
            feedback.textContent = error instanceof SyntaxError ? 'No se pudo guardar. Verifica tu sesión y vuelve a intentarlo.' : error.message;
            feedback.classList.add('ht-error');
        } finally {
            peers.forEach(item => { item.disabled = false; syncToggle(item); });
            feedback.hidden = false;
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeConciliationInbox);
else initializeConciliationInbox();
document.addEventListener('livewire:navigated', initializeConciliationInbox);
