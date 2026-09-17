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
const filters = buildSync({ entryPoints: [path.join(root, 'resources/js/table-column-filters.js')], bundle: true, write: false, format: 'iife' }).outputFiles[0].text;

async function assertProcessHeader(page) {
    const status = page.locator('thead th').nth(13);
    const filter = status.getByRole('button', { name: /^Filtrar Estado de proceso/ });
    await filter.waitFor();
    assert.equal(await filter.getAttribute('data-column'), '13');
    assert.match(await page.locator('thead th').nth(11).innerText(), /APROBACI.N/i);
    const lines = await status.locator(':scope > div > span > span').evaluateAll(spans => spans.map(span => {
        const bounds = span.getBoundingClientRect();
        return { text: span.textContent.trim(), y: bounds.y, bottom: bounds.bottom };
    }));
    assert.equal(lines.length, 2);
    assert.equal(lines[0].text, 'Estado de');
    assert.match(lines[1].text, /^proceso/);
    assert.ok(lines[1].y >= lines[0].bottom - 1);
    assert.ok((await status.boundingBox()).width <= 136, 'process header stays compact');
    return status;
}

test('all hospital request categories show fourteen columns and preserve searchable filters', async () => {
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
                    await assertProcessHeader(page);
                    assert.equal(await page.locator('thead th').count(), 14);
                    assert.equal(await page.locator('thead button[data-column]').count(), 12);
                    const institution = page.locator('thead th').nth(3);
                    assert.match(await institution.innerText(), /INSTITUCI.N/i);
                    assert.match(await page.locator('thead th').nth(4).innerText(), /HOSPITAL/i);
                    await institution.getByRole('button', { name: 'Filtrar Institución', exact: true }).click();
                    const institutionPanel = page.locator('[data-filter-search]').locator('..').locator('..');
                    await institutionPanel.locator('[data-filter-search]').fill('Institucion de prueba');
                    assert.equal(await institutionPanel.locator('[data-filter-options] label:visible').count(), empty ? 0 : 1);
                    await institutionPanel.getByRole('button', { name: 'Aceptar', exact: true }).click();
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
                        assert.deepEqual(await page.locator('tbody tr').evaluateAll(rows => [...new Set(rows.map(row => row.cells.length))]), [14]);
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

test('administrative request tables place process status between approval and next process', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const category of ['todas', 'nutricionales', 'oncologicos', 'antibioticos']) {
            const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/hospital-request-columns.php', category, 'populated', 'Super Admin'], { cwd: root, encoding: 'utf8' });
            for (const width of [1366, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 800 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${styles}</style><main class="admin-page"><div class="admin-content">${html}</div></main><script>${filters}</script>`);
                const status = await assertProcessHeader(page);
                assert.equal(await page.locator('thead th').count(), 22);
                assert.match(await page.locator('thead th').nth(14).innerText(), /PR.XIMO\s+PROCESO/i);
                assert.equal((await page.locator('tbody tr').first().locator('td').nth(3).innerText()).trim(), 'Institucion de prueba');
                const value = (await page.locator('tbody tr').first().locator('td').nth(13).innerText()).trim();
                await status.getByRole('button').click();
                const panel = page.locator('[data-filter-search]').locator('..').locator('..');
                await panel.locator('[data-filter-search]').fill(value);
                assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 1);
                await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
                if (process.env.REQUEST_COLUMN_SCREENSHOTS) {
                    await status.scrollIntoViewIfNeeded();
                    await page.screenshot({ path: path.join(process.env.REQUEST_COLUMN_SCREENSHOTS, `request-process-${category}-${width}.png`) });
                    await page.locator('thead th').nth(3).scrollIntoViewIfNeeded();
                    await page.screenshot({ path: path.join(process.env.REQUEST_COLUMN_SCREENSHOTS, `request-institution-${category}-${width}.png`) });
                }
                assert.equal(await page.locator('tbody tr').first().locator('td').nth(3).evaluate(cell => cell.scrollWidth <= cell.clientWidth + 1), true);
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
