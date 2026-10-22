const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const source = buildSync({
    stdin: { contents: "import './resources/js/enabled-services.js'; import './resources/js/table-column-filters.js';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser, fixture;
before(async () => {
    fixture = JSON.parse(execFileSync('php', ['tests/Browser/fixtures/enabled-services.php'], { cwd: root, encoding: 'utf8' }));
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

async function pageFixture(scope, width = 1366) {
    const page = await browser.newPage({ viewport: { width, height: 850 } });
    const state = { patches: [], getFailure: false, patchFailure: false, editor: structuredClone(fixture[scope]), errors: [] };
    page.on('pageerror', error => state.errors.push(error.message));
    await page.route('**/*', async route => {
        const request = route.request();
        if (new URL(request.url()).pathname.endsWith('/services')) {
            if (request.method() === 'PATCH') {
                const payload = request.postDataJSON();
                state.patches.push(payload);
                if (state.patchFailure) return route.fulfill({ status: 409, json: { message: 'La configuración cambió. Vuelve a abrir la ventana.' } });
                if (scope === 'hospital') state.editor.hospitals[0].services = payload.services;
                else payload.hospital_services.forEach(row => {
                    state.editor.hospitals.find(hospital => hospital.id === row.id).services = row.services;
                });
                return route.fulfill({ json: { message: 'Servicios actualizados' } });
            }
            if (state.getFailure) return route.fulfill({ status: 500, json: { message: 'No se pudo cargar.' } });
            return route.fulfill({ json: state.editor });
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
            <style>${css}</style></head><body>${fixture[scope + 'Html']}<script>${source}</script></body></html>` });
    });
    await page.goto(`http://enabled-services.test/admin/instituciones${scope === 'hospital' ? '/1/hospitals' : ''}`);
    const trigger = scope === 'hospital'
        ? page.getByRole('button', { name: 'Editar servicios habilitados de Hospital de prueba', exact: true })
        : page.getByRole('button', { name: 'Editar servicios habilitados de Institución de prueba', exact: true });
    return { page, state, trigger, dialog: page.locator('[data-enabled-services-dialog]') };
}

async function ready(page) {
    await page.waitForFunction(() => !document.querySelector('[data-services-save]').disabled);
}

test('hospital pencil opens service list, cancels drafts, and saves the selected switch', async () => {
    for (const width of [1366, 390]) {
        const { page, state, trigger, dialog } = await pageFixture('hospital', width);
        try {
            for (const close of ['cross', 'button', 'escape']) {
                await trigger.click();
                await ready(page);
                const toggle = dialog.getByRole('switch', { name: 'Solicitud de cotización de Hospital de prueba' });
                assert.equal(await toggle.getAttribute('aria-checked'), 'true');
                assert.equal(await toggle.innerText(), 'Sí');
                assert.equal(await dialog.locator('.enabled-services-list li').count(), 1);
                await toggle.click();
                assert.equal(await toggle.getAttribute('aria-checked'), 'false');
                assert.equal(await toggle.innerText(), 'No');
                assert.equal(await toggle.evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(220, 38, 38)');
                if (close === 'cross') await dialog.locator('.hospital-field-cross').click();
                else if (close === 'button') await dialog.locator('footer').getByRole('button', { name: 'Cerrar', exact: true }).click();
                else await page.keyboard.press('Escape');
                assert.equal(state.patches.length, 0);
                assert.equal(await trigger.evaluate(node => node === document.activeElement), true);
            }
            await trigger.click();
            await ready(page);
            const box = await dialog.boundingBox();
            assert.ok(box.x >= 0 && box.x + box.width <= width && box.y + box.height <= 851);
            if (process.env.SERVICES_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.SERVICES_SCREENSHOTS, `services-hospital-${width}.png`) });
            await dialog.getByRole('switch').click();
            await Promise.all([page.waitForEvent('load'), dialog.getByRole('button', { name: 'Guardar cambios' }).click()]);
            assert.equal(state.patches.length, 1);
            assert.deepEqual(state.patches[0], { services: { quotation_requests: false }, version: fixture.hospital.version });
            await trigger.click();
            await ready(page);
            assert.equal(await dialog.getByRole('switch').getAttribute('aria-checked'), 'false');
            assert.deepEqual(state.errors, []);
        } finally { await page.close(); }
    }
});

test('institution popup lists hospitals, applies bulk switches and saves mixed selections', async () => {
    for (const width of [1366, 390]) {
        const { page, state, trigger, dialog } = await pageFixture('institution', width);
        try {
            const row = page.locator('#institutions-table tbody tr').filter({ hasText: 'Institución de prueba' });
            assert.equal((await row.locator('[data-institution-enabled-services]').innerText()).trim(), 'Solicitud de cotización (1/2)');
            assert.equal(await row.locator('td,th').nth(13).locator('svg').count(), 1);
            await trigger.click();
            await ready(page);
            assert.equal(await dialog.getByRole('switch').count(), 2);
            assert.deepEqual(await dialog.locator('[role="switch"]').evaluateAll(nodes => nodes.map(node => node.getAttribute('aria-checked'))), ['true', 'false']);
            assert.deepEqual(await dialog.locator('th').allTextContents(), ['Hospital', 'Solicitud de cotizaciónTodosSÍNO']);
            await dialog.getByRole('button', { name: 'Habilitar todos', exact: true }).click();
            assert.equal(await dialog.locator('[role="switch"][aria-checked="true"]').count(), 2);
            await dialog.getByRole('button', { name: 'Inhabilitar todos', exact: true }).click();
            assert.equal(await dialog.locator('[role="switch"][aria-checked="false"]').count(), 2);
            await dialog.getByRole('switch').first().click();
            assert.equal(await dialog.getByRole('switch').first().evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(0, 165, 148)');
            assert.equal(await dialog.locator('[data-services-enabled-count]').innerText(), '1 habilitado');
            const search = dialog.getByRole('searchbox', { name: 'Buscar hospital' });
            await search.fill(state.editor.hospitals[1].name);
            assert.equal(await dialog.locator('tbody tr:visible').count(), 1);
            await dialog.getByRole('button', { name: 'Habilitar todos', exact: true }).click();
            assert.equal(await dialog.locator('[role="switch"][aria-checked="true"]').count(), 2);
            await search.fill('ninguno-coincide');
            assert.equal(await dialog.locator('[data-services-no-results]').isVisible(), true);
            await search.fill('');
            await dialog.getByRole('button', { name: 'Inhabilitar todos', exact: true }).click();
            await dialog.getByRole('switch').first().click();
            const layout = await dialog.evaluate(node => {
                const table = node.querySelector('table');
                const wrap = table.parentElement;
                const cell = table.querySelector('tbody td');
                return { left: cell.getBoundingClientRect().left, edge: wrap.getBoundingClientRect().left,
                    display: getComputedStyle(cell).display, tableWidth: table.getBoundingClientRect().width,
                    width: wrap.clientWidth, scroll: wrap.scrollLeft, minWidth: getComputedStyle(table).minWidth,
                    cellWidth: cell.getBoundingClientRect().width };
            });
            assert.ok(layout.left >= layout.edge - 1 && layout.cellWidth > 0, JSON.stringify(layout));
            if (process.env.SERVICES_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.SERVICES_SCREENSHOTS, `services-institution-${width}.png`) });
            await Promise.all([page.waitForEvent('load'), dialog.getByRole('button', { name: 'Guardar cambios' }).click()]);
            assert.equal(state.patches.length, 1);
            assert.deepEqual(state.patches[0].services, { quotation_requests: false });
            assert.deepEqual(state.patches[0].hospital_services, fixture.institution.hospitals.map((hospital, index) => ({ id: hospital.id, services: { quotation_requests: index === 0 } })));
            assert.deepEqual(state.errors, []);
        } finally { await page.close(); }
    }
});

test('load retries work and save failures keep the selections visible', async () => {
    const { page, state, trigger, dialog } = await pageFixture('hospital');
    try {
        state.getFailure = true;
        await trigger.click();
        await dialog.getByText('No se pudo cargar.').waitFor();
        assert.equal(await dialog.getByRole('button', { name: 'Guardar cambios' }).isDisabled(), true);
        state.getFailure = false;
        await dialog.getByRole('button', { name: 'Reintentar' }).click();
        await ready(page);
        await dialog.getByRole('switch').click();
        state.patchFailure = true;
        await dialog.getByRole('button', { name: 'Guardar cambios' }).click();
        await dialog.getByText('La configuración cambió. Vuelve a abrir la ventana.').waitFor();
        assert.equal(await dialog.getByRole('switch').getAttribute('aria-checked'), 'false');
        assert.equal(await dialog.getByRole('button', { name: 'Guardar cambios' }).isEnabled(), true);
        assert.equal(state.patches.length, 1);
        assert.deepEqual(state.errors, []);
    } finally { await page.close(); }
});
