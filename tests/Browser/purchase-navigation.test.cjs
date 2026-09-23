const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ stdin: { contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js';
    import './resources/js/request-navigation'; import './resources/js/purchase-navigation'; Alpine.start();`, resolveDir: root },
    bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const cache = new Map();
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(width) {
    const page = await browser.newPage({ viewport: { width, height: 950 }, reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => {
        const url = new URL(route.request().url());
        if (url.pathname.endsWith('promesa-logo.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
        if (url.pathname.endsWith('oc-template.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/purchase-orders/oc-template.png')) });
        const creation = url.pathname.match(/laboratory\/(\d+)\/ordenes-de-compra\/nueva$/);
        if (url.pathname.endsWith('/compras/nueva')) return route.fulfill({ status: 302, headers: { location: `http://purchase-navigation.test/mezclaspro/public/admin/oncologicos/laboratory/${url.searchParams.get('laboratory_id')}/ordenes-de-compra/nueva` } });
        if (url.pathname.endsWith('/almacenes/ordenes-de-compra') || url.pathname.endsWith('/compras/stock-minimo') || creation) {
            const section = creation ? 'new' : url.pathname.endsWith('/compras/stock-minimo') ? 'minimum-stock' : url.searchParams.get('section') || 'mine';
            const central = creation ? creation[1] : url.searchParams.get('laboratory_id') || '1';
            const key = `${section}-${central}`;
            if (!cache.has(key)) cache.set(key, execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/purchase-navigation.php', section, central], { cwd: root, encoding: 'utf8' }));
            return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}@media(min-width:640px){main{margin-left:176px}}body{background:#f1f5f9}</style></head><body><div x-data="{open:false}"><button type="button" class="fixed right-4 top-4 sm:hidden" style="z-index:51" aria-label="Abrir menu" x-on:click="open = true">Menu</button>${cache.get(key)}</div><script>${script}</script></body></html>` });
        }
        return route.abort();
    });
    await page.goto('http://purchase-navigation.test/mezclaspro/public/admin/almacenes/ordenes-de-compra?section=mine&laboratory_id=1');
    return { page, errors };
}

test('carousel and filter buttons retain central and section on desktop and mobile', async () => {
    for (const width of [1840, 768, 390, 320]) {
        const { page, errors } = await fixture(width);
        try {
            const nav = page.getByRole('navigation', { name: 'Filtros de compras' });
            assert.deepEqual((await nav.getByRole('link').allTextContents()).map(text => text.trim()), ['Todas', 'Pagadas', 'Pendientes de pago', 'Rechazadas', 'Nueva OC']);
            const navBounds = await nav.boundingBox();
            const newOrderBounds = await nav.getByRole('link', { name: 'Nueva OC', exact: true }).boundingBox();
            assert.ok(Math.abs(newOrderBounds.x + newOrderBounds.width - navBounds.x - navBounds.width) <= 1);
            if (width >= 768) assert.ok(Math.abs(newOrderBounds.y - navBounds.y) <= 1);
            assert.equal(await page.locator('[data-purchase-direction="-1"]').isDisabled(), true);
            if (width < 1840) {
                await page.getByRole('button', { name: 'Central siguiente', exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-purchase-carousel]').scrollLeft > 0);
                await page.getByRole('button', { name: 'Central anterior', exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-purchase-carousel]').scrollLeft <= 1);
            }
            await page.getByRole('link', { name: 'Seleccionar central Monterrey', exact: true }).click();
            assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '2');
            for (const [label, section, status] of [['Pagadas', 'paid', 'pagada'], ['Pendientes de pago', 'pending', 'pendiente_pago'], ['Rechazadas', 'rejected', 'rechazada']]) {
                await nav.getByRole('link', { name: label, exact: true }).click();
                assert.equal(new URL(page.url()).searchParams.get('section'), section);
                assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '2');
                assert.equal(await nav.getByRole('link', { name: label, exact: true }).getAttribute('aria-current'), 'page');
                assert.equal(await page.locator('.js-purchase-order-filter-row').count(), 1);
                assert.ok((await page.locator('.js-purchase-order-filter-row').innerText()).includes(`OC-2-${status}`));
            }
            await nav.getByRole('link', { name: 'Todas', exact: true }).click();
            assert.equal(new URL(page.url()).searchParams.get('section'), 'all');
            assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '2');
            assert.equal(await nav.getByRole('link', { name: 'Todas', exact: true }).getAttribute('aria-current'), 'page');
            assert.equal(await page.locator('.js-purchase-order-filter-row').count(), 4);
            await page.getByRole('link', { name: 'Seleccionar central CDMX', exact: true }).click();
            assert.equal(new URL(page.url()).searchParams.get('section'), 'all');
            assert.equal(await page.locator('.js-purchase-order-filter-row').count(), 4);
            assert.ok((await page.locator('#purchase-orders-table').innerText()).includes('OC-1-pagada'));
            assert.ok(!(await page.locator('#purchase-orders-table').innerText()).includes('OC-2-pagada'));
            await page.getByRole('link', { name: 'Seleccionar central Monterrey', exact: true }).click();
            const bounds = await page.locator('[data-purchase-navigation]').boundingBox();
            const buttons = await nav.getByRole('link').evaluateAll(links => links.map(link => {
                const rect = link.getBoundingClientRect();
                return { x: rect.x, right: rect.right, scrollWidth: link.scrollWidth, width: link.clientWidth };
            }));
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            for (const button of buttons) assert.ok(button.x >= bounds.x && button.right <= width && button.scrollWidth <= button.width + 1);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            if (process.env.PURCHASE_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.PURCHASE_SCREENSHOTS, `purchase-navigation-${width}.png`) });
            const stock = page.locator('#compras-submenu').getByRole('link', { name: 'Stock mínimo', exact: true });
            if (width < 640) await page.getByRole('button', { name: 'Abrir menu', exact: true }).click();
            await stock.click();
            await page.waitForURL('**/compras/stock-minimo?laboratory_id=2');
            assert.equal(await stock.getAttribute('aria-current'), 'page');
            assert.deepEqual((await nav.getByRole('link').allTextContents()).map(text => text.trim()), ['Stock minimo', 'OC Automatizadas', 'OC Automatizadas Historial', 'Nueva OC']);
            await page.getByRole('heading', { name: 'Stock Mínimo', exact: true }).waitFor();
            await page.getByRole('link', { name: 'Seleccionar central CDMX', exact: true }).click();
            await page.waitForURL('**/compras/stock-minimo?laboratory_id=1');
            await page.getByRole('link', { name: 'Seleccionar central Monterrey', exact: true }).click();
            await page.waitForURL('**/compras/stock-minimo?laboratory_id=2');
            await nav.getByRole('link', { name: 'Nueva OC', exact: true }).click();
            await page.waitForURL('**/laboratory/2/ordenes-de-compra/nueva');
            assert.ok(page.url().endsWith('/laboratory/2/ordenes-de-compra/nueva'));
            assert.equal(await nav.getByRole('link', { name: 'Nueva OC', exact: true }).getAttribute('aria-current'), 'page');
            await page.getByRole('link', { name: 'Seleccionar central CDMX', exact: true }).click();
            await page.waitForURL('**/laboratory/1/ordenes-de-compra/nueva');
            assert.ok(page.url().endsWith('/laboratory/1/ordenes-de-compra/nueva'));
            assert.equal(await page.locator('#purchase-order-form, #purchase-order-config').count(), 0);
            const newOrderUrl = new URL(await page.locator('[data-purchase-order-popup]').getAttribute('href'));
            assert.ok(newOrderUrl.pathname.endsWith('/compras/nueva'));
            assert.equal(newOrderUrl.searchParams.get('laboratory_id'), '1');
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('selected central is brought into view and arrows disable at each end', async () => {
    const { page, errors } = await fixture(390);
    try {
        await page.goto('http://purchase-navigation.test/mezclaspro/public/admin/almacenes/ordenes-de-compra?section=paid&laboratory_id=7');
        const visible = await page.locator('[data-purchase-carousel]').evaluate(carousel => {
            const selected = carousel.querySelector('[aria-current="page"]').getBoundingClientRect();
            const bounds = carousel.getBoundingClientRect();
            return selected.left >= bounds.left - 1 && selected.right <= bounds.right + 1;
        });
        assert.equal(visible, true);
        assert.equal(await page.getByRole('button', { name: 'Central siguiente', exact: true }).isDisabled(), true);
        assert.equal(await page.getByRole('button', { name: 'Central anterior', exact: true }).isEnabled(), true);
        await page.locator('[data-purchase-carousel] [aria-current="page"]').press('Shift+Tab');
        await page.keyboard.press('Enter');
        await page.waitForURL('**laboratory_id=6');
        assert.equal(new URL(page.url()).searchParams.get('laboratory_id'), '6');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
