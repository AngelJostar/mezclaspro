const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { mkdirSync, readFileSync, readdirSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const cssFile = readdirSync(path.join(root, 'public/build/assets')).find(name => /^app-.*\.css$/.test(name));
const css = readFileSync(path.join(root, 'public/build/assets', cssFile), 'utf8');
let browser;
before(async () => { browser = await chromium.launch({ channel: process.env.PLAYWRIGHT_CHANNEL || 'msedge', headless: true }); });
after(async () => { await browser.close(); });

async function fixture() {
    let assignments = [], userId = 1, failure = false;
    const writes = [], errors = [];
    const page = await browser.newPage({ viewport: { width: 1360, height: 900 } });
    page.on('pageerror', error => errors.push(error.message));
    const render = type => execFileSync('php', ['tests/Browser/fixtures/training.php', type, '[]', '[]', 'null', JSON.stringify({
        assignments, as_user: userId, personnel: [{ id: 2, name: 'Lucía', lastname: 'Capacitación' }, { id: 3, name: 'Otro', lastname: 'Alumno' }],
    })], { cwd: root, encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
    await page.route('**/*', async route => {
        const req = route.request(), url = new URL(req.url());
        if (url.hostname !== 'training.test') return route.abort();
        if (req.method() === 'POST') {
            if (url.pathname.endsWith('/asignar')) {
                const payload = req.postDataJSON(); writes.push(payload);
                if (failure) return route.fulfill({ status: 422, json: { message: 'No se pudo guardar. Intenta nuevamente.' } });
                assignments = payload.programs.map(program => ({ user_id: payload.user_id, program_id: program.program_id,
                    module_ids: program.module_ids, start_date: payload.start_date, status: 'assigned' }));
                return route.fulfill({ json: { redirect: 'http://training.test/admin/capacitaciones/alumnos?training_view=in_progress' } });
            }
            assert.match(url.pathname, /asignaciones\/\d+\/iniciar$/);
            const id = Number(url.pathname.match(/asignaciones\/(\d+)/)[1]);
            assignments[id - 1].status = 'in_progress'; assignments[id - 1].started_at = '2026-10-26 12:00:00';
            const data = JSON.parse(render('assignments'));
            return route.fulfill({ json: { assignment: data.assignments.find(item => item.id === id) } });
        }
        if (url.pathname === '/admin/capacitaciones/asignaciones') return route.fulfill({ contentType: 'application/json', body: render('assignments') });
        const type = url.pathname.endsWith('/alumnos') ? 'alumnos' + url.search : 'programas';
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="test"><style>' + css + '</style>' + render(type) });
    });
    await page.goto('http://training.test/admin/capacitaciones/alumnos');
    return { page, writes, errors, login: id => { userId = id; }, fail: value => { failure = value; }, seed: values => { assignments = values; } };
}

test('saved assignment appears in administrator table and selected learner can start real assigned content after login/reload', async () => {
    const f = await fixture(), { page } = f;
    try {
        await page.locator('[data-open-new-training]').click();
        await page.locator('[data-assignment-student-select]').selectOption('2');
        await page.locator('[data-assignment-start-date]').fill('2026-10-26');
        await page.locator('[data-program-id="induccion"] [data-module-id="module-2"]').check();
        f.fail(true);
        await page.locator('[data-confirm-training-assignment]').click();
        await page.locator('[data-assignment-error]:visible').waitFor();
        assert.equal(await page.locator('[data-training-assignment-modal]').evaluate(node => node.open), true);
        assert.equal(await page.locator('[data-program-id="induccion"] [data-module-id="module-2"]').isChecked(), true);
        f.fail(false);
        await Promise.all([page.waitForEvent('load'), page.locator('[data-confirm-training-assignment]').click()]);
        assert.deepEqual(f.writes[1], { user_id: 2, start_date: '2026-10-26', programs: [{ program_id: 'induccion', module_ids: ['module-2'] }] });
        const row = page.locator('[data-student-key="user-2"]');
        assert.match(await row.innerText(), /Lucía Capacitación/);
        assert.match(await row.innerText(), /Por iniciar · 1 módulos asignados/);
        await page.reload();
        assert.equal(await row.count(), 1);
        f.login(2);
        await page.goto('http://training.test/admin/capacitaciones/programas');
        const start = page.getByRole('button', { name: 'Iniciar capacitación', exact: true });
        await start.waitFor();
        await start.click();
        const reader = page.locator('[data-assigned-reader]');
        await reader.waitFor({ state: 'visible' });
        assert.equal(await page.locator('[data-assigned-module] option').count(), 1);
        assert.match(await page.locator('[data-assigned-module]').innerText(), /Seguridad operativa/);
        assert.ok(await page.locator('[data-assigned-chapters] article').count() > 0);
        await page.locator('[data-close-assigned-reader]').click();
        await page.reload();
        await page.getByRole('button', { name: 'Continuar capacitación', exact: true }).waitFor();
        assert.equal(await page.locator('.training-learner-hero').isVisible(), false);
        assert.equal(await page.locator('.training-obligation-flow').isVisible(), false);
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-asignada-inicio.png'), fullPage: true });
        f.login(3);
        await page.reload();
        await page.getByText('No tienes capacitaciones pendientes asignadas.', { exact: true }).waitFor();
        assert.equal(await page.locator('[data-start-assignment]').count(), 0);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('multiple assignments show only selected modules and an administrative closure removes the active course on refresh', async () => {
    const f = await fixture(), { page } = f;
    try {
        f.seed([{ user_id: 2, program_id: 'induccion', module_ids: ['module-1'], start_date: '2026-10-26', status: 'assigned' },
            { user_id: 2, program_id: 'proveedores', module_ids: ['module-3'], start_date: '2026-10-26', status: 'assigned' }]);
        f.login(2); await page.goto('http://training.test/admin/capacitaciones/programas');
        await page.locator('[data-start-assignment="2"]').waitFor();
        assert.equal(await page.locator('[data-start-assignment]').count(), 2);
        await page.locator('[data-start-assignment="2"]').click();
        await page.locator('[data-assigned-reader]').waitFor({ state: 'visible' });
        assert.match(await page.locator('[data-assigned-module]').innerText(), /Validacion final/);
        f.seed([{ user_id: 2, program_id: 'induccion', module_ids: ['module-1'], start_date: '2026-10-26', status: 'assigned' },
            { user_id: 2, program_id: 'proveedores', module_ids: ['module-3'], start_date: '2026-10-26', status: 'completed' }]);
        await page.locator('[data-close-assigned-reader]').click();
        await page.locator('[data-refresh-assignments]').click();
        await page.getByText('1 capacitación pendiente o en curso.', { exact: true }).waitFor();
        assert.equal(await page.locator('[data-start-assignment]').count(), 1);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});
