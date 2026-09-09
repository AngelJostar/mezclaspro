const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const filters = readFileSync(path.join(root, 'resources/js/table-column-filters.js'), 'utf8');
const livewire = readFileSync(path.join(root, 'vendor/livewire/livewire/dist/livewire.js'), 'utf8');

test('waste totals follow column filters and active sections without mixing units or requests', async () => {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/waste-report.php'], { cwd: root, encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1600, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<style>${styles}</style><main class="admin-page">${html}</main><script>${filters}</script><script>${livewire}</script>`);
            const total = page.locator('[data-waste-total="all"]');
            const sectionTotal = page.locator('[data-waste-current-total]');
            const allTotal = '300.00 mg \u00b7 70.00 mL \u00b7 2.00 frascos \u00b7 2.00 mezclas';
            await total.waitFor();
            assert.equal(await total.innerText(), allTotal);
            assert.equal(await sectionTotal.innerText(), allTotal);
            assert.equal(await page.locator('[data-waste-total="inspeccion"]').innerText(), '290.00 mg \u00b7 2.00 mezclas');
            const reportRows = page.locator('#waste-report-table tbody .js-waste-filter-row:visible');
            assert.equal(await reportRows.count(), 5);
            assert.match(await page.locator('#waste-report-table thead tr th').nth(5).innerText(), /UNIDADES/);
            for (const row of await page.locator('[data-waste-type="inspeccion"]').all()) {
                assert.equal((await row.locator('td').nth(4).innerText()).trim(), 'Medicamento de prueba');
                assert.match(await row.locator('td').nth(5).innerText(), /mg.*mezcla/);
            }

            const summaryFits = await page.locator('[data-waste-total]').evaluateAll(elements => elements.every(el =>
                el.scrollWidth <= el.clientWidth + 1 && el.getBoundingClientRect().right <= window.innerWidth));
            assert.equal(summaryFits, true);
            if (process.env.INSPECTION_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `waste-report-${width}.png`) });
            }

            const panel = page.locator('#waste-report-column-filter-panel');
            const select = async (label, value) => {
                await page.getByRole('button', { name: `Filtrar ${label}`, exact: true }).click();
                const all = panel.getByRole('checkbox', { name: '(Todos)', exact: true });
                await all.check();
                if (value !== null) {
                    await all.uncheck();
                    await panel.getByRole('checkbox', { name: value, exact: true }).check();
                }
                await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            };
            await select('Lote', 'LOTE-A');
            assert.equal(await reportRows.count(), 3);
            const lotTotal = '100.00 mg \u00b7 50.00 mL \u00b7 2.00 frascos \u00b7 1.00 mezcla';
            assert.equal(await total.innerText(), lotTotal);
            await page.getByRole('tab', { name: /Mermas de inspecci/ }).click();
            await page.waitForFunction(() => document.querySelector('[data-waste-current-total]').textContent === '90.00 mg \u00b7 1.00 mezcla');
            await page.waitForFunction(() => [...document.querySelectorAll('.js-waste-filter-row')].filter(row => row.getClientRects().length).length === 1);
            assert.equal(await reportRows.count(), 1);
            assert.equal(await total.innerText(), lotTotal);
            await page.getByRole('button', { name: 'Ordenar Unidades', exact: true }).click();
            assert.equal(await sectionTotal.innerText(), '90.00 mg \u00b7 1.00 mezcla');

            await page.getByRole('button', { name: 'Filtrar Lote', exact: true }).click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(await total.innerText(), lotTotal);
            await select('\u00c1rea', 'Nutricional');
            assert.equal(await reportRows.count(), 0);
            assert.equal(await total.innerText(), '0.00 unidades');
            assert.equal(await page.locator('#waste-filter-empty').isVisible(), true);
            await select('\u00c1rea', null);
            await select('Lote', null);
            await page.getByRole('tab', { name: /^Todas/ }).click();
            await page.waitForFunction(() => document.querySelectorAll('.js-waste-filter-row:not(.hidden)').length === 5);
            assert.equal(await total.innerText(), allTotal);

            await page.getByRole('tab', { name: /Solicitudes de Merma/ }).click();
            const requestsTotal = page.locator('[data-waste-request-total]');
            await requestsTotal.waitFor();
            assert.equal(await requestsTotal.innerText(), '30.00 mL \u00b7 3.00 frascos');
            assert.equal(await page.getByRole('form', { name: 'Filtrar reporte de mermas por periodo' }).isVisible(), true);
            await page.locator('#waste-request-table').getByRole('button', { name: 'Filtrar Producto', exact: true }).click();
            const requestPanel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]:visible');
            await requestPanel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
            await requestPanel.getByRole('checkbox', { name: 'Producto solicitado 1', exact: true }).check();
            await requestPanel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            await page.waitForFunction(() => document.querySelector('[data-waste-request-total]').textContent === '10.00 mL \u00b7 1.00 frasco');
            assert.equal(await total.textContent(), allTotal);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});
