import { createElement, ChevronLeft, ChevronRight } from 'lucide';
import { createProductPicker } from './purchase-product-picker';
import '../css/purchase-order.css';

function initializePurchaseOrder() {
    const form = document.getElementById('purchase-order-form');
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = 'true';
    form.noValidate = true;
    const config = JSON.parse(document.getElementById('purchase-order-config').textContent);
    const hiddenInputs = document.getElementById('item-hidden-inputs');
    const discountInput = document.getElementById('discount');
    const taxRateInput = document.getElementById('tax_rate');
    const deliveryLaboratorySelect = document.getElementById('delivery_laboratory_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    const inventoryDestinationSelect = document.getElementById('inventory_destination');
    const deliveryAttentionInput = document.getElementById('delivery_attention');
    const deliveryAddressInput = document.getElementById('delivery_address');
    const deliveryDestinationSummary = document.getElementById('delivery_destination_summary');
    const deliveryDestinations = config.destinations;
    const supplierCatalog = config.suppliers;
    const supplierInput = document.getElementById('supplier');
    const slots = [0, 1].map((slot) => ({
        slot,
        part: document.querySelector(`[data-item-part][data-item-slot="${slot}"]`),
        description: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="description"]`),
        quantity: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="quantity"]`),
        unitPrice: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="unit_price"]`),
        subtotal: document.querySelector(`[data-item-subtotal][data-item-slot="${slot}"]`),
        removeButton: document.querySelector(`[data-remove-item-slot="${slot}"]`),
        toggle: document.querySelector(`[data-product-toggle="${slot}"]`),
    }));

    let items = config.items;
    let currentPage = 0;
    let products = new Map();
    let catalogReady = false;
    let catalogRequest = null;
    const catalogStatus = document.getElementById('po-catalog-status');
    const catalogMessage = document.getElementById('po-catalog-message');
    const retryCatalog = document.getElementById('po-catalog-retry');
    const previousPage = document.getElementById('po-previous-items');
    const nextPage = document.getElementById('po-next-items');
    previousPage.append(createElement(ChevronLeft, { width: 16, height: 16, 'aria-hidden': 'true' }));
    nextPage.append(createElement(ChevronRight, { width: 16, height: 16, 'aria-hidden': 'true' }));

    items = items.length ? items.map((item) => ({
        product_key: item.product_key ?? '',
        description: item.description ?? '',
        quantity: item.quantity ?? 1,
        unit_price: item.unit_price ?? 0,
    })) : [{ description: '', quantity: 1, unit_price: 0 }];

    const picker = createProductPicker(slots, (slotIndex, product) => {
        const itemIndex = currentPage * 2 + slotIndex;
        // The sheet's spare row becomes a real line only when a product is entered.
        if (!items[itemIndex]) {
            if (!catalogReady || !products.size || itemIndex !== items.length) return;
            saveVisibleItems();
            items.push({
                product_key: product?.product_key ?? '',
                description: slots[slotIndex].description.value,
                quantity: 1,
                unit_price: 0,
            });
            renderPage();
        }
        const item = items[itemIndex];
        if (item.product_key && item.product_key !== product?.product_key) {
            slots[slotIndex].unitPrice.value = 0;
        }
        item.product_key = product?.product_key ?? '';
        validateDescription(slots[slotIndex], item);
        updateTotals();
    });

    function validateDescription(slot, item) {
        slot.description.setCustomValidity(item && !products.has(item.product_key)
            ? 'Selecciona un producto del catálogo del almacén y subalmacén.' : '');
    }

    async function loadProducts(reset = false) {
        saveVisibleItems();
        catalogRequest?.abort();
        const request = new AbortController();
        catalogRequest = request;
        picker.setProducts([]);
        catalogReady = false;
        products = new Map();
        retryCatalog.hidden = true;
        if (reset) {
            items.forEach((item) => {
                item.product_key = '';
                item.description = '';
                item.unit_price = 0;
            });
        }
        const ready = selectedDeliveryLaboratory() && selectedWarehouse() && inventoryDestinationSelect.value;
        catalogStatus.dataset.state = ready ? 'loading' : 'pending';
        catalogMessage.textContent = ready ? 'Cargando catálogo...' : 'Catálogo pendiente: central, almacén y subalmacén.';
        renderPage();
        if (!ready) return;

        const url = new URL(config.productsUrl, window.location.href);
        url.search = new URLSearchParams({
            delivery_laboratory_id: deliveryLaboratorySelect.value,
            warehouse_id: warehouseSelect.value,
            inventory_destination: inventoryDestinationSelect.value,
        }).toString();
        try {
            const response = await fetch(url, { signal: request.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Catalog request failed');
            const data = await response.json();
            if (request.signal.aborted || catalogRequest !== request) return;
            if (!Array.isArray(data.products)) throw new Error('Invalid catalog response');
            // Keep edits made to quantities while the catalog request was in flight.
            saveVisibleItems();
            products = new Map(data.products.map((product) => [product.product_key, product]));
            items.forEach((item) => {
                const product = products.get(item.product_key);
                if (product) item.description = product.description;
                else item.product_key = '';
            });
            picker.setProducts(data.products);
            catalogReady = true;
            catalogStatus.dataset.state = 'ready';
            catalogMessage.textContent = products.size
                ? `${products.size} ${products.size === 1 ? 'producto' : 'productos'} en el catálogo seleccionado`
                : 'Sin productos registrados en este almacén y subalmacén.';
            renderPage();
        } catch (error) {
            if (request.signal.aborted || catalogRequest !== request) return;
            catalogStatus.dataset.state = 'error';
            catalogMessage.textContent = 'No se pudo cargar el catálogo. Reintenta la consulta.';
            retryCatalog.hidden = false;
        }
    }

    const money = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    });

    function numberValue(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function applyCatalogSupplier() {
        const selectedName = supplierInput.value.trim().toLocaleLowerCase('es-MX');
        const supplier = supplierCatalog.find((item) =>
            String(item.name ?? '').trim().toLocaleLowerCase('es-MX') === selectedName
        );

        if (!supplier) return;

        const values = {
            supplier_rfc: supplier.rfc,
            supplier_contact: supplier.contact_name,
            supplier_phone: supplier.phone,
            supplier_email: supplier.email,
            supplier_fax: supplier.fax || 'N/A',
            order_type: supplier.category,
            supplier_address: supplier.address,
            supplier_bank_details: supplier.bank_details,
        };

        Object.entries(values).forEach(([id, value]) => {
            document.getElementById(id).value = value ?? '';
        });
    }

    function pageCount() {
        return Math.max(1, Math.ceil(items.length / 2));
    }

    function selectedDeliveryLaboratory() {
        return deliveryDestinations.find((item) => item.id === deliveryLaboratorySelect.value);
    }

    function selectedWarehouse() {
        const destination = selectedDeliveryLaboratory();
        return destination?.warehouses.find((item) => item.id === warehouseSelect.value);
    }

    function syncDeliveryFields() {
        const destination = selectedDeliveryLaboratory();
        const warehouse = selectedWarehouse();
        const inventoryLabel = inventoryDestinationSelect.selectedOptions[0]?.dataset.label ?? '';

        deliveryAttentionInput.value = warehouse
            ? `${warehouse.name} - ${destination.name}`
            : (destination?.name ?? '');
        deliveryDestinationSummary.textContent = [destination?.name, warehouse?.name, inventoryLabel]
            .filter(Boolean)
            .join(' / ');
        deliveryDestinationSummary.title = deliveryDestinationSummary.textContent;
    }

    function renderWarehouses() {
        const destination = selectedDeliveryLaboratory();
        warehouseSelect.replaceChildren();
        warehouseSelect.append(new Option('Almacén: Selecciona', ''));
        warehouseSelect.disabled = !destination?.warehouses.length;
        inventoryDestinationSelect.value = '';
        inventoryDestinationSelect.disabled = true;

        if (!destination?.warehouses.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Sin almacenes registrados';
            option.disabled = true;
            option.selected = true;
            warehouseSelect.appendChild(option);
            deliveryAddressInput.value = destination?.address ?? '';
            syncDeliveryFields();
            return;
        }

        destination.warehouses.forEach((warehouse) => {
            const option = document.createElement('option');
            option.value = warehouse.id;
            option.dataset.name = warehouse.name;
            option.textContent = `Almac\u00e9n: ${warehouse.name}`;
            warehouseSelect.appendChild(option);
        });

        const warehouse = selectedWarehouse();
        deliveryAddressInput.value = warehouse?.address || destination.address || '';
        syncDeliveryFields();
    }

    function updateDeliveryAddress() {
        const destination = selectedDeliveryLaboratory();
        const warehouse = selectedWarehouse();
        deliveryAddressInput.value = warehouse?.address || destination?.address || '';
        inventoryDestinationSelect.value = '';
        inventoryDestinationSelect.disabled = !warehouse;
        syncDeliveryFields();
    }

    function saveVisibleItems() {
        slots.forEach(({ slot, description, quantity, unitPrice }) => {
            const itemIndex = currentPage * 2 + slot;
            if (itemIndex >= items.length) return;

            items[itemIndex] = {
                ...items[itemIndex],
                description: description.value,
                quantity: quantity.value,
                unit_price: unitPrice.value,
            };
        });
    }

    function updateTotals() {
        saveVisibleItems();
        let subtotal = 0;

        items.forEach((item) => {
            subtotal += numberValue(item.quantity) * numberValue(item.unit_price);
        });

        slots.forEach(({ slot, subtotal: subtotalCell }) => {
            const item = items[currentPage * 2 + slot];
            subtotalCell.textContent = item
                ? money.format(numberValue(item.quantity) * numberValue(item.unit_price))
                : '';
        });

        const discount = Math.min(Math.max(numberValue(discountInput.value), 0), subtotal);
        const discounted = Math.max(subtotal - discount, 0);
        const tax = discounted * (Math.max(numberValue(taxRateInput.value), 0) / 100);

        document.getElementById('subtotal-display').textContent = money.format(subtotal);
        document.getElementById('discounted-display').textContent = money.format(discounted);
        document.getElementById('tax-display').textContent = money.format(tax);
        document.getElementById('total-display').textContent = money.format(discounted + tax);
    }

    function renderPage() {
        picker.close();
        slots.forEach(({ slot, part, description, quantity, unitPrice, subtotal, removeButton, toggle }) => {
            const itemIndex = currentPage * 2 + slot;
            const item = items[itemIndex];
            const inputs = [description, quantity, unitPrice];

            if (item) {
                part.textContent = itemIndex + 1;
                description.value = item.description ?? '';
                quantity.value = item.quantity ?? 1;
                unitPrice.value = item.unit_price ?? 0;
                inputs.forEach((input) => {
                    input.disabled = false;
                    input.required = true;
                });
                subtotal.textContent = money.format(numberValue(item.quantity) * numberValue(item.unit_price));
                removeButton.hidden = itemIndex === 0;
                removeButton.disabled = itemIndex === 0;
                removeButton.title = `Eliminar partida ${itemIndex + 1}`;
                removeButton.setAttribute('aria-label', `Eliminar partida ${itemIndex + 1}`);
            } else {
                part.textContent = '';
                description.value = '';
                quantity.value = '';
                unitPrice.value = '';
                inputs.forEach((input) => {
                    input.disabled = true;
                    input.required = false;
                });
                subtotal.textContent = '';
                removeButton.hidden = true;
                removeButton.disabled = true;
            }
            description.disabled = !catalogReady || !products.size;
            description.placeholder = catalogReady
                ? (products.size ? 'Buscar producto' : 'Sin productos en este subalmacén')
                : (catalogStatus.dataset.state === 'loading' ? 'Cargando catálogo...' : 'Selecciona central, almacén y subalmacén');
            toggle.disabled = description.disabled;
            description.setAttribute('aria-label', `Descripción de la partida ${itemIndex + 1}`);
            toggle.setAttribute('aria-label', `Mostrar productos de la partida ${itemIndex + 1}`);
            validateDescription(slots[slot], item);
        });

        previousPage.disabled = currentPage === 0;
        nextPage.disabled = currentPage >= pageCount() - 1;
        document.getElementById('po-items-page').textContent = `${currentPage * 2 + 1}-${Math.min(currentPage * 2 + 2, items.length)} de ${items.length}`;
        updateTotals();
    }

    function buildHiddenInputs() {
        hiddenInputs.replaceChildren();

        items.forEach((item, index) => {
            ['product_key', 'description', 'quantity', 'unit_price'].forEach((field) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${field}]`;
                input.value = item[field] ?? '';
                hiddenInputs.appendChild(input);
            });
        });
    }

    function validateItems() {
        if (!selectedDeliveryLaboratory() || !selectedWarehouse() || !inventoryDestinationSelect.value) {
            const target = !selectedDeliveryLaboratory() ? deliveryLaboratorySelect
                : !selectedWarehouse() ? warehouseSelect : inventoryDestinationSelect;
            target.focus();
            target.reportValidity();
            return false;
        }
        if (!catalogReady || !products.size) {
            catalogStatus.focus();
            return false;
        }
        const invalidIndex = items.findIndex((item) =>
            !products.has(item.product_key)
            || numberValue(item.quantity) <= 0
            || String(item.unit_price).trim() === ''
            || !Number.isFinite(Number(item.unit_price))
            || numberValue(item.unit_price) < 0
        );

        if (invalidIndex === -1) return true;

        currentPage = Math.floor(invalidIndex / 2);
        renderPage();
        const slot = slots[invalidIndex % 2];
        const target = !products.has(items[invalidIndex].product_key)
            ? slot.description
            : (numberValue(items[invalidIndex].quantity) <= 0 ? slot.quantity : slot.unitPrice);
        target.focus();
        target.reportValidity();
        return false;
    }

    slots.forEach(({ quantity, unitPrice }) => {
        [quantity, unitPrice].forEach((input) => input.addEventListener('input', updateTotals));
    });

    document.getElementById('add-item').addEventListener('click', function () {
        saveVisibleItems();
        items.push({ description: '', quantity: 1, unit_price: 0 });
        currentPage = Math.floor((items.length - 1) / 2);
        renderPage();
        slots[(items.length - 1) % 2].description.focus();
    });

    slots.forEach(({ slot, removeButton }) => {
        removeButton.addEventListener('click', function () {
            const itemIndex = currentPage * 2 + slot;
            if (itemIndex === 0 || itemIndex >= items.length) return;

            saveVisibleItems();
            items.splice(itemIndex, 1);
            currentPage = Math.min(currentPage, pageCount() - 1);
            renderPage();
        });
    });

    discountInput.addEventListener('input', updateTotals);
    supplierInput.addEventListener('change', applyCatalogSupplier);
    deliveryLaboratorySelect.addEventListener('change', () => { renderWarehouses(); loadProducts(true); });
    warehouseSelect.addEventListener('change', () => { updateDeliveryAddress(); loadProducts(true); });
    inventoryDestinationSelect.addEventListener('change', () => { syncDeliveryFields(); loadProducts(true); });
    retryCatalog.addEventListener('click', () => loadProducts());
    [previousPage, nextPage].forEach((button, index) => button.addEventListener('click', () => {
        saveVisibleItems();
        currentPage = Math.max(0, Math.min(currentPage + (index ? 1 : -1), pageCount() - 1));
        renderPage();
    }));

    form.addEventListener('submit', function (event) {
        syncDeliveryFields();
        saveVisibleItems();
        if (!validateItems()) {
            event.preventDefault();
            return;
        }
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }
        buildHiddenInputs();
    });

    renderPage();
    loadProducts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePurchaseOrder);
} else {
    initializePurchaseOrder();
}
