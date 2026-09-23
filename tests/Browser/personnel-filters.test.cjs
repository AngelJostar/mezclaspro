const { test } = require('node:test');
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
const script = buildSync({ stdin: { contents: "import './resources/js/fixed-table-scrollbar'; import './resources/js/personnel-table-icons';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text + readFileSync(path.join(root, 'resources/js/table-column-filters.js'), 'utf8');
const fixture = count => execFileSync('php', ['tests/Browser/fixtures/personnel-scrollbar.php', String(count), 'filters'], { cwd: root, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
const populated = fixture(205);
const empty = execFileSync('php', ['tests/Browser/fixtures/personnel-scrollbar.php', '0'], { cwd: root, encoding: 'utf8' });

async function mount(browser, width, html = populated) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => route.abort());
    await page.setContent(`<style>${styles}</style><main class="admin-page sm:ml-44"><div class="admin-content">${html}</div></main><script>${script}</script>`);
    await page.locator('#personnel-column-filter-panel').waitFor({ state: 'attached' });
    return { page, errors };
}

test('personnel filters use the correct grouped columns, combine, cancel, clear and include later pages', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const { page, errors } = await mount(browser, 1366);
        const table = page.locator('#training-personnel-table');
        const visible = table.locator('[data-student-row]:visible');
        const panel = page.locator('#personnel-column-filter-panel');
        const count = page.locator('[data-personnel-count]');
        const clear = page.locator('[data-personnel-clear-filters]');
        const filter = async (column, values) => {
            await table.locator(`.js-personnel-filter[data-column="${column}"]`).click();
            await panel.locator('[data-filter-all]').uncheck();
            for (const value of values) await panel.getByRole('checkbox', { name: value, exact: true }).check();
            await panel.getByRole('button', { name: 'Aceptar', exact: true }).click();
        };
        assert.deepEqual(await table.locator('.js-personnel-filter').evaluateAll(nodes => nodes.map(n => Number(n.dataset.column))),
            [0, 2, 3, 4, 5, 6, 7, 8, 9, 11, 16, 17, 19, 12, 14]);
        assert.equal(await table.locator('.js-personnel-sort').count(), 15);
        assert.equal(await table.locator('.js-personnel-sort svg').count(), 15);
        assert.equal(await visible.count(), 200);
        assert.equal(await count.innerText(), '1-200 de 205');
        await page.locator('[data-personnel-next]').click();
        assert.equal(await visible.count(), 5);
        assert.equal(await count.innerText(), '201-205 de 205');
        await filter(0, ['Brenda Perez']);
        assert.equal(await visible.count(), 1);
        assert.equal(await visible.locator('.training-person strong').innerText(), 'Brenda Perez');
        assert.equal(await count.innerText(), '1-1 de 1');
        await clear.click();
        await filter(2, ['Verificador']);
        assert.equal(await visible.count(), 2);
        await filter(8, ['Baja']);
        assert.equal(await visible.count(), 1);
        assert.equal(await visible.locator('.training-person strong').innerText(), 'Alvaro 2');
        await table.locator('.js-personnel-filter[data-column="8"]').click();
        await panel.locator('[data-filter-all]').check();
        await panel.getByRole('button', { name: 'Cancelar', exact: true }).click();
        assert.equal(await visible.count(), 1);
        await clear.click();
        await page.locator('[data-personnel-department]').selectOption('Calidad');
        assert.equal(await visible.count(), 2);
        await filter(8, ['Contratado']);
        assert.equal(await visible.count(), 1);
        await visible.locator('[data-student-status]').selectOption('inactive');
        assert.equal(await visible.count(), 0);
        assert.equal(await table.locator('[data-personnel-empty]').isVisible(), true);
        assert.equal(await count.innerText(), '0-0 de 0');
        await clear.click();
        await table.locator('.js-personnel-filter[data-column="0"]').click();
        await panel.locator('[data-filter-search]').fill('ALVARO');
        assert.equal(await panel.locator('[data-filter-options] label:visible').count(), 2);
        await page.keyboard.press('Escape');
        assert.equal(await panel.isVisible(), false);
        assert.deepEqual(errors, []);
        await page.close();
    } finally { await browser.close(); }
});

test('personnel sorting handles names, numbers, dates, missing values and both directions before pagination', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const { page, errors } = await mount(browser, 1366);
        const table = page.locator('#training-personnel-table');
        const rows = table.locator('[data-student-row]');
        const sort = column => table.locator(`.js-personnel-sort[data-sort-column="${column}"]`).click();
        const firstName = () => table.locator('[data-student-row]:visible .training-person strong').first().innerText();
        const lastName = () => rows.last().locator('.training-person strong').innerText();
        await sort(0);
        assert.equal(await firstName(), 'Alvaro 2');
        assert.equal(await table.locator('th[aria-sort="ascending"]').count(), 1);
        assert.equal(await table.locator('.js-personnel-sort[data-sort-column="0"] svg').getAttribute('data-personnel-table-icon'), 'arrow-up');
        await sort(0);
        assert.equal(await firstName(), 'Persona de prueba 205');
        assert.equal(await table.locator('th[aria-sort="descending"]').count(), 1);
        await sort(3);
        assert.equal(await firstName(), 'Alvaro 2');
        assert.equal(await lastName(), 'Brenda Perez');
        await sort(3);
        assert.equal(await firstName(), 'Persona de prueba 4');
        assert.equal(await lastName(), 'Brenda Perez');
        await sort(9);
        assert.equal(await firstName(), 'Alvaro 2');
        await sort(6);
        assert.equal(await firstName(), 'Alvaro 10');
        await sort(6);
        assert.equal(await firstName(), 'Alvaro 2');
        await sort(4);
        await sort(4);
        assert.equal(await firstName(), 'Alvaro 10');
        // Synthetic IDs exercise numeric sorting without touching real personnel records.
        await rows.evaluateAll(nodes => nodes.forEach((row, index) => { row.cells[11].textContent = String(index + 1); }));
        await sort(11);
        await sort(11);
        assert.equal(await table.locator('[data-student-row]:visible').first().locator('td').nth(11).innerText(), '205');
        assert.equal(await table.locator('.js-personnel-sort[aria-pressed="true"]').count(), 1);
        assert.deepEqual(errors, []);
        await page.close();
    } finally { await browser.close(); }
});

test('personnel controls fit desktop/mobile and remain usable on empty lists', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 1366, 390, 320]) {
            const { page, errors } = await mount(browser, width, fixture(5));
            const table = page.locator('#training-personnel-table');
            await table.scrollIntoViewIfNeeded();
            for (const button of await table.locator('.js-personnel-filter').all()) {
                await button.click();
                const bounds = await page.locator('#personnel-column-filter-panel').boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
                assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 900);
                await page.keyboard.press('Escape');
            }
            const layoutIssues = await table.locator('th .flex').evaluateAll(wrappers => wrappers.filter(wrapper => {
                const children = Array.from(wrapper.children).map(child => child.getBoundingClientRect());
                return children.slice(1).some((box, i) => box.left < children[i].right - 1);
            }).length);
            assert.equal(layoutIssues, 0);
            const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla', exact: true });
            await bar.getByRole('slider').focus();
            await page.keyboard.press('Home');
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            if (process.env.SCROLLBAR_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.SCROLLBAR_SCREENSHOTS, `personnel-filters-${width}.png`) });
            assert.deepEqual(errors, []);
            await page.close();
        }
        const { page, errors } = await mount(browser, 390, empty);
        await page.getByRole('button', { name: 'Filtrar Personal', exact: true }).click();
        assert.equal(await page.locator('#personnel-column-filter-panel [data-filter-options] label').count(), 0);
        await page.keyboard.press('Escape');
        await page.locator('.js-personnel-sort[data-sort-column="0"]').click();
        assert.equal(await page.locator('[data-personnel-empty]').isVisible(), true);
        assert.equal(await page.locator('[data-personnel-count]').innerText(), '0-0 de 0');
        assert.deepEqual(errors, []);
        await page.close();
    } finally { await browser.close(); }
});
