const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const htmlCache = new Map();
function render(type, removed) {
    const key = JSON.stringify([type, removed]);
    if (!htmlCache.has(key)) htmlCache.set(key, execFileSync('php', ['tests/Browser/fixtures/training.php', type, JSON.stringify(removed)], { cwd: root, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 }));
    return htmlCache.get(key);
}
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });
async function fixture(type, width = 1366) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const removed = [], requests = [], errors = [];
    let failDelete = false;
    let catalog = JSON.parse(render('catalog', [])).programs, failCatalog = false, catalogRequests = 0;
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const req = route.request(), url = new URL(req.url());
        if (url.hostname !== 'training.test') return route.abort();
        if (url.pathname.endsWith('/personal/programas-disponibles')) {
            catalogRequests++;
            return failCatalog ? route.fulfill({ status: 503, json: { message: 'Prueba de conexión' } })
                : route.fulfill({ json: { programs: catalog.filter(program => !removed.includes(program.id)) } });
        }
        if (req.method() === 'POST' && url.pathname.endsWith('/personal')) {
            requests.push(req.postData());
            return route.fulfill({ contentType: 'text/html', body: 'Personal recibido en prueba' });
        }
        if (req.method() === 'DELETE') {
            requests.push(req.postDataJSON());
            if (failDelete) return route.fulfill({ status: 500, json: { message: 'Error temporal de prueba' } });
            removed.push(decodeURIComponent(url.pathname.split('/').pop()));
            return route.fulfill({ json: { deleted: removed.at(-1) } });
        }
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="csrf-token" content="test"><style>' + css + '</style>' + render(type, removed) });
    });
    await page.goto('http://training.test/admin/capacitaciones/' + type);
    return { page, removed, requests, errors, setFailure: value => { failDelete = value; }, catalog,
        setCatalog: value => { catalog = value; }, setCatalogFailure: value => { failCatalog = value; }, catalogRequests: () => catalogRequests };
}
test('deletion requires two Yes actions; No preserves edits; failures keep the program; deletion survives reload', async () => {
    const { page, requests, errors, setFailure } = await fixture('programas');
    try {
        const card = page.locator('[data-program-card][data-program-id="induccion"]');
        await card.locator('[data-edit-program]').click();
        const edit = page.locator('[data-program-modal]');
        await edit.locator('[name=title]').fill('Borrador sin guardar');
        await edit.getByRole('button', { name: 'Eliminar Programa' }).click();
        const confirm = page.locator('[data-delete-program-modal]');
        assert.match(await confirm.textContent(), /Induccion y seguridad operativa/);
        await confirm.getByRole('button', { name: 'No', exact: true }).click();
        assert.equal(await edit.locator('[name=title]').inputValue(), 'Borrador sin guardar');
        await edit.getByRole('button', { name: 'Eliminar Programa' }).click();
        await confirm.getByRole('button', { name: 'Sí', exact: true }).click();
        assert.match(await confirm.textContent(), /no podrás recuperarlo/);
        assert.equal(requests.length, 0);
        await confirm.getByRole('button', { name: 'No', exact: true }).click();
        assert.equal(await edit.locator('[name=title]').inputValue(), 'Borrador sin guardar');
        await edit.getByRole('button', { name: 'Eliminar Programa' }).click();
        await confirm.getByRole('button', { name: 'Sí', exact: true }).click();
        setFailure(true);
        await confirm.getByRole('button', { name: 'Sí', exact: true }).click();
        await confirm.getByRole('alert').waitFor();
        assert.equal(await card.count(), 1);
        setFailure(false);
        await Promise.all([page.waitForEvent('load'), confirm.getByRole('button', { name: 'Sí', exact: true }).click()]);
        assert.equal(await card.count(), 0);
        await page.reload();
        assert.equal(await card.count(), 0);
        assert.equal(requests.at(-1).confirmation_name, 'Induccion y seguridad operativa');
        for (const id of ['proveedores', 'almacenamiento']) {
            await page.locator(`[data-program-card][data-program-id="${id}"] [data-edit-program]`).click();
            await edit.getByRole('button', { name: 'Eliminar Programa' }).click();
            await confirm.getByRole('button', { name: 'Sí', exact: true }).click();
            await Promise.all([page.waitForEvent('load'), confirm.getByRole('button', { name: 'Sí', exact: true }).click()]);
        }
        assert.equal(await page.locator('[data-program-card]').count(), 0);
        assert.match(await page.locator('[data-selected-program-name]').first().textContent(), /Sin programas/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
test('personnel training checkboxes select individual modules, whole programs and all programs', async () => {
    for (const width of [1366, 390]) {
        const { page, errors } = await fixture('personal', width);
        try {
            await page.locator('[data-open-personnel-create]').click();
            const modal = page.locator('[data-personnel-create-modal]');
            const all = modal.locator('[data-training-select-all]');
            await all.check();
            assert.equal(await modal.locator('[data-training-module]:checked').count(), 12);
            const item = modal.locator('[data-training-module]').first();
            await item.uncheck();
            assert.equal(await all.evaluate(el => el.indeterminate), true);
            const program = item.locator('xpath=ancestor::fieldset');
            assert.equal(await program.locator('[data-training-program-all]').evaluate(el => el.indeterminate), true);
            const data = await modal.locator('form').evaluate(form => [...new FormData(form).entries()].filter(([name]) => name.startsWith('training_')));
            assert.equal(data.filter(([name]) => name === 'training_all[]').length, 2);
            await all.check();
            await all.uncheck();
            assert.equal(await modal.locator('[data-training-module]:checked').count(), 0);
            await program.locator('[data-training-program-all]').check();
            await program.evaluate(el => el.scrollIntoView({ block: 'center' }));
            if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, `training-access-${width}.png`) });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('personnel enrollment refreshes deleted programs and renamed modules without losing fields, files or valid selections', async () => {
    const { page, catalog, setCatalog, setCatalogFailure, errors } = await fixture('personal');
    try {
        await page.locator('[data-open-personnel-create]').click();
        const modal = page.locator('[data-personnel-create-modal]'), field = modal.locator('[data-personnel-training-access]');
        await field.locator('xpath=self::*[@data-catalog-state="ready"]').waitFor();
        await modal.locator('[name=first_name]').fill('Borrador conservado');
        await modal.locator('[name=cv]').setInputFiles({ name: 'CV-prueba.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 prueba') });
        await field.locator('[data-program-id=induccion] [data-training-module][value="module-1"]').check();
        await field.locator('[data-program-id=almacenamiento] [data-training-program-all]').check();
        const updated = structuredClone(catalog.filter(program => program.id !== 'almacenamiento'));
        const induction = updated.find(program => program.id === 'induccion');
        induction.title = 'Inducción actualizada'; induction.modules[0].title = 'Bienvenida revisada';
        induction.modules.push({ id: 'module-added', stage: 'Módulo 5', title: 'Módulo recién creado' });
        setCatalog(updated);
        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
        await field.getByText('Bienvenida revisada', { exact: false }).waitFor();
        assert.equal(await field.locator('[data-program-id=almacenamiento]').count(), 0);
        assert.equal(await field.locator('[data-program-id=induccion] [value="module-1"]').isChecked(), true);
        assert.equal(await field.locator('[value="module-added"]').isChecked(), false);
        assert.equal(await modal.locator('[name=first_name]').inputValue(), 'Borrador conservado');
        assert.equal(await modal.locator('[name=cv]').evaluate(el => el.files[0].name), 'CV-prueba.pdf');
        induction.modules[0].title = 'Actualización con formulario abierto'; setCatalog(updated);
        await field.getByText('Actualización con formulario abierto', { exact: false }).waitFor({ timeout: 20000 });
        assert.equal(await field.locator('[data-program-id=induccion] [value="module-1"]').isChecked(), true);
        await modal.evaluate(el => el.close());
        induction.modules[0].title = 'Bienvenida al reabrir'; setCatalog(updated);
        await page.locator('[data-open-personnel-create]').click();
        await field.getByText('Bienvenida al reabrir', { exact: false }).waitFor();
        setCatalogFailure(true);
        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
        await field.locator('[data-training-catalog-retry]:visible').waitFor();
        assert.equal(await field.locator('[data-program-id=induccion] [value="module-1"]').isChecked(), true);
        setCatalogFailure(false); setCatalog([]);
        await field.locator('[data-training-catalog-retry]').click();
        await field.locator('[data-training-catalog-empty]:visible').waitFor();
        assert.equal(await field.locator('[data-training-module]').count(), 0);
        assert.equal(await modal.locator('[name=first_name]').inputValue(), 'Borrador conservado');
        setCatalog(updated);
        await page.evaluate(() => window.dispatchEvent(new StorageEvent('storage', { key: 'mezclaspro.training.catalog-revision' })));
        await field.getByText('Bienvenida al reabrir', { exact: false }).waitFor();
        assert.equal(await field.locator('[data-training-module]:checked').count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('saving personnel rechecks the catalog and stops when a selected program was deleted or the connection fails', async () => {
    const { page, catalog, setCatalog, setCatalogFailure, requests, errors } = await fixture('personal');
    try {
        await page.locator('[data-open-personnel-create]').click();
        const modal = page.locator('[data-personnel-create-modal]'), form = modal.locator('form'), field = modal.locator('[data-personnel-training-access]');
        await field.locator('xpath=self::*[@data-catalog-state="ready"]').waitFor();
        await field.locator('[data-program-id=induccion] [value="module-1"]').check();
        // The test isolates the pre-submit catalog check; no real personnel are created.
        await form.evaluate(el => { el.noValidate = true; const lab = el.querySelector('[data-personnel-laboratory]'); lab.add(new Option('Central de prueba', '1')); lab.value = '1'; });
        setCatalog(catalog.filter(program => program.id !== 'induccion'));
        await modal.locator('[data-save-personnel]').click();
        await field.getByText('El catálogo cambió.', { exact: false }).waitFor();
        assert.equal(requests.length, 0);
        assert.equal(await field.locator('[data-program-id=induccion]').count(), 0);
        setCatalogFailure(true);
        await modal.locator('[data-save-personnel]').click();
        await field.locator('[data-training-catalog-retry]:visible').waitFor();
        assert.equal(requests.length, 0);
        assert.equal(await modal.locator('[data-save-personnel]').isEnabled(), true);
        setCatalogFailure(false);
        await Promise.all([page.waitForEvent('load'), modal.locator('[data-save-personnel]').click()]);
        assert.equal(requests.length, 1);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
