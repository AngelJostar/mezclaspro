const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css].map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser;
before(async () => { browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined }); });
after(async () => { await browser?.close(); });
async function fixture(width = 1366) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    page.setDefaultTimeout(10000);
    const program = { id: 'calidad', title: 'Calidad', description: 'Programa de calidad', owner: 'Calidad', status: 'Activo', is_custom: true, module_definitions: [], exam_definitions: [], task_definitions: [] };
    const errors = [], requests = []; let failure = false;
    page.on('pageerror', error => errors.push(error.message));
    await page.addInitScript(() => localStorage.setItem('mezclaspro.training.selected-program.v1', 'calidad'));
    await page.route('**/*', async route => {
        const req = route.request(), url = new URL(req.url());
        if (url.hostname !== 'training.test') return route.abort();
        if (req.method() !== 'GET') {
            let data;
            if ((req.headers()['content-type'] || '').includes('multipart')) {
                data = {};
                for (const match of req.postData().matchAll(/name="([^"]+)"(?:; filename="[^"]*")?\r\n(?:Content-Type:[^\r]*\r\n)?\r\n([\s\S]*?)\r\n--/g)) data[match[1]] = match[2];
            } else data = req.postDataJSON();
            requests.push({ method: req.method(), path: url.pathname, data });
            if (failure) return route.fulfill({ status: 422, json: { errors: { title: ['Revisa los datos e intenta nuevamente.'] } } });
            const segments = url.pathname.split('/').filter(Boolean), base = segments.indexOf('calidad');
            const [section, id, child, childId] = segments.slice(base + 1);
            let saved;
            if (section === 'modulos' && child === 'capitulos') {
                const module = program.module_definitions.find(m => m.id === id);
                if (req.method() === 'DELETE') {
                    module.chapter_definitions = module.chapter_definitions.filter(c => c.id !== childId); saved = { deleted: childId };
                } else {
                    saved = { ...data, id: childId || 'chapter-test', duration: Math.ceil(Number(data.duration_seconds) / 60) + ' min' };
                    module.chapter_definitions = [...module.chapter_definitions.filter(c => c.id !== saved.id), saved];
                }
                module.chapters = module.chapter_definitions.length;
            } else if (section === 'modulos') {
                saved = { ...(program.module_definitions.find(m => m.id === id) || { id: 'module-test', project: 'calidad', chapter_definitions: [], chapters: 0, duration: '0 min' }), ...data };
                program.module_definitions = [...program.module_definitions.filter(m => m.id !== saved.id), saved];
            } else if (section === 'tareas') {
                saved = { ...data, id: id || 'task-test', projectId: 'calidad', project: 'Calidad', stage: program.module_definitions.find(m => m.id === data.moduleId).stage, due: data.due_date || 'Sin fecha', tone: 'active', requires_file: data.requires_file === '1' };
                program.task_definitions = [...program.task_definitions.filter(t => t.id !== saved.id), saved];
            } else if (section === 'examenes') {
                saved = data;
                program.exam_definitions = [...program.exam_definitions.filter(e => e.id !== saved.id), saved];
            } else throw Error('Unexpected route: ' + req.url());
            return route.fulfill({ json: saved });
        }
        const body = execFileSync('php', ['tests/Browser/fixtures/training.php', 'programas', '[]', JSON.stringify([program])], { cwd: root, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="test"><style>' + css + '</style>' + body });
    });
    await page.goto('http://training.test/admin/capacitaciones/programas');
    return { page, program, errors, requests, fail: value => { failure = value; } };
}
async function saveContent(page) {
    await Promise.all([page.waitForEvent('load'), page.locator('[data-content-save]').click()]);
}
async function addModule(page) {
    await page.locator('[data-add-module]').click();
    await page.locator('[data-content-form] [name=title]').fill('Trazabilidad');
    await saveContent(page);
}

test('module and chapter buttons save independent records, preserve input on failure, and confirm chapter deletion', async () => {
    const { page, program, errors, requests, fail } = await fixture();
    try {
        await page.locator('[data-add-module]').click();
        const form = page.locator('[data-content-form]');
        await form.locator('[name=title]').fill('Trazabilidad');
        fail(true); await page.locator('[data-content-save]').click();
        await page.locator('[data-content-error]:visible').waitFor();
        assert.equal(await form.locator('[name=title]').inputValue(), 'Trazabilidad');
        fail(false); await saveContent(page);
        assert.equal(program.module_definitions.length, 1);
        assert.equal(await page.locator('[data-module-editor-title]').textContent(), 'Trazabilidad');
        await page.locator('[data-edit-module]').click();
        await form.locator('[name=title]').fill('Trazabilidad actualizada');
        await saveContent(page);
        assert.equal(program.module_definitions.length, 1);
        await page.locator('[data-add-chapter]').click();
        await form.locator('[name=title]').fill('Identificación');
        await form.locator('[name=objective]').fill('Reconocer los datos del producto.');
        await form.locator('[name=duration_minutes]').fill('8');
        await form.locator('[name=video]').fill('https://example.test/clase');
        await saveContent(page);
        assert.equal(program.module_definitions[0].chapter_definitions[0].duration_seconds, '480');
        assert.match(await page.locator('[data-chapter-list]').textContent(), /Identificación/);
        await page.locator('[data-edit-chapter]').click();
        await form.locator('[name=title]').fill('Identificación revisada');
        await saveContent(page);
        await page.reload();
        assert.match(await page.locator('[data-chapter-list]').textContent(), /Identificación revisada/);
        await page.locator('[data-delete-chapter]').click();
        await page.locator('[data-content-confirm]').getByRole('button', { name: 'No, conservar' }).click();
        assert.equal(program.module_definitions[0].chapters, 1);
        await page.locator('[data-delete-chapter]').click();
        await Promise.all([page.waitForEvent('load'), page.locator('[data-content-confirm]').getByRole('button', { name: 'Sí, eliminar' }).click()]);
        assert.equal(program.module_definitions[0].chapters, 0);
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 1);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('new task in an empty program guides module creation, resumes the task, saves and edits it', async () => {
    const { page, program, errors } = await fixture(390);
    try {
        await page.locator('[data-program-tab=tasks]').click();
        await page.locator('[data-new-task]').click();
        const modal = page.locator('[data-content-dialog]'), form = page.locator('[data-content-form]');
        assert.equal(await page.locator('[data-content-save]').isDisabled(), true);
        await modal.getByRole('button', { name: 'Agregar módulo' }).click();
        await form.locator('[name=title]').fill('Trazabilidad'); await saveContent(page);
        await modal.waitFor({ state: 'visible' });
        assert.equal(await page.locator('#training-content-title').textContent(), 'Nueva tarea');
        await form.locator('[name=title]').fill('Entrega de evidencia');
        await form.locator('[name=instructions]').fill('Adjunta el checklist completo.');
        await form.locator('[name=status]').selectOption('Activa');
        await form.locator('[name=due_date]').fill('2026-11-01');
        const box = await modal.boundingBox(); assert.ok(box.x >= 0 && box.x + box.width <= 391);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-task-mobile.png') });
        await saveContent(page);
        assert.equal(program.task_definitions[0].moduleId, 'module-test');
        await page.locator('[data-edit-task=task-test]').click();
        assert.equal(await form.locator('[name=instructions]').inputValue(), 'Adjunta el checklist completo.');
        await form.locator('[name=title]').fill('Evidencia revisada');
        await saveContent(page);
        await page.reload();
        assert.match(await page.locator('[data-task-id=task-test]').textContent(), /Evidencia revisada/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('exams save to the server and survive reload; a program without modules starts module creation', async () => {
    const { page, program, errors, requests } = await fixture();
    try {
        await page.locator('[data-program-tab=exams]').click();
        await page.locator('[data-new-exam]').click();
        await page.locator('[data-content-form] [name=title]').fill('Trazabilidad');
        await saveContent(page);
        const form = page.locator('[data-new-exam-form]');
        await form.waitFor({ state: 'visible' });
        await form.locator('[name=name]').fill('Evaluación de calidad');
        await form.getByRole('button', { name: 'Guardar borrador' }).click();
        await form.waitFor({ state: 'hidden' });
        assert.equal(program.exam_definitions[0].status, 'Borrador');
        assert.equal(requests.at(-1).method, 'PUT');
        await page.reload();
        const examId = program.exam_definitions[0].id;
        assert.equal(await page.locator('[data-exam-id="' + examId + '"]').count(), 1);
        await page.locator('[data-edit-exam="' + examId + '"]').click();
        const edit = page.locator('[data-exam-editor-form]');
        await edit.locator('[name=status]').selectOption('Publicado');
        await edit.locator('[data-exam-question-text]').fill('¿Identificas el producto?');
        await edit.locator('[data-exam-option-text]').nth(0).fill('Sí');
        await edit.locator('[data-exam-option-text]').nth(1).fill('No');
        await edit.getByRole('button', { name: 'Guardar cambios' }).click();
        await edit.waitFor({ state: 'hidden' });
        assert.equal(program.exam_definitions[0].status, 'Publicado');
        await page.reload();
        assert.match(await page.locator('[data-exam-id="' + examId + '"]').textContent(), /Publicado/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
