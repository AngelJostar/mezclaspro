const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const source = buildSync({ stdin: { contents: "import './resources/js/purchase-order'; import './resources/js/workflow-modal';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const form = execFileSync('php', ['tests/Browser/fixtures/purchase-order.php', '{}', 'popup'], { cwd: root, encoding: 'utf8' });
const shell = readFileSync(path.join(root, 'resources/views/layouts/includes/workflow-modal.blade.php'), 'utf8');
const origin = 'http://purchase-order.test';
const config = embedded => `<script type="application/json" id="workflow-page-config">${JSON.stringify({ embedded })}</script>`;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(width = 1440, onPost) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, acceptDownloads: true });
    const errors = [], posts = [];
    let listLoads = 0;
    page.on('pageerror', error => errors.push(error.message));
    // Every request is intercepted; no live orders, supplier records or messages are created.
    await page.context().route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        if (url.pathname.endsWith('/oc-template.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/purchase-orders/oc-template.png')) });
        if (url.pathname.endsWith('/productos')) return route.fulfill({ json: { products: [{ product_key: 'oncologicos:1', description: 'Medicamento de prueba - Frasco 100 mg' }] } });
        if (request.method() === 'POST') {
            posts.push(request.postData());
            return onPost ? onPost(route, posts.length) : route.fulfill({ status: 500, json: { message: 'Test failure' } });
        }
        if (url.pathname === '/download') return route.fulfill({ contentType: 'application/pdf', headers: { 'Content-Disposition': 'attachment; filename="OC-TEST.pdf"' }, body: '%PDF-1.4\n%%EOF' });
        let body;
        if (url.pathname === '/list') {
            listLoads++;
            body = `<main style="padding:24px"><h1>Compras</h1><nav><a href="/admin/compras/nueva?laboratory_id=1" data-purchase-order-popup aria-haspopup="dialog">Nueva OC</a></nav><p>Ordenes de compra</p></main>${shell}${config(false)}`;
        } else if (url.pathname === '/admin/compras/nueva') {
            assert.equal(url.searchParams.get('laboratory_id'), '1');
            assert.equal(url.searchParams.get('purchase_popup'), '1');
            body = `<main class="admin-page workflow-page purchase-popup-page"><div class="admin-content">${form}</div></main>${config(true)}`;
        } else if (url.pathname.endsWith('/nueva')) {
            body = `<main class="admin-page workflow-page purchase-popup-page"><div class="admin-content">${form}</div></main>${config(true)}`;
        } else return route.abort();
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html class="${url.pathname === '/list' ? '' : 'workflow-embedded'}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style></head><body>${body}<script>${source}</script></body></html>` });
    });
    await page.goto(`${origin}/list?section=all&laboratory_id=1`);
    await page.getByRole('link', { name: 'Nueva OC', exact: true }).click();
    const frame = page.frameLocator('[data-workflow-frame]');
    await frame.getByRole('button', { name: 'Generar orden', exact: true }).waitFor({ timeout: 10000 });
    return { page, frame, errors, posts, listLoads: () => listLoads };
}

async function fillOrder(frame) {
    await frame.getByLabel('Central receptora').selectOption('1');
    await frame.getByLabel('Almacén receptor', { exact: true }).selectOption('11');
    await frame.getByLabel('Subalmacén receptor').selectOption('oncologicos');
    await frame.locator('#po-catalog-status[data-state="ready"]').waitFor();
    await frame.getByRole('combobox', { name: 'Descripción de la partida 1', exact: true }).fill('Medicamento');
    await frame.getByRole('option', { name: 'Medicamento de prueba - Frasco 100 mg', exact: true }).click();
    await frame.locator('[data-item-field="quantity"]').first().fill('2');
    await frame.locator('[data-item-field="unit_price"]').first().fill('100');
    await frame.getByLabel('Proveedor', { exact: true }).fill('Proveedor de prueba');
}

test('new purchase order opens over the list with fixed actions and closes without saving on desktop and mobile', async () => {
    for (const width of [1440, 768, 390, 320]) {
        const { page, frame, errors, posts, listLoads } = await fixture(width);
        try {
            assert.equal(page.url(), `${origin}/list?section=all&laboratory_id=1`);
            const bounds = await page.getByRole('dialog').boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width && bounds.y + bounds.height <= 900);
            assert.equal(await frame.locator('[data-purchase-navigation]').count(), 0);
            const sheet = await frame.locator('.po-sheet').evaluate(el => getComputedStyle(el).backgroundImage);
            assert.match(sheet, /oc-template\.png/);
            await frame.locator('.po-form-body').evaluate(el => el.scrollTop = el.scrollHeight);
            const actions = await frame.locator('.po-popup-form footer').boundingBox();
            assert.ok(actions.y >= bounds.y && actions.y + actions.height <= bounds.y + bounds.height);
            assert.equal(await frame.locator('html').evaluate(el => el.scrollWidth <= innerWidth + 1), true);
            await frame.getByLabel('Proveedor', { exact: true }).fill('Sin guardar');
            if (process.env.PO_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.PO_SCREENSHOT_DIR, `purchase-order-modal-${width}.png`) });
            await frame.getByRole('link', { name: 'Cerrar', exact: true }).click();
            await page.getByRole('dialog').waitFor({ state: 'hidden' });
            assert.equal(posts.length, 0);
            assert.equal(listLoads(), 1);
            assert.equal(await page.getByRole('link', { name: 'Nueva OC' }).evaluate(el => el === document.activeElement), true);
            await page.getByRole('link', { name: 'Nueva OC' }).click();
            assert.equal(await frame.getByLabel('Proveedor', { exact: true }).inputValue(), '');
            await frame.getByLabel('Proveedor', { exact: true }).press('Escape');
            await page.getByRole('dialog').waitFor({ state: 'hidden' });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('validation stays in the popup, generation prevents duplicates and downloads before refreshing on close', async () => {
    let release;
    const gate = new Promise(resolve => { release = resolve; });
    const { page, frame, errors, posts, listLoads } = await fixture(1440, async (route, count) => {
        if (count === 1) return route.fulfill({ status: 422, json: { errors: { supplier: ['Revisa el proveedor de prueba.'] } } });
        await gate;
        return route.fulfill({ status: 201, json: { folio: 'OC-TEST', download_url: `${origin}/download` } });
    });
    try {
        await fillOrder(frame);
        await frame.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await frame.getByRole('alert').waitFor();
        assert.match(await frame.getByRole('alert').innerText(), /Revisa el proveedor/);
        assert.equal(await frame.getByLabel('Proveedor', { exact: true }).inputValue(), 'Proveedor de prueba');
        await frame.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await page.waitForFunction(() => document.querySelector('[data-workflow-modal-close]').disabled);
        await frame.locator('#purchase-order-form').evaluate(el => el.requestSubmit());
        await frame.getByLabel('Proveedor', { exact: true }).press('Escape');
        assert.equal(await page.getByRole('dialog').evaluate(el => el.open), true);
        const downloading = page.waitForEvent('download');
        release();
        const download = await downloading;
        assert.equal(download.suggestedFilename(), 'OC-TEST.pdf');
        assert.equal(await download.failure(), null);
        await frame.getByText('Orden OC-TEST generada', { exact: true }).waitFor();
        assert.equal(posts.length, 2);
        assert.equal(listLoads(), 1);
        assert.equal(await frame.getByRole('button', { name: 'Generar orden' }).count(), 0);
        await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Cerrar ventana' }).click()]);
        assert.equal(listLoads(), 2);
        assert.equal(page.url(), `${origin}/list?section=all&laboratory_id=1`);
        assert.deepEqual(errors, []);
    } finally { release(); await page.close(); }
});

test('an unconfirmed server response keeps captured data and blocks accidental resubmission', async () => {
    const { page, frame, posts } = await fixture();
    try {
        await fillOrder(frame);
        await frame.getByRole('button', { name: 'Generar orden', exact: true }).click();
        await frame.getByRole('alert').waitFor();
        assert.match(await frame.getByRole('alert').innerText(), /No se pudo confirmar/);
        assert.equal(await frame.getByLabel('Proveedor', { exact: true }).inputValue(), 'Proveedor de prueba');
        assert.equal(await frame.getByRole('button', { name: 'Generar orden', exact: true }).isDisabled(), true);
        await frame.getByRole('link', { name: 'Cerrar', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
        assert.equal(posts.length, 1);
    } finally { await page.close(); }
});
