const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const scripts = buildSync({ stdin: {
    contents: "import './resources/js/adjustment-proposal.js'; import './resources/js/workflow-modal.js'; import './resources/js/mixture-workflow.js'; import './resources/js/table-column-filters.js';", resolveDir: root,
}, bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;

async function pageFor(browser, width, kind, retry = false) {
    const fixture = kind === 'nutricionales' ? ['tests/Browser/fixtures/nutrition-proposal.php']
        : ['tests/Browser/fixtures/mixture-diluent.php', 'aprobacion', retry ? 'proposal-retry' : '', '', kind];
    const html = execFileSync('php', fixture, { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    page.setDefaultTimeout(10000);
    page.setDefaultNavigationTimeout(10000);
    const errors = [], requests = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => {
        if (route.request().method() === 'POST') {
            requests.push(new URLSearchParams(route.request().postData()));
            return route.fulfill({ status: 200, contentType: 'text/html', body: 'Propuesta recibida por la prueba' });
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html class="workflow-embedded"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body><script>window.testAlerts=[];window.messages=[];window.addEventListener('message',e=>window.messages.push(e.data));window.Swal={isVisible(){return false},fire(o){window.testAlerts.push(o);return Promise.resolve({isConfirmed:false})}};</script><main class="admin-page workflow-page"><div class="admin-content">${html}</div></main><script id="workflow-page-config" type="application/json">{"embedded":true,"completed":false}</script><script>${scripts}</script></body></html>` });
    });
    await page.goto('http://mixture.test/');
    return { page, requests, errors };
}

test('proposal dialog compares values, cancels safely, and submits only through the dedicated send button', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const kind of ['oncologicos', 'antibioticos', 'nutricionales']) {
            for (const width of [1320, 390]) {
                const { page, requests, errors } = await pageFor(browser, width, kind);
                const opener = page.getByRole('button', { name: 'AJUSTAR MEZCLA', exact: true });
                await opener.click();
                const dialog = page.getByRole('dialog', { name: 'Propuesta de ajuste de mezcla #1' });
                assert.equal(await dialog.isVisible(), true);
                assert.equal(requests.length, 0);
                assert.deepEqual(await page.evaluate(() => window.testAlerts), []);
                assert.deepEqual(await dialog.locator('thead th').allTextContents(), ['Campo', 'Solicitud original', 'Ajuste Prodifem']);
                const field = kind === 'nutricionales'
                    ? dialog.getByRole('spinbutton', { name: 'Volumen total (ml)', exact: true })
                    : dialog.getByRole('spinbutton', { name: 'Dosis - Medicamento 1', exact: true });
                const before = await field.inputValue();
                await field.fill('300');
                assert.equal(await field.locator('..').getAttribute('data-changed'), 'true');
                await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
                assert.equal(requests.length, 0);
                await opener.click();
                assert.equal(await field.inputValue(), before);
                await page.keyboard.press('Escape');
                assert.equal(await dialog.isVisible(), false);
                assert.deepEqual(await page.evaluate(() => window.messages), []);
                await opener.click();
                await field.fill('300');
                await dialog.getByRole('button', { name: 'Enviar propuesta al hospital', exact: true }).click();
                assert.equal(requests.length, 0);
                await dialog.getByRole('textbox', { name: 'Motivo del ajuste *', exact: true }).fill('Propuesta de ajuste para revisar en el hospital.');
                const box = await dialog.boundingBox();
                assert.ok(box.x >= 0 && box.y >= 0 && box.x + box.width <= width + 1 && box.y + box.height <= 901);
                assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
                assert.equal(await dialog.locator('.proposal-table-scroll').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
                assert.equal(await dialog.locator('[data-automatic-column-filters]').count(), 0);
                assert.equal(await dialog.locator('.proposal-icon-button svg').count() > 0, true);
                if (process.env.ADJUSTMENT_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.ADJUSTMENT_SCREENSHOTS, `proposal-editor-${kind}-${width}.png`) });
                await dialog.getByRole('button', { name: 'Enviar propuesta al hospital', exact: true }).click();
                await page.waitForURL('**/admin/**');
                assert.equal(requests.length, 1);
                assert.equal(requests[0].get('accion'), 'ajustar');
                assert.equal(requests[0].get('approval_popup'), '1');
                assert.match(requests[0].get('adjustment_description'), /Propuesta de ajuste/);
                if (kind === 'nutricionales') assert.equal(requests[0].get('volumen_total'), '300');
                else {
                    const mix = JSON.parse(requests[0].get('mezcla_json'));
                    assert.equal(mix.medicamentos[0].dosis, '300');
                    assert.equal(mix.medicamentos[0].medicamento_id, '10');
                    assert.equal(mix.volumen_dilucion, '250');
                }
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally { await browser.close(); }
});

test('medicine dependencies, multiple medicines and validation retry retain the proposal correctly', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const { page, requests, errors } = await pageFor(browser, 1320, 'oncologicos');
        await page.getByRole('button', { name: 'AJUSTAR MEZCLA', exact: true }).click();
        const dialog = page.getByRole('dialog');
        await dialog.getByRole('combobox', { name: 'Diluyente - Medicamento 1', exact: true }).selectOption('2');
        await dialog.getByRole('combobox', { name: 'Medicamento - Medicamento 1', exact: true }).selectOption('20');
        assert.equal(await dialog.getByRole('combobox', { name: 'Diluyente - Medicamento 1', exact: true }).inputValue(), '');
        await dialog.getByRole('combobox', { name: 'Diluyente - Medicamento 1', exact: true }).selectOption('1');
        await dialog.getByRole('button', { name: 'Agregar medicamento', exact: true }).click();
        await dialog.getByRole('combobox', { name: 'Medicamento - Medicamento 2', exact: true }).selectOption('10');
        await dialog.getByRole('spinbutton', { name: 'Dosis - Medicamento 2', exact: true }).fill('15');
        await dialog.getByRole('combobox', { name: 'Diluyente - Medicamento 2', exact: true }).selectOption('2');
        await dialog.getByRole('combobox', { name: 'Vía de administración - Medicamento 2', exact: true }).selectOption('1');
        await dialog.getByRole('textbox', { name: 'Motivo del ajuste *', exact: true }).fill('Propuesta con dos medicamentos.');
        await dialog.getByRole('button', { name: 'Enviar propuesta al hospital', exact: true }).click();
        assert.match(await dialog.getByRole('alert').innerText(), /mismo diluyente/);
        assert.equal(requests.length, 0);
        await dialog.getByRole('button', { name: 'Quitar medicamento 2', exact: true }).click();
        await dialog.getByRole('button', { name: 'Restaurar medicamento 2', exact: true }).click();
        assert.equal(await dialog.getByRole('spinbutton', { name: 'Dosis - Medicamento 2', exact: true }).inputValue(), '15');
        await dialog.getByRole('combobox', { name: 'Diluyente - Medicamento 2', exact: true }).selectOption('1');
        await dialog.getByRole('button', { name: 'Enviar propuesta al hospital', exact: true }).click();
        await page.waitForURL('**/admin/**');
        assert.equal(JSON.parse(requests[0].get('mezcla_json')).medicamentos.length, 2);
        assert.deepEqual(errors, []);
        await page.close();

        const retry = await pageFor(browser, 1320, 'oncologicos', true);
        assert.equal(await retry.page.getByRole('dialog').isVisible(), true);
        assert.equal(await retry.page.getByRole('spinbutton', { name: 'Dosis - Medicamento 1', exact: true }).inputValue(), '175');
        assert.equal(await retry.page.getByRole('textbox', { name: 'Motivo del ajuste *', exact: true }).inputValue(), 'Motivo conservado');
        await retry.page.close();
    } finally { await browser.close(); }
});
