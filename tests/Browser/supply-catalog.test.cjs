const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const filters = buildSync({ entryPoints: [path.join(root, 'resources/js/table-column-filters.js')], bundle: true, write: false }).outputFiles[0].text;
const catalogUrl = 'http://catalog.test/admin/catalogo-listas/insumos/catalogo';
const bodies = Object.fromEntries(['diluyentes', 'consumibles'].map(section => [section,
    execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/supply-catalog.php', section], { cwd: root, encoding: 'utf8' }),
]));
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openCatalog(width = 1440) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.pathname === '/font.woff2') {
            await route.fulfill({ contentType: 'font/woff2', body: readFileSync(path.join(root, 'public/fonts/figtree/figtree-latin-400-normal.woff2')) });
        } else if (url.pathname === '/admin/catalogo-listas/insumos/catalogo') {
            const section = url.searchParams.get('tipo_insumo') === 'consumibles' ? 'consumibles' : 'diluyentes';
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}
                @font-face{font-family:Figtree;src:url('/font.woff2')}body{font-family:Figtree,sans-serif;padding:16px}
                </style></head><body>${bodies[section]}<script>${filters}</script></body></html>` });
        } else { await route.abort(); }
    });
    await page.goto(catalogUrl);
    return { page, errors };
}

test('supply switch changes catalogs, search labels and creation targets; entry always resets to diluents', async () => {
    const { page, errors } = await openCatalog();
    const active = page.locator('.supply-catalog-switch [aria-current="page"]');
    try {
        assert.equal((await active.innerText()).trim(), 'Diluyentes');
        assert.equal(await page.getByRole('link', { name: 'Catalogo', exact: true }).count(), 0);
        assert.equal(await page.locator('.catalog-row').count(), 2);
        assert.match(await page.getByRole('link', { name: 'Nuevo diluyente', exact: true }).getAttribute('href'), /insumos\/productos\/nuevo$/);
        await page.getByRole('link', { name: 'Consumibles', exact: true }).click();
        assert.equal((await active.innerText()).trim(), 'Consumibles');
        assert.equal(await page.getByRole('searchbox', { name: 'Buscar consumible', exact: true }).count(), 1);
        assert.equal(await page.locator('.catalog-row').count(), 3);
        assert.equal(await page.locator('#catalogTable').getByText('CLORURO DE SODIO 0.9%').count(), 0);
        assert.match(await page.getByRole('link', { name: 'Nuevo consumible', exact: true }).getAttribute('href'), /consumibles\/crear$/);
        assert.equal(await page.getByRole('link', { name: 'Catalogo', exact: true }).count(), 0);
        await page.getByRole('link', { name: 'Diluyentes', exact: true }).focus();
        await page.keyboard.press('Enter');
        assert.equal((await active.innerText()).trim(), 'Diluyentes');
        await page.goBack();
        assert.equal((await active.innerText()).trim(), 'Consumibles');
        await page.locator('.category-carousel a').filter({ hasText: 'Insumos' }).click();
        assert.equal((await active.innerText()).trim(), 'Diluyentes');
        assert.equal(new URL(page.url()).search, '');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('search and column filters work in both catalogs, including zero results', async () => {
    const { page, errors } = await openCatalog();
    try {
        const search = page.locator('#catalogSearch');
        const visibleRows = page.locator('.catalog-row:visible');
        await search.fill('cloruro');
        assert.equal(await visibleRows.count(), 1);
        await search.fill('no-existe');
        assert.equal(await visibleRows.count(), 0);
        assert.equal(await page.getByRole('status').isVisible(), true);
        await page.getByRole('link', { name: 'Consumibles', exact: true }).click();
        assert.equal(await search.inputValue(), '');
        await search.fill('jeringa 20');
        assert.equal(await visibleRows.count(), 1);
        assert.equal(await page.getByRole('status').isVisible(), false);
        await search.fill('');
        await page.locator('.js-catalog-column-filter[data-column="0"]').click();
        const panel = page.locator('#catalog-column-filter-panel');
        await panel.getByLabel('Guante', { exact: true }).uncheck();
        await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
        assert.equal(await visibleRows.count(), 2);
        await search.fill('20');
        assert.equal(await visibleRows.count(), 1);
        await search.fill('guante');
        assert.equal(await visibleRows.count(), 0);
        assert.equal(await page.getByRole('status').isVisible(), true);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('switch and toolbar fit desktop, tablet and phone widths; table columns remain reachable', async () => {
    for (const width of [1774, 1366, 1024, 768, 390, 320]) {
        const { page, errors } = await openCatalog(width);
        try {
            for (const section of ['diluyentes', 'consumibles']) {
                await page.goto(`${catalogUrl}?tipo_insumo=${section}`);
                await page.evaluate(() => document.fonts.ready);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `Page overflow at ${width}`);
                for (const selector of ['.supply-catalog-switch', '.supply-catalog-switch a', '#catalogSearch', '.supply-catalog-actions a']) {
                    for (const element of await page.locator(selector).all()) {
                        const box = await element.boundingBox();
                        assert.ok(box.x >= 0 && box.x + box.width <= width + 1, `${selector} at ${width}`);
                        assert.equal(await element.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true, `Clipped ${selector} at ${width}`);
                    }
                }
                const toggle = await page.locator('.supply-catalog-switch').boundingBox();
                const actions = await page.locator('.supply-catalog-actions').boundingBox();
                assert.ok(toggle.y + toggle.height <= actions.y + 1 || toggle.x + toggle.width <= actions.x, `Toolbar overlap at ${width}`);
                const scroll = page.locator('#catalogTable').locator('..');
                await scroll.evaluate(el => { el.scrollLeft = el.scrollWidth; });
                const last = await page.locator('#catalogTable th').last().boundingBox();
                const bounds = await scroll.boundingBox();
                assert.ok(last.x + last.width <= bounds.x + bounds.width + 1);
                await scroll.evaluate(el => { el.scrollLeft = 0; });
                if (process.env.SUPPLY_SCREENSHOT_DIR) {
                    await page.screenshot({ path: path.join(process.env.SUPPLY_SCREENSHOT_DIR, `supply-catalog-${section}-${width}.png`), fullPage: true });
                }
            }
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});
