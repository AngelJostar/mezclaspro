const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {execFileSync} = require('node:child_process');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '../..');

test('institution client home shows its hospitals and only consultation actions on desktop and mobile', async () => {
    const fixture = JSON.parse(execFileSync('php', ['tests/Browser/fixtures/institution-portal.php'], {cwd:root, encoding:'utf8'}));
    const manifest = JSON.parse(readFileSync(path.join(root,'public/build/manifest.json')));
    const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
        .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
    const browser = await chromium.launch({headless:true, channel:process.env.PLAYWRIGHT_CHANNEL || undefined});
    try {
        for (const width of [1366, 390]) {
            const page = await browser.newPage({viewport:{width, height:900}});
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (route.request().resourceType() !== 'document') return route.abort();
                const key = url.pathname.endsWith('/cotizacion') ? 'quotations' : url.searchParams.get('institution_hospital') === '2' ? 'filtered' : 'home';
                const html = fixture[key].replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<link\b[^>]*>/gi, '')
                    .replace('</head>', `<style>${css}</style></head>`);
                return route.fulfill({contentType:'text/html', body:html});
            });
            await page.goto('http://institution-portal.test/admin/solicitudes');
            const banner = page.getByRole('region', {name:'Acceso de institución'});
            assert.match(await banner.innerText(), /Solo consulta · 2 hospitales/);
            assert.equal(await page.locator('[data-request-create-menu]').count(), 0);
            const rows = page.locator('.admin-content table tbody tr');
            assert.equal(await rows.count(), 7);
            if (process.env.INSTITUTION_EDITOR_SCREENSHOTS) await page.screenshot({path:path.join(process.env.INSTITUTION_EDITOR_SCREENSHOTS,`institution-portal-${width}.png`)});
            await banner.getByLabel('Hospital', {exact:true}).selectOption('2');
            await Promise.all([page.waitForEvent('load'), banner.getByRole('button',{name:'Consultar'}).click()]);
            assert.equal(await rows.count(), 2);
            assert.match(await rows.first().innerText(), /Hospital Norte/);
            await page.goto('http://institution-portal.test/admin/solicitudes/cotizacion');
            assert.equal(await page.locator('[data-quotation-send]').count(), 0);
            assert.equal(await page.getByRole('link',{name:'Descargar PDF',exact:true}).count(), 5);
            await page.close();
        }
    } finally {await browser.close();}
});
