const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync, mkdirSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });

async function fixture(role = 'Admin', assignments = {}) {
    const page = await browser.newPage({ viewport: { width: 1500, height: 950 } });
    const completions = [], writes = [], errors = [];
    let failure = false;
    page.setDefaultTimeout(10000);
    page.on('pageerror', error => errors.push(error.message));
    await page.addInitScript(assignments => {
        if (!localStorage.getItem('test-initialized')) {
            localStorage.setItem('mezclaspro.training.assignments.v1', JSON.stringify(assignments));
            localStorage.setItem('test-initialized', 'yes');
        }
    }, assignments);
    await page.route('**/*', async route => {
        const req = route.request(), url = new URL(req.url());
        if (url.hostname !== 'training.test') return route.abort();
        if (req.method() === 'POST') {
            assert.equal(url.pathname, '/admin/capacitaciones/alumnos/concluir');
            const data = req.postDataJSON(); writes.push(data);
            if (failure) return route.fulfill({ status: 500, json: { message: 'No se pudo guardar. Intenta nuevamente.' } });
            const titles = { induccion: 'Induccion y seguridad operativa', proveedores: 'Validacion de proveedores', citotoxicos: 'Manejo seguro de citotoxicos' };
            const { browser_assignments, ...completion } = data;
            completions.push({ ...completion, program_name: titles[data.program_id], browser_assignments });
            return route.fulfill({ json: { message: 'Concluida', redirect: 'http://training.test/admin/capacitaciones/alumnos?training_view=completed' } });
        }
        const html = execFileSync('php', ['tests/Browser/fixtures/training.php', 'alumnos' + url.search, '[]', '[]', 'null', JSON.stringify({ role, completions })],
            { cwd: root, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="test"><style>' + css + '</style>' + html });
    });
    await page.goto('http://training.test/admin/capacitaciones/alumnos');
    return { page, errors, writes, completions, fail: value => { failure = value; } };
}

test('Concluir moves the selected training to Concluidos and survives reload without changing another student', async () => {
    const f = await fixture(); const { page } = f;
    try {
        assert.equal(await page.locator('[data-conclude-training]').count(), 4);
        assert.equal(await page.locator('[data-add-training]').count(), 0);
        await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Concluir Induccion y seguridad operativa para Ana Torres', exact: true }).click()]);
        assert.deepEqual(f.writes, [{ student_key: 'preview-ana-torres', program_id: 'induccion', start_date: '2026-08-01', browser_assignments: [] }]);
        assert.match(page.url(), /training_view=completed/);
        let ana = page.locator('[data-student-key="preview-ana-torres"]');
        assert.match(await ana.innerText(), /Concluido anticipadamente/);
        assert.match(await ana.locator('[data-training-start-dates]').innerText(), /01\/08\/2026/);
        assert.equal(await ana.locator('[data-current-program-id]').count(), 0);
        assert.equal(await page.locator('[data-conclude-training]').count(), 0);
        await page.reload();
        assert.match(await ana.innerText(), /Concluido anticipadamente/);
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-concluida.png'), fullPage: true });
        await page.getByRole('link', { name: 'En curso', exact: true }).click();
        assert.equal(await page.locator('[data-student-key="preview-ana-torres"]').count(), 0);
        assert.match(await page.locator('[data-student-key="preview-carlos-hernandez"]').innerText(), /40%/);
        assert.equal(await page.locator('[data-conclude-training]').count(), 3);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('closing one of multiple courses preserves the others and server errors keep the row retryable', async () => {
    const f = await fixture('Super Admin', { 'Ana Torres': [{ programId: 'proveedores', programName: 'Validacion de proveedores', startDate: '2026-10-01', modules: ['Modulo 1'], progress: 10 }] });
    const { page } = f;
    try {
        const button = page.getByRole('button', { name: 'Concluir Validacion de proveedores para Ana Torres', exact: true });
        f.fail(true); await button.click();
        await page.locator('[data-student-key="preview-ana-torres"] [data-conclude-error]:visible').waitFor();
        assert.equal(await button.isEnabled(), true);
        assert.equal(await page.locator('[data-student-key="preview-ana-torres"] [data-current-program-id]').count(), 2);
        f.fail(false);
        await Promise.all([page.waitForEvent('load'), button.click()]);
        assert.equal(f.completions.length, 1);
        assert.equal(f.completions[0].start_date, '2026-10-01');
        await page.getByRole('link', { name: 'En curso', exact: true }).click();
        const ana = page.locator('[data-student-key="preview-ana-torres"]');
        assert.equal(await ana.locator('[data-current-program-id]').count(), 1);
        assert.equal(await ana.locator('[data-current-program-id]').getAttribute('data-current-program-id'), 'induccion');
        assert.match(await ana.innerText(), /75%/);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('employee view has neither the Concluir button nor the action column', async () => {
    const f = await fixture('Capacitacion');
    try {
        assert.equal(await f.page.locator('[data-conclude-training]').count(), 0);
        assert.equal(await f.page.getByRole('columnheader', { name: 'Concluir', exact: true }).count(), 0);
        assert.equal(await f.page.locator('[data-student-row]').count(), 4);
        assert.deepEqual(f.errors, []);
    } finally { await f.page.close(); }
});

test('closing the original course keeps a browser-assigned course visible even after clearing its local cache', async () => {
    const f = await fixture('Admin', { 'Ana Torres': [{ programId: 'proveedores', programName: 'Validacion de proveedores', startDate: '2026-10-01', modules: ['Modulo 1'], progress: 10 }] });
    const { page } = f;
    try {
        await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Concluir Induccion y seguridad operativa para Ana Torres', exact: true }).click()]);
        await page.evaluate(() => localStorage.removeItem('mezclaspro.training.assignments.v1'));
        await page.getByRole('link', { name: 'En curso', exact: true }).click();
        const ana = page.locator('[data-student-key="preview-ana-torres"]');
        assert.equal(await ana.locator('[data-current-program-id]').getAttribute('data-current-program-id'), 'proveedores');
        assert.match(await ana.innerText(), /10%/);
        await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Concluir Validacion de proveedores para Ana Torres', exact: true }).click()]);
        assert.equal(await ana.locator('[data-current-program-id]').count(), 0);
        assert.equal(await ana.locator('.training-early-completion').count(), 2);
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});
