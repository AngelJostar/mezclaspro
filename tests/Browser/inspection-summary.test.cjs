const { test } = require('node:test');
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
const icons = buildSync({ entryPoints: [path.join(root, 'resources/js/mixture-workflow.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const livewire = readFileSync(path.join(root, 'vendor/livewire/livewire/dist/livewire.js'), 'utf8');
const switchDefaults = {
    esta_rotulado: true, medicamento: true, volumen_medicamento: true, sello_seguridad: true,
    esta_roto: false, contenido_homogeneo: true, presenta_turbidez: false, aprueba_contenedor: true,
    numero_lote: true, dosis_volumen_total: true, rubrica_preparador: true, presenta_fugas: false,
    coloracion_apropiada: true, presenta_particulas: false, aprueba_contenido: true,
};

async function assertSwitch(page, field, checked) {
    const control = page.locator(`input[wire\\:model="${field}"]`);
    assert.equal(await control.isChecked(), checked, field);
    const appearance = await control.evaluate(el => {
        const track = el.nextElementSibling;
        const label = el.closest('label').lastElementChild;
        return {
            background: getComputedStyle(track).backgroundColor,
            label: [...track.children].filter(child => getComputedStyle(child).visibility === 'visible').map(child => child.textContent).join(''),
            width: track.getBoundingClientRect().width, height: track.getBoundingClientRect().height,
            leftOfQuestion: track.getBoundingClientRect().right < label.getBoundingClientRect().left,
        };
    });
    assert.deepEqual(appearance, {
        background: checked ? 'rgb(21, 128, 61)' : 'rgb(220, 38, 38)',
        label: checked ? 'Sí' : 'No', width: 52, height: 24, leftOfQuestion: true,
    }, field);
    assert.equal(await page.evaluate(field => Boolean(window.Livewire.first().$get(field)), field), checked);
    return control;
}

test('inspection summary and editable checklist fit desktop and mobile without hiding actions', async () => {
    const states = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/inspection-summary.php'], { cwd: root, encoding: 'utf8' }));
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const [state, html] of Object.entries(states)) {
            for (const width of [1366, 1024, 768, 390, 320]) {
                const page = await browser.newPage({ viewport: { width, height: 800 } });
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                await page.route('**/*', route => route.abort());
                await page.setContent(`<!doctype html><html lang="es"><head><meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1"><style>${styles}</style></head>
                    <body><main class="admin-content">${html}</main><script>${icons}</script>
                    <script>window.livewireScriptConfig = {uri: 'http://inspection.test/livewire/update', csrf: 'test'};</script>
                    <script>${livewire}</script><script>window.Livewire.start();</script></body></html>`);
                await page.waitForFunction(() => document.activeElement?.getAttribute('aria-label') === 'Cerrar inspección', null, { timeout: 5000 })
                    .catch(error => { throw new Error(`${error.message}; browser errors: ${errors.join('; ')}`); });
                const dialog = page.getByRole('dialog');
                assert.equal(await dialog.count(), 1);
                assert.equal(await dialog.getByRole('heading', { name: 'Datos del paciente' }).count(), 1);
                assert.equal(await dialog.locator('.inspection-context-band input, .inspection-context-band select').count(), 0);
                assert.equal(await dialog.locator('svg[data-inspection-icon]').count(), 3);
                assert.equal(await dialog.getByRole('button', { name: 'Cerrar inspección' }).count(), 1);
                await page.keyboard.press('Shift+Tab');
                assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Aprobada');
                await page.keyboard.press('Tab');
                assert.equal(await page.evaluate(() => document.activeElement.getAttribute('aria-label')), 'Cerrar inspección');
                await dialog.locator('.inspection-review-body').evaluate(el => { el.scrollTop = 0; });
                assert.equal(await dialog.getByRole('button', { name: 'Marcar default' }).count(), 0);
                assert.equal(await page.getByRole('switch').count(), 15);
                assert.equal(await page.locator('.inspection-summary-table tbody tr').count(), 2);
                assert.equal(await page.locator('.inspection-summary-table [rowspan="2"]').innerText(), '250.00 mL');
                const bounds = await dialog.boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
                assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 800);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
                for (const selector of ['.inspection-patient-grid dd', '.inspection-care-grid dd', 'label', 'th', 'td', 'button']) {
                    const overflow = await dialog.locator(selector).evaluateAll(els => els.some(el => el.scrollWidth > el.clientWidth + 1));
                    assert.equal(overflow, false, `${selector} overflow at ${width} (${state})`);
                }
                const actions = dialog.locator('footer').getByRole('button');
                assert.deepEqual((await actions.allTextContents()).map(text => text.trim()), ['Rechazada', 'Cancelar', 'Aprobada']);
                assert.equal(await actions.last().evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
                for (const button of await actions.all()) {
                    const box = await button.boundingBox();
                    assert.ok(box.x >= 0 && box.x + box.width <= width && box.y >= 0 && box.y + box.height <= 800);
                }
                if (process.env.INSPECTION_SCREENSHOTS && [1366, 390].includes(width)) {
                    await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `inspection-summary-${state}-${width}.png`) });
                }
                const tableScroll = dialog.locator('.inspection-summary-table').locator('..');
                await tableScroll.evaluate(el => { el.scrollLeft = el.scrollWidth; });
                assert.equal(await tableScroll.evaluate(el => {
                    const right = el.querySelector('th:last-child').getBoundingClientRect().right;
                    return right <= el.getBoundingClientRect().right + 1;
                }), true);
                const container = page.getByLabel('Tipo de contenedor', { exact: true });
                await container.selectOption('Bolsa');
                assert.equal(await container.inputValue(), 'Bolsa');
                for (const [field, checked] of Object.entries(switchDefaults)) {
                    const control = await assertSwitch(page, field, checked);
                    if (state === 'summary' && [1366, 390].includes(width)) {
                        await control.click();
                        await assertSwitch(page, field, !checked);
                        await control.press('Space');
                        await assertSwitch(page, field, checked);
                    }
                }
                if (process.env.INSPECTION_SCREENSHOTS && state === 'summary' && [1366, 390].includes(width)) {
                    await page.locator('.inspection-check-item').first().evaluate(el => el.scrollIntoView({ block: 'start' }));
                    await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `inspection-switches-${width}.png`) });
                }
                await page.getByRole('switch').first().uncheck();
                await page.getByLabel('Dosis / Volumen (mL)', { exact: true }).fill('250.00');
                await page.getByLabel('Peso de la mezcla (g)', { exact: true }).fill('260.00');
                await page.getByLabel('Observaciones', { exact: true }).fill('Observacion de prueba');
                assert.equal(await page.getByLabel('Inspeccionó', { exact: true }).getAttribute('readonly'), '');
                assert.equal(await page.getByLabel('Inspeccionó', { exact: true }).inputValue(), 'gcortes');
                await page.getByLabel('Aprobó', { exact: true }).selectOption('gcortes');
                const body = dialog.locator('.inspection-review-body');
                await body.evaluate(el => { el.scrollTop = el.scrollHeight; });
                assert.equal(await body.evaluate(el => el.scrollWidth > el.clientWidth + 1), false);
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
