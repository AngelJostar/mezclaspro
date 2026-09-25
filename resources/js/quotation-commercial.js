import { createIcons, X, Droplets, FlaskConical, Pill, Search, Plus, Trash2, LockKeyhole, Pencil, Check, RotateCcw } from 'lucide';
import '../css/quotation-commercial.css';

function initCommercialQuotation() {
    const dialog = document.querySelector('[data-quote-wizard]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const form = dialog.querySelector('form');
    const find = selector => dialog.querySelector(selector);
    const field = name => form.elements.namedItem(name);
    const icons = () => createIcons({ icons: { X, Droplets, FlaskConical, Pill, Search, Plus, Trash2, LockKeyhole, Pencil, Check, RotateCcw }, nameAttr: 'data-qw-icon' });
    const labels = { nutricionales: 'Nutricionales', oncologicos: 'Oncologicos', antibioticos: 'Antibioticos' };
    const money = (value, decimals = 2) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value);
    const round = value => Math.round((value + Number.EPSILON) * 100) / 100;
    let step = 0, category = '', catalog = null, items = [], loading = false, busy = false;
    let controller, version = 0, submissionKey, updateUrl, pricingToken, documentIdentity;
    let mixtureCount = 1, mixtureSections = [], recipeSections = [], requirements = [];
    const normalize = text => String(text || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const mixtureItems = number => items.filter(item => item.mixture_number === number);
    const recipeRows = number => requirements.filter(row => row.mixture_number === number);
    const completeRecipe = () => Array.from({ length: mixtureCount }, (_, index) => {
        const rows = recipeRows(index + 1);
        return rows.length > 0 && rows.every(row => row.medicine.trim() && Number(row.concentration) > 0
            && Number(row.concentration) <= 1000000) && new Set(rows.map(row => normalize(row.medicine))).size === rows.length;
    }).every(Boolean);
    const completeMixtures = () => Array.from({ length: mixtureCount }, (_, index) => mixtureItems(index + 1).length > 0).every(Boolean);
    const unit = () => category === 'nutricionales' ? 'mL' : 'mg';
    const noCommercial = () => field('no_commercial_relationship').checked;
    const productFor = id => catalog?.products.find(product => Number(product.id) === Number(id));
    const priceFor = item => item.unit_price_override === '' ? null
        : item.unit_price_override != null ? Number(item.unit_price_override) : productFor(item.presentation_id)?.unit_price;
    const el = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    function error(message = '') {
        const box = find('[data-qw-error]');
        box.textContent = message;
        box.hidden = !message;
        if (message) box.focus();
    }
    async function request(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': field('_token').value, ...(options.body ? { 'Content-Type': 'application/json' } : {}) } });
        let data;
        try { data = await response.json(); } catch { throw new Error('No se pudo leer la respuesta. Verifica tu sesion antes de intentar de nuevo.'); }
        if (!response.ok) throw new Error(response.status === 419 ? 'La sesion vencio. Recarga la pagina.'
            : Object.values(data.errors || {}).flat().join(' ') || data.message || 'No fue posible completar la operacion.');
        return data;
    }
    function sync() {
        dialog.dataset.step = String(step);
        dialog.querySelectorAll('[data-qw-step]').forEach(section => {
            section.hidden = Number(section.dataset.qwStep) !== step;
            if (section.tagName === 'FIELDSET') section.disabled = section.hidden || busy;
        });
        if (field('seller_id')) {
            find('[data-qw-seller]').hidden = ![1, 2].includes(step);
            field('seller_id').disabled = ![1, 2].includes(step) || busy;
        }
        const badge = find('[data-qw-badge]');
        badge.hidden = !category || step === 0;
        badge.textContent = `${labels[category] || ''} · ${unit()}`;
        find('[data-qw-step-label]').textContent = ['Categoria de medicamentos', 'Receta o solicitud de mezcla', 'Desglose de medicamentos y datos del paciente', 'Revision de cotizacion'][step];
        find('[data-qw-back]').textContent = step === 0 ? 'Cancelar' : 'Atras';
        find('[data-qw-next]').textContent = ['Continuar', 'Continuar', 'Continuar', 'Generar cotizaci\u00f3n'][step];
        find('[data-qw-next]').disabled = busy || loading || (step === 0 ? !category : !catalog || (step === 1 ? !completeRecipe() : !completeMixtures()));
        find('[data-qw-save]').hidden = step !== 3;
        find('[data-qw-save]').disabled = busy || !pricingToken;
        find('[data-qw-back]').disabled = busy;
        find('[data-qw-close]').disabled = busy;
        dialog.querySelectorAll('[name=billing_mode]').forEach(input => { input.disabled = !noCommercial() || busy; });
        find('[data-qw-lock]').hidden = noCommercial();
        find('[data-qw-commercial-label]').textContent = noCommercial() ? 'Si' : 'No';
        find('[data-qw-unit-label]').textContent = `Por ${unit()}`;
        const mixtureLimit = mixtureCount >= 50 || items.length >= 50 || requirements.length >= 50;
        find('[data-qw-add-mixture]').disabled = loading || busy || !catalog || mixtureLimit;
        find('[data-qw-recipe-add-mixture]').disabled = loading || busy || !catalog || mixtureLimit;
        dialog.querySelectorAll('[data-qw-recipe-add]').forEach(button => { button.disabled = loading || busy || !catalog || requirements.length >= 50; });
        recipeSections.forEach(section => { section.search.disabled = loading || busy || !catalog; });
        mixtureSections.forEach(section => {
            section.search.disabled = loading || busy || !catalog;
            section.root.querySelector('[data-qw-remove-mixture]').disabled = busy || loading;
        });
        dialog.querySelectorAll('[data-qw-items] tr').forEach(row => {
            const unavailable = loading || busy || productFor(items[Number(row.dataset.itemIndex)]?.presentation_id)?.unit_price == null;
            row.querySelectorAll('.qw-price button, .qw-price input').forEach(control => {
                control.disabled = unavailable || control.hidden;
            });
        });
        find('[data-qw-status]').textContent = busy ? 'Procesando...' : loading ? 'Cargando precios...' : '';
        icons();
    }
    function go(next) {
        closeResults();
        step = next;
        sync();
        find('.qw-body').scrollTop = 0;
        (step === 0 ? find('[name=category]:not(:disabled)') : step === 1 ? field('institution_id') : find('[data-qw-back]'))?.focus();
    }
    function filterHospitals() {
        const institution = field('institution_id').value;
        [...field('hospital_id').options].forEach(option => {
            if (!option.value) return;
            const allowed = JSON.parse(option.dataset.institutions).map(String).includes(institution);
            option.disabled = !allowed;
            option.hidden = !allowed;
        });
    }
    function closeResults(section) {
        if (!section) { [...mixtureSections, ...recipeSections].forEach(closeResults); return; }
        const { search, results, resultList } = section;
        results.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        resultList.querySelectorAll('[role=option]').forEach(option => option.setAttribute('aria-selected', 'false'));
    }
    function renderResults(section) {
        if (!section) { [...mixtureSections, ...recipeSections].forEach(renderResults); return; }
        const { search, results, resultList, root, number } = section;
        closeResults(section);
        resultList.replaceChildren();
        const status = root.querySelector('[data-qw-result-status]');
        status.hidden = true;
        status.textContent = '';
        if (!catalog || loading || busy || document.activeElement !== search) return;
        const term = normalize(search.value).trim();
        if (!term) return;
        const products = section.recipe ? [...new Map(catalog.products.map(product => [normalize(product.name), product])).values()] : catalog.products;
        const matches = products.filter(product => normalize(section.recipe ? product.name : `${product.name} ${product.brand} ${product.presentation}`).includes(term));
        for (const product of matches.slice(0, 5)) {
            const row = el('div', undefined, 'qw-result');
            row.id = section.recipe ? `${resultList.id}-option-${product.id}` : `qw-mixture-${number}-option-${product.id}`;
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', 'false');
            const title = el('div');
            title.append(el('strong', product.name));
            if (!section.recipe) title.append(el('small', [product.brand, product.presentation].filter(Boolean).join(' · ')));
            const price = el('span', product.unit_price == null ? 'Sin precio' : `${money(product.unit_price, 4)} / ${product.unit === 'ml' ? 'mL' : product.unit}`, 'qw-result-price');
            const disabled = section.recipe ? recipeRows(number).some(item => item !== section.recipe && normalize(item.medicine) === normalize(product.name))
                : product.unit_price == null || product.unit_price < 0 || mixtureItems(number).some(item => item.presentation_id === product.id) || items.length >= 50;
            row.setAttribute('aria-disabled', String(disabled));
            row.addEventListener('mousedown', event => event.preventDefault());
            row.addEventListener('click', () => {
                if (disabled) return;
                if (section.recipe) {
                    section.recipe.medicine = product.name; search.value = product.name; pricingToken = null;
                    closeResults(section); sync(); root.querySelector('[data-qw-recipe-concentration]').focus(); return;
                }
                items.push({ presentation_id: product.id, quantity: '', unit: product.unit, mixture_number: number });
                search.value = '';
                pricingToken = null; renderItems(); renderResults(); sync();
                mixtureSections[number - 1].root.querySelector('[data-qw-items]').lastElementChild.querySelector('input').focus();
            });
            row.append(title); if (!section.recipe) row.append(price); resultList.append(row);
        }
        if (!matches.length) {
            status.textContent = 'Sin coincidencias.';
            status.hidden = false;
        }
        results.hidden = false;
        search.setAttribute('aria-expanded', 'true');
    }
    function navigateResults(event, section) {
        const { search, results, resultList } = section;
        if (event.isComposing) return;
        if (event.key === 'Escape' && !results.hidden) {
            event.preventDefault(); event.stopPropagation(); closeResults(section); return;
        }
        if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
        event.preventDefault();
        // Enter in the search field selects a suggestion, never submits the quotation.
        if (results.hidden) {
            if (event.key === 'Enter') return;
            renderResults(section);
        }
        if (results.hidden) return;
        const options = [...resultList.querySelectorAll('[role=option][aria-disabled=false]')];
        if (!options.length) return;
        const active = options.findIndex(option => option.id === search.getAttribute('aria-activedescendant'));
        if (event.key === 'Enter') { options[Math.max(active, 0)].click(); return; }
        const next = event.key === 'ArrowDown' ? (active + 1) % options.length : (active <= 0 ? options.length : active) - 1;
        resultList.querySelectorAll('[role=option]').forEach(option => option.setAttribute('aria-selected', String(option === options[next])));
        search.setAttribute('aria-activedescendant', options[next].id);
        options[next].scrollIntoView({ block: 'nearest' });
    }
    function lineAmount(item) {
        const product = productFor(item.presentation_id);
        const quantity = Number(item.quantity);
        const price = priceFor(item);
        if (!product || price == null || !Number.isFinite(price) || price < 0 || !Number.isFinite(quantity) || quantity <= 0
            || (product.unit === 'frasco' && !Number.isInteger(quantity))) return 0;
        const base = round(quantity * price);
        return round(base + (product.vat ? round(base * .16) : 0));
    }
    function removeMixtureByNumber(number) {
        items = items.filter(item => item.mixture_number !== number);
        requirements = requirements.filter(row => row.mixture_number !== number);
        [...items, ...requirements].forEach(row => { if (row.mixture_number > number) row.mixture_number -= 1; });
        mixtureCount -= 1; pricingToken = null;
        renderRecipes(); renderItems(); sync();
        find(step === 1 ? '[data-qw-recipe-add-mixture]' : '[data-qw-add-mixture]').focus();
    }
    function renderRecipes() {
        closeResults(); recipeSections = [];
        const container = find('[data-qw-recipe-mixtures]'); container.replaceChildren();
        for (let number = 1; number <= mixtureCount; number++) {
            if (!recipeRows(number).length) requirements.push({ mixture_number: number, medicine: '', concentration: '' });
            const root = find('[data-qw-recipe-template]').content.firstElementChild.cloneNode(true);
            root.dataset.qwRecipeMixture = String(number); root.setAttribute('aria-label', `Receta de la mezcla ${number}`);
            root.querySelector('[data-qw-recipe-title]').textContent = `Mezcla ${number}`;
            root.querySelector('[data-qw-recipe-concentration-heading]').textContent = `Concentraci\u00f3n (${unit()})`;
            const remove = root.querySelector('[data-qw-recipe-remove-mixture]');
            remove.hidden = mixtureCount === 1; remove.title = `Eliminar mezcla ${number}`; remove.setAttribute('aria-label', remove.title);
            remove.addEventListener('click', () => removeMixtureByNumber(number));
            root.querySelector('[data-qw-recipe-add]').addEventListener('click', () => {
                if (requirements.length >= 50) return;
                requirements.push({ mixture_number: number, medicine: '', concentration: '' }); pricingToken = null;
                renderRecipes(); sync(); recipeSections.filter(section => section.number === number).at(-1).search.focus();
            });
            recipeRows(number).forEach((recipe, index) => {
                const row = el('tr'); row.dataset.qwRecipeRow = '';
                const medicine = el('td');
                const autocomplete = el('div', undefined, 'qw-autocomplete');
                const label = el('label', undefined, 'qw-search'); label.innerHTML = '<i data-qw-icon="search"></i>';
                const search = el('input'); search.type = 'search'; search.placeholder = 'Buscar medicamento'; search.autocomplete = 'off';
                search.required = true; search.maxLength = 255; search.value = recipe.medicine; search.dataset.qwRecipeMedicine = '';
                search.setAttribute('role', 'combobox'); search.setAttribute('aria-label', `Medicamento ${index + 1} de la receta, mezcla ${number}`);
                search.setAttribute('aria-autocomplete', 'list'); search.setAttribute('aria-haspopup', 'listbox'); search.setAttribute('aria-expanded', 'false');
                const results = el('div', undefined, 'qw-results'); results.hidden = true;
                const resultList = el('div'); resultList.id = `qw-recipe-${number}-${index}-results`;
                resultList.setAttribute('role', 'listbox'); resultList.setAttribute('aria-label', 'Medicamentos de la receta');
                search.setAttribute('aria-controls', resultList.id);
                const status = el('p', undefined, 'qw-result-status'); status.dataset.qwResultStatus = ''; status.hidden = true; status.setAttribute('role', 'status');
                results.append(resultList, status); label.append(search); autocomplete.append(label, results); medicine.append(autocomplete);
                const concentration = el('td'), group = el('div', undefined, 'qw-quantity');
                const input = el('input'); input.type = 'number'; input.inputMode = 'decimal'; input.min = '0.0001'; input.max = '1000000'; input.step = '0.0001'; input.required = true;
                input.placeholder = 'Capturar'; input.value = recipe.concentration; input.dataset.qwRecipeConcentration = '';
                input.setAttribute('aria-label', `Concentracion del medicamento ${index + 1}, mezcla ${number}, en ${unit()}`);
                input.addEventListener('input', () => { recipe.concentration = input.value; pricingToken = null; sync(); });
                group.append(input, el('span', unit())); concentration.append(group);
                const action = el('td'), button = el('button', undefined, 'quotation-icon-button'); button.type = 'button';
                button.title = `Eliminar medicamento ${index + 1} de la receta, mezcla ${number}`; button.setAttribute('aria-label', button.title);
                button.innerHTML = '<i data-qw-icon="trash-2"></i>';
                button.addEventListener('click', () => {
                    requirements.splice(requirements.indexOf(recipe), 1); pricingToken = null;
                    renderRecipes(); sync(); recipeSections.find(section => section.number === number).search.focus();
                });
                action.append(button); row.append(medicine, concentration, action); root.querySelector('[data-qw-recipe-rows]').append(row);
                const section = { root: row, number, recipe, search, results, resultList }; recipeSections.push(section);
                search.addEventListener('input', () => { recipe.medicine = search.value; pricingToken = null; sync(); renderResults(section); });
                search.addEventListener('focus', () => renderResults(section)); search.addEventListener('blur', () => closeResults(section));
                search.addEventListener('keydown', event => navigateResults(event, section));
            });
            container.append(root);
        }
        icons();
    }
    function renderPrice(cell, item, product) {
        cell.replaceChildren();
        const group = el('div', undefined, 'qw-price');
        const label = `${product?.name || 'medicamento'} (${product?.presentation || ''})`;
        const priceUnit = product?.unit === 'ml' ? 'mL' : product?.unit;
        const editing = !!item.editing_price;
        const previous = item.unit_price_override;
        const value = priceFor(item);
        const display = el('span', value == null ? 'Sin precio' : `${money(value, 4)} / ${priceUnit}`);
        display.hidden = editing;
        const input = el('input'); input.type = 'number'; input.min = '0'; input.max = '999999999.99';
        input.step = '0.0001'; input.inputMode = 'decimal'; input.required = true;
        input.value = value ?? ''; input.hidden = !editing; input.disabled = !editing;
        input.setAttribute('aria-label', `Precio unitario de ${label}`);
        const edit = el('button', undefined, 'quotation-icon-button'); edit.type = 'button';
        edit.title = `${editing ? 'Aplicar' : 'Editar'} precio unitario de ${label}`;
        edit.setAttribute('aria-label', edit.title);
        edit.innerHTML = `<i data-qw-icon="${editing ? 'check' : 'pencil'}"></i>`;
        edit.disabled = product?.unit_price == null;
        const restore = el('button', undefined, 'quotation-icon-button'); restore.type = 'button';
        restore.title = `Restablecer precio de lista: ${money(product?.unit_price || 0, 4)} / ${priceUnit}`;
        restore.setAttribute('aria-label', `Restablecer precio de lista de ${label}`);
        restore.innerHTML = '<i data-qw-icon="rotate-ccw"></i>';
        restore.hidden = item.unit_price_override == null;
        const refresh = () => { pricingToken = null; renderPrice(cell, item, product); updateEstimate(); icons(); };
        input.addEventListener('input', () => {
            item.unit_price_override = input.value; pricingToken = null; restore.hidden = false; updateEstimate();
        });
        edit.addEventListener('click', () => {
            if (editing && !input.reportValidity()) return;
            item.editing_price = !editing; refresh();
            cell.querySelector(editing ? 'button' : 'input').focus();
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); edit.click(); }
            if (event.key === 'Escape') {
                event.preventDefault(); event.stopPropagation();
                if (previous == null) delete item.unit_price_override; else item.unit_price_override = previous;
                item.editing_price = false; refresh(); cell.querySelector('button').focus();
            }
        });
        restore.addEventListener('click', () => {
            delete item.unit_price_override; item.editing_price = false;
            refresh(); cell.querySelector('button').focus();
        });
        group.append(display, input);
        if (editing) group.append(el('small', `/ ${priceUnit}`));
        group.append(edit, restore); cell.append(group);
    }
    function resetPrices() {
        items.forEach(item => { delete item.unit_price_override; item.editing_price = false; });
    }
    function updateEstimate() {
        dialog.querySelectorAll('[data-qw-amount]').forEach(cell => { cell.textContent = money(lineAmount(items[Number(cell.closest('tr').dataset.itemIndex)])); });
        const charges = (catalog?.charges || []).reduce((total, charge) => total + round(charge.total), 0);
        let total = 0;
        mixtureSections.forEach(section => {
            const selected = mixtureItems(section.number);
            const amount = round(selected.reduce((sum, item) => sum + lineAmount(item), selected.length ? charges : 0));
            section.root.querySelector('[data-qw-mixture-total]').textContent = `${money(amount)} MXN`;
            total += amount;
        });
        find('[data-qw-estimate]').textContent = `${money(total)} MXN`;
    }
    function renderItems() {
        closeResults();
        const container = find('[data-qw-mixtures]'); container.replaceChildren(); mixtureSections = [];
        for (let number = 1; number <= mixtureCount; number++) {
            const root = find('[data-qw-mixture-template]').content.firstElementChild.cloneNode(true);
            root.dataset.qwMixture = String(number);
            root.setAttribute('aria-label', `Mezcla ${number}`);
            root.querySelector('[data-qw-mixture-title]').textContent = `Mezcla ${number}`;
            const removeMixture = root.querySelector('[data-qw-remove-mixture]');
            removeMixture.hidden = mixtureCount === 1; removeMixture.title = `Eliminar mezcla ${number}`;
            removeMixture.setAttribute('aria-label', removeMixture.title);
            removeMixture.addEventListener('click', () => removeMixtureByNumber(number));
            const summary = el('dl', undefined, 'qw-recipe-summary');
            recipeRows(number).filter(recipe => recipe.medicine.trim()).forEach(recipe => {
                const pair = el('div'); pair.append(el('dt', recipe.medicine), el('dd', `${recipe.concentration} ${unit()}`)); summary.append(pair);
            });
            if (summary.childElementCount) root.querySelector('.qw-mixture-header').after(summary);
            const search = root.querySelector('[data-qw-search]'), resultList = root.querySelector('[data-qw-result-list]');
            resultList.id = `qw-medication-results-${number}`; search.setAttribute('aria-controls', resultList.id);
            const section = { root, number, search, resultList, results: root.querySelector('[data-qw-results]') };
            mixtureSections.push(section); container.append(root);
            search.addEventListener('input', () => renderResults(section));
            search.addEventListener('focus', () => renderResults(section));
            search.addEventListener('blur', () => closeResults(section));
            search.addEventListener('keydown', event => navigateResults(event, section));
            const quantityHeading = root.querySelector('[data-qw-quantity-heading]');
            quantityHeading.classList.toggle('qw-bottle-count', catalog?.billing_mode === 'frasco');
            quantityHeading.textContent = catalog?.billing_mode === 'frasco' ? 'Cantidad de frascos'
                : catalog?.billing_mode === 'mixed' ? 'Cantidad solicitada' : 'Concentraci\u00f3n disponible';
            const body = root.querySelector('[data-qw-items]');
            items.forEach((item, index) => {
                if (item.mixture_number !== number) return;
                const product = productFor(item.presentation_id);
                const row = el('tr'); row.dataset.itemIndex = String(index);
                const name = el('td'); name.append(el('strong', product?.name || 'Producto no disponible'), el('small', product ? [product.brand, product.presentation].filter(Boolean).join(' · ') : ''));
                const concentration = el('td'); const group = el('div', undefined, 'qw-quantity');
                const bottle = (product?.unit || item.unit) === 'frasco';
                const input = el('input'); input.type = 'number'; input.min = bottle ? '1' : '0.0001'; input.max = '1000000'; input.step = bottle ? '1' : 'any'; input.required = true;
                input.inputMode = bottle ? 'numeric' : 'decimal';
                input.value = item.quantity; input.setAttribute('aria-label', bottle ? `Cantidad de frascos de ${product?.name || 'medicamento'}`
                    : `Concentracion de ${product?.name || 'medicamento'} en ${unit()}`);
                input.addEventListener('input', () => { item.quantity = input.value; pricingToken = null; updateEstimate(); });
                group.append(input, el('span', bottle ? 'frascos' : unit())); concentration.append(group);
                const price = el('td'); renderPrice(price, item, product);
                const amount = el('td'); amount.dataset.qwAmount = '';
                const action = el('td'); const remove = el('button', undefined, 'quotation-icon-button');
                remove.type = 'button'; remove.title = 'Eliminar medicamento'; remove.setAttribute('aria-label', `Eliminar ${product?.name || 'medicamento'}`);
                remove.innerHTML = '<i data-qw-icon="trash-2"></i>';
                remove.addEventListener('click', () => { items.splice(index, 1); pricingToken = null; renderItems(); sync(); mixtureSections[number - 1].search.focus(); });
                action.append(remove); row.append(name, concentration, price, amount, action); body.append(row);
            });
            root.querySelector('[data-qw-empty]').hidden = mixtureItems(number).length > 0;
        }
        updateEstimate(); icons();
    }
    async function loadCatalog(preserve = false) {
        controller?.abort();
        const current = new AbortController(); controller = current;
        catalog = null; pricingToken = null; loading = false; error();
        if (!preserve) items = [];
        if (!category || !field('hospital_id').value) { renderRecipes(); renderItems(); renderResults(); sync(); return; }
        loading = true; renderResults(); sync();
        const params = new URLSearchParams({ flow: 'commercial', category, hospital_id: field('hospital_id').value, no_commercial_relationship: noCommercial() ? '1' : '0' });
        if (noCommercial()) params.set('billing_mode', field('billing_mode').value || 'unit');
        try {
            const data = await request(`${dialog.dataset.optionsUrl}?${params}`, { signal: current.signal });
            if (current !== controller || !dialog.open) return;
            catalog = data;
            items.forEach(item => {
                const product = productFor(item.presentation_id);
                if (!product) return;
                // A change of billing unit requires a new quantity, never reinterpret mg/mL as bottles.
                if (item.unit !== product.unit) {
                    item.quantity = ''; delete item.unit_price_override; item.editing_price = false;
                }
                item.unit = product.unit;
            });
            if (!noCommercial()) {
                dialog.querySelectorAll('[name=billing_mode]').forEach(input => { input.checked = input.value === catalog.billing_mode; });
            }
            const missing = items.some(item => !productFor(item.presentation_id));
            if (missing) error('Hay medicamentos que no estan disponibles en esta lista. Retiralos o selecciona otra lista.');
        } catch (failure) {
            if (failure.name !== 'AbortError' && current === controller) {
                error(failure.message);
            }
        } finally {
            if (current === controller) { loading = false; renderRecipes(); renderItems(); renderResults(); sync(); }
        }
    }
    function payload(action = 'save') {
        return { flow: 'commercial', category, hospital_id: Number(field('hospital_id').value), institution_id: Number(field('institution_id').value),
            ...(field('seller_id') ? { seller_id: field('seller_id').value || null } : {}),
            no_commercial_relationship: noCommercial(), ...(noCommercial() ? { billing_mode: field('billing_mode').value } : {}),
            patient_name: field('patient_name').value, observations: field('observations').value,
            patient_paternal_surname: field('patient_paternal_surname').value,
            patient_maternal_surname: field('patient_maternal_surname').value,
            patient_platform_id: field('patient_platform_id').value,
            mixture_count: mixtureCount,
            requirements: requirements.map(requirement => ({ mixture_number: requirement.mixture_number,
                medicine: requirement.medicine.trim(), concentration: requirement.concentration === '' ? null : Number(requirement.concentration) }))
                .filter(requirement => requirement.medicine !== '' || requirement.concentration !== null),
            items: [...items].sort((a, b) => a.mixture_number - b.mixture_number).map(item => ({ presentation_id: item.presentation_id, mixture_number: item.mixture_number,
                [item.unit === 'frasco' ? 'bottle_count' : 'concentration']: Number(item.quantity),
                ...(item.unit_price_override != null ? { unit_price_override: Number(item.unit_price_override) } : {}) })),
            action, submission_key: submissionKey, ...(pricingToken ? { pricing_token: pricingToken } : {}) };
    }
    function review(snapshot, document = {}) {
        const metadata = find('[data-qw-review-meta]'); metadata.replaceChildren();
        for (const [label, value] of [['Categoria', `${labels[category]} · ${unit()}`], ['Institucion', field('institution_id').selectedOptions[0].text],
            ['Hospital', field('hospital_id').selectedOptions[0].text], ['Lista de precios', snapshot.price_list.name],
            ['Sin relacion comercial', noCommercial() ? 'Si' : 'No'], ['Cobro', snapshot.billing_mode === 'mixed' ? 'Segun medicamento' : snapshot.billing_mode === 'frasco' ? 'Por frasco' : `Por ${unit()}`]]) {
            const pair = el('div'); pair.append(el('dt', label), el('dd', value)); metadata.append(pair);
        }
        const body = find('[data-qw-review-lines]'); body.replaceChildren();
        (snapshot.requirements || []).forEach(requirement => {
            const pair = el('div');
            pair.append(el('dt', `Requerimiento de la mezcla ${requirement.mixture_number}`),
                el('dd', `${requirement.medicine}: ${requirement.concentration} ${requirement.unit === 'ml' ? 'mL' : 'mg'}`));
            metadata.append(pair);
        });
        const unitPrice = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2, maximumFractionDigits: 4 });
        const quantity = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 4 });
        const unitLabels = { frasco: 'Frasco', ml: 'mL', mg: 'mg', servicio: 'Servicio' };
        snapshot.lines.forEach(line => {
            const row = el('tr'); const description = el('td');
            description.append(el('strong', line.description), el('small', line.presentation));
            if (line.mixture_number) description.prepend(el('small', `Mezcla ${line.mixture_number}`));
            if (line.concentration) description.append(el('small', `${line.concentration} ${line.concentration_unit === 'ml' ? 'mL' : 'mg'}`));
            if (line.vat > 0) description.append(el('small', `IVA incluido en el importe: ${money(line.vat)}`));
            row.append(el('td', quantity.format(line.quantity)), el('td', unitLabels[line.unit] || line.unit), description,
                el('td', unitPrice.format(line.unit_price)), el('td', money(line.total)));
            body.append(row);
        });
        find('[data-qw-recipient]').textContent = field('hospital_id').selectedOptions[0].text;
        const date = documentIdentity || document;
        find('[data-qw-date]').textContent = date.date || '';
        find('[data-qw-date]').dateTime = date.date_iso || '';
        find('[data-qw-folio]').hidden = !documentIdentity?.folio;
        find('[data-qw-folio]').textContent = documentIdentity?.folio ? `Folio: ${documentIdentity.folio}` : '';
        const tax = round(snapshot.lines.reduce((sum, line) => sum + Number(line.vat || 0), 0));
        find('[data-qw-tax-summary]').hidden = tax === 0;
        find('[data-qw-subtotal]').textContent = money(round(snapshot.total - tax));
        find('[data-qw-tax]').textContent = money(tax);
        find('[data-qw-total]').textContent = `${money(snapshot.total)} MXN`;
        find('[data-qw-total-words]').textContent = document.total_in_words || '';
        find('[data-qw-total-words]').hidden = !document.total_in_words;
        const considerations = find('[data-qw-considerations]'); considerations.replaceChildren();
        snapshot.lines.filter(line => line.unit === 'servicio').forEach(line => {
            const mixture = line.mixture_number ? `Mezcla ${line.mixture_number}: ` : '';
            considerations.append(el('p', `${mixture}${line.description}: ${money(line.total)} MXN${line.vat > 0 ? ' (IVA incluido)' : ''}.`));
        });
        if (tax > 0) considerations.append(el('p', `El total cotizado incluye ${money(tax)} MXN de IVA, conforme al desglose de los conceptos.`));
        find('[data-qw-observations]').textContent = field('observations').value.trim();
        find('[data-qw-observations]').hidden = !field('observations').value.trim();
    }
    async function preview() {
        if (!form.reportValidity() || !completeMixtures() || !catalog) return;
        const data = payload(); const current = version;
        busy = true; error(); sync();
        try {
            const result = await request(dialog.dataset.previewUrl, { method: 'POST', body: JSON.stringify(data) });
            if (current !== version) return;
            pricingToken = result.pricing_token; review(result.pricing_snapshot, result.document); step = 3;
        } catch (failure) { error(failure.message); }
        finally { busy = false; sync(); if (step === 3) { find('.qw-body').scrollTop = 0; find('[data-qw-back]').focus(); } }
    }
    async function save(action) {
        if (busy || !pricingToken) return;
        const data = payload(action); busy = true; error(); sync();
        try {
            const result = await request(updateUrl || dialog.dataset.storeUrl, { method: updateUrl ? 'PUT' : 'POST', body: JSON.stringify(data) });
            location.assign(result.redirect_url);
        } catch (failure) { busy = false; pricingToken = null; go(2); error(failure.message); }
    }
    function reset() {
        controller?.abort(); controller = null; version += 1;
        form.reset(); form.querySelectorAll('[data-qw-inactive-seller]').forEach(option => option.remove());
        step = 0; category = ''; catalog = null; items = []; loading = false; busy = false; updateUrl = null; pricingToken = null; documentIdentity = null;
        mixtureCount = 1; requirements = [];
        submissionKey = crypto.randomUUID(); error();
        find('#quote-wizard-title').textContent = 'Nueva cotizacion';
        filterHospitals(); renderRecipes(); renderItems(); renderResults(); sync();
        if (!dialog.open) dialog.showModal();
        go(0);
    }
    document.querySelector('[data-quotation-new]')?.addEventListener('click', reset);
    dialog.querySelectorAll('[name=category]').forEach(input => input.addEventListener('change', () => {
        if (category !== input.value) { catalog = null; items = []; mixtureCount = 1; requirements = []; pricingToken = null; }
        category = input.value; renderRecipes(); renderItems(); sync();
    }));
    field('institution_id').addEventListener('change', () => { field('hospital_id').value = ''; filterHospitals(); loadCatalog(); });
    field('hospital_id').addEventListener('change', () => loadCatalog());
    field('no_commercial_relationship').addEventListener('change', () => {
        if (noCommercial() && !field('billing_mode').value) field('billing_mode').value = 'unit';
        resetPrices();
        loadCatalog(true);
    });
    dialog.querySelectorAll('[name=billing_mode]').forEach(input => input.addEventListener('change', () => { resetPrices(); loadCatalog(true); }));
    function addMixture() {
        if (loading || busy || !catalog || mixtureCount >= 50 || items.length >= 50 || requirements.length >= 50) return;
        mixtureCount += 1; pricingToken = null; renderRecipes(); renderItems(); go(1); recipeSections.at(-1).search.focus();
    }
    find('[data-qw-add-mixture]').addEventListener('click', addMixture);
    find('[data-qw-recipe-add-mixture]').addEventListener('click', addMixture);
    find('[data-qw-back]').addEventListener('click', () => { if (busy) return; error(); if (step === 0) dialog.close(); else go(step - 1); });
    find('[data-qw-close]').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    dialog.addEventListener('close', () => { version += 1; controller?.abort(); });
    find('[data-qw-save]').addEventListener('click', () => save('save'));
    form.addEventListener('submit', event => {
        event.preventDefault(); if (busy || loading) return;
        if (step === 0 && category) { go(1); loadCatalog(true); }
        else if (step === 1 && catalog && completeRecipe() && form.reportValidity()) { error(); renderItems(); go(2); }
        else if (step === 2) preview();
        else if (step === 3) save('send');
    });
    document.querySelectorAll('[data-quotation-edit][data-quotation-flow="commercial"]').forEach(button => button.addEventListener('click', async () => {
        reset(); const current = version; busy = true; sync();
        try {
            const data = await request(button.dataset.quotationEdit);
            if (current !== version || !dialog.open) return;
            if (!data.editable) throw new Error('Esta cotizacion ya no se puede editar.');
            updateUrl = data.update_url; documentIdentity = { ...data.document, folio: data.folio }; const capture = data.clinical_data;
            category = capture.category; field('category').value = category;
            field('institution_id').value = capture.institution_id; filterHospitals(); field('hospital_id').value = capture.hospital_id;
            field('no_commercial_relationship').checked = !!capture.no_commercial_relationship;
            field('billing_mode').value = capture.billing_mode || 'unit';
            field('patient_name').value = capture.patient_name || ''; field('observations').value = capture.observations || '';
            field('patient_paternal_surname').value = capture.patient_paternal_surname || '';
            field('patient_maternal_surname').value = capture.patient_maternal_surname || '';
            field('patient_platform_id').value = capture.patient_platform_id || '';
            if (field('seller_id')) {
                if (data.seller_id && ![...field('seller_id').options].some(option => option.value === String(data.seller_id))) {
                    const option = new Option(`${data.seller_name} (no disponible)`, data.seller_id); option.dataset.qwInactiveSeller = ''; field('seller_id').add(option);
                }
                field('seller_id').value = data.seller_id || '';
            }
            items = capture.items.map((item, index) => {
                const line = data.pricing_snapshot?.lines[index];
                const bottle = item.bottle_count != null || line?.unit === 'frasco';
                // Older commercial drafts captured concentration; use their already quoted bottle count.
                return { presentation_id: item.presentation_id, mixture_number: item.mixture_number ?? (category === 'nutricionales' ? 1 : index + 1),
                    quantity: bottle ? (item.bottle_count ?? line.quantity) : item.concentration,
                    ...(item.unit_price_override != null ? { unit_price_override: item.unit_price_override } : {}),
                    unit: bottle ? 'frasco' : category === 'nutricionales' ? 'ml' : 'mg' };
            });
            mixtureCount = capture.mixture_count ?? Math.max(1, ...items.map(item => item.mixture_number));
            requirements = (capture.requirements || []).map(requirement => ({ mixture_number: requirement.mixture_number,
                medicine: requirement.medicine || '', concentration: requirement.concentration ?? '' }));
            busy = false; find('#quote-wizard-title').textContent = `Editar ${data.folio}`; go(2); await loadCatalog(true);
        } catch (failure) { if (current === version) { busy = false; sync(); error(failure.message); } }
    }));
    icons();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCommercialQuotation, { once: true });
else initCommercialQuotation();
