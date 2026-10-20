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
const fixture = action => execFileSync('php', ['tests/Browser/fixtures/conciliation-period.php', action], { cwd: root, encoding: 'utf8' });
const html = fixture('clientPage'), form = fixture('clientForm');

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
