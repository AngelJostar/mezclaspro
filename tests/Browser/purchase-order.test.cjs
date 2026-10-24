const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const source = buildSync({ entryPoints: [path.join(root, 'resources/js/purchase-order.js')], bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const products = [
    { product_key: 'oncologicos:1', description: 'Pembrolizumab - Frasco 100 mg / 4 ml - Keytruda' },
    { product_key: 'oncologicos:2', description: 'Ácido folínico - Ampolleta 50 mg - Marca prueba' },
];
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture({ width = 1440, old = {}, onCatalog, suppliers = null } = {}) {
    const body = execFileSync('php', ['tests/Browser/fixtures/purchase-order.php', JSON.stringify(old), '', JSON.stringify(suppliers)], { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    const posts = [];
    const queries = [];
    page.on('pageerror', error => errors.push(error.message));
    // All requests, including submission, are intercepted. Never create a live purchase order.
    await page.route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        if (url.pathname.endsWith('/productos')) {
            queries.push(Object.fromEntries(url.searchParams));
            if (onCatalog) return onCatalog(route, url);
            const matches = url.searchParams.get('warehouse_id') === '11' && url.searchParams.get('inventory_destination') === 'oncologicos';
            return route.fulfill({ json: { products: matches ? products : [] } });
        }
        if (request.method() === 'POST') {
            posts.push(new URLSearchParams(request.postData()));
            return route.fulfill({ contentType: 'text/html', body: 'Orden de prueba recibida' });
        }
        if (url.pathname.endsWith('/promesa-logo.png')) {
            return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
        }
        if (url.pathname !== '/') return route.abort();
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}body{margin:0;padding:20px;background:#f1f5f9}.po-editor{max-width:1500px;margin:auto}</style></head><body>${body}<script>${source}</script></body></html>` });
    });
    await page.goto('http://purchase-order.test/');
    return { page, errors, posts, queries };
}

async function selectDestination(page) {
    await page.getByLabel('Central receptora').selectOption('1');
    await page.getByLabel('Almacén receptor', { exact: true }).selectOption('11');
    await page.getByLabel('Subalmacén receptor').selectOption('oncologicos');
    await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
}

test('supplier search supports names, accents, RFC, keyboard selection and the full dropdown; replaces all supplier fields', async () => {
    const suppliers = [
        { name: 'Báxter México', commercial_name: 'Soluciones médicas', rfc: 'BAX010101AAA', contact_name: 'Ana Pérez', phone: '5512345678', email: 'ana@example.test', fax: '1234', category: 'Medicamentos', address: 'Calle Central 10', bank_details: 'Banco: Banco Prueba\nCuenta: 123456\nCLABE: 012345678901234567' },
        { name: 'Otro proveedor', commercial_name: 'Insumos', rfc: 'OTR020202BBB', contact_name: 'Luis Ramos', phone: '5587654321', email: null, fax: null, category: 'Material de curación', address: 'Avenida Sur 20', bank_details: null },
    ];
    const { page, errors, posts } = await fixture({ suppliers });
    try {
        const input = page.getByRole('combobox', { name: 'Proveedor', exact: true });
        const options = page.locator('#po-supplier-options');
        await input.fill('bax');
        assert.equal(await options.getByRole('option').count(), 1);
        await input.press('ArrowDown'); await input.press('Enter');
        assert.equal(await input.inputValue(), suppliers[0].name);
        for (const [id, key] of Object.entries({ supplier_rfc: 'rfc', supplier_bank_details: 'bank_details', supplier_address: 'address', supplier_contact: 'contact_name', supplier_phone: 'phone', supplier_email: 'email' })) {
            assert.equal(await page.locator('#' + id).inputValue(), suppliers[0][key]);
        }
        assert.equal(posts.length, 0);
        await page.getByRole('button', { name: 'Mostrar proveedores registrados' }).click();
        assert.equal(await options.getByRole('option').count(), 2);
        await options.getByRole('option').nth(1).click();
        assert.equal(await input.inputValue(), suppliers[1].name);
        assert.equal(await page.locator('#supplier_bank_details').inputValue(), '');
        assert.equal(await page.locator('#supplier_email').inputValue(), '');
        assert.equal(await page.locator('#supplier_phone').inputValue(), suppliers[1].phone);
        assert.equal(await page.locator('#supplier_address').inputValue(), suppliers[1].address);
        await input.fill('bax010101');
        assert.equal(await page.locator('#supplier_rfc').inputValue(), '');
        assert.equal(await options.getByRole('option').count(), 1);
        await options.getByRole('option').click();
        await input.fill('soluciones medicas');
        assert.equal(await options.getByRole('option').count(), 1);
        await input.press('Escape');
        assert.equal(await page.locator('.po-supplier-menu').isVisible(), false);
        await input.fill('No coincide');
        assert.equal(await options.getByRole('option').count(), 0);
        assert.match(await page.locator('.po-supplier-results').textContent(), /Sin proveedores coincidentes/);
        await page.getByLabel('RFC del proveedor').click();
        assert.equal(await page.locator('.po-supplier-menu').isVisible(), false);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('empty supplier catalog explains the empty state and restored supplier data is preserved', async () => {
    const { page, errors } = await fixture({ suppliers: [], old: { supplier: 'Captura anterior', supplier_phone: '5555555555', supplier_bank_details: 'Datos capturados' } });
    try {
        assert.equal(await page.locator('#supplier_bank_details').inputValue(), 'Datos capturados');
        await page.getByRole('button', { name: 'Mostrar proveedores registrados' }).click();
        assert.match(await page.locator('.po-supplier-results').textContent(), /No hay proveedores disponibles/);
        assert.equal(await page.locator('#supplier_phone').inputValue(), '5555555555');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('five subwarehouses determine order type and supplier selection cannot overwrite it', async () => {
    const { page, errors } = await fixture();
    try {
        const destination = page.getByLabel('Subalmacén receptor');
        const type = page.getByLabel('Tipo de orden', { exact: true });
        assert.deepEqual(await destination.locator('option').allTextContents(), ['Selecciona subalmacén', 'Oncológico', 'Antibióticos', 'Nutricional', 'Diluyentes', 'Consumibles']);
        assert.equal(await type.inputValue(), '');
        assert.equal(await type.evaluate(el => el.readOnly), true);
        await page.getByLabel('Central receptora').selectOption('1');
        await page.getByLabel('Almacén receptor', { exact: true }).selectOption('11');
        for (const [key, expected] of Object.entries({ oncologicos: 'Medicamentos', antibioticos: 'Medicamentos', nutricionales: 'Medicamentos', insumos: 'Insumos', consumibles: 'Insumos' })) {
            await destination.selectOption(key);
            assert.equal(await type.inputValue(), expected);
            await page.getByRole('button', { name: 'Mostrar proveedores registrados' }).click();
            await page.locator('#po-supplier-options').getByRole('option').click();
            assert.equal(await type.inputValue(), expected);
            await page.getByLabel('Proveedor', { exact: true }).fill('Otro proveedor');
            await page.getByLabel('Proveedor', { exact: true }).press('Escape');
            assert.equal(await type.inputValue(), expected);
        }
        await page.getByLabel('Almacén receptor', { exact: true }).selectOption('12');
        assert.equal(await destination.inputValue(), '');
        assert.equal(await type.inputValue(), '');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('restored consumables selection computes Insumos instead of a stale submitted type', async () => {
    const { page, errors } = await fixture({ old: { delivery_laboratory_id: '1', warehouse_id: '11', inventory_destination: 'consumibles', order_type: 'Medicamentos' } });
    try {
        assert.equal(await page.getByLabel('Subalmacén receptor').inputValue(), 'consumibles');
        assert.equal(await page.getByLabel('Tipo de orden', { exact: true }).inputValue(), 'Insumos');
        assert.match(await page.locator('#delivery_destination_summary').textContent(), /Consumibles/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('requires all three destinations and searches by typing, dropdown and keyboard on every page', async () => {
    const { page, errors, posts, queries } = await fixture();
    try {
        const description = page.getByRole('combobox', { name: 'Descripción de la partida 1', exact: true });
        if (process.env.PO_SCREENSHOT_DIR) await page.locator('.po-sheet').screenshot({ path: path.join(process.env.PO_SCREENSHOT_DIR, 'purchase-order-document.png') });
        assert.equal(await page.getByLabel('Elaboró', { exact: true }).inputValue(), 'Ana Maria Lopez Ruiz');
        assert.equal(await page.getByLabel('Elaboró', { exact: true }).isEditable(), false);
        await page.getByLabel('Proveedor', { exact: true }).fill('Proveedor del catálogo');
        await page.getByLabel('Proveedor', { exact: true }).dispatchEvent('change');
        assert.equal(await page.locator('#supplier_rfc').inputValue(), 'PRU010101AAA');
        assert.equal(await page.locator('#supplier_bank_details').inputValue(), 'Cuenta de prueba');
        assert.equal(await page.locator('#supplier_email').inputValue(), 'proveedor@example.test');
        assert.equal(await description.isDisabled(), true);
        assert.equal(await page.getByLabel('Almacén receptor', { exact: true }).isDisabled(), true);
        await page.getByLabel('Central receptora').selectOption('1');
        assert.equal(await description.isDisabled(), true);
        await page.getByLabel('Almacén receptor', { exact: true }).selectOption('11');
        assert.equal(await description.isDisabled(), true);
        assert.equal(queries.length, 0);
        await page.getByLabel('Subalmacén receptor').selectOption('oncologicos');
        await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
        assert.equal(queries.length, 1);
        await description.fill('pEm');
        assert.equal(await page.locator('#po-product-options').getByRole('option').count(), 1);
        await description.press('ArrowDown');
        await description.press('Enter');
        assert.equal(await description.inputValue(), products[0].description);
        assert.equal(await page.locator('#po-product-menu').isVisible(), false);
        await page.locator('[data-item-field="quantity"]').first().fill('2');
        await page.locator('[data-item-field="unit_price"]').first().fill('100');
        await page.getByRole('button', { name: 'Mostrar productos de la partida 1', exact: true }).click();
        await page.getByRole('combobox', { name: 'Buscar producto en el catálogo' }).fill('acido');
        await page.getByRole('combobox', { name: 'Buscar producto en el catálogo' }).press('Escape');
        assert.equal(await description.inputValue(), products[0].description);
        assert.equal(await page.locator('[data-item-field="unit_price"]').first().inputValue(), '100');

        await page.getByRole('button', { name: 'Agregar partida', exact: true }).click();
        await page.getByRole('button', { name: 'Mostrar productos de la partida 2' }).click();
        const search = page.getByRole('combobox', { name: 'Buscar producto en el catálogo' });
        await search.fill('acido 50');
        assert.equal(await page.locator('#po-product-options').getByRole('option').count(), 1);
        await page.locator('#po-product-options').getByRole('option').click();
        await page.locator('[data-item-field="unit_price"]').nth(1).fill('50');
        await page.getByRole('button', { name: 'Agregar partida', exact: true }).click();
        await page.getByRole('combobox', { name: 'Descripción de la partida 3', exact: true }).fill('pembro');
        await page.locator('#po-product-options').getByRole('option').click();
        await page.locator('[data-item-field="unit_price"]').nth(2).fill('75');
        assert.equal(await description.inputValue(), products[0].description);
        assert.equal(await page.locator('[data-item-field="quantity"]').first().inputValue(), '2');
        assert.equal(await page.getByRole('combobox', { name: 'Descripción de la partida 2' }).inputValue(), products[1].description);
        assert.match(await page.locator('#subtotal-display').innerText(), /325/);
        await page.getByLabel('Proveedor', { exact: true }).fill('Proveedor de prueba');
        await page.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await page.waitForURL('**/ordenes-de-compra');
        assert.equal(posts.length, 1);
        assert.equal(posts[0].get('items[0][product_key]'), 'oncologicos:1');
        assert.equal(posts[0].get('items[1][product_key]'), 'oncologicos:2');
        assert.equal(posts[0].get('items[2][product_key]'), 'oncologicos:1');
        assert.equal(posts[0].get('items[0][quantity]'), '2');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('rejects unselected text and clears all pages when the warehouse changes', async () => {
    const { page, errors, posts } = await fixture();
    try {
        await selectDestination(page);
        const first = page.getByRole('combobox', { name: 'Descripción de la partida 1', exact: true });
        await first.fill('no existe');
        assert.equal(await page.locator('.po-product-results').innerText(), 'Sin coincidencias');
        await first.press('Escape');
        assert.equal(await page.locator('#po-product-menu').isVisible(), false);
        await page.getByRole('button', { name: 'Generar orden', exact: true }).click();
        assert.equal(posts.length, 0);
        assert.match(await first.evaluate(el => el.validationMessage), /catálogo/);
        await first.fill('pem');
        await page.locator('#po-product-options').getByRole('option').click();
        for (let line = 2; line <= 11; line++) {
            await page.getByRole('button', { name: 'Agregar partida', exact: true }).click();
            await page.getByRole('combobox', { name: `Descripción de la partida ${line}`, exact: true }).fill('pem');
            await page.locator('#po-product-options').getByRole('option').click();
        }
        await page.getByLabel('Almacén receptor', { exact: true }).selectOption('12');
        assert.equal(await page.getByLabel('Subalmacén receptor').inputValue(), '');
        assert.equal(await page.getByRole('combobox', { name: 'Descripción de la partida 11', exact: true }).inputValue(), '');
        await page.getByRole('button', { name: 'Partidas anteriores' }).click();
        assert.equal(await first.inputValue(), '');
        assert.equal(await first.isDisabled(), true);
        await page.getByLabel('Subalmacén receptor').selectOption('oncologicos');
        await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
        assert.match(await page.locator('#po-catalog-message').innerText(), /Sin productos/);
        assert.equal(await first.isDisabled(), true);
        await page.getByLabel('Central receptora').selectOption('2');
        assert.equal(await page.getByLabel('Almacén receptor', { exact: true }).inputValue(), '');
        assert.equal(await page.getByLabel('Subalmacén receptor').isDisabled(), true);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('handles failed queries, retries and stale responses without mixing catalogs', async () => {
    let calls = 0;
    let held;
    let markHeld;
    const requestHeld = new Promise(resolve => { markHeld = resolve; });
    const { page, errors } = await fixture({ onCatalog: async (route, url) => {
        calls++;
        if (calls === 1) return route.fulfill({ status: 503, json: {} });
        if (calls === 3) { held = route; markHeld(); return; }
        return route.fulfill({ json: { products: url.searchParams.get('inventory_destination') === 'oncologicos' ? products : [] } });
    } });
    try {
        await page.getByLabel('Central receptora').selectOption('1');
        await page.getByLabel('Almacén receptor', { exact: true }).selectOption('11');
        await page.getByLabel('Subalmacén receptor').selectOption('oncologicos');
        await page.locator('#po-catalog-status[data-state="error"]').waitFor();
        assert.equal(await page.locator('[data-item-field="description"]').first().isDisabled(), true);
        await page.getByRole('button', { name: 'Reintentar' }).click();
        await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
        await page.getByLabel('Subalmacén receptor').selectOption('antibioticos');
        await page.waitForFunction(() => document.querySelector('#po-catalog-status').dataset.state === 'loading');
        await requestHeld;
        await page.getByLabel('Subalmacén receptor').selectOption('oncologicos');
        await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
        await held.fulfill({ json: { products: [{ product_key: 'antibioticos:9', description: 'Producto de respuesta vieja' }] } });
        await page.getByRole('button', { name: 'Mostrar productos de la partida 1', exact: true }).click();
        assert.equal(await page.locator('#po-product-options').getByRole('option').count(), 2);
        assert.equal(await page.locator('#po-product-options').getByRole('option', { name: 'Producto de respuesta vieja' }).count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('restores validated old selections and keeps the popup inside desktop and mobile viewports', async () => {
    for (const width of [1440, 390]) {
        const { page, errors } = await fixture({ width, old: {
            delivery_laboratory_id: 1, warehouse_id: 11, inventory_destination: 'oncologicos',
            prepared_by: 'Nombre anterior',
            items: [{ ...products[0], quantity: 4, unit_price: 20 }],
        } });
        try {
            await page.locator('#po-catalog-status[data-state="ready"]').waitFor();
            assert.equal(await page.getByLabel('Elaboró', { exact: true }).inputValue(), 'Ana Maria Lopez Ruiz');
            assert.equal(await page.getByRole('combobox', { name: 'Descripción de la partida 1', exact: true }).inputValue(), products[0].description);
            await page.getByRole('button', { name: 'Mostrar productos de la partida 1', exact: true }).click();
            const box = await page.locator('#po-product-menu').boundingBox();
            assert.ok(box.x >= 0 && box.x + box.width <= width);
            assert.ok(box.y >= 0 && box.y + box.height <= 900);
            assert.equal(await page.locator('#po-product-options').getByRole('option').count(), 2);
            const topHit = await page.locator('[role="option"]').first().evaluate(el => {
                const r = el.getBoundingClientRect();
                return el.contains(document.elementFromPoint(r.left + 8, r.top + 8));
            });
            assert.equal(topHit, true, 'Options should not be clipped by the sheet');
            if (process.env.PO_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.PO_SCREENSHOT_DIR, `purchase-order-search-${width}.png`) });
            await page.getByRole('combobox', { name: 'Buscar producto en el catálogo' }).press('Escape');
            assert.equal(await page.locator('#po-product-menu').isVisible(), false);
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('the spare row inherits the catalog and becomes a line through typing or its dropdown', async () => {
    const { page, errors, posts, queries } = await fixture();
    try {
        const second = page.getByRole('combobox', { name: 'Descripción de la partida 2', exact: true });
        assert.equal(await second.isDisabled(), true);
        await selectDestination(page);
        assert.equal(await second.isDisabled(), false);
        assert.equal(await second.getAttribute('placeholder'), 'Buscar producto');
        await page.getByRole('button', { name: 'Mostrar productos de la partida 1', exact: true }).click();
        await page.getByRole('option', { name: products[0].description, exact: true }).click();
        await page.locator('[data-item-field="quantity"]').first().fill('3');
        await page.locator('[data-item-field="unit_price"]').first().fill('100');

        await page.getByRole('button', { name: 'Mostrar productos de la partida 2' }).click();
        await page.getByRole('option', { name: products[1].description, exact: true }).click();
        assert.equal(await second.inputValue(), products[1].description);
        assert.equal(await page.locator('[data-item-field="quantity"]').nth(1).inputValue(), '1');
        await page.locator('[data-item-field="unit_price"]').nth(1).fill('50');
        assert.match(await page.locator('#po-items-page').innerText(), /1-2 de 2/);
        if (process.env.PO_SCREENSHOT_DIR) {
            await page.screenshot({ path: path.join(process.env.PO_SCREENSHOT_DIR, 'purchase-order-shared-subwarehouse.png') });
        }

        await page.getByRole('button', { name: 'Agregar partida', exact: true }).click();
        const third = page.getByRole('combobox', { name: 'Descripción de la partida 3', exact: true });
        assert.equal(await third.isDisabled(), false);
        await third.fill('pem');
        await page.getByRole('option', { name: products[0].description, exact: true }).click();
        const fourth = page.getByRole('combobox', { name: 'Descripción de la partida 4', exact: true });
        await fourth.fill('acido');
        await fourth.press('ArrowDown');
        await fourth.press('Enter');
        assert.equal(await fourth.inputValue(), products[1].description);
        assert.equal(await page.locator('[data-item-field="quantity"]').nth(1).inputValue(), '1');
        assert.equal(queries.length, 1, 'All lines reuse the order-wide catalog');
        assert.equal(await page.locator('[data-item-field="quantity"]').first().inputValue(), '3');
        assert.equal(await page.locator('[data-item-field="unit_price"]').first().inputValue(), '100');
        assert.equal(await second.inputValue(), products[1].description);

        await page.getByLabel('Proveedor', { exact: true }).fill('Proveedor de prueba');
        await page.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await page.waitForURL('**/ordenes-de-compra');
        assert.equal(posts.length, 1);
        assert.deepEqual(posts[0].getAll('inventory_destination'), ['oncologicos']);
        assert.equal(posts[0].get('warehouse_id'), '11');
        assert.equal(posts[0].get('delivery_laboratory_id'), '1');
        for (let i = 0; i < 4; i++) assert.equal(posts[0].get(`items[${i}][product_key]`), products[i % 2].product_key);
        assert.equal(posts[0].has('items[4][product_key]'), false);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('opening an unused spare row does not add a required or submitted item', async () => {
    const { page, posts, errors } = await fixture();
    try {
        await selectDestination(page);
        await page.getByRole('button', { name: 'Mostrar productos de la partida 1', exact: true }).click();
        await page.getByRole('option', { name: products[0].description, exact: true }).click();
        await page.getByRole('button', { name: 'Mostrar productos de la partida 2' }).click();
        await page.getByRole('combobox', { name: 'Buscar producto en el catálogo' }).fill('acido');
        await page.getByRole('combobox', { name: 'Buscar producto en el catálogo' }).press('Escape');
        assert.match(await page.locator('#po-items-page').innerText(), /1-1 de 1/);
        await page.getByLabel('Proveedor', { exact: true }).fill('Proveedor de prueba');
        await page.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await page.waitForURL('**/ordenes-de-compra');
        assert.equal(posts[0].get('items[0][product_key]'), products[0].product_key);
        assert.equal(posts[0].has('items[1][product_key]'), false);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
