const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

const loginUrl = process.env.LOGIN_TEST_URL || 'http://localhost/mezclaspro/public/login';
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

test('login matches the branded layout without overlap across desktop, tablet and mobile sizes', async () => {
    for (const [width, height] of [[1774, 887], [1440, 900], [1366, 768], [1100, 800], [768, 1024], [390, 844], [320, 740], [2560, 1080]]) {
        const page = await browser.newPage({ viewport: { width, height } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        try {
            assert.equal((await page.goto(loginUrl, { waitUntil: 'networkidle' })).status(), 200);
            await page.evaluate(() => document.fonts.ready);
            assert.equal(await page.getByRole('heading', { name: 'Acceso al portal', exact: true }).count(), 1);
            assert.equal(await page.locator('img').evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0)), true);
            assert.equal(await page.evaluate(() => document.fonts.check('700 48px Figtree')), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const box = async selector => page.locator(selector).boundingBox();
            const panel = await box('.login-panel');
            const header = await box('.welcome-header');
            const footer = await box('.welcome-footer');
            const benefits = await box('.welcome-benefits');
            const photo = await box('.login-visual');
            assert.ok(header.y + header.height <= panel.y);
            assert.ok(panel.y + panel.height <= benefits.y);
            assert.ok(benefits.y + benefits.height <= footer.y);
            if (width > 1100) assert.ok(panel.x + panel.width < photo.x);
            else assert.ok(benefits.y + benefits.height <= photo.y);
            for (const selector of ['.login-panel', '.login-field input', '.login-submit', '.login-assurance', '.welcome-benefits', '.welcome-support']) {
                for (const element of await page.locator(selector).all()) {
                    const rect = await element.boundingBox();
                    assert.ok(rect.x >= 0 && rect.x + rect.width <= width + 1, `${selector} overflows at ${width}`);
                }
            }
            const toggle = await box('.login-password-toggle');
            const password = await box('#password');
            assert.ok(toggle.x >= password.x && toggle.x + toggle.width <= password.x + password.width);
            assert.deepEqual(errors, []);
            if (process.env.LOGIN_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.LOGIN_SCREENSHOT_DIR, `promesa-login-${width}.png`), fullPage: true });
            }
        } finally { await page.close(); }
    }
});

test('password toggle is accessible, keeps the value and never submits the form', async () => {
    const page = await browser.newPage();
    try {
        await page.goto(loginUrl);
        const password = page.getByLabel('Contraseña', { exact: true });
        await password.fill('clave-de-prueba');
        await page.getByRole('button', { name: 'Mostrar contraseña', exact: true }).click();
        assert.equal(await password.getAttribute('type'), 'text');
        assert.equal(await password.inputValue(), 'clave-de-prueba');
        assert.equal(await page.locator('[data-password-show]').isVisible(), false);
        assert.equal(await page.locator('[data-password-hide]').isVisible(), true);
        const toggle = page.getByRole('button', { name: 'Ocultar contraseña', exact: true });
        assert.equal(await toggle.getAttribute('aria-pressed'), 'true');
        await toggle.press('Enter');
        assert.equal(await password.getAttribute('type'), 'password');
        assert.equal(await page.locator('[data-password-show]').isVisible(), true);
        assert.equal(await page.locator('[data-password-hide]').isVisible(), false);
        assert.equal(page.url(), loginUrl);
    } finally { await page.close(); }
});

test('form submits the existing username, password and CSRF fields to the existing login endpoint', async () => {
    const page = await browser.newPage();
    try {
        await page.goto(loginUrl);
        await page.getByRole('button', { name: 'Iniciar sesión', exact: true }).click();
        assert.equal(await page.locator('#username').evaluate(input => input.validity.valueMissing), true);
        await page.getByLabel('Usuario', { exact: true }).fill('usuario.de.prueba');
        await page.getByLabel('Contraseña', { exact: true }).fill('clave-de-prueba');
        const csrf = await page.locator('input[name="_token"]').inputValue();
        // Intercept the POST: never attempt authentication or change data in the live system.
        await page.route('**/login', route => route.request().method() === 'POST'
            ? route.fulfill({ contentType: 'text/html', body: '<p>Solicitud capturada</p>' }) : route.continue());
        const submitted = page.waitForRequest(request => request.method() === 'POST');
        await page.getByRole('button', { name: 'Iniciar sesión', exact: true }).click();
        const request = await submitted;
        assert.equal(request.url(), loginUrl);
        const data = new URLSearchParams(request.postData());
        assert.equal(data.get('username'), 'usuario.de.prueba');
        assert.equal(data.get('password'), 'clave-de-prueba');
        assert.equal(data.get('_token'), csrf);
    } finally { await page.close(); }
});
