const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const source = buildSync({ stdin: { contents: "import './resources/js/institution-field-editor.js'; import './resources/js/enabled-services.js'; import './resources/js/fixed-table-scrollbar.js';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser, fixture;
before(async () => {
    fixture = JSON.parse(execFileSync('php', ['tests/Browser/fixtures/institution-field-editor.php'], { cwd: root, encoding: 'utf8' }));
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

async function pageFixture(width = 1366) {
    const page = await browser.newPage({ viewport: { width, height: 850 } });
    const state = { patches: [], getFailure: false, patchFailure: false, fields: structuredClone(fixture.fields), errors: [] };
    page.on('pageerror', error => state.errors.push(error.message));
    await page.route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        const field = url.pathname.split('/').at(-1);
        if (['nombre','razon_social','username','password','mixtures'].includes(field)) {
            if (request.method() === 'PATCH') {
                const payload = request.postDataJSON();
                state.patches.push({ field, url: url.pathname, payload });
                if (state.patchFailure) return route.fulfill({ status: 422, json: { errors: { value: ['No se pudo guardar el valor de prueba.'] } } });
                if (state.fields[field]) state.fields[field].value = payload.value;
                return route.fulfill({ json: { message: 'Cambios guardados' } });
            }
            if (state.getFailure) return route.fulfill({ status: 500, json: { message: 'No se pudo cargar.' } });
            return route.fulfill({ json: field === 'mixtures' ? (url.pathname.includes('/2/') ? fixture.empty : fixture.mixtures) : state.fields[field] });
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style></head><body><div class="admin-page sm:ml-44">${fixture.html}</div><script>${source}</script></body></html>` });
    });
    await page.goto('http://institution-fields.test/admin/instituciones');
    const row = page.locator('#institutions-table tbody tr').filter({ hasText: 'Institución de prueba' });
    return { page, state, row, dialog: page.locator('[data-institution-field-dialog]') };
}
const ready = page => page.waitForFunction(() => !document.querySelector('[data-institution-field-save]').disabled);

test('pencil columns align with their data and each popup saves only its selected field', async () => {
    for (const width of [1366,390]) {
        const { page, state, row, dialog } = await pageFixture(width);
        try {
            const headers = await page.locator('#institutions-table thead th').allTextContents();
            assert.equal(headers.length,18);
            for (const [index, text] of [[2,'Editar nombre'],[4,'Editar razón social'],[6,'Editar usuario'],[8,'Editar contraseña'],[11,'Editar mezclas habilitadas'],[13,'Editar servicios habilitados']]) assert.equal(headers[index].trim(),text);
            assert.equal(await row.locator('td,th').count(),18);
            assert.equal(await row.locator('[data-field="username"]').count(),3);
            assert.equal(await row.locator('[data-field="password"]').count(),3);
            for (const field of ['nombre','razon_social','username','password']) {
                const trigger = row.locator(`[data-field="${field}"]`).first();
                await trigger.click();
                await ready(page);
                const input = dialog.locator('[data-institution-field-input]');
                const original = await input.inputValue();
                await input.fill('Edición descartada');
                await dialog.locator('.hospital-field-cross').click();
                assert.equal(await trigger.evaluate(node => node === document.activeElement),true);
                assert.equal(state.patches.length,['nombre','razon_social','username','password'].indexOf(field));
                await trigger.click();
                await ready(page);
                assert.equal(await input.inputValue(),original);
                if (field === 'password') {
                    assert.equal(await input.getAttribute('type'),'password');
                    await dialog.getByRole('button',{name:'Mostrar contraseña'}).click();
                    assert.equal(await input.getAttribute('type'),'text');
                }
                const value = field === 'username' ? 'usuario.modificado' : 'Valor de prueba';
                await input.fill(value);
                const box = await dialog.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width && box.y + box.height <= 851);
                if (field === 'nombre' && process.env.INSTITUTION_EDITOR_SCREENSHOTS) await page.screenshot({path:path.join(process.env.INSTITUTION_EDITOR_SCREENSHOTS,`institution-name-${width}.png`)});
                const url = new URL(await trigger.getAttribute('data-url')).pathname;
                await Promise.all([page.waitForEvent('load'),dialog.getByRole('button',{name:'Guardar cambios'}).click()]);
                const patch = state.patches.at(-1);
                assert.equal(patch.url,url);
                assert.deepEqual(patch.payload, fixture.fields[field] ? {value,version:fixture.fields[field].version} : {[field]:value});
            }
            assert.deepEqual(state.errors,[]);
        } finally { await page.close(); }
    }
});

test('mixture pencil opens three columns; bulk and individual selections stay independent', async () => {
    for (const width of [1366,390]) {
        const { page, state, row } = await pageFixture(width);
        try {
            const trigger = row.getByRole('button',{name:'Editar mezclas habilitadas de Institución de prueba',exact:true});
            const dialog = page.locator('[data-enabled-services-dialog]');
            await trigger.click();
            await page.waitForFunction(() => !document.querySelector('[data-services-save]').disabled);
            assert.equal(await dialog.getByRole('heading',{name:'Mezclas habilitadas'}).count(),1);
            assert.equal(await dialog.getByRole('switch').count(),6);
            const oncology = dialog.locator('[role="switch"][data-service-key="oncology"]');
            const antibiotics = dialog.locator('[role="switch"][data-service-key="antibiotics"]');
            await dialog.locator('th').nth(1).getByRole('button',{name:'Inhabilitar todos',exact:true}).click();
            assert.equal(await oncology.first().getAttribute('aria-checked'),'false');
            assert.equal(await oncology.last().getAttribute('aria-checked'),'false');
            assert.equal(await antibiotics.last().getAttribute('aria-checked'),'true');
            await dialog.locator('th').nth(2).getByRole('button',{name:'Habilitar todos',exact:true}).click();
            await antibiotics.first().click();
            assert.equal(await antibiotics.first().getAttribute('aria-checked'),'false');
            assert.equal(await antibiotics.last().getAttribute('aria-checked'),'true');
            if (process.env.INSTITUTION_EDITOR_SCREENSHOTS) await page.screenshot({path:path.join(process.env.INSTITUTION_EDITOR_SCREENSHOTS,`institution-mixtures-${width}.png`)});
            await Promise.all([page.waitForEvent('load'),dialog.getByRole('button',{name:'Guardar cambios'}).click()]);
            assert.equal(state.patches.length,1);
            assert.deepEqual(state.patches[0].payload.hospital_services.map(row => row.services),[
                {oncology:false,antibiotics:false,nutrition:true},{oncology:false,antibiotics:true,nutrition:false},
            ]);
            await page.getByRole('button',{name:'Editar mezclas habilitadas de Institución vacía',exact:true}).click();
            await dialog.getByText('Esta institución no tiene hospitales asignados.').waitFor();
            assert.equal(await dialog.getByRole('button',{name:'Guardar cambios'}).isDisabled(),true);
            await dialog.locator('footer').getByRole('button',{name:'Cerrar',exact:true}).click();
            assert.deepEqual(state.errors,[]);
        } finally { await page.close(); }
    }
});

test('loading can retry; saving errors keep the popup and the edited input', async () => {
    const { page, state, row, dialog } = await pageFixture();
    try {
        state.getFailure = true;
        await row.locator('[data-field="razon_social"]').click();
        await dialog.getByText('No se pudo cargar.').waitFor();
        assert.equal(await dialog.getByRole('button',{name:'Guardar cambios'}).isDisabled(),true);
        state.getFailure = false;
        await dialog.getByRole('button',{name:'Reintentar'}).click();
        await ready(page);
        await dialog.locator('input:not([type="hidden"])').fill('Razón social corregida');
        state.patchFailure = true;
        await dialog.getByRole('button',{name:'Guardar cambios'}).click();
        await dialog.getByText('No se pudo guardar el valor de prueba.').waitFor();
        assert.equal(await dialog.locator('[data-institution-field-input]').inputValue(),'Razón social corregida');
        assert.equal(await dialog.getByRole('button',{name:'Guardar cambios'}).isEnabled(),true);
        await page.keyboard.press('Escape');
        assert.equal(await dialog.isVisible(),false);
        assert.deepEqual(state.errors,[]);
    } finally { await page.close(); }
});
