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
const bodies = Object.fromEntries(['oncologicos', 'nutricionales', 'antibioticos'].map(category => [category,
    execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/catalog-availability.php', category], { cwd: root, encoding: 'utf8' }),
]));
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openCatalog(category = 'oncologicos', width = 1440) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const requests = [], errors = [];
    const state = { fail: false };
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const request = route.request();
        if (request.url().endsWith('/estado')) {
            requests.push(request.postData());
            const active = /name="is_available"\r\n\r\n1/.test(request.postData());
            await route.fulfill({ status: state.fail ? 500 : 200, contentType: 'application/json', body: JSON.stringify(
                state.fail ? { message: 'No se pudo guardar el estado.' } : { is_available: active, message: 'Estado guardado.' }
            ) });
        } else if (request.url().endsWith('/catalogo')) {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}body{font-family:Arial,sans-serif;padding:16px}</style></head><body>${bodies[category]}<script>${script}</script></body></html>` });
        } else { await route.abort(); }
    });
    await page.goto(`http://catalog.test/admin/catalogo-listas/${category}/catalogo`);
    return { page, requests, errors, state };
}

test('switch updates color and label in every category and submits explicit state with CSRF', async () => {
    for (const category of Object.keys(bodies)) {
        const { page, requests, errors } = await openCatalog(category);
        try {
            const toggle = page.getByRole('switch').first();
            assert.equal(await toggle.getAttribute('aria-checked'), 'true');
            assert.equal(await toggle.locator('.catalog-status-track').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
            await toggle.click();
            await page.locator('[data-catalog-status-form]').first().locator('[aria-checked="false"]').waitFor();
            assert.equal(await toggle.innerText(), 'Inactivo');
            assert.equal(await toggle.locator('.catalog-status-track').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(220, 38, 38)');
            assert.match(requests[0], /name="_method"\r\n\r\nPATCH/);
            assert.match(requests[0], /name="_token"/);
            assert.match(requests[0], /name="is_available"\r\n\r\n0/);
            await toggle.focus();
            await page.keyboard.press('Space');
            await page.locator('[data-catalog-status-form]').first().locator('[aria-checked="true"]').waitFor();
            assert.equal(await toggle.innerText(), 'Activo');
            assert.equal(requests.length, 2);
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('failed saves keep the old state and can be retried', async () => {
    const { page, state, requests, errors } = await openCatalog();
    try {
        state.fail = true;
        const toggle = page.getByRole('switch').first();
        await toggle.click();
        await page.locator('#catalogStatusMessage[data-error="true"]').waitFor();
        assert.equal(await toggle.getAttribute('aria-checked'), 'true');
        assert.equal(await toggle.isEnabled(), true);
        state.fail = false;
        await toggle.click();
        await page.locator('#catalogStatusMessage[data-error="false"]').waitFor();
        assert.equal(await toggle.getAttribute('aria-checked'), 'false');
        assert.equal(requests.length, 2);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('state filter and product columns still match after inserting the switch', async () => {
    const { page, errors } = await openCatalog();
    try {
        const headers = await page.locator('#catalogTable th').allTextContents();
        assert.match(headers[0], /Estado/);
        assert.match(headers[1], /Producto/);
        await page.locator('.js-catalog-column-filter[data-column="0"]').click();
        const panel = page.locator('#catalog-column-filter-panel');
        await panel.getByLabel('Inactivo', { exact: true }).uncheck();
        await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
        assert.equal(await page.locator('.catalog-row:visible').count(), 1);
        await page.getByRole('switch', { checked: true }).click();
        await page.locator('#catalogStatusMessage[data-error="false"]').waitFor();
        assert.equal(await page.locator('.catalog-row:visible').count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('switch stays before Product, has no clipped text, and table scrolls on desktop and mobile', async () => {
    for (const width of [1840, 390]) {
        const { page, errors } = await openCatalog('oncologicos', width);
        try {
            const toggle = page.getByRole('switch').first();
            const product = page.locator('.catalog-row').first().locator('td').nth(1);
            const left = await toggle.boundingBox(), right = await product.boundingBox();
            assert.ok(left.x + left.width <= right.x + 1);
            assert.equal(await toggle.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const scroll = page.locator('#catalogTable').locator('..');
            await scroll.evaluate(el => { el.scrollLeft = el.scrollWidth; });
            const last = await page.locator('#catalogTable th').last().boundingBox();
            assert.ok(last.x + last.width <= width);
            await scroll.evaluate(el => { el.scrollLeft = 0; });
            if (process.env.CATALOG_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.CATALOG_SCREENSHOT_DIR, `catalog-status-${width}.png`), fullPage: true });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});
