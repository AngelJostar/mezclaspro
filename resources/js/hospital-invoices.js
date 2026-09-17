function initializeInvoiceDetail() {
    const root = document.querySelector('[data-invoice-detail]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    const form = root.querySelector('[data-payment-form]');
    if (!form) return;
    const open = root.querySelector('[data-open-payment]');
    const feedback = form.querySelector('[data-payment-feedback]');
    open.addEventListener('click', () => {
        form.hidden = false;
        form.elements.amount.focus();
    });
    form.querySelector('[data-cancel-payment]').addEventListener('click', () => {
        form.hidden = true;
        open.focus();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!form.reportValidity() || form.dataset.sending) return;
        form.dataset.sending = 'true';
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        feedback.hidden = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo enviar el reporte.');
            feedback.className = 'ht-feedback';
            feedback.textContent = data.message;
            form.querySelectorAll('input, textarea').forEach(input => { input.disabled = true; });
            const link = document.createElement('a');
            link.href = location.href;
            link.textContent = ' Ver reporte';
            link.className = 'ht-clear';
            feedback.append(link);
        } catch (error) {
            feedback.className = 'ht-error';
            feedback.textContent = error instanceof SyntaxError ? 'No se pudo enviar. Verifica tu sesi\u00f3n e intenta de nuevo.' : error.message;
            submit.disabled = false;
        } finally {
            feedback.hidden = false;
            delete form.dataset.sending;
        }
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeInvoiceDetail);
else initializeInvoiceDetail();
document.addEventListener('livewire:navigated', initializeInvoiceDetail);
