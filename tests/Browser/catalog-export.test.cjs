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
const sections = ['todos', 'oncologicos', 'nutricionales', 'antibioticos', 'diluyentes', 'consumibles'];
const fixtures = Object.fromEntries(sections.map(section => [section, JSON.parse(execFileSync('php', [
    '-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/catalog-export.php',
    ['diluyentes', 'consumibles'].includes(section) ? 'insumos' : section, section,
], { cwd: root, encoding: 'utf8' }))]));
const urlFor = section => `http://catalog.test/admin/catalogo-listas/${['diluyentes', 'consumibles'].includes(section) ? 'insumos' : section}/catalogo?tipo_insumo=${section}`;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function openPage(width) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        const match = url.pathname.match(/^\/admin\/catalogo-listas\/([^/]+)\/catalogo(\/descargar)?$/);
        if (!match) { await route.abort(); return; }
        const section = match[1] === 'insumos' ? url.searchParams.get('tipo_insumo') || 'diluyentes' : match[1];
        const fixture = fixtures[section];
        if (match[2]) {
            await route.fulfill({ contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                headers: { 'Content-Disposition': fixture.disposition }, body: Buffer.from(fixture.content, 'base64') });
        } else {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}
                body{font-family:Arial,sans-serif;padding:16px}</style></head><body>${fixture.html}<script>${filters}</script></body></html>` });
        }
    });
    return page;
}

test('Descargar downloads the selected catalog without leaving the page, including both supply switch positions', async () => {
    const page = await openPage(1440);
    try {
        for (const section of sections) {
            await page.goto(urlFor(section));
            const url = page.url();
            const downloadEvent = page.waitForEvent('download');
            await page.getByRole('link', { name: 'Descargar', exact: true }).click();
            const download = await downloadEvent;
            assert.match(download.suggestedFilename(), new RegExp(`^catalogo_${section}_\\d{8}_\\d{6}\\.xlsx$`));
            assert.equal(await download.failure(), null);
            assert.deepEqual(readFileSync(await download.path()), Buffer.from(fixtures[section].content, 'base64'));
            assert.equal(page.url(), url);
            await download.delete();
        }
    } finally { await page.close(); }
});

test('download actions stay to the right on desktop and wrap without clipping on small screens', async () => {
    for (const width of [1366, 1024, 768, 390, 320]) {
        const page = await openPage(width);
        try {
            for (const section of sections) {
                await page.goto(urlFor(section));
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `${section} overflows at ${width}`);
                const controls = await page.locator('.catalog-actions > *').all();
                const bounds = [];
                for (const control of controls) {
                    const box = await control.boundingBox();
                    assert.ok(box.x >= 0 && box.x + box.width <= width + 1, `${section} action outside ${width}`);
                    if (await control.evaluate(el => el.tagName === 'A')) {
                        assert.ok(await control.evaluate(el => el.scrollWidth <= el.clientWidth + 1), `${section} text clipped at ${width}`);
                    }
                    bounds.push(box);
                }
                for (let i = 1; i < bounds.length; i++) {
                    const prev = bounds[i - 1], current = bounds[i];
                    assert.ok(current.x >= prev.x + prev.width || current.y >= prev.y + prev.height,
                        `${section} controls overlap at ${width}`);
                }
                if (width >= 1024) {
                    const search = bounds[0], download = bounds.at(-1);
                    assert.ok(download.x > search.x + search.width, `${section} download must be right of search`);
                }
                if (process.env.CATALOG_SCREENSHOT_DIR && [1366, 390].includes(width)) {
                    await page.screenshot({ path: path.join(process.env.CATALOG_SCREENSHOT_DIR, `catalog-export-${section}-${width}.png`), fullPage: true });
                }
            }
        } finally { await page.close(); }
    }
});
