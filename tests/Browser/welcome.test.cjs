const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

const baseUrl = process.env.WELCOME_TEST_URL || 'http://localhost/mezclaspro/public/';
let browser;
before(async () => {
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

// Read-only checks on the public home page. No login or application data is changed.
test('home page renders at reference, desktop, tablet and mobile sizes', async () => {
    for (const [width, height] of [[1821, 864], [1440, 900], [1366, 768], [1024, 768], [768, 1024], [390, 844], [320, 740], [2560, 1080]]) {
        const page = await browser.newPage({ viewport: { width, height } });
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        try {
            const response = await page.goto(baseUrl, { waitUntil: 'networkidle' });
            assert.equal(response.status(), 200);
            await page.evaluate(() => document.fonts.ready);
            assert.equal(await page.evaluate(() => document.fonts.check('700 64px Figtree') && document.fonts.check('400 23px Figtree')), true);
            assert.equal(await page.getByRole('link', { name: 'Acceder al sistema', exact: true }).isVisible(), true);
            assert.equal(await page.locator('svg[data-welcome-icon]').count(), 8);
            assert.equal(await page.locator('img').evaluateAll((images) => images.every((image) => image.complete && image.naturalWidth > 0)), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const layout = await page.evaluate(() => {
                const box = (selector) => {
                    const r = document.querySelector(selector).getBoundingClientRect();
                    return { left: r.left, top: r.top, right: r.right, bottom: r.bottom };
                };
                return { header: box('.welcome-header'), copy: box('.welcome-copy'), footer: box('.welcome-footer'), visual: box('.welcome-visual') };
            });
            assert.ok(layout.header.bottom <= layout.copy.top + 1);
            assert.ok(layout.copy.bottom <= layout.footer.top + 1);
            if (width < 1024) assert.ok(layout.copy.bottom <= layout.visual.top + 1);
            for (const selector of ['.welcome-support', '.welcome-access', '.welcome-benefits', '.welcome-connection']) {
                const box = await page.locator(selector).boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width + 1, `${selector} exceeds width ${width}`);
            }
            assert.deepEqual(errors, []);
            if (process.env.WELCOME_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.WELCOME_SCREENSHOT_DIR, `promesa-home-${width}.png`), fullPage: true });
            }
        } finally { await page.close(); }
    }
});

test('public access and privacy links navigate normally in the same tab', async () => {
    const page = await browser.newPage();
    try {
        await page.goto(baseUrl);
        await page.getByRole('link', { name: 'Acceder al sistema', exact: true }).click();
        await page.waitForURL('**/login');
        assert.equal(browser.contexts().length, 1);
        await page.goto(baseUrl);
        await page.getByRole('link', { name: 'Aviso de privacidad', exact: true }).click();
        await page.waitForURL('**/aviso-de-privacidad');
        assert.equal((await page.request.get(page.url())).status(), 200);
    } finally { await page.close(); }
});
