const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const confirmations = buildSync({
    entryPoints: [path.join(root, 'resources/js/request-process-confirmations.js')],
    bundle: true, write: false, format: 'iife',
}).outputFiles[0].text;

// Supply the app's SweetAlert2 CDN script locally to keep the test offline.
test('preparation confirmation returns to the list with a success dialog and OK', async () => {
    assert.ok(process.env.SWEETALERT_SCRIPT_PATH, 'Set SWEETALERT_SCRIPT_PATH to the SweetAlert2 script used by the app.');
    const swal = readFileSync(process.env.SWEETALERT_SCRIPT_PATH, 'utf8');
    const fixture = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/mixture-preparation.php'], { cwd: root, encoding: 'utf8' }));
    assert.equal(fixture.targetUrl, fixture.listUrl);
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1366, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 800 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            let submissions = 0;
            let loads = 0;
            await page.route('**/*', async route => {
                const request = route.request();
                if (request.url() === 'http://preparation.test/mezclaspro/public/admin/oncologicos/mezclas/1') {
                    assert.equal(request.method(), 'POST');
                    const data = new URLSearchParams(request.postData());
                    assert.equal(data.get('_method'), 'PUT');
                    assert.equal(data.get('accion'), 'preparada');
                    assert.equal(data.get('return_to'), fixture.listUrl);
                    submissions++;
                    await route.fulfill({ status: 302, headers: { location: fixture.targetUrl } });
                } else if (request.url() === fixture.listUrl) {
                    loads++;
                    await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html><head><meta charset="utf-8"><style>${styles}</style><script>${swal}</script></head>
                        <body><main class="admin-page"><h1>Lista de Solicitudes</h1>${submissions ? fixture.after : fixture.before}</main><script>${confirmations}</script></body></html>` });
                } else {
                    await route.abort();
                }
            });
            await page.goto(fixture.listUrl);
            await page.getByRole('button', { name: 'Preparar', exact: true }).click();
            await page.getByRole('button', { name: 'No', exact: true }).click();
            assert.equal(submissions, 0);
            await page.getByRole('button', { name: 'Preparar', exact: true }).click();
            await page.getByRole('button', { name: 'Si', exact: true }).click();
            const success = page.getByRole('dialog');
            await success.getByRole('heading', { name: '\u00c9xito', exact: true }).waitFor();
            await page.evaluate(() => Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {}))));
            assert.equal(submissions, 1);
            assert.equal(page.url(), fixture.listUrl);
            assert.deepEqual(await success.getByRole('button').allTextContents(), ['OK']);
            const bounds = await success.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
            assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 800);
            await page.keyboard.press('Escape');
            assert.equal(await success.isVisible(), true);
            if (process.env.INSPECTION_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `mixture-preparation-success-${width}.png`) });
            }
            await success.getByRole('button', { name: 'OK', exact: true }).click();
            await success.waitFor({ state: 'hidden' });
            assert.equal(await page.getByRole('heading', { name: 'Lista de Solicitudes', exact: true }).isVisible(), true);
            assert.equal(await page.getByRole('button', { name: 'Inspeccionar', exact: true }).count(), 1);
            assert.equal(submissions, 1);
            assert.equal(loads, 2);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});
