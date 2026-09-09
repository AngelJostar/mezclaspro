const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = readFileSync(path.join(root, 'public/build', manifest['resources/css/app.css'].file), 'utf8');

test('rejection reason, validation and success dialogs fit desktop and mobile', async () => {
    const states = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/inspection-rejection.php'], { cwd: root, encoding: 'utf8' }));
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const [state, html] of Object.entries(states)) {
            for (const width of [1265, 390, 320]) {
                const page = await browser.newPage({ viewport: { width, height: 700 } });
                await page.route('**/*', route => route.abort());
                await page.setContent(`<style>${styles}</style>${html}`);
                const dialog = page.getByRole(state === 'success' ? 'alertdialog' : 'dialog');
                assert.equal(await dialog.count(), 1);
                const bounds = await dialog.boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
                assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 700);
                const buttons = dialog.getByRole('button');
                assert.deepEqual(await buttons.allTextContents(), state === 'success' ? ['OK'] : ['Cancelar', 'Guardar']);
                assert.equal(await page.getByRole('button', { name: 'Aprobada', exact: true }).count(), 0);
                for (const button of await buttons.all()) {
                    assert.equal(await button.evaluate(el => el.scrollWidth > el.clientWidth + 1), false);
                    const box = await button.boundingBox();
                    assert.ok(box.x >= bounds.x && box.x + box.width <= bounds.x + bounds.width);
                }
                if (state !== 'success') {
                    const reason = dialog.getByLabel('Motivo del rechazo');
                    await reason.fill('Fuga detectada en el contenedor');
                    assert.equal(await reason.inputValue(), 'Fuga detectada en el contenedor');
                    assert.equal(await reason.getAttribute('maxlength'), '2000');
                } else {
                    assert.equal(await dialog.getByRole('heading', { name: 'Éxito' }).count(), 1);
                    assert.equal(await buttons.first().evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
                }
                if (state === 'error') assert.match(await dialog.getByRole('alert').innerText(), /Registra el motivo del rechazo/);
                if (process.env.INSPECTION_SCREENSHOTS && width !== 320) {
                    await page.screenshot({ path: path.join(process.env.INSPECTION_SCREENSHOTS, `inspection-rejection-${state}-${width}.png`) });
                }
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }
});
