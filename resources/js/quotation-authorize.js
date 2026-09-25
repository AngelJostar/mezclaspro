import { createIcons, FileCheck2, X, Info, Check } from 'lucide';
import '../css/quotation-authorize.css';

function initQuotationAuthorization() {
    const dialog = document.querySelector('[data-quotation-authorize-dialog]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    createIcons({ icons: { FileCheck2, X, Info, Check }, nameAttr: 'data-quotation-authorize-icon' });
    const find = selector => dialog.querySelector(selector);
    const confirm = find('[data-authorize-confirm]');
    const cancelButtons = dialog.querySelectorAll('[data-authorize-cancel]');
    let sourceForm, opener, saving = false, canAuthorize = false;

    function sync() {
        confirm.disabled = saving || !canAuthorize;
        cancelButtons.forEach(button => { button.disabled = saving; });
        dialog.setAttribute('aria-busy', String(saving));
        find('[data-authorize-label]').textContent = saving ? 'Autorizando...' : 'Autorizar cotizaci\u00f3n';
    }
    function open(form) {
        if (saving) return;
        const data = JSON.parse(form.dataset.quotationAuthorization);
        sourceForm = form; opener = form.querySelector('[data-quotation-authorize]');
        canAuthorize = data.can_authorize === true;
        for (const key of ['folio', 'date', 'hospital', 'amount']) {
            find(`[data-authorize-${key}]`).textContent = data[key] || 'Sin registrar';
        }
        sync(); dialog.showModal();
        find('.quotation-authorize-cancel').focus();
    }
    document.querySelectorAll('[data-quotation-authorization]').forEach(form => {
        form.querySelector('[data-quotation-authorize]').addEventListener('click', () => open(form));
        form.addEventListener('submit', event => {
            if (sourceForm !== form || !saving) { event.preventDefault(); open(form); }
        });
    });
    confirm.addEventListener('click', () => {
        if (!sourceForm || saving || !canAuthorize) return;
        saving = true; sync(); sourceForm.requestSubmit();
    });
    cancelButtons.forEach(button => button.addEventListener('click', () => {
        if (!saving) dialog.close();
    }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog || saving) return;
        const box = dialog.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => { sourceForm = null; canAuthorize = false; opener?.focus(); });
    window.addEventListener('pageshow', () => { saving = false; sync(); });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initQuotationAuthorization);
else initQuotationAuthorization();
