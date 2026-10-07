const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync, mkdirSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/conciliation-period-create.js')], bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const render = (action = 'page') => execFileSync('php', ['tests/Browser/fixtures/conciliation-period.php', action], { cwd: root, encoding: 'utf8' });

test('create period filters remittances, retains selection, selects all available and saves with recovery', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport: { width: 1500, height: 1100 } });
        const errors = [], payloads = [];
        let failSave = true;
        const candidates = JSON.parse(render('candidates'));
        candidates.rows.find(row => row.key === 'oncologicos-4').period_id = 77;
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => {
            const request = route.request(), url = new URL(request.url());
            if (url.hostname !== 'conciliation.test') return route.abort();
            if (request.resourceType() === 'document') return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="fixture"><style>${styles}</style>${render()}<script>${script}</script>` });
            if (url.pathname.endsWith('/remisiones')) return route.fulfill({ json: candidates });
            if (url.pathname.endsWith('/conciliacion-periodos') && request.method() === 'POST') {
                payloads.push(request.postDataJSON());
                return failSave ? route.fulfill({ status: 409, json: { message: 'Una remisión cambió. Revisa la selección.' } })
                    : route.fulfill({ status: 201, json: { period_id: 10, redirect: 'https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&modalidad=periodo&creado=10' } });
            }
            return route.abort();
        });
        await page.goto('https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&modalidad=periodo');
        await page.locator('[data-period-create-open]').click();
        const dialog = page.locator('[data-period-create-dialog]');
        await dialog.locator('[data-create-row]').first().waitFor();
        assert.equal(await dialog.locator('[data-create-row]').count(), 4);
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-4"]').isDisabled(), true);
        assert.equal(await dialog.locator('[data-create-submit]').isDisabled(), true);
        const search = dialog.getByPlaceholder('Buscar por remisión o paciente');
        await search.fill('REM-1');
        await dialog.getByRole('button', { name: 'Aplicar filtros' }).click();
        assert.equal(await dialog.locator('[data-create-row]').count(), 1);
        await dialog.locator('[data-create-all]').check();
        await search.fill('REM-2');
        await dialog.getByRole('button', { name: 'Aplicar filtros' }).click();
        await dialog.locator('[data-create-all]').check();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        assert.match(await dialog.locator('[data-create-total]').innerText(), /700.25/);
        assert.equal(await dialog.locator('[data-create-range]').innerText(), '09/09/2026 – 06/10/2026');
        await dialog.getByRole('button', { name: 'Limpiar', exact: true }).click();
        assert.equal(await dialog.locator('[data-create-all]').evaluate(el => el.indeterminate), true);
        await dialog.locator('[data-create-all]').check();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '3 remisiones seleccionadas');
        assert.match(await dialog.locator('[data-create-total]').innerText(), /1,200.25/);
        await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
        assert.equal(payloads.length, 0);
        await page.locator('[data-period-create-open]').click();
        await dialog.locator('[data-create-row]').first().waitFor();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '0 remisiones seleccionadas');
        await dialog.locator('[data-create-choice="oncologicos-1"]').check();
        await dialog.locator('[data-create-choice="antibioticos-2"]').check();
        await dialog.locator('input[name="from"]').fill('2026-10-01');
        await dialog.locator('input[name="to"]').fill('2026-10-06');
        await dialog.getByRole('button', { name: 'Aplicar filtros' }).click();
        assert.equal(await dialog.locator('[data-create-row]').count(), 1);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        await dialog.getByRole('button', { name: 'Limpiar', exact: true }).click();
        mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-create-period.png') });
        const box = await dialog.boundingBox();
        assert.ok(box.x >= 0 && box.x + box.width <= 1500 && box.height <= 1100);
        await dialog.locator('[data-create-submit]').click();
        await dialog.locator('[data-create-error]').waitFor({ state: 'visible' });
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        assert.equal(payloads.length, 1);
        assert.deepEqual(Object.keys(payloads[0].selection).sort(), ['antibioticos-2', 'oncologicos-1']);
        assert.equal(payloads[0].hospital_id, 1);
        assert.equal(payloads[0].institucion_id, 1);
        assert.equal(payloads[0].selection['oncologicos-1'], candidates.rows.find(row => row.key === 'oncologicos-1').version);
        await page.setViewportSize({ width: 390, height: 844 });
        const mobileBox = await dialog.boundingBox();
        assert.ok(mobileBox.x >= 0 && mobileBox.x + mobileBox.width <= 390 && mobileBox.height <= 844);
        assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth), true);
        failSave = false;
        await dialog.locator('[data-create-submit]').click();
        await page.waitForURL('**/*creado=10');
        assert.equal(payloads.length, 2);
        assert.equal(payloads[0].creation_key, payloads[1].creation_key);
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});

test('the AI launcher has identical geometry in both modes', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const period = render();
        const remission = execFileSync('php', ['tests/Browser/fixtures/conciliation-inbox.php'], { cwd: root, encoding: 'utf8' });
        for (const width of [1440, 1366, 820, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            await page.route('**/*', route => route.abort());
            const boxes = [];
            for (const html of [period, remission]) {
                await page.setContent(`<meta charset="utf-8"><style>${styles}</style>${html}`);
                const launcher = page.locator('[data-agent-open]');
                boxes.push(await launcher.boundingBox());
                assert.equal(await launcher.locator('svg').count(), 1);
            }
            assert.deepEqual(boxes[0], boxes[1], `Different AI launcher geometry at ${width}px`);
            await page.close();
        }
    } finally { await browser.close(); }
});
