const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');
const { buildSync } = require('esbuild');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
const script = buildSync({ stdin: { contents: "import './resources/js/mixture-messages.js';", resolveDir: root },
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' } }).outputFiles[0].text;
const fixture = role => execFileSync('php', ['tests/Browser/fixtures/mixture-messages.php', role], { cwd: root, encoding: 'utf8' });

test('mixture chat preserves history, unread badges, drafts and safe retry on desktop and mobile', async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const role of ['Super Admin', 'Institucion']) {
            const html = fixture(role);
            for (const width of [1440, 390]) {
                const page = await browser.newPage({ viewport: { width, height: 900 } });
                page.setDefaultTimeout(10000);
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const side = role === 'Super Admin' ? 'central' : 'hospital';
                const key = side === 'central' ? 'oncologicos:38' : 'nutricionales:17';
                const messages = side === 'central' ? [{ id: 1, side: 'hospital', author: 'Ana Hospital',
                    body: 'Buen dia. ¿Pueden confirmar el horario de entrega?', sent_at: '2026-09-14T09:10:00-06:00' }] : [];
                let readThrough = 0;
                let failNextSend = true;
                const sends = [];
                const tokens = new Map();
                const json = (route, data, status = 200) => route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(data) });
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.pathname === '/fixture') return route.fulfill({ contentType: 'text/html', body:
                        `<!doctype html><html><head><meta charset="utf-8"><meta name="csrf-token" content="test-token"><style>${styles}</style></head><body><main class="admin-page"><div class="admin-content">${html}</div></main><script>${script}</script></body></html>` });
                    if (url.pathname.endsWith('/mensajes/estado')) {
                        const summaries = Object.fromEntries(url.searchParams.getAll('targets[]').map(requested => [requested,
                            requested === key ? { total: messages.length, unread: messages.filter(m => m.side !== side && m.id > readThrough).length, latest_id: messages.at(-1)?.id || 0 }
                                : { total: 0, unread: 0, latest_id: 0 }]));
                        return json(route, { summaries });
                    }
                    if (url.pathname.endsWith('/leidos')) {
                        readThrough = Math.max(readThrough, route.request().postDataJSON().through_id);
                        return json(route, { ok: true });
                    }
                    if (route.request().method() === 'POST') {
                        assert.equal(route.request().headers()['x-csrf-token'], 'test-token');
                        const body = route.request().postDataJSON();
                        sends.push(body);
                        if (!tokens.has(body.client_token)) {
                            const message = { id: messages.length + 1, side, author: side === 'central' ? 'Laura Central' : 'Ana Hospital', body: body.body, sent_at: '2026-09-14T09:15:00-06:00' };
                            messages.push(message); tokens.set(body.client_token, message);
                        }
                        if (failNextSend) { failNextSend = false; return json(route, { message: 'Conexion interrumpida' }, 500); }
                        return json(route, { message: tokens.get(body.client_token) });
                    }
                    return json(route, { target: { id: Number(key.split(':')[1]), hospital: 'Hospital de prueba', patient: 'Paciente de prueba' },
                        side, can_send: side === 'hospital' || messages.length > 0,
                        messages: messages.filter(message => message.id > Number(url.searchParams.get('after_id') || 0)), has_older: false });
                });
                await page.goto('http://localhost/fixture');
                const cell = page.locator(`[data-mixture-message-key="${key}"]`);
                const history = cell.locator('.mixture-message-history');
                await cell.locator('svg').first().waitFor();
                assert.equal(await page.locator('[data-mixture-message-key="antibioticos:39"] .mixture-message-history').isDisabled(), true);
                if (side === 'central') {
                    await page.waitForFunction(key => !document.querySelector(`[data-mixture-message-key="${key}"] button`).disabled, key);
                    assert.equal(await cell.locator('.mixture-message-unread').innerText(), '1');
                    assert.equal(await history.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(22, 163, 74)');
                    assert.equal(await history.locator('svg').evaluate(el => getComputedStyle(el).color), 'rgb(255, 255, 255)');
                    await history.click();
                } else {
                    assert.equal(await history.isDisabled(), true);
                    await cell.getByRole('button', { name: 'Enviar mensaje', exact: true }).click();
                }
                const dialog = page.getByRole('dialog');
                const input = dialog.getByRole('textbox', { name: 'Mensaje', exact: true });
                await page.waitForFunction(() => !document.querySelector('#mixture-chat-body').disabled);
                const send = dialog.getByRole('button', { name: 'Enviar mensaje', exact: true });
                assert.equal(await send.isDisabled(), true);
                if (side === 'central') await cell.locator('.mixture-message-unread').waitFor({ state: 'hidden' });
                await input.fill('Borrador de la mezcla');
                await page.keyboard.press('Escape');
                await dialog.waitFor({ state: 'hidden' });
                await (side === 'central' ? history : cell.locator('.mixture-message-compose')).click();
                await page.waitForFunction(() => !document.querySelector('#mixture-chat-body').disabled);
                assert.equal(await input.inputValue(), 'Borrador de la mezcla');
                const body = 'Estamos verificando la programacion. <img src=x onerror="window.chatXss=true">\n' + 'Detalle'.repeat(35);
                await input.fill(body);
                await send.click();
                await dialog.locator('[data-chat-error]').waitFor({ state: 'visible' });
                assert.equal(await input.inputValue(), body);
                await send.click();
                await dialog.getByText('Mensaje enviado', { exact: true }).waitFor();
                await dialog.locator('[data-chat-messages] p').filter({ hasText: 'Estamos verificando' }).waitFor();
                assert.equal(sends.length, 2);
                assert.equal(sends[0].client_token, sends[1].client_token);
                assert.equal(messages.length, side === 'central' ? 2 : 1);
                assert.equal(await input.inputValue(), '');
                assert.equal(await dialog.locator('img').count(), 0);
                const bounds = await dialog.boundingBox();
                assert.ok(bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= width + 1 && bounds.y + bounds.height <= 901);
                assert.equal(await dialog.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
                assert.equal(await dialog.locator('[data-chat-messages]').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
                assert.equal(await dialog.getByRole('button', { name: /borrar|eliminar|editar/i }).count(), 0);
                if (process.env.MESSAGE_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.MESSAGE_SCREENSHOTS, `mixture-chat-${side}-${width}.png`) });
                await dialog.getByRole('button', { name: 'Cerrar mensajes' }).click();
                await history.waitFor({ state: 'visible' });
                assert.equal(await history.isEnabled(), true);
                await history.click();
                await dialog.locator('[data-chat-messages] p').filter({ hasText: 'Estamos verificando' }).waitFor();
                await dialog.getByRole('button', { name: 'Cerrar mensajes' }).click();
                messages.push({ id: messages.length + 1, side: side === 'central' ? 'hospital' : 'central',
                    author: 'Respuesta nueva', body: 'Gracias, quedamos pendientes.', sent_at: '2026-09-14T09:20:00-06:00' });
                await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
                await cell.locator('.mixture-message-unread').waitFor({ state: 'visible' });
                assert.equal(await cell.locator('.mixture-message-unread').innerText(), '1');
                await history.click();
                await dialog.getByText('Gracias, quedamos pendientes.', { exact: true }).waitFor();
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
    } finally { await browser.close(); }
});
