const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { spawn } = require('node:child_process');
const { createInterface } = require('node:readline');
const { once } = require('node:events');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/consumable-modal.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const catalogUrl = 'http://consumable.test/mezclaspro/public/admin/catalogo-listas/insumos/catalogo?tipo_insumo=consumibles';

function fixture() {
    const php = spawn('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/consumable-modal.php'],
        { cwd: root, stdio: ['pipe', 'pipe', 'pipe'] });
    const waiting = [];
    let stderr = '';
    php.stderr.on('data', data => { stderr += data; });
    const lines = createInterface({ input: php.stdout });
    lines.on('line', line => {
        const pending = waiting.shift();
        if (!pending) return;
        try {
            const result = JSON.parse(line);
            if (result.fixture_error) pending.reject(new Error(result.fixture_error));
            else pending.resolve(result);
        } catch (error) { pending.reject(error); }
    });
    php.on('exit', code => waiting.splice(0).forEach(pending => pending.reject(new Error(`PHP ${code}: ${stderr}`))));
    return {
        send(command) {
            return new Promise((resolve, reject) => {
                const timer = setTimeout(() => reject(new Error(`Fixture timeout: ${stderr}`)), 15000);
                waiting.push({ resolve: value => { clearTimeout(timer); resolve(value); }, reject: error => { clearTimeout(timer); reject(error); } });
                php.stdin.write(`${JSON.stringify(command)}\n`);
            });
        },
        async close() {
            const exited = once(php, 'exit');
            php.stdin.end();
            await exited;
            lines.close();
        },
    };
}

test('consumable modal supports multiple presentations, validation, cancel and real controller persistence on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const [width, height] of [[1642, 958], [1366, 768], [390, 844], [320, 680]]) {
            const server = fixture();
            const page = await browser.newPage({ viewport: { width, height } });
            page.setDefaultTimeout(10000);
            const errors = [];
            let posts = 0;
            let failLoad = false;
            let failSave = false;
            page.on('pageerror', error => errors.push(error.message));
            try {
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.origin !== 'http://consumable.test') return route.abort();
                    if (url.pathname.endsWith('/font.woff2')) {
                        return route.fulfill({ contentType: 'font/woff2', body: readFileSync(path.join(root, 'public/fonts/figtree/figtree-latin-400-normal.woff2')) });
                    }
                    if (route.request().method() === 'POST') {
                        posts++;
                        if (failSave) return route.fulfill({ status: 503, body: 'Unavailable' });
                        const body = new Response(route.request().postDataBuffer(), { headers: { 'content-type': route.request().headers()['content-type'] } });
                        const result = await server.send({ type: 'store', body: new URLSearchParams(await body.formData()).toString() });
                        return route.fulfill({ status: result.status, contentType: 'application/json', body: JSON.stringify(result.json) });
                    }
                    if (url.pathname.endsWith('/consumibles/crear')) {
                        if (failLoad) return route.fulfill({ status: 503, body: 'Unavailable' });
                        const result = await server.send({ type: 'form' });
                        return route.fulfill({ contentType: 'text/html', body: result.html });
                    }
                    const result = await server.send({ type: 'catalog' });
                    return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
                        <style>${css}@font-face{font-family:Figtree;src:url('/font.woff2')}body{font-family:Figtree,sans-serif;padding:16px}</style></head>
                        <body>${result.html}<script>${script}</script></body></html>` });
                });
                await page.goto(catalogUrl);
                const open = page.getByRole('link', { name: 'Nuevo consumible', exact: true });
                const dialog = page.getByRole('dialog', { name: 'Nuevo consumible', exact: true });
                const rows = dialog.locator('[data-consumable-presentation]');
                const name = dialog.locator('[name="name"]');
                const save = dialog.getByRole('button', { name: 'Guardar consumible', exact: true });
                await page.getByRole('searchbox').fill('Jeringa');
                await open.click();
                await dialog.locator('[data-new-consumable-form]').waitFor();
                await page.waitForFunction(() => document.activeElement.id === 'consumable-name');
                assert.equal(page.url(), catalogUrl);
                assert.equal(await dialog.locator('svg[data-consumable-icon="box"]').count(), 1);
                assert.equal(await dialog.locator('[name="unit"]').inputValue(), 'pieza');
                assert.equal(await rows.count(), 1);
                assert.equal(await rows.locator('[data-remove-presentation]').isDisabled(), true);
                await name.fill('Cambio cancelado');
                await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
                assert.equal(await dialog.count(), 0);
                assert.equal(await page.getByRole('searchbox').inputValue(), 'Jeringa');
                assert.equal(posts, 0);
                assert.equal(await open.evaluate(el => el === document.activeElement), true);

                failLoad = true;
                await open.click();
                await page.getByText('No se pudo cargar el formulario. Intenta de nuevo.').waitFor();
                failLoad = false;
                await dialog.getByRole('button', { name: 'Reintentar', exact: true }).click();
                await dialog.locator('[data-new-consumable-form]').waitFor();
                await page.keyboard.press('Escape');
                assert.equal(await dialog.count(), 0);

                const creation = await server.send({ type: 'create' });
                await page.goto(creation.redirect);
                await dialog.locator('[data-new-consumable-form]').waitFor();
                assert.equal(page.url(), catalogUrl);
                assert.equal(await name.inputValue(), '');
                const dimensions = await dialog.boundingBox();
                assert.ok(dimensions.x >= 0 && dimensions.x + dimensions.width <= width + 1 && dimensions.y >= 0 && dimensions.y + dimensions.height <= height + 1);
                assert.equal(await dialog.evaluate(el => el.scrollWidth > el.clientWidth), false);
                assert.equal(await dialog.locator('.diluent-modal-body').evaluate(el => el.scrollWidth > el.clientWidth), false);
                const saveBox = await save.boundingBox();
                assert.ok(saveBox.y + saveBox.height <= height && saveBox.x + saveBox.width <= width);
                if (process.env.CONSUMABLE_MODAL_SCREENSHOTS && width !== 320) {
                    await page.screenshot({ path: path.join(process.env.CONSUMABLE_MODAL_SCREENSHOTS, `new-consumable-${width}.png`) });
                }
                await save.click();
                assert.equal(posts, 0);
                await name.fill('Jeringa');
                await rows.nth(0).locator('[data-presentation-field="presentation"]').fill('Caja con 100 piezas');
                await rows.nth(0).locator('[data-presentation-field="commercial_name"]').fill('Marca nueva');
                await rows.nth(0).locator('[data-presentation-field="manufacturer"]').fill('Fabricante nuevo');
                await dialog.getByRole('button', { name: 'Agregar presentaci\u00f3n', exact: true }).click();
                await rows.nth(1).locator('[data-presentation-field="presentation"]').fill('Fila para eliminar');
                await dialog.getByRole('button', { name: 'Agregar presentaci\u00f3n', exact: true }).click();
                await rows.nth(2).locator('[data-presentation-field="presentation"]').fill('Caja con 50 piezas');
                await rows.nth(1).locator('[data-remove-presentation]').click();
                assert.equal(await rows.count(), 2);
                assert.equal(await rows.nth(1).locator('[data-presentation-field="presentation"]').getAttribute('name'), 'presentations[1][presentation]');
                assert.equal(await rows.nth(1).locator('[data-presentation-field="presentation"]').inputValue(), 'Caja con 50 piezas');
                await save.click();
                await dialog.locator('[data-field-error="name"]:not([hidden])').waitFor();
                assert.equal(await name.getAttribute('aria-invalid'), 'true');
                assert.equal(await rows.count(), 2);
                assert.equal((await server.send({ type: 'records' })).rows.length, 3);
                await name.fill('Consumible nuevo de prueba');
                // Bypass native validation once to exercise nested server-side errors.
                await rows.nth(1).locator('[data-presentation-field="presentation"]').evaluate(input => { input.required = false; input.value = ''; });
                await save.click();
                await dialog.locator('[data-field-error="presentations.1.presentation"]:not([hidden])').waitFor();
                await rows.nth(1).locator('[data-presentation-field="presentation"]').fill('Caja con 50 piezas');
                failSave = true;
                await save.click();
                await dialog.getByText('No se pudo confirmar el guardado. Revisa el cat\u00e1logo antes de intentar de nuevo.').waitFor();
                assert.equal(await name.inputValue(), 'Consumible nuevo de prueba');
                assert.equal(await save.isEnabled(), true);
                failSave = false;
                await dialog.locator('form').evaluate(form => { form.requestSubmit(); form.requestSubmit(); });
                await page.waitForURL(catalogUrl);
                await page.getByText('Consumible nuevo de prueba', { exact: true }).first().waitFor();
                assert.equal(posts, 4);
                assert.equal(await page.getByRole('dialog').count(), 0);
                const records = (await server.send({ type: 'records' })).rows;
                assert.equal(records.length, 4);
                const created = records[3];
                assert.equal(created.unit, 'pieza');
                assert.equal(created.catalog_presentations.length, 2);
                assert.equal(created.catalog_presentations[0].commercial_name, 'Marca nueva');
                assert.equal(created.catalog_presentations[1].presentation, 'Caja con 50 piezas');
                assert.deepEqual(errors, []);
            } finally { await page.close(); await server.close(); }
        }
    } finally { await browser.close(); }
});
