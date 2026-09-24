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
const script = buildSync({ stdin: {
    contents: `import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js';
        import './resources/js/request-navigation.js';
        import './resources/js/request-quotations.js';
        import './resources/js/table-column-filters.js';
        import './resources/js/fixed-table-scrollbar.js';
        Alpine.start();`, resolveDir: root,
}, bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;

function screen(query = '', capture = false) {
    const html = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/request-quotations.php', query, capture ? 'capture' : ''], { cwd: root, encoding: 'utf8' });
    return `<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style>
        <div x-data="{ open: false }">${html}</div><script>${script}</script>`;
}

async function setDelivery(page, row, index, value) {
    await row.locator('[data-delivery-button]').nth(index).click();
    const picker = page.locator('[data-quotation-row-picker]');
    if (value) {
        await picker.locator('[data-picker-date]').fill(value);
        await picker.getByRole('button', { name: 'Aplicar', exact: true }).click();
    } else await picker.getByRole('button', { name: 'Borrar', exact: true }).click();
    await picker.waitFor({ state: 'hidden' });
}

async function assertMedicationColumns(mixture) {
    const table = mixture.locator('.qw-table');
    const headings = await table.locator('thead th').all();
    const rows = await table.locator('[data-qw-items] tr, [data-qw-entry]').all();
    assert.equal(headings.length, 5);
    assert.equal(await table.locator('[data-qw-entry]').count(), 1);
    const requirementHeadings = await mixture.locator('.qw-requirement-table thead th').all();
    assert.equal(requirementHeadings.length, 5);
    assert.equal(await mixture.locator('[data-qw-requirement-heading]').textContent(), 'Concentraci\u00f3n solicitada');
    for (let i = 0; i < headings.length; i++) {
        const heading = await headings[i].boundingBox(), requirement = await requirementHeadings[i].boundingBox();
        assert.ok(Math.abs(heading.x - requirement.x) < 1 && Math.abs(heading.width - requirement.width) < 1,
            'Requirements align with the medication breakdown columns');
    }
    for (const row of rows) {
        const cells = await row.locator('td').all();
        assert.equal(cells.length, headings.length);
        for (let i = 0; i < cells.length; i++) {
            const heading = await headings[i].boundingBox(), cell = await cells[i].boundingBox();
            assert.ok(Math.abs(heading.x - cell.x) < 1 && Math.abs(heading.width - cell.width) < 1,
                `Medication column ${i + 1} aligns with its header`);
        }
    }
    const entry = table.locator('[data-qw-entry]');
    const cell = await entry.locator('td').first().boundingBox();
    for (const control of await entry.locator('[data-qw-add], [data-qw-search]').all()) {
        const box = await control.boundingBox();
        assert.ok(box.x >= cell.x && box.x + box.width <= cell.x + cell.width,
            'Both medication entry controls stay inside the medication column');
    }
    assert.deepEqual(await entry.locator('td').allTextContents().then(cells => cells.slice(1)), ['', '', '', '']);
}

test('commercial wizard chooses category first, locks hospital tariffs and reviews server totals on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen('', true);
    const product = { id: 1, name: 'Medicamento de prueba', brand: 'Marca de prueba', presentation: 'Frasco de prueba', unit: 'mg', unit_price: 2, content: 100, vat: true };
    try {
        for (const width of [1440, 1056, 768, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [], previews = [], posts = [], catalogs = [];
            let genericMissing = false, delayCatalog = null;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const request = route.request(), url = new URL(request.url());
                if (url.pathname.endsWith('/opciones')) {
                    catalogs.push(Object.fromEntries(url.searchParams));
                    if (delayCatalog) await delayCatalog;
                    const generic = url.searchParams.get('no_commercial_relationship') === '1';
                    if (generic && genericMissing) return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'No hay una lista base/generica configurada para esta categoria.' }) });
                    const nutrition = url.searchParams.get('category') === 'nutricionales';
                    const bottle = generic ? url.searchParams.get('billing_mode') === 'frasco' : !nutrition;
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ hospital_id: 1, institutions: [{ id: 1, nombre: 'Institucion Uno' }],
                        concentration_unit: nutrition ? 'ml' : 'mg', billing_mode: bottle ? 'frasco' : 'unit',
                        price_list: { id: generic ? 2 : 1, name: generic ? 'Lista base de prueba' : 'Lista asignada de prueba', source: generic ? 'generic' : 'hospital' },
                        products: [{ ...product, unit: bottle ? 'frasco' : nutrition ? 'ml' : 'mg', unit_price: bottle ? 200 : 2 }], charges: [{ name: 'Servicio', total: 15, vat_included: true }] }) });
                }
                if (url.pathname.endsWith('/revisar')) {
                    previews.push(request.postDataJSON());
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ pricing_token: 'a'.repeat(64),
                        document: { date: '23 de septiembre de 2026', date_iso: '2026-09-23', total_in_words: 'Cuatrocientos setenta y nueve pesos 00/100 M.N.' }, pricing_snapshot: {
                        price_list: { id: 1, name: 'Lista asignada de prueba' }, billing_mode: 'frasco', total: 479,
                        lines: [{ description: product.name, presentation: product.presentation, quantity: 2, unit: 'frasco', unit_price: 200, vat: 64, total: 464 },
                            { description: 'Servicio', presentation: '', quantity: 1, unit: 'servicio', unit_price: 15, vat: 0, total: 15 }],
                    } }) });
                }
                if (request.method() === 'POST') {
                    posts.push(request.postDataJSON());
                    return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ errors: { pricing_token: ['Revisa nuevamente la cotizacion: los precios cambiaron.'] } }) });
                }
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            const dialog = page.locator('[data-quote-wizard]');
            const open = page.getByRole('button', { name: 'Nueva Cotizacion', exact: true });
            await open.click();
            assert.equal(await dialog.getByRole('button', { name: 'Continuar', exact: true }).isDisabled(), true);
            assert.equal(await dialog.getByRole('radio').count(), 3);
            assert.equal(await dialog.locator('[data-qw-seller]').isVisible(), false);
            assert.equal(await page.locator('[data-quotation-dialog]').isVisible(), false);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-wizard-categories-${width}.png`) });
            for (const [category, unit] of [['nutricionales', 'mL'], ['oncologicos', 'mg'], ['antibioticos', 'mg']]) {
                await dialog.locator(`[name=category][value=${category}]`).check();
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                const seller = dialog.locator('.qw-header [name=seller_id]');
                await seller.selectOption({ label: 'Vendedora Prueba' });
                const sellerId = await seller.inputValue();
                const headerBounds = await dialog.locator('.qw-header').boundingBox();
                const sellerBounds = await seller.boundingBox();
                assert.ok(sellerBounds.y >= headerBounds.y && sellerBounds.y + sellerBounds.height <= headerBounds.y + headerBounds.height);
                if (width > 1000) {
                    const controls = await dialog.locator('.qw-hospital-row select, .qw-hospital-row .qw-toggle, .qw-hospital-row .qw-billing').all();
                    const bounds = [];
                    for (const control of controls) bounds.push(await control.boundingBox());
                    assert.equal(bounds.length, 4);
                    for (let index = 1; index < bounds.length; index++) {
                        assert.ok(Math.abs(bounds[index].y - bounds[0].y) <= 1, 'All hospital controls align in one row');
                        assert.ok(bounds[index - 1].x + bounds[index - 1].width < bounds[index].x, 'Hospital controls do not overlap');
                    }
                    const badgeBounds = await dialog.locator('[data-qw-badge]').boundingBox();
                    const closeBounds = await dialog.locator('[data-qw-close]').boundingBox();
                    assert.ok(sellerBounds.x + sellerBounds.width < badgeBounds.x);
                    assert.ok(badgeBounds.x + badgeBounds.width < closeBounds.x);
                }
                if (category === 'nutricionales' && process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-layout-empty-${width}.png`) });
                await dialog.locator('[name=institution_id]').selectOption('1');
                await dialog.locator('[name=hospital_id]').selectOption('1');
                await dialog.getByText('Lista asignada: Lista asignada de prueba', { exact: true }).waitFor();
                await assertMedicationColumns(dialog.locator('[data-qw-mixture]'));
                assert.equal(await dialog.getByRole('radio', { name: `Por ${unit}`, exact: true }).isDisabled(), true);
                assert.equal(await dialog.getByRole('radio', { name: 'Por frasco', exact: true }).isDisabled(), true);
                await dialog.getByRole('combobox', { name: 'Buscar medicamento' }).fill('no existe');
                await dialog.getByText('Sin coincidencias.', { exact: true }).waitFor();
                await dialog.getByRole('combobox', { name: 'Buscar medicamento' }).fill('Medicamento');
                await dialog.getByRole('option', { name: /Medicamento de prueba/ }).click();
                const bottle = category !== 'nutricionales';
                const quantity = dialog.getByRole('spinbutton', { name: bottle ? 'Cantidad de frascos de Medicamento de prueba' : `Concentracion de Medicamento de prueba en ${unit}` });
                assert.equal(await dialog.locator('[data-qw-quantity-heading]').textContent(), bottle ? 'Cantidad de frascos' : 'Concentraci\u00f3n disponible');
                assert.equal(await quantity.getAttribute('min'), bottle ? '1' : '0.0001');
                assert.equal(await quantity.getAttribute('step'), bottle ? '1' : 'any');
                if (bottle) {
                    const column = await dialog.locator('[data-qw-quantity-heading]').boundingBox();
                    assert.ok(Math.abs(column.width - 150) <= 1, 'Bottle quantities use a compact fixed-width column');
                    await quantity.fill('1000000');
                    assert.equal(await quantity.evaluate(input => {
                        const style = getComputedStyle(input);
                        const context = document.createElement('canvas').getContext('2d');
                        context.font = style.font;
                        return context.measureText(input.value).width + parseFloat(style.paddingLeft) + parseFloat(style.paddingRight) + 18 <= input.clientWidth;
                    }), true, 'The maximum bottle count fits with the number stepper');
                    const previousPreviews = previews.length;
                    await quantity.fill('1.5');
                    await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                    assert.equal(await quantity.evaluate(input => input.validity.stepMismatch), true);
                    assert.equal(previews.length, previousPreviews);
                }
                await quantity.fill(bottle ? '2' : '101');
                assert.equal(await dialog.locator('.qw-quantity span').textContent(), bottle ? 'frascos' : unit);
                assert.equal(await dialog.getByRole('combobox', { name: 'Buscar medicamento' }).inputValue(), '');
                assert.equal(await dialog.locator('[data-qw-results]').isVisible(), false);
                assert.equal(await dialog.locator('[data-qw-items] tr').count(), 1);
                await assertMedicationColumns(dialog.locator('[data-qw-mixture]'));
                assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), category === 'nutricionales' ? '$249.32 MXN' : '$479.00 MXN');
                if (category === 'oncologicos') {
                    await dialog.locator('[name=no_commercial_relationship]').check();
                    await dialog.getByText('Lista base / generica: Lista base de prueba', { exact: true }).waitFor();
                    assert.equal(await dialog.getByRole('radio', { name: 'Por mg', exact: true }).isEnabled(), true);
                    await dialog.getByRole('radio', { name: 'Por mg', exact: true }).check();
                    await page.waitForFunction(() => !document.querySelector('[data-qw-search]').disabled);
                    const concentration = dialog.getByRole('spinbutton', { name: 'Concentracion de Medicamento de prueba en mg' });
                    assert.equal(await concentration.inputValue(), '');
                    await concentration.fill('101');
                    assert.equal(await dialog.locator('[data-qw-items] td').nth(2).textContent(), '$2.0000 / mg');
                    genericMissing = true;
                    await dialog.getByRole('radio', { name: 'Por frasco', exact: true }).check();
                    await dialog.getByRole('alert').getByText('No hay una lista base/generica configurada para esta categoria.').waitFor();
                    assert.equal(await dialog.getByRole('button', { name: 'Continuar', exact: true }).isDisabled(), true);
                    genericMissing = false;
                    await dialog.locator('[name=no_commercial_relationship]').uncheck();
                    await dialog.getByText('Lista asignada: Lista asignada de prueba', { exact: true }).waitFor();
                    assert.equal(await quantity.inputValue(), '');
                    await quantity.fill('2');
                    if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-wizard-medications-${width}.png`) });
                    const patient = dialog.getByLabel('Nombre (opcional)', { exact: true });
                    const paternal = dialog.getByLabel('Apellido paterno (opcional)', { exact: true });
                    const maternal = dialog.getByLabel('Apellido materno (opcional)', { exact: true });
                    const platformId = dialog.getByLabel('ID de la plataforma (opcional)', { exact: true });
                    const observations = dialog.getByLabel('Observaciones', { exact: true });
                    await patient.fill('Maria Elena');
                    await paternal.fill('Garcia');
                    await maternal.fill('Lopez');
                    await platformId.fill('000123-A');
                    await observations.fill('Primera observacion\nSegunda observacion\nTercera observacion');
                    await observations.scrollIntoViewIfNeeded();
                    const patientBounds = await patient.boundingBox(), observationsBounds = await observations.boundingBox();
                    const paternalBounds = await paternal.boundingBox(), maternalBounds = await maternal.boundingBox(), idBounds = await platformId.boundingBox();
                    assert.equal(patientBounds.height, 42);
                    for (const bounds of [paternalBounds, maternalBounds, idBounds]) {
                        assert.equal(bounds.height, patientBounds.height);
                        assert.ok(Math.abs(bounds.width - patientBounds.width) <= 1);
                    }
                    assert.equal(observationsBounds.height, 60);
                    assert.equal(observationsBounds.x, patientBounds.x);
                    assert.ok(observationsBounds.y >= idBounds.y + idBounds.height);
                    if (width > 700) {
                        assert.equal(paternalBounds.y, patientBounds.y);
                        assert.equal(idBounds.y, maternalBounds.y);
                        assert.ok(maternalBounds.y >= patientBounds.y + patientBounds.height);
                        assert.equal(maternalBounds.x, patientBounds.x);
                        assert.equal(idBounds.x, paternalBounds.x);
                        assert.ok(Math.abs(observationsBounds.width - (idBounds.x + idBounds.width - patientBounds.x)) <= 1);
                    } else {
                        assert.ok(paternalBounds.y >= patientBounds.y + patientBounds.height);
                        assert.ok(maternalBounds.y >= paternalBounds.y + paternalBounds.height);
                        assert.ok(idBounds.y >= maternalBounds.y + maternalBounds.height);
                        assert.ok(Math.abs(observationsBounds.width - patientBounds.width) <= 1);
                    }
                    assert.equal(await observations.evaluate(element => getComputedStyle(element).resize), 'none');
                    assert.match(await observations.inputValue(), /Primera observacion\nSegunda observacion/);
                    if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-extra-fields-${width}.png`) });
                    await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                    await dialog.locator('[data-qw-step="2"]').waitFor();
                    assert.equal(await dialog.locator('.qw-document h3, .qw-document-subtitle').count(), 0);
                    assert.equal(await dialog.getByText('Medicamento y servicio de preparaci\u00f3n', { exact: true }).count(), 0);
                    assert.equal(await dialog.getByRole('button', { name: 'Generar cotizaci\u00f3n', exact: true }).isEnabled(), true);
                    assert.equal(await dialog.locator('[data-qw-seller]').isVisible(), false);
                    assert.equal(await seller.isDisabled(), true);
                    assert.equal(await dialog.locator('[data-qw-total]').textContent(), '$479.00 MXN');
                    assert.equal(await dialog.locator('[data-qw-recipient]').textContent(), 'Hospital de prueba');
                    assert.equal(await dialog.locator('[data-qw-date]').textContent(), '23 de septiembre de 2026');
                    assert.equal(await dialog.locator('[data-qw-date]').getAttribute('datetime'), '2026-09-23');
                    assert.equal(await dialog.locator('[data-qw-total-words]').textContent(), 'Cuatrocientos setenta y nueve pesos 00/100 M.N.');
                    assert.deepEqual(await dialog.locator('.qw-document-table thead th').allTextContents(), ['Cantidad', 'Unidad', 'Descripci\u00f3n', 'Precio unitario', 'Importe']);
                    assert.equal(await dialog.locator('[data-qw-review-lines] tr').first().locator('td').nth(1).textContent(), 'Frasco');
                    assert.equal(await dialog.locator('[data-qw-review-lines] tr').first().locator('td').last().textContent(), '$464.00');
                    assert.equal(await dialog.locator('[data-qw-subtotal]').textContent(), '$415.00');
                    assert.equal(await dialog.locator('[data-qw-tax]').textContent(), '$64.00');
                    assert.match(await dialog.locator('[data-qw-considerations]').textContent(), /Servicio: \$15.00 MXN/);
                    assert.match(await dialog.locator('[data-qw-observations]').textContent(), /Primera observacion/);
                    assert.equal(await dialog.locator('.qw-document').evaluate(element => element.scrollWidth <= element.clientWidth + 1), true);
                    const brand = dialog.locator('.qw-document-brand');
                    const logo = brand.getByAltText('Logotipo de PROMESA');
                    assert.equal(await logo.evaluate(element => element.complete && element.naturalWidth === 1448), true);
                    assert.equal(await brand.locator('.quotation-brand-details strong').textContent(), 'Central de Mezclas Est\u00e9riles PROMESA');
                    assert.equal(await brand.locator('.quotation-brand-details p').count(), 3);
                    assert.match(await brand.textContent(), /RFC:.*Tel\./s);
                    assert.equal(await dialog.locator('.qw-document-signature strong').textContent(), 'PROMESA');
                    const logoFrame = await brand.locator('.quotation-brand-logo').boundingBox();
                    const issuerDetails = await brand.locator('.quotation-brand-details').boundingBox();
                    assert.ok(issuerDetails.x >= logoFrame.x + logoFrame.width || issuerDetails.y >= logoFrame.y + logoFrame.height);
                    assert.equal(previews.at(-1).category, 'oncologicos');
                    assert.equal(previews.at(-1).seller_id, sellerId);
                    assert.equal(previews.at(-1).patient_name, 'Maria Elena');
                    assert.equal(previews.at(-1).patient_paternal_surname, 'Garcia');
                    assert.equal(previews.at(-1).patient_maternal_surname, 'Lopez');
                    assert.equal(previews.at(-1).patient_platform_id, '000123-A');
                    assert.equal(previews.at(-1).billing_mode, undefined);
                    assert.equal(previews.at(-1).mixture_count, 1);
                    assert.deepEqual(previews.at(-1).items, [{ presentation_id: 1, mixture_number: 1, bottle_count: 2 }]);
                    assert.doesNotMatch(await dialog.locator('[data-qw-review-lines]').textContent(), /mg|mL/);
                    if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-wizard-review-${width}.png`) });
                    await dialog.getByRole('button', { name: 'Guardar borrador', exact: true }).click();
                    await dialog.getByRole('alert').getByText('Revisa nuevamente la cotizacion: los precios cambiaron.').waitFor();
                    assert.equal(await quantity.inputValue(), '2');
                    assert.equal(await seller.isEnabled(), true);
                    assert.equal(await seller.inputValue(), sellerId);
                    assert.equal(posts.at(-1).seller_id, sellerId);
                    assert.equal(posts.at(-1).pricing_token, 'a'.repeat(64));
                    assert.equal(posts.at(-1).action, 'save');
                    assert.equal(posts.at(-1).flow, 'commercial');
                    assert.equal(posts.at(-1).patient_platform_id, '000123-A');
                    assert.equal(await patient.inputValue(), 'Maria Elena');
                    assert.equal(await paternal.inputValue(), 'Garcia');
                    assert.equal(await maternal.inputValue(), 'Lopez');
                    assert.equal(await platformId.inputValue(), '000123-A');
                }
                await dialog.getByRole('button', { name: 'Eliminar Medicamento de prueba', exact: true }).click();
                assert.equal(await dialog.locator('[data-qw-items] tr').count(), 0);
                assert.equal(await dialog.getByRole('button', { name: 'Continuar', exact: true }).isDisabled(), true);
                const bounds = await dialog.boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width && bounds.y >= 0 && bounds.y + bounds.height <= 900);
                const geometry = await dialog.evaluate(el => ({ client: el.clientWidth, scroll: el.scrollWidth,
                    outside: [...el.querySelectorAll('*')].filter(node => node.getBoundingClientRect().right > el.getBoundingClientRect().right)
                        .slice(0, 8).map(node => ({ tag: node.tagName, class: node.className, right: node.getBoundingClientRect().right })) }));
                assert.ok(geometry.scroll <= geometry.client + 1, JSON.stringify({ width, category, geometry }));
                const footer = await dialog.locator('.qw-footer').boundingBox();
                assert.ok(footer.y >= bounds.y && footer.y + footer.height <= bounds.y + bounds.height);
                await dialog.getByRole('button', { name: 'Atras', exact: true }).click();
            }
            assert.equal(catalogs.at(-1).category, 'antibioticos');
            let release;
            delayCatalog = new Promise(resolve => { release = resolve; });
            await dialog.locator('[name=category][value=oncologicos]').check();
            await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
            assert.equal(await dialog.getByRole('combobox', { name: 'Buscar medicamento' }).isDisabled(), true);
            await dialog.getByRole('button', { name: 'Cerrar nueva cotizacion', exact: true }).click();
            release(); delayCatalog = null;
            await open.click();
            assert.equal(await dialog.locator('[name=category]:checked').count(), 0);
            assert.equal(await dialog.locator('[data-qw-items] tr').count(), 0);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('medication autocomplete stays empty until typing and offers at most five priced matches below the field', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen('', true);
    const products = Array.from({ length: 7 }, (_, index) => ({ id: index + 1, name: `\u00c1cido fol\u00ednico ${index + 1}`,
        brand: 'Marca con nombre comercial largo', presentation: 'Frasco ampula 50 mg / 4 mL', unit: 'frasco',
        unit_price: index === 0 ? null : 2950 + index, content: 50, vat: false }));
    products.push({ id: 8, name: 'Azacitidina', brand: 'Otra marca', presentation: '100 mg', unit: 'mg', unit_price: 324, content: 100, vat: false });
    try {
        for (const width of [1440, 768, 390, 320]) {
            const page = await browser.newPage({ viewport: { width, height: 900 }, hasTouch: width < 400 });
            const errors = [], posts = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const request = route.request(), url = new URL(request.url());
                if (request.method() === 'POST') { posts.push(request.postData()); return route.abort(); }
                if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                    price_list: { id: 1, name: 'Lista de prueba' }, billing_mode: 'mixed', products, charges: [],
                }) });
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            const dialog = page.locator('[data-quote-wizard]');
            await dialog.locator('[name=category][value=oncologicos]').check();
            await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
            await dialog.locator('[name=institution_id]').selectOption('1');
            await dialog.locator('[name=hospital_id]').selectOption('1');
            await dialog.getByText('Lista asignada: Lista de prueba', { exact: true }).waitFor();
            const search = dialog.getByRole('combobox', { name: 'Buscar medicamento', exact: true });
            const add = dialog.getByRole('button', { name: 'Agregar medicamento', exact: true });
            assert.equal(await dialog.locator('[data-qw-add]').count(), 1);
            assert.equal(await add.evaluate(button => Boolean(button.compareDocumentPosition(document.querySelector('[data-qw-search]')) & Node.DOCUMENT_POSITION_FOLLOWING)), true);
            const addBounds = await add.boundingBox(), searchBounds = await search.boundingBox();
            const headerBounds = await dialog.locator('.qw-table thead').boundingBox();
            await assertMedicationColumns(dialog.locator('[data-qw-mixture]'));
            assert.ok(addBounds.y >= headerBounds.y + headerBounds.height, 'The medication entry controls must follow the table header');
            if (Math.abs(addBounds.y - searchBounds.y) <= 1) {
                assert.ok(addBounds.x + addBounds.width < searchBounds.x);
                assert.equal(addBounds.height, searchBounds.height);
            } else assert.ok(addBounds.y + addBounds.height < searchBounds.y);
            const results = dialog.locator('[data-qw-results]');
            const options = dialog.locator('#qw-medication-results-1 [role=option]');
            assert.equal(await results.isVisible(), false);
            await search.focus();
            assert.equal(await search.getAttribute('aria-expanded'), 'false');
            await search.fill('   ');
            assert.equal(await results.isVisible(), false);
            await search.fill('  ACIDO  ');
            assert.equal(await options.count(), 5);
            assert.deepEqual(await options.locator('strong').allTextContents(), products.slice(0, 5).map(product => product.name));
            assert.equal(await options.nth(1).locator('.qw-result-price').textContent(), '$2,951.0000 / frasco');
            assert.equal(await options.first().getAttribute('aria-disabled'), 'true');
            assert.equal(await search.getAttribute('aria-expanded'), 'true');
            await results.scrollIntoViewIfNeeded();
            const inputBounds = await search.boundingBox(), popupBounds = await results.boundingBox();
            assert.ok(Math.abs(popupBounds.x - inputBounds.x) < 2 && Math.abs(popupBounds.width - inputBounds.width) < 2);
            assert.ok(Math.abs(popupBounds.y - (inputBounds.y + inputBounds.height)) < 2);
            assert.ok(popupBounds.x >= 0 && popupBounds.x + popupBounds.width <= width);
            assert.equal(await results.evaluate(element => element.scrollWidth <= element.clientWidth + 1), true);
            for (const row of await options.all()) {
                assert.equal(await row.evaluate(element => {
                    const name = element.firstElementChild.getBoundingClientRect();
                    const price = element.lastElementChild.getBoundingClientRect();
                    const row = element.getBoundingClientRect();
                    return (name.right <= price.left || name.bottom <= price.top)
                        && price.right <= row.right && price.top >= row.top && price.bottom <= row.bottom;
                }), true);
            }
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-autocomplete-${width}.png`) });
            await search.press('ArrowDown');
            assert.equal(await search.getAttribute('aria-activedescendant'), 'qw-mixture-1-option-2');
            await search.press('ArrowDown');
            assert.equal(await search.getAttribute('aria-activedescendant'), 'qw-mixture-1-option-3');
            await search.press('ArrowUp');
            await search.press('Enter');
            assert.equal(await search.inputValue(), '');
            assert.equal(await results.isVisible(), false);
            assert.equal(await dialog.locator('[data-qw-items] tr').count(), 1);
            assert.equal(await add.isEnabled(), true);
            assert.equal(await search.isEnabled(), true);
            assert.equal(await dialog.locator('[data-qw-search]').count(), 1);
            await assertMedicationColumns(dialog.locator('[data-qw-mixture]'));
            const entryAfterAdd = await add.boundingBox();
            const medicationBounds = await dialog.locator('[data-qw-items] tr').last().boundingBox();
            assert.ok(entryAfterAdd.y >= medicationBounds.y + medicationBounds.height, 'A blank medication entry remains after the selected medications');
            assert.match(await dialog.locator('[data-qw-items] tr').textContent(), /fol\u00ednico 2/);
            assert.equal(posts.length, 0, 'Selecting with Enter must not review or save the quotation');
            await add.click();
            assert.equal(await search.evaluate(input => document.activeElement === input), true);
            assert.equal(await results.isVisible(), false);
            await search.fill('acido');
            assert.equal(await options.nth(1).getAttribute('aria-disabled'), 'true');
            await search.press('Escape');
            assert.equal(await results.isVisible(), false);
            assert.equal(await dialog.isVisible(), true);
            await search.press('ArrowDown');
            assert.equal(await search.getAttribute('aria-activedescendant'), 'qw-mixture-1-option-3');
            await search.press('Tab');
            assert.equal(await results.isVisible(), false);
            await search.fill('sin coincidencia');
            assert.equal(await options.count(), 0);
            await dialog.getByText('Sin coincidencias.', { exact: true }).waitFor();
            await search.press('Enter');
            assert.equal(posts.length, 0);
            await search.fill('Azacitidina');
            assert.equal(await options.count(), 1);
            assert.equal(await options.first().locator('.qw-result-price').textContent(), '$324.0000 / mg');
            if (width < 400) await options.first().tap();
            else await options.first().click();
            assert.equal(await dialog.locator('[data-qw-items] tr').count(), 2);
            assert.equal(await results.isVisible(), false);
            assert.equal(await dialog.locator('[data-qw-quantity-heading]').textContent(), 'Cantidad solicitada');
            const amounts = dialog.locator('[data-qw-items] .qw-quantity input');
            assert.equal(await amounts.nth(0).getAttribute('step'), '1');
            assert.equal(await amounts.nth(1).getAttribute('step'), 'any');
            assert.deepEqual(await dialog.locator('.qw-quantity span').allTextContents(), ['frascos', 'mg']);
            await amounts.nth(0).fill('2');
            await amounts.nth(1).fill('1.5');
            assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$6,388.00 MXN');
            await search.fill('acido');
            await search.fill('');
            assert.equal(await results.isVisible(), false);
            assert.equal(await options.count(), 0);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('special unit prices edit only the quotation, restore in drafts and reset when billing changes', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen('', true).replace('data-quotation-flow="clinical"', 'data-quotation-flow="commercial"');
    try {
        for (const width of [1440, 768, 390]) for (const unit of ['frasco', 'mg', 'ml']) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const category = unit === 'ml' ? 'nutricionales' : unit === 'mg' ? 'antibioticos' : 'oncologicos';
            const errors = [], previews = [], saves = [];
            let savedDraft;
            const product = { id: 1, name: 'Medicamento especial', presentation: 'Frasco 100', unit, unit_price: 200, vat: true };
            const snapshot = data => {
                const quantity = data.items[0].bottle_count ?? data.items[0].concentration;
                const price = data.items[0].unit_price_override ?? 200;
                const subtotal = Math.round(quantity * price * 100) / 100;
                const vat = Math.round(subtotal * .16 * 100) / 100;
                return { price_list: { id: 1, name: 'Lista del hospital' }, billing_mode: unit === 'frasco' ? 'frasco' : 'unit',
                    total: Math.round((subtotal + vat) * 100) / 100, lines: [{ description: product.name, presentation: product.presentation,
                        quantity, unit, unit_price: price, subtotal, vat, total: Math.round((subtotal + vat) * 100) / 100 }] };
            };
            page.on('pageerror', failure => errors.push(failure.message));
            await page.route('**/*', route => {
                const request = route.request(), url = new URL(request.url());
                if (url.pathname.endsWith('/opciones')) {
                    const generic = url.searchParams.get('no_commercial_relationship') === '1';
                    const selectedUnit = generic ? (url.searchParams.get('billing_mode') === 'frasco' ? 'frasco' : category === 'nutricionales' ? 'ml' : 'mg') : unit;
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ products: [{ ...product, unit: selectedUnit }], charges: [],
                        price_list: { id: generic ? 2 : 1, name: generic ? 'Lista base' : 'Lista del hospital' }, billing_mode: selectedUnit === 'frasco' ? 'frasco' : 'unit' }) });
                }
                if (url.pathname.endsWith('/revisar')) {
                    const data = request.postDataJSON(); previews.push(data);
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ pricing_snapshot: snapshot(data), pricing_token: 'c'.repeat(64) }) });
                }
                if (request.method() === 'POST') {
                    savedDraft = request.postDataJSON(); saves.push(savedDraft);
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ redirect_url: '/admin/solicitudes/cotizacion' }) });
                }
                if (url.pathname.endsWith('/cotizacion/1')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                    editable: true, folio: 'COT-000001', update_url: '/admin/solicitudes/cotizacion/1', clinical_data: savedDraft, pricing_snapshot: snapshot(savedDraft) }) });
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            const dialog = page.locator('[data-quote-wizard]');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            await dialog.locator(`[name=category][value=${category}]`).check();
            await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
            await dialog.locator('[name=institution_id]').selectOption('1');
            await dialog.locator('[name=hospital_id]').selectOption('1');
            await dialog.getByText('Lista asignada: Lista del hospital', { exact: true }).waitFor();
            const search = dialog.getByRole('combobox', { name: 'Buscar medicamento' });
            await search.fill('Medicamento especial');
            await dialog.getByRole('listbox', { name: 'Medicamentos disponibles' }).getByRole('option').click();
            const row = dialog.locator('[data-qw-items] tr');
            await row.locator('.qw-quantity input').fill('2');
            assert.match(await row.locator('.qw-price').textContent(), /\$200\.0000/);
            const edit = row.getByRole('button', { name: /^Editar precio unitario/ });
            await edit.click();
            const input = row.getByRole('spinbutton', { name: /^Precio unitario de/ });
            assert.equal(await input.inputValue(), '200');
            for (const invalid of ['', '-1', '0.12345']) {
                await input.fill(invalid);
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                assert.equal(previews.length, 0, 'Invalid prices must not reach the server');
            }
            await input.fill('123.4567');
            await input.press('Enter');
            assert.equal(previews.length, 0, 'Enter applies the price without submitting');
            assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$286.42 MXN');
            await search.fill('Medicamento especial');
            assert.match(await dialog.getByRole('listbox', { name: 'Medicamentos disponibles' }).getByRole('option').textContent(), /\$200\.0000/, 'The catalog retains the original list price');
            await search.press('Escape');
            await edit.click();
            await input.fill('100');
            await input.press('Escape');
            assert.match(await row.locator('.qw-price').textContent(), /123\.4567/);
            await row.getByRole('button', { name: /^Restablecer precio de lista/ }).click();
            assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$464.00 MXN');
            await edit.click();
            await input.fill('123.4567');
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-price-edit-${unit}-${width}.png`) });
            const geometry = await row.locator('.qw-price').evaluate(element => {
                const cell = element.closest('td').getBoundingClientRect();
                return [...element.children].filter(child => !child.hidden).every(child => {
                    const box = child.getBoundingClientRect(); return box.x >= cell.x && box.right <= cell.right;
                });
            });
            assert.equal(geometry, true, 'Price controls stay inside their column');
            await input.press('Enter');
            await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
            await dialog.locator('[data-qw-step="2"]').waitFor();
            assert.equal(previews.at(-1).items[0].unit_price_override, 123.4567);
            assert.equal(await dialog.locator('[data-qw-total]').textContent(), '$286.42 MXN');
            const savedNavigation = page.waitForEvent('framenavigated', frame => frame === page.mainFrame());
            await dialog.getByRole('button', { name: 'Guardar borrador', exact: true }).click();
            await savedNavigation;
            assert.equal(page.url(), 'http://localhost/admin/solicitudes/cotizacion');
            await page.locator('[data-quotation-row]').first().waitFor();
            assert.equal(await page.locator('[data-quotation-row]').count(), 5);
            assert.equal(saves[0].items[0].unit_price_override, 123.4567);
            await page.getByRole('button', { name: 'Editar COT-000001', exact: true }).click();
            await dialog.getByText('Lista asignada: Lista del hospital', { exact: true }).waitFor();
            assert.match(await row.locator('.qw-price').textContent(), /123\.4567/);
            assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$286.42 MXN');
            await dialog.getByRole('switch').check();
            await dialog.getByText('Lista base / generica: Lista base', { exact: true }).waitFor();
            assert.match(await row.locator('.qw-price').textContent(), /200\.0000/);
            assert.equal(await row.getByRole('button', { name: /^Restablecer precio de lista/ }).isVisible(), false);
            assert.equal(await dialog.evaluate(element => element.scrollWidth <= element.clientWidth + 1), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('commercial drafts restore bottle counts including older concentration drafts and submit only after review', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen('', true).replace('data-quotation-flow="clinical"', 'data-quotation-flow="commercial"');
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    const errors = [], saves = [];
    let oldDraft = true;
    page.on('pageerror', error => errors.push(error.message));
    const snapshot = { price_list: { id: 2, name: 'Base nutricional' }, billing_mode: 'frasco', total: 200,
        lines: [{ description: 'Componente', presentation: '100 mL', quantity: 1, unit: 'frasco', unit_price: 200, vat: 0, total: 200 }] };
    try {
        await page.route('**/*', route => {
            const request = route.request(), url = new URL(request.url());
            if (request.method() === 'PUT') {
                saves.push(request.postDataJSON());
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ redirect_url: '/admin/solicitudes/cotizacion' }) });
            }
            if (url.pathname.endsWith('/cotizacion/1')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                folio: 'COT-000001', editable: true, update_url: '/admin/solicitudes/cotizacion/1', seller_id: 900, seller_name: 'Vendedor anterior',
                pricing_snapshot: snapshot, document: { date: '22 de septiembre de 2026', date_iso: '2026-09-22' },
                clinical_data: { flow: 'commercial', category: 'nutricionales', institution_id: 1, hospital_id: 1, no_commercial_relationship: true,
                    billing_mode: 'frasco', patient_name: oldDraft ? 'Paciente anterior completo' : 'Maria Elena', observations: 'Cotizacion previa',
                    ...(oldDraft ? {} : { patient_paternal_surname: 'Garcia', patient_maternal_surname: 'Lopez', patient_platform_id: '000123-A' }),
                    items: [{ presentation_id: 1, ...(oldDraft ? { concentration: 50, unit: 'ml' } : { bottle_count: 1, unit: 'frasco' }) }] },
            }) });
            if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                price_list: snapshot.price_list, billing_mode: 'frasco', products: [{ id: 1, name: 'Componente', presentation: '100 mL', unit: 'frasco', unit_price: 200, content: 100, vat: false }], charges: [],
            }) });
            if (url.pathname.endsWith('/revisar')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ pricing_snapshot: snapshot, pricing_token: 'b'.repeat(64),
                document: { date: '23 de septiembre de 2026', date_iso: '2026-09-23', total_in_words: 'Doscientos pesos 00/100 M.N.' } }) });
            if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
            if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
            return route.abort();
        });
        for (const legacy of [true, false]) {
            oldDraft = legacy;
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            await page.getByRole('button', { name: 'Editar COT-000001', exact: true }).click();
            const dialog = page.locator('[data-quote-wizard]');
            await dialog.getByText('Lista base / generica: Base nutricional', { exact: true }).waitFor();
            assert.equal(await page.locator('[data-quotation-dialog]').isVisible(), false);
            assert.equal(await dialog.getByRole('switch').isChecked(), true);
            assert.equal(await dialog.getByRole('radio', { name: 'Por frasco', exact: true }).isChecked(), true);
            assert.equal(await dialog.locator('[name=seller_id]').inputValue(), '900');
            assert.equal(await dialog.getByLabel('Nombre (opcional)', { exact: true }).inputValue(), legacy ? 'Paciente anterior completo' : 'Maria Elena');
            assert.equal(await dialog.getByLabel('Apellido paterno (opcional)', { exact: true }).inputValue(), legacy ? '' : 'Garcia');
            assert.equal(await dialog.getByLabel('Apellido materno (opcional)', { exact: true }).inputValue(), legacy ? '' : 'Lopez');
            assert.equal(await dialog.getByLabel('ID de la plataforma (opcional)', { exact: true }).inputValue(), legacy ? '' : '000123-A');
            assert.equal(await dialog.getByRole('spinbutton', { name: 'Cantidad de frascos de Componente' }).inputValue(), '1');
            await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
            await dialog.locator('[data-qw-step="2"]').waitFor();
            assert.equal(await dialog.locator('[data-qw-folio]').textContent(), 'Folio: COT-000001');
            assert.equal(await dialog.locator('[data-qw-date]').textContent(), '22 de septiembre de 2026');
            assert.equal(await dialog.locator('[data-qw-tax-summary]').isVisible(), false);
            assert.equal(await dialog.locator('[data-qw-total-words]').textContent(), 'Doscientos pesos 00/100 M.N.');
            const savedNavigation = page.waitForEvent('framenavigated', frame => frame === page.mainFrame());
            await dialog.getByRole('button', { name: 'Generar cotizaci\u00f3n', exact: true }).click();
            await savedNavigation;
            assert.equal(page.url(), 'http://localhost/admin/solicitudes/cotizacion');
            await page.locator('[data-quotation-row]').first().waitFor();
            assert.equal(await page.locator('[data-quotation-row]').count(), 5);
            assert.equal(saves.at(-1).billing_mode, 'frasco');
            assert.equal(saves.at(-1).no_commercial_relationship, true);
            assert.equal(saves.at(-1).seller_id, '900');
            assert.equal(saves.at(-1).patient_name, legacy ? 'Paciente anterior completo' : 'Maria Elena');
            assert.equal(saves.at(-1).patient_paternal_surname, legacy ? '' : 'Garcia');
            assert.equal(saves.at(-1).patient_maternal_surname, legacy ? '' : 'Lopez');
            assert.equal(saves.at(-1).patient_platform_id, legacy ? '' : '000123-A');
            assert.equal(saves.at(-1).action, 'send');
            assert.equal(saves.at(-1).pricing_token, 'b'.repeat(64));
            assert.equal(saves.at(-1).mixture_count, 1);
            assert.deepEqual(saves.at(-1).items, [{ presentation_id: 1, mixture_number: 1, bottle_count: 1 }]);
        }
        assert.equal(saves.length, 2);
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});

test('quotation sends a named PDF by email and downloads it for WhatsApp without changing status', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen();
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            const posts = [];
            let pdfAttempts = 0;
            page.on('pageerror', error => errors.push(error.message));
            await page.addInitScript(() => {
                window.openedMessages = [];
                window.open = (...args) => { window.openedMessages.push(args); return null; };
            });
            await page.route('**/*', route => {
                const request = route.request();
                const url = new URL(request.url());
                if (/\/cotizacion\/\d+\/pdf$/.test(url.pathname)) {
                    pdfAttempts++;
                    return route.fulfill({ status: pdfAttempts === 1 ? 403 : 200,
                        contentType: pdfAttempts === 1 ? 'text/html' : 'application/pdf',
                        body: pdfAttempts === 1 ? 'Forbidden' : '%PDF-1.4\nquotation fixture\n%%EOF' });
                }
                if (url.pathname.endsWith('/cotizacion/1/correo') && request.method() === 'POST') {
                    posts.push(request.postDataJSON());
                    return route.fulfill({ status: posts.length === 1 ? 503 : 200, contentType: 'application/json',
                        body: JSON.stringify({ message: posts.length === 1 ? 'Configura el SMTP de produccion.' : 'Cotizacion enviada al servidor de correo para su entrega.' }) });
                }
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            assert.equal(await page.locator('[data-quotation-dialog]').count(), 0, 'Sending works without the capture form');
            const row = page.locator('[data-quotation-row]').filter({ hasText: 'COT-000001' });
            const originalStatus = await row.locator('td').nth(9).textContent();
            const opener = page.getByRole('button', { name: 'Enviar COT-000001', exact: true });
            await opener.click();
            const dialog = page.getByRole('dialog', { name: 'Enviar COT-000001', exact: true });
            const email = dialog.getByLabel('Correo del destinatario *', { exact: true });
            const note = dialog.getByLabel('Mensaje (opcional)', { exact: true });
            const submit = dialog.getByRole('button', { name: 'Enviar correo', exact: true });
            await dialog.waitFor({ state: 'visible' });
            assert.equal(await email.evaluate(el => document.activeElement === el), true);
            assert.equal(await dialog.locator('[data-send-filename]').textContent(), 'COT-000001.pdf');
            assert.equal(await dialog.locator('[data-send-download]').getAttribute('download'), 'COT-000001.pdf');
            assert.match(await dialog.locator('[data-send-download]').getAttribute('href'), /\/cotizacion\/1\/pdf$/);
            assert.equal(await dialog.locator('[data-send-summary]').count(), 0);
            await submit.click();
            assert.equal(posts.length, 0);
            await email.fill('no-es-correo');
            await submit.click();
            assert.equal(posts.length, 0);
            await email.fill('destino@example.test');
            await note.fill('Buenos dias: revisi\u00f3n & precios + IVA.');
            await submit.click();
            await dialog.getByRole('alert').getByText('Configura el SMTP de produccion.').waitFor();
            assert.equal(await submit.isEnabled(), true);
            assert.equal(await email.inputValue(), 'destino@example.test');
            await submit.evaluate(button => { button.click(); button.click(); });
            await dialog.getByRole('status').getByText('Cotizacion enviada al servidor de correo para su entrega.').waitFor();
            assert.equal(posts.length, 2, 'Double click must not send twice');
            assert.deepEqual(posts[1], { email: 'destino@example.test', note: 'Buenos dias: revisi\u00f3n & precios + IVA.' });
            assert.equal(await submit.isDisabled(), true);
            assert.equal(await row.locator('td').nth(9).textContent(), originalStatus);

            await dialog.getByText('WhatsApp', { exact: true }).click();
            const phone = dialog.getByLabel('Telefono con codigo de pais *', { exact: true });
            assert.equal(await email.isVisible(), false);
            const whatsapp = dialog.getByRole('button', { name: 'Descargar PDF y abrir WhatsApp', exact: true });
            await dialog.getByText('PDF no disponible', { exact: true }).waitFor();
            assert.equal(await whatsapp.isDisabled(), true);
            assert.equal(await page.evaluate(() => window.openedMessages.length), 0);
            await dialog.getByRole('button', { name: 'Reintentar PDF', exact: true }).click();
            await dialog.getByText('Documento PDF', { exact: true }).waitFor();
            assert.equal(await whatsapp.isEnabled(), true);
            await phone.fill('0001');
            await whatsapp.click();
            assert.equal(await page.evaluate(() => window.openedMessages.length), 0);
            await phone.fill('+52 (55) 1234-5678');
            const downloaded = page.waitForEvent('download');
            await whatsapp.click();
            assert.equal((await downloaded).suggestedFilename(), 'COT-000001.pdf');
            const opened = await page.evaluate(() => window.openedMessages);
            assert.equal(opened.length, 1);
            const url = new URL(opened[0][0]);
            assert.equal(url.origin, 'https://wa.me');
            assert.equal(url.pathname, '/525512345678');
            assert.equal(url.searchParams.get('text'), 'Buenos dias: revisi\u00f3n & precios + IVA.\n\nCotizacion COT-000001');
            assert.match(await dialog.locator('[data-send-status]').textContent(), /Adjunta COT-000001.pdf/);
            assert.deepEqual(opened[0].slice(1), ['_blank', 'noopener,noreferrer']);
            assert.equal(posts.length, 2, 'WhatsApp must not call the email endpoint');
            assert.equal(await row.locator('td').nth(9).textContent(), originalStatus);
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
            assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 900);
            assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            assert.equal(await dialog.locator('svg[data-quotation-icon]').count() > 0, true);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-send-${width}.png`) });
            await page.keyboard.press('Escape');
            await dialog.waitFor({ state: 'hidden' });
            assert.equal(await opener.evaluate(el => document.activeElement === el), true);
            await page.getByRole('button', { name: 'Enviar COT-000003', exact: true }).click();
            const other = page.getByRole('dialog', { name: 'Enviar COT-000003', exact: true });
            assert.equal(await other.getByLabel('Correo del destinatario *', { exact: true }).inputValue(), '');
            assert.equal(await other.getByLabel('Mensaje (opcional)', { exact: true }).inputValue(), '');
            assert.equal(await other.locator('[data-send-filename]').textContent(), 'COT-000003.pdf');
            assert.match(await other.locator('[data-send-download]').getAttribute('href'), /\/cotizacion\/3\/pdf$/);
            await other.getByText('WhatsApp', { exact: true }).click();
            await other.getByText('Documento PDF', { exact: true }).waitFor();
            await other.getByLabel('Telefono con codigo de pais *', { exact: true }).fill('+52 5512345678');
            const nextDownload = page.waitForEvent('download');
            await other.getByRole('button', { name: 'Descargar PDF y abrir WhatsApp' }).click();
            assert.equal((await nextDownload).suggestedFilename(), 'COT-000003.pdf');
            await other.getByRole('button', { name: 'Cancelar', exact: true }).click();
            await other.waitFor({ state: 'hidden' });
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('quotation list matches request navigation and filters without layout overflow', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1920, 1440, 768, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.stack));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/admin/solicitudes/cotizacion') {
                    return route.fulfill({ contentType: 'text/html', body: screen(url.search.slice(1)) });
                }
                if (url.pathname === '/img/promesa-logo.png') {
                    return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                }
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            await page.getByRole('heading', { name: 'Lista de Cotizaciones' }).waitFor();
            const categories = page.getByRole('navigation', { name: 'Tipo de solicitudes' });
            const statuses = page.getByRole('navigation', { name: 'Estado de las cotizaciones' });
            assert.equal(await categories.getByRole('link').count(), 4);
            assert.deepEqual(await statuses.getByRole('link').allTextContents(), ['Todas', 'Recibidas', 'Enviadas', 'Autorizadas', 'En preparacion']);
            assert.equal(await page.locator('[data-quotation-row]').count(), 5);
            assert.equal(await page.locator('#request-quotations-table th').count(), 14);
            assert.deepEqual((await page.locator('[data-quotation-row] td:first-child').allTextContents()).map(text => text.trim()),
                ['Antibiotico', 'Nutricional', 'Oncologica', 'Oncologica', 'Oncologica']);
            assert.equal(await page.getByRole('button', { name: 'Filtrar Tipo', exact: true }).count(), 1);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true);
            for (const input of await page.locator('form[aria-label="Filtros de cotizaciones"]').locator('input:not([type="hidden"]), select, button').all()) {
                const box = await input.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width + 1, 'Filter must fit viewport');
            }
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({
                path: path.join(process.env.QUOTATION_SCREENSHOTS, `request-quotations-${width}.png`), fullPage: true,
            });

            await page.getByRole('button', { name: 'Ver COT-000003', exact: true }).click();
            const dialog = page.getByRole('dialog');
            await dialog.waitFor({ state: 'visible' });
            assert.ok((await dialog.innerText()).includes('$95.25 MXN'));
            assert.ok((await dialog.innerText()).includes('Paciente de prueba 3'));
            const dialogBox = await dialog.boundingBox();
            assert.ok(dialogBox.x >= 0 && dialogBox.x + dialogBox.width <= width);
            await page.keyboard.press('Escape');
            await dialog.waitFor({ state: 'hidden' });

            await statuses.getByRole('link', { name: 'Recibidas', exact: true }).click();
            await page.waitForURL('**estado=recibidas**');
            assert.equal(await statuses.getByRole('link', { name: 'Recibidas', exact: true }).getAttribute('aria-current'), 'page');
            assert.equal(await page.locator('[data-quotation-row]').count(), 1);
            assert.ok((await page.locator('[data-quotation-row]').innerText()).includes('COT-000005'));
            await statuses.getByRole('link', { name: 'Todas', exact: true }).click();
            await page.waitForURL('**estado=todas**');

            await page.getByLabel('Institución', { exact: true }).selectOption('1');
            assert.equal(await page.locator('#quotation-hospital option[value="2"]').isDisabled(), true);
            await page.getByLabel('Hospital', { exact: true }).selectOption('1');
            await page.getByLabel('Desde', { exact: true }).fill('2026-09-21');
            await page.getByLabel('Hasta', { exact: true }).fill('2026-09-21');
            await page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click();
            await page.waitForURL('**desde=2026-09-21**');
            assert.equal(await page.locator('[data-quotation-row]').count(), 2);
            await statuses.getByRole('link', { name: 'Enviadas', exact: true }).click();
            await page.waitForURL('**estado=enviadas**');
            assert.equal(await page.locator('[data-quotation-row]').count(), 1);
            await categories.getByRole('link', { name: 'Oncologicas', exact: true }).click();
            await page.waitForURL('**tipo=oncologicos**');
            const params = new URL(page.url()).searchParams;
            assert.equal(params.get('estado'), 'enviadas');
            assert.equal(params.get('hospital_id'), '1');
            assert.equal(params.get('desde'), '2026-09-21');
            await page.getByLabel('Buscar', { exact: true }).fill('NO-EXISTE');
            await page.getByRole('button', { name: 'Aplicar filtros', exact: true }).click();
            await page.getByText('No hay cotizaciones para los filtros seleccionados.').waitFor();
            await page.getByRole('link', { name: 'Limpiar', exact: true }).click();
            await page.waitForURL('http://localhost/admin/solicitudes/cotizacion?tipo=oncologicos');
            assert.equal(await page.locator('[data-quotation-row]').count(), 3);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('numbered mixtures have independent medication tables, per-mixture charges, combined totals and persistent drafts', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen('', true).replace('data-quotation-flow="clinical"', 'data-quotation-flow="commercial"');
    try {
        for (const [category, unit] of [['oncologicos', 'frasco'], ['antibioticos', 'mg'], ['nutricionales', 'ml']]) {
            for (const width of [1440, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 1100 } });
                const errors = [], previews = [];
                let saved;
                const products = [1, 2].map(id => ({ id, name: `Medicamento ${id === 1 ? 'A' : 'B'}`, brand: 'Marca de prueba',
                    presentation: 'Frasco de prueba', unit, unit_price: id === 1 ? 10 : 5, content: 100, vat: false }));
                const snapshot = data => {
                    const lines = data.items.map(item => {
                        const product = products[item.presentation_id - 1], quantity = item.bottle_count ?? item.concentration;
                        const price = item.unit_price_override ?? product.unit_price;
                        return { description: product.name, presentation: product.presentation, unit, quantity, unit_price: price,
                            mixture_number: item.mixture_number, mixtures: 1, vat: 0, subtotal: quantity * price, total: quantity * price };
                    });
                    for (let number = 1; number <= data.mixture_count; number++) lines.push({ description: 'Preparacion', presentation: '',
                        unit: 'servicio', quantity: 1, unit_price: 7, mixture_number: number, mixtures: 1, subtotal: 7, vat: 0, total: 7 });
                    return { lines, total: lines.reduce((sum, line) => sum + line.total, 0), mixtures: data.mixture_count,
                        requirements: (data.requirements || []).map(requirement => ({ ...requirement, unit: category === 'nutricionales' ? 'ml' : 'mg' })),
                        price_list: { id: 1, name: 'Lista del hospital' }, billing_mode: unit === 'frasco' ? 'frasco' : 'unit' };
                };
                page.on('pageerror', error => errors.push(error.message));
                await page.route('**/*', route => {
                    const request = route.request(), url = new URL(request.url());
                    if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ products,
                        charges: [{ name: 'Preparacion', total: 7 }], price_list: { id: 1, name: 'Lista del hospital' }, billing_mode: unit === 'frasco' ? 'frasco' : 'unit' }) });
                    if (url.pathname.endsWith('/revisar')) {
                        const data = request.postDataJSON(); previews.push(data);
                        return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ pricing_snapshot: snapshot(data), pricing_token: 'd'.repeat(64) }) });
                    }
                    if (request.method() === 'POST') {
                        saved = request.postDataJSON();
                        return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ redirect_url: '/admin/solicitudes/cotizacion' }) });
                    }
                    if (url.pathname.endsWith('/cotizacion/1')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                        editable: true, folio: 'COT-000001', update_url: '/admin/solicitudes/cotizacion/1', clinical_data: saved, pricing_snapshot: snapshot(saved) }) });
                    if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                    return route.fulfill({ contentType: 'text/html', body: html });
                });
                await page.goto('http://localhost/admin/solicitudes/cotizacion');
                await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
                const dialog = page.locator('[data-quote-wizard]');
                await dialog.locator(`[name=category][value=${category}]`).check();
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                await dialog.locator('[name=institution_id]').selectOption('1');
                await dialog.locator('[name=hospital_id]').selectOption('1');
                await dialog.getByText('Lista asignada: Lista del hospital', { exact: true }).waitFor();
                const mixture = number => dialog.locator(`[data-qw-mixture="${number}"]`);
                const requirementName = number => mixture(number).locator('[data-qw-requirement-medicine]');
                const requirementQuantity = number => mixture(number).locator('[data-qw-requirement-concentration]');
                const add = async (number, name, quantity) => {
                    await mixture(number).getByRole('combobox', { name: 'Buscar medicamento' }).fill(name);
                    await mixture(number).getByRole('option').filter({ hasText: name }).click();
                    await mixture(number).locator('.qw-quantity input').last().fill(String(quantity));
                };
                await assertMedicationColumns(mixture(1));
                assert.equal(await mixture(1).locator('.qw-requirement-table caption').textContent(), 'Requerimiento');
                assert.equal(await mixture(1).locator('[data-qw-requirement-unit]').textContent(), category === 'nutricionales' ? 'mL' : 'mg');
                await requirementName(1).fill('Medicamento A');
                await requirementQuantity(1).fill('150');
                await add(1, 'Medicamento A', 2);
                await add(1, 'Medicamento B', 1);
                assert.equal(await requirementName(1).inputValue(), 'Medicamento A');
                assert.equal(await requirementQuantity(1).inputValue(), '150');
                assert.equal(await mixture(1).locator('[data-qw-mixture-total]').textContent(), '$32.00 MXN');
                await dialog.getByRole('button', { name: 'Agregar mezcla', exact: true }).click();
                assert.equal(await dialog.getByRole('button', { name: 'Continuar', exact: true }).isDisabled(), true);
                await add(2, 'Medicamento A', 3);
                assert.equal(await requirementName(2).inputValue(), '');
                await requirementName(2).fill('Medicamento A');
                await requirementQuantity(2).fill('250.1234');
                await assertMedicationColumns(mixture(2));
                assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$69.00 MXN');
                await mixture(2).getByRole('button', { name: /^Editar precio unitario/ }).click();
                await mixture(2).getByRole('spinbutton', { name: /^Precio unitario de/ }).fill('8');
                await mixture(2).getByRole('button', { name: /^Aplicar precio unitario/ }).click();
                assert.match(await mixture(1).locator('.qw-price').first().textContent(), /10\.0000/);
                assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$63.00 MXN');
                const addMixtureBounds = await dialog.locator('[data-qw-add-mixture]').boundingBox();
                const grandTotalBounds = await dialog.locator('[data-qw-estimate]').boundingBox();
                assert.ok(addMixtureBounds.x + addMixtureBounds.width < grandTotalBounds.x,
                    'Add mixture stays on the left of the grand total');
                if (width > 700) {
                    assert.ok(Math.abs(addMixtureBounds.y + addMixtureBounds.height / 2
                        - grandTotalBounds.y - grandTotalBounds.height / 2) <= 1,
                    'Add mixture is level with the grand total amount');
                } else {
                    assert.ok(grandTotalBounds.y < addMixtureBounds.y + addMixtureBounds.height,
                        'The compact total and add button share the same row on mobile');
                }
                await mixture(2).getByRole('combobox', { name: 'Buscar medicamento' }).fill('Medicamento A');
                assert.equal(await mixture(2).getByRole('option').getAttribute('aria-disabled'), 'true');
                await page.keyboard.press('Escape');
                assert.deepEqual(await dialog.locator('[data-qw-mixture-title]').allTextContents(), ['Mezcla 1', 'Mezcla 2']);
                for (const section of await dialog.locator('[data-qw-mixture]').all()) {
                    assert.equal(await section.evaluate(element => {
                        const style = getComputedStyle(element);
                        return ['Top', 'Right', 'Bottom', 'Left'].every(side => style[`border${side}Width`] === '1px'
                            && style[`border${side}Style`] === 'solid');
                    }), true, 'Each mixture has its own complete border');
                    const frame = await section.boundingBox();
                    for (const child of await section.locator('.qw-mixture-header, .qw-mixture-total').all()) {
                        const box = await child.boundingBox();
                        assert.ok(box.x > frame.x && box.x + box.width < frame.x + frame.width
                            && box.y > frame.y && box.y + box.height < frame.y + frame.height,
                        'The mixture heading and total remain inside its frame');
                    }
                }
                assert.equal(await dialog.evaluate(element => element.scrollWidth <= element.clientWidth + 1), true);
                if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-mixtures-${category}-${width}.png`) });
                await dialog.getByRole('button', { name: 'Eliminar mezcla 1', exact: true }).click();
                assert.deepEqual(await dialog.locator('[data-qw-mixture-title]').allTextContents(), ['Mezcla 1']);
                assert.equal(await mixture(1).locator('.qw-quantity input').inputValue(), '3');
                assert.equal(await requirementQuantity(1).inputValue(), '250.1234');
                assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$31.00 MXN');
                await dialog.getByRole('button', { name: 'Agregar mezcla', exact: true }).click();
                await add(2, 'Medicamento B', 4);
                assert.equal(await requirementName(2).inputValue(), '');
                await requirementName(2).fill('Medicamento B');
                const previewsBefore = previews.length;
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                assert.equal(previews.length, previewsBefore);
                assert.equal(await requirementQuantity(2).evaluate(input => input.validity.valueMissing), true);
                await requirementQuantity(2).fill('10.5');
                if (process.env.QUOTATION_SCREENSHOTS) {
                    await mixture(1).locator('.qw-requirement-table').scrollIntoViewIfNeeded();
                    await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-requirements-${category}-${width}.png`) });
                }
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                await dialog.locator('[data-qw-step="2"]').waitFor();
                assert.equal(previews.at(-1).mixture_count, 2);
                assert.deepEqual(previews.at(-1).items.map(item => item.mixture_number), [1, 2]);
                assert.deepEqual(previews.at(-1).requirements, [
                    { mixture_number: 1, medicine: 'Medicamento A', concentration: 250.1234 },
                    { mixture_number: 2, medicine: 'Medicamento B', concentration: 10.5 },
                ]);
                assert.match(await dialog.locator('[data-qw-review-meta]').textContent(), /250\.1234/);
                assert.equal(await dialog.locator('[data-qw-total]').textContent(), '$58.00 MXN');
                assert.match(await dialog.locator('[data-qw-review-lines]').textContent(), /Mezcla 1/);
                assert.match(await dialog.locator('[data-qw-review-lines]').textContent(), /Mezcla 2/);
                const navigation = page.waitForEvent('framenavigated', frame => frame === page.mainFrame());
                await dialog.getByRole('button', { name: 'Guardar borrador', exact: true }).click();
                await navigation;
                await page.getByRole('button', { name: 'Editar COT-000001', exact: true }).click();
                await dialog.getByText('Lista asignada: Lista del hospital', { exact: true }).waitFor();
                assert.equal(saved.mixture_count, 2);
                assert.equal(await dialog.locator('[data-qw-mixture]').count(), 2);
                assert.equal(await mixture(1).locator('.qw-quantity input').inputValue(), '3');
                assert.equal(await mixture(2).locator('.qw-quantity input').inputValue(), '4');
                assert.equal(await requirementName(1).inputValue(), 'Medicamento A');
                assert.equal(await requirementQuantity(1).inputValue(), '250.1234');
                assert.equal(await requirementName(2).inputValue(), 'Medicamento B');
                assert.equal(await requirementQuantity(2).inputValue(), '10.5');
                await dialog.locator('[data-qw-close]').click();
                await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
                await dialog.locator(`[name=category][value=${category}]`).check();
                await dialog.getByRole('button', { name: 'Continuar', exact: true }).click();
                assert.equal(await requirementName(1).inputValue(), '');
                assert.equal(await requirementQuantity(1).inputValue(), '');
                assert.equal(await dialog.locator('[data-qw-estimate]').textContent(), '$0.00 MXN');
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally { await browser.close(); }
});

test('legacy quotation editing preserves clinical data, diluents and delivery calendars', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const catalog = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/request-quotations.php', 'category=oncologicos', 'options'], { cwd: root, encoding: 'utf8' }));
    catalog.products[0].diluents = [
        { id: 1, name: 'Solucion salina' }, { id: 2, name: 'Dextrosa 5%' },
        { id: 3, name: 'Agua inyectable' }, { id: 4, name: 'Otro diluyente de prueba' }, { id: 5, name: 'Dextrosa 50%' },
    ];
    catalog.products.push({ ...catalog.products[0], id: 999, name: 'Sin diluyentes configurados', diluents: [] });
    const html = screen('tipo=oncologicos', true);
    const draft = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/request-quotations.php', 'id=1', 'show'], { cwd: root, encoding: 'utf8' });
    const productLabel = product => [product.name, product.brand, product.presentation, `#${product.id}`].filter(Boolean).join(' - ');
    try {
        for (const width of [1440, 1056, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify(catalog) });
                if (url.pathname.endsWith('/cotizacion/1')) return route.fulfill({ contentType: 'application/json', body: draft });
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion?tipo=oncologicos');
            await page.getByRole('button', { name: 'Editar COT-000001', exact: true }).click();
            const dialog = page.locator('[data-quotation-dialog]');
            const row = dialog.locator('[data-quotation-medications] tr');
            const table = dialog.locator('.quotation-medication-table');
            const picker = dialog.locator('[data-quotation-row-picker]');
            await page.waitForFunction(() => !document.querySelector('[data-drug]').disabled);
            assert.equal(await dialog.locator('[name=patient_name]').inputValue(), 'Paciente de prueba de captura');
            assert.equal(await dialog.locator('[name=seller_id] option:checked').textContent(), 'Vendedora Prueba');
            assert.equal(await row.locator('[data-key=dose_mg]').inputValue(), '50');
            assert.equal(await table.locator('thead tr').count(), 2);
            assert.deepEqual(await table.locator('th[colspan="3"]').allTextContents(), ['Diluyente', 'Fecha de entrega*']);
            assert.equal(await row.locator('td').count(), 13);
            await row.locator('[data-drug]').fill(productLabel(catalog.products[0]));
            await row.getByRole('checkbox', { name: 'Diluyente CS', exact: true }).check();
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '1');
            await row.getByRole('checkbox', { name: 'Diluyente DX', exact: true }).check();
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '2');
            assert.equal(await row.locator('input[type=checkbox]:checked').count(), 1);
            await row.getByRole('checkbox', { name: 'Otro diluyente', exact: true }).click();
            await picker.locator('[data-picker-diluent]').selectOption('5');
            await picker.getByRole('button', { name: 'Aplicar', exact: true }).click();
            await picker.waitFor({ state: 'hidden' });
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '5');
            assert.equal(await row.getByRole('checkbox', { name: 'Otro diluyente', exact: true }).isChecked(), true);
            await row.getByRole('checkbox', { name: 'Otro diluyente', exact: true }).uncheck();
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '');
            await row.getByRole('checkbox', { name: 'Otro diluyente', exact: true }).click();
            await page.keyboard.press('Escape');
            assert.equal(await picker.isVisible(), false);
            assert.equal(await dialog.isVisible(), true);
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '');

            await row.locator('[data-drug]').fill(productLabel(catalog.products.at(-1)));
            for (const checkbox of await row.getByRole('checkbox').all()) assert.equal(await checkbox.isDisabled(), true);
            await row.locator('[data-drug]').fill(productLabel(catalog.products[0]));
            await row.getByRole('checkbox', { name: 'Diluyente CS', exact: true }).check();
            await dialog.locator('[name=scheduled_date]').fill('2026-09-22');
            await setDelivery(page, row, 0, '2026-09-23T10:00');
            await setDelivery(page, row, 1, '2026-09-24T10:00');
            await setDelivery(page, row, 1, '');
            assert.equal(await row.locator('[data-delivery]').first().inputValue(), '2026-09-23T10:00');
            assert.equal(await row.locator('[data-delivery-button]').first().getAttribute('title'), 'Entrega 1: 23/09/2026 10:00');
            assert.equal(await row.locator('[data-delivery-button][data-filled=true]').count(), 1);
            await row.locator('[data-delivery-button]').last().click();
            await picker.locator('[data-picker-date]').fill('2026-09-21T10:00');
            await picker.getByRole('button', { name: 'Aplicar', exact: true }).click();
            assert.equal(await picker.isVisible(), true);
            assert.equal(await picker.locator('[data-picker-date]').evaluate(input => input.validity.rangeUnderflow), true);
            await picker.locator('[data-picker-date]').fill('2026-09-25T10:00');
            const pickerBounds = await picker.boundingBox();
            assert.ok(pickerBounds.x >= 0 && pickerBounds.x + pickerBounds.width <= width);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-delivery-picker-${width}.png`) });
            await picker.locator('[data-picker-date]').press('Enter');
            await picker.waitFor({ state: 'hidden' });
            assert.equal(await row.locator('[data-delivery]').last().inputValue(), '2026-09-25T10:00');
            assert.equal(await row.locator('[data-key=diluent_id]').inputValue(), '1');
            assert.equal(await row.getByRole('checkbox', { name: 'Diluyente CS', exact: true }).isChecked(), true);
            assert.equal(await row.getByRole('checkbox', { name: 'Diluyente CS', exact: true }).evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(0, 139, 121)');

            await dialog.getByRole('heading', { name: 'Tabla de medicamentos' }).scrollIntoViewIfNeeded();
            if (width >= 1056) assert.equal(await dialog.locator('.quotation-medication-scroll').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            for (const control of await row.locator('input:not([type=hidden]), button').all()) {
                assert.equal(await control.evaluate(el => {
                    const control = el.getBoundingClientRect();
                    const cell = el.closest('td').getBoundingClientRect();
                    return control.x >= cell.x && control.right <= cell.right + 1 && control.top >= cell.top && control.bottom <= cell.bottom;
                }), true);
            }
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-reference-table-${width}.png`) });
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
