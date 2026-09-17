const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ stdin: { contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js'; import './resources/js/hospital-tools.js'; import './resources/js/conciliation-agent.js'; import './resources/js/table-column-filters.js'; import './resources/js/fixed-table-scrollbar.js'; import './resources/js/administration-carousel.js'; Alpine.start();`, resolveDir: root }, loader: { '.css': 'empty' }, bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const php = (...args) => execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', ...args], { cwd: root, encoding: 'utf8' });
const hospital = php('tests/Browser/fixtures/hospital-entry.php', 'herramientas', 'conciliacion', JSON.stringify({ columnas: { type: ['Oncologica'] } }));
const inbox = php('tests/Browser/fixtures/conciliation-submission.php');
const detail = php('tests/Browser/fixtures/conciliation-submission.php', 'detail');
const preview = JSON.parse(php('tests/Browser/fixtures/conciliation-submission.php', 'preview', JSON.stringify({ desde: '2026-09-08', hasta: '2026-09-08', columnas: { type: ['Oncologica'] } })));
const document = html => `<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style><div x-data="{open:false}">${html}</div><script>${script}</script>`;

test('conciliation agent opens with applied filters, cancels without running and saves a shared execution on desktop/mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [], posts = [];
            let fail = false;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const request = route.request(), url = new URL(request.url());
                if (url.pathname.endsWith('promesa-logo.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                if (url.pathname.endsWith('agente-conciliacion')) {
                    if (fail) { fail = false; return route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ message: 'Servicio no disponible.' }) }); }
                    if (request.method() === 'POST') posts.push(request.postDataJSON());
                    const args = request.method() === 'POST' ? ['agent-run', request.postData()] : ['agent-info', JSON.stringify(Object.fromEntries(url.searchParams))];
                    return route.fulfill({ contentType: 'application/json', body: php('tests/Browser/fixtures/conciliation-submission.php', ...args) });
                }
                return route.fulfill({ contentType: 'text/html', body: document(php('tests/Browser/fixtures/conciliation-submission.php', '', JSON.stringify({ hospital_id: 1, search: 'Hospital' }))) });
            });
            await page.goto('http://localhost/admin/instituciones-reportes?seccion=conciliacion&hospital_id=1&search=Hospital');
            const trigger = page.getByRole('button', { name: 'Agente de IA', exact: true });
            assert.equal(await trigger.locator('svg').count(), 1);
            await trigger.click();
            const modal = page.getByRole('dialog', { name: 'Agente de conciliación', exact: true });
            await modal.locator('[data-agent-run]').waitFor();
            assert.equal(await modal.getByRole('button', { name: 'Reintentar', exact: true }).isVisible(), false);
            assert.match(await modal.locator('.ca-scope').innerText(), /Hospital de prueba/);
            assert.equal(await modal.locator('input[name="filters[hospital_id]"]').inputValue(), '1');
            assert.equal(await modal.getByRole('link', { name: 'Ver en el Centro de agentes' }).count(), 1);
            const bounds = await modal.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1 && bounds.y >= 0 && bounds.y + bounds.height <= 961);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-agent-${width}.png`) });
            await modal.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(posts.length, 0);
            assert.equal(await modal.isVisible(), false);
            fail = true;
            await trigger.click();
            await modal.getByText('Servicio no disponible.', { exact: true }).waitFor();
            await modal.getByRole('button', { name: 'Reintentar', exact: true }).click();
            await modal.locator('[data-agent-run]').waitFor();
            await modal.getByRole('textbox', { name: 'Instrucciones adicionales (opcional)' }).fill('Destacar datos faltantes.');
            await modal.getByRole('button', { name: 'Correr proceso', exact: true }).click();
            await modal.getByRole('heading', { name: 'Ejecución #1 · Completada' }).waitFor();
            assert.equal(posts.length, 1);
            assert.equal(posts[0].filters.hospital_id, '1');
            assert.equal(posts[0].instructions, 'Destacar datos faltantes.');
            assert.match(await modal.locator('.ca-result').innerText(), /Se revisaron 1 conciliaciones/);
            assert.equal(await modal.getByRole('link', { name: 'Ver conciliación CON-000001' }).count(), 1);
            await modal.getByRole('button', { name: 'Cerrar', exact: true }).click();
            assert.equal(await modal.isVisible(), false);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('send confirmation validates dates, cancels safely and retries with the same identifier on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [], posts = [], previews = [];
            let resolveSend, sendReady;
            const nextSend = () => new Promise(resolve => { sendReady = resolve; });
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                if (new URL(route.request().url()).pathname.endsWith('/conciliacion/resumen')) {
                    previews.push(new URL(route.request().url()).searchParams);
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify(preview) });
                }
                if (route.request().method() === 'POST') {
                    posts.push(new URLSearchParams(route.request().postData()));
                    return new Promise(resolve => { resolveSend = async success => {
                        await route.fulfill({ status: success ? 201 : 422, contentType: 'application/json', body: JSON.stringify({ folio: 'CON-000001', summary: preview.summary, message: success ? 'Solicitud CON-000001 enviada a Prodifem.' : 'No hay mezclas para enviar.' }) }); resolve();
                    }; sendReady(); });
                }
                if (new URL(route.request().url()).pathname.endsWith('promesa-logo.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: document(hospital) });
            });
            await page.goto('http://localhost/admin/herramientas');
            const trigger = page.getByRole('button', { name: 'Enviar a proveedor', exact: true });
            assert.equal(await trigger.locator('svg').count(), 1);
            await page.locator('#tools-desde').fill('2026-09-08');
            await page.locator('#tools-hasta').fill('2026-09-08');
            await trigger.click();
            const dialog = page.locator('[data-conciliation-dialog]');
            await dialog.waitFor({ state: 'visible' });
            await dialog.locator('form').waitFor({ state: 'visible' });
            assert.match(await dialog.textContent(), /08 de septiembre de 2026/);
            assert.match(await dialog.textContent(), /PROMESA/);
            assert.equal(await dialog.locator('form [data-summary="total.count"]').textContent(), '2');
            assert.equal(await dialog.locator('form [data-summary="no.count"]').first().textContent(), '1');
            assert.equal(await dialog.locator('.ht-send-total > [data-money]').textContent(), '$200.25 MXN');
            assert.equal(previews[0].get('columnas[type][]'), 'Oncologica');
            assert.equal(await dialog.locator('.ht-send-summary button').count(), 0);
            assert.equal(posts.length, 0);
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 961);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-send-${width}.png`) });
            await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(await dialog.isVisible(), false);
            assert.equal(posts.length, 0);
            await trigger.click();
            await dialog.locator('form').waitFor({ state: 'visible' });
            await dialog.getByRole('button', { name: 'Confirmar y enviar', exact: true }).click();
            assert.equal(posts.length, 0, 'A reason is required before submission');
            await dialog.getByRole('textbox').fill('Revisar registro de prueba');
            const firstPost = nextSend();
            await dialog.getByRole('button', { name: 'Confirmar y enviar', exact: true }).click();
            await firstPost;
            assert.equal(await dialog.getByRole('button', { name: 'Enviando...' }).isDisabled(), true);
            await page.keyboard.press('Escape');
            assert.equal(await dialog.isVisible(), true);
            await resolveSend(false);
            await dialog.getByRole('alert').waitFor({ state: 'visible' });
            assert.equal(posts.length, 1);
            const retryPost = nextSend();
            await dialog.getByRole('button', { name: 'Confirmar y enviar', exact: true }).click();
            await retryPost;
            await resolveSend(true);
            await dialog.locator('[data-send-success]').waitFor({ state: 'visible' });
            assert.equal(await dialog.getByRole('heading', { name: 'Conciliación enviada con éxito' }).count(), 1);
            assert.match(await dialog.locator('[data-send-success]').textContent(), /Enviada · Pendiente de revisión/);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-success-${width}.png`) });
            await dialog.locator('[data-send-success]').getByRole('button', { name: 'Cerrar', exact: true }).click();
            assert.equal(posts.length, 2);
            assert.equal(posts[0].get('submission_key'), posts[1].get('submission_key'));
            assert.equal(posts[1].get('desde'), '2026-09-08');
            assert.equal(posts[1].get('hasta'), '2026-09-08');
            assert.equal(posts[1].get('columnas[type][]'), 'Oncologica');
            assert.equal(posts[1].get('confirmation_token'), preview.confirmation_token);
            assert.equal(posts[1].get('reasons[oncologicos-1]'), 'Revisar registro de prueba');
            assert.ok(posts[1].get('_token'));
            assert.equal(await page.getByRole('status').filter({ hasText: 'CON-000001' }).isVisible(), true);
            await page.locator('#tools-desde').fill('2026-10-01');
            await trigger.click();
            assert.equal(await dialog.isVisible(), false);
            assert.equal(posts.length, 2);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('Prodifem opens a read-only summary modal with all rows, filters, search and download without pagination', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            let failNext = false;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('promesa-logo.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                if (url.pathname.endsWith('/1')) {
                    if (failNext) { failNext = false; return route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ message: 'Servicio temporalmente no disponible.' }) }); }
                    const body = php('tests/Browser/fixtures/conciliation-submission.php', 'summary', JSON.stringify(Object.fromEntries(url.searchParams)), 'many');
                    return route.fulfill({ contentType: 'application/json', body });
                }
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: document(php('tests/Browser/fixtures/conciliation-submission.php', '', '{}', 'many')) });
            });
            await page.goto('http://localhost/admin/instituciones-reportes?seccion=conciliacion');
            assert.equal(await page.getByRole('heading', { name: 'Panel Administrativo' }).count(), 1);
            assert.equal(await page.getByRole('cell', { name: 'CON-000001', exact: true }).count(), 1);
            assert.match(await page.locator('.ht-table thead th').nth(2).innerText(), /Institución/);
            assert.match(await page.locator('.ht-table thead th').nth(3).innerText(), /Hospital/);
            assert.equal(await page.locator('.ht-table tbody tr').first().locator('td').nth(2).innerText(), 'Institucion de prueba');
            await page.locator('.ht-table thead th').nth(2).getByRole('button').waitFor({ state: 'visible' });
            const badge = page.locator('.ht-no-count');
            assert.equal(await badge.innerText(), '1');
            assert.deepEqual(await badge.evaluate(node => {
                const style = getComputedStyle(node);
                return [style.borderTopColor, style.borderTopWidth, style.borderRadius];
            }), ['rgb(220, 38, 38)', '2px', '50%']);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-inbox-${width}.png`) });
            await page.getByRole('link', { name: 'Ver solicitud', exact: true }).click();
            const modal = page.getByRole('dialog', { name: 'Resumen de conciliación · CON-000001', exact: true });
            await modal.locator('.ht-review-table').waitFor({ state: 'visible' });
            assert.equal(page.url(), 'http://localhost/admin/instituciones-reportes?seccion=conciliacion');
            assert.equal(await modal.locator('tbody tr').count(), 17);
            assert.equal(await modal.getByRole('navigation', { name: 'Paginación de la conciliación' }).count(), 0);
            assert.equal(await modal.getByText('Mostrando 17 mezclas', { exact: true }).count(), 1);
            assert.equal(await modal.locator('thead th').count(), 16);
            assert.equal(await modal.locator('.ht-review-total dd').innerText(), '17');
            assert.equal(await modal.locator('.ht-review-no dd').innerText(), '1');
            assert.equal(await modal.getByRole('switch').count(), 0);
            assert.match(await modal.getByRole('link', { name: 'Descargar reporte', exact: true }).getAttribute('href'), /\/conciliaciones\/1\/descargar$/);
            assert.match(await modal.getByRole('link', { name: 'Ver mezcla 1', exact: true }).getAttribute('href'), /\/mezclas\/1$/);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            const bounds = await modal.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1 && bounds.y >= 0 && bounds.y + bounds.height <= 961);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-received-${width}.png`) });
            await modal.getByRole('link', { name: 'Ver mezcla 112', exact: true }).scrollIntoViewIfNeeded();
            assert.equal(await modal.getByRole('link', { name: 'Ver mezcla 112', exact: true }).isVisible(), true);
            await modal.getByRole('link', { name: 'No conciliables (1)', exact: true }).click();
            await modal.getByRole('link', { name: 'No conciliables (1)', exact: true }).and(modal.locator('[aria-current]')).waitFor({ state: 'visible' });
            assert.equal(await modal.locator('tbody tr').count(), 1);
            assert.match(await modal.locator('tbody').textContent(), /Registro pendiente de revision/);
            await modal.getByRole('link', { name: 'Conciliables (16)', exact: true }).click();
            await modal.getByRole('link', { name: 'Conciliables (16)', exact: true }).and(modal.locator('[aria-current]')).waitFor({ state: 'visible' });
            assert.equal(await modal.locator('tbody tr').count(), 16);
            await modal.getByRole('searchbox', { name: 'Buscar solicitud o paciente' }).fill('SOL-112');
            await modal.getByRole('button', { name: 'Buscar', exact: true }).click();
            await page.waitForFunction(() => document.querySelector('.ht-review-table tbody').textContent.includes('Paciente filtro 112'));
            assert.equal(await modal.locator('tbody tr').count(), 1);
            assert.equal(await modal.getByRole('searchbox').evaluate(input => document.activeElement === input), true);
            await modal.getByRole('searchbox').fill('Sin coincidencias');
            await modal.getByRole('searchbox').press('Enter');
            await modal.getByText('No hay mezclas que coincidan con los filtros.', { exact: true }).waitFor({ state: 'visible' });
            await page.keyboard.press('Escape');
            await page.locator('[data-conciliation-review]').waitFor({ state: 'hidden' });
            failNext = true;
            await page.getByRole('link', { name: 'Ver solicitud', exact: true }).click();
            await page.locator('[data-review-error]').waitFor({ state: 'visible' });
            await page.getByRole('button', { name: 'Reintentar', exact: true }).click();
            await modal.locator('.ht-review-table').waitFor({ state: 'visible' });
            await modal.getByRole('button', { name: 'Cerrar', exact: true }).click();
            await page.locator('[data-conciliation-review]').waitFor({ state: 'hidden' });
            assert.equal(await page.getByRole('link', { name: 'Ver solicitud', exact: true }).evaluate(node => node === document.activeElement), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('inbox institution and hospital filters cascade, persist, combine and clear on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('promesa-logo.png')) return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                const html = php('tests/Browser/fixtures/conciliation-submission.php', 'filters', JSON.stringify(Object.fromEntries(url.searchParams)));
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: document(html) });
            });
            await page.goto('http://localhost/admin/instituciones-reportes?seccion=conciliacion');
            const institution = page.getByLabel('Institución', { exact: true });
            const hospital = page.getByLabel('Hospital', { exact: true });
            const apply = page.getByRole('button', { name: 'Aplicar filtros', exact: true });
            const ids = () => page.locator('.ht-table tbody tr').evaluateAll(rows => rows.map(row => row.cells[0].textContent.trim()));
            assert.equal((await ids()).length, 3);
            await hospital.selectOption('1');
            await institution.selectOption('2');
            assert.equal(await hospital.inputValue(), '');
            assert.equal(await hospital.locator('option[value="1"]').isDisabled(), true);
            assert.equal(await hospital.locator('option[value="2"]').isEnabled(), true);
            await hospital.selectOption('2');
            await apply.click();
            await page.waitForURL('**hospital_id=2**');
            assert.deepEqual(await ids(), ['CON-000002']);
            assert.equal(await page.locator('.ht-table tbody tr').first().locator('td').nth(2).innerText(), 'Otra institucion');
            assert.equal(await institution.inputValue(), '2');
            assert.equal(await hospital.inputValue(), '2');
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-filters-${width}.png`) });
            await institution.selectOption('1');
            assert.equal(await hospital.inputValue(), '');
            await apply.click();
            await page.waitForURL('**institucion_id=1**');
            assert.deepEqual(await ids(), ['CON-000003', 'CON-000001']);
            await hospital.selectOption('3');
            await apply.click();
            await page.waitForURL('**hospital_id=3**');
            assert.deepEqual(await ids(), ['CON-000003']);
            await institution.selectOption('3');
            await apply.click();
            await page.waitForURL('**institucion_id=3**');
            assert.match(await page.locator('.ht-table tbody').textContent(), /No hay solicitudes.*filtros seleccionados/);
            await page.getByRole('link', { name: 'Limpiar', exact: true }).click();
            await page.waitForURL('**?seccion=conciliacion');
            assert.equal((await ids()).length, 3);
            assert.equal(await institution.inputValue(), '');
            assert.equal(await hospital.inputValue(), '');
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
