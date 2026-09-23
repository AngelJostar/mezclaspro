const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const bundle = buildSync({ stdin: { contents: `import './resources/js/minimum-stock';
    import './resources/js/purchase-navigation'; import './resources/js/request-navigation'; import './resources/js/table-column-filters';
    import './resources/js/fixed-table-scrollbar';`, resolveDir: root }, bundle: true, write: false, outdir: 'out' });
const script = bundle.outputFiles.find(file => file.path.endsWith('.js')).text;
const css = bundle.outputFiles.find(file => file.path.endsWith('.css')).text;
const pages = new Map();
function renderStock(query) {
    const key = JSON.stringify(query);
    if (!pages.has(key)) pages.set(key, execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/minimum-stock.php', key], { cwd: root, encoding: 'utf8' }));
    return pages.get(key);
}
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openStock(width = 1840) {
    const page = await browser.newPage({ viewport: { width, height: 950 }, reducedMotion: 'reduce' });
    const state = { fail: false, minimum_stock: 10, maximum_stock: 30, supplier: { id: 1, name: 'Proveedor A', email: 'compras-a@example.test', status: 'active' } };
    const errors = [], requests = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        if (route.request().method() === 'PATCH') {
            const body = route.request().postDataJSON();
            requests.push(body);
            assert.ok(route.request().headers()['x-csrf-token']);
            if (!state.fail) {
                if ('minimum_stock' in body) Object.assign(state, body);
                if ('supplier_id' in body) state.supplier = body.supplier_id ? { id: 2, name: 'Proveedor B', email: 'compras-b@example.test', status: 'active' } : null;
            }
            await route.fulfill({ status: state.fail ? 422 : 200, contentType: 'application/json', body: JSON.stringify(state.fail
                ? { errors: { minimum_stock: ['No se pudo guardar la configuracion.'] } } : state) });
        } else if (new URL(route.request().url()).pathname.includes('/stock-minimo')) {
            const url = new URL(route.request().url());
            const query = Object.fromEntries(url.searchParams);
            const detail = url.pathname.match(/\/ordenes\/(\d+)\/(\d+)$/);
            if (detail) Object.assign(query, { laboratory_id: detail[1], order: detail[2] });
            const html = renderStock(query);
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}\n${css}\nbody{background:#f1f5f9}main{padding:16px;min-width:0}@media(min-width:640px){main{margin-left:160px}}</style></head><body>${html}<script>${script}</script></body></html>` });
        } else await route.abort();
    });
    await page.goto('http://stock.test/admin/compras/stock-minimo');
    await page.locator('[data-automatic-column-filters="true"]').waitFor();
    return { page, state, errors, requests, row: page.locator('[data-stock-key="medicine:1"]') };
}

test('limits edit, cancel, save, validation and failed save preserve data', async () => {
    const { page, row, state, errors, requests } = await openStock();
    try {
        const minimum = row.locator('[data-stock-min]'), maximum = row.locator('[data-stock-max]');
        assert.equal(await minimum.evaluate(el => el.readOnly), true);
        await row.getByRole('button', { name: 'Editar', exact: true }).click();
        await minimum.fill('15');
        await row.getByRole('button', { name: 'Cancelar edicion' }).click();
        assert.equal(await minimum.inputValue(), '10');
        assert.equal(requests.length, 0);
        await row.getByRole('button', { name: 'Editar', exact: true }).click();
        await minimum.fill('40');
        await row.getByRole('button', { name: 'Guardar', exact: true }).click();
        assert.equal(await maximum.evaluate(el => el.validity.customError), true);
        assert.equal(requests.length, 0);
        await minimum.fill('12');
        await maximum.fill('36');
        await row.getByRole('button', { name: 'Guardar', exact: true }).click();
        await row.getByRole('button', { name: 'Editar', exact: true }).waitFor();
        assert.deepEqual(requests[0], { minimum_stock: 12, maximum_stock: 36 });
        assert.equal(await minimum.evaluate(el => el.readOnly), true);
        assert.match(await row.locator('td').first().innerText(), /Activo/);
        assert.equal(await row.locator('td').first().locator('input, button').count(), 0);
        state.fail = true;
        await row.getByRole('button', { name: 'Editar', exact: true }).click();
        await minimum.fill('14');
        await row.getByRole('button', { name: 'Guardar', exact: true }).click();
        await row.locator('[data-stock-error]').waitFor();
        assert.equal(await minimum.inputValue(), '14');
        await row.getByRole('button', { name: 'Cancelar edicion' }).click();
        assert.equal(await minimum.inputValue(), '12');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('supplier dialog previews email, saves supplier, handles errors and clears assignment', async () => {
    const { page, row, state, errors, requests } = await openStock();
    try {
        const dialog = page.getByRole('dialog');
        await row.getByRole('button', { name: 'Cambiar de proveedor' }).click();
        assert.equal(await dialog.getByRole('option', { name: 'Proveedor inactivo (inactivo)' }).evaluate(option => option.disabled), true);
        await dialog.getByRole('combobox').selectOption('2');
        assert.equal(await dialog.locator('[data-stock-dialog-email]').innerText(), 'compras-b@example.test');
        await dialog.getByRole('button', { name: 'Guardar proveedor' }).click();
        await dialog.waitFor({ state: 'hidden' });
        assert.deepEqual(requests[0], { supplier_id: 2 });
        assert.equal(await row.locator('[data-stock-supplier-name]').innerText(), 'Proveedor B');
        assert.equal(await row.locator('[data-stock-supplier-email]').innerText(), 'compras-b@example.test');
        state.fail = true;
        await row.getByRole('button', { name: 'Cambiar de proveedor' }).click();
        await dialog.getByRole('combobox').selectOption('');
        await dialog.getByRole('button', { name: 'Guardar proveedor' }).click();
        await dialog.locator('[data-stock-dialog-error]').waitFor();
        assert.equal(await row.getAttribute('data-supplier-id'), '2');
        state.fail = false;
        await dialog.getByRole('button', { name: 'Guardar proveedor' }).click();
        await dialog.waitFor({ state: 'hidden' });
        assert.equal(await row.locator('[data-stock-supplier-name]').innerText(), 'Sin proveedor');
        assert.equal(await row.locator('[data-stock-min]').inputValue(), '10');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('reference columns, filters, scroll bar and dialog fit desktop and mobile', async () => {
    for (const width of [1840, 1366, 390, 320]) {
        const { page, row, errors } = await openStock(width);
        try {
            assert.equal(await page.locator('thead th').count(), 12);
            assert.equal(await page.locator('thead button').count(), 10);
            assert.equal(await page.getByRole('button', { name: 'Filtrar Punto de reorden (piezas)', exact: true }).count(), 1);
            assert.equal((await row.locator('[data-stock-current]').innerText()).trim(), '17');
            assert.equal(await row.locator('[data-stock-current] input, [data-stock-current] button').count(), 0);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            await page.getByRole('button', { name: 'Filtrar Estado', exact: true }).click();
            const panel = page.locator('[id$="-panel"]:visible');
            await panel.getByRole('checkbox', { name: 'Activo', exact: true }).uncheck();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await page.locator('[data-stock-row]:visible').count(), 1);
            await page.getByRole('button', { name: 'Filtrar Estado', exact: true }).click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await page.getByRole('button', { name: 'Filtrar Stock actual (piezas)', exact: true }).click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
            await panel.getByRole('checkbox', { name: '17', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await page.locator('[data-stock-row]:visible').count(), 1);
            assert.equal(await row.isVisible(), true);
            await page.getByRole('button', { name: 'Filtrar Stock actual (piezas)', exact: true }).click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            const scroll = page.locator('.minimum-stock-scroll');
            await scroll.scrollIntoViewIfNeeded();
            if (await scroll.evaluate(el => el.scrollWidth > el.clientWidth + 2)) {
                const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla' });
                await bar.waitFor();
                const bounds = await bar.boundingBox();
                assert.ok(bounds.y + bounds.height >= 945);
                await bar.getByRole('slider').press('End');
                const last = await page.locator('thead th').last().boundingBox();
                assert.ok(last.x + last.width <= width);
            }
            await row.getByRole('button', { name: 'Cambiar de proveedor' }).click();
            const dialog = page.getByRole('dialog');
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
            await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
            await scroll.evaluate(el => { el.scrollLeft = 0; });
            if (process.env.STOCK_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.STOCK_SCREENSHOTS, `minimum-stock-${width}.png`), fullPage: true });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('product carousel filters rows, keeps central and tab, and scrolls on mobile', async () => {
    const { page, errors } = await openStock();
    try {
        const carousel = page.getByRole('navigation', { name: 'Tipo de producto', exact: true });
        assert.equal(await carousel.getByRole('link').count(), 4);
        const carouselBox = await carousel.boundingBox();
        const centralBox = await page.getByRole('navigation', { name: 'Centrales de compras', exact: true }).boundingBox();
        const tabsBox = await page.getByRole('navigation', { name: 'Filtros de compras', exact: true }).boundingBox();
        assert.ok(carouselBox.y >= centralBox.y + centralBox.height);
        assert.ok(tabsBox.y >= carouselBox.y + carouselBox.height);
        for (const [label, keys] of [['Nutricionales', ['nutrition:1']], ['Oncologicos', ['medicine:1', 'medicine:2']],
            ['Antibioticos', ['medicine:3']], ['Todas', ['medicine:1', 'medicine:2', 'medicine:3', 'nutrition:1', 'diluent:1', 'consumable:1']]]) {
            await carousel.getByRole('link', { name: new RegExp(label) }).click();
            await page.waitForLoadState();
            assert.match(await carousel.locator('[aria-current="page"]').innerText(), new RegExp(label));
            assert.deepEqual((await page.locator('[data-stock-row]').evaluateAll(rows => rows.map(row => row.dataset.stockKey))).sort(), keys.sort());
            assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '1');
        }
        await carousel.getByRole('link', { name: /Nutricionales/ }).click();
        await page.getByRole('link', { name: 'Seleccionar central Monterrey', exact: true }).click();
        assert.equal(new URL(page.url()).searchParams.get('tipo'), 'nutricionales');
        assert.equal((await page.locator('[data-stock-current]').innerText()).trim(), '90');
        await page.getByRole('link', { name: 'OC Automatizadas', exact: true }).click();
        assert.equal(new URL(page.url()).searchParams.get('tipo'), 'nutricionales');
        await page.getByRole('link', { name: 'Stock minimo', exact: true }).click();
        assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '2');
        assert.equal(await page.locator('[data-stock-row]').count(), 1);
        await page.setViewportSize({ width: 390, height: 950 });
        await page.reload();
        const track = carousel.locator('.request-selector-scroll');
        const initial = await track.evaluate(el => el.scrollLeft);
        await carousel.getByRole('button', { name: 'Tipo siguiente', exact: true }).click();
        await page.waitForFunction(value => document.querySelector('[data-request-type-selector] .request-selector-scroll').scrollLeft > value + 20, initial);
        await carousel.getByRole('button', { name: 'Tipo anterior', exact: true }).click();
        await page.waitForFunction(value => document.querySelector('[data-request-type-selector] .request-selector-scroll').scrollLeft <= value + 5, initial);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('automatic orders display full information, filters and detail on desktop and mobile', async () => {
    for (const width of [1840, 390]) {
        const { page, errors } = await openStock(width);
        try {
            await page.getByRole('link', { name: 'OC Automatizadas', exact: true }).click();
            const table = page.locator('.automatic-orders-table');
            assert.equal(await table.locator('thead th').count(), 24);
            assert.equal(await table.locator('thead button').count(), 23);
            assert.equal(await table.locator('[data-automatic-order]').count(), 3);
            assert.equal(await table.getByText('Pendiente de revision', { exact: true }).count(), 3);
            assert.ok(await table.getByText('Por cotizar', { exact: true }).count() >= 3);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            await page.getByRole('navigation', { name: 'Tipo de producto', exact: true }).getByRole('link', { name: /Oncologicos/ }).click();
            assert.equal(await table.locator('[data-automatic-order]').count(), 1);
            assert.match(await table.innerText(), /Proveedor A/);
            await table.getByRole('button', { name: 'Filtrar Estado', exact: true }).click();
            const panel = page.locator('[id$="-panel"]:visible');
            await panel.getByRole('checkbox', { name: 'Pendiente de revision', exact: true }).uncheck();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await table.locator('[data-automatic-order]:visible').count(), 0);
            await table.getByRole('button', { name: 'Filtrar Estado', exact: true }).click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await table.scrollIntoViewIfNeeded();
            const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla' });
            await bar.waitFor();
            await bar.getByRole('slider').press('End');
            const link = table.getByRole('link', { name: /Ver orden/ });
            const bounds = await link.boundingBox();
            assert.ok(bounds.x + bounds.width <= width);
            if (process.env.STOCK_SCREENSHOTS) {
                await table.locator('..').evaluate(el => { el.scrollLeft = 0; });
                await page.screenshot({ path: path.join(process.env.STOCK_SCREENSHOTS, `automatic-orders-${width}.png`), fullPage: true });
            }
            await link.click();
            await page.getByRole('heading', { name: /Orden de compra OC-AUTO/ }).waitFor();
            assert.ok(await page.getByRole('heading', { name: 'Entrega y facturacion', exact: true }).isVisible());
            assert.match(await page.locator('main').innerText(), /no enviada al proveedor/);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            if (process.env.STOCK_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.STOCK_SCREENSHOTS, `automatic-order-detail-${width}.png`), fullPage: true });
            await page.getByRole('link', { name: 'Volver a ordenes', exact: true }).click();
            assert.equal(await table.locator('[data-automatic-order]').count(), 1);
            assert.equal(new URL(page.url()).searchParams.get('tipo'), 'oncologicos');
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('automatic history button opens closed orders and preserves navigation on desktop and mobile', async () => {
    for (const width of [1840, 390, 320]) {
        const { page, errors } = await openStock(width);
        try {
            const button = page.getByRole('link', { name: 'OC Automatizadas Historial', exact: true });
            await button.click();
            assert.equal(await button.getAttribute('aria-current'), 'page');
            assert.equal(new URL(page.url()).searchParams.get('view'), 'history');
            const table = page.locator('.automatic-orders-table');
            assert.equal(await table.locator('[data-automatic-order]').count(), 1);
            assert.match(await table.innerText(), /OC-AUTO-HIST-TEST/);
            assert.match(await table.innerText(), /Cancelada/);
            assert.equal(await table.getByText('Pendiente de revision', { exact: true }).count(), 0);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            const bounds = await button.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
            if (process.env.STOCK_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.STOCK_SCREENSHOTS, `automatic-history-${width}.png`), fullPage: true });
            const carousel = page.getByRole('navigation', { name: 'Tipo de producto', exact: true });
            await carousel.getByRole('link', { name: /Oncologicos/ }).click();
            await table.getByRole('link', { name: 'Ver orden OC-AUTO-HIST-TEST', exact: true }).click();
            await page.getByRole('heading', { name: 'Orden de compra OC-AUTO-HIST-TEST', exact: true }).waitFor();
            await page.getByRole('link', { name: 'Volver a ordenes', exact: true }).click();
            assert.equal(new URL(page.url()).searchParams.get('view'), 'history');
            assert.equal(new URL(page.url()).searchParams.get('tipo'), 'oncologicos');
            await carousel.getByRole('link', { name: /Nutricionales/ }).click();
            assert.equal(await table.locator('[data-automatic-order]').count(), 0);
            assert.equal(new URL(page.url()).searchParams.get('view'), 'history');
            await page.getByRole('link', { name: 'Seleccionar central Monterrey', exact: true }).click();
            assert.equal(new URL(page.url()).searchParams.get('view'), 'history');
            assert.equal(new URL(page.url()).searchParams.get('tipo'), 'nutricionales');
            assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '2');
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});
