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

test('Mensajeria opens the conversation filter and preserves category links on desktop and mobile', async () => {
    const pages = Object.fromEntries(['todas', 'mensajeria'].map(state => [state, execFileSync('php',
        ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/solicitud-messaging-filter.php', state],
        { cwd: root, encoding: 'utf8' })]));
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            page.setDefaultTimeout(10000);
            await page.route('**/*', route => {
                const state = new URL(route.request().url()).searchParams.get('estado') || 'todas';
                return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><style>${styles}</style></head><body><main class="admin-page"><div class="admin-content">${pages[state]}</div></main></body></html>` });
            });
            await page.goto('http://localhost/admin/solicitudes');
            const nav = page.getByRole('navigation', { name: 'Estado de las solicitudes' });
            const labels = (await nav.getByRole('link').allTextContents()).map(label => label.trim());
            assert.match(labels[1], /^Pendientes/);
            assert.equal(labels[2], 'Mensajería');
            assert.match(labels[3], /^En Ajuste/);
            await nav.getByRole('link', { name: 'Mensajería', exact: true }).click();
            await page.waitForURL('**/*estado=mensajeria');
            assert.equal(await nav.getByRole('link', { name: 'Mensajería', exact: true }).getAttribute('aria-current'), 'page');
            assert.deepEqual((await page.locator('[data-mixture-message-key]').evaluateAll(cells => cells.map(cell => cell.dataset.mixtureMessageKey))).sort(),
                ['antibioticos:2', 'nutricionales:11', 'oncologicos:1']);
            const categories = page.getByRole('navigation', { name: 'Tipo de solicitudes' }).getByRole('link');
            assert.equal(await categories.count(), 4);
            for (const category of await categories.all()) assert.match(await category.getAttribute('href'), /estado=mensajeria/);
            const boxes = await nav.getByRole('link').evaluateAll(links => links.map(link => {
                const box = link.getBoundingClientRect();
                return { x: box.x, y: box.y, right: box.right, bottom: box.bottom, fits: link.scrollWidth <= link.clientWidth };
            }));
            for (let i = 0; i < boxes.length; i++) {
                assert.ok(boxes[i].x >= 0 && boxes[i].right <= width && boxes[i].fits);
                for (let j = i + 1; j < boxes.length; j++) assert.ok(boxes[i].right <= boxes[j].x || boxes[j].right <= boxes[i].x || boxes[i].bottom <= boxes[j].y || boxes[j].bottom <= boxes[i].y);
            }
            if (process.env.MESSAGE_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.MESSAGE_SCREENSHOTS, `messaging-filter-${width}.png`) });
            await page.close();
        }
    } finally { await browser.close(); }
});
