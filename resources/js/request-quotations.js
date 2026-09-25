import { createIcons, Plus, FileSpreadsheet, X, Trash2, FileText, UserRound, Table2, Droplets, Stethoscope, Upload, CalendarDays, Send, Mail, MessageCircle, Download, RotateCw } from 'lucide';
import '../css/request-quotations.css';
import './quotation-send';
import './quotation-commercial';
import './quotation-documents';
import './quotation-authorize';

function initQuotations() {
    const icons = () => createIcons({ icons: { Plus, FileSpreadsheet, X, Trash2, FileText, UserRound, Table2, Droplets, Stethoscope, Upload, CalendarDays, Send, Mail, MessageCircle, Download, RotateCw }, nameAttr: 'data-quotation-icon' });
    icons();
    const dialog = document.querySelector('[data-quotation-dialog]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const form = dialog.querySelector('form');
    const field = name => form.elements.namedItem(name);
    const find = selector => dialog.querySelector(selector);
    const category = field('category');
    const hospital = field('hospital_id');
    const errors = find('[data-quotation-errors]');
    const medications = find('[data-quotation-medications]');
    const components = find('[data-quotation-components]');
    const status = find('[data-quotation-save-status]');
    const catalogStatus = find('[data-quotation-catalog-status]');
    const medicationStatus = find('[data-quotation-medication-status]');
    const refreshMedication = new WeakMap();
    const syncDiluents = new WeakMap();
    const picker = find('[data-quotation-row-picker]');
    const pickerDate = find('[data-picker-date]');
    const pickerDiluent = find('[data-picker-diluent]');
    let pickerApply;
    const submitButtons = [...form.querySelectorAll('[type=submit]')];
    let catalog = null;
    let loading = false;
    let saving = false;
    let requestController;
    let submissionKey;
    let updateUrl = null;
    let openVersion = 0;
    const label = product => [product.name, product.brand, product.presentation, `#${product.id}`].filter(Boolean).join(' - ');
    const option = (value, text) => new Option(text, value);
    const localDate = () => {
        const date = new Date();
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    };
    function clearErrors() {
        errors.hidden = true;
        errors.replaceChildren();
        form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
    }
    function showError(message, details = {}) {
        errors.hidden = false;
        errors.replaceChildren();
        const heading = document.createElement('p');
        heading.textContent = message;
        errors.append(heading);
        Object.entries(details).forEach(([name, messages]) => {
            const p = document.createElement('p');
            p.textContent = messages.join(' ');
            errors.append(p);
            const input = field(name) || (name.startsWith('rows.') ? medications.querySelector('[data-drug]') : null);
            input?.setAttribute('aria-invalid', 'true');
        });
        errors.focus();
    }
    async function jsonRequest(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...options.headers } });
        let body;
        try { body = await response.json(); } catch { throw new Error('No se pudo leer la respuesta. Recarga la pagina para verificar tu sesion.'); }
        if (!response.ok) {
            const error = new Error(response.status === 419 ? 'La sesion vencio. Recarga la pagina antes de guardar.' : body.message || 'No fue posible completar la operacion.');
            error.details = body.errors;
            throw error;
        }
        return body;
    }
    function buttons() {
        submitButtons.forEach(button => { button.disabled = loading || saving || !catalog?.products.length; });
        form.querySelectorAll('[data-quotation-close]').forEach(button => { button.disabled = saving; });
        find('[data-quotation-add]').disabled = saving || medications.rows.length >= 50;
        medications.querySelectorAll('[data-remove]').forEach(button => { button.disabled = saving; });
        medications.querySelectorAll('[data-drug]').forEach(input => {
            input.disabled = loading || saving || !catalog?.products.length;
        });
        medications.querySelectorAll('[data-delivery-button]').forEach(button => { button.disabled = saving; });
        [...medications.rows].forEach(row => syncDiluents.get(row)?.());
    }
    function openRowPicker(title, value, onApply, choices = null) {
        if (picker.open) picker.close();
        pickerApply = onApply;
        find('#quotation-row-picker-title').textContent = title;
        find('[data-picker-date-field]').hidden = Boolean(choices);
        find('[data-picker-diluent-field]').hidden = !choices;
        pickerDate.disabled = Boolean(choices);
        pickerDiluent.disabled = !choices;
        pickerDate.setCustomValidity('');
        pickerDiluent.setCustomValidity('');
        if (choices) {
            pickerDiluent.replaceChildren(option('', 'Seleccionar diluyente'));
            choices.forEach(item => pickerDiluent.add(option(item.id, item.name)));
            pickerDiluent.value = value;
        } else {
            pickerDate.min = `${field('scheduled_date').value}T00:00`;
            pickerDate.value = value;
        }
        picker.showModal();
        (choices ? pickerDiluent : pickerDate).focus();
    }
    function applyRowPicker() {
        const input = pickerDate.disabled ? pickerDiluent : pickerDate;
        input.setCustomValidity(input.value ? '' : 'Selecciona un valor o usa Borrar.');
        if (!input.reportValidity()) return;
        pickerApply(input.value);
        picker.close();
    }
    picker.querySelectorAll('[data-picker-cancel]').forEach(button => button.addEventListener('click', () => picker.close()));
    find('[data-picker-apply]').addEventListener('click', applyRowPicker);
    find('[data-picker-clear]').addEventListener('click', () => { pickerApply(''); picker.close(); });
    picker.addEventListener('close', () => { pickerDate.disabled = true; pickerDiluent.disabled = true; });
    [pickerDate, pickerDiluent].forEach(input => {
        input.addEventListener('input', () => input.setCustomValidity(''));
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); applyRowPicker(); }
        });
    });
    function diluentGroup(diluent) {
        const name = diluent.name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase().replace(/\s+/g, ' ');
        // Unrecognized names and concentrations remain explicit catalog choices under Otro.
        if (/^(cs|(?:cloruro de sodio|solucion salina|solucion fisiologica)(?: al)?(?: 0[.,]9\s*%)?)$/.test(name)) return 'CS';
        if (/^(dx|(?:dextrosa|glucosa|solucion glucosada)(?: al)?(?: 5\s*%)?)$/.test(name)) return 'DX';
        return 'Otro';
    }
    function setCatalogStatus(message) {
        catalogStatus.textContent = message;
        medicationStatus.textContent = message;
        medicationStatus.hidden = !message;
    }
    function setType() {
        const pending = category.value === 'antibioticos';
        find('[data-quotation-pending]').hidden = !pending;
        find('[data-quotation-content]').hidden = pending;
        find('[data-quotation-content]').disabled = pending;
        dialog.querySelectorAll('[data-quotation-type]').forEach(group => {
            group.hidden = group.dataset.quotationType !== category.value;
            group.disabled = group.hidden;
        });
        field('sex').required = category.value === 'oncologicos';
        find('[data-oncology-required]').hidden = category.value !== 'oncologicos';
        field('diagnosis').required = category.value === 'oncologicos';
        if (!updateUrl) field('service').value = category.value === 'nutricionales' ? 'Nutricion parenteral' : '';
        find('#quotation-capture-title').textContent = (updateUrl ? 'Editar cotizacion' : 'Nueva cotizacion')
            + (category.value === 'oncologicos' ? ' oncologica' : category.value === 'nutricionales' ? ' nutricional' : pending ? ' de antibioticos' : '');
    }
    function renumberRows() {
        [...medications.rows].forEach((row, index) => { row.querySelector('[data-row-number]').textContent = index + 1; });
    }
    function addRow(data = {}) {
        if (medications.rows.length >= 50) return;
        const row = document.querySelector('#quotation-medication-row').content.firstElementChild.cloneNode(true);
        const drug = row.querySelector('[data-drug]');
        const productId = row.querySelector('[data-key=presentation_id]');
        const diluent = row.querySelector('[data-key=diluent_id]');
        const choices = [...row.querySelectorAll('[data-diluent-choice]')];
        const allowedDiluents = () => catalog?.products.find(item => String(item.id) === productId.value)?.diluents || [];
        function syncDiluentChoices() {
            const allowed = allowedDiluents();
            choices.forEach(checkbox => {
                const group = allowed.filter(item => diluentGroup(item) === checkbox.dataset.diluentChoice);
                checkbox.checked = group.some(item => String(item.id) === diluent.value);
                checkbox.disabled = loading || saving || !group.length;
                checkbox.title = group.map(item => item.name).join(', ') || `${checkbox.dataset.diluentChoice}: no disponible para este medicamento`;
            });
        }
        syncDiluents.set(row, syncDiluentChoices);
        choices.forEach(checkbox => checkbox.addEventListener('change', () => {
            if (!checkbox.checked) { diluent.value = ''; syncDiluentChoices(); return; }
            const available = allowedDiluents().filter(item => diluentGroup(item) === checkbox.dataset.diluentChoice);
            if (available.length === 1) diluent.value = available[0].id;
            else openRowPicker(`Diluyente ${checkbox.dataset.diluentChoice}`, diluent.value, value => {
                diluent.value = value; syncDiluentChoices();
            }, available);
            syncDiluentChoices();
        }));
        function selectDrug() {
            const product = catalog?.products.find(item => label(item) === drug.value);
            const previousDiluent = String(product?.id) === productId.value ? diluent.value : '';
            productId.value = product?.id || '';
            drug.setCustomValidity(product ? '' : 'Selecciona un medicamento de la lista del hospital.');
            drug.removeAttribute('aria-invalid');
            diluent.value = product?.diluents?.some(item => String(item.id) === previousDiluent) ? previousDiluent : '';
            syncDiluentChoices();
        }
        refreshMedication.set(row, () => {
            const product = catalog?.products.find(item => String(item.id) === productId.value);
            if (product) {
                drug.value = label(product); selectDrug();
            }
            else if (productId.value) {
                drug.setCustomValidity('El medicamento ya no esta activo en esta lista. Selecciona otro producto o elimina el renglon.');
                drug.setAttribute('aria-invalid', 'true');
                diluent.value = '';
                syncDiluentChoices();
            } else selectDrug();
        });
        drug.addEventListener('input', selectDrug);
        row.querySelector('[data-remove]').addEventListener('click', () => {
            row.remove();
            if (!medications.rows.length) addRow();
            renumberRows();
            buttons();
        });
        const product = catalog?.products.find(item => String(item.id) === String(data.presentation_id));
        if (product) { drug.value = label(product); selectDrug(); }
        else if (data.presentation_id) drug.value = [data.product_name || 'Medicamento', data.presentation_name].filter(Boolean).join(' - ');
        row.querySelectorAll('[data-key]').forEach(input => { input.value = data[input.dataset.key] ?? ''; });
        row.querySelectorAll('[data-delivery]').forEach((input, index) => {
            input.value = data.deliveries?.[index] || '';
            const button = input.nextElementSibling;
            function updateDelivery() {
                button.dataset.filled = input.value ? 'true' : 'false';
                const [date, time] = input.value.split('T');
                button.title = `Entrega ${index + 1}: ${input.value ? `${date.split('-').reverse().join('/')} ${time}` : 'sin fecha'}`;
                button.setAttribute('aria-label', button.title);
            }
            button.addEventListener('click', () => openRowPicker(`Fecha de entrega ${index + 1}`, input.value, value => {
                input.value = value; updateDelivery();
            }));
            updateDelivery();
        });
        medications.append(row);
        if (catalog) refreshMedication.get(row)();
        renumberRows();
        buttons();
        icons();
        return row;
    }
    function nutritionFields(values = []) {
        components.replaceChildren();
        const groups = new Map();
        const unavailable = values.filter(item => !catalog.products.some(product => String(product.id) === String(item.presentation_id)))
            .map(item => ({ id: item.presentation_id, name: `${item.product_name} (no disponible)`, presentation: item.presentation_name, group: 'No disponibles' }));
        [...catalog.products, ...unavailable].forEach(product => {
            if (!groups.has(product.group)) {
                const heading = document.createElement('h4');
                heading.textContent = product.group;
                const grid = document.createElement('div');
                grid.className = 'quotation-grid';
                groups.set(product.group, grid);
                components.append(heading, grid);
            }
            const container = document.createElement('label');
            container.className = 'quotation-field';
            const title = document.createElement('span');
            title.textContent = product.name;
            const brand = document.createElement('small');
            brand.textContent = [product.brand, product.presentation].filter(Boolean).join(' - ');
            const input = document.createElement('input');
            input.type = 'number'; input.min = '0'; input.max = '100000'; input.step = 'any'; input.placeholder = '0.00';
            input.dataset.component = product.id;
            input.value = values.find(item => String(item.presentation_id) === String(product.id))?.volume_ml ?? '';
            input.setAttribute('aria-label', `${product.name} (${product.brand || product.presentation}) en ml`);
            if (product.group === 'No disponibles') {
                const validate = () => input.setCustomValidity(Number(input.value) ? 'Este componente ya no esta activo. Retira su cantidad antes de guardar.' : '');
                validate(); input.addEventListener('input', validate);
            }
            const unit = document.createElement('small'); unit.textContent = 'ml';
            container.append(title, brand, input, unit);
            groups.get(product.group).append(container);
        });
        volume();
    }
    function volume() {
        const total = [...components.querySelectorAll('input')].reduce((sum, input) => sum + (Number(input.value) || 0), 0);
        find('[data-quotation-volume]').value = total.toFixed(2);
    }
    function age() {
        const birth = field('birth_date').value;
        const today = localDate();
        find('[data-quotation-age]').value = birth && birth <= today ? Number(today.slice(0, 4)) - Number(birth.slice(0, 4)) - (today.slice(5) < birth.slice(5) ? 1 : 0) : '';
    }
    async function loadCatalog(prefill = null) {
        requestController?.abort();
        requestController = new AbortController();
        const controller = requestController;
        catalog = null;
        loading = true;
        find('[data-quotation-capture-fields]').disabled = false;
        components.replaceChildren();
        find('#quotation-medicines').replaceChildren();
        if (prefill) {
            medications.replaceChildren();
            prefill.rows?.forEach(addRow);
        }
        if (category.value === 'oncologicos' && !medications.rows.length) addRow();
        buttons();
        field('institution_id').replaceChildren(option('', 'Seleccionar institucion'));
        find('[data-quotation-price-list]').value = '';
        clearErrors();
        setCatalogStatus(category.value && hospital.value ? 'Cargando lista de precios...' : 'Selecciona el tipo de mezcla y el hospital para consultar los medicamentos disponibles.');
        if (!['oncologicos', 'nutricionales'].includes(category.value) || !hospital.value) { loading = false; buttons(); return; }
        try {
            const url = new URL(dialog.dataset.optionsUrl, location.href);
            url.searchParams.set('category', category.value); url.searchParams.set('hospital_id', hospital.value);
            const result = await jsonRequest(url, { signal: controller.signal });
            if (controller !== requestController) return;
            catalog = result;
            catalog.institutions.forEach(item => field('institution_id').add(option(item.id, item.nombre)));
            if (prefill) field('institution_id').value = prefill.institution_id;
            else if (catalog.institutions.length === 1) field('institution_id').value = catalog.institutions[0].id;
            find('[data-quotation-price-list]').value = catalog.price_list.name;
            setCatalogStatus(catalog.products.length ? '' : 'No hay productos activos en la lista de precios asignada.');
            const list = find('#quotation-medicines'); list.replaceChildren();
            catalog.products.forEach(item => list.append(option(label(item), label(item))));
            if (category.value === 'oncologicos') [...medications.rows].forEach(row => refreshMedication.get(row)());
            else nutritionFields(prefill?.components || []);
            const capturedProducts = prefill?.rows || prefill?.components || [];
            if (capturedProducts.some(item => !catalog.products.some(product => String(product.id) === String(item.presentation_id)))) {
                showError('Hay productos que ya no estan activos en esta lista. Revisa los medicamentos o componentes antes de guardar.');
            }
        } catch (error) {
            if (error.name !== 'AbortError' && controller === requestController) {
                setCatalogStatus('Lista de precios no disponible.');
                showError(error.message, error.details);
            }
        } finally {
            if (controller === requestController) { loading = false; buttons(); }
        }
    }
    function reset() {
        if (picker.open) picker.close();
        openVersion += 1;
        requestController?.abort();
        requestController = null;
        form.reset(); clearErrors();
        form.querySelectorAll('[data-inactive-seller]').forEach(option => option.remove());
        updateUrl = null; catalog = null; saving = false; loading = false;
        medications.replaceChildren(); components.replaceChildren();
        find('#quotation-medicines').replaceChildren();
        find('[data-quotation-capture-fields]').disabled = false;
        submissionKey = crypto.randomUUID();
        category.disabled = false;
        category.value = dialog.dataset.selectedType === 'todas' ? '' : dialog.dataset.selectedType;
        category.disabled = Boolean(category.value);
        field('scheduled_date').value = localDate();
        field('birth_date').max = localDate();
        field('infusion_hours').value = '24';
        status.textContent = '* Campos obligatorios';
        find('[data-quotation-existing-attachment]').hidden = true;
        find('[data-quotation-age]').value = '';
        setType();
        if (!dialog.open) dialog.showModal();
        return openVersion;
    }
    document.querySelectorAll('[data-quotation-edit]:not([data-quotation-flow="commercial"])').forEach(button => button.addEventListener('click', async () => {
        const version = reset();
        loading = true; buttons(); find('[data-quotation-capture-fields]').disabled = true;
        try {
            const data = await jsonRequest(button.dataset.quotationEdit);
            if (version !== openVersion || !dialog.open) return;
            if (!data.editable) throw new Error('Esta cotizacion ya no se puede editar.');
            updateUrl = data.update_url;
            Object.entries(data.clinical_data).forEach(([key, value]) => { if (field(key) && typeof value !== 'object') field(key).value = value ?? ''; });
            const seller = field('seller_id');
            const sellerName = find('[data-quotation-seller-name]');
            if (sellerName) sellerName.value = data.seller_name || 'Sin asignar';
            if (seller) {
                if (data.seller_id && !Array.from(seller.options).some(option => option.value === String(data.seller_id))) {
                    const option = new Option(`${data.seller_name} (no disponible)`, String(data.seller_id));
                    option.dataset.inactiveSeller = '';
                    seller.add(option);
                }
                seller.value = data.seller_id ?? '';
            }
            category.disabled = true;
            setType(); age();
            const attachment = find('[data-quotation-existing-attachment]');
            attachment.hidden = !data.attachment_url; attachment.href = data.attachment_url || '';
            await loadCatalog(data.clinical_data);
        } catch (error) { if (version === openVersion) { loading = false; buttons(); showError(error.message, error.details); } }
    }));
    form.querySelectorAll('[data-quotation-close]').forEach(button => button.addEventListener('click', () => { if (!saving) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => { openVersion += 1; requestController?.abort(); });
    category.addEventListener('change', () => { setType(); loadCatalog(); });
    hospital.addEventListener('change', () => loadCatalog());
    field('birth_date').addEventListener('change', age);
    field('scheduled_date').addEventListener('change', () => {
        form.querySelectorAll('[data-delivery], [name=delivery_at]').forEach(input => { input.min = `${field('scheduled_date').value}T00:00`; });
    });
    components.addEventListener('input', volume);
    find('[data-quotation-add]').addEventListener('click', () => {
        const row = addRow();
        row?.querySelector('input:not(:disabled):not([type=hidden])')?.focus();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (picker.open) { applyRowPicker(); return; }
        if (saving || loading || !catalog?.products.length) return;
        clearErrors();
        const data = new FormData(form);
        data.set('category', category.value);
        data.set('submission_key', submissionKey);
        data.set('action', event.submitter?.value || 'save');
        if (updateUrl) data.set('_method', 'PUT');
        const file = field('attachment').files[0];
        if (file?.size > 5 * 1024 * 1024) { showError('El adjunto debe ser menor o igual a 5 MB.'); return; }
        if (!file || category.value !== 'oncologicos') data.delete('attachment');
        if (category.value === 'oncologicos') {
            for (const row of medications.rows) {
                const invalidDelivery = [...row.querySelectorAll('[data-delivery]')].find((input, index) =>
                    (index === 0 && !input.value) || (input.value && input.value < `${field('scheduled_date').value}T00:00`));
                if (invalidDelivery) {
                    showError('Captura la primera entrega de cada medicamento y revisa que las fechas sean posteriores o iguales a la fecha de programacion.');
                    invalidDelivery.nextElementSibling.click();
                    return;
                }
            }
            [...medications.rows].forEach((row, index) => {
                row.querySelectorAll('[data-key]').forEach(input => data.set(`rows[${index}][${input.dataset.key}]`, input.value));
                [...row.querySelectorAll('[data-delivery]')].filter(input => input.value).forEach((input, day) => data.set(`rows[${index}][deliveries][${day}]`, input.value));
            });
        } else {
            const selected = [...components.querySelectorAll('input')].filter(input => input.value !== '' && Number(input.value) !== 0);
            if (!selected.length) { showError('Captura al menos un componente de la nutricion parenteral.'); return; }
            selected.forEach((input, index) => {
                data.set(`components[${index}][presentation_id]`, input.dataset.component);
                data.set(`components[${index}][volume_ml]`, input.value);
            });
        }
        saving = true; buttons(); status.textContent = 'Guardando cotizacion...';
        try {
            const result = await jsonRequest(updateUrl || form.action, { method: 'POST', body: data });
            status.textContent = `${result.folio} guardada.`;
            location.assign(result.redirect_url);
        } catch (error) {
            saving = false; buttons(); status.textContent = 'No se pudo confirmar el guardado.';
            showError(error.message, error.details);
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initQuotations, { once: true });
else initQuotations();
