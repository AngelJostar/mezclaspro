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
        Object.assign(candidates.rows.find(row => row.key === 'oncologicos-4'), {
            period_id: 77, institution_id: 2, institution: 'Otra institución', hospital_id: 2, hospital: 'Otro hospital',
        });
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
        const visibleRows = dialog.locator('[data-create-row]:visible');
        const toggleAll = dialog.locator('thead [data-create-toggle-all]');
        const panel = dialog.locator('#conciliation-period-create-column-filter-panel');
        const filterColumn = async (column, values) => {
            await dialog.locator(`.cp-create-filter[data-column="${column}"]`).click();
            await panel.locator('[data-filter-all]').check();
            await panel.locator('[data-filter-all]').uncheck();
            for (const value of values) await panel.getByRole('checkbox', { name: value, exact: true }).check();
            await panel.locator('[data-filter-accept]').click();
        };
        await dialog.locator('[data-create-row]').first().waitFor();
        assert.equal(await dialog.locator('[data-create-row]').count(), 4);
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-4"]').isDisabled(), true);
        assert.equal(await dialog.locator('[data-create-submit]').isDisabled(), true);
        for (const field of ['institution', 'from', 'to']) assert.equal(await dialog.locator(`[name="${field}"]`).getAttribute('required'), '');
        assert.equal(await dialog.locator('[name="hospital"]').getAttribute('required'), null);
        assert.equal(await dialog.locator('[data-create-filters] select').count(), 2);
        assert.equal(await dialog.locator('[data-create-filters] input[type="date"]').count(), 2);
        assert.equal(await dialog.getByRole('button', { name: 'Aplicar filtros' }).count(), 0);
        assert.equal(await dialog.getByPlaceholder('Buscar por remisión o paciente').count(), 0);
        assert.equal(await dialog.locator('.cp-create-filter').count(), 7);
        for (const trigger of await dialog.locator('.cp-create-filter').all()) {
            await trigger.click();
            assert.ok(await panel.locator('[data-filter-options] label').count() > 0);
            await panel.locator('[data-filter-cancel]').click();
        }
        await dialog.getByRole('button', { name: 'Filtrar Paciente', exact: true }).click();
        await page.keyboard.press('Escape');
        assert.equal(await panel.isVisible(), false);
        assert.equal(await dialog.isVisible(), true);
        await filterColumn(0, ['REM-1']);
        assert.equal(await visibleRows.count(), 1);
        assert.equal(await toggleAll.isChecked(), false);
        await toggleAll.click();
        assert.equal(await toggleAll.isChecked(), true);
        await filterColumn(0, ['REM-2']);
        assert.equal(await toggleAll.isChecked(), false);
        await toggleAll.click();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        await toggleAll.click();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '1 remisión seleccionada');
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-1"]').isChecked(), true);
        assert.equal(await dialog.locator('[data-create-choice="antibioticos-2"]').isChecked(), false);
        assert.equal(await toggleAll.isChecked(), false);
        await toggleAll.click();
        assert.match(await dialog.locator('[data-create-total]').innerText(), /700.25/);
        await dialog.locator('[data-create-submit]').click();
        assert.equal(payloads.length, 0);
        assert.equal(await dialog.locator('[name="institution"]').evaluate(el => el.validity.valueMissing), true);
        await dialog.locator('[name="institution"]').selectOption('1');
        await dialog.locator('[data-create-submit]').click();
        assert.equal(payloads.length, 0);
        assert.equal(await dialog.locator('[name="from"]').evaluate(el => el.validity.valueMissing), true);
        await dialog.locator('[name="from"]').fill('2026-09-09');
        await dialog.locator('[name="from"]').dispatchEvent('change');
        await dialog.locator('[data-create-submit]').click();
        assert.equal(payloads.length, 0);
        assert.equal(await dialog.locator('[name="to"]').evaluate(el => el.validity.valueMissing), true);
        await dialog.locator('[name="to"]').fill('2026-09-01');
        await dialog.locator('[name="to"]').dispatchEvent('change');
        await dialog.locator('[data-create-submit]').click();
        assert.equal(payloads.length, 0);
        assert.equal(await dialog.locator('[name="to"]').evaluate(el => el.validity.customError), true);
        await dialog.locator('[name="to"]').fill('2026-10-06');
        await dialog.locator('[name="to"]').dispatchEvent('change');
        assert.equal(await dialog.locator('[data-create-range]').innerText(), '09/09/2026 – 06/10/2026');
        await dialog.getByRole('button', { name: 'Limpiar filtros', exact: true }).click();
        assert.equal(await visibleRows.count(), 4);
        assert.equal(await dialog.locator('.cp-create-filter.bg-blue-100').count(), 0);
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-1"]').isChecked(), true);
        assert.equal(await dialog.locator('[data-create-choice="antibioticos-2"]').isChecked(), true);
        assert.equal(await toggleAll.evaluate(el => el.indeterminate), true);
        await toggleAll.check();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '3 remisiones seleccionadas');
        assert.equal(await toggleAll.isChecked(), true);
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-4"]').isChecked(), false);
        assert.match(await dialog.locator('[data-create-total]').innerText(), /1,200.25/);
        await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
        assert.equal(payloads.length, 0);
        await page.locator('[data-period-create-open]').click();
        await dialog.locator('[data-create-row]').first().waitFor();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '0 remisiones seleccionadas');
        await dialog.locator('[data-create-choice="oncologicos-1"]').check();
        await dialog.locator('[data-create-choice="antibioticos-2"]').check();
        await dialog.locator('select[name="institution"]').selectOption('1');
        assert.equal(await visibleRows.count(), 3);
        assert.equal(await dialog.locator('select[name="hospital"] option').count(), 2);
        await dialog.locator('select[name="hospital"]').selectOption('1');
        await dialog.locator('input[name="from"]').fill('2026-10-01');
        await dialog.locator('input[name="from"]').dispatchEvent('change');
        await dialog.locator('input[name="to"]').fill('2026-10-06');
        await dialog.locator('input[name="to"]').dispatchEvent('change');
        assert.equal(await visibleRows.count(), 1);
        await filterColumn(6, ['$500.00 MXN']);
        assert.equal(await visibleRows.count(), 0);
        assert.equal(await dialog.locator('[data-create-empty]').isVisible(), true);
        assert.equal(await toggleAll.isDisabled(), true);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        await dialog.getByRole('button', { name: 'Limpiar filtros', exact: true }).click();
        assert.equal(await visibleRows.count(), 4);
        for (const field of ['institution', 'hospital', 'from', 'to']) assert.equal(await dialog.locator(`[name="${field}"]`).inputValue(), '');
        await filterColumn(4, ['06/10/2026']);
        assert.equal(await visibleRows.count(), 1);
        await dialog.getByRole('button', { name: 'Limpiar filtros', exact: true }).click();
        await dialog.locator('[name="institution"]').selectOption('1');
        await dialog.locator('[name="from"]').fill('2026-10-01');
        await dialog.locator('[name="to"]').fill('2026-10-31');
        await dialog.locator('[name="to"]').dispatchEvent('change');
        await dialog.locator('[data-create-submit]').click();
        assert.equal(payloads.length, 0);
        assert.match(await dialog.locator('[data-create-error]').innerText(), /fuera del rango/);
        await dialog.locator('[name="from"]').fill('2026-09-01');
        await dialog.locator('[name="from"]').dispatchEvent('change');
        const summaryBox = await dialog.locator('.cp-create-summary').boundingBox();
        assert.ok(summaryBox.height <= 42 && summaryBox.width < 800);
        mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-create-period.png') });
        await dialog.getByRole('button', { name: 'Filtrar Institución', exact: true }).click();
        await panel.locator('[data-filter-search]').fill('otra');
        assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
        await panel.locator('[data-filter-search]').fill('');
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-create-period-filter.png') });
        await panel.locator('[data-filter-cancel]').click();
        const box = await dialog.boundingBox();
        assert.ok(box.x >= 0 && box.x + box.width <= 1500 && box.height <= 1100);
        await dialog.locator('[data-create-submit]').click();
        await dialog.locator('[data-create-error]').waitFor({ state: 'visible' });
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '2 remisiones seleccionadas');
        assert.equal(payloads.length, 1);
        assert.deepEqual(Object.keys(payloads[0].selection).sort(), ['antibioticos-2', 'oncologicos-1']);
        assert.equal(payloads[0].hospital_id, 1);
        assert.equal(payloads[0].institucion_id, 1);
        assert.equal(payloads[0].desde, '2026-09-01');
        assert.equal(payloads[0].hasta, '2026-10-31');
        assert.equal(payloads[0].selection['oncologicos-1'], candidates.rows.find(row => row.key === 'oncologicos-1').version);
        await page.setViewportSize({ width: 390, height: 844 });
        const mobileBox = await dialog.boundingBox();
        assert.ok(mobileBox.x >= 0 && mobileBox.x + mobileBox.width <= 390 && mobileBox.height <= 844);
        assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth), true);
        await dialog.getByRole('button', { name: 'Filtrar Institución', exact: true }).click();
        const filterBox = await panel.boundingBox();
        assert.ok(filterBox.x >= 0 && filterBox.x + filterBox.width <= 390 && filterBox.y >= 0 && filterBox.y + filterBox.height <= 844);
        await panel.locator('[data-filter-cancel]').click();
        failSave = false;
        await dialog.locator('[data-create-submit]').click();
        await page.waitForURL('**/*creado=10');
        assert.equal(payloads.length, 2);
        assert.equal(payloads[0].creation_key, payloads[1].creation_key);
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});

test('bulk selection checks all rows and creation groups selected hospitals of the same institution', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const candidates = JSON.parse(render('candidates'));
        Object.assign(candidates.rows.find(row => row.key === 'oncologicos-4'), { hospital_id: 2, hospital: 'Segundo hospital de la institución' });
        candidates.rows.push({ ...candidates.rows[0], key: 'oncologicos-900', id: 900, remision: 'REM-AJENA', institution_id: 2,
            institution: 'Otra institución', hospital_id: 3, hospital: 'Hospital de otra institución' });
        for (let id = 901; id <= 931; id++) candidates.rows.push({ ...candidates.rows.at(-1), key: `oncologicos-${id}`, id, remision: `REM-${id}` });
        const errors = [], payloads = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => {
            const request = route.request(), url = new URL(request.url());
            if (url.hostname !== 'conciliation.test') return route.abort();
            if (request.resourceType() === 'document') return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="fixture"><style>${styles}</style>${render()}<script>${script}</script>` });
            if (url.pathname.endsWith('/remisiones')) return route.fulfill({ json: candidates });
            if (url.pathname.endsWith('/conciliacion-periodos') && request.method() === 'POST') {
                payloads.push(request.postDataJSON());
                return route.fulfill({ status: 201, json: { period_id: 10, period_ids: [10, 11], period_count: 2,
                    redirect: 'https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&modalidad=periodo&institucion_id=1&creado=10' } });
            }
            return route.abort();
        });
        await page.goto('https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&modalidad=periodo');
        await page.locator('[data-period-create-open]').click();
        const dialog = page.locator('[data-period-create-dialog]');
        await dialog.locator('[data-create-row]').first().waitFor();
        await dialog.locator('[data-create-toggle-all]').click();
        assert.equal(await dialog.locator('[data-create-choice]:checked').count(), 36);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '36 remisiones seleccionadas');
        assert.equal(await dialog.locator('[data-create-error]').isVisible(), false);
        assert.equal(await dialog.locator('.cp-create-scroll').evaluate(el => el.scrollHeight > el.clientHeight), true);
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-931"]').isChecked(), true);
        assert.equal(await dialog.locator('[data-create-toggle-all]').isChecked(), true);
        await dialog.locator('[data-create-toggle-all]').click();
        assert.equal(await dialog.locator('[data-create-choice]:checked').count(), 0);
        assert.equal(await dialog.locator('[data-create-toggle-all]').isChecked(), false);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '0 remisiones seleccionadas');
        await dialog.locator('[data-create-toggle-all]').check();
        assert.equal(await dialog.locator('[data-create-choice]:checked').count(), 36);
        await dialog.locator('[name="institution"]').selectOption('1');
        assert.equal(await dialog.locator('[name="hospital"]').inputValue(), '');
        assert.equal(await dialog.locator('[data-create-row]:visible').count(), 4);
        await dialog.locator('[name="from"]').fill('2026-09-01');
        await dialog.locator('[name="to"]').fill('2026-10-31');
        await dialog.locator('[name="to"]').dispatchEvent('change');
        await dialog.locator('[data-create-submit]').click();
        assert.match(await dialog.locator('[data-create-error]').innerText(), /institución indicada/);
        assert.equal(payloads.length, 0);
        await dialog.locator('[name="institution"]').selectOption('');
        await dialog.locator('[data-create-toggle-all]').uncheck();
        await dialog.locator('[name="institution"]').selectOption('1');
        await dialog.locator('[data-create-toggle-all]').click();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '4 remisiones seleccionadas');
        assert.equal(await dialog.locator('[data-create-toggle-all]').isChecked(), true);
        assert.equal(await dialog.locator('[data-create-period-count]').innerText(), '2 periodos');
        assert.match(await dialog.locator('[data-create-total]').innerText(), /1,400.50/);
        assert.equal(await dialog.locator('[data-create-submit]').isEnabled(), true);
        await dialog.locator('[name="hospital"]').selectOption('2');
        assert.equal(await dialog.locator('[data-create-row]:visible').count(), 1);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '4 remisiones seleccionadas');
        await dialog.getByRole('button', { name: 'Limpiar filtros', exact: true }).click();
        await dialog.locator('[data-create-choice="oncologicos-900"]').check();
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '5 remisiones seleccionadas');
        await dialog.locator('[data-create-choice="oncologicos-900"]').uncheck();
        assert.equal(await dialog.locator('[data-create-choice="oncologicos-900"]').isChecked(), false);
        assert.equal(await dialog.locator('[data-create-selected]').innerText(), '4 remisiones seleccionadas');
        await dialog.locator('[name="institution"]').selectOption('1');
        await dialog.locator('[name="from"]').fill('2026-09-01');
        await dialog.locator('[name="to"]').fill('2026-10-31');
        await dialog.locator('[name="to"]').dispatchEvent('change');
        mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-create-all-hospitals.png') });
        await dialog.locator('[data-create-submit]').click();
        await page.waitForURL('**/*creado=10');
        assert.equal(payloads.length, 1);
        assert.equal(payloads[0].institucion_id, 1);
        assert.equal(payloads[0].hospital_id, null);
        assert.equal(payloads[0].desde, '2026-09-01');
        assert.equal(payloads[0].hasta, '2026-10-31');
        assert.deepEqual(Object.keys(payloads[0].selection).sort(), ['antibioticos-2', 'nutricionales-11', 'oncologicos-1', 'oncologicos-4']);
        assert.equal(new URL(page.url()).searchParams.has('hospital_id'), false);
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
