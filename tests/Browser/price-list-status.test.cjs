const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/catalog-product-status.js')],
    bundle: true, write: false, outdir: 'out' }).outputFiles.find(file => file.path.endsWith('.js')).text;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openList(category = 'oncologicos', width = 1440, catalogActive = true) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const state = { active: true, fail: false };
    const errors = [], requests = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        if (route.request().url().endsWith('/estado')) {
            requests.push(route.request().postData());
            if (!state.fail) state.active = /name="is_active"\r\n\r\n1/.test(requests.at(-1));
            await route.fulfill({ status: state.fail ? 500 : 200, contentType: 'application/json', body: JSON.stringify(
                state.fail ? { message: 'No se pudo guardar el estado.' } : { is_active: state.active, message: 'Estado guardado.' }
            ) });
        } else if (route.request().url().endsWith('/listas/1')) {
            const body = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/price-list-status.php', category, state.active ? '1' : '0', catalogActive ? '1' : '0'],
                { cwd: root, encoding: 'utf8' });
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}body{font-family:Arial,sans-serif;padding:16px}</style></head><body>${body}<script>${script}</script></body></html>` });
        } else await route.abort();
    });
    await page.goto(`http://catalog.test/admin/catalogo-listas/${category}/listas/1`);
    return { page, state, errors, requests };
}

test('editable green/red list status beside readonly catalog state in every category', async () => {
    for (const category of ['oncologicos', 'nutricionales', 'antibioticos']) {
        const { page, errors, requests } = await openList(category);
        try {
            assert.deepEqual((await page.locator('thead th').allTextContents()).slice(0, 3), ['Estado de Catalogo', 'Estado en Lista de Precios', 'Producto']);
            const toggle = page.getByRole('switch');
            const catalog = page.locator('tbody td').first();
            assert.equal(await catalog.locator('button, input, form').count(), 0);
            await toggle.click();
            await page.getByRole('switch', { checked: false }).waitFor();
            assert.equal(await toggle.innerText(), 'Inactivo');
            assert.equal(await toggle.locator('.catalog-status-track').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(220, 38, 38)');
            assert.equal(await toggle.locator('.catalog-status-track > span').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(255, 255, 255)');
            assert.equal((await catalog.innerText()).trim(), 'Activo');
            assert.match(requests[0], /name="_method"\r\n\r\nPATCH/);
            assert.match(requests[0], /name="_token"/);
            assert.match(requests[0], /name="is_active"\r\n\r\n0/);
            await page.reload();
            await page.getByRole('switch', { checked: false }).waitFor();
            await toggle.focus();
            await page.keyboard.press('Space');
            await page.getByRole('switch', { checked: true }).waitFor();
            assert.equal(await toggle.innerText(), 'Activo');
            assert.equal(await toggle.locator('.catalog-status-track').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('catalog inactivity disables activation and failed saves preserve the displayed state', async () => {
    const blocked = await openList('oncologicos', 1440, false);
    try {
        assert.equal(await blocked.page.getByRole('switch').isDisabled(), true);
        assert.equal(await blocked.page.getByRole('switch').getAttribute('aria-checked'), 'false');
        assert.equal(blocked.requests.length, 0);
    } finally { await blocked.page.close(); }
    const { page, state, errors } = await openList();
    try {
        state.fail = true;
        await page.getByRole('switch').click();
        await page.locator('#catalogStatusMessage[data-error="true"]').waitFor();
        assert.equal(await page.getByRole('switch').getAttribute('aria-checked'), 'true');
        assert.equal(await page.getByRole('switch').isEnabled(), true);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('status columns fit and the price table scrolls without page overflow on desktop/mobile', async () => {
    for (const width of [1840, 390]) {
        const { page, errors } = await openList('oncologicos', width);
        try {
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const toggle = page.getByRole('switch');
            assert.equal(await toggle.evaluate(el => el.scrollWidth <= el.clientWidth), true);
            const columns = await page.locator('tbody td').evaluateAll(cells => cells.slice(0, 3).map(el => ({ left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right })));
            assert.ok(columns[0].right <= columns[1].left + 1 && columns[1].right <= columns[2].left + 1);
            const scroll = page.locator('table').locator('..');
            await scroll.evaluate(el => { el.scrollLeft = el.scrollWidth; });
            const last = await page.locator('thead th').last().boundingBox();
            assert.ok(last.x + last.width <= width);
            await scroll.evaluate(el => { el.scrollLeft = 0; });
            if (process.env.CATALOG_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.CATALOG_SCREENSHOT_DIR, `price-list-status-${width}.png`), fullPage: true });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});
