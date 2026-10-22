const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const source = buildSync({
    stdin: { contents: "import './resources/js/hospital-field-editor.js'; import './resources/js/table-column-filters.js';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser, fixture;
before(async () => {
    fixture = JSON.parse(execFileSync('php', ['tests/Browser/fixtures/institution-hospital-fields.php'], { cwd: root, encoding: 'utf8' }));
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

async function pageFixture(width) {
    const page = await browser.newPage({ viewport: { width, height: 850 } });
    const state = { patches: [], getFailure: false, patchFailure: false, editors: structuredClone(fixture.editors), errors: [] };
    page.on('pageerror', error => state.errors.push(error.message));
    await page.route('**/*', async route => {
        const request = route.request();
        const match = new URL(request.url()).pathname.match(/\/fields\/(services|routes|sellers)$/);
        if (match) {
            const field = match[1];
            if (request.method() === 'PATCH') {
                state.patches.push({ field, ...request.postDataJSON() });
                if (state.patchFailure) return route.fulfill({ status: 422, json: { errors: { values: ['Selecciona vendedores activos.'] } } });
                state.editors[field].selected = request.postDataJSON().values;
                return route.fulfill({ json: { message: 'Guardado' } });
            }
            if (state.getFailure) return route.fulfill({ status: 500, json: { message: 'No se pudo cargar.' } });
            return route.fulfill({ json: state.editors[field] });
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
            <style>${css}</style></head><body>${fixture.html}<script>${source}</script></body></html>` });
    });
    await page.goto('http://hospital-fields.test/admin/instituciones/1/hospitals');
    return { page, state };
}

test('each pencil opens its own editor; close discards drafts and save sends only that column', async () => {
    for (const width of [1366, 390]) {
        const { page, state } = await pageFixture(width);
        try {
            const headers = await page.locator('table thead th').allTextContents();
            for (const [name, edit] of [['Mezclas habilitadas', 'Editar mezclas habilitadas'], ['Ruta asignada', 'Editar ruta asignada'], ['Vendedor asignado', 'Editar vendedor asignado']]) {
                const index = headers.findIndex(text => text.includes(name));
                assert.equal(headers[index + 1].trim(), edit);
            }
            assert.equal(await page.locator('[data-hospital-field-edit]').count(), 3);
            const dialog = page.getByRole('dialog');
            for (const field of ['services', 'routes', 'sellers']) {
                const button = page.locator(`[data-hospital-field-edit][data-field="${field}"]`);
                await button.click();
                await dialog.getByRole('button', { name: 'Guardar cambios' }).waitFor();
                await page.waitForFunction(() => !document.querySelector('[data-hospital-field-save]').disabled);
                assert.equal(await dialog.locator('input:checked').count(), fixture.editors[field].selected.length);
                const firstOption = dialog.locator('input[type="checkbox"]').first();
                const original = await firstOption.isChecked();
                await firstOption.setChecked(!original);
                if (field === 'services') await page.keyboard.press('Escape');
                else if (field === 'routes') await dialog.locator('.hospital-field-cross').click();
                else await dialog.locator('footer').getByRole('button', { name: 'Cerrar', exact: true }).click();
                assert.equal(state.patches.length, 0);
                assert.equal(await button.evaluate(node => node === document.activeElement), true);
                await button.click();
                await page.waitForFunction(() => !document.querySelector('[data-hospital-field-save]').disabled);
                assert.equal(await firstOption.isChecked(), original);
                const box = await dialog.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width && box.y + box.height <= 851);
                if (field === 'services' && process.env.HOSPITAL_FIELD_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.HOSPITAL_FIELD_SCREENSHOTS, `hospital-field-${width}.png`) });
                }
                await dialog.locator('.hospital-field-cross').click();
            }
            await page.locator('[data-hospital-field-edit][data-field="services"]').click();
            await dialog.getByLabel('Antibióticos', { exact: true }).check();
            await Promise.all([
                page.waitForEvent('load'),
                dialog.getByRole('button', { name: 'Guardar cambios' }).click(),
            ]);
            assert.equal(state.patches.length, 1);
            assert.equal(state.patches[0].field, 'services');
            assert.deepEqual(state.patches[0].values, ['oncology', 'antibiotics', 'nutrition']);
            assert.equal(state.patches[0].version, fixture.editors.services.version);
            assert.deepEqual(state.errors, []);
        } finally { await page.close(); }
    }
});

test('loading failures can retry; validation errors keep the modal and choices for correction', async () => {
    const { page, state } = await pageFixture(1366);
    try {
        state.getFailure = true;
        await page.locator('[data-hospital-field-edit][data-field="sellers"]').click();
        const dialog = page.getByRole('dialog');
        await dialog.getByText('No se pudo cargar.').waitFor();
        assert.equal(await dialog.getByRole('button', { name: 'Guardar cambios' }).isDisabled(), true);
        state.getFailure = false;
        await dialog.getByRole('button', { name: 'Reintentar' }).click();
        await dialog.getByLabel('Vendedor nuevo', { exact: true }).check();
        await dialog.getByRole('searchbox').fill('nuevo');
        assert.equal(await dialog.locator('.hospital-field-option:visible').count(), 1);
        state.patchFailure = true;
        await dialog.getByRole('button', { name: 'Guardar cambios' }).click();
        await dialog.getByText('Selecciona vendedores activos.').waitFor();
        assert.equal(await dialog.getByLabel('Vendedor nuevo', { exact: true }).isChecked(), true);
        assert.equal(await dialog.getByRole('button', { name: 'Guardar cambios' }).isEnabled(), true);
        assert.equal(state.patches.length, 1);
        assert.deepEqual(state.errors, []);
    } finally { await page.close(); }
});
