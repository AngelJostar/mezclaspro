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
const filters = buildSync({ entryPoints: [path.join(root, 'resources/js/table-column-filters.js')], bundle: true, write: false, format: 'iife' }).outputFiles[0].text;
const navigation = buildSync({
    entryPoints: [path.join(root, 'resources/js/request-navigation.js')],
    bundle: true, write: false, format: 'iife',
}).outputFiles[0].text;

test('rejected requests populate validations and remain searchable without enabling production actions', async () => {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/solicitud-validations.php', 'todas', 'rejected'], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<style>${css}</style>${html}<script>${filters}\n${navigation}</script>`);
            const table = page.locator('#solicitud-validations-table');
            const categoryFilter = table.locator('thead th').first().locator('button');
            await categoryFilter.waitFor();
            assert.equal(await table.locator('[data-validation-row]').count(), 6);
            assert.equal(await table.getByRole('link', { name: 'Ver', exact: true }).count(), 6);
            assert.equal(await table.locator('form, button').count(), 17);
            assert.equal(await table.locator('tbody script').count(), 0);
            assert.equal(await table.locator('tbody tr').first().locator('td').count(), 17);
            if (process.env.VALIDATION_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.VALIDATION_SCREENSHOTS, `validaciones-rechazadas-${width}.png`) });
            }
            await categoryFilter.click();
            const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
            await panel.locator('[data-filter-all]').uncheck();
            await panel.locator('[data-filter-search]').fill('Nutri');
            await panel.getByRole('checkbox', { name: 'Nutricional', exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
            assert.equal(await table.locator('[data-validation-row]:visible').count(), 2);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});

test('validations have searchable filters in every column on desktop and mobile', async () => {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/solicitud-validations.php'], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 850 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<style>${css}</style>${html}<script>${filters}\n${navigation}</script>`);
            const triggers = page.locator('#solicitud-validations-table thead button[data-column]');
            await triggers.first().waitFor();
            assert.equal(await triggers.count(), 17);
            assert.equal(await page.getByRole('link', { name: 'Exportar a Excel', exact: true }).isVisible(), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1), false);
            if (process.env.VALIDATION_SCREENSHOTS) {
                await page.screenshot({ path: path.join(process.env.VALIDATION_SCREENSHOTS, `validaciones-${width}.png`) });
            }
            const panel = page.locator('[id^="automatic-table-filter-"][id$="-panel"]');
            for (const trigger of await triggers.all()) {
                await trigger.click();
                assert.equal(await panel.locator('[data-filter-search]').isVisible(), true);
                assert.equal(await panel.locator('[data-filter-options] label').count(), 0);
                await panel.locator('[data-filter-search]').fill('ejemplo');
                assert.equal(await panel.locator('[data-filter-empty]').isVisible(), true);
                const box = await panel.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width + 1);
                await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
                assert.equal(await page.getByText('No hay solicitudes rechazadas para estos filtros.', { exact: true }).isVisible(), true);
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});

test('request category cards match the catalog and keep the selected card visible', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const type of ['todas', 'nutricionales', 'oncologicos', 'antibioticos']) {
            const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/solicitud-validations.php', type], { cwd: root, encoding: 'utf8' });
            for (const width of [1440, 768, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 850 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${css}</style>${html}<script>${navigation}</script>`);
                const carousel = page.getByRole('navigation', { name: 'Tipo de solicitudes', exact: true });
                const selected = carousel.locator('a[aria-current="page"]');
                await selected.locator('svg[data-request-navigation-icon="check"]').waitFor();
                assert.equal(await carousel.locator('a').count(), 4);
                assert.equal(await carousel.getByText('Seleccionada', { exact: true }).count(), 1);
                assert.deepEqual(await selected.evaluate(link => ({
                    background: getComputedStyle(link).backgroundColor,
                    border: getComputedStyle(link).borderTopColor,
                })), { background: 'rgb(236, 254, 255)', border: 'rgb(6, 182, 212)' });
                const dimensions = await carousel.locator('a').evaluateAll(links => links.map(link => ({
                    width: link.offsetWidth, height: link.offsetHeight,
                    fits: link.scrollWidth <= link.clientWidth && link.scrollHeight <= link.clientHeight,
                })));
                assert.equal(dimensions.every(size => size.width === 192 && size.height === 64 && size.fits), true);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1), false);
                const cardBox = await selected.boundingBox();
                const track = carousel.locator('.request-selector-scroll');
                const trackBox = await track.boundingBox();
                assert.ok(cardBox.x >= trackBox.x - 1 && cardBox.x + cardBox.width <= trackBox.x + trackBox.width + 1);
                if (process.env.VALIDATION_SCREENSHOTS && type === 'oncologicos') {
                    await page.screenshot({ path: path.join(process.env.VALIDATION_SCREENSHOTS, `carrusel-solicitudes-${width}.png`) });
                }
                if (width === 390 && type === 'todas') {
                    await carousel.getByRole('button', { name: 'Tipo siguiente', exact: true }).click();
                    await page.waitForFunction(() => document.querySelector('.request-selector-scroll').scrollLeft >= 239);
                    await carousel.getByRole('button', { name: 'Tipo anterior', exact: true }).click();
                    await page.waitForFunction(() => document.querySelector('.request-selector-scroll').scrollLeft < 1);
                }
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
