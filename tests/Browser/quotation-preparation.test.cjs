const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const formatQuantity = value => new Intl.NumberFormat('es-MX', { maximumFractionDigits: 4 }).format(value);

test('quoted requests reuse category forms and lock commercial quantities on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const category of ['oncologicos', 'antibioticos', 'nutricionales']) {
            for (const unit of [category === 'nutricionales' ? 'ml' : 'mg', 'frasco']) {
              for (const count of [1, 2]) {
                const source = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                    'tests/Browser/fixtures/quotation-preparation.php', category, unit, String(count)], { cwd: root, encoding: 'utf8' });
                for (const width of [1440, 390]) {
                    const page = await browser.newPage({ viewport: { width, height: 1000 } });
                    const errors = [];
                    page.on('pageerror', error => errors.push(error.message));
                    await page.route('**/*', route => route.fulfill({ contentType: 'text/html', body:
                        `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style>${source}` }));
                    await page.goto('http://localhost/test-preparation');
                    const form = page.locator('[data-quotation-preparation]');
                    assert.equal(await form.count(), 1);
                    assert.equal(await form.getByRole('link', { name: /^COT-/ }).count(), 1);
                    assert.equal(await form.getByText('Hospital de prueba', { exact: true }).count(), 1);
                    assert.equal(await form.locator('[data-quoted-summary] thead th').count(), 4);
                    const concentrationUnit = category === 'nutricionales' ? 'mL' : 'mg';
                    const content = category === 'antibioticos' ? 500 : 100;
                    assert.deepEqual(await form.locator('[data-presentation-concentration]').allTextContents(), Array(count).fill(`${content} ${concentrationUnit}`));
                    assert.equal(await form.locator('[data-quoted-medication-total] td').textContent(), `${formatQuantity((unit === 'frasco' ? content * 2 : 50) * count)} ${concentrationUnit}`);
                    assert.deepEqual(await form.locator('[data-quoted-concentration]').allTextContents(), Array(count).fill(`${formatQuantity(unit === 'frasco' ? content * 2 : 50)} ${concentrationUnit}`));
                    if (category === 'nutricionales') {
                        assert.equal(await form.locator('[name^="quoted_volumes["]').count(), count);
                        for (let i = 0; i < count; i++) {
                            const volume = form.locator(`[name="quoted_volumes[${i}]"]`);
                            assert.equal(await volume.getAttribute('readonly') !== null, unit !== 'frasco');
                            assert.equal(await volume.inputValue(), unit === 'frasco' ? '' : '50');
                        }
                        assert.equal(await form.locator('[name=nombre_paciente]').inputValue(), 'Paciente');
                        assert.equal(await form.locator('[name=apellidos_paciente]').inputValue(), 'Prueba');
                        assert.equal(await form.locator('[name=volumen_total]').isDisabled(), true);
                    } else {
                        assert.deepEqual(await form.locator('[data-required-concentration]').allTextContents(), Array(count).fill(unit === 'frasco' ? 'Pendiente' : '50 mg'));
                        assert.equal(await form.locator('[name=cantidad_mezclas]').inputValue(), String(count));
                        const medicine = form.locator('[data-name=medicamento]');
                        assert.equal(await medicine.count(), count);
                        const dose = form.locator('[data-name=dosis]');
                        for (let i = 0; i < count; i++) {
                            assert.equal(await medicine.nth(i).isDisabled(), true);
                            assert.equal(await dose.nth(i).inputValue(), unit === 'frasco' ? '' : '50');
                            assert.equal(await dose.nth(i).getAttribute('readonly') !== null, unit !== 'frasco');
                        }
                        assert.equal(await form.getByRole('button', { name: 'Agregar Mezcla', exact: true }).isVisible(), false);
                        assert.equal(await form.getByRole('button', { name: 'Agregar otra fecha de entrega', exact: true }).first().isVisible(), false);
                        assert.equal(await form.getByRole('button', { name: 'Eliminar medicamento', exact: true }).first().isVisible(), false);
                        assert.equal(await form.locator('[name=paciente_nombre]').inputValue(), 'Paciente Prueba');
                    }
                    assert.deepEqual(errors, []);
                    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, `${category}/${unit}/${width} must not overflow`);
                    if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `preparation-${category}-${unit}-${count}-${width}.png`), fullPage: true });
                    await page.close();
                }
              }
            }
        }
    } finally { await browser.close(); }
});

test('presentations share a medication name and quoted concentration without merging their dose inputs', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const category of ['oncologicos', 'antibioticos']) {
            for (const unit of ['frasco', 'mg']) {
                const source = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
                    'tests/Browser/fixtures/quotation-preparation.php', category, unit, '2', 'presentations'], { cwd: root, encoding: 'utf8' });
                for (const width of [1440, 390]) {
                    const page = await browser.newPage({ viewport: { width, height: 1100 } });
                    const errors = [];
                    page.on('pageerror', error => errors.push(error.message));
                    await page.route('**/*', route => route.fulfill({ contentType: 'text/html', body:
                        `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style>${source}` }));
                    await page.goto('http://localhost/test-preparation');
                    const form = page.locator('[data-quotation-preparation]');
                    const summary = form.locator('[data-quoted-summary]');
                    assert.deepEqual(await summary.locator('[data-presentation-concentration]').allTextContents(), ['100 mg', '50 mg', '25 mg', '25 mg']);
                    assert.deepEqual(await summary.locator('[data-quoted-medication-total] td').allTextContents(), unit === 'frasco' ? ['250 mg', '100 mg'] : ['50 mg', '25 mg']);
                    const tables = form.locator('.quoted-medicine-table');
                    assert.equal(await tables.count(), 2);
                    const first = tables.first();
                    assert.equal(await first.getByRole('columnheader', { name: 'Concentraci\u00f3n cotizada' }).count(), 1);
                    const headings = await first.locator('thead th:visible').allTextContents();
                    assert.deepEqual(headings.slice(1, 4), ['Concentraci\u00f3n cotizada', 'Concentraci\u00f3n requerida', 'DOSIS']);
                    assert.equal(await first.locator('[data-name=medicamento]:visible').count(), 2);
                    assert.equal(await first.locator('[data-name=dosis]:visible').count(), 3);
                    assert.deepEqual(await first.locator('[data-quoted-concentration]').allTextContents(), unit === 'frasco' ? ['250 mg', '75 mg'] : ['50 mg', '20 mg']);
                    assert.equal(await first.locator('[data-quoted-concentration]').first().getAttribute('rowspan'), '2');
                    const required = first.locator('[data-required-concentration]');
                    assert.equal(await required.first().getAttribute('rowspan'), '2');
                    assert.deepEqual(await required.allTextContents(), unit === 'frasco' ? ['Pendiente', 'Pendiente'] : ['50 mg', '20 mg']);
                    assert.deepEqual(await first.locator('[data-name=medicamento]').evaluateAll(inputs => inputs.map(input => input.value)), [category === 'antibioticos' ? '2' : '1', category === 'antibioticos' ? '2' : '1', '10']);
                    const doses = first.locator('[data-name=dosis]');
                    if (unit === 'frasco') {
                        await doses.nth(0).fill('150');
                        assert.equal(await required.first().textContent(), 'Pendiente');
                        await doses.nth(1).fill('25');
                        assert.deepEqual(await required.allTextContents(), ['175 mg', 'Pendiente']);
                        await doses.nth(2).fill('50');
                        assert.deepEqual(await required.allTextContents(), ['175 mg', '50 mg']);
                        assert.equal(await tables.nth(1).locator('[data-required-concentration]').textContent(), 'Pendiente');
                        await tables.nth(1).locator('[data-name=dosis]').fill('20');
                        assert.equal(await tables.nth(1).locator('[data-required-concentration]').textContent(), '20 mg');
                        assert.deepEqual(await required.allTextContents(), ['175 mg', '50 mg']);
                        assert.equal(await doses.nth(0).inputValue(), '150');
                        assert.equal(await doses.nth(1).inputValue(), '25');
                        assert.equal(await doses.nth(0).getAttribute('max'), '200');
                        assert.equal(await doses.nth(1).getAttribute('max'), '50');
                        await doses.nth(1).fill('51');
                        assert.equal(await doses.nth(1).evaluate(input => input.validity.rangeOverflow), true);
                        assert.equal(await required.first().textContent(), 'Revisar dosis');
                        await doses.nth(1).fill('0');
                        assert.equal(await required.first().textContent(), 'Revisar dosis');
                        await doses.nth(1).fill('');
                        assert.equal(await required.first().textContent(), 'Pendiente');
                        await doses.nth(0).fill('150.1234'); await doses.nth(1).fill('25.4321');
                        assert.equal(await required.first().textContent(), '175.5555 mg');
                        await doses.nth(0).fill('150');
                        await doses.nth(1).fill('25');
                        assert.deepEqual(await required.allTextContents(), ['175 mg', '50 mg']);
                        assert.deepEqual(await first.locator('[data-quoted-concentration]').allTextContents(), ['250 mg', '75 mg']);
                    } else {
                        assert.equal(await tables.nth(1).locator('[data-required-concentration]').textContent(), '5 mg');
                        assert.deepEqual(await doses.evaluateAll(inputs => inputs.map(input => input.value)), ['40', '10', '20']);
                        assert.equal(await doses.evaluateAll(inputs => inputs.every(input => input.readOnly)), true);
                    }
                    assert.deepEqual(errors, []);
                    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true);
                    if (process.env.QUOTATION_SCREENSHOTS) {
                        await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `preparation-concentrations-${category}-${unit}-${width}.png`), fullPage: true });
                    }
                    await page.close();
                }
            }
        }
    } finally { await browser.close(); }
});
