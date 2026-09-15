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
const navigation = buildSync({
    entryPoints: [path.join(root, 'resources/js/administration-carousel.js')],
    bundle: true, write: false, format: 'iife',
}).outputFiles[0].text;

test('all billing screens keep the administration carousel and usable billing tabs on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const section of ['pending', 'receivable', 'history', 'movements']) {
            const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/billing-navigation.php', section], { cwd: root, encoding: 'utf8' });
            for (const width of [1440, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 900 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${css}</style>${html}<script>${navigation}</script>`);
                const administration = page.getByRole('navigation', { name: 'Secciones de administración', exact: true });
                const billing = page.getByRole('navigation', { name: 'Secciones de facturación', exact: true });
                await administration.locator('svg').first().waitFor();
                assert.equal(await administration.locator('a[aria-current="page"]').innerText(), 'Facturación');
                assert.equal(await billing.locator('a').count(), 4);
                assert.equal(await billing.locator('a[aria-current="page"]').count(), 1);
                for (const tab of await billing.locator('a').all()) {
                    const box = await tab.boundingBox();
                    assert.ok(box.x >= 0 && box.x + box.width <= width + 1);
                    assert.equal(await tab.evaluate(link => link.scrollWidth <= link.clientWidth), true);
                    assert.match(await tab.getAttribute('href'), /instituciones-reportes\/facturacion/);
                }
                const panel = page.locator(`[data-billing-section="${section}"]`);
                assert.equal(await panel.getByRole('navigation', { name: 'Secciones de facturación', exact: true }).count(), 1);
                const panelBox = await panel.boundingBox();
                const billingBox = await billing.boundingBox();
                const administrationBox = await administration.boundingBox();
                assert.ok(panelBox.y >= administrationBox.y + administrationBox.height);
                assert.ok(billingBox.x > panelBox.x && billingBox.y > panelBox.y);
                assert.ok(billingBox.x + billingBox.width < panelBox.x + panelBox.width);
                assert.ok((await panel.locator('form').first().boundingBox()).y >= billingBox.y + billingBox.height);
                if (process.env.BILLING_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.BILLING_SCREENSHOTS, `facturacion-${section}-${width}.png`) });
                }
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
