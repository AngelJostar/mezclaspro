const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = readFileSync(path.join(root, 'public/build', manifest['resources/css/app.css'].file), 'utf8');

test('process actions preserve alert colors and delivery matches the approval green', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const statusColors = new Set();
    try {
        for (const [stage, label] of [['aprobada', 'Dispensar'], ['dispensada', 'Preparar'], ['preparada', 'Inspeccionar'], ['revisada', 'Entregar']]) {
            const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                'tests/Browser/fixtures/inspection-process.php', stage], { cwd: root, encoding: 'utf8' });
            for (const width of [1265, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 800 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${styles}</style><main class="admin-page"><div class="admin-content">${html}</div></main>`);
                if (stage !== 'aprobada') {
                    const statusLabel = { dispensada: 'Dispensada', preparada: 'Preparada', revisada: 'Inspeccionada' }[stage];
                    const badge = page.locator('tbody tr').first().getByText(statusLabel, { exact: true });
                    statusColors.add(await badge.evaluate(el => `${getComputedStyle(el).backgroundColor}|${getComputedStyle(el).color}`));
                }
                const actions = page.getByRole(stage === 'aprobada' ? 'link' : 'button', { name: label, exact: true });
                assert.equal(await actions.count(), 2);
                for (let index = 0; index < 2; index++) {
                    const action = actions.nth(index);
                    await action.scrollIntoViewIfNeeded();
                    const appearance = await action.evaluate(el => ({
                        background: getComputedStyle(el).backgroundColor,
                        color: getComputedStyle(el).color,
                        clipped: el.scrollWidth > el.clientWidth + 1,
                    }));
                    const background = stage === 'revisada' ? 'rgb(22, 163, 74)' : (index === 0 ? 'rgb(250, 204, 21)' : 'rgb(220, 38, 38)');
                    assert.equal(appearance.background, background);
                    assert.equal(appearance.color, index === 0 && stage !== 'revisada' ? 'rgb(17, 24, 39)' : 'rgb(255, 255, 255)');
                    assert.equal(appearance.clipped, false);
                    if (stage === 'revisada') {
                        const approval = page.getByRole('button', { name: 'Aprobada', exact: true }).nth(index);
                        assert.equal(await approval.evaluate(el => getComputedStyle(el).backgroundColor), appearance.background);
                    }
                }
                if (process.env.INSPECTION_SCREENSHOTS && width === 1265) {
                    await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `inspection-process-${stage}.png`) });
                }
                await page.close();
            }
        }
        assert.equal(statusColors.size, 1);
    } finally {
        await browser.close();
    }
});
