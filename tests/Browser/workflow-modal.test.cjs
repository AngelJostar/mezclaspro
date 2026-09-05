const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

// These pages are intercepted fixtures: no application records or endpoints are used.
const root = path.resolve(__dirname, '../..');
const source = buildSync({
    entryPoints: [path.join(root, 'resources/js/workflow-modal.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const shell = readFileSync(path.join(root, 'resources/views/layouts/includes/workflow-modal.blade.php'), 'utf8');
const css = readFileSync(path.join(root, 'resources/css/workflow-modal.css'), 'utf8');
const config = (value) => `<script type="application/json" id="workflow-page-config">${JSON.stringify(value)}</script>`;
const scripts = `<script>${source}</script>`;
let browser;

before(async () => {
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

async function fixture(destination = '') {
    const context = await browser.newContext();
    const page = await context.newPage();
    let listLoads = 0;
    await page.route('http://workflow.test/**', async (route) => {
        const url = new URL(route.request().url());
        let body;
        if (url.pathname === '/list') {
            listLoads += 1;
            body = `<a href="/edit?approval_popup=1" data-approval-popup="test">Aprobacion</a>
                <a href="/edit?dispensing_popup=1" data-dispensing-popup="test">Dispensar</a>
                ${shell}${config({ embedded: false })}`;
        } else {
            const completed = url.pathname === '/complete';
            const confirmationVisible = completed || url.pathname === '/confirm';
            body = `<h1 data-workflow-heading>${url.searchParams.has('dispensing_popup') ? 'Dispensar' : 'Aprobacion'} #TEST${destination}</h1>
                <form><input aria-label="Dato de prueba" value="Original">
                    <button formaction="/error">Validar</button>
                    <button formaction="/complete">Guardar</button>
                    <button formaction="/confirm">Confirmar</button>
                </form>
                ${url.pathname === '/error' ? '<p role="alert">Dato no valido</p>' : ''}
                ${confirmationVisible ? '<div data-test-dialog><button data-test-ok>Aceptar</button></div>' : ''}
                ${config({ embedded: true, completed, waitForConfirmation: completed })}
                <script>
                    window.Swal = { isVisible: () => !!document.querySelector('[data-test-dialog]') };
                    document.addEventListener('click', (event) => {
                        if (!event.target.matches('[data-test-ok]')) return;
                        document.querySelector('[data-test-dialog]').remove();
                        document.dispatchEvent(new CustomEvent('workflow-popup-dialog-closed'));
                    });
                </script>`;
        }
        await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><style>${css}</style></head><body>${body}${scripts}</body></html>` });
    });
    await page.goto('http://workflow.test/list?estado=pendientes');
    return { page, context, listLoads: () => listLoads };
}

test('approval and dispensing remain in one browser page and restore focus on close', async () => {
    const { page, context } = await fixture();
    try {
        for (const name of ['Aprobacion', 'Dispensar']) {
            await page.getByRole('link', { name, exact: true }).click();
            await page.getByRole('heading', { name: `${name} #TEST`, exact: true }).waitFor();
            assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.matches(':modal')), true);
            assert.equal(context.pages().length, 1);
            assert.equal(page.url(), 'http://workflow.test/list?estado=pendientes');
            await page.frameLocator('iframe').getByRole('textbox').fill('Cambio sin guardar');
            await page.getByRole('button', { name: 'Cerrar ventana' }).click();
            assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), false);
            assert.equal(await page.evaluate(() => document.activeElement.textContent), name);
        }
        await page.getByRole('link', { name: 'Aprobacion', exact: true }).click();
        assert.equal(await page.frameLocator('iframe').getByRole('textbox').inputValue(), 'Original');
        await page.getByRole('button', { name: 'Cerrar ventana' }).press('Escape');
        assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), false);
    } finally {
        await context.close();
    }
});

test('workflow headers show the destination in small text without overlapping the close button', async () => {
    const destination = ' | Institucion: Institucion de Prueba | Hospital: Hospital de Prueba';
    const { page, context } = await fixture(destination);
    try {
        for (const width of [1320, 390]) {
            await page.setViewportSize({ width, height: 900 });
            for (const name of ['Aprobacion', 'Dispensar']) {
                await page.getByRole('link', { name, exact: true }).click();
                const header = page.getByRole('heading', { name: `${name} #TEST${destination}`, exact: true });
                await header.waitFor();
                assert.equal(await header.evaluate(el => getComputedStyle(el).fontSize), '12px');
                const a = await header.boundingBox();
                const b = await page.getByRole('button', { name: 'Cerrar ventana' }).boundingBox();
                assert.ok(a.x + a.width <= b.x);
                assert.ok(b.x + b.width <= width);
                if (width > 640) assert.ok(a.height < 25);
                await page.getByRole('button', { name: 'Cerrar ventana' }).click();
            }
        }
    } finally { await context.close(); }
});

test('validation remains inside the modal and completion waits for acknowledgement', async () => {
    const { page, context, listLoads } = await fixture();
    try {
        await page.getByRole('link', { name: 'Aprobacion', exact: true }).click();
        await page.frameLocator('iframe').getByRole('button', { name: 'Validar', exact: true }).click();
        await page.frameLocator('iframe').getByRole('alert').waitFor();
        assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), true);
        assert.equal(listLoads(), 1);
        await page.frameLocator('iframe').getByRole('button', { name: 'Guardar', exact: true }).click();
        await page.frameLocator('iframe').getByRole('button', { name: 'Aceptar' }).waitFor();
        assert.equal(listLoads(), 1);
        await page.getByRole('button', { name: 'Cerrar ventana' }).press('Escape');
        assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), true);
        await Promise.all([
            page.waitForEvent('load'),
            page.frameLocator('iframe').getByRole('button', { name: 'Aceptar' }).click(),
        ]);
        assert.equal(listLoads(), 2);
        assert.equal(page.url(), 'http://workflow.test/list?estado=pendientes');
        assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), false);
    } finally {
        await context.close();
    }
});

test('delegated rows work and messages from other windows or origins are ignored', async () => {
    const { page, context, listLoads } = await fixture();
    try {
        await page.evaluate(() => {
            const replacement = document.querySelector('[data-approval-popup]').cloneNode(true);
            replacement.textContent = 'Fila nueva';
            document.body.append(replacement);
        });
        await page.getByRole('link', { name: 'Fila nueva' }).click();
        await page.getByRole('heading', { name: 'Aprobacion #TEST' }).waitFor();
        await page.evaluate(() => {
            const data = { type: 'mezclaspro:workflow-modal', action: 'complete' };
            window.dispatchEvent(new MessageEvent('message', { data, origin: location.origin, source: window }));
            window.dispatchEvent(new MessageEvent('message', {
                data, origin: 'https://untrusted.test', source: document.querySelector('iframe').contentWindow,
            }));
        });
        assert.equal(listLoads(), 1);
        assert.equal(await page.locator('[data-workflow-modal]').evaluate((dialog) => dialog.open), true);
        await page.frameLocator('iframe').getByRole('textbox').press('Escape');
        await page.locator('[data-workflow-modal]').waitFor({ state: 'hidden' });
    } finally {
        await context.close();
    }
});
