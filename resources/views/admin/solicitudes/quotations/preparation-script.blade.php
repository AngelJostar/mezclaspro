<script>
    document.addEventListener('DOMContentLoaded', () => {
        const defaults = {{ Illuminate\Support\Js::from($quotationDefaults) }};
        const items = {{ Illuminate\Support\Js::from($quotedItems) }};
        const quotedMixtures = {{ Illuminate\Support\Js::from($quotedMixtures) }};
        const quantity = value => new Intl.NumberFormat('es-MX', { maximumFractionDigits: 4 }).format(value);
        const hasOldInput = @json(session()->hasOldInput());
        const form = document.querySelector('form[data-quotation-preparation]');
        if (!form) return;
        if (!hasOldInput) {
            for (const [name, value] of Object.entries(defaults)) {
                const field = form.elements.namedItem(name);
                if (field && typeof value !== 'object') field.value = value ?? '';
            }
        }
        if (@json($preparationQuotation->category) === 'nutricionales') {
            for (const name of ['sobrellenado_ml', 'volumen_total']) {
                const field = form.elements.namedItem(name);
                if (field) { field.value = ''; field.disabled = true; }
            }
            return;
        }
        form.querySelectorAll('button[onclick]').forEach(button => {
            if (/agregar|eliminar|ajustarCantidad/i.test(button.getAttribute('onclick'))) button.hidden = true;
        });
        form.elements.cantidad_mezclas.readOnly = true;
        const groups = items.reduce((groups, item) => {
            (groups[item.mixture_number] ||= []).push(item); return groups;
        }, {});
        form.querySelectorAll('#contenedorMezclas > .oncology-mixture').forEach((mixture, index) => {
            const group = Object.values(groups)[index];
            if (!group) return;
            const table = mixture.querySelector('.oncology-medicine-table');
            table.classList.replace('oncology-medicine-table', 'quoted-medicine-table');
            table.dataset.disableColumnFilters = '';
            table.parentElement.tabIndex = 0;
            table.parentElement.setAttribute('role', 'region');
            table.parentElement.setAttribute('aria-label', `Medicamentos de la mezcla ${index + 1}`);
            table.querySelector('thead tr').lastElementChild.hidden = true;
            const heading = document.createElement('th');
            heading.className = 'border px-4 py-2 text-xs'; heading.scope = 'col';
            heading.textContent = 'Concentraci\u00f3n cotizada';
            table.querySelector('thead tr').children[0].after(heading);
            const requiredHeading = heading.cloneNode(false);
            requiredHeading.textContent = 'Concentraci\u00f3n requerida';
            heading.after(requiredHeading);
            const medications = Object.values(quotedMixtures)[index];
            const requiredConcentrations = new Map();
            let previousCatalog;
            mixture.querySelectorAll('[data-name="medicamento"]').forEach((medicine, medicineIndex) => {
                const item = group[medicineIndex];
                if (!item) return;
                const row = medicine.closest('tr');
                row.lastElementChild.hidden = true;
                medicine.disabled = true;
                const medication = medications.find(medication => medication.catalog_id === item.catalog_id);
                if (previousCatalog !== item.catalog_id) {
                    const span = Object.keys(medication.items).length;
                    medicine.closest('td').rowSpan = span;
                    const total = document.createElement('td');
                    total.className = 'border quoted-concentration'; total.rowSpan = span;
                    total.dataset.quotedConcentration = String(item.catalog_id);
                    total.textContent = medication.quoted_concentration === null ? 'Concentracion incompleta'
                        : `${quantity(medication.quoted_concentration)} ${item.concentration_unit}`;
                    medicine.closest('td').after(total);
                    const required = document.createElement('td');
                    required.className = 'border quoted-concentration'; required.rowSpan = span;
                    required.dataset.requiredConcentration = String(item.catalog_id);
                    required.setAttribute('aria-live', 'polite');
                    required.setAttribute('aria-atomic', 'true');
                    total.after(required);
                    requiredConcentrations.set(item.catalog_id, { cell: required, unit: item.concentration_unit, doses: [] });
                } else {
                    medicine.closest('td').hidden = true;
                }
                previousCatalog = item.catalog_id;
                const dose = row.querySelector('[data-name="dosis"]');
                const presentation = document.createElement('span');
                presentation.className = 'quoted-presentation'; presentation.textContent = item.presentation;
                dose.before(presentation);
                dose.setAttribute('aria-label', `Dosis de ${item.name}, ${item.presentation}, en ${item.concentration_unit}`);
                dose.min = '0.0001'; dose.step = '0.0001'; dose.required = true;
                if (item.clinical_quantity !== null) {
                    dose.value = item.clinical_quantity;
                    dose.readOnly = true;
                } else {
                    dose.max = String(item.quantity * item.capacity);
                }
                requiredConcentrations.get(item.catalog_id).doses.push(dose);
                for (const name of ['diluyente', 'via_administracion']) row.querySelector(`[data-name="${name}"]`).required = true;
            });
            requiredConcentrations.forEach(({ cell, unit, doses }) => {
                const update = () => {
                    if (doses.some(dose => dose.value === '')) {
                        cell.textContent = 'Pendiente';
                    } else if (doses.some(dose => !dose.validity.valid || !Number.isFinite(dose.valueAsNumber))) {
                        cell.textContent = 'Revisar dosis';
                    } else {
                        const total = doses.reduce((sum, dose) => sum + Math.round(dose.valueAsNumber * 10000), 0) / 10000;
                        cell.textContent = `${quantity(total)} ${unit}`;
                    }
                };
                doses.forEach(dose => {
                    dose.addEventListener('input', update);
                    dose.addEventListener('change', update);
                });
                update();
            });
            for (const name of ['volumen_dilucion', 'tiempo_infusion']) {
                mixture.querySelector(`[data-name="${name}"]`).required = true;
            }
            for (const name of ['set_infusion', 'infusor_id']) {
                const field = mixture.querySelector(`[data-name="${name}"]`);
                field.disabled = true;
            }
        });
    });
</script>
