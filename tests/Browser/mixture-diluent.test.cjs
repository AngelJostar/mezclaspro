const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = readFileSync(path.join(root, 'public/build', manifest['resources/css/app.css'].file), 'utf8');
const workflowCss = readFileSync(path.join(root, 'resources/css/mixture-workflow.css'), 'utf8');
const workflowJs = buildSync({ entryPoints: [path.join(root, 'resources/js/mixture-workflow.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(mode = 'dispensacion', width = 1320, oldInput = false) {
    const body = execFileSync('php', ['tests/Browser/fixtures/mixture-diluent.php', mode, oldInput ? 'old-input' : ''], { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => route.request().url() === 'http://mixture.test/'
        ? route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}${workflowCss}body{padding:20px}</style></head><body><script>window.testAlerts=[];window.Swal={fire(options){window.testAlerts.push(options);return Promise.resolve({isConfirmed:false});}};</script>${body}<script>${workflowJs}</script></body></html>` })
        : route.abort());
    await page.goto('http://mixture.test/');
    return { page, errors };
}

test('dispensing puts diluent beside its presentation and preserves selection and volume suggestions', async () => {
    for (const width of [1320, 390]) {
        const { page, errors } = await fixture('dispensacion', width);
        try {
            assert.equal(await page.locator('#medicamentos_mezcla [data-name="diluyente"]').count(), 0);
            assert.equal(await page.locator('#medicamentos_mezcla .medicamento-row > td').count(), 3);
            assert.equal(await page.locator('#medicamentos_mezcla .medicamento-detail-row > td').getAttribute('colspan'), '3');
            const diluent = page.locator('#diluyentes_mezcla select');
            const presentation = page.getByLabel('Presentación del diluyente', { exact: true });
            assert.equal(await diluent.inputValue(), '1');
            assert.equal(await presentation.inputValue(), '501');
            const a = await diluent.boundingBox();
            const b = await presentation.boundingBox();
            if (width > 768) {
                assert.ok(Math.abs(a.y - b.y) < 2);
                assert.ok(a.x + a.width <= b.x);
            } else {
                assert.ok(a.y + a.height <= b.y);
                assert.ok(b.x >= 0 && b.x + b.width <= width);
            }
            await diluent.selectOption('2');
            assert.equal(await presentation.inputValue(), '601');
            assert.equal(await presentation.locator('option[value="501"]').count(), 0);
            await diluent.selectOption('1');
            await page.locator('[data-name="volumen_dilucion"]').fill('500');
            assert.equal(await presentation.inputValue(), '502');
            await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
            const payload = JSON.parse(await page.locator('#mezcla_json').inputValue());
            assert.equal(payload.medicamentos[0].diluyente_id, '1');
            assert.equal(payload.diluent_presentation_id, '502');
            assert.equal(payload.volumen_dilucion, '500');
            assert.deepEqual(errors, []);
            if (process.env.DILUENT_SCREENSHOT_DIR) {
                await page.locator('[data-diluent-fields]').screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `mixture-diluent-${width}.png`) });
            }
        } finally { await page.close(); }
    }
});

test('new medication rows keep their own moved controls and incompatible diluents remain blocked', async () => {
    const { page, errors } = await fixture();
    try {
        await page.getByRole('button', { name: '+ Agregar Medicamento', exact: true }).click();
        await page.locator('#fila_2 [data-name="medicamento"]').selectOption('20');
        assert.equal(await page.locator('#diluyentes_mezcla select').count(), 2);
        assert.equal(await page.locator('#diluyente_label_fila_1').innerText(), 'Medicamento Uno');
        assert.equal(await page.locator('#diluyente_label_fila_2').innerText(), 'Medicamento Dos');
        assert.equal(await page.locator('#diluyente_fila_2 option[value="2"]').count(), 0);
        await page.locator('#diluyente_fila_2').selectOption('1');
        await page.locator('#fila_2 .dosis-input').fill('10');
        await page.locator('#detalle_fila_2 [data-name="via_administracion"]').selectOption('1');
        await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
        const payload = JSON.parse(await page.locator('#mezcla_json').inputValue());
        assert.deepEqual(payload.medicamentos.map(item => item.diluyente_id), ['1', '1']);
        await page.locator('#diluyente_fila_1').selectOption('2');
        await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
        const alert = await page.evaluate(() => window.testAlerts.at(-1));
        assert.equal(alert.icon, 'error');
        assert.match(alert.text, /mismo diluyente/);
        await page.locator('#fila_1 [data-name="medicamento"]').selectOption('20');
        assert.equal(await page.locator('#diluyente_label_fila_1').innerText(), 'Medicamento Dos');
        assert.equal(await page.locator('#diluyente_fila_1').inputValue(), '');
        assert.equal(await page.locator('#diluyente_fila_1 option[value="2"]').count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('infusion time sits beside the infusor and retains its value in the submitted mixture', async () => {
    for (const mode of ['dispensacion', 'edicion']) {
        for (const width of [1320, 390]) {
            const { page, errors } = await fixture(mode, width);
            try {
                const time = page.getByLabel('Tiempo de infusión (min)*', { exact: true });
                const infusor = page.getByLabel('Infusor', { exact: true });
                assert.equal(await page.locator('[data-name="tiempo_infusion"]').count(), 1);
                assert.equal(await time.inputValue(), '15');
                assert.equal(await infusor.isDisabled(), true);
                const a = await time.boundingBox();
                const b = await infusor.boundingBox();
                if (width > 768) {
                    assert.ok(Math.abs(a.y - b.y) < 2);
                    assert.ok(a.x + a.width <= b.x);
                } else {
                    assert.ok(a.y + a.height <= b.y);
                }
                for (const box of [a, b]) assert.ok(box.x >= 0 && box.x + box.width <= width);
                await time.fill('60');
                await page.getByRole('button', {
                    name: mode === 'dispensacion' ? 'Guardar Dispensacion' : 'ACTUALIZAR MEZCLA', exact: true,
                }).click();
                const payload = JSON.parse(await page.locator('#mezcla_json').inputValue());
                assert.equal(payload.tiempo_infusion, '60');
                assert.equal(payload.volumen_dilucion, '250');
                assert.deepEqual(errors, []);
                if (process.env.DILUENT_SCREENSHOT_DIR && mode === 'dispensacion') {
                    await page.locator('[data-infusion-fields]').screenshot({
                        path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `mixture-infusion-${width}.png`),
                    });
                }
            } finally { await page.close(); }
        }
    }
});

test('editing outside dispensing retains the original table and diluent serialization', async () => {
    const { page, errors } = await fixture('edicion');
    try {
        assert.equal(await page.locator('#medicamentos_mezcla [data-name="diluyente"]').count(), 1);
        assert.equal(await page.locator('#medicamentos_mezcla .medicamento-row > td').count(), 4);
        assert.equal(await page.locator('[data-diluent-fields]').isVisible(), false);
        await page.locator('#diluyente_fila_1').selectOption('2');
        await page.getByRole('button', { name: 'ACTUALIZAR MEZCLA', exact: true }).click();
        const payload = JSON.parse(await page.locator('#mezcla_json').inputValue());
        assert.equal(payload.medicamentos[0].diluyente_id, '2');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('compact patient sections match the reference and preserve all submitted values', async () => {
    const groups = [
        ['paciente_nombre', 'registro', 'sexo', 'fecha_nacimiento', 'peso'],
        ['servicio', 'piso', 'cama', 'fecha_entrega'],
        ['diagnostico', 'medico_nombre', 'medico_cedula', 'observaciones'],
    ];
    for (const width of [1320, 820, 390]) {
        const { page, errors } = await fixture('dispensacion', width);
        try {
            assert.deepEqual(await page.locator('.dispensing-patient-section h2').allTextContents()
                .then(titles => titles.map(title => title.trim())), ['Datos del paciente', 'Atención hospitalaria', 'Información clínica']);
            assert.equal(await page.locator('.dispensing-patient-section h2 svg').count(), 3);
            for (let i = 0; i < groups.length; i++) {
                const inputs = page.locator('.dispensing-patient-grid').nth(i).locator('input, select');
                assert.deepEqual(await inputs.evaluateAll(elements => elements.map(el => el.name)), groups[i]);
                for (const name of groups[i]) assert.equal(await page.locator(`[name="${name}"]`).count(), 1);
                const boxes = await inputs.evaluateAll(elements => elements.map(el => {
                    const { x, y, width, height } = el.getBoundingClientRect();
                    return { x, y, width, height };
                }));
                for (const box of boxes) assert.ok(box.x >= 0 && box.x + box.width <= width);
                if (width >= 820) {
                    for (const box of boxes.slice(0, i === 2 ? 3 : undefined)) assert.equal(box.y, boxes[0].y);
                }
            }
            const values = await page.locator('#formularioMezcla').evaluate(form => Object.fromEntries(new FormData(form)));
            assert.equal(values.paciente_nombre, 'Paciente de Prueba');
            assert.equal(values.registro, 'REG-123');
            assert.equal(values.sexo, 'F');
            assert.equal(values.fecha_nacimiento, '1986-09-10');
            assert.equal(values.peso, '57.00');
            assert.equal(values.servicio, 'HOSPITALIZACION');
            assert.equal(values.piso, '2');
            assert.equal(values.cama, '201');
            assert.equal(values.fecha_entrega, '2026-09-04T18:21');
            assert.equal(values.diagnostico, 'Diagnostico de prueba');
            assert.equal(values.medico_nombre, 'Medico de Prueba');
            assert.equal(values.medico_cedula, '123456');
            assert.equal(values.observaciones, 'Observaciones de prueba');
            assert.match(await page.locator('[data-workflow-heading]').innerText(), /#1 \| Institución: Institucion de Prueba \| Hospital: Hospital de Prueba/);
            assert.deepEqual(errors, []);
            if (process.env.DILUENT_SCREENSHOT_DIR) {
                await page.locator('[data-dispensing-patient]').screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `dispensing-patient-${width}.png`) });
            }
            if (width >= 820) {
                const height = (await page.locator('[data-dispensing-patient]').boundingBox()).height;
                assert.ok(height < 360, `Patient section at ${width}px has height ${height}px`);
            }
        } finally { await page.close(); }
    }
    const { page } = await fixture('dispensacion', 1320, true);
    try {
        assert.equal(await page.locator('#paciente_nombre').inputValue(), 'Nombre actualizado');
        assert.equal(await page.locator('#registro').inputValue(), 'REG-456');
        assert.equal(await page.locator('#sexo').inputValue(), 'M');
    } finally { await page.close(); }
});
