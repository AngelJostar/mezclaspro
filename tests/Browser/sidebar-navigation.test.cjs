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
const script = buildSync({
    stdin: {
        contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js';
            import './resources/js/request-navigation.js';
            Alpine.start();`,
        resolveDir: root,
    },
    bundle: true, write: false, format: 'iife',
}).outputFiles[0].text;

async function assertLeftAlignedLabels(page) {
    const labels = await page.locator('#logo-sidebar a, #logo-sidebar button').evaluateAll(elements => elements
        .filter(element => element.getClientRects().length)
        .map(element => {
            const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, {
                acceptNode: node => node.textContent.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT,
            });
            const text = walker.nextNode();
            const start = text.textContent.search(/\S/);
            const range = document.createRange();
            range.setStart(text, start);
            range.setEnd(text, start + 1);
            return { name: element.innerText.trim(), left: range.getBoundingClientRect().left,
                align: getComputedStyle(text.parentElement).textAlign };
        }));
    for (const label of labels) {
        assert.ok(Math.abs(label.left - labels[0].left) < 1, `${label.name} must share the sidebar's left text edge`);
        assert.equal(label.align, 'left', label.name);
    }
}

test('hospital starts in Preparacion without internal personnel menus', async () => {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/hospital-entry.php'], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            await page.route('**/*', route => route.request().url().endsWith('/img/promesa-logo.png')
                ? route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) })
                : route.abort());
            await page.setContent(`<meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{ open: true }">${html}</div>`);
            await page.addScriptTag({ content: script });
            const sidebar = page.locator('#logo-sidebar');
            const list = sidebar.getByRole('link', { name: 'Preparacion', exact: true });
            await list.waitFor({ state: 'visible' });
            assert.equal(await list.getAttribute('aria-current'), 'page');
            assert.equal(await list.getAttribute('href'), 'http://localhost/admin/solicitudes');
            const quotation = sidebar.getByRole('link', { name: 'Cotizacion', exact: true });
            assert.equal(await quotation.isVisible(), true);
            assert.equal(await quotation.getAttribute('href'), 'http://localhost/admin/solicitudes/cotizacion');
            assert.equal(await quotation.getAttribute('aria-current'), null);
            assert.equal(await sidebar.getByRole('button').count(), 1);
            assert.equal(await sidebar.getByText('Personal y Capacitaciones', { exact: true }).count(), 0);
            assert.equal(await sidebar.locator('a[href*="/capacitaciones"]').count(), 0);
            assert.equal(await page.getByRole('heading', { name: 'Lista de Solicitudes', exact: true }).count(), 1);
            assert.equal(await page.getByText('Hospital ajeno', { exact: true }).count(), 0);
            assert.equal(await page.getByRole('navigation', { name: 'Tipo de solicitudes' }).getByRole('link').count(), 4);
            await assertLeftAlignedLabels(page);
            if (process.env.SIDEBAR_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.SIDEBAR_SCREENSHOTS, `hospital-entry-${width}.png`) });
            await page.close();
        }
    } finally { await browser.close(); }
});

test('only sidebar dropdowns have visible arrows that follow their expanded state', async () => {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/sidebar-navigation.php'], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 768, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<style>${css}</style><div x-data="{ open: true }">${html}</div>`);
            await page.addScriptTag({ content: script });
            const sidebar = page.locator('#logo-sidebar');
            const toggles = sidebar.locator('button[aria-controls]');
            await sidebar.locator('svg[data-request-navigation-icon]').first().waitFor();
            assert.equal(await toggles.count(), 6);
            assert.equal(await sidebar.locator('a [data-request-navigation-icon]').count(), 0);
            assert.deepEqual((await sidebar.locator('#solicitudes-submenu a').allTextContents()).map(text => text.trim()), ['Preparacion', 'Cotizacion']);
            assert.equal(await sidebar.locator('a[href*="/solicitudes/validaciones"]').count(), 0);
            await assertLeftAlignedLabels(page);
            for (const button of await toggles.all()) {
                const label = (await button.innerText()).trim();
                const arrow = button.locator('svg[data-request-navigation-icon="chevron-down"]');
                const submenu = page.locator(`#${await button.getAttribute('aria-controls')}`);
                assert.equal(await arrow.count(), 1, label);
                assert.equal(await arrow.getAttribute('aria-hidden'), 'true');
                assert.equal(await button.getAttribute('aria-expanded'), 'false', label);
                assert.equal(await submenu.isVisible(), false, label);
                const fits = await button.evaluate(element => {
                    const bounds = element.getBoundingClientRect();
                    const arrow = element.querySelector('svg').getBoundingClientRect();
                    const label = element.querySelector('span');
                    return arrow.width > 0 && arrow.height > 0 && arrow.right <= bounds.right
                        && arrow.left >= label.getBoundingClientRect().right
                        && label.scrollWidth <= label.clientWidth + 1;
                });
                assert.equal(fits, true, `${label}: arrow and label must not overlap`);
                await button.click();
                await page.waitForFunction(id => document.querySelector(`button[aria-controls="${id}"]`).getAttribute('aria-expanded') === 'true', await button.getAttribute('aria-controls'));
                await submenu.waitFor({ state: 'visible' });
                assert.equal(await submenu.isVisible(), true, label);
                assert.equal(await arrow.evaluate(icon => icon.classList.contains('rotate-180')), true, label);
                await assertLeftAlignedLabels(page);
                await button.click();
                await submenu.waitFor({ state: 'hidden' });
                assert.equal(await button.getAttribute('aria-expanded'), 'false', label);
                assert.equal(await arrow.evaluate(icon => icon.classList.contains('rotate-180')), false, label);
            }
            await page.waitForFunction(() => [...document.querySelectorAll('#logo-sidebar button svg')]
                .every(icon => ['none', 'matrix(1, 0, 0, 1, 0, 0)'].includes(getComputedStyle(icon).transform)));
            if (process.env.SIDEBAR_SCREENSHOTS) {
                await sidebar.screenshot({ path: path.join(process.env.SIDEBAR_SCREENSHOTS, `sidebar-left-aligned-${width}.png`) });
                await sidebar.locator('button[aria-controls="solicitudes-submenu"]').click();
                await page.locator('#solicitudes-submenu').waitFor({ state: 'visible' });
                await sidebar.screenshot({ path: path.join(process.env.SIDEBAR_SCREENSHOTS, `sidebar-left-aligned-open-${width}.png`) });
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally {
        await browser.close();
    }
});
