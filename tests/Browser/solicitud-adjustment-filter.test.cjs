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
const fixture = state => execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
    'tests/Browser/fixtures/solicitud-adjustment-filter.php', state], { cwd: root, encoding: 'utf8' });

test('En Ajuste filters real queries and wraps cleanly on desktop and mobile', async () => {
    const documents = Object.fromEntries(['todas', 'pendientes', 'en_ajuste', 'historial'].map(state => [state,
        `<!doctype html><html><head><meta charset="utf-8"><style>${styles}</style></head><body><main class="admin-page"><div class="admin-content">${fixture(state)}</div></main></body></html>`]));
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            await page.route('**/*', route => {
                const state = new URL(route.request().url()).searchParams.get('estado') || 'todas';
                return route.fulfill({ contentType: 'text/html', body: documents[state] });
            });
            await page.goto('http://localhost/admin/solicitudes');
            const nav = page.getByRole('navigation', { name: 'Estado de las solicitudes' });
            const adjustmentLink = nav.getByRole('link', { name: /^En Ajuste\s+12$/ });
            const badge = adjustmentLink.locator('span');
            assert.equal((await badge.innerText()).trim(), '12');
            const badgeStyle = element => ({ background: getComputedStyle(element).backgroundColor,
                radius: getComputedStyle(element).borderRadius, height: getComputedStyle(element).height });
            assert.deepEqual(await badge.evaluate(badgeStyle), await nav.getByRole('link', { name: /^Pendientes/ }).locator('span').evaluate(badgeStyle));
            for (const state of ['pendientes', 'historial']) {
                await page.goto(`http://localhost/admin/solicitudes?estado=${state}`);
                assert.equal((await badge.innerText()).trim(), '12');
            }
            await adjustmentLink.click();
            await page.waitForURL('**/*estado=en_ajuste');
            assert.equal(await adjustmentLink.getAttribute('aria-current'), 'page');
            assert.equal((await badge.innerText()).trim(), '12');
            assert.equal(await page.locator('tbody tr').count(), 15);
            for (const text of await page.locator('tbody tr').allTextContents()) {
                assert.doesNotMatch(text, /already_approved|request_cancelled|hospital 2|Aprobada con ajuste/i);
            }
            const boxes = await nav.getByRole('link').evaluateAll(links => links.map(link => {
                const box = link.getBoundingClientRect();
                return { x: box.x, y: box.y, right: box.right, bottom: box.bottom, fits: link.scrollWidth <= link.clientWidth };
            }));
            assert.equal(boxes.length, 8);
            for (let i = 0; i < boxes.length; i++) {
                assert.ok(boxes[i].x >= 0 && boxes[i].right <= width && boxes[i].fits);
                for (let j = i + 1; j < boxes.length; j++) {
                    assert.ok(boxes[i].right <= boxes[j].x || boxes[j].right <= boxes[i].x ||
                        boxes[i].bottom <= boxes[j].y || boxes[j].bottom <= boxes[i].y);
                }
            }
            if (process.env.ADJUSTMENT_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.ADJUSTMENT_SCREENSHOTS, `adjustment-filter-${width}.png`) });
            }
            await page.close();
        }
    } finally { await browser.close(); }
});
