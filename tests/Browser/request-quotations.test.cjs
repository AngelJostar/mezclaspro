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
    return `<meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style>
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

test('quotation send dialog sends email on the server and prepares WhatsApp without changing status', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const html = screen();
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            const posts = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.addInitScript(() => {
                window.openedMessages = [];
                window.open = (...args) => { window.openedMessages.push(args); return null; };
            });
            await page.route('**/*', route => {
                const request = route.request();
                const url = new URL(request.url());
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
            const originalStatus = await row.locator('td').nth(8).textContent();
            const opener = page.getByRole('button', { name: 'Enviar COT-000001', exact: true });
            await opener.click();
            const dialog = page.getByRole('dialog', { name: 'Enviar COT-000001', exact: true });
            const email = dialog.getByLabel('Correo del destinatario *', { exact: true });
            const note = dialog.getByLabel('Mensaje (opcional)', { exact: true });
            const summary = dialog.getByLabel('Cotizacion', { exact: true });
            const submit = dialog.getByRole('button', { name: 'Enviar correo', exact: true });
            await dialog.waitFor({ state: 'visible' });
            assert.equal(await email.evaluate(el => document.activeElement === el), true);
            assert.match(await summary.inputValue(), /COT-000001/);
            assert.match(await summary.inputValue(), /Total: \$100.50 MXN/);
            assert.doesNotMatch(await summary.inputValue(), /Paciente/);
            assert.equal(await summary.getAttribute('readonly'), '');
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
            assert.equal(await row.locator('td').nth(8).textContent(), originalStatus);

            await dialog.getByText('WhatsApp', { exact: true }).click();
            const phone = dialog.getByLabel('Telefono con codigo de pais *', { exact: true });
            assert.equal(await email.isVisible(), false);
            const whatsapp = dialog.getByRole('button', { name: 'Abrir WhatsApp', exact: true });
            await phone.fill('0001');
            await whatsapp.click();
            assert.equal(await page.evaluate(() => window.openedMessages.length), 0);
            await phone.fill('+52 (55) 1234-5678');
            await whatsapp.click();
            const opened = await page.evaluate(() => window.openedMessages);
            assert.equal(opened.length, 1);
            const url = new URL(opened[0][0]);
            assert.equal(url.origin, 'https://wa.me');
            assert.equal(url.pathname, '/525512345678');
            assert.equal(url.searchParams.get('text'), 'Buenos dias: revisi\u00f3n & precios + IVA.\n\n' + await summary.inputValue());
            assert.deepEqual(opened[0].slice(1), ['_blank', 'noopener,noreferrer']);
            assert.equal(posts.length, 2, 'WhatsApp must not call the email endpoint');
            assert.equal(await row.locator('td').nth(8).textContent(), originalStatus);
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
            assert.match(await other.getByLabel('Cotizacion', { exact: true }).inputValue(), /Total: \$95.25 MXN/);
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
            assert.equal(await page.locator('#request-quotations-table th').count(), 13);
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

test('quotation popup captures oncology and nutrition, validates errors and fits desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const catalogs = Object.fromEntries(['oncologicos', 'nutricionales'].map(category => [category,
        execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/request-quotations.php', `category=${category}`, 'options'], { cwd: root, encoding: 'utf8' })]));
    const draft = execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', 'tests/Browser/fixtures/request-quotations.php', 'id=1', 'show'], { cwd: root, encoding: 'utf8' });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            const posts = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const request = route.request();
                const url = new URL(request.url());
                if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: catalogs[url.searchParams.get('category')] });
                if (url.pathname.endsWith('/cotizacion/1')) return route.fulfill({ contentType: 'application/json', body: draft });
                if (request.method() === 'POST') {
                    posts.push(request.postData());
                    if (posts.length === 2) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ folio: 'COT-000001', redirect_url: '/admin/solicitudes/cotizacion?buscar=COT-000001' }) });
                    return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'Revisa los datos.', errors: { doctor_license: ['Cedula de prueba invalida.'] } }) });
                }
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: screen(url.search.slice(1), true) });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            const dialog = page.locator('[data-quotation-dialog]');
            const input = name => dialog.locator(`[name="${name}"]`);
            await page.getByRole('button', { name: 'Editar COT-000001', exact: true }).click();
            await page.waitForFunction(() => document.querySelector('[data-quotation-price-list]').value === 'Lista onco de prueba');
            assert.equal(await input('patient_name').inputValue(), 'Paciente de prueba de captura');
            assert.equal(await input('seller_id').locator('option:checked').textContent(), 'Vendedora Prueba');
            assert.equal(await dialog.locator('[data-key=dose_mg]').inputValue(), '50');
            assert.equal(await dialog.locator('[data-key=diluent_id]').inputValue(), '1');
            assert.equal(await dialog.locator('[data-delivery]').nth(1).inputValue(), '2026-09-24T09:00');
            await page.keyboard.press('Escape');
            await page.getByRole('button', { name: 'Ver COT-000001', exact: true }).click();
            await page.getByRole('heading', { name: 'Importes cotizados (MXN)' }).waitFor();
            assert.ok((await page.getByRole('dialog').innerText()).includes('Solucion salina'));
            await page.keyboard.press('Escape');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            await dialog.waitFor({ state: 'visible' });
            assert.equal(await input('category').inputValue(), '');
            assert.equal(await input('seller_id').inputValue(), '');
            await input('seller_id').selectOption({ label: 'Vendedora Prueba' });
            assert.ok(await input('seller_id').inputValue());
            assert.equal(await dialog.getByRole('button', { name: 'Enviar a revision' }).isDisabled(), true);
            await input('category').selectOption('oncologicos');
            const row = dialog.locator('[data-quotation-medications] tr');
            assert.equal(await row.count(), 1);
            assert.equal(await dialog.getByRole('button', { name: 'Agregar medicamento' }).isEnabled(), true);
            assert.equal(await input('patient_name').isEnabled(), true);
            assert.equal(await row.locator('[data-drug]').isDisabled(), true);
            await row.locator('[data-key=dose_mg]').fill('50');
            await dialog.getByRole('button', { name: 'Agregar medicamento' }).click();
            assert.equal(await row.count(), 2);
            await row.last().locator('[data-key=dose_mg]').fill('25');
            await input('hospital_id').selectOption('1');
            await page.waitForFunction(() => document.querySelector('[data-quotation-price-list]').value === 'Lista onco de prueba');
            assert.equal(await row.count(), 2);
            assert.equal(await row.first().locator('[data-key=dose_mg]').inputValue(), '50');
            assert.equal(await row.last().locator('[data-key=dose_mg]').inputValue(), '25');
            assert.equal(await row.first().locator('[data-drug]').isEnabled(), true);
            await row.last().getByRole('button', { name: 'Eliminar medicamento' }).click();
            assert.equal(await input('institution_id').inputValue(), '1');
            assert.equal(await dialog.locator('[data-quotation-price-list]').inputValue(), 'Lista onco de prueba');
            await input('scheduled_date').fill('2026-09-22');
            await input('service').fill('Servicio de prueba');
            await input('patient_name').fill('Paciente de prueba');
            await input('sex').selectOption('F');
            await input('birth_date').fill('1990-01-01');
            await input('weight_kg').fill('60');
            await input('height_cm').fill('160');
            await input('diagnosis').fill('Diagnostico de prueba');
            const product = JSON.parse(catalogs.oncologicos).products[0];
            const productLabel = [product.name, product.brand, product.presentation, `#${product.id}`].filter(Boolean).join(' - ');
            await row.locator('[data-drug]').fill(productLabel);
            await row.locator('[data-key=dose_mg]').fill('50');
            await row.getByRole('checkbox', { name: 'Diluyente CS', exact: true }).check();
            await row.locator('[data-key=dilution_ml]').fill('100');
            await row.locator('[data-key=boluses_per_day]').fill('2');
            await row.locator('[data-key=infusion_minutes]').fill('60');
            await setDelivery(page, row, 0, '2026-09-23T10:00');
            await input('doctor_name').fill('Medico de prueba');
            await input('doctor_license').fill('TEST-123');
            await setDelivery(page, row, 0, '');
            await dialog.getByRole('button', { name: 'Guardar borrador' }).click();
            const deliveryPicker = dialog.locator('[data-quotation-row-picker]');
            await deliveryPicker.waitFor({ state: 'visible' });
            assert.equal(posts.length, 0, 'The first delivery is still required before saving');
            await deliveryPicker.locator('[data-picker-date]').fill('2026-09-23T10:00');
            await deliveryPicker.getByRole('button', { name: 'Aplicar', exact: true }).click();
            await deliveryPicker.waitFor({ state: 'hidden' });
            await dialog.getByRole('button', { name: 'Agregar medicamento' }).click();
            assert.equal(await row.count(), 2);
            await row.last().getByRole('button', { name: 'Eliminar medicamento' }).click();
            assert.equal(await row.count(), 1);
            await dialog.getByRole('button', { name: 'Guardar borrador' }).click();
            await dialog.getByText('Cedula de prueba invalida.').waitFor();
            assert.equal(posts.length, 1);
            assert.match(posts[0], /name="category"\r\n\r\noncologicos/);
            assert.match(posts[0], /name="rows\[0\]\[dose_mg\]"\r\n\r\n50/);
            assert.match(posts[0], /name="rows\[0\]\[diluent_id\]"\r\n\r\n1/);
            assert.match(posts[0], /name="rows\[0\]\[deliveries\]\[0\]"\r\n\r\n2026-09-23T10:00/);
            assert.doesNotMatch(posts[0], /name="npt"/);
            assert.equal(await input('patient_name').inputValue(), 'Paciente de prueba');
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
            assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-oncology-${width}.png`) });
            await dialog.getByRole('button', { name: 'Guardar borrador' }).click();
            await page.waitForURL('**buscar=COT-000001');
            assert.equal(posts.length, 2);
            await page.goto('http://localhost/admin/solicitudes/cotizacion?tipo=nutricionales');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            assert.equal(await input('category').inputValue(), 'nutricionales');
            assert.equal(await input('category').isDisabled(), true);
            await input('hospital_id').selectOption('1');
            await dialog.getByRole('heading', { name: 'Aminoacidos', exact: true }).waitFor();
            await input('scheduled_date').fill('2026-09-22');
            await input('patient_name').fill('Paciente nutricional');
            await input('birth_date').fill('1990-01-01');
            await input('weight_kg').fill('60');
            await input('npt').selectOption('ADULT');
            await dialog.locator('[data-component="1"]').fill('50');
            await input('overfill_ml').fill('10');
            await input('delivery_at').fill('2026-09-23T10:00');
            await input('doctor_name').fill('Medico de prueba');
            await input('doctor_license').fill('TEST-123');
            assert.equal(await dialog.locator('[data-quotation-volume]').inputValue(), '50.00');
            await dialog.getByRole('button', { name: 'Enviar a revision' }).click();
            await dialog.getByText('Cedula de prueba invalida.').waitFor();
            assert.equal(posts.length, 3);
            assert.match(posts[2], /name="category"\r\n\r\nnutricionales/);
            assert.match(posts[2], /name="components\[0\]\[volume_ml\]"\r\n\r\n50/);
            assert.doesNotMatch(posts[2], /name="height_cm"/);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-nutrition-${width}.png`) });
            await page.keyboard.press('Escape');
            await page.goto('http://localhost/admin/solicitudes/cotizacion?tipo=antibioticos');
            assert.equal(await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).isDisabled(), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('oncology medication rows remain usable before, during and after catalog loading', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const catalog = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/request-quotations.php', 'category=oncologicos', 'options'], { cwd: root, encoding: 'utf8' }));
    const html = screen('tipo=oncologicos', true);
    const productLabel = product => [product.name, product.brand, product.presentation, `#${product.id}`].filter(Boolean).join(' - ');
    try {
        for (const width of [1440, 1056, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            let mode = 'delayed';
            let onCatalogRequest;
            const catalogRequest = new Promise(resolve => { onCatalogRequest = resolve; });
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('/opciones')) {
                    if (mode === 'delayed') { onCatalogRequest(route); return; }
                    if (mode === 'error') return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'El hospital no tiene lista asignada.' }) });
                    const products = mode === 'empty' ? [] : mode === 'unavailable' ? [{ ...catalog.products[0], id: 999 }] : catalog.products;
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ ...catalog, products }) });
                }
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion?tipo=oncologicos');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            const dialog = page.locator('[data-quotation-dialog]');
            const rows = dialog.locator('[data-quotation-medications] tr');
            const add = dialog.getByRole('button', { name: 'Agregar medicamento' });
            const hospital = dialog.locator('[name=hospital_id]');
            const save = dialog.getByRole('button', { name: 'Guardar borrador' });
            assert.equal(await rows.count(), 1);
            assert.equal(await add.isEnabled(), true);
            await rows.first().locator('[data-key=dose_mg]').fill('50');
            await hospital.selectOption('1');
            const pending = await catalogRequest;
            await add.click();
            await rows.last().locator('[data-key=dose_mg]').fill('25');
            assert.equal(await save.isDisabled(), true);
            await pending.fulfill({ contentType: 'application/json', body: JSON.stringify(catalog) });
            await page.waitForFunction(() => !document.querySelector('[data-drug]').disabled);
            assert.equal(await rows.count(), 2);
            assert.equal(await rows.last().locator('[data-key=dose_mg]').inputValue(), '25');
            await rows.first().locator('[data-drug]').fill(productLabel(catalog.products[0]));
            await rows.first().getByRole('checkbox', { name: 'Diluyente CS', exact: true }).check();
            await rows.first().locator('[data-key=dilution_ml]').fill('100');

            await dialog.getByRole('heading', { name: 'Tabla de medicamentos' }).scrollIntoViewIfNeeded();
            assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            const rowBounds = await rows.first().boundingBox();
            const bodyBounds = await dialog.locator('.quotation-modal-body').boundingBox();
            assert.ok(rowBounds.height >= 40 && rowBounds.y >= bodyBounds.y && rowBounds.y + rowBounds.height <= bodyBounds.y + bodyBounds.height, 'Medication inputs must be visible, not clipped under the header or footer');
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-medications-${width}.png`) });

            await hospital.selectOption('');
            assert.equal(await rows.first().locator('[data-drug]').isDisabled(), true);
            assert.equal(await save.isDisabled(), true);
            mode = 'empty';
            await hospital.selectOption('1');
            await dialog.locator('[data-quotation-medication-status]').getByText('No hay productos activos en la lista de precios asignada.').waitFor();
            await add.click();
            assert.equal(await rows.count(), 3);
            assert.equal(await save.isDisabled(), true);
            assert.equal(await rows.first().locator('[data-key=dose_mg]').inputValue(), '50');
            mode = 'error';
            await hospital.selectOption('2');
            await dialog.getByText('El hospital no tiene lista asignada.').waitFor();
            assert.equal(await add.isEnabled(), true);
            assert.equal(await save.isDisabled(), true);
            assert.equal(await rows.count(), 3);

            mode = 'unavailable';
            await hospital.selectOption('1');
            await page.waitForFunction(() => !document.querySelector('[data-drug]').disabled);
            assert.equal(await rows.first().locator('[data-drug]').evaluate(input => input.validity.valid), false);
            assert.equal(await rows.first().locator('[data-drug]').getAttribute('aria-invalid'), 'true');
            await rows.first().locator('[data-drug]').fill(productLabel({ ...catalog.products[0], id: 999 }));
            assert.equal(await rows.first().locator('[data-drug]').evaluate(input => input.validity.valid), true);
            assert.equal(await rows.first().locator('[data-key=presentation_id]').inputValue(), '999');
            assert.equal(await rows.first().locator('[data-key=dilution_ml]').inputValue(), '100');
            await page.evaluate(() => {
                const button = document.querySelector('[data-quotation-add]');
                for (let i = 0; i < 50; i++) button.click();
            });
            assert.equal(await rows.count(), 50);
            assert.equal(await add.isDisabled(), true);
            await rows.last().getByRole('button', { name: 'Eliminar medicamento' }).click();
            assert.equal(await add.isEnabled(), true);
            assert.equal(await rows.count(), 49);

            await page.keyboard.press('Escape');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            assert.equal(await rows.count(), 1);
            assert.equal(await rows.first().locator('[data-key=dose_mg]').inputValue(), '');
            assert.equal(await rows.first().locator('[data-drug]').inputValue(), '');
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});

test('oncology reference table groups diluents and delivery calendars without losing catalog IDs', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const catalog = JSON.parse(execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/request-quotations.php', 'category=oncologicos', 'options'], { cwd: root, encoding: 'utf8' }));
    catalog.products[0].diluents = [
        { id: 1, name: 'Solucion salina' }, { id: 2, name: 'Dextrosa 5%' },
        { id: 3, name: 'Agua inyectable' }, { id: 4, name: 'Otro diluyente de prueba' }, { id: 5, name: 'Dextrosa 50%' },
    ];
    catalog.products.push({ ...catalog.products[0], id: 999, name: 'Sin diluyentes configurados', diluents: [] });
    const html = screen('tipo=oncologicos', true);
    const productLabel = product => [product.name, product.brand, product.presentation, `#${product.id}`].filter(Boolean).join(' - ');
    try {
        for (const width of [1440, 1056, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('/opciones')) return route.fulfill({ contentType: 'application/json', body: JSON.stringify(catalog) });
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion?tipo=oncologicos');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            const dialog = page.locator('[data-quotation-dialog]');
            const row = dialog.locator('[data-quotation-medications] tr');
            const table = dialog.locator('.quotation-medication-table');
            const picker = dialog.locator('[data-quotation-row-picker]');
            await dialog.locator('[name=hospital_id]').selectOption('1');
            await page.waitForFunction(() => !document.querySelector('[data-drug]').disabled);
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

test('new quotation from Todas exposes the three mixture types in the modal header', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const catalogs = Object.fromEntries(['oncologicos', 'nutricionales'].map(category => [category,
        execFileSync('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
            'tests/Browser/fixtures/request-quotations.php', `category=${category}`, 'options'], { cwd: root, encoding: 'utf8' })]));
    const html = screen('', true);
    try {
        for (const width of [1440, 768, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 960 } });
            const errors = [];
            const requestedTypes = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('/opciones')) {
                    const type = url.searchParams.get('category');
                    requestedTypes.push(type);
                    return route.fulfill({ contentType: 'application/json', body: catalogs[type] });
                }
                if (url.pathname === '/admin/solicitudes/cotizacion') return route.fulfill({ contentType: 'text/html', body: html });
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({ contentType: 'image/png', body: readFileSync(path.join(root, 'public/img/promesa-logo.png')) });
                return route.abort();
            });
            await page.goto('http://localhost/admin/solicitudes/cotizacion');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            const dialog = page.locator('[data-quotation-dialog]');
            const type = dialog.locator('.quotation-modal-header [name=category]');
            assert.equal(await type.isEnabled(), true);
            assert.equal(await type.inputValue(), '');
            assert.deepEqual(await type.locator('option').allTextContents(), ['Seleccionar tipo de mezcla', 'Oncologica', 'Nutricional', 'Antibioticos']);
            for (const value of ['oncologicos', 'nutricionales', 'antibioticos']) assert.equal(await type.locator(`option[value=${value}]`).isEnabled(), true);
            const selectBounds = await type.boundingBox();
            const headerBounds = await dialog.locator('.quotation-modal-header').boundingBox();
            assert.ok(selectBounds.x >= 0 && selectBounds.x + selectBounds.width <= width);
            assert.ok(selectBounds.y >= headerBounds.y && selectBounds.y + selectBounds.height <= headerBounds.y + headerBounds.height);
            if (process.env.QUOTATION_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.QUOTATION_SCREENSHOTS, `quotation-type-selector-${width}.png`) });
            await type.selectOption('oncologicos');
            await dialog.getByRole('heading', { name: 'Tabla de medicamentos', exact: true }).waitFor();
            await dialog.locator('[name=hospital_id]').selectOption('1');
            await page.waitForFunction(() => document.querySelector('[data-quotation-price-list]').value === 'Lista onco de prueba');
            await dialog.locator('[name=patient_name]').fill('Paciente de prueba');
            await type.selectOption('antibioticos');
            await dialog.getByText('Formato de cotizacion pendiente.', { exact: true }).waitFor();
            assert.equal(await dialog.locator('[data-quotation-content]').isVisible(), false);
            assert.equal(await dialog.getByRole('button', { name: 'Guardar borrador', exact: true }).isDisabled(), true);
            assert.equal(await dialog.getByRole('button', { name: 'Enviar a revision', exact: true }).isDisabled(), true);
            await type.selectOption('nutricionales');
            await dialog.getByRole('heading', { name: 'Aminoacidos', exact: true }).waitFor();
            assert.equal(await dialog.getByRole('heading', { name: 'Tabla de medicamentos', exact: true }).isVisible(), false);
            assert.equal(await dialog.locator('[name=patient_name]').inputValue(), 'Paciente de prueba');
            await type.selectOption('oncologicos');
            await page.waitForFunction(() => document.querySelector('[data-quotation-price-list]').value === 'Lista onco de prueba');
            assert.equal(await dialog.locator('[data-quotation-medications] tr').count(), 1);
            assert.equal(await dialog.locator('[data-quotation-pending]').isVisible(), false);
            assert.equal(await dialog.locator('[name=patient_name]').inputValue(), 'Paciente de prueba');
            assert.deepEqual(requestedTypes, ['oncologicos', 'nutricionales', 'oncologicos']);
            await page.keyboard.press('Escape');
            await page.getByRole('button', { name: 'Nueva Cotizacion', exact: true }).click();
            assert.equal(await type.inputValue(), '');
            assert.equal(await dialog.locator('[data-quotation-pending]').isVisible(), false);
            assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
