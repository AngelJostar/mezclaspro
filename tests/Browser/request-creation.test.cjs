const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/workflow-modal.js')], bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;
const shell = readFileSync(path.join(root, 'resources/views/layouts/includes/workflow-modal.blade.php'), 'utf8');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n') + readFileSync(path.join(root, 'resources/css/workflow-modal.css'), 'utf8');
const config = data => `<script type="application/json" id="workflow-page-config">${JSON.stringify(data)}</script>`;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(width, actualForms = true) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    let listLoads = 0;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.hostname !== 'creation.test') return route.abort();
        let body;
        if (url.pathname === '/list') {
            listLoads++;
            body = `<main class="admin-content" style="margin:80px 12px 0 ${width > 640 ? 190 : 6}px"><h1>Lista de Solicitudes</h1>${['nutricionales', 'oncologicos', 'antibioticos'].map(type => `<a href="/create?tipo_solicitud=${type}" data-request-create-popup="${type}">${type}</a>`).join(' | ')}</main>${shell}${config({ embedded: false })}`;
        } else if (actualForms) {
            const type = url.searchParams.get('tipo_solicitud');
            const html = execFileSync('php', ['tests/Browser/fixtures/request-creation.php', type], { cwd: root, encoding: 'utf8' });
            body = `<main class="admin-page workflow-page request-creation-page"><div class="admin-content">${html}</div></main>${config({ embedded: true, requestCreation: true })}`;
        } else {
            const saved = url.pathname === '/saved';
            body = saved ? '<p role="status">Solicitud registrada correctamente</p>' : `<form action="/error"><label>Paciente<input name="patient" value="${url.pathname === '/error' ? 'Dato conservado' : ''}"></label><button>Validar</button><button formaction="/saved">Guardar</button></form>${url.pathname === '/error' ? '<p role="alert">Revisa los datos</p>' : ''}`;
            body += config({ embedded: true, requestCreation: true, requestCreated: saved });
        }
        await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html class="${url.pathname === '/list' ? '' : 'workflow-embedded'}"><head><meta charset="utf-8"><style>${css}</style></head><body>${body}<script>${script}</script></body></html>` });
    });
    await page.goto('http://creation.test/list?estado=pendientes');
    return { page, errors, listLoads: () => listLoads };
}

test('all three real request formats open over the list, and only the upper close button dismisses them', async () => {
    for (const width of [1366, 390]) {
        const { page, errors } = await fixture(width);
        try {
            for (const type of ['nutricionales', 'oncologicos', 'antibioticos']) {
                await page.getByRole('link', { name: type, exact: true }).click();
                const frame = page.frameLocator('[data-workflow-frame]');
                await frame.locator('input[name="request_popup"]').waitFor({ state: 'attached' });
                const field = frame.locator(type === 'nutricionales' ? '[name="nombre_paciente"]' : '[name="paciente_nombre"]');
                await field.fill('Prueba sin guardar');
                await field.press('Escape');
                await page.mouse.click(2, 2);
                await page.getByRole('button', { name: 'Cerrar ventana' }).press('Escape');
                assert.equal(await page.locator('dialog[data-workflow-modal]').evaluate(el => el.open), true);
                assert.equal(await field.inputValue(), 'Prueba sin guardar');
                if (type === 'nutricionales') {
                    for (const name of ['macronutrients', 'electrolytes', 'additives']) {
                        const grid = frame.locator(`[data-layout-grid="${name}"]`);
                        const columns = await grid.evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length);
                        assert.equal(columns, width > 768 ? 2 : 1);
                        const first = await grid.locator('[data-layout-card]').nth(0).boundingBox();
                        const second = await grid.locator('[data-layout-card]').nth(1).boundingBox();
                        if (width > 768) assert.ok(Math.abs(first.y - second.y) < 1 && second.x >= first.x + first.width);
                    }
                    await frame.locator('#macro-layout-grid').scrollIntoViewIfNeeded();
                }
                assert.equal(page.url(), 'http://creation.test/list?estado=pendientes');
                assert.equal(await frame.locator('[data-workflow-popup-close]').isVisible(), false);
                const bounds = await page.locator('[data-workflow-modal]').boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
                assert.ok(bounds.y >= 80 && bounds.y + bounds.height <= 900);
                if (process.env.CREATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.CREATION_SCREENSHOTS, `creation-${type}-${width}.png`) });
                await page.getByRole('button', { name: 'Cerrar ventana' }).click();
                assert.equal(await page.locator('[data-workflow-modal]').evaluate(el => el.open), false);
                assert.equal(await page.getByRole('link', { name: type, exact: true }).evaluate(el => el === document.activeElement), true);
            }
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('validation stays in the frame and saving updates the list only after explicitly closing', async () => {
    const { page, listLoads } = await fixture(1366, false);
    try {
        await page.getByRole('link', { name: 'nutricionales', exact: true }).click();
        const frame = page.frameLocator('iframe');
        await frame.getByRole('button', { name: 'Validar', exact: true }).click();
        await frame.getByRole('alert').waitFor();
        assert.equal(await frame.getByRole('textbox').inputValue(), 'Dato conservado');
        assert.equal(listLoads(), 1);
        await frame.getByRole('button', { name: 'Guardar', exact: true }).click();
        await frame.getByRole('status').waitFor();
        assert.equal(listLoads(), 1);
        await page.getByRole('button', { name: 'Cerrar ventana' }).press('Escape');
        assert.equal(await page.locator('[data-workflow-modal]').evaluate(el => el.open), true);
        await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Cerrar ventana' }).click()]);
        assert.equal(listLoads(), 2);
        assert.equal(page.url(), 'http://creation.test/list?estado=pendientes');
    } finally { await page.close(); }
});
