const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');

test('hospital credentials remain visible while table filtering and horizontal scrolling work', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const role of ['super', 'manager']) {
            const fixture = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/hospital-credentials.php', role], { cwd: root, encoding: 'utf8' }));
            for (const width of [1366, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 850 } });
                page.setDefaultTimeout(15000);
                const errors = [];
                const failedRequests = [];
                page.on('pageerror', error => errors.push(error.message));
                page.on('requestfailed', request => failedRequests.push(`${request.url()}: ${request.failure()?.errorText}`));
                await page.route('**/*', route => {
                    const url = new URL(route.request().url());
                    if (url.origin === 'http://hospitals.test') {
                        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head>
                            <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
                            <style>${styles}</style>
                            <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css">
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                            <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
                            </head><body>${fixture.html}</body></html>` });
                    }
                    if (['cdn.datatables.net', 'cdnjs.cloudflare.com'].includes(url.hostname)) return route.continue();
                    return route.abort();
                });
                await page.goto('http://hospitals.test/list');
                try {
                    await page.waitForFunction(() => window.DataTable?.isDataTable('#hospitalsTable'));
                } catch (error) {
                    throw new Error(`${error.message}\n${[...errors, ...failedRequests].join('\n')}`);
                }
                const headers = page.locator('#hospitalsTable thead th');
                assert.equal(await headers.count(), role === 'super' ? 8 : 7);
                assert.equal(await page.locator('.js-hospital-column-filter').count(), 5);
                const search = page.locator('.dt-search input');
                await search.fill('Hospital Uno');
                await page.waitForFunction(() => document.querySelectorAll('#hospitalsTable tbody tr').length === 1);
                if (role === 'super') {
                    const credentials = page.locator('.hospital-credentials');
                    assert.match(await credentials.innerText(), /hospital\.uno/);
                    assert.match(await credentials.innerText(), /Clave-Prueba-001/);
                    assert.match(await credentials.innerText(), /hospital\.turno/);
                    assert.equal(await credentials.locator('button, input').count(), 0);
                    await credentials.scrollIntoViewIfNeeded();
                    assert.equal(await credentials.evaluate(el => el.scrollHeight > el.clientHeight + 1), false);
                    assert.equal(await credentials.locator('dd').evaluateAll(nodes => nodes.some(el => el.scrollWidth > el.clientWidth + 1)), false);
                    if (process.env.HOSPITAL_SCREENSHOTS) {
                        await page.screenshot({ path: path.join(process.env.HOSPITAL_SCREENSHOTS, `hospital-credentials-${width}.png`) });
                    }
                    await search.fill('Clave-Prueba-001');
                    await page.getByText('Nada encontrado', { exact: true }).waitFor();
                } else {
                    assert.equal(await page.locator('.hospital-credentials').count(), 0);
                    assert.equal((await page.content()).includes('Clave-Prueba-001'), false);
                }
                await search.fill('');
                await page.waitForFunction(() => document.querySelectorAll('#hospitalsTable tbody tr').length === 3);
                await page.getByRole('button', { name: 'Filtrar Nombre', exact: true }).click();
                await page.locator('[data-filter-search]').fill('Uno');
                assert.equal(await page.locator('.js-filter-option:visible').count(), 1);
                await page.locator('[data-filter-cancel]').click();
                assert.equal(await page.locator('#hospitalsTable tbody tr').first().getByText('Editar', { exact: true }).count(), 1);
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
