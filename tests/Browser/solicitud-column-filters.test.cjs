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
const script = readFileSync(path.join(root, 'resources/js/table-column-filters.js'), 'utf8');

test('empty request lists keep searchable header filters without treating the empty message as a record', async () => {
    const html = execFileSync('php', ['tests/Browser/fixtures/request-scrollbar.php'], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 390, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 800 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<style>${styles}</style><main class="admin-page">${html}</main><script>${script}</script>`);
            const triggers = page.locator('thead button[data-column]');
            await triggers.first().waitFor();
            assert.deepEqual(await triggers.evaluateAll(buttons => buttons.map(button => Number(button.dataset.column))),
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 10, 11]);
            assert.equal(await page.locator('thead [data-command-column] button[data-column]').count(), 0);
            const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
            for (const trigger of await triggers.all()) {
                await trigger.click();
                assert.equal(await panel.locator('[data-filter-search]').isVisible(), true);
                assert.equal(await panel.locator('[data-filter-options] label').count(), 0);
                assert.equal(await panel.locator('[data-filter-empty]').isVisible(), true);
                const box = await panel.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width);
                assert.ok(box.y >= 0 && box.y + box.height <= 800);
                await panel.locator('[data-filter-search]').fill('hospital');
                await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
                assert.equal(await page.getByText('No se encontraron solicitudes.', { exact: true }).isVisible(), true);
            }

            // Simulate records arriving after the empty state without changing real hospital data.
            await page.locator('tbody').evaluate(tbody => {
                const records = [
                    ['Oncologica', '38', '24', 'Hospital Norte', 'Maria Perez', '2026-09-08 09:00', '2026-09-08 15:00', 'Aprobada', 'L001', 'Ver', 'Aprobada', 'Dispensar'],
                    ['Nutricional', '39', '25', 'Hospital Sur', 'Jose Lopez', '2026-09-08 10:00', '2026-09-08 16:00', 'Pendiente', 'L002', 'Ver', 'Sin accion', 'Proceso'],
                ];
                tbody.replaceChildren(...records.map(values => {
                    const row = document.createElement('tr');
                    for (let i = 0; i < 19; i++) row.insertCell().textContent = values[i] || 'Documento';
                    return row;
                }));
            });
            const hospitalFilter = page.getByRole('button', { name: 'Filtrar Hospital', exact: true });
            await hospitalFilter.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).uncheck();
            await panel.locator('[data-filter-search]').fill('norte');
            assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
            await panel.getByRole('checkbox', { name: 'Hospital Norte', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await page.locator('tbody tr:visible').count(), 1);
            assert.equal(await page.locator('tbody tr:visible td').nth(1).innerText(), '38');
            await hospitalFilter.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
            assert.equal(await page.locator('tbody tr:visible').count(), 1);
            await hospitalFilter.click();
            await panel.getByRole('checkbox', { name: '(Todos)', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await page.locator('tbody tr:visible').count(), 2);
            assert.equal(await triggers.count(), 11);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});

test('approval and next-process filters support selection, search, cancel and combined filters', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const category of ['oncologicos', 'antibioticos']) {
            const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/solicitud-column-filters.php', category], { cwd: root, encoding: 'utf8' });
            for (const width of [1366, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 800 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${styles}</style><main class="admin-page">${html}</main><script>${script}</script>`);
                const approval = page.getByRole('button', { name: 'Filtrar Aprobación', exact: true });
                const processFilter = page.getByRole('button', { name: 'Filtrar Próximo proceso', exact: true });
                await approval.waitFor();
                assert.equal(await processFilter.count(), 1);
                assert.equal(await page.getByRole('button', { name: 'Filtrar Ver', exact: true }).count(), 0);
                const rows = page.locator('tbody tr:visible');
                const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
                const accept = panel.getByRole('button', { name: 'Aceptar', exact: true });
                const all = panel.getByRole('checkbox', { name: '(Todos)', exact: true });
                assert.equal(await rows.count(), 6);

                await approval.click();
                assert.deepEqual(await panel.locator('[data-filter-options] label').allTextContents(), ['Aprobada', 'Aprobar', 'Rechazada']);
                await all.uncheck();
                await panel.getByRole('checkbox', { name: 'Aprobada', exact: true }).check();
                await accept.click();
                assert.equal(await rows.count(), 4);

                await processFilter.click();
                assert.deepEqual(await panel.locator('[data-filter-options] label').allTextContents(), ['Dispensar', 'Entregar', 'Inspeccionar', 'Preparar', 'Proceso']);
                await all.uncheck();
                await panel.locator('[data-filter-search]').fill('inspeccionar');
                assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
                await panel.getByRole('checkbox', { name: 'Inspeccionar', exact: true }).check();
                const box = await panel.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width);
                assert.ok(box.y >= 0 && box.y + box.height <= 800);
                if (process.env.INSPECTION_SCREENSHOTS && category === 'oncologicos') {
                    await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `solicitud-column-filters-${width}.png`) });
                }
                await accept.click();
                assert.equal(await rows.count(), 1);
                assert.equal((await rows.first().locator('td').nth(1).innerText()).trim(), '4');

                await processFilter.click();
                await all.check();
                await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
                assert.equal(await rows.count(), 1);

                await approval.click();
                await all.check();
                await all.uncheck();
                await panel.getByRole('checkbox', { name: 'Rechazada', exact: true }).check();
                await accept.click();
                assert.equal(await rows.count(), 0);

                for (const trigger of [approval, processFilter]) {
                    await trigger.click();
                    await all.check();
                    await accept.click();
                }
                assert.equal(await rows.count(), 6);
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
