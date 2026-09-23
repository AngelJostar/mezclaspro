import { createIcons, X } from 'lucide';
import '../css/minimum-stock.css';

function initMinimumStock() {
    const root = document.querySelector('[data-minimum-stock]');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    createIcons({ icons: { X }, nameAttr: 'data-stock-icon', root });
    const dialog = root.querySelector('[data-stock-supplier-dialog]');
    if (!dialog) return;
    const supplierForm = dialog.querySelector('form');
    const supplierSelect = supplierForm.elements.supplier_id;
    const notice = root.querySelector('[data-stock-notice]');
    let supplierRow = null;
    let savingSupplier = false;

    async function save(row, data, form) {
        const response = await fetch(row.dataset.stockUrl, {
            method: 'PATCH', credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
            body: JSON.stringify(data),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'No se pudo guardar. Intenta nuevamente.');
        notice.textContent = 'Configuracion de stock guardada.';
        return result;
    }

    root.querySelectorAll('[data-stock-row]').forEach(row => {
        const form = row.querySelector('[data-stock-limits]');
        const minimum = row.querySelector('[data-stock-min]');
        const maximum = row.querySelector('[data-stock-max]');
        const edit = row.querySelector('[data-stock-edit]');
        const submit = row.querySelector('[data-stock-save]');
        const cancel = row.querySelector('[data-stock-cancel]');
        const error = row.querySelector('[data-stock-error]');
        let original = [minimum.value, maximum.value];
        let saving = false;
        const editing = active => {
            minimum.readOnly = maximum.readOnly = !active;
            edit.hidden = active;
            submit.hidden = cancel.hidden = !active;
            error.hidden = true;
        };
        const validate = () => maximum.setCustomValidity(minimum.value !== '' && maximum.value !== '' && +maximum.value < +minimum.value
            ? 'El punto de reorden debe ser mayor o igual al stock minimo.' : '');
        minimum.addEventListener('input', validate);
        maximum.addEventListener('input', validate);
        edit.addEventListener('click', () => { original = [minimum.value, maximum.value]; editing(true); minimum.focus(); });
        cancel.addEventListener('click', () => {
            [minimum.value, maximum.value] = original;
            maximum.setCustomValidity('');
            editing(false);
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving || submit.hidden) return;
            validate();
            if (!minimum.reportValidity() || !maximum.reportValidity()) return;
            saving = true;
            submit.disabled = cancel.disabled = true;
            minimum.readOnly = maximum.readOnly = true;
            error.hidden = true;
            try {
                const result = await save(row, { minimum_stock: +minimum.value, maximum_stock: +maximum.value }, form);
                minimum.value = result.minimum_stock;
                maximum.value = result.maximum_stock;
                original = [minimum.value, maximum.value];
                editing(false);
            } catch (failure) {
                minimum.readOnly = maximum.readOnly = false;
                error.textContent = failure.message;
                error.hidden = false;
            } finally {
                saving = false;
                submit.disabled = cancel.disabled = false;
            }
        });
    });

    const updateEmail = () => { dialog.querySelector('[data-stock-dialog-email]').textContent = supplierSelect.selectedOptions[0]?.dataset.email || '-'; };
    root.querySelectorAll('[data-stock-change-supplier]').forEach(button => button.addEventListener('click', () => {
        supplierRow = button.closest('[data-stock-row]');
        supplierSelect.value = supplierRow.dataset.supplierId;
        dialog.querySelector('[data-stock-dialog-product]').textContent = supplierRow.dataset.productLabel;
        dialog.querySelector('[data-stock-dialog-error]').hidden = true;
        updateEmail();
        dialog.showModal();
        supplierSelect.focus();
    }));
    supplierSelect.addEventListener('change', updateEmail);
    dialog.querySelectorAll('[data-stock-close-dialog]').forEach(button => button.addEventListener('click', () => { if (!savingSupplier) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (savingSupplier) event.preventDefault(); });
    supplierForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!supplierRow || savingSupplier) return;
        savingSupplier = true;
        const controls = dialog.querySelectorAll('button, select');
        const error = dialog.querySelector('[data-stock-dialog-error]');
        error.hidden = true;
        controls.forEach(control => { control.disabled = true; });
        try {
            const result = await save(supplierRow, { supplier_id: supplierSelect.value ? Number(supplierSelect.value) : null }, supplierForm);
            supplierRow.dataset.supplierId = result.supplier?.id || '';
            const name = supplierRow.querySelector('[data-stock-supplier-name]');
            name.textContent = result.supplier?.name || 'Sin proveedor';
            name.parentElement.dataset.columnFilterValue = name.textContent;
            supplierRow.querySelector('[data-stock-supplier-state]').textContent = result.supplier ? 'Predeterminado' : '';
            supplierRow.querySelector('[data-stock-supplier-email]').textContent = result.supplier?.email || '-';
            dialog.close();
        } catch (failure) {
            error.textContent = failure.message;
            error.hidden = false;
        } finally {
            savingSupplier = false;
            controls.forEach(control => { control.disabled = false; });
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initMinimumStock, { once: true });
else initMinimumStock();
