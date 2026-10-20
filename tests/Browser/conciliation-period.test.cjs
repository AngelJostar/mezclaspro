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
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/conciliation-period.js')], bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const render = (action, data = {}, saved = {}, decisions = []) => execFileSync('php', ['tests/Browser/fixtures/conciliation-period.php', action, JSON.stringify(data), JSON.stringify(saved), JSON.stringify(decisions)], { cwd: root, encoding: 'utf8' });

test('period view filters, reviews drafts, cancels, accepts totals and sends a row', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        let saves = 0, sends = 0, authorizations = 0, savedChoices = {}, failSave = false, failAuthorization = true;
        const decisions = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', async route => {
            const request = route.request(), url = new URL(request.url());
            if (url.hostname !== 'conciliation.test') return route.abort();
            if (request.resourceType() === 'document') return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="fixture"><style>${styles}</style>${render('page')}<script>${script}</script>` });
            if (url.pathname.endsWith('/detalle')) return route.fulfill({ json: JSON.parse(render('detail', Object.fromEntries(url.searchParams), savedChoices, decisions)) });
            if (url.pathname.endsWith('/autorizar-cambios')) {
                if (failAuthorization) return route.fulfill({ status: 409, json: { message: 'Las remisiones cambiaron. Vuelve a abrir el detalle.' } });
                const result = JSON.parse(render('authorizeChanges', request.postDataJSON(), savedChoices, decisions));
                const group = result.group;
                decisions.push({ group_key: group.key, version: group.review_version, action: 'authorize', institution_id: group.institution_id,
                    hospital_id: group.hospital_id, period_from: group.from, period_to: group.to });
                authorizations++;
                return route.fulfill({ json: result });
            }
            if (url.pathname.endsWith('/aceptar')) {
                if (failSave) return route.fulfill({ status: 409, json: { message: 'Las remisiones cambiaron. Vuelve a abrir el detalle.' } });
                const payload = request.postDataJSON();
                const result = JSON.parse(render('accept', payload, savedChoices));
                // Preserve only changed choices, as the real endpoint avoids unnecessary writes.
                Object.entries(payload.choices).filter(([, yes]) => !yes).forEach(([key, yes]) => { savedChoices[key] = yes; });
                saves++;
                return route.fulfill({ json: result });
            }
            if (url.pathname.endsWith('/enviar')) {
                assert.equal(request.postDataJSON().hospital_id, 1);
                sends++;
                return route.fulfill({ json: { folio: 'CON-000001' } });
            }
            return route.abort();
        });
        await page.goto('https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&modalidad=periodo');
        const rows = page.locator('[data-period-row]');
        assert.equal(await rows.count(), 2);
        assert.equal(await page.getByRole('heading', { name: 'Solicitudes de conciliación', exact: true }).count(), 0);
        assert.equal(await page.locator('[data-conciliation-count="todas"]').innerText(), '2');
        assert.equal(await page.locator('[data-conciliation-count="pendientes"]').innerText(), '2');
        for (const tab of ['todas', 'recibidas', 'enviadas', 'pendientes']) {
            const href = new URL(await page.locator(`[data-conciliation-count="${tab}"]`).locator('..').getAttribute('href'));
            assert.equal(href.searchParams.get('modalidad'), 'periodo');
            assert.equal(href.searchParams.get('bandeja'), tab);
            assert.equal(href.searchParams.get('desde'), '2026-09-01');
        }
        assert.equal(await page.getByRole('link', { name: 'Por periodo', exact: true }).getAttribute('aria-current'), 'page');
        const row = rows.first();
        assert.match(await row.locator('[data-period-total]').innerText(), /1,200.25/);
        assert.equal(await row.locator('[data-period-status]').innerText(), 'Pendiente');
        assert.equal(await row.locator('[data-period-authorize-open]').isDisabled(), true);
        const observationsButton = row.locator('.ht-view[data-period-observations]');
        assert.equal(await observationsButton.isDisabled(), true);
        assert.equal(await observationsButton.evaluate(button => getComputedStyle(button).backgroundColor), 'rgb(229, 231, 235)');
        const observations = page.locator('[data-period-observations-dialog]');
        assert.equal(await observations.isVisible(), false);
        for (const trigger of await page.locator('.cp-filter').all()) {
            await trigger.click();
            assert.equal(await page.locator('#conciliation-period-column-filter-panel [data-filter-options] label').count() > 0, true);
            await page.locator('#conciliation-period-column-filter-panel [data-filter-cancel]').click();
        }
        await page.getByRole('button', { name: 'Filtrar Periodo de la conciliación', exact: true }).click();
        const panel = page.locator('#conciliation-period-column-filter-panel');
        await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
        await panel.locator('[data-filter-options] input').first().check();
        await panel.locator('[data-filter-accept]').click();
        assert.equal(await page.locator('[data-period-row]:visible').count(), 1);
        await page.getByRole('button', { name: 'Filtrar Periodo de la conciliación', exact: true }).click();
        await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
        await panel.locator('[data-filter-accept]').click();
        await row.locator('[data-period-open]').click();
        const dialog = page.locator('[data-period-dialog]');
        await dialog.locator('[data-period-item]').first().waitFor();
        await dialog.locator('[data-period-item="oncologicos-1"] [data-period-choice="0"]').click();
        assert.match(await dialog.locator('[data-period-summary-new]').innerText(), /700.25/);
        assert.match(await dialog.locator('[data-period-excluded]').innerText(), /500.00/);
        await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
        assert.equal(saves, 0);
        assert.match(await row.locator('[data-period-new]').innerText(), /1,200.25/);
        await row.locator('[data-period-open]').click();
        await dialog.locator('[data-period-item]').first().waitFor();
        assert.equal(await dialog.locator('[data-period-item="oncologicos-1"] [data-period-choice="1"]').getAttribute('aria-pressed'), 'true');
        await dialog.locator('[data-period-item="oncologicos-1"] [data-period-choice="0"]').click();
        mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-period-detail.png') });
        await dialog.locator('[data-period-accept]').click();
        await dialog.waitFor({ state: 'hidden' });
        assert.equal(saves, 1);
        assert.match(await row.locator('[data-period-new]').innerText(), /700.25/);
        assert.equal(await row.locator('[data-period-status]').innerText(), 'Con cambios');
        assert.equal(await observationsButton.isEnabled(), true);
        assert.equal(await row.locator('[data-period-authorize-open]').isEnabled(), true);
        await observationsButton.click();
        await observations.getByText('Remisión marcada como no conciliable.').waitFor();
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-period-observations.png') });
        await observations.getByRole('button', { name: 'Cerrar', exact: true }).click();
        assert.equal(authorizations, 0);
        await row.locator('[data-period-authorize-open]').click();
        await observations.locator('[data-period-authorize]').click();
        await observations.locator('[data-period-observations-error]').waitFor({ state: 'visible' });
        assert.equal(await observations.locator('[data-period-authorize]').isEnabled(), true);
        failAuthorization = false;
        await observations.locator('[data-period-authorize]').click();
        await observations.locator('.cp-authorized').waitFor();
        assert.equal(authorizations, 1);
        assert.equal(sends, 0);
        assert.equal(await row.locator('[data-period-status]').innerText(), 'Aceptada');
        assert.equal(await observationsButton.isEnabled(), true);
        assert.equal(await row.locator('[data-period-authorize-open]').isDisabled(), true);
        assert.match(await row.locator('[data-period-new]').innerText(), /700.25/);
        await observations.getByRole('button', { name: 'Cerrar', exact: true }).click();
        failSave = true;
        await row.locator('[data-period-open]').click();
        await dialog.locator('[data-period-item]').first().waitFor();
        await dialog.locator('[data-period-accept]').click();
        await dialog.locator('[data-period-error]').waitFor({ state: 'visible' });
        assert.match(await dialog.locator('[data-period-error]').innerText(), /cambiaron/);
        await page.keyboard.press('Escape');
        await row.locator('[data-period-send]').click();
        await page.getByRole('status').filter({ hasText: 'enviada a' }).waitFor();
        assert.equal(sends, 1);
        assert.equal(await page.locator('[data-conciliation-count="enviadas"]').innerText(), '1');
        assert.equal(await page.locator('[data-conciliation-count="pendientes"]').innerText(), '1');
        assert.equal(await row.locator('[data-period-send]').isDisabled(), true);
        assert.equal(await row.locator('[data-period-folio]').innerText(), 'CON-000001');
        await page.screenshot({ path: path.join(root, 'storage/app/testing/conciliation-period.png') });
        await page.setViewportSize({ width: 390, height: 844 });
        await row.locator('[data-period-open]').click();
        await dialog.locator('[data-period-item]').first().waitFor();
        const box = await dialog.boundingBox();
        assert.ok(box.x >= 0 && box.x + box.width <= 390 && box.height <= 844);
        await dialog.getByRole('button', { name: 'Cerrar detalle' }).click();
        // Accepting a period without changes keeps its observations unavailable.
        failSave = false;
        const unchanged = rows.nth(1);
        await unchanged.locator('[data-period-open]').click();
        await dialog.locator('[data-period-item]').first().waitFor();
        await dialog.locator('[data-period-accept]').click();
        await dialog.waitFor({ state: 'hidden' });
        assert.equal(await unchanged.locator('[data-period-status]').innerText(), 'Aceptada');
        const unchangedObservations = unchanged.locator('.ht-view[data-period-observations]');
        assert.equal(await unchangedObservations.isDisabled(), true);
        assert.equal(await unchangedObservations.evaluate(button => getComputedStyle(button).backgroundColor), 'rgb(229, 231, 235)');
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});
