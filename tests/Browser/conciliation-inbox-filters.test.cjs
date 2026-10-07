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
const automaticFilters = buildSync({ entryPoints: [path.join(root, 'resources/js/table-column-filters.js')],
    bundle: true, write: false, format: 'iife' }).outputFiles[0].text;

test('conciliation headers open billing-style menus and apply, cancel and restore server filters', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.hostname !== 'conciliation.test' || route.request().resourceType() !== 'document') return route.abort();
            const html = execFileSync('php', ['tests/Browser/fixtures/conciliation-inbox.php', url.search.slice(1)], { cwd: root, encoding: 'utf8' });
            await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><style>${styles}</style>${html}<script>${automaticFilters}</script>` });
        });
        await page.goto('https://conciliation.test/admin/instituciones-reportes?seccion=conciliacion&bandeja=pendientes');
        const panel = page.locator('#conciliation-inbox-column-filter-panel');
        const triggers = page.locator('.js-inbox-column-filter');
        assert.equal(await triggers.count(), 8);
        for (const trigger of await triggers.all()) {
            await trigger.click();
            assert.equal(await panel.getByPlaceholder('Buscar (Todos)').isVisible(), true);
            assert.ok(await panel.locator('[data-filter-options] label').count() > 0);
            assert.equal(await trigger.getAttribute('aria-expanded'), 'true');
            await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(await panel.isVisible(), false);
        }
        assert.equal(await page.locator('[id^="automatic-table-filter-"][id$="-panel"]').count(), 0);
        const type = page.getByRole('button', { name: 'Filtrar Tipo', exact: true });
        await type.click();
        await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
        await panel.getByPlaceholder('Buscar (Todos)').fill('nutri');
        assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
        await panel.getByRole('checkbox', { name: 'Nutricional', exact: true }).check();
        await Promise.all([page.waitForURL(url => url.searchParams.has('columnas[type][]')),
            panel.getByRole('button', { name: 'Aceptar', exact: true }).click()]);
        await page.waitForLoadState();
        assert.equal(new URL(page.url()).searchParams.get('bandeja'), 'pendientes');
        assert.equal(await page.locator('#conciliation-inbox-table tbody tr').count(), 1);
        await type.click();
        assert.equal(await panel.getByRole('checkbox', { name: 'Nutricional', exact: true }).isChecked(), true);
        assert.equal(await panel.getByRole('checkbox', { name: 'Oncologica', exact: true }).isChecked(), false);
        await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
        await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
        assert.equal(await page.locator('#conciliation-inbox-table tbody tr').count(), 1);
        await type.click();
        await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
        await Promise.all([page.waitForURL(url => !url.searchParams.has('columnas[type][]')),
            panel.getByRole('button', { name: 'Aceptar', exact: true }).click()]);
        await page.waitForLoadState();
        assert.equal(await page.locator('#conciliation-inbox-table tbody tr').count(), 4);
        await page.setViewportSize({ width: 390, height: 800 });
        await page.getByRole('button', { name: 'Filtrar Estatus de conciliación', exact: true }).click();
        const bounds = await panel.boundingBox();
        assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= 390);
        assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 800);
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});
