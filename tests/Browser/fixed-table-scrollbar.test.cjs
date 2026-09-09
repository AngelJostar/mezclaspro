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
const script = buildSync({ entryPoints: [path.join(root, 'resources/js/fixed-table-scrollbar.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const table = execFileSync('php', ['tests/Browser/fixtures/request-scrollbar.php'], { cwd: root, encoding: 'utf8' });

test('fixed table scrollbar stays visible for empty requests and supports arrows, dragging, keyboard, resize and refreshed tables', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1881, 1366, 390, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 800 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            try {
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${styles}</style><main class="admin-page sm:ml-44"><div class="admin-content"><h1>Lista de Solicitudes</h1>${table}</div></main><script>${script}</script>`);
                const source = page.locator('[data-sticky-x-position="viewport"]');
                const bar = page.getByRole('group', { name: 'Desplazamiento horizontal de la tabla', exact: true });
                const range = bar.getByRole('slider');
                const left = bar.getByRole('button', { name: 'Desplazar columnas a la izquierda', exact: true });
                const right = bar.getByRole('button', { name: 'Desplazar columnas a la derecha', exact: true });
                await bar.waitFor();
                const box = await bar.boundingBox();
                const sourceBox = await source.boundingBox();
                assert.equal(Math.round(box.y + box.height), 800);
                assert.ok(Math.abs(sourceBox.x - box.x) < 1);
                assert.ok(Math.abs(sourceBox.width - box.width) < 1);
                assert.equal(await left.isDisabled(), true);
                assert.equal(await right.isEnabled(), true);
                assert.equal(await bar.locator('svg').count(), 2);
                assert.equal(await range.getAttribute('aria-controls'), await source.getAttribute('id'));
                const thumbWidth = await range.evaluate(el => parseFloat(el.style.getPropertyValue('--scroll-thumb-width')));
                assert.ok(thumbWidth >= 28 && thumbWidth <= (await range.boundingBox()).width);
                await right.click();
                assert.ok(await source.evaluate(el => el.scrollLeft > 0));
                await left.click();
                assert.equal(await source.evaluate(el => el.scrollLeft), 0);
                await range.focus();
                await page.keyboard.press('End');
                await page.waitForFunction(() => {
                    const el = document.querySelector('[data-sticky-x-position]');
                    return Math.abs(el.scrollWidth - el.clientWidth - el.scrollLeft) < 1;
                });
                assert.equal(await right.isDisabled(), true);
                const last = await source.locator('th').last().boundingBox();
                assert.ok(last.x + last.width <= sourceBox.x + sourceBox.width + 1);
                await page.keyboard.press('Home');
                await page.keyboard.press('ArrowRight');
                assert.equal(await source.evaluate(el => el.scrollLeft), 80);

                await source.evaluate(el => { el.scrollLeft = (el.scrollWidth - el.clientWidth) / 2; });
                await page.waitForFunction(() => Math.abs(Number(document.querySelector('.fixed-table-scrollbar input').value)
                    - document.querySelector('[data-sticky-x-position]').scrollLeft) <= 1);
                const rangeBox = await range.boundingBox();
                await page.mouse.move(rangeBox.x + rangeBox.width / 2, rangeBox.y + rangeBox.height / 2);
                await page.mouse.down();
                await page.mouse.move(rangeBox.x + rangeBox.width - 1, rangeBox.y + rangeBox.height / 2, { steps: 10 });
                await page.mouse.up();
                assert.ok(await source.evaluate(el => el.scrollLeft >= (el.scrollWidth - el.clientWidth) * 0.95));

                if (process.env.SCROLLBAR_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.SCROLLBAR_SCREENSHOTS, `fixed-requests-scrollbar-${width}.png`) });
                }
                await source.locator('tbody').evaluate(body => {
                    const original = body.firstElementChild;
                    for (let i = 0; i < 20; i++) body.append(original.cloneNode(true));
                });
                await page.evaluate(() => scrollTo(0, 500));
                await page.waitForFunction(() => Math.abs(document.querySelector('.fixed-table-scrollbar').getBoundingClientRect().bottom - innerHeight) < 1);
                await bar.waitFor();
                assert.equal(await page.locator('.fixed-table-scrollbar').count(), 1);
                await page.setViewportSize({ width: width === 320 ? 390 : 320, height: 700 });
                await page.waitForFunction(() => document.querySelector('.fixed-table-scrollbar').getBoundingClientRect().right <= innerWidth);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);

                await page.evaluate(() => {
                    const current = document.querySelector('[data-sticky-x-position]');
                    current.replaceWith(current.cloneNode(true));
                    document.dispatchEvent(new CustomEvent('livewire:navigated'));
                });
                await bar.waitFor();
                assert.equal(await page.locator('.fixed-table-scrollbar').count(), 1);
                await range.focus();
                await page.keyboard.press('Home');
                assert.equal(await source.evaluate(el => el.scrollLeft), 0);

                await page.evaluate(() => {
                    scrollTo(0, 0);
                    document.querySelector('[data-sticky-x-position] table').remove();
                });
                await bar.waitFor({ state: 'hidden' });
                await source.evaluate(el => el.remove());
                await page.waitForFunction(() => !document.querySelector('.fixed-table-scrollbar'));
                assert.equal(await page.locator('.has-fixed-table-scrollbar').count(), 0);
                assert.deepEqual(errors, []);
            } finally { await page.close(); }
        }
    } finally { await browser.close(); }
});
