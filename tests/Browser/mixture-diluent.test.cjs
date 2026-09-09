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
const modalCss = readFileSync(path.join(root, 'resources/css/workflow-modal.css'), 'utf8');
const workflowJs = buildSync({ entryPoints: [path.join(root, 'resources/js/mixture-workflow.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(mode = 'dispensacion', width = 1320, oldInput = false, remainders = false, category = 'oncologicos') {
    const body = execFileSync('php', ['tests/Browser/fixtures/mixture-diluent.php', mode, oldInput ? 'old-input' : '', typeof remainders === 'string' ? remainders : remainders ? 'remainders' : '', category], { cwd: root, encoding: 'utf8' });
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const embedded = mode === 'aprobacion';
    const content = embedded ? `<main class="admin-page workflow-page"><div class="admin-content">${body}</div></main>` : body;
    await page.route('**/*', route => route.request().url() === 'http://mixture.test/'
        ? route.fulfill({ contentType: 'text/html', body: `<!doctype html><html class="${embedded ? 'workflow-embedded' : ''}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}${workflowCss}${modalCss}body{padding:${embedded ? '0' : '20px'}}</style></head><body><script>window.testAlerts=[];window.Swal={fire(options){window.testAlerts.push(options);return Promise.resolve({isConfirmed:false});}};</script>${content}<script>${workflowJs}</script></body></html>` })
        : route.abort());
    await page.goto('http://mixture.test/');
    return { page, errors };
}

test('dispensing proposes vials and informational remaining mg, recalculating dose, lot, presentation and manual changes', async () => {
    for (const category of ['oncologicos', 'antibioticos']) {
        for (const width of [1320, 390]) {
            const { page, errors } = await fixture('dispensacion', width, false, 'proposal', category);
            try {
                assert.equal(await page.locator('#presentaciones_body_1 [data-presentation-id="13"] .input-frascos').inputValue(), '4');
                await page.locator('#presentacion_selector_1').selectOption('11');
                const row = page.locator('#presentaciones_body_1 [data-presentation-id="11"]');
                const quantity = row.locator('.input-frascos');
                const estimate = row.locator('.estimated-remainder');
                assert.equal(await quantity.inputValue(), '8');
                assert.equal(await estimate.innerText(), '200.00 mg');
                assert.match(await page.locator('#resumen_dosis_1').innerText(), /Dosis cubierta: 1800.00 mg/);
                assert.equal(await page.locator('#resumen_dosis_1 svg').count(), 1);
                await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
                const payload = JSON.parse(await page.locator('#mezcla_json').inputValue());
                assert.equal(payload.medicamentos[0].presentaciones.length, 1);
                assert.equal(payload.medicamentos[0].presentaciones[0].frascos, 8);
                assert.equal(payload.medicamentos[0].presentaciones[0].batch_id, '100');
                assert.equal(JSON.stringify(payload).includes('estimatedRemainder'), false);

                await row.locator('.batch-select').selectOption('101');
                assert.equal(await quantity.inputValue(), '7');
                assert.equal(await estimate.innerText(), '50.00 mg');
                await row.locator('.batch-select').selectOption('102');
                assert.equal(await quantity.inputValue(), '0');
                assert.equal(await estimate.innerText(), '200.00 mg');
                await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
                const remainderOnly = JSON.parse(await page.locator('#mezcla_json').inputValue()).medicamentos[0].presentaciones;
                assert.equal(remainderOnly.length, 1);
                assert.equal(remainderOnly[0].frascos, 0);
                assert.equal(remainderOnly[0].batch_id, '102');

                await row.locator('.batch-select').selectOption('103');
                assert.equal(await quantity.inputValue(), '7');
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), '0.00 mg');
                assert.equal(await estimate.innerText(), '50.00 mg');
                assert.match(await page.locator('#resumen_dosis_1').innerText(), /Remanente a utilizar: 100.00 mg/);

                await row.locator('.batch-select').selectOption('104');
                assert.equal(await quantity.inputValue(), '2');
                assert.equal(await estimate.innerText(), '-');
                assert.match(await page.locator('#resumen_dosis_1').innerText(), /faltan 1300.00 mg/);

                await row.locator('.batch-select').selectOption('100');
                await page.locator('#fila_1 .dosis-input').fill('2000');
                await page.locator('#presentacion_selector_1').selectOption('11');
                assert.equal(await quantity.inputValue(), '8');
                assert.equal(await estimate.innerText(), '0.00 mg');
                await page.locator('#fila_1 .dosis-input').fill('1800');
                await page.locator('#presentacion_selector_1').selectOption('11');
                await quantity.fill('7');
                assert.equal(await estimate.innerText(), '-');
                assert.match(await page.locator('#resumen_dosis_1').innerText(), /faltan 50.00 mg/);
                await quantity.fill('9');
                assert.equal(await estimate.innerText(), '200.00 mg');

                await page.locator('#presentacion_selector_1').selectOption('13');
                assert.equal(await quantity.inputValue(), '0');
                const alternative = page.locator('#presentaciones_body_1 [data-presentation-id="13"]');
                assert.equal(await alternative.locator('.input-frascos').inputValue(), '4');
                assert.equal(await alternative.locator('.estimated-remainder').innerText(), '200.00 mg');
                await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
                const alternativePayload = JSON.parse(await page.locator('#mezcla_json').inputValue()).medicamentos[0].presentaciones;
                assert.equal(alternativePayload.length, 1);
                assert.equal(alternativePayload[0].batch_id, '300');

                await page.getByRole('button', { name: '+ Agregar Medicamento', exact: true }).click();
                await page.locator('#fila_2 [data-name="medicamento"]').selectOption('20');
                await page.locator('#fila_2 .dosis-input').fill('1800');
                const addedRow = page.locator('#presentaciones_body_2 .presentacion-row').first();
                assert.equal(await addedRow.locator('.input-frascos').inputValue(), '2');
                assert.equal(await addedRow.locator('.estimated-remainder').innerText(), '200.00 mg');
                assert.deepEqual(errors, []);
                if (process.env.DILUENT_SCREENSHOT_DIR) {
                    await page.locator('#presentaciones_body_1').screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `dispensing-proposal-${category}-${width}.png`) });
                }
            } finally { await page.close(); }
        }
    }
});

test('combines same-brand presentations, recalculates the complete dose, and blocks incomplete manual selections', async () => {
    for (const width of [1320, 390]) {
        const { page, errors } = await fixture('dispensacion', width, false, 'combined');
        try {
            const large = page.locator('#presentaciones_body_1 [data-presentation-id="14"]');
            const small = page.locator('#presentaciones_body_1 [data-presentation-id="11"]');
            const other = page.locator('#presentaciones_body_1 [data-presentation-id="16"]');
            const summary = page.locator('#resumen_dosis_1');
            assert.deepEqual(await page.locator('#presentaciones_body_1 .presentacion-row').evaluateAll(rows => rows.map(row => row.dataset.cantidadMg)), ['600', '400', '100']);
            assert.equal(await large.locator('.input-frascos').inputValue(), '2');
            assert.equal(await small.locator('.input-frascos').inputValue(), '2');
            assert.equal(await other.locator('.input-frascos').inputValue(), '0');
            assert.equal(await page.locator('#presentaciones_body_1 [data-dispensing-selected="1"]').count(), 2);
            assert.match(await summary.innerText(), /Dosis cubierta: 1000.00 mg/);
            await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
            let payload = JSON.parse(await page.locator('#mezcla_json').inputValue()).medicamentos[0];
            assert.equal(Number(payload.dosis), 1000);
            assert.deepEqual(payload.presentaciones.map(row => [row.batch_id, row.frascos]), [['140', 2], ['110', 2]]);
            await page.locator('#fila_1 .dosis-input').fill('850');
            assert.equal(await large.locator('.input-frascos').inputValue(), '2');
            assert.equal(await small.locator('.input-frascos').inputValue(), '1');
            assert.equal(await small.locator('.estimated-remainder').innerText(), '50.00 mg');
            if (process.env.DILUENT_SCREENSHOT_DIR) {
                await page.locator('.dispensing-mixture').screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `combined-dispensing-${width}.png`) });
            }
            await large.locator('.input-frascos').fill('1');
            assert.match(await summary.innerText(), /faltan 350.00 mg/);
            await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
            assert.equal(await page.evaluate(() => window.testAlerts.at(-1).title), 'Dosis sin cubrir');
            await large.locator('.batch-select').selectOption('141');
            assert.match(await summary.innerText(), /faltan 250.00 mg/);
            await page.locator('#presentacion_selector_1').selectOption('16');
            assert.equal(await other.locator('.input-frascos').inputValue(), '2');
            assert.equal(await large.locator('.input-frascos').inputValue(), '0');
            assert.equal(await small.locator('.input-frascos').inputValue(), '0');
            await page.getByRole('button', { name: 'Guardar Dispensacion', exact: true }).click();
            payload = JSON.parse(await page.locator('#mezcla_json').inputValue()).medicamentos[0];
            assert.deepEqual(payload.presentaciones.map(row => row.batch_id), ['160']);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
            assert.deepEqual(errors, []);
        } finally { await page.close(); }
    }
});

test('oncology and antibiotic dispensing show converted mg for each lot and new medication row', async () => {
    for (const category of ['oncologicos', 'antibioticos']) {
        for (const width of [1320, 390]) {
            const { page, errors } = await fixture('dispensacion', width, false, true, category);
            try {
                const row = page.locator('#presentaciones_body_1 .presentacion-row').first();
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), '50.00 mg');
                assert.equal(await row.getAttribute('data-remanente-ml'), '2');
                await row.locator('.batch-select').selectOption('101');
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), '3.09 mg');
                assert.equal(await row.getAttribute('data-remanente-ml'), '0.1234');
                await row.locator('.batch-select').selectOption('102');
                assert.equal((await row.locator('.selected-remainder').innerText()).trim(), '0.00 mg');
                assert.equal(await row.locator('.remainder-waste-button').isDisabled(), true);
                await row.locator('.batch-select').selectOption('100');
                assert.equal(await row.locator('.remainder-waste-button').isEnabled(), true);
                assert.equal((await page.locator('#presentaciones_body_1 .selected-remainder').nth(1).innerText()).trim(), 'Sin concentracion');
                await page.getByRole('button', { name: '+ Agregar Medicamento', exact: true }).click();
                await page.locator('#fila_2 [data-name="medicamento"]').selectOption('20');
                assert.equal((await page.locator('#presentaciones_body_2 .selected-remainder').first().innerText()).trim(), '500.00 mg');
                assert.deepEqual(errors, []);
                if (process.env.DILUENT_SCREENSHOT_DIR) {
                    await page.locator('#presentaciones_body_1').screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `remainder-${category}-${width}.png`) });
                }
            } finally { await page.close(); }
        }
    }
});

test('approval and rejection buttons align with the right edge of the form at all viewport sizes', async () => {
    for (const width of [1320, 768, 390, 320]) {
        const { page, errors } = await fixture('aprobacion', width);
        try {
            const actions = page.locator('[data-workflow-approval-actions]');
            const approve = actions.getByRole('button', { name: 'APROBAR MEZCLA', exact: true });
            const reject = actions.getByRole('button', { name: 'RECHAZAR MEZCLA', exact: true });
            const a = await approve.boundingBox();
            const b = await reject.boundingBox();
            const form = await page.locator('#formularioMezcla').boundingBox();
            assert.ok(Math.abs(b.x + b.width - form.x - form.width) < 2);
            assert.ok(a.y + a.height <= b.y || a.x + a.width <= b.x);
            for (const box of [a, b]) {
                assert.ok(box.x >= 0 && box.x + box.width <= width);
                assert.ok(box.y + box.height <= form.y);
            }
            if (width >= 768) assert.equal(a.y, b.y);
            assert.equal(await approve.getAttribute('form'), 'formularioMezcla');
            assert.equal(await reject.getAttribute('form'), 'formularioMezcla');
            assert.deepEqual(errors, []);
            if (process.env.DILUENT_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.DILUENT_SCREENSHOT_DIR, `approval-actions-${width}.png`) });
            }
        } finally { await page.close(); }
    }
});

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
            assert.equal(await page.locator('#diluent_presentation_hint').innerText(), 'Lote: DIL-250 · Caducidad: 30/04/2027');
            assert.equal(await presentation.locator('option:checked').innerText(), '250 mL · Marca A');
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
            assert.equal(await page.locator('#diluent_presentation_hint').innerText(), 'Lote: DIL-500 · Caducidad: 30/06/2027');
            await presentation.selectOption('501');
            assert.equal(await page.locator('#diluent_presentation_hint').innerText(), 'Lote: DIL-250 · Caducidad: 30/04/2027');
            await presentation.selectOption('502');
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
                const time = page.getByLabel(mode === 'dispensacion' ? 'Tiempo de infusión*' : 'Tiempo de infusión (min)*', { exact: true });
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
