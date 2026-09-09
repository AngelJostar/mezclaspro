const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = readFileSync(path.join(root, 'public/build', manifest['resources/css/app.css'].file), 'utf8');
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

test('inventory shows mg for oncology and antibiotics, updating quantities and waste target on lot changes', async () => {
    for (const category of ['oncologicos', 'antibioticos']) {
        for (const width of [1550, 390]) {
            const body = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/remainder-inventory.php', category], { cwd: root, encoding: 'utf8' });
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.request().url() === 'http://inventory.test/'
                ? route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}body{padding:20px}</style></head><body><script>window.testAlerts=[];window.Swal={fire(options){window.testAlerts.push(options);return Promise.resolve({isConfirmed:false});}};</script>${body}</body></html>` })
                : route.abort());
            try {
                await page.goto('http://inventory.test/');
                const row = page.locator('.presentation-row').first();
                const batch = category === 'oncologicos' ? 100 : 200;
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), category === 'oncologicos' ? '62.50 mg' : '625.00 mg');
                await row.locator('.lote-select').selectOption(String(batch + 1));
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), category === 'oncologicos' ? '3.09 mg' : '30.85 mg');
                assert.equal(await row.locator('.remainder-waste-button').isEnabled(), true);
                assert.match(await row.locator('.remainder-waste-form').getAttribute('action'), new RegExp(`/${batch + 1}/`));
                await row.locator('.remainder-waste-button').click();
                assert.match(await page.evaluate(() => window.testAlerts.at(-1).text), /0 mg/);
                await row.locator('.lote-select').selectOption(String(batch + 2));
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), '0.00 mg');
                assert.equal(await row.locator('.remainder-waste-button').isDisabled(), true);
                assert.equal((await page.locator('.presentation-row').nth(1).locator('.selected-remainder').innerText()).trim(), 'Sin concentracion');
                assert.match(await page.locator('.presentation-row').nth(2).innerText(), /0\.00 mg/);
                assert.deepEqual(errors, []);
                await row.locator('.lote-select').selectOption(String(batch));
                if (process.env.DILUENT_SCREENSHOT_DIR) {
                    await page.screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `inventory-remainder-${category}-${width}.png`) });
                }
            } finally { await page.close(); }
        }
    }
});
