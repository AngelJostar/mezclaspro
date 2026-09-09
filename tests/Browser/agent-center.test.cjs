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
        'tests/Browser/fixtures/agent-center.php', String(count)], { cwd: root, stdio: ['pipe', 'pipe', 'pipe'] });
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
                assert.deepEqual(errors, []);
            } finally {
                await page.close();
                await server.close();
            }
        }
    } finally { await browser.close(); }
});
