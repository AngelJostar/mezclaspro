const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = readFileSync(path.join(root, 'public/build', manifest['resources/css/app.css'].file), 'utf8');
const script = buildSync({
    stdin: { contents: "import './resources/js/maintenance-identification'; import './resources/js/table-column-filters';", resolveDir: root },
    bundle: true, write: false, format: 'iife',
}).outputFiles[0].text;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(width = 1320) {
    const body = execFileSync('php', ['tests/Browser/fixtures/maintenance-identification.php'], { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => route.request().url() === 'http://maintenance.test/'
        ? route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}body{padding:16px}</style></head><body class="admin-page"><main class="admin-content">${body}</main><script>${script}</script></body></html>` })
        : route.abort());
    await page.goto('http://maintenance.test/');
    return { page, errors };
}

test('compact columns show Ver instead of identification and open an accessible in-page dialog', async () => {
    for (const width of [1320, 390]) {
        const { page, errors } = await fixture(width);
        try {
            const table = page.locator('.maintenance-calendar-table');
            const buttons = table.locator('[data-view-identification]');
            const cell = table.locator('tbody tr').first().locator('td').nth(4);
            assert.equal((await cell.innerText()).trim(), 'Ver');
            const widths = await table.locator('thead th').evaluateAll(headers => headers.slice(0, 7).map(h => h.getBoundingClientRect().width));
            assert.ok(widths.reduce((sum, value) => sum + value, 0) <= 800);
            assert.ok(widths[4] <= 132);
            assert.equal(await table.locator('thead th').nth(4).locator('button').count(), 1);
            assert.equal(await table.locator('thead th > div > span').evaluateAll(labels => labels.every(label => label.scrollWidth <= label.clientWidth + 1)), true);
            await buttons.first().click();
            const dialog = page.getByRole('dialog', { name: 'Identificación', exact: true });
            assert.equal(await dialog.isVisible(), true);
            assert.match(await dialog.innerText(), /ONC: IM-02, IM-03, IM-04/);
            assert.match(await dialog.innerText(), /IM-11/);
            const close = dialog.getByRole('button', { name: 'Cerrar ventana' });
            assert.equal(await close.locator('svg').count(), 1);
            const box = await dialog.boundingBox();
            const x = await close.boundingBox();
            assert.ok(box.x >= 0 && box.x + box.width <= width);
            assert.ok(x.x > box.x + box.width / 2 && x.y < box.y + 60);
            assert.equal(page.context().pages().length, 1);
            if (process.env.MAINTENANCE_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.MAINTENANCE_SCREENSHOT_DIR, `maintenance-identification-${width}.png`) });
            }
            await close.click();
            assert.equal(await dialog.isVisible(), false);
            assert.equal(await buttons.first().evaluate(el => el === document.activeElement), true);
            await buttons.nth(1).click();
            assert.match(await dialog.innerText(), /Sin identificacion registrada/);
            await page.keyboard.press('Escape');
            assert.equal(await dialog.isVisible(), false);
            await buttons.nth(2).click();
            assert.match(await dialog.innerText(), /<script>alert\(1\)<\/script>/);
            assert.equal(await dialog.locator('script').count(), 0);
            assert.equal(await dialog.locator('.identification-body').evaluate(el => el.scrollHeight > el.clientHeight), true);
            await page.mouse.click(1, 1);
            assert.equal(await dialog.isVisible(), false);
            await page.locator('#maintenance-service-catalog [data-view-identification]').first().click();
            assert.match(await dialog.innerText(), /ONC: IM-02/);
            await page.keyboard.press('Escape');
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('identification filters retain the original values and ordinary column filters still work', async () => {
    const { page, errors } = await fixture();
    try {
        const table = page.locator('.maintenance-calendar-table');
        await table.getByRole('button', { name: 'Filtrar Identificación', exact: true }).click();
        const panel = page.locator('[data-filter-options]:visible').locator('..').locator('..');
        const firstValue = (await table.locator('tbody tr').first().locator('td').nth(4).getAttribute('data-column-filter-value')).replace(/\s+/g, ' ').trim();
        const options = panel.locator('.js-column-filter-option');
        assert.equal(await options.filter({ hasText: /^Ver$/ }).count(), 0);
        await panel.locator('[data-filter-all]').uncheck();
        await options.filter({ hasText: firstValue }).locator('input').check();
        await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
        assert.equal(await table.locator('tbody tr:visible').count(), 1);
        await table.locator('tbody tr:visible [data-view-identification]').click();
        assert.match(await page.getByRole('dialog').innerText(), /IM-11/);
        await page.keyboard.press('Escape');
        await table.getByRole('button', { name: 'Filtrar Proveedor', exact: true }).click();
        assert.match(await page.locator('[data-filter-options]:visible').innerText(), /Proveedor Uno/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
