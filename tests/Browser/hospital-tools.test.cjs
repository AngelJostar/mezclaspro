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
const script = buildSync({ stdin: { contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js'; import './resources/js/hospital-tools.js'; import './resources/js/table-column-filters.js'; import './resources/js/fixed-table-scrollbar.js'; Alpine.start();`, resolveDir: root },
    loader: { '.css': 'empty' }, bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const pages = Object.fromEntries(['conciliacion', 'facturacion', 'ajustes'].map(tab => [tab, execFileSync('php',
    ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/hospital-entry.php', 'herramientas', tab], { cwd: root, encoding: 'utf8' })]));

test('quick filters sit below the tools tabs, preserve dates and separate invoice paperwork from payment', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                const query = Object.fromEntries(url.searchParams);
                const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/hospital-entry.php',
                    'herramientas', query.tab || 'conciliacion', JSON.stringify(query), 'quick-filters'], { cwd: root, encoding: 'utf8' });
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{open:false}">${html}</div><script>${script}</script>` });
            });
            await page.goto('http://localhost/admin/herramientas?desde=2026-08-01&hasta=2026-09-30');
            const conciliation = page.getByRole('navigation', { name: 'Filtros de conciliación', exact: true });
            assert.deepEqual(await conciliation.getByRole('link').allTextContents(), ['Todas', 'Conciliables', 'No conciliables']);
            const tabs = await page.locator('.ht-tabs').boundingBox();
            const buttons = await conciliation.boundingBox();
            const dates = await page.locator('#tools-filters').boundingBox();
            assert.ok(buttons.y >= tabs.y + tabs.height && buttons.y + buttons.height <= dates.y);
            for (const [label, count] of [['No conciliables', 1], ['Conciliables', 7], ['Todas', 8]]) {
                await conciliation.getByRole('link', { name: label, exact: true }).click();
                await page.waitForLoadState('load');
                assert.equal(await page.locator('.ht-conciliation-table tbody tr').count(), count);
                assert.equal(await conciliation.getByRole('link', { name: label, exact: true }).getAttribute('aria-current'), 'page');
                assert.equal(new URL(page.url()).searchParams.get('desde'), '2026-08-01');
            }
            await conciliation.getByRole('link', { name: 'No conciliables', exact: true }).click();
            await page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(new URL(page.url()).searchParams.get('conciliacion_estado'), 'no_conciliables');
            assert.equal(await page.locator('.ht-conciliation-table tbody tr').count(), 1);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-quick-filters-${width}.png`) });
            await page.getByRole('link', { name: 'Facturación', exact: true }).click();
            const invoices = page.getByRole('navigation', { name: 'Filtros de facturación', exact: true });
            assert.deepEqual(await invoices.getByRole('link').allTextContents(), ['Recibidas', 'En firma', 'Entregadas']);
            for (const [label, count, folio] of [['Recibidas', 3, 'F-1051'], ['En firma', 1, 'F-1052'], ['Entregadas', 1, 'F-1053']]) {
                await invoices.getByRole('link', { name: label, exact: true }).click();
                await page.waitForLoadState('load');
                assert.equal(await page.locator('.ht-invoice-table tbody tr').count(), count);
                assert.ok((await page.locator('.ht-invoice-table').innerText()).includes(folio));
            }
            await page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(new URL(page.url()).searchParams.get('tramite_estado'), 'delivered');
            assert.equal(new URL(await page.getByRole('link', { name: 'Exportar a Excel', exact: true }).getAttribute('href')).searchParams.get('tramite_estado'), 'delivered');
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `invoices-quick-filters-${width}.png`) });
            await page.getByRole('link', { name: 'Bitácora de ajustes', exact: true }).click();
            await page.waitForLoadState('load');
            assert.equal(await page.locator('.ht-quick-filters').count(), 0);
            assert.equal(await page.getByRole('navigation', { name: 'Estado del ajuste' }).count(), 0);
            assert.equal(await page.getByRole('button', { name: 'Ordenar y filtrar Estado del ajuste', exact: true }).count(), 1);
            assert.equal(await page.getByRole('link', { name: 'Descargar reporte', exact: true }).count(), 1);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('hospital tools layout, range calendar, periods, tabs, and details work on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                const html = pages[url.searchParams.get('tab') || 'conciliacion'];
                if (!html) return route.abort();
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{ open: false }">${html}</div><script>${script}</script>` });
            });
            await page.goto('http://localhost/admin/herramientas');
            const tools = page.locator('[data-hospital-tools]');
            assert.equal(await tools.locator('.ht-table tbody tr').count(), 4);
            assert.equal(await tools.locator('svg').count() > 5, true);
            assert.equal(await tools.getByText('Hospital ajeno').count(), 0);
            assert.equal(await tools.locator('.ht-view').first().getAttribute('href'), 'http://localhost/admin/oncologicos/mezclas/1');
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1));
            const opener = page.locator('[data-open-range="hasta"]');
            await opener.click();
            const calendar = page.getByRole('dialog', { name: 'Seleccionar rango' });
            await calendar.waitFor({ state: 'visible' });
            const bounds = await calendar.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 961);
            await calendar.locator('[data-date="2026-09-03"]').click();
            assert.equal(await calendar.getByRole('button', { name: 'Aplicar rango' }).isEnabled(), false);
            await calendar.locator('[data-date="2026-09-15"]').click();
            assert.equal(await calendar.locator('.in-range').count(), 13);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `tools-calendar-${width}.png`) });
            await calendar.getByRole('button', { name: 'Aplicar rango' }).click();
            assert.equal(await tools.locator('[name="desde"]').inputValue(), '2026-09-03');
            assert.equal(await tools.locator('[name="hasta"]').inputValue(), '2026-09-15');
            await opener.click();
            await calendar.locator('[data-date="2026-09-20"]').click();
            await page.keyboard.press('Escape');
            assert.equal(await calendar.isVisible(), false);
            assert.equal(await tools.locator('[name="hasta"]').inputValue(), '2026-09-15');
            await tools.getByRole('radio', { name: 'Mes', exact: true }).check();
            assert.equal(await tools.locator('[name="desde"]').inputValue(), '2026-09-01');
            assert.equal(await tools.locator('[name="hasta"]').inputValue(), '2026-09-30');
            await tools.getByRole('radio', { name: 'Año', exact: true }).check();
            assert.equal(await tools.locator('[name="desde"]').inputValue(), '2026-01-01');
            assert.equal(await tools.locator('[name="hasta"]').inputValue(), '2026-12-31');
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `tools-${width}.png`) });
            await tools.getByRole('link', { name: 'Facturación', exact: true }).click();
            await page.waitForURL('**tab=facturacion');
            assert.equal(await tools.locator('.ht-table tbody tr').count(), 2);
            assert.equal(await tools.getByText('FACTURA-AJENA').count(), 0);
            await tools.getByRole('link', { name: 'Bitácora de ajustes', exact: true }).click();
            await page.waitForURL('**tab=ajustes');
            assert.equal(await tools.locator('.ht-table tbody tr').count(), 1);
            assert.equal(await tools.locator('[data-approval-popup]').count(), 1);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('conciliable switch saves, disables while saving, and reverts after a rejected update', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage();
        let reject = false;
        let posted;
        await page.route('**/*', route => {
            if (route.request().method() === 'PATCH') {
                posted = route.request().postDataJSON();
                return route.fulfill({ status: reject ? 409 : 200, contentType: 'application/json', body: JSON.stringify(reject ? { message: 'El estado cambio.' } : { conciliable: 'No' }) });
            }
            return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="test"><style>${css}</style><div x-data="{ open: false }">${pages.conciliacion}</div><script>${script}</script>` });
        });
        await page.goto('http://localhost/admin/herramientas');
        assert.equal(await page.locator('[data-conciliable]:checked').count(), 4);
        assert.deepEqual(await page.locator('.ht-switch output').allTextContents(), ['Sí', 'Sí', 'Sí', 'Sí']);
        for (const [name, previous] of [['Conciliable oncologicos mezcla 1', 'Si'], ['Conciliable antibioticos mezcla 2', null]]) {
            const toggle = page.getByRole('switch', { name, exact: true });
            reject = false;
            await toggle.uncheck();
            await page.getByRole('status').filter({ hasText: 'Estado conciliable guardado.' }).waitFor();
            assert.deepEqual(posted, { conciliable: false, previous });
            assert.equal(await toggle.isEnabled(), true);
            assert.equal(await toggle.isChecked(), false);
            assert.equal(await toggle.evaluate(input => input.closest('td').dataset.columnFilterValue), 'No');
            reject = true;
            await toggle.check();
            await page.getByRole('status').filter({ hasText: 'El estado cambio.' }).waitFor();
            assert.equal(await toggle.isChecked(), false);
            assert.equal(await toggle.isEnabled(), true);
        }
    } finally { await browser.close(); }
});

test('conciliation scrollbar stays at the viewport bottom and moves every column on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 1366, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            await page.route('**/*', route => new URL(route.request().url()).pathname === '/img/promesa-logo.png'
                ? route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) }) : route.abort());
            await page.setContent(`<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{ open: false }">${pages.conciliacion}</div><script>${script}</script>`);
            const source = page.locator('.ht-table-scroll[data-sticky-x-position="viewport"]');
            const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla', exact: true });
            await bar.waitFor();
            const box = await bar.boundingBox();
            const sourceBox = await source.boundingBox();
            assert.equal(Math.round(box.y + box.height), 900);
            assert.ok(Math.abs(box.x - sourceBox.x) < 1 && Math.abs(box.width - sourceBox.width) < 1);
            await bar.getByRole('button', { name: 'Desplazar columnas a la derecha', exact: true }).click();
            assert.ok(await source.evaluate(el => el.scrollLeft > 0));
            await bar.getByRole('slider').focus();
            await page.keyboard.press('End');
            const last = await source.locator('th').last().boundingBox();
            assert.ok(last.x >= sourceBox.x && last.x + last.width <= sourceBox.x + sourceBox.width + 1);
            await source.locator('tbody').evaluate(body => {
                const row = body.firstElementChild;
                for (let i = 0; i < 20; i++) body.append(row.cloneNode(true));
            });
            await page.evaluate(() => scrollTo(0, 600));
            await page.waitForFunction(() => Math.abs(document.querySelector('.fixed-table-scrollbar').getBoundingClientRect().bottom - innerHeight) < 1);
            assert.equal(await bar.isVisible(), true);
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-fixed-scrollbar-${width}.png`) });
            await bar.getByRole('slider').focus();
            await page.keyboard.press('Home');
            assert.equal(await source.evaluate(el => el.scrollLeft), 0);
            assert.equal(await page.locator('.fixed-table-scrollbar').count(), 1);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            await page.close();
        }
    } finally { await browser.close(); }
});

test('conciliation download uses the currently selected dates and retained column filters', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const requests = [];
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                const query = {};
                for (const [key, value] of url.searchParams) {
                    const column = key.match(/^columnas\[([^\]]+)\]\[\d*\]$/);
                    if (column) ((query.columnas ||= {})[column[1]] ||= []).push(value);
                    else query[key] = value;
                }
                const downloading = url.pathname === '/admin/herramientas/conciliacion/exportar';
                const args = ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/hospital-entry.php', 'herramientas', 'conciliacion', JSON.stringify(query)];
                if (downloading) {
                    requests.push(query);
                    const body = execFileSync('php', [...args, 'export-conciliation'], { cwd: root });
                    return route.fulfill({ contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', headers: { 'Content-Disposition': 'attachment; filename="conciliacion_del_periodo.xlsx"' }, body });
                }
                if (url.pathname !== '/admin/herramientas') return route.abort();
                const html = execFileSync('php', args, { cwd: root, encoding: 'utf8' });
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{open:false}">${html}</div><script>${script}</script>` });
            });
            const query = new URLSearchParams({ orden: 'id', direccion: 'desc', 'columnas[type][]': 'Oncologica' });
            await page.goto(`http://localhost/admin/herramientas?${query}`);
            const button = page.getByRole('button', { name: 'Descargar reporte', exact: true });
            await button.waitFor();
            const bounds = await button.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            await page.getByLabel('Desde', { exact: true }).fill('2026-09-08');
            await page.getByLabel('Hasta', { exact: true }).fill('2026-09-08');
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-export-${width}.png`) });
            const pending = page.waitForEvent('download');
            await button.click();
            const download = await pending;
            assert.equal(download.suggestedFilename(), 'conciliacion_del_periodo.xlsx');
            assert.equal(await download.failure(), null);
            assert.equal(readFileSync(await download.path()).subarray(0, 2).toString(), 'PK');
            assert.deepEqual(requests[0], { tab: 'conciliacion', conciliacion_estado: 'todas', orden: 'id', direccion: 'desc', columnas: { type: ['Oncologica'] }, periodo: 'dia', desde: '2026-09-08', hasta: '2026-09-08' });
            assert.equal(await page.locator('.ht-conciliation-table').count(), 1);
            await page.getByLabel('Desde', { exact: true }).fill('2026-09-09');
            await button.click();
            assert.equal(await page.getByLabel('Hasta', { exact: true }).evaluate(input => input.checkValidity()), false);
            assert.equal(requests.length, 1);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('conciliation headers sort and filter server results, retain selection, and work when empty', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                if (url.pathname !== '/admin/herramientas') return route.abort();
                const query = {};
                for (const [key, value] of url.searchParams) {
                    const column = key.match(/^columnas\[([^\]]+)\]\[\d*\]$/);
                    if (column) ((query.columnas ||= {})[column[1]] ||= []).push(value);
                    else query[key] = value;
                }
                const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                    'tests/Browser/fixtures/hospital-entry.php', 'herramientas', 'conciliacion', JSON.stringify(query)], { cwd: root, encoding: 'utf8' });
                return route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{ open: false }">${html}</div><script>${script}</script>` });
            });
            await page.goto('http://localhost/admin/herramientas');
            const table = page.locator('.ht-conciliation-table');
            const filter = name => page.getByRole('button', { name: `Ordenar y filtrar ${name}`, exact: true });
            const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
            const all = panel.getByRole('checkbox', { name: '(Todos)', exact: true });
            const accept = panel.getByRole('button', { name: 'Aceptar', exact: true });
            const ids = () => table.locator('tbody tr').evaluateAll(rows => rows.filter(row => row.cells.length === 13).map(row => row.cells[1].textContent.trim()));
            const navigate = action => Promise.all([page.waitForEvent('framenavigated', frame => frame === page.mainFrame()), action()]);
            await filter('Tipo').waitFor();
            assert.equal(await table.locator('thead th').count(), 13);
            assert.equal(await table.locator('thead button[data-column]').count(), 12);
            assert.equal(await filter('Fecha y hora programada de entrega').count(), 1);
            assert.equal(await table.locator('thead [data-command-column] button').count(), 0);
            assert.equal(await table.locator('.ht-approval:disabled').count(), 4);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-columns-${width}.png`) });
            await filter('ID mezcla').click();
            await navigate(() => panel.getByRole('button', { name: 'Ordenar ascendente', exact: true }).click());
            assert.deepEqual(await ids(), ['1', '2', '4', '11']);
            assert.equal(await table.locator('th').nth(1).getAttribute('aria-sort'), 'ascending');
            await filter('ID mezcla').click();
            await navigate(() => panel.getByRole('button', { name: 'Ordenar descendente', exact: true }).click());
            assert.deepEqual(await ids(), ['11', '4', '2', '1']);
            await filter('Tipo').click();
            await all.uncheck();
            await panel.locator('[data-filter-search]').fill('onco');
            assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
            await panel.getByRole('checkbox', { name: 'Oncologica', exact: true }).check();
            const box = await panel.boundingBox();
            assert.ok(box.x >= 0 && box.x + box.width <= width && box.y >= 0 && box.y + box.height <= 900);
            await navigate(() => accept.click());
            assert.deepEqual(await ids(), ['4', '1']);
            await filter('Conciliable').click();
            await all.uncheck();
            await panel.getByRole('checkbox', { name: 'Sí', exact: true }).check();
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TOOLS_SCREENSHOTS, `conciliation-filter-${width}.png`) });
            await navigate(() => accept.click());
            assert.deepEqual(await ids(), ['4', '1']);
            await navigate(() => page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click());
            assert.deepEqual(await ids(), ['4', '1']);
            await filter('Tipo').click();
            await all.check();
            await page.keyboard.press('Escape');
            assert.equal(await panel.isVisible(), false);
            assert.deepEqual(await ids(), ['4', '1']);
            await filter('Tipo').click();
            assert.equal(await panel.getByRole('checkbox', { name: 'Oncologica', exact: true }).isChecked(), true);
            assert.equal(await panel.getByRole('checkbox', { name: 'Nutricional', exact: true }).isChecked(), false);
            await all.check();
            await all.uncheck();
            await navigate(() => accept.click());
            assert.deepEqual(await ids(), []);
            assert.equal(await table.locator('thead button[data-column]').count(), 12);
            await filter('Tipo').click();
            await all.check();
            await navigate(() => accept.click());
            assert.deepEqual(await ids(), ['11', '4', '2', '1']);
            await page.goto('http://localhost/admin/herramientas?desde=2026-09-20&hasta=2026-09-30');
            await filter('Aprobación').click();
            assert.equal(await panel.locator('[data-filter-options] label').count(), 0);
            assert.equal(await panel.getByRole('button', { name: 'Ordenar ascendente', exact: true }).isEnabled(), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
