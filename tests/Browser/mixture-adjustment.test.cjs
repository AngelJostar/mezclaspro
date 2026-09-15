const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const workflow = require('esbuild').buildSync({
    stdin: { contents: "import './resources/js/workflow-modal.js'; import './resources/js/mixture-adjustments.js';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const modal = readFileSync(path.join(root, 'resources/views/layouts/includes/workflow-modal.blade.php'), 'utf8');
const fixture = (name, ...args) => execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', `tests/Browser/fixtures/${name}.php`, ...args], { cwd: root, encoding: 'utf8' });
const document = (content, embedded) => `<!doctype html><html><head><meta charset="utf-8"><style>${styles}</style></head><body><main class="admin-page ${embedded ? 'workflow-page' : ''}"><div class="admin-content">${content}</div></main>${embedded ? '' : modal}<script id="workflow-page-config" type="application/json">${JSON.stringify({ embedded, completed: false })}</script><script>${workflow}</script></body></html>`;

test('adjustment versions open in a popup and central approval remains separate', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const state of ['requested', 'authorized', 'approved']) {
            const table = fixture('hospital-request-columns', 'todas', 'populated', 'Super Admin', state);
            const readOnly = fixture('mixture-adjustment', state, 'central');
            const decision = fixture('mixture-adjustment', state, 'central', 'decision');
            for (const width of [1440, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 900 } });
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                await page.route('**/*', route => {
                    const url = new URL(route.request().url());
                    return route.fulfill({ contentType: 'text/html', body: url.pathname === '/fixture'
                        ? document(table, false) : document(url.searchParams.has('decision') ? decision : readOnly, true) });
                });
                await page.goto('http://localhost/fixture');
                const row = page.locator('tbody tr').first();
                const adjustment = row.locator('td').nth(12).getByRole('link');
                assert.match(await adjustment.innerText(), state === 'requested' ? /Ajuste Solicitado/ : state === 'authorized' ? /Ajuste autorizado/ : /Aprobada con Ajuste/);
                if (state === 'approved') {
                    const approval = row.locator('td').nth(11).getByRole('link', { name: 'Aprobada', exact: true });
                    assert.equal(await approval.count(), 1);
                    assert.equal((await adjustment.innerText()).trim(), 'Aprobada con Ajuste');
                    for (const button of [approval, adjustment]) {
                        assert.equal(await button.evaluate(element => element.scrollWidth <= element.clientWidth), true);
                        assert.equal(await button.evaluate(element => getComputedStyle(element).color), 'rgb(255, 255, 255)');
                    }
                }
                if (state !== 'approved') {
                    assert.equal((await row.locator('td').nth(11).innerText()).trim(), 'Aprobar');
                    assert.equal(await row.locator('td').nth(14).getByRole('button', { name: 'Proceso' }).isDisabled(), true);
                    const approval = row.locator('td').nth(11).getByRole('link', { name: 'Aprobar', exact: true });
                    const colors = element => ({ background: getComputedStyle(element).backgroundColor, text: getComputedStyle(element).color });
                    const expectedColors = { background: 'rgb(251, 191, 36)', text: 'rgb(255, 255, 255)' };
                    assert.deepEqual(await approval.evaluate(colors), expectedColors);
                    assert.deepEqual(await adjustment.evaluate(colors), expectedColors);
                    await approval.hover();
                    assert.deepEqual(await approval.evaluate(colors), { ...expectedColors, background: 'rgb(245, 158, 11)' });
                    await adjustment.hover();
                    assert.deepEqual(await adjustment.evaluate(colors), { ...expectedColors, background: 'rgb(245, 158, 11)' });
                }
                await adjustment.click();
                const frame = page.frameLocator('[data-workflow-frame]');
                await frame.locator('[data-adjustment-version]').waitFor();
                assert.ok(await frame.locator('[data-adjustment-changed="true"]').count() >= 2);
                assert.equal(await frame.getByRole('button', { name: 'Aprobar mezcla', exact: true }).count(), 0);
                const box = await page.locator('[data-workflow-modal]').boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width + 1);
                assert.equal(await frame.locator('body').evaluate(body => body.scrollWidth <= innerWidth + 1), true);
                if (process.env.ADJUSTMENT_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.ADJUSTMENT_SCREENSHOTS, `adjustment-${state}-${width}.png`) });
                await page.locator('[data-workflow-modal-close]').click();
                if (state !== 'approved') {
                    await row.locator('td').nth(11).getByRole('link', { name: 'Aprobar', exact: true }).click();
                    await frame.getByRole('button', { name: 'Aprobar mezcla', exact: true }).waitFor();
                    assert.equal(await frame.getByRole('button', { name: 'Aprobar mezcla', exact: true }).isDisabled(), state === 'requested');
                    assert.equal(await frame.getByRole('button', { name: 'Rechazar mezcla', exact: true }).isEnabled(), true);
                }
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally { await browser.close(); }
});

test('hospital review shows consent and response controls without central approval', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const content = fixture('mixture-adjustment', 'requested', 'hospital');
        const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
        await page.setContent(document(content, true));
        assert.equal(await page.getByRole('button', { name: 'Aprobar mezcla', exact: true }).count(), 0);
        assert.equal(await page.getByRole('checkbox').isChecked(), false);
        await page.getByRole('checkbox').check();
        assert.equal(await page.getByRole('button', { name: 'Autorizar ajustes', exact: true }).isEnabled(), true);
        await page.getByRole('textbox', { name: 'Respuesta del hospital' }).fill('Autorizado para prueba.');
        assert.equal(await page.locator('form').evaluate(form => form.checkValidity()), true);
        await page.close();
    } finally { await browser.close(); }
});
