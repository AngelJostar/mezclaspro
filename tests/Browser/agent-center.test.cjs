const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { spawn } = require('node:child_process');
const { createInterface } = require('node:readline');
const { once } = require('node:events');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');

const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const icons = buildSync({ entryPoints: [path.join(root, 'resources/js/agent-center.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const livewire = readFileSync(path.join(root, 'vendor/livewire/livewire/dist/livewire.js'), 'utf8');

function fixture(count = 8) {
    const php = spawn('php', ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3',
        'tests/Browser/fixtures/agent-center.php', String(count)], { cwd: root, stdio: ['pipe', 'pipe', 'pipe'],
        env: { ...process.env, CACHE_DRIVER: 'array', SESSION_DRIVER: 'array' } });
    const waiting = [];
    let stderr = '';
    php.stderr.on('data', data => { stderr += data; });
    const lines = createInterface({ input: php.stdout });
    lines.on('line', line => {
        const pending = waiting.shift();
        if (!pending) return;
        try {
            const response = JSON.parse(line);
            if (response.fixture_error) pending.reject(new Error(response.fixture_error));
            else pending.resolve(response);
        } catch (error) { pending.reject(error); }
    });
    php.on('exit', code => waiting.splice(0).forEach(pending => pending.reject(new Error(`PHP exited ${code}: ${stderr}`))));
    return {
        send(command) {
            return new Promise((resolve, reject) => {
                const timer = setTimeout(() => reject(new Error(`Fixture timed out: ${stderr}`)), 15000);
                waiting.push({ resolve: value => { clearTimeout(timer); resolve(value); }, reject: error => { clearTimeout(timer); reject(error); } });
                php.stdin.write(`${JSON.stringify(command)}\n`);
            });
        },
        async close() {
            const exited = once(php, 'exit');
            php.stdin.end();
            await exited;
            lines.close();
        },
    };
}

test('agent carousel toggles information and creates, edits and persists agents through Livewire', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1366, 390, 320]) {
            const server = fixture('promesa');
            const page = await browser.newPage({ viewport: { width, height: 850 } });
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            try {
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.origin !== 'http://agents.test') return route.abort();
                    if (url.pathname === '/livewire/update') {
                        const result = await server.send(route.request().postDataJSON());
                        return route.fulfill({ contentType: 'application/json', body: JSON.stringify(result) });
                    }
                    const result = await server.send({ type: 'render' });
                    await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="es"><head>
                        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
                        <style>${styles}</style>${result.styles}</head><body><main class="admin-content"><h1>Superadministrador</h1>${result.html}</main>
                        <script>${icons}</script><script>window.livewireScriptConfig={uri:'http://agents.test/livewire/update',csrf:'test'};</script>
                        <script>${livewire}</script><script>window.Livewire.start();</script></body></html>` });
                });
                await page.goto('http://agents.test/panel');
                const track = page.locator('.agent-carousel-track');
                const choices = page.locator('.agent-choice');
                assert.equal(await choices.count(), 14);
                assert.equal(await page.locator('[data-agent-information]').count(), 0);
                assert.equal(await page.getByRole('button', { name: 'Todos', exact: true }).getAttribute('aria-expanded'), 'false');
                assert.equal(await page.locator('.agent-choice[aria-pressed="true"]').count(), 0);
                const dimensions = await choices.evaluateAll(els => els.map(el => {
                    const box = el.getBoundingClientRect();
                    const label = el.querySelector('.agent-choice-name');
                    return { width: box.width, height: box.height, clipped: label.scrollHeight > label.clientHeight + 1 };
                }));
                for (const box of dimensions) {
                    assert.equal(box.width, 96);
                    assert.equal(box.height, 96);
                    assert.equal(box.clipped, false, `Clipped agent name at ${width}px`);
                }
                if (process.env.AGENT_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.AGENT_SCREENSHOTS, `agent-squares-${width}.png`) });
                }
                const next = page.getByRole('button', { name: 'Agentes siguientes', exact: true });
                await page.waitForFunction(() => !document.querySelector('[aria-label="Agentes siguientes"]').disabled);
                assert.equal(await page.getByRole('button', { name: 'Agentes anteriores', exact: true }).isDisabled(), true);
                await next.click();
                await page.waitForFunction(() => document.querySelector('.agent-carousel-track').scrollLeft > 50);
                await page.waitForFunction(() => !document.querySelector('[aria-label="Agentes anteriores"]').disabled);
                assert.equal(await page.getByRole('button', { name: 'Agentes anteriores', exact: true }).isDisabled(), false);
                assert.equal(await page.locator('[data-agent-select]').count(), 12);
                assert.equal(await page.locator('[data-agent-information]').count(), 0);
                const first = page.locator('[data-agent-select="1"]');
                await first.click();
                await page.waitForFunction(() => document.querySelectorAll('[data-agent-information]').length === 1);
                assert.equal(await first.getAttribute('aria-pressed'), 'true');
                const activation = page.getByRole('switch', { name: 'Estado de Auditoría de consumos', exact: true });
                assert.equal(await activation.getAttribute('aria-checked'), 'false');
                assert.equal(await page.locator('[data-agent-select][data-active="false"]').count(), 12);
                await activation.click();
                await page.waitForFunction(() => document.querySelector('.agent-switch').getAttribute('aria-checked') === 'true');
                await page.waitForFunction(() => getComputedStyle(document.querySelector('.agent-switch-thumb')).transform === 'matrix(1, 0, 0, 1, 42, 0)');
                assert.equal(await page.locator('.agent-status').count(), 0);
                assert.equal(await page.locator('.agent-activation > span').count(), 0);
                assert.equal(await first.getAttribute('data-active'), 'true');
                assert.equal(await page.locator('[data-agent-select][data-active="false"]').count(), 11);
                const activeColor = await activation.evaluate(el => getComputedStyle(el).backgroundColor);
                assert.ok(['rgb(22, 163, 74)', 'rgb(21, 128, 61)'].includes(activeColor));
                assert.equal(await first.locator('.agent-state-dot').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
                const switchBox = await activation.boundingBox();
                assert.equal(switchBox.width, 70);
                assert.equal(switchBox.height, 28);
                await page.reload();
                assert.equal(await first.getAttribute('data-active'), 'true');
                assert.equal(await page.locator('[data-agent-information]').count(), 0);
                await first.click();
                await page.waitForFunction(() => document.querySelector('.agent-switch')?.getAttribute('aria-checked') === 'true');
                if (process.env.AGENT_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.AGENT_SCREENSHOTS, `agent-active-${width}.png`), fullPage: true });
                }
                await activation.focus();
                await page.keyboard.press('Space');
                await page.waitForFunction(() => document.querySelector('.agent-switch').getAttribute('aria-checked') === 'false');
                await page.waitForFunction(() => getComputedStyle(document.querySelector('.agent-switch-thumb')).transform === 'none');
                assert.equal(await page.locator('.agent-status').count(), 0);
                assert.equal(await page.locator('.agent-activation > span').count(), 0);
                assert.equal(await first.getAttribute('data-active'), 'false');
                assert.equal(await first.locator('.agent-state-dot').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(220, 38, 38)');
                assert.equal(await activation.evaluate(el => {
                    const thumb = el.querySelector('.agent-switch-thumb').getBoundingClientRect();
                    const label = el.querySelector('.agent-switch-text').getBoundingClientRect();
                    return thumb.right <= label.left;
                }), true);
                if (process.env.AGENT_SCREENSHOTS) {
                    await page.screenshot({ path: path.join(process.env.AGENT_SCREENSHOTS, `agent-inactive-${width}.png`), fullPage: true });
                }
                await first.click();
                await page.waitForFunction(() => document.querySelectorAll('[data-agent-information]').length === 0);
                await page.getByRole('button', { name: 'Todos', exact: true }).click();
                await page.waitForFunction(() => document.querySelectorAll('[data-agent-information]').length === 12);
                await page.getByRole('button', { name: 'Todos', exact: true }).click();
                await page.waitForFunction(() => document.querySelectorAll('[data-agent-information]').length === 0);
                await page.getByRole('button', { name: 'Crear nuevo', exact: true }).click();
                const dialog = page.getByRole('dialog');
                await dialog.waitFor();
                await page.waitForFunction(() => document.activeElement.id === 'agent-name');
                await dialog.getByLabel('Nombre', { exact: true }).fill('   ');
                await dialog.getByRole('button', { name: 'Guardar', exact: true }).click();
                await page.getByText('Escribe el nombre del agente.', { exact: true }).waitFor();
                const agentName = 'Agente nuevo de prueba';
                await dialog.getByLabel('Nombre', { exact: true }).fill(agentName);
                await dialog.getByLabel('Descripción', { exact: true }).fill('Descripcion del agente nuevo');
                await dialog.getByLabel('Instrucciones', { exact: true }).fill('Primera instruccion.\nSegunda instruccion.');
                if (process.env.AGENT_SCREENSHOTS && width !== 320) {
                    await page.screenshot({ path: path.join(process.env.AGENT_SCREENSHOTS, `agent-form-${width}.png`) });
                }
                const box = await dialog.boundingBox();
                assert.ok(box.x >= 0 && box.x + box.width <= width && box.y >= 0 && box.y + box.height <= 850);
                assert.equal(await dialog.evaluate(el => el.scrollWidth > el.clientWidth), false);
                await dialog.getByRole('button', { name: 'Guardar', exact: true }).click();
                await dialog.waitFor({ state: 'hidden' });
                await page.getByRole('status').filter({ hasText: 'Agente creado.' }).waitFor();
                assert.equal(await page.locator('[data-agent-select]').count(), 13);
                assert.equal(await page.locator('[data-agent-information]').count(), 1);
                assert.equal(await page.getByRole('button', { name: agentName, exact: true }).getAttribute('aria-pressed'), 'true');
                await page.getByRole('button', { name: `Editar agente ${agentName}`, exact: true }).click();
                await dialog.waitFor();
                assert.equal(await dialog.getByLabel('Nombre', { exact: true }).inputValue(), agentName);
                await dialog.getByLabel('Nombre', { exact: true }).fill('Cambio cancelado');
                await dialog.getByRole('button', { name: 'Cancelar', exact: true }).click();
                await dialog.waitFor({ state: 'hidden' });
                assert.equal(await page.getByRole('button', { name: agentName, exact: true }).count(), 1);
                await page.getByRole('button', { name: `Editar agente ${agentName}`, exact: true }).click();
                await dialog.getByLabel('Nombre', { exact: true }).fill('Agente actualizado de prueba');
                await dialog.getByRole('button', { name: 'Guardar', exact: true }).click();
                await dialog.waitFor({ state: 'hidden' });
                await page.getByRole('status').filter({ hasText: 'Agente actualizado.' }).waitFor();
                if (process.env.AGENT_SCREENSHOTS && width !== 320) {
                    await page.screenshot({ path: path.join(process.env.AGENT_SCREENSHOTS, `agent-center-${width}.png`) });
                }
                await page.reload();
                await page.getByRole('button', { name: 'Agente actualizado de prueba', exact: true }).waitFor();
                assert.equal(await page.locator('[data-agent-information]').count(), 0);
                assert.equal(await page.getByRole('button', { name: 'Todos', exact: true }).getAttribute('aria-expanded'), 'false');
                await page.getByRole('button', { name: 'Agente actualizado de prueba', exact: true }).click();
                await page.waitForFunction(() => document.querySelectorAll('[data-agent-information]').length === 1);
                assert.equal(await page.locator('.agent-instructions').filter({ hasText: 'Primera instruccion.' }).count(), 1);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
                assert.equal(await track.evaluate(el => el.getBoundingClientRect().right > innerWidth), false);
                assert.equal(await page.locator('.agent-choice').evaluateAll(els => els.some(el => el.scrollWidth > el.clientWidth + 1)), false);
                assert.ok(await page.locator('svg[data-agent-icon="bot"]').count() >= 12);
                await page.getByRole('button', {name: 'Caducidades y mermas', exact: true}).click();
                await page.getByRole('button', {name: 'Editar agente Caducidades y mermas', exact: true}).click();
                await dialog.getByRole('checkbox', {name: 'Central A', exact: true}).check();
                await dialog.getByLabel('Responsable de seguimiento (persona o área)', {exact: true}).fill('Almacén A');
                await dialog.getByLabel('Caducidad: anticipación (días)', {exact: true}).fill('10');
                assert.equal(await dialog.locator('.agent-check input').evaluateAll(els => els.some(el => el.getBoundingClientRect().height !== 16)), false);
                assert.equal(await dialog.evaluate(el => el.scrollWidth > el.clientWidth), false);
                if (process.env.AGENT_SCREENSHOTS) await page.screenshot({path: path.join(process.env.AGENT_SCREENSHOTS, `agent-config-${width}.png`)});
                await dialog.getByRole('button', {name: 'Guardar', exact: true}).click();
                await dialog.waitFor({state: 'hidden'});
                await page.getByRole('switch', {name: 'Estado de Caducidades y mermas', exact: true}).click();
                await page.waitForFunction(() => document.querySelector('.agent-switch').getAttribute('aria-checked') === 'true');
                await page.getByRole('button', {name: 'Ejecutar ahora', exact: true}).click();
                await page.getByRole('status').filter({hasText: 'Completada'}).waitFor();
                assert.equal(await page.locator('.agent-finding').count(), 1);
                await page.locator('.agent-finding > summary').click();
                await page.getByRole('button', {name: 'Actualizar seguimiento', exact: true}).click();
                await dialog.getByLabel('Estado', {exact: true}).selectOption('resolved');
                await dialog.getByLabel('Justificación y evidencia de seguimiento', {exact: true}).fill('Lote revisado con responsable de almacén.');
                await dialog.getByRole('button', {name: 'Guardar seguimiento', exact: true}).click();
                await dialog.waitFor({state: 'hidden'});
                await page.locator('.agent-findings-heading select').selectOption('resolved');
                await page.locator('.agent-finding').waitFor();
                await page.getByRole('button', {name: 'Ejecutar ahora', exact: true}).click();
                await page.getByRole('status').filter({hasText: 'Ejecución #2: Completada'}).waitFor();
                assert.equal(await page.locator('.agent-finding').count(), 1);
                await page.reload();
                await page.getByRole('button', {name: 'Caducidades y mermas', exact: true}).click();
                await page.getByText('2 ejecuciones', {exact:true}).waitFor();
                assert.equal(await page.locator('.agent-finding').count(), 0);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
                if (process.env.AGENT_SCREENSHOTS) await page.screenshot({path: path.join(process.env.AGENT_SCREENSHOTS, `agent-execution-${width}.png`)});
                await page.getByRole('button', {name:'Configurar OpenAI', exact:true}).click();
                await dialog.getByLabel('Clave API nueva', {exact:true}).waitFor();
                const apiKey = dialog.getByLabel('Clave API nueva', {exact:true});
                const reveal = dialog.getByRole('button', {name:'Mostrar clave API', exact:true});
                assert.equal(await apiKey.inputValue(), '');
                assert.equal(await apiKey.getAttribute('type'), 'password');
                assert.equal(await reveal.getAttribute('aria-pressed'), 'false');
                await reveal.locator('svg[data-agent-icon="eye"]').waitFor({state:'visible'});
                const toggleRequests = [];
                const recordToggleRequest = request => toggleRequests.push(request.url());
                page.on('request', recordToggleRequest);
                await apiKey.fill('clave-de-prueba-no-valida');
                await reveal.click();
                const conceal = dialog.getByRole('button', {name:'Ocultar clave API', exact:true});
                assert.equal(await apiKey.getAttribute('type'), 'text');
                assert.equal(await apiKey.inputValue(), 'clave-de-prueba-no-valida');
                assert.equal(await conceal.getAttribute('aria-pressed'), 'true');
                assert.equal(await conceal.getAttribute('title'), 'Ocultar clave API');
                await conceal.locator('svg[data-agent-icon="eye-off"]').waitFor({state:'visible'});
                const inputBox = await apiKey.boundingBox();
                const toggleBox = await conceal.boundingBox();
                assert.ok(toggleBox.x >= inputBox.x && toggleBox.x + toggleBox.width <= inputBox.x + inputBox.width);
                assert.ok(toggleBox.y >= inputBox.y && toggleBox.y + toggleBox.height <= inputBox.y + inputBox.height);
                await conceal.focus();
                await page.keyboard.press('Space');
                assert.equal(await apiKey.getAttribute('type'), 'password');
                assert.equal(await apiKey.inputValue(), 'clave-de-prueba-no-valida');
                assert.equal(await reveal.getAttribute('aria-pressed'), 'false');
                page.off('request', recordToggleRequest);
                assert.deepEqual(toggleRequests, [], 'Revealing or hiding a key must not submit it');
                assert.equal(await dialog.evaluate(el => el.scrollWidth > el.clientWidth), false);
                if (process.env.AGENT_SCREENSHOTS) await page.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS, `agent-openai-${width}.png`)});
                await reveal.click();
                await dialog.getByRole('button', {name:'Cancelar', exact:true}).click();
                await dialog.waitFor({state:'hidden'});
                await page.getByRole('button', {name:'Configurar OpenAI', exact:true}).click();
                await apiKey.waitFor();
                assert.equal(await apiKey.inputValue(), '');
                assert.equal(await apiKey.getAttribute('type'), 'password');
                assert.equal(await reveal.getAttribute('aria-pressed'), 'false');
                await dialog.getByRole('button', {name:'Guardar conexión', exact:true}).click();
                await dialog.locator('#openai-key-error').filter({hasText:'Falta la clave API'}).waitFor();
                assert.equal(await apiKey.getAttribute('aria-invalid'), 'true');
                await apiKey.fill('clave-de-prueba-no-valida');
                await reveal.click();
                await dialog.getByRole('button', {name:'Guardar conexión', exact:true}).click();
                await dialog.locator('#openai-key-error').filter({hasText:'La clave API debe comenzar'}).waitFor();
                assert.equal(await apiKey.inputValue(), 'clave-de-prueba-no-valida');
                assert.equal(await apiKey.getAttribute('type'), 'password');
                assert.equal(await dialog.getByLabel('Modelo', {exact:true}).getAttribute('aria-invalid'), 'false');
                if (process.env.AGENT_SCREENSHOTS) await page.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS, `agent-openai-validation-${width}.png`)});
                const testKey = `sk-proj-${'aB9_-'.repeat(30)}`;
                await apiKey.fill(testKey);
                await dialog.getByLabel('Modelo', {exact:true}).fill('invalid model');
                await dialog.getByRole('button', {name:'Guardar conexión', exact:true}).click();
                await dialog.locator('#openai-model-error').filter({hasText:'Escribe un identificador'}).waitFor();
                assert.equal(await apiKey.inputValue(), testKey);
                assert.equal(await apiKey.getAttribute('aria-invalid'), 'false');
                assert.equal(await dialog.locator('#openai-key-error').textContent(), '');
                await dialog.getByLabel('Modelo', {exact:true}).fill('gpt-4.1-mini');
                await dialog.getByRole('button', {name:'Guardar conexión', exact:true}).click();
                await dialog.waitFor({state:'hidden'});
                await page.getByRole('status').filter({hasText:'Configuración de OpenAI guardada.'}).waitFor();
                await page.getByRole('button', {name:'Configurar OpenAI', exact:true}).click();
                await apiKey.waitFor();
                assert.equal(await apiKey.inputValue(), '');
                assert.equal(await apiKey.getAttribute('type'), 'password');
                await dialog.getByText('Clave API: configurada', {exact:true}).waitFor();
                await dialog.getByRole('button', {name:'Cancelar', exact:true}).click();
                assert.deepEqual(errors, []);
            } finally {
                await page.close();
                await server.close();
            }
        }
    } finally { await browser.close(); }
});

test('clinical agent chat sends, preserves private history, retries and adapts to mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const width of [1366, 390, 320]) {
            const server = fixture('clinical-chat');
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            try {
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.origin !== 'http://agents.test') return route.abort();
                    if (url.pathname === '/livewire/update') {
                        const payload = route.request().postDataJSON();
                        if (payload.components.some(component => component.calls.some(call => call.method === 'send'))) {
                            await new Promise(resolve => setTimeout(resolve, 250));
                        }
                        const result = await server.send(payload);
                        return route.fulfill({contentType:'application/json', body:JSON.stringify(result)});
                    }
                    const result = await server.send({type:'render'});
                    return route.fulfill({contentType:'text/html', body:`<!doctype html><html lang="es"><head>
                        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
                        <style>${styles}</style>${result.styles}</head><body><main class="admin-content"><h1>Superadministrador</h1>${result.html}</main>
                        <script>${icons}</script><script>window.livewireScriptConfig={uri:'http://agents.test/livewire/update',csrf:'test'};</script>
                        <script>${livewire}</script><script>window.Livewire.start();</script></body></html>`});
                });
                await page.goto('http://agents.test/panel');
                const chat = page.locator('.clinical-agent-chat');
                await page.getByRole('button', {name:'Soporte quimico y clinico de solicitudes', exact:true}).click();
                const draft = chat.getByLabel('Mensaje al agente', {exact:true});
                const send = chat.getByRole('button', {name:'Enviar mensaje', exact:true});
                await draft.fill('Hola, consulta de prueba');
                await send.click();
                const busy = chat.getByRole('button', {name:'Consultando...', exact:true});
                await busy.waitFor();
                assert.equal(await busy.isDisabled(), true);
                await chat.getByText('Respuesta de prueba. Se requiere revision profesional. [S1]', {exact:true}).waitFor();
                assert.equal(await draft.inputValue(), '');
                assert.equal(await chat.locator('.clinical-chat-turn').count(), 1);
                await chat.getByText('Fuentes citadas (1)', {exact:true}).click();
                await chat.getByText('Pendiente de revision; no es evidencia aprobada', {exact:false}).waitFor();
                if (process.env.AGENT_SCREENSHOTS) await chat.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS, `clinical-agent-chat-${width}.png`)});
                await draft.fill('Segunda pregunta');
                await send.click();
                await chat.getByText('Seguimiento de prueba recibido.', {exact:true}).waitFor();
                await page.reload();
                await page.getByRole('button', {name:'Soporte quimico y clinico de solicitudes', exact:true}).click();
                await chat.getByText('Segunda pregunta', {exact:true}).waitFor();
                assert.equal(await chat.locator('.clinical-chat-turn').count(), 2);
                const firstConversation = await chat.getByRole('combobox', {name:'Historial de conversaciones'}).inputValue();
                await chat.getByRole('button', {name:'Nueva conversacion', exact:true}).click();
                await chat.getByText('Sin mensajes en esta conversacion.', {exact:true}).waitFor();
                await draft.fill('Simular error');
                await send.click();
                await chat.getByRole('alert').filter({hasText:'limite de uso o cuota'}).waitFor();
                assert.equal(await draft.inputValue(), 'Simular error');
                assert.equal(await chat.locator('.clinical-chat-turn').count(), 0);
                await draft.fill('Reintento de prueba');
                await send.click();
                await chat.getByText('Respuesta de prueba. Se requiere revision profesional. [S1]', {exact:true}).waitFor();
                await chat.getByRole('combobox', {name:'Historial de conversaciones'}).selectOption(firstConversation);
                await chat.getByText('Segunda pregunta', {exact:true}).waitFor();
                assert.equal(await chat.locator('.clinical-chat-turn').count(), 2);
                const activation = page.getByRole('switch', {name:'Estado de Soporte quimico y clinico de solicitudes', exact:true});
                await activation.click();
                await chat.getByText('El agente esta desactivado.', {exact:true}).waitFor();
                assert.equal(await send.isDisabled(), true);
                await activation.click();
                await chat.getByText('El agente esta desactivado.', {exact:true}).waitFor({state:'hidden'});
                assert.equal(await send.isDisabled(), false);
                assert.equal(await chat.evaluate(el => el.scrollWidth > el.clientWidth), false);
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
                assert.deepEqual(errors, []);
            } finally {
                await page.close();
                await server.close();
            }
        }
    } finally { await browser.close(); }
});
