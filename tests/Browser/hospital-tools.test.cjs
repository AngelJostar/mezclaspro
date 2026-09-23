const { test, before, after } = require('node:test');
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
const script = buildSync({ stdin: { contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js';
    import './resources/js/request-navigation.js'; import './resources/js/administration-carousel.js'; Alpine.start();`, resolveDir: root },
    bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const sections = { reportes: 'Reportes', conciliacion: 'Conciliación', facturacion: 'Facturación', pagos: 'Pagos' };
const bodies = Object.fromEntries(Object.keys(sections).map(section => [section,
    execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/hospital-tools.php', section], { cwd: root, encoding: 'utf8' }),
]));
const baseUrl = 'http://hospital-tools.test/mezclaspro/public/admin/herramientas';
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openTools(width) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.pathname.endsWith('/img/promesa-logo.png')) {
            await route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
        } else if (url.pathname.endsWith('/admin/herramientas')) {
            const body = bodies[url.searchParams.get('seccion')] || bodies.reportes;
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}body{font-family:Arial,sans-serif;background:#f1f5f9}</style></head><body><div x-data="{open:false}">${body}</div><script>${script}</script></body></html>` });
        } else { await route.abort(); }
    });
    await page.goto(baseUrl);
    return { page, errors };
}

test('four hospital sections navigate, retain selection on reload, and support browser Back', async () => {
    const { page, errors } = await openTools(1840);
    try {
        const nav = page.getByRole('navigation', { name: 'Secciones de herramientas' });
        assert.deepEqual((await nav.getByRole('link').allTextContents()).map(text => text.trim()), Object.values(sections));
        for (const [key, label] of Object.entries(sections)) {
            await nav.getByRole('link', { name: label, exact: true }).click();
            assert.equal(page.url(), `${baseUrl}?seccion=${key}`);
            await page.reload();
            const active = nav.locator('[aria-current="page"]');
            assert.equal(await active.count(), 1);
            assert.equal((await active.innerText()).trim(), label);
            assert.equal(await active.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(47, 67, 130)');
            assert.equal(await page.locator('#logo-sidebar').getByRole('link', { name: 'Herramientas', exact: true }).getAttribute('aria-current'), 'page');
        }
        await page.goBack();
        assert.equal((await nav.locator('[aria-current="page"]').innerText()).trim(), 'Facturación');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('hospital toolbar fits desktop and mobile; arrows reveal tabs and selected section stays visible', async () => {
    for (const width of [1840, 768, 390, 320]) {
        const { page, errors } = await openTools(width);
        try {
            const nav = page.getByRole('navigation', { name: 'Secciones de herramientas' });
            await nav.locator('svg').first().waitFor();
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const scroll = nav.locator('#administration-carousel');
            if (await scroll.evaluate(el => el.scrollWidth > el.clientWidth)) {
                await nav.getByRole('button', { name: 'Sección siguiente', exact: true }).click();
                await page.waitForFunction(() => document.querySelector('#administration-carousel').scrollLeft > 0);
                await nav.getByRole('button', { name: 'Sección anterior', exact: true }).click();
                await page.waitForFunction(() => document.querySelector('#administration-carousel').scrollLeft === 0);
            }
            for (const link of await nav.getByRole('link').all()) {
                assert.equal(await link.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            }
            if (process.env.HOSPITAL_TOOLS_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.HOSPITAL_TOOLS_SCREENSHOTS, `hospital-tools-${width}.png`) });
            await nav.getByRole('link', { name: 'Pagos', exact: true }).click();
            await page.waitForFunction(() => {
                const container = document.querySelector('#administration-carousel').getBoundingClientRect();
                const selected = document.querySelector('#administration-carousel [aria-current]').getBoundingClientRect();
                return selected.left >= container.left - 1 && selected.right <= container.right + 1;
            });
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});
