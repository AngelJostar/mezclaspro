const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync, mkdirSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

function render(type, grants) {
    return execFileSync('php', ['tests/Browser/fixtures/training.php', type, '[]', '[]', JSON.stringify(grants)],
        { cwd: root, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
}
async function fixture(grants = null, width = 1366) {
    const html = render('programas', grants);
    let catalog = JSON.parse(render('continuous', grants)), failure = false, requests = 0;
    const page = await browser.newPage({ viewport: { width, height: 950 } });
    const errors = [], writes = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const req = route.request(), url = new URL(req.url());
        if (url.hostname !== 'training.test') return route.abort();
        if (req.method() !== 'GET') { writes.push(req.url()); return route.fulfill({ status: 403, json: {} }); }
        if (url.pathname === '/admin/capacitaciones/continua') {
            requests++;
            return route.fulfill({ status: failure ? 503 : 200, json: failure ? { message: 'Temporal' } : catalog });
        }
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="test"><style>' + css + '</style>' + html });
    });
    await page.goto('http://training.test/admin/capacitaciones/programas');
    await page.locator('[data-role-button="learner"]').click();
    return { page, errors, writes, catalog, requests: () => requests, fail: value => { failure = value; }, setCatalog: value => { catalog = value; } };
}
async function openLibrary(page) {
    await page.getByRole('tab', { name: 'Capacitación Continua', exact: true }).click();
    await page.waitForFunction(() => document.querySelector('[data-continuous-library]').getAttribute('aria-busy') === 'false');
}

test('free chapter consultation leaves mandatory progress, tasks, exams and locks untouched', async () => {
    const f = await fixture(); const { page } = f;
    try {
        await page.getByRole('tab', { name: 'Mi capacitacion', exact: true }).click();
        const before = await page.locator('[data-learner-panel="training"]').innerText();
        const storage = await page.evaluate(() => JSON.stringify(localStorage));
        await openLibrary(page);
        assert.equal(await page.locator('.training-obligation-flow').isVisible(), false);
        assert.equal(await page.locator('[data-continuous-program] option').count(), 2);
        await page.locator('[data-continuous-program]').selectOption('induccion');
        await page.locator('[data-continuous-modules] button').last().click();
        const chapter = page.locator('[data-continuous-chapter]').last();
        await chapter.locator('summary').click();
        assert.equal(await chapter.getAttribute('open'), '');
        assert.equal(await page.locator('[data-continuous-chapter]').first().getAttribute('open'), null);
        assert.match(await chapter.innerText(), /Ver video en otra pestaña/);
        assert.match(await chapter.locator('a').getAttribute('href'), /^https:\/\//);
        assert.equal(await chapter.locator('a').getAttribute('rel'), 'noopener noreferrer');
        await page.getByRole('tab', { name: 'Mi capacitacion', exact: true }).click();
        assert.equal(await page.locator('.training-obligation-flow').isVisible(), true);
        assert.equal(await page.locator('[data-learner-panel="training"]').innerText(), before);
        assert.equal(await page.evaluate(() => JSON.stringify(localStorage)), storage);
        assert.deepEqual(f.writes, []);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('an employee without mandatory induction can consult only their enabled module on mobile', async () => {
    const f = await fixture({ proveedores: ['module-3'] }, 390); const { page } = f;
    try {
        const home = page.getByRole('tab', { name: 'Inicio', exact: true });
        await home.focus();
        await home.press('ArrowRight');
        await page.locator('[data-continuous-content]:visible').waitFor();
        assert.equal(await page.getByRole('tab', { name: 'Capacitación Continua', exact: true }).getAttribute('aria-selected'), 'true');
        assert.equal(await page.locator('[data-continuous-program] option').count(), 1);
        assert.equal(await page.locator('[data-continuous-modules] button').count(), 1);
        assert.match(await page.locator('[data-continuous-modules]').innerText(), /Validacion final/);
        await page.locator('[data-continuous-chapter] summary').last().click();
        assert.equal(await page.locator('[data-continuous-chapter]').last().getAttribute('open'), '');
        const box = await page.locator('[data-continuous-reader]').boundingBox();
        assert.ok(box.width < 390, 'reader fits the mobile viewport');
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-continua-mobile.png'), fullPage: true });
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('catalog updates preserve selection, hide revoked content, and offer a retry after network errors', async () => {
    const f = await fixture({ proveedores: true, induccion: true }); const { page } = f;
    try {
        await openLibrary(page);
        await page.locator('[data-continuous-program]').selectOption('proveedores');
        await page.locator('[data-continuous-modules] button').last().click();
        const program = f.catalog.programs.find(p => p.id === 'proveedores');
        program.title = 'Proveedores actualizados';
        program.modules[2].title = 'Validación actualizada';
        await page.evaluate(() => window.dispatchEvent(new StorageEvent('storage', { key: 'mezclaspro.training.catalog-revision', newValue: 'updated' })));
        await page.getByRole('heading', { name: 'Validación actualizada' }).waitFor();
        assert.equal(await page.locator('[data-continuous-program]').inputValue(), 'proveedores');
        program.modules = program.modules.slice(0, 1);
        await page.locator('[data-continuous-refresh]').click();
        await page.getByRole('heading', { name: program.modules[0].title, exact: true }).waitFor();
        assert.equal(await page.locator('[data-continuous-modules] button').count(), 1);
        f.fail(true);
        await page.locator('[data-continuous-refresh]').click();
        await page.locator('[data-continuous-status].is-error').waitFor();
        assert.equal(await page.locator('[data-continuous-content]').isVisible(), false);
        f.fail(false);
        await page.locator('[data-continuous-refresh]').click();
        await page.locator('[data-continuous-content]:visible').waitFor();
        assert.equal(await page.locator('[data-continuous-program]').inputValue(), 'proveedores');
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-continua.png'), fullPage: true });
        f.setCatalog({ programs: [] });
        await page.locator('[data-continuous-refresh]').click();
        await page.locator('[data-continuous-empty]:visible').waitFor();
        assert.equal(await page.locator('[data-continuous-chapter]').count(), 0);
        assert.deepEqual(f.writes, []);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('no enabled programs produces a clear empty state without sample content', async () => {
    const f = await fixture({});
    try {
        await openLibrary(f.page);
        await f.page.locator('[data-continuous-empty]:visible').waitFor();
        assert.equal(await f.page.locator('[data-continuous-modules] button').count(), 0);
        assert.equal(await f.page.locator('[data-continuous-chapter]').count(), 0);
        assert.deepEqual(f.errors, []);
    } finally { await f.page.close(); }
});
