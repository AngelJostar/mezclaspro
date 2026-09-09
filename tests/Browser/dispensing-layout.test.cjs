const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [
    path.join('public/build', manifest['resources/css/app.css'].file),
    'resources/css/mixture-workflow.css', 'resources/css/workflow-modal.css',
].map(file => readFileSync(path.join(root, file), 'utf8')).join('\n');
const bundle = files => buildSync({
    stdin: { contents: files.map(file => `import './resources/js/${file}';`).join('\n'), resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const modalScript = bundle(['workflow-modal.js']);
const formScript = bundle(['mixture-workflow.js', 'workflow-modal.js', 'table-column-filters.js']);
const shell = readFileSync(path.join(root, 'resources/views/layouts/includes/workflow-modal.blade.php'), 'utf8');
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(width, category) {
    // Exercise the real dialog, iframe, form and automatic filters with synthetic data only.
    const form = execFileSync('php', ['tests/Browser/fixtures/mixture-diluent.php', 'dispensacion', '', 'proposal', category], { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin !== 'http://dispensing.test') return route.abort();
        const isForm = url.pathname === '/edit';
        const content = isForm
            ? `<main class="admin-page workflow-page"><div class="admin-content">${form}</div></main>
                <script type="application/json" id="workflow-page-config">{"embedded":true,"completed":false}</script>
                <script>window.Swal={fire:()=>Promise.resolve({isConfirmed:false})};</script><script>${formScript}</script>`
            : `<a href="/edit?modo=dispensacion&dispensing_popup=1" data-dispensing-popup="test">Dispensar</a>${shell}
                <script type="application/json" id="workflow-page-config">{"embedded":false}</script><script>${modalScript}</script>`;
        await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html class="${isForm ? 'workflow-embedded' : ''}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style></head><body>${content}</body></html>` });
    });
    await page.goto('http://dispensing.test/list');
    await page.getByRole('link', { name: 'Dispensar', exact: true }).click();
    const frame = page.frameLocator('[data-workflow-frame]');
    await frame.locator('.dispensing-presentation-table[data-automatic-column-filters]').first().waitFor();
    return { page, frame, errors };
}

async function assertTableFits(frame, rowId, width) {
    const scroll = frame.locator(`#presentaciones_wrap_${rowId} .dispensing-presentation-scroll`);
    const measurements = await scroll.evaluate(el => {
        const table = el.querySelector('table');
        const viewport = document.documentElement.clientWidth;
        return {
            viewport, pageWidth: document.documentElement.scrollWidth,
            formWidth: document.querySelector('.admin-content').scrollWidth,
            formClientWidth: document.querySelector('.admin-content').clientWidth,
            left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right,
            clientWidth: el.clientWidth, scrollWidth: el.scrollWidth,
            tableWidth: table.getBoundingClientRect().width,
        };
    });
    assert.ok(measurements.pageWidth <= measurements.viewport + 1, `Document overflows at ${width}`);
    assert.ok(measurements.formWidth <= measurements.formClientWidth + 1, `Form overflows at ${width}`);
    assert.ok(measurements.left >= 0 && measurements.right <= measurements.viewport + 1);
    if (measurements.clientWidth >= 1120) {
        assert.ok(measurements.scrollWidth <= measurements.clientWidth + 1, `Desktop table needs scrolling at ${width}`);
    } else {
        assert.ok(measurements.scrollWidth > measurements.clientWidth, `Small viewport needs a local scroll area at ${width}`);
        assert.equal(await scroll.getAttribute('tabindex'), '0');
    }
    assert.equal(await scroll.getAttribute('data-disable-sticky-x'), '');
    await scroll.evaluate(el => { el.scrollLeft = el.scrollWidth; });
    const visible = await scroll.evaluate(el => {
        const lastCell = el.querySelector('tbody tr td:last-child').getBoundingClientRect();
        const region = el.getBoundingClientRect();
        return lastCell.left >= region.left && lastCell.right <= region.right + 1;
    });
    assert.equal(visible, true, `Rightmost column cannot be reached at ${width}`);
    for (const selector of ['th', 'td', 'th > div > span', '.estimated-remainder']) {
        const overflow = await scroll.locator(selector).evaluateAll(elements => elements.some(el => el.scrollWidth > el.clientWidth + 1));
        assert.equal(overflow, false, `${selector} text or controls overflow at ${width}`);
    }
    await scroll.evaluate(el => { el.scrollLeft = 0; });
}

test('dispensing popup contains the entire table, including rightmost fields and newly added medications', async () => {
    for (const category of ['oncologicos', 'antibioticos']) {
        for (const width of [1480, 1366, 1320, 1024, 768, 390, 320]) {
            const { page, frame, errors } = await fixture(width, category);
            try {
                await assertTableFits(frame, 1, width);
                const unit = await frame.locator('#fila_1 .dispensing-unit-input > span').boundingBox();
                const dialog = await page.locator('[data-workflow-modal]').boundingBox();
                assert.ok(unit.x + unit.width < dialog.x + dialog.width, 'Unit field is clipped');
                assert.equal(await frame.locator('.dispensing-medication-table > thead').isVisible(), false);
                assert.equal(await frame.getByLabel('Medicamento', { exact: true }).count(), 1);
                assert.equal(await frame.getByLabel('Dosis en miligramos', { exact: true }).count(), 1);
                assert.equal(await frame.locator('#resumen_dosis_1 svg').count(), 1);
                const fields = ['#fila_1 .dispensing-medication-field', '#fila_1 .dispensing-dose-field', '#resumen_dosis_1',
                    '#diluyentes_mezcla', '.dispensing-volume-field', '.dispensing-time-field', '.dispensing-infusor-field'];
                for (const selector of fields) {
                    const field = frame.locator(selector);
                    const box = await field.boundingBox();
                    assert.ok(box.x >= dialog.x && box.x + box.width < dialog.x + dialog.width, `${selector} is clipped at ${width}`);
                    assert.equal(await field.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true, `${selector} text overflow at ${width}`);
                }
                if (width >= 1320) {
                    const drug = await frame.getByLabel('Medicamento', { exact: true }).boundingBox();
                    const dose = await frame.getByLabel('Dosis en miligramos', { exact: true }).boundingBox();
                    const summary = await frame.locator('#resumen_dosis_1').boundingBox();
                    assert.ok(drug.x + drug.width < dose.x && dose.x + dose.width < summary.x);
                    const diluent = await frame.locator('#diluyentes_mezcla').boundingBox();
                    const presentation = await frame.locator('#diluent_presentation_id').boundingBox();
                    const volume = await frame.locator('#volumen_dilucion').boundingBox();
                    assert.ok(diluent.x + diluent.width < presentation.x && presentation.x + presentation.width < volume.x);
                    assert.ok(Math.abs(presentation.y - volume.y) < 2);
                }
                assert.equal(await frame.locator('#presentaciones_body_1 .input-frascos').first().inputValue(), '4');
                assert.equal(await frame.locator('#presentaciones_body_1 .estimated-remainder').first().innerText(), '200.00 mg');
                assert.equal(await frame.locator('#presentaciones_body_1 .info-caducidad').first().innerText(), '31/12/2027');
                await frame.locator('.dispensing-mixture').evaluate(el => el.scrollIntoView({ block: 'start' }));
                if (process.env.DISPENSING_SCREENSHOT_DIR && category === 'oncologicos') {
                    await page.screenshot({ path: path.join(process.env.DISPENSING_SCREENSHOT_DIR, `dispensing-popup-${width}.png`) });
                }
                await frame.getByRole('button', { name: '+ Agregar Medicamento', exact: true }).click();
                await frame.locator('#fila_2 [data-name="medicamento"]').selectOption('20');
                await frame.locator('#fila_2 .dosis-input').fill('1800');
                await frame.locator('#presentaciones_wrap_2 table[data-automatic-column-filters]').waitFor();
                await assertTableFits(frame, 2, width);
                assert.equal(await frame.locator('#presentaciones_body_2 .input-frascos').first().inputValue(), '2');
                assert.equal(await frame.locator('#presentaciones_body_2 .estimated-remainder').first().innerText(), '200.00 mg');
                assert.deepEqual(errors, []);
            } finally { await page.close(); }
        }
    }
});
