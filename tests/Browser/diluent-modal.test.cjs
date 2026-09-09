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
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/diluent-modal.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const catalogUrl = 'http://diluent.test/mezclaspro/public/admin/catalogo-listas/insumos/catalogo';

function fixture() {
    const php = spawn('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/diluent-modal.php'],
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

test('new diluent modal follows the reference and saves through the actual controller without leaving the catalog on cancel or error', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const [width, height] of [[1619, 971], [1366, 768], [390, 844], [320, 680]]) {
            const server = fixture();
            const page = await browser.newPage({ viewport: { width, height } });
            page.setDefaultTimeout(10000);
            const errors = [];
            let posts = 0;
            let failLoad = false;
            page.on('pageerror', error => errors.push(error.message));
            try {
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.origin !== 'http://diluent.test') return route.abort();
                    if (url.pathname.endsWith('/font.woff2')) {
                        return route.fulfill({ contentType: 'font/woff2', body: readFileSync(path.join(root, 'public/fonts/figtree/figtree-latin-400-normal.woff2')) });
                    }
                    if (route.request().method() === 'POST') {
                        posts++;
                        const body = new Response(route.request().postDataBuffer(), { headers: { 'content-type': route.request().headers()['content-type'] } });
                        const result = await server.send({ type: 'store', fields: Object.fromEntries(await body.formData()) });
                        return route.fulfill({ status: result.status, contentType: 'application/json', body: JSON.stringify(result.json) });
                    }
                    if (url.pathname.endsWith('/productos/nuevo')) {
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
                const open = page.getByRole('link', { name: 'Nuevo diluyente', exact: true });
                const dialog = page.getByRole('dialog', { name: 'Nuevo diluyente', exact: true });
                await page.getByRole('searchbox').fill('GLU');
                await open.click();
                await dialog.locator('[data-new-diluent-form]').waitFor();
                await page.waitForFunction(() => document.activeElement.id === 'generic_description');
                assert.equal(page.url(), catalogUrl);
                assert.equal(await dialog.locator('svg[data-diluent-icon="syringe"]').count(), 1);
                assert.equal(await dialog.locator('[name="concentration"]').inputValue(), '0');
                assert.equal(await dialog.locator('[name="stability_hours"]').getAttribute('required'), '');
                await dialog.locator('[name="generic_description"]').fill('Cambio cancelado');
                await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
                assert.equal(await dialog.count(), 0);
                assert.equal(await page.getByRole('searchbox').inputValue(), 'GLU');
                assert.equal(posts, 0);
                assert.equal(await open.evaluate(el => el === document.activeElement), true);

                failLoad = true;
                await open.click();
                await page.getByText('No se pudo cargar el formulario. Intenta de nuevo.').waitFor();
                failLoad = false;
                await dialog.getByRole('button', { name: 'Reintentar', exact: true }).click();
                await dialog.locator('[data-new-diluent-form]').waitFor();
                await page.keyboard.press('Escape');
                assert.equal(await dialog.count(), 0);

                await page.goto(`${catalogUrl}?nuevo_diluyente=1`);
                await dialog.locator('[data-new-diluent-form]').waitFor();
                assert.equal(page.url(), catalogUrl);
                assert.equal(await dialog.locator('[name="generic_description"]').inputValue(), '');
                const lab = dialog.locator('[name="laboratory_id"]');
                const warehouse = dialog.locator('[name="warehouse_id"]');
                assert.equal(await lab.locator('option[value="4"]').count(), 0);
                assert.equal(await warehouse.locator('option[value="3"]').count(), 0);
                await lab.selectOption('2');
                assert.equal(await warehouse.inputValue(), '2');
                assert.equal(await warehouse.locator('option[value="1"]').count(), 0);
                await lab.selectOption('3');
                assert.equal(await warehouse.isDisabled(), true);
                assert.equal(await dialog.getByRole('button', { name: 'Guardar diluyente', exact: true }).isDisabled(), true);
                await lab.selectOption('1');
                const values = { generic_description: 'Diluyente nuevo de prueba', commercial_name: 'Marca de prueba',
                    concentration: '500', presentation: 'Bolsa 500 mL nueva', stability_hours: '24', manufacturer: 'Fabricante de prueba' };
                for (const [name, value] of Object.entries(values)) await dialog.locator(`[name="${name}"]`).fill(value);
                const dimensions = await dialog.boundingBox();
                assert.ok(dimensions.x >= 0 && dimensions.x + dimensions.width <= width + 1 && dimensions.y >= 0 && dimensions.y + dimensions.height <= height + 1);
                assert.equal(await dialog.evaluate(el => el.scrollWidth > el.clientWidth), false);
                const saveBox = await dialog.getByRole('button', { name: 'Guardar diluyente', exact: true }).boundingBox();
                assert.ok(saveBox.y + saveBox.height <= height && saveBox.x + saveBox.width <= width);
                if (process.env.DILUENT_MODAL_SCREENSHOTS && width !== 320) {
                    await dialog.locator('.diluent-modal-body').evaluate(el => { el.scrollTop = 0; });
                    await page.screenshot({ path: path.join(process.env.DILUENT_MODAL_SCREENSHOTS, `new-diluent-${width}.png`) });
                }

                await warehouse.evaluate(el => { el.add(new Option('Otro almacen', '2')); el.value = '2'; });
                await dialog.getByRole('button', { name: 'Guardar diluyente', exact: true }).click();
                await dialog.locator('[data-field-error="warehouse_id"]:not([hidden])').waitFor();
                assert.equal(await dialog.locator('[name="generic_description"]').inputValue(), values.generic_description);
                assert.equal((await server.send({ type: 'records' })).rows.length, 2);
                await lab.selectOption('2');
                await dialog.locator('form').evaluate(form => { form.requestSubmit(); form.requestSubmit(); });
                await page.waitForURL(catalogUrl);
                await page.getByRole('link', { name: 'Nuevo diluyente', exact: true }).waitFor();
                await page.getByText('Diluyente nuevo de prueba', { exact: true }).waitFor();
                assert.equal(posts, 2);
                assert.equal(await page.getByRole('dialog').count(), 0);
                const records = (await server.send({ type: 'records' })).rows;
                assert.equal(records.length, 3);
                assert.equal(records[2].stability_hours, 24);
                assert.equal(Number(records[2].warehouse_id), 2);
                assert.equal(Number(records[2].stock_actual), 0);
                assert.deepEqual(errors, []);
            } finally { await page.close(); await server.close(); }
        }
    } finally { await browser.close(); }
});
