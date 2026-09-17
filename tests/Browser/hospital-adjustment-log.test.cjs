const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ stdin: { contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js'; import './resources/js/hospital-tools.js'; import './resources/js/table-column-filters.js'; import './resources/js/workflow-modal.js'; import './resources/js/mixture-messages.js'; import './resources/js/fixed-table-scrollbar.js'; Alpine.start();`, resolveDir: root },
    loader: { '.css': 'empty' }, bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const cache = new Map();
function render(mode, query) {
    const key = mode + JSON.stringify(query);
    if (!cache.has(key)) cache.set(key, execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/hospital-adjustment-log.php', mode, JSON.stringify(query)], { cwd: root, encoding: 'utf8' }));
    return cache.get(key);
}

test('adjustment log retains history, filters by status/date, and opens read-only versions on desktop/mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            page.setDefaultTimeout(10000);
            const errors = [];
            const messages = new Map([['oncologicos:1', [{ id: 1, side: 'central', author: 'Central de prueba', body: 'Propuesta registrada para revision.', sent_at: '2026-09-15T10:00:00-06:00' }]]]);
            const read = new Set();
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                const json = data => route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) });
                if (url.pathname.endsWith('/mensajes/estado')) return json({ summaries: Object.fromEntries(url.searchParams.getAll('targets[]').map(key => [key, { total: (messages.get(key) || []).length, unread: key === 'oncologicos:1' && !read.has(key) ? 1 : 0 }])) });
                if (url.pathname.includes('/solicitudes/mensajes/')) {
                    const parts = url.pathname.split('/');
                    const key = `${parts[4]}:${parts[5]}`;
                    if (parts[6] === 'leidos') { read.add(key); return json({ ok: true }); }
                    const history = messages.get(key) || [];
                    if (route.request().method() === 'POST') {
                        assert.equal(route.request().headers()['x-csrf-token'], 'test-token');
                        const message = { id: history.length + 1, side: 'hospital', author: 'Hospital de prueba', body: route.request().postDataJSON().body, sent_at: '2026-09-15T10:05:00-06:00' };
                        history.push(message); messages.set(key, history); return json({ message });
                    }
                    return json({ target: { id: Number(parts[5]), hospital: 'Hospital de prueba', patient: 'Paciente de prueba' }, side: 'hospital', can_send: true, messages: history.filter(m => m.id > Number(url.searchParams.get('after_id') || 0)), has_older: false });
                }
                const history = url.pathname.includes('/ajustes/oncologicos/');
                const query = {};
                for (const [key, value] of url.searchParams) {
                    const column = key.match(/^columnas\[([^\]]+)\]\[\d*\]$/);
                    if (column) ((query.columnas ||= {})[column[1]] ||= []).push(value);
                    else query[key] = value;
                }
                if (url.pathname.endsWith('/ajustes/exportar')) {
                    const body = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/hospital-adjustment-log.php', 'export', JSON.stringify(query)], { cwd: root });
                    return route.fulfill({ contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', headers: { 'Content-Disposition': 'attachment; filename="bitacora_de_ajustes.xlsx"' }, body });
                }
                if (history) query.target = url.pathname.split('/').pop();
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="test-token"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{open:false}">${render(history ? 'history' : 'log', query)}</div><script>${script}</script>` });
            });
            await page.goto('http://localhost/admin/herramientas?tab=ajustes');
            if (width < 640) await page.waitForFunction(() => document.querySelector('#logo-sidebar').getBoundingClientRect().right <= 1);
            const table = page.locator('.ht-log-table');
            assert.equal(await table.locator('tbody tr').count(), 7);
            assert.equal(await page.getByRole('checkbox', { name: 'Todo el historial' }).isChecked(), true);
            assert.equal(await page.getByLabel('Desde', { exact: true }).isDisabled(), true);
            assert.ok((await table.innerText()).includes('2025-01-05'));
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            assert.ok(await table.evaluate(el => el.getBoundingClientRect().width >= 1100));
            assert.equal((await page.getByRole('checkbox', { name: 'Todo el historial' }).boundingBox()).height, 18);
            assert.equal(await table.locator('th').count(), 17);
            assert.equal(await table.locator('th button[data-column]').count(), 14);
            assert.equal(await table.locator('.ht-approval:disabled').count(), 7);
            assert.equal(await table.locator('[data-mixture-message-key]').count(), 7);
            const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla', exact: true });
            await bar.waitFor();
            assert.equal(Math.round((await bar.boundingBox()).y + (await bar.boundingBox()).height), 960);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `adjustment-log-${width}.png`), fullPage: true });
            const messageCell = table.locator('[data-mixture-message-key="oncologicos:1"]');
            await messageCell.locator('.mixture-message-history').click();
            const chat = page.locator('[data-mixture-chat]');
            await chat.getByText('Propuesta registrada para revision.', { exact: true }).waitFor();
            await chat.getByRole('textbox', { name: 'Mensaje', exact: true }).fill('Gracias. Revisaremos la propuesta.');
            await chat.getByRole('button', { name: 'Enviar mensaje', exact: true }).click();
            await chat.getByText('Gracias. Revisaremos la propuesta.', { exact: true }).waitFor();
            assert.equal(await chat.getByRole('button', { name: /eliminar|borrar/i }).count(), 0);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `adjustment-messages-${width}.png`) });
            await chat.getByRole('button', { name: 'Cerrar mensajes', exact: true }).click();
            const nutritionCell = table.locator('[data-mixture-message-key="nutricionales:11"]');
            assert.equal(await nutritionCell.locator('.mixture-message-history').isDisabled(), true);
            await nutritionCell.getByRole('button', { name: 'Enviar mensaje', exact: true }).click();
            await chat.getByText('Sin mensajes', { exact: true }).waitFor();
            assert.equal(await chat.getByText('Gracias. Revisaremos la propuesta.', { exact: true }).count(), 0);
            await chat.getByRole('button', { name: 'Cerrar mensajes', exact: true }).click();
            const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
            const approval = page.getByRole('button', { name: 'Ordenar y filtrar Aprobación', exact: true });
            await approval.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
            await panel.getByRole('checkbox', { name: 'Aprobada', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(await table.locator('tbody tr').count(), 2);
            assert.equal(new URL(await page.getByRole('link', { name: 'Descargar reporte', exact: true }).getAttribute('href')).searchParams.get('columnas[approval][0]'), 'Aprobada');
            const downloaded = page.waitForEvent('download');
            await page.getByRole('link', { name: 'Descargar reporte', exact: true }).click();
            const download = await downloaded;
            assert.equal(download.suggestedFilename(), 'bitacora_de_ajustes.xlsx');
            assert.equal(await download.failure(), null);
            assert.equal(readFileSync(await download.path()).subarray(0, 2).toString(), 'PK');
            await approval.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(await table.locator('tbody tr').count(), 7);
            assert.equal(await page.locator('.ht-quick-filters').count(), 0);
            assert.equal(await page.getByRole('navigation', { name: 'Estado del ajuste' }).count(), 0);
            const adjustmentFilter = page.getByRole('button', { name: 'Ordenar y filtrar Estado del ajuste', exact: true });
            for (const [name, count] of [['Ajuste Solicitado', 1], ['Ajuste autorizado', 1], ['Aprobada con Ajuste', 2], ['Rechazado', 2]]) {
                await adjustmentFilter.click();
                await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
                await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
                await panel.getByRole('checkbox', { name, exact: true }).check();
                await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
                await page.waitForLoadState('load');
                assert.equal(await table.locator('tbody tr').count(), count, name);
            }
            const exportUrl = new URL(await page.getByRole('link', { name: 'Descargar reporte', exact: true }).getAttribute('href'));
            assert.equal(exportUrl.pathname, '/admin/herramientas/ajustes/exportar');
            assert.equal(exportUrl.searchParams.get('columnas[adjustment_status][0]'), 'Rechazado');
            await page.getByRole('link', { name: 'Ver historial oncologicos mezcla 20', exact: true }).click();
            const modal = page.locator('[data-workflow-modal]');
            await modal.waitFor({ state: 'visible' });
            const frame = page.frameLocator('[data-workflow-frame]');
            await frame.getByRole('heading', { name: 'Historial de ajustes · Mezcla #20' }).waitFor();
            assert.equal(await frame.locator('form').count(), 0);
            assert.equal(await frame.locator('[data-adjustment-changed="true"]').count(), 1);
            assert.equal(await frame.getByText('Respuesta de Prodifem de prueba').count(), 1);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `adjustment-history-${width}.png`) });
            await frame.getByRole('link', { name: 'Cerrar historial' }).click();
            await modal.waitFor({ state: 'hidden' });
            await adjustmentFilter.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await page.waitForLoadState('load');
            await page.getByRole('link', { name: 'Ver historial oncologicos mezcla 1', exact: true }).click();
            await frame.locator('.ht-version').first().waitFor();
            assert.equal(await frame.locator('.ht-version').count(), 2);
            await frame.getByRole('link', { name: 'Cerrar historial' }).click();
            await modal.waitFor({ state: 'hidden' });
            await page.getByRole('checkbox', { name: 'Todo el historial' }).uncheck();
            assert.equal(await page.getByLabel('Desde', { exact: true }).isEnabled(), true);
            await page.getByLabel('Desde', { exact: true }).fill('2026-09-01');
            await page.getByLabel('Hasta', { exact: true }).fill('2026-09-30');
            await page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(await table.locator('tbody tr').count(), 6);
            assert.equal(await page.getByRole('checkbox', { name: 'Todo el historial' }).isChecked(), false);
            await page.getByRole('link', { name: 'Limpiar', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(await table.locator('tbody tr').count(), 7);
            assert.equal(await page.getByRole('checkbox', { name: 'Todo el historial' }).isChecked(), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
