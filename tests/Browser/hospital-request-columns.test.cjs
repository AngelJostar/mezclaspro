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

test('all hospital request categories show only eleven columns and preserve searchable filters', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const category of ['todas', 'nutricionales', 'oncologicos', 'antibioticos']) {
            for (const empty of [false, true]) {
                const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                    'tests/Browser/fixtures/hospital-request-columns.php', category, empty ? 'empty' : 'populated'], { cwd: root, encoding: 'utf8' });
                for (const width of [1366, 390]) {
                    const page = await browser.newPage({ viewport: { width, height: 800 } });
                    const errors = [];
                    page.on('pageerror', error => errors.push(error.message));
                    await page.route('**/*', route => route.abort());
                    await page.setContent(`<style>${styles}</style><main class="admin-page"><div class="admin-content">${html}</div></main><script>${filters}</script>`);
                    const approval = page.getByRole('button', { name: 'Filtrar Aprobación', exact: true });
                    await approval.waitFor();
                    assert.equal(await page.locator('thead th').count(), 11);
                    assert.equal(await page.locator('thead button[data-column]').count(), 10);
                    assert.equal(await page.getByRole('button', { name: 'Filtrar Próximo proceso', exact: true }).count(), 0);
                    await approval.click();
                    const panel = page.locator('[data-filter-search]').locator('..').locator('..');
                    await panel.locator('[data-filter-search]').fill('aprobada');
                    assert.equal(await panel.locator('[data-filter-options] label:visible').count(), empty ? 0 : 1);
                    const box = await panel.boundingBox();
                    assert.ok(box.x >= 0 && box.x + box.width <= width);
                    await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
                    if (!empty) {
                        assert.equal(await page.locator('tbody tr:visible').count(), category === 'todas' ? 3 : 1);
                        assert.deepEqual(await page.locator('tbody tr').evaluateAll(rows => [...new Set(rows.map(row => row.cells.length))]), [11]);
                    }
                    assert.deepEqual(errors, []);
                    await page.close();
                }
            }
        }
    } finally {
        await browser.close();
    }
});
