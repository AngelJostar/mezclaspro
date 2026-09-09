const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

const privacyUrl = process.env.PRIVACY_TEST_URL || 'http://localhost/mezclaspro/public/aviso-de-privacidad';
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

test('privacy layout and assets render without horizontal overflow at desktop, tablet and mobile sizes', async () => {
    for (const [width, height] of [[1666, 944], [1440, 900], [1366, 768], [1024, 768], [768, 1024], [390, 844], [320, 740], [2560, 1080]]) {
        const page = await browser.newPage({ viewport: { width, height } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        try {
            assert.equal((await page.goto(privacyUrl, { waitUntil: 'networkidle' })).status(), 200);
            await page.evaluate(() => document.fonts.ready);
            assert.equal(await page.title(), 'Aviso de privacidad | PROMESA');
            assert.equal(await page.locator('img').evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0)), true);
            assert.equal(await page.evaluate(() => document.fonts.check('700 40px Figtree')), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `Page overflows at ${width}`);
            assert.equal(await page.locator('.privacy-document section').count(), 10);
            assert.equal(await page.locator('.privacy-document h2 svg').count(), 10);
            const header = await page.locator('.privacy-header').boundingBox();
            const intro = await page.locator('.privacy-intro').boundingBox();
            const sidebar = await page.locator('.privacy-sidebar').boundingBox();
            const article = await page.locator('.privacy-document').boundingBox();
            assert.ok(header.y + header.height <= intro.y + 1);
            assert.ok(intro.y + intro.height <= sidebar.y);
            const titleCopy = await page.locator('.privacy-title-copy').boundingBox();
            const updated = await page.locator('.privacy-updated').boundingBox();
            if (width <= 1199) assert.ok(titleCopy.y + titleCopy.height <= updated.y, `Update date overlaps the title at ${width}`);
            else assert.ok(titleCopy.x + titleCopy.width <= updated.x, `Update date overlaps the title at ${width}`);
            assert.equal(await page.locator('.privacy-title-copy').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true, `Title text overflows at ${width}`);
            if (width >= 900) assert.ok(sidebar.x + sidebar.width < article.x);
            else assert.ok(sidebar.y + sidebar.height < article.y);
            for (const selector of ['.privacy-title-copy', '.privacy-updated', '.privacy-document', '.privacy-footer-inner', '.welcome-support']) {
                const rect = await page.locator(selector).boundingBox();
                assert.ok(rect.x >= 0 && rect.x + rect.width <= width + 1, `${selector} overflows at ${width}`);
            }
            assert.equal(await page.locator('.privacy-section-content').evaluateAll(elements => elements.every(el => el.scrollWidth <= el.clientWidth + 1)), true);
            if (process.env.PRIVACY_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.PRIVACY_SCREENSHOT_DIR, `promesa-privacy-${width}.png`) });
            }
            await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
            const contact = await page.locator('#contacto').boundingBox();
            const footer = await page.locator('.privacy-footer').boundingBox();
            assert.ok(contact.y + contact.height <= footer.y + 1, `Footer hides contact details at ${width}`);
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('contents, keyboard navigation, active section and footer links work', async () => {
    const page = await browser.newPage({ viewport: { width: 1666, height: 944 } });
    try {
        await page.goto(privacyUrl);
        const index = page.getByRole('navigation', { name: 'Contenido del aviso' });
        assert.equal(await index.locator('[aria-current="location"]').getAttribute('href'), '#responsable');
        await index.getByRole('link', { name: 'Derechos ARCO' }).focus();
        await page.keyboard.press('Enter');
        await page.waitForURL('**/aviso-de-privacidad#derechos-arco');
        await page.waitForFunction(() => document.querySelector('.privacy-index a[aria-current="location"]')?.hash === '#derechos-arco');
        const target = await page.locator('#derechos-arco').boundingBox();
        assert.ok(target.y >= 0 && target.y < 120);
        await page.getByRole('navigation', { name: 'Enlaces del pie' }).getByRole('link', { name: 'Contacto', exact: true }).click();
        await page.waitForURL('**/aviso-de-privacidad#contacto');
        assert.equal(await page.locator('#contacto a[href^="mailto:"]').getAttribute('href'), 'mailto:contacto@prodifem.com.mx');
        assert.equal(await page.locator('#contacto a[href^="tel:"]').getAttribute('href'), 'tel:+525591862620');
        await page.waitForFunction(() => document.querySelector('.privacy-index a[aria-current="location"]')?.hash === '#contacto');
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.getByRole('navigation', { name: 'Ruta de navegación' }).getByRole('link', { name: 'Inicio', exact: true }).click();
        await page.waitForURL(privacyUrl.replace('aviso-de-privacidad', ''));
    } finally { await page.close(); }
});

test('mobile contents expand and collapse with keyboard and preserve anchor navigation', async () => {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    try {
        await page.goto(privacyUrl);
        assert.equal(await page.locator('.privacy-index').evaluate(el => el.open), false);
        const summary = page.locator('.privacy-index summary');
        await summary.focus();
        await summary.press('Enter');
        assert.equal(await page.locator('.privacy-index').evaluate(el => el.open), true);
        await page.locator('.privacy-index a[href="#cookies"]').click();
        await page.waitForURL('**/aviso-de-privacidad#cookies');
        const section = await page.locator('#cookies').boundingBox();
        assert.ok(section.y >= 0 && section.y < 120);
        await page.evaluate(() => window.scrollTo(0, 0));
        await summary.click();
        assert.equal(await page.locator('.privacy-index').evaluate(el => el.open), false);
    } finally { await page.close(); }
});

test('the complete notice and contents remain usable without JavaScript', async () => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();
    try {
        await page.goto(privacyUrl);
        assert.equal(await page.locator('.privacy-section').count(), 10);
        await page.locator('.privacy-index a[href="#datos-personales"]').click();
        await page.waitForURL('**/aviso-de-privacidad#datos-personales');
        assert.equal(await page.getByRole('heading', { name: '2. Datos personales recabados', exact: true }).isVisible(), true);
    } finally { await context.close(); }
});
