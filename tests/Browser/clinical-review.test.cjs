const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const html = execFileSync('php', ['tests/Browser/fixtures/clinical-review.php'], { cwd: root, encoding: 'utf8' });
const css = readFileSync(path.join(root, 'resources/css/clinical-review.css'), 'utf8');
const script = buildSync({ stdin: { contents: "import './resources/js/clinical-review.js'; import './resources/js/nutrition-manual-selection.js';", resolveDir: root }, bundle: true,
    write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;

const result = passed => ({ review_id: 'synthetic-receipt', can_submit: passed, can_view_internal: true,
    expires_at: new Date(Date.now() + 600000).toISOString(), result: {
        status: passed ? 'no_blockers' : 'blocked', summary: 'Resultado de prueba de interfaz; no es una evaluacion clinica.',
        findings: passed ? [] : [{ field: 'volumen_total', severity: 'blocking',
            message: 'Verifica el volumen. <img src=x onerror="window.clinicalXss=true">', calculation: 'Calculo de prueba.',
            suggestion: 'Volumen total: 1000 mL. Debe contener los 1200 mL de componentes, conforme a la orden medica. <img src=x onerror="window.suggestionXss=true">', source_ids: ['SYSTEM'] }],
        sources: [{ id: 'S1', title: 'Protocolo de prueba', reference: 'Seccion de prueba', reviewed: true, sha256: 'a'.repeat(64) }],
        calculations: [{ formula: 'Suma de componentes de prueba', result: 1200, unit: 'mL', assumptions: 'Datos sinteticos.' }],
        limitations: [], notice: 'Soporte de IA sujeto a revision profesional.' } });

test('clinical review gates submission, preserves notes and rejects stale responses on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            page.setDefaultTimeout(10000);
            const errors = []; page.on('pageerror', e => errors.push(e.message));
            let mode = 'blocked', calls = 0, saves = 0, waiting;
            await page.route('**/*', async route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/fixture') return route.fulfill({ contentType: 'text/html', body:
                    `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>
                    *{box-sizing:border-box}body{margin:0;background:#f0f5f7;font:14px Arial;color:#153962}
                    h1{font-size:22px}label,input,textarea{display:block}input,textarea{max-width:100%;padding:10px;border:1px solid #c8d8e8}
                    input[type=checkbox]{display:inline}textarea{width:100%;margin-top:8px}button{padding:12px;background:#0b735d;color:white;border:0;border-radius:4px}
                    ${css}</style></head><body>${html}<script>${script}</script></body></html>` });
                if (url.pathname === '/saved') { saves++; return route.fulfill({ contentType: 'text/html', body: 'Solicitud de prueba recibida' }); }
                assert.equal(route.request().method(), 'POST');
                assert.equal(route.request().headers()['x-csrf-token'], 'test-token');
                calls++;
                if (mode === 'delayed') await new Promise(resolve => { waiting = resolve; });
                if (mode === 'error') return route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ message: 'Servicio no disponible. No se habilito el envio.' }) });
                if (mode === 'without-suggestion') {
                    const data = result(false);
                    data.can_view_internal = false;
                    data.result.status = 'needs_review';
                    data.result.findings[0].severity = 'review';
                    data.result.findings[0].suggestion = '';
                    return route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) });
                }
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify(result(mode !== 'blocked')) });
            });
            await page.goto('http://localhost/fixture');
            assert.match(await page.locator('[data-npt-manual-description]').innerText(), /Nutrición Parenteral Adulto/);
            const setContextOpen = async open => {
                const context = page.locator('[data-clinical-context]');
                if (await context.evaluate(element => element.open) !== open) {
                    await page.getByText('Datos para la revision clinica', { exact: true }).click();
                }
            };
            await setContextOpen(true);
            const contextFields = page.locator('[data-clinical-context] textarea');
            assert.equal(await contextFields.count(), 6);
            for (const field of await contextFields.all()) await field.fill('Contexto sintetico para prueba de interfaz.');
            const button = page.locator('[data-clinical-submit]');
            await button.click();
            await page.getByText('SOLICITUD RECHAZADA', { exact: true }).waitFor();
            assert.equal(await page.getByText('Rechazo:', { exact: true }).isVisible(), true);
            assert.equal(await page.locator('[data-medical-authorization]').isVisible(), false);
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');
            assert.equal(await button.innerText(), 'Validar y Continuar');
            assert.equal(await page.locator('#volume').getAttribute('aria-invalid'), 'true');
            assert.equal(await page.locator('#notes').inputValue(), 'Nota del usuario');
            assert.equal(await page.locator('#volume').inputValue(), '1000');
            assert.equal(await page.locator('.clinical-suggestion').innerText(), 'Sugerencia: ' + result(false).result.findings[0].suggestion);
            assert.equal(await page.locator('.clinical-suggestion').isVisible(), true);
            assert.equal(await page.locator('img').count(), 0);
            assert.equal(await page.evaluate(() => window.clinicalXss), undefined);
            assert.equal(await page.evaluate(() => window.suggestionXss), undefined);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            assert.equal(saves, 0);
            if (process.env.CLINICAL_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.CLINICAL_SCREENSHOTS, `clinical-blocked-${width}.png`), fullPage: true });

            mode = 'without-suggestion'; await button.click();
            await page.getByText('Revision incompleta', { exact: true }).waitFor();
            assert.equal(await page.locator('.clinical-suggestion').count(), 0);
            assert.equal(await page.locator('[data-clinical-result] li').count(), 1);
            assert.equal(await page.locator('[data-clinical-result] details').count(), 0);
            assert.equal(await page.locator('[data-clinical-result] .clinical-notice').count(), 0);
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');
            assert.equal(await page.locator('[data-medical-authorization]').isVisible(), false);
            assert.equal(await page.locator('#volume').getAttribute('aria-invalid'), 'true');
            assert.equal(await button.innerText(), 'Validar y Continuar');
            assert.equal(saves, 0);

            mode = 'authorization';
            await setContextOpen(false);
            const authorizationResult = result(false);
            authorizationResult.requires_medical_authorization = true;
            authorizationResult.result.status = 'authorization_required';
            authorizationResult.result.findings = [{field:'i_4_g/Kg', severity:'authorization',
                message:'Desviacion de dosis de prueba que requiere autorizacion medica segun un protocolo ficticio.',
                calculation:'Caso simulado, no es una indicacion clinica.', source_ids:['S1']}];
            // Swap the response below using the same endpoint, without creating any real request.
            await page.route('**/*', async route => {
                if (mode !== 'authorization' || route.request().method() !== 'POST' || new URL(route.request().url()).pathname === '/saved') return route.fallback();
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify(authorizationResult) });
            });
            await button.click();
            await page.getByText('Advertencia: requiere autorizacion medica', { exact: true }).waitFor();
            assert.equal(await page.getByText('Advertencia:', { exact: true }).isVisible(), true);
            assert.equal(await page.locator('.clinical-suggestion').count(), 0);
            assert.equal(await page.locator('[data-medical-authorization]').isVisible(), true);
            await button.click();
            assert.equal(saves, 0);
            assert.equal(await page.locator('[name="medical_authorization[doctor_name]"]').getAttribute('aria-invalid'), 'true');
            assert.equal(await page.locator('[data-medical-authorization] input').count(), 2);
            for (const key of ['reference', 'authorized_at', 'reason', 'confirmed']) {
                assert.equal(await page.locator(`[name="medical_authorization[${key}]"]`).count(), 0);
            }
            for (const [key, value] of Object.entries({doctor_name:'Medico de prueba', doctor_license:'PRUEBA123'})) {
                await page.locator(`[name="medical_authorization[${key}]"]`).fill(value);
            }
            assert.equal(await page.locator('[data-medical-authorization] :invalid').count(), 0);
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), 'synthetic-receipt');
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            if (process.env.CLINICAL_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.CLINICAL_SCREENSHOTS, `clinical-authorization-${width}.png`), fullPage: true });
            await page.locator('#volume').fill('1300');
            assert.equal(await page.locator('[data-medical-authorization]').isVisible(), false);
            assert.equal(await page.locator('[name="medical_authorization[doctor_name]"]').inputValue(), '');
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');

            // An authorization from a previous warning cannot survive a rejected revalidation.
            mode = 'blocked'; await button.click();
            await page.getByText('SOLICITUD RECHAZADA', { exact: true }).waitFor();
            assert.equal(await page.locator('[data-medical-authorization]').isVisible(), false);
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');
            assert.equal(await button.innerText(), 'Validar y Continuar');
            assert.equal(saves, 0);

            const callsBeforeMissing = calls;
            await page.locator('#test-weight').fill('');
            await page.locator('#test-birth').fill('');
            await button.click();
            assert.equal(await page.locator('#test-weight').getAttribute('aria-invalid'), 'true');
            assert.equal(await page.locator('#test-birth').getAttribute('aria-invalid'), 'true');
            assert.equal(calls, callsBeforeMissing);
            assert.equal(saves, 0);
            if (process.env.CLINICAL_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.CLINICAL_SCREENSHOTS, `clinical-required-${width}.png`), fullPage: true });
            await page.locator('#test-weight').fill('70');
            await page.locator('#test-birth').fill('1986-01-01');

            mode = 'passed'; await button.click();
            await page.getByRole('button', { name: 'Enviar Solicitud', exact: true }).waitFor();
            await button.click();
            await page.getByText('Confirma que revisaste los hallazgos antes de enviar.', { exact: true }).waitFor();
            assert.equal(saves, 0);
            await page.locator('[name=npt]').selectOption('INF');
            assert.match(await page.locator('[data-npt-manual-description]').innerText(), /Nutrición Parenteral Pediátrico/);
            assert.equal(await page.locator('[data-clinical-result]').innerText(), '');
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');
            assert.equal(await button.innerText(), 'Validar y Continuar');
            await page.locator('#volume').fill('1100');
            assert.equal(await button.innerText(), 'Validar y Continuar');
            assert.equal(await page.locator('[name=clinical_review_token]').inputValue(), '');
            await setContextOpen(true);
            await page.locator('#clinical-context-allergies').fill('Contexto modificado para prueba.');

            mode = 'delayed'; const before = calls; await button.click();
            await page.waitForFunction(() => document.querySelector('[data-clinical-submit]').disabled);
            while (calls === before) await new Promise(resolve => setTimeout(resolve, 10));
            await page.locator('#volume').fill('1200'); waiting();
            await page.getByText('La solicitud cambio durante la revision. Vuelve a validar.', { exact: true }).waitFor();
            assert.equal(await button.innerText(), 'Validar y Continuar');
            assert.equal(saves, 0);
            mode = 'error'; await button.click();
            await page.getByText('Servicio no disponible. No se habilito el envio.', { exact: true }).waitFor();
            assert.equal(await button.innerText(), 'Validar y Continuar');
            mode = 'passed'; await button.click();
            await page.getByRole('button', { name: 'Enviar Solicitud', exact: true }).waitFor();
            await page.locator('[name=clinical_acknowledged]').check();
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            if (process.env.CLINICAL_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.CLINICAL_SCREENSHOTS, `clinical-ready-${width}.png`), fullPage: true });
            await button.click();
            await page.getByText('Solicitud de prueba recibida', { exact: true }).waitFor();
            assert.equal(saves, 1); assert.deepEqual(errors, []);
            await page.close();
        }
    } finally { await browser.close(); }
});
