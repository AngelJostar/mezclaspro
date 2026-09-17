const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root,'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root,'public/build',file),'utf8')).join('\n');
const script = buildSync({ stdin:{ contents:`import { Alpine } from './vendor/livewire/livewire/dist/livewire.esm.js'; import './resources/js/hospital-tools.js'; import './resources/js/table-column-filters.js'; import './resources/js/workflow-modal.js'; Alpine.start();`, resolveDir:root },loader:{'.css':'empty'},bundle:true,write:false,format:'iife' }).outputFiles[0].text;
const cache = new Map();
function render(mode, query) {
    const key = mode+JSON.stringify(query);
    if (!cache.has(key)) cache.set(key,execFileSync('php',['-d','extension=pdo_sqlite','-d','extension=sqlite3','tests/Browser/fixtures/hospital-invoices.php',mode,JSON.stringify(query)],{cwd:root,encoding:'utf8'}));
    return cache.get(key);
}
test('hospital invoices show totals, filters, scoped detail and payment reporting on desktop/mobile', async () => {
    const browser = await chromium.launch({headless:true,channel:process.env.PLAYWRIGHT_CHANNEL || undefined});
    try {
        for (const width of [1890,1440,390]) {
            const page = await browser.newPage({viewport:{width,height:960}});
            const errors = [];
            let reports = 0;
            page.on('pageerror', e => errors.push(e.message));
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/img/promesa-logo.png') return route.fulfill({contentType:'image/png',body:readFileSync(path.join(root,'public/img/promesa-logo.png'))});
                if (route.request().method() === 'POST') {
                    reports++;
                    return route.fulfill({status:reports === 1 ? 422 : 201,contentType:'application/json',body:JSON.stringify({message:reports === 1 ? 'Error de prueba. Revisa la referencia.' : 'Pago reportado. Pendiente de validación por Prodifem.'})});
                }
                const detail = /\/facturacion\/[a-f0-9]{64}$/.test(url.pathname);
                const query = Object.fromEntries(url.searchParams);
                if (detail) query.invoice = url.pathname.split('/').pop();
                return route.fulfill({contentType:'text/html; charset=utf-8',body:`<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style><div x-data="{open:false}">${render(detail ? 'detail' : 'list',query)}</div><script>${script}</script>`});
            });
            await page.goto('http://localhost/admin/herramientas?tab=facturacion&desde=2026-08-01&hasta=2026-09-15');
            if (width < 640) await page.waitForFunction(() => document.querySelector('#logo-sidebar').getBoundingClientRect().right <= 1);
            const table = page.locator('.ht-invoice-table');
            assert.equal(await table.locator('tbody tr').count(),5);
            assert.ok((await page.locator('.ht-invoice-summary').innerText()).includes('$300,000.00'));
            assert.equal(await table.getByRole('button',{name:'PDF no disponible',exact:true}).count(),5);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({path:path.join(process.env.TOOLS_SCREENSHOTS,`hospital-invoices-${width}.png`),fullPage:true});
            const states = page.getByRole('combobox',{name:'Estado de pago:'});
            for (const [state,count] of [['pending',2],['partial',1],['paid',2],['overdue',1]]) {
                await states.selectOption(state);
                await page.waitForURL(url => url.searchParams.get('pago_estado') === state);
                await page.waitForLoadState('load');
                assert.equal(await table.locator('tbody tr').count(),count);
            }
            const exportUrl = new URL(await page.getByRole('link',{name:'Exportar a Excel',exact:true}).getAttribute('href'));
            assert.equal(exportUrl.searchParams.get('pago_estado'),'overdue');
            await table.getByRole('link',{name:'Ver detalle F-1051',exact:true}).click();
            const frame = page.frameLocator('[data-workflow-frame]');
            await frame.getByRole('heading',{name:'Factura F-1051',exact:true}).waitFor();
            assert.equal(await frame.locator('.ht-invoice-lines').first().locator('tbody tr').count(),2);
            await frame.getByRole('button',{name:'Reportar pago',exact:true}).click();
            await frame.getByLabel('Importe MXN',{exact:true}).fill('1000.50');
            await frame.getByLabel('Fecha de pago',{exact:true}).fill('2026-09-14');
            await frame.getByLabel('Referencia',{exact:true}).fill('REF-TEST');
            if (process.env.TOOLS_SCREENSHOTS) await page.screenshot({path:path.join(process.env.TOOLS_SCREENSHOTS,`hospital-invoice-detail-${width}.png`)});
            await frame.getByRole('button',{name:'Enviar reporte',exact:true}).click();
            await frame.getByText('Error de prueba. Revisa la referencia.',{exact:true}).waitFor();
            assert.equal(await frame.getByRole('button',{name:'Enviar reporte',exact:true}).isEnabled(),true);
            await frame.getByRole('button',{name:'Enviar reporte',exact:true}).click();
            await frame.getByRole('link',{name:'Ver reporte',exact:true}).waitFor();
            assert.equal(await frame.getByRole('button',{name:'Enviar reporte',exact:true}).isDisabled(),true);
            assert.equal(reports,2);
            await frame.getByRole('link',{name:'Cerrar detalle',exact:true}).click();
            await page.locator('[data-workflow-modal]').waitFor({state:'hidden'});
            await states.selectOption('all');
            await page.waitForURL(url => url.searchParams.get('pago_estado') === 'all');
            await page.waitForLoadState('load');
            await page.getByRole('searchbox',{name:'Buscar folio de factura'}).fill('1055');
            await page.getByRole('button',{name:'Buscar factura',exact:true}).click();
            await page.waitForLoadState('load');
            assert.equal(await table.locator('tbody tr').count(),1);
            assert.ok((await table.innerText()).includes('F-1055'));
            assert.deepEqual(errors,[]);
            await page.close();
        }
    } finally { await browser.close(); }
});
