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
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/client-conciliation.js')], bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const fixture = (action, payload = {}) => execFileSync('php', ['tests/Browser/fixtures/conciliation-period.php', action, JSON.stringify(payload)], { cwd: root, encoding: 'utf8' });
const html = fixture('clientPage'), form = fixture('clientForm'), detail = fixture('clientDetail');

test('clients can inspect the shared period detail, retry errors and cancel without submitting changes', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            const errors = [], methods = [];
            let loads = 0;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const request = route.request(), url = new URL(request.url());
                if (url.hostname !== 'conciliation.test') return route.abort();
                if (url.pathname.endsWith('/detalle')) {
                    methods.push(request.method());
                    return route.fulfill({ status: ++loads === 1 ? 503 : 200, contentType: 'application/json', body: loads === 1 ? '{}' : detail });
                }
                return route.fulfill({ contentType: 'text/html', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style>${html}<script>${script}</script>` });
            });
            await page.goto('https://conciliation.test/admin/herramientas?seccion=conciliacion');
            const table = page.locator('.client-conciliation-panel > .ht-table-scroll > table');
            assert.equal(await table.locator('thead th').count(), 15);
            assert.equal(await table.locator('thead th').nth(5).textContent(), 'Monto del periodo');
            assert.equal(await table.locator('thead th').nth(6).textContent(), 'Conciliación');
            assert.equal(await table.locator('thead th').nth(7).textContent(), 'Monto después de conciliación');
            assert.equal(await table.locator('thead th').nth(12).textContent(), 'Observaciones');
            const row = table.locator('tbody tr').first();
            const total = (await row.locator('td').nth(5).textContent()).trim();
            const reconciled = (await row.locator('td').nth(7).textContent()).trim();
            assert.equal(await row.locator('td').nth(5).locator('input').count(), 0);
            assert.equal(await row.locator('td').nth(7).locator('input').count(), 0);
            const trigger = row.locator('[data-client-detail-open]');
            await trigger.click();
            const dialog = page.getByRole('dialog', { name: 'Detalle de conciliación', exact: true });
            await dialog.getByText('No se pudo cargar la conciliación. Inténtalo de nuevo.', { exact: true }).waitFor();
            await dialog.getByRole('button', { name: 'Reintentar' }).click();
            await dialog.getByRole('table').waitFor();
            assert.equal(await dialog.locator('[data-period-item]').count(), 3);
            assert.equal(await dialog.locator('thead th').count(), 10);
            assert.equal(await dialog.locator('.cp-summary > div').count(), 3);
            const summary = await dialog.locator('.cp-summary').textContent();
            assert.ok(summary.includes(total) && summary.includes(reconciled));
            assert.equal(await dialog.locator('input, select, form').count(), 0);
            assert.equal(await dialog.getByRole('button', { name: 'Aceptar y continuar' }).isEnabled(), true);
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
            await page.screenshot({ path: path.join(root, `storage/app/testing/client-conciliation-detail-${width}.png`) });
            await page.keyboard.press('Escape');
            await dialog.waitFor({ state: 'hidden' });
            assert.equal(await trigger.evaluate(el => el === document.activeElement), true);
            await trigger.click();
            await dialog.getByRole('table').waitFor();
            await dialog.locator('footer').getByRole('button', { name: 'Cancelar', exact: true }).click();
            await dialog.waitFor({ state: 'hidden' });
            await row.locator('[data-client-observations-open]').click();
            const observations = page.getByRole('dialog', { name: 'Observaciones', exact: true });
            await observations.getByText('No hay observaciones registradas para este periodo.').waitFor();
            await observations.getByRole('button', { name: 'Cerrar observaciones', exact: true }).click();
            assert.deepEqual(methods, ['GET', 'GET', 'GET', 'GET']);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('hospital accepts or requests changes through the shared detail and safely retries saving', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) for (const withChanges of [false, true]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            const errors = [], posts = [];
            let saved = null;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const request = route.request(), url = new URL(request.url());
                if (url.hostname !== 'conciliation.test') return route.abort();
                if (url.pathname.endsWith('/detalle')) return route.fulfill({ contentType: 'application/json', body: saved ? fixture('clientReviewedDetail', saved) : detail });
                if (url.pathname.endsWith('/aceptar')) {
                    posts.push(request.postDataJSON());
                    if (posts.length === 1) return route.fulfill({ status: 503, json: { message: 'No se pudo confirmar la respuesta.' } });
                    saved = request.postDataJSON();
                    return route.fulfill({ status: 201, contentType: 'application/json', body: fixture('clientAccept', saved) });
                }
                return route.fulfill({ contentType: 'text/html', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style>${html}<script>${script}</script>` });
            });
            await page.goto('https://conciliation.test/admin/herramientas?seccion=conciliacion');
            const trigger = page.locator('[data-client-detail-open]');
            await trigger.click();
            const dialog = page.getByRole('dialog', { name: 'Detalle de conciliación', exact: true });
            await dialog.getByRole('table').waitFor();
            const accept = dialog.getByRole('button', { name: 'Aceptar y continuar', exact: true });
            assert.deepEqual(await dialog.locator('[data-period-item] .cp-status').allTextContents(), ['Sin Cambios', 'Sin Cambios', 'Sin Cambios']);
            if (withChanges) {
                const firstRow = dialog.locator('[data-period-item]').first();
                const no = firstRow.getByRole('button', { name: 'No', exact: true });
                const reasonDialog = page.getByRole('dialog', { name: 'Motivo de No conciliable', exact: true });
                for (const close of ['Cancelar', 'Cerrar motivo', 'Escape']) {
                    await no.click();
                    await reasonDialog.locator('textarea').fill('Este borrador se descarta.');
                    if (close === 'Escape') await page.keyboard.press('Escape');
                    else await reasonDialog.getByRole('button', { name: close, exact: true }).click();
                    await reasonDialog.waitFor({ state: 'hidden' });
                    assert.equal(await no.getAttribute('aria-pressed'), 'false');
                    assert.match(await dialog.locator('[data-period-excluded]').innerText(), /0\.00/);
                    assert.equal(await dialog.isVisible(), true);
                }
                await no.click();
                assert.equal(await reasonDialog.locator('textarea').inputValue(), '');
                await reasonDialog.locator('textarea').fill('   ');
                await reasonDialog.getByRole('button', { name: 'Aceptar y guardar', exact: true }).click();
                await reasonDialog.getByText('Indica el motivo del cambio.', { exact: true }).waitFor();
                assert.equal(posts.length, 0);
                await reasonDialog.locator('textarea').fill('Diferencia de entrega para revisar.');
                await page.screenshot({ path: path.join(root, `storage/app/testing/client-conciliation-reason-${width}.png`) });
                await reasonDialog.getByRole('button', { name: 'Aceptar y guardar', exact: true }).click();
                await reasonDialog.waitFor({ state: 'hidden' });
                assert.match(await dialog.locator('[data-period-excluded]').innerText(), /500\.00/);
                assert.match(await dialog.locator('[data-period-summary-new]').innerText(), /700\.25/);
                assert.deepEqual(await dialog.locator('[data-period-item] .cp-status').allTextContents(), ['Con Cambios', 'Sin Cambios', 'Sin Cambios']);
                assert.equal(posts.length, 0);
                assert.equal(await dialog.locator('textarea').count(), 0);
                await firstRow.locator('[data-client-row-observations]').click();
                assert.equal(await reasonDialog.locator('textarea').inputValue(), 'Diferencia de entrega para revisar.');
                await reasonDialog.locator('textarea').fill('Edición cancelada');
                await reasonDialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
                await firstRow.getByRole('button', { name: 'Sí', exact: true }).click();
                assert.equal(await firstRow.locator('[data-client-row-observations]').isDisabled(), true);
                assert.equal(await firstRow.locator('.cp-status').innerText(), 'Sin Cambios');
                assert.match(await dialog.locator('[data-period-summary-new]').innerText(), /1,200\.25/);
                await no.click();
                assert.equal(await reasonDialog.locator('textarea').inputValue(), '');
                await reasonDialog.locator('textarea').fill('Diferencia de entrega para revisar.');
                await reasonDialog.getByRole('button', { name: 'Aceptar y guardar', exact: true }).click();
            }
            await accept.click();
            await dialog.getByText('No se pudo confirmar la respuesta.', { exact: true }).waitFor();
            await accept.click();
            await dialog.waitFor({ state: 'hidden' });
            assert.equal(posts.length, 2);
            assert.deepEqual(posts[0], posts[1]);
            if (withChanges) assert.deepEqual(Object.values(posts[0].reasons), ['Diferencia de entrega para revisar.']);
            const row = page.locator('[data-client-submission]').first();
            assert.equal(await row.locator('[data-client-submission-status]').innerText(), withChanges ? 'Con cambios' : 'Aceptada');
            assert.equal(await row.getAttribute('data-conciliation-differences'), String(withChanges));
            for (const [name, color] of [['total', 'rgb(107, 114, 128)'], ['yes', 'rgb(202, 138, 4)'], ['no', 'rgb(220, 38, 38)']]) {
                assert.equal(await row.locator(`.ht-conciliation-count--${name}`).evaluate(el => getComputedStyle(el).color), withChanges ? color : 'rgb(86, 103, 127)');
            }
            await page.locator('.client-conciliation-panel > .ht-table-scroll').evaluate(el => { el.scrollLeft = el.scrollWidth; });
            await page.screenshot({ path: path.join(root, `storage/app/testing/client-conciliation-counts-${width}-${withChanges}.png`) });
            assert.match(await row.locator('[data-client-new-amount]').innerText(), withChanges ? /700\.25/ : /1,200\.25/);
            await trigger.click();
            await dialog.getByText('Respuesta del hospital:', { exact: false }).waitFor();
            assert.deepEqual(await dialog.locator('[data-period-item] .cp-status').allTextContents(), [withChanges ? 'Con Cambios' : 'Sin Cambios', 'Sin Cambios', 'Sin Cambios']);
            if (withChanges) await page.screenshot({ path: path.join(root, `storage/app/testing/client-conciliation-row-status-${width}.png`) });
            assert.equal(await accept.isDisabled(), true);
            assert.equal(await dialog.locator('[data-period-choice]:enabled').count(), 0);
            if (withChanges) {
                await dialog.locator('[data-client-row-observations]:enabled').first().click();
                const savedReason = page.getByRole('dialog', { name: 'Observaciones de la remisión', exact: true });
                assert.equal(await savedReason.locator('textarea').inputValue(), 'Diferencia de entrega para revisar.');
                assert.equal(await savedReason.locator('textarea').evaluate(el => el.readOnly), true);
                assert.equal(await savedReason.getByRole('button', { name: 'Aceptar y guardar' }).count(), 0);
                await savedReason.getByRole('button', { name: 'Cerrar motivo' }).click();
            }
            await dialog.getByRole('button', { name: 'Cerrar detalle' }).click();
            await row.locator('[data-client-observations-open]').click();
            const observations = page.getByRole('dialog', { name: 'Observaciones', exact: true });
            await observations.getByText(withChanges ? 'Diferencia de entrega para revisar.' : 'No hay observaciones registradas para este periodo.', { exact: true }).waitFor();
            await page.screenshot({ path: path.join(root, `storage/app/testing/client-conciliation-observations-${width}-${withChanges}.png`) });
            await observations.getByRole('button', { name: 'Cerrar', exact: true }).click();
            assert.equal(await page.getByRole('button', { name: 'Autorizar cambios', exact: true }).count(), 0);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('observations retry loading and display saved notes as text', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage();
        const data = JSON.parse(detail), methods = [];
        const note = '<img src=x onerror="window.noteExecuted=true">\nMotivo de prueba.';
        data.group.rows[0].reason = note;
        await page.route('**/*', route => {
            const request = route.request(), url = new URL(request.url());
            if (url.hostname !== 'conciliation.test') return route.abort();
            if (url.pathname.endsWith('/detalle')) {
                methods.push(request.method());
                return route.fulfill({ status: methods.length === 1 ? 503 : 200, json: data });
            }
            return route.fulfill({ contentType: 'text/html', body: `<meta charset="utf-8"><style>${styles}</style>${html}<script>${script}</script>` });
        });
        await page.goto('https://conciliation.test/admin/herramientas?seccion=conciliacion');
        const trigger = page.locator('[data-client-observations-open]');
        await trigger.click();
        const dialog = page.getByRole('dialog', { name: 'Observaciones', exact: true });
        await dialog.getByRole('button', { name: 'Reintentar' }).click();
        await dialog.locator('.client-observation p').waitFor();
        assert.equal(await dialog.locator('.client-observation p').textContent(), note);
        assert.equal(await dialog.locator('img, script').count(), 0);
        assert.equal(await page.evaluate(() => window.noteExecuted), undefined);
        await page.keyboard.press('Escape');
        await dialog.waitFor({ state: 'hidden' });
        assert.equal(await trigger.evaluate(el => el === document.activeElement), true);
        assert.deepEqual(methods, ['GET', 'GET']);
    } finally { await browser.close(); }
});

test('clients request changes with reasons, cancel and retry without authorization controls or duplicate requests', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            const errors = [], posts = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const request = route.request(), url = new URL(request.url());
                if (url.hostname !== 'conciliation.test') return route.abort();
                if (url.pathname.endsWith('/solicitar-cambios')) {
                    if (request.method() === 'GET') return route.fulfill({ contentType: 'application/json', body: form });
                    posts.push(request.postDataJSON());
                    return route.fulfill({ status: posts.length === 1 ? 503 : 201, contentType: 'application/json', body: JSON.stringify({ message: posts.length === 1 ? 'No se pudo confirmar la solicitud.' : 'Solicitud CON-000002 enviada a PROMESA Prodifem. Pendiente de autorización.' }) });
                }
                return route.fulfill({ contentType: 'text/html', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style>${html}<script>${script}</script>` });
            });
            await page.goto('https://conciliation.test/admin/herramientas?seccion=conciliacion');
            assert.equal(await page.getByRole('button', { name: 'Autorizar cambios', exact: true }).count(), 0);
            const trigger = page.getByRole('button', { name: 'Solicitar cambios', exact: true });
            await trigger.click();
            const dialog = page.getByRole('dialog', { name: 'Solicitar cambios', exact: true });
            const submit = dialog.getByRole('button', { name: 'Enviar solicitud de cambios' });
            await submit.click();
            await dialog.getByText('Indica al menos un cambio y su motivo.', { exact: true }).waitFor();
            const row = dialog.locator('[data-client-change-row]').first();
            await row.locator('select').selectOption('0');
            await submit.click();
            await dialog.getByText('Indica el motivo de cada cambio solicitado.', { exact: true }).waitFor();
            await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(posts.length, 0);
            await trigger.click();
            await row.locator('textarea').fill('Solicito revisar la entrega de esta remisión.');
            await row.locator('select').selectOption('0');
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            if (width === 1440) {
                mkdirSync(path.join(root, 'storage/app/testing'), { recursive: true });
                await page.screenshot({ path: path.join(root, 'storage/app/testing/client-conciliation.png') });
            }
            await submit.click();
            await dialog.getByText('No se pudo confirmar la solicitud.', { exact: true }).waitFor();
            await submit.click();
            await dialog.waitFor({ state: 'hidden' });
            assert.equal(posts.length, 2);
            assert.equal(posts[0].submission_key, posts[1].submission_key);
            assert.deepEqual(Object.values(posts[0].requests)[0], { conciliable: false, reason: 'Solicito revisar la entrega de esta remisión.' });
            await page.getByText('Solicitud CON-000002 enviada a PROMESA Prodifem. Pendiente de autorización.', { exact: true }).waitFor();
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
