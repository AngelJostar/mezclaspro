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
                if (childId === 'ordenar' && req.method() === 'PATCH') {
                    module.chapter_definitions = data.chapter_ids.map(id => module.chapter_definitions.find(chapter => chapter.id === id));
                    saved = { chapter_ids: data.chapter_ids };
                } else if (req.method() === 'DELETE') {
                    module.chapter_definitions = module.chapter_definitions.filter(c => c.id !== childId); saved = { deleted: childId };
                } else {
                    saved = { ...data, id: childId || 'chapter-' + (module.chapter_definitions.length + 1), duration: Math.ceil(Number(data.duration_seconds) / 60) + ' min' };
                    module.chapter_definitions = [...module.chapter_definitions.filter(c => c.id !== saved.id), saved];
                }
                module.chapters = module.chapter_definitions.length;
            } else if (section === 'modulos' && req.method() === 'DELETE') {
                program.module_definitions = program.module_definitions.filter(m => m.id !== id);
                program.task_definitions = program.task_definitions.filter(t => t.moduleId !== id);
                program.exam_definitions = program.exam_definitions.filter(e => e.moduleId !== id);
                saved = { deleted: id };
            } else if (section === 'modulos') {
                saved = { ...(program.module_definitions.find(m => m.id === id) || { id: 'module-test', project: 'calidad', created_by: 1, chapter_definitions: [], chapters: 0, duration: '0 min' }), ...data };
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
async function addChapter(page, title = 'Identificación') {
    await page.locator('[data-add-chapter]').click();
    await page.locator('[data-content-form] [name=title]').fill(title);
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
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-module-edit-location.png'), fullPage: true });
        await page.locator('[data-edit-module]:visible').click();
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
        const confirmation = page.locator('[data-content-confirm]');
        const no = confirmation.getByRole('button', { name: 'No', exact: true });
        const yes = confirmation.getByRole('button', { name: 'Sí', exact: true });
        const firstMessage = '¿Estás seguro que quieres eliminar el capítulo “Identificación revisada”?';
        const finalMessage = 'Al eliminar el capítulo no podrás recuperar la información. ¿Estás seguro que quieres eliminarlo?';
        await page.locator('[data-delete-chapter]').click();
        assert.equal(await confirmation.locator('[data-content-confirm-message]').innerText(), firstMessage);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-delete-chapter-first.png') });
        await no.click();
        assert.equal(program.module_definitions[0].chapters, 1);
        await page.locator('[data-delete-chapter]').click();
        await yes.click();
        assert.equal(await confirmation.locator('[data-content-confirm-message]').innerText(), finalMessage);
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 0);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-delete-chapter-final.png') });
        await no.click();
        assert.equal(program.module_definitions[0].chapters, 1);
        await page.locator('[data-delete-chapter]').click();
        assert.equal(await confirmation.locator('[data-content-confirm-message]').innerText(), firstMessage);
        await yes.click();
        await page.keyboard.press('Escape');
        assert.equal(await confirmation.evaluate(node => node.open), false);
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 0);
        await page.locator('[data-delete-chapter]').click();
        assert.equal(await confirmation.locator('[data-content-confirm-message]').innerText(), firstMessage);
        await yes.click();
        fail(true); await yes.click();
        await confirmation.locator('[data-content-delete-error]:visible').waitFor();
        assert.equal(program.module_definitions[0].chapters, 1);
        assert.equal(await confirmation.locator('[data-content-confirm-message]').innerText(), finalMessage);
        fail(false);
        await Promise.all([page.waitForEvent('load'), yes.click()]);
        assert.equal(program.module_definitions[0].chapters, 0);
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 2);
        await page.reload();
        assert.equal(await page.locator('[data-training-chapter]').count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('module deletion asks once, cancels safely, retries failures and persists without deleting other modules', async () => {
    const { page, program, errors, requests, fail } = await fixture(530);
    try {
        await addModule(page); await addChapter(page);
        program.module_definitions.push({ ...program.module_definitions[0], id: 'module-keep', title: 'Conservar', chapter_definitions: [], chapters: 0 });
        await page.reload();
        await page.locator('[data-module-id="module-keep"] .training-module-select').click();
        const remove = page.locator('[data-module-id="module-test"] [data-delete-module]');
        const confirm = page.locator('[data-content-confirm]');
        const yes = confirm.getByRole('button', { name: 'Sí', exact: true });
        const no = confirm.getByRole('button', { name: 'No', exact: true });
        if (process.env.TRAINING_SCREENSHOTS) await page.locator('.training-module-list').screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-delete-module-buttons.png') });
        await remove.click();
        assert.equal(await confirm.locator('[data-content-confirm-message]').innerText(), '¿Estás seguro que quieres eliminar el módulo “Trazabilidad”?');
        assert.equal(await confirm.locator('[data-content-delete-details]').isVisible(), true);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-delete-module-confirm.png') });
        await no.click();
        assert.equal(program.module_definitions.length, 2);
        assert.equal(await page.locator('[data-module-editor-title]').textContent(), 'Conservar');
        await remove.click(); await page.keyboard.press('Escape');
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 0);
        await remove.click(); fail(true); await yes.click();
        await confirm.locator('[data-content-delete-error]:visible').waitFor();
        assert.equal(program.module_definitions.length, 2);
        fail(false);
        await Promise.all([page.waitForEvent('load'), yes.click()]);
        assert.deepEqual(program.module_definitions.map(m => m.id), ['module-keep']);
        assert.equal(await page.locator('[data-module-editor-title]').textContent(), 'Conservar');
        assert.equal(requests.filter(r => r.method === 'DELETE').length, 2);
        await page.reload();
        assert.equal(await page.locator('[data-module-id="module-test"]').count(), 0);
        await page.locator('[data-module-id="module-keep"] [data-delete-module]').click();
        await Promise.all([page.waitForEvent('load'), yes.click()]);
        assert.equal(await page.locator('[data-module-item]:visible').count(), 0);
        assert.match(await page.locator('[data-module-empty]').textContent(), /aún no tiene módulos/);
        assert.equal(await page.locator('[data-add-module]').isEnabled(), true);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('chapter cards reorder by dragging and keyboard, persist, and roll back failed saves', async () => {
    const { page, program, errors, requests, fail } = await fixture();
    try {
        await addModule(page); await addChapter(page, 'Primero'); await addChapter(page, 'Segundo'); await addChapter(page, 'Tercero');
        program.task_definitions = [{ id: 't1', projectId: 'calidad', moduleId: 'module-test', chapterId: 'chapter-1', title: 'Tarea del primero', status: 'Activa', stage: 'Módulo 1', due: 'Sin fecha', tone: 'active' }];
        await page.reload();
        const order = () => page.locator('[data-training-chapter]').evaluateAll(rows => rows.map(row => row.dataset.trainingChapter));
        const saved = () => page.locator('[data-chapter-order-status]').filter({ hasText: 'Orden de capítulos guardado.' }).waitFor();
        assert.equal(await page.locator('[data-chapter-drag]').count(), 3);
        await page.locator('[data-chapter-drag="chapter-3"]').dragTo(page.locator('[data-training-chapter="chapter-1"]'), { targetPosition: { x: 30, y: 8 } });
        await saved();
        assert.deepEqual(await order(), ['chapter-3', 'chapter-1', 'chapter-2']);
        assert.match(await page.locator('[data-training-chapter="chapter-3"] strong').first().textContent(), /Capítulo 1 · Tercero/);
        assert.equal(await page.locator('[data-training-chapter="chapter-1"] [data-chapter-activity-id=t1]').count(), 1);
        assert.deepEqual(requests.find(r => r.path.endsWith('/ordenar')).data.expected_order, ['chapter-1', 'chapter-2', 'chapter-3']);
        await page.reload();
        assert.deepEqual(await order(), ['chapter-3', 'chapter-1', 'chapter-2']);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-chapter-order.png'), fullPage: true });
        fail(true);
        const lastRow = page.locator('[data-training-chapter="chapter-2"]');
        const box = await lastRow.boundingBox();
        await page.locator('[data-chapter-drag="chapter-3"]').dragTo(lastRow, { targetPosition: { x: 30, y: box.height - 8 } });
        await page.locator('[data-chapter-order-status]').filter({ hasText: 'No se guardó el nuevo orden.' }).waitFor();
        assert.deepEqual(await order(), ['chapter-3', 'chapter-1', 'chapter-2']);
        assert.deepEqual(program.module_definitions[0].chapter_definitions.map(c => c.id), ['chapter-3', 'chapter-1', 'chapter-2']);
        fail(false);
        await page.locator('[data-chapter-drag="chapter-3"]').focus(); await page.keyboard.press('ArrowDown'); await saved();
        assert.deepEqual(await order(), ['chapter-1', 'chapter-3', 'chapter-2']);
        await page.reload();
        assert.deepEqual(await order(), ['chapter-1', 'chapter-3', 'chapter-2']);
        const count = requests.length;
        await page.locator('[data-chapter-drag="chapter-1"]').dragTo(page.locator('[data-module-editor-title]'));
        assert.deepEqual(await order(), ['chapter-1', 'chapter-3', 'chapter-2']);
        assert.equal(requests.length, count, 'dropping outside the chapter list does not save');
        await page.setViewportSize({ width: 390, height: 900 });
        await page.getByRole('button', { name: 'Subir capítulo Segundo', exact: true }).click(); await saved();
        assert.deepEqual(await order(), ['chapter-1', 'chapter-2', 'chapter-3']);
        program.module_definitions[0].created_by = 999;
        await page.reload();
        assert.equal(await page.locator('[data-chapter-drag]').count(), 0);
        assert.equal(await page.locator('[data-training-chapter][draggable=true]').count(), 0);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('chapter tasks are created and edited on the right of their chapter and persist on mobile', async () => {
    const { page, program, errors } = await fixture(390);
    try {
        assert.equal(await page.locator('[data-program-tab=tasks]').count(), 0);
        await addModule(page); await addChapter(page); await addChapter(page, 'Segundo capítulo');
        await page.locator('[data-create-chapter-task="chapter-2"]').click();
        const modal = page.locator('[data-content-dialog]'), form = page.locator('[data-content-form]');
        assert.equal(await page.locator('#training-content-title').textContent(), 'Nueva tarea');
        assert.equal(await form.locator('[name=chapterId]').inputValue(), 'chapter-2');
        await form.locator('[name=title]').fill('Entrega de evidencia');
        await form.locator('[name=instructions]').fill('Adjunta el checklist completo.');
        await form.locator('[name=status]').selectOption('Activa');
        await form.locator('[name=due_date]').fill('2026-11-01');
        const box = await modal.boundingBox(); assert.ok(box.x >= 0 && box.x + box.width <= 391);
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-task-mobile.png') });
        await saveContent(page);
        assert.equal(program.task_definitions[0].moduleId, 'module-test');
        assert.equal(program.task_definitions[0].chapterId, 'chapter-2');
        assert.equal(await page.locator('[data-training-chapter="chapter-1"] [data-chapter-activity-id]').count(), 0);
        await page.locator('[data-edit-chapter-task=task-test]').click();
        assert.equal(await form.locator('[name=instructions]').inputValue(), 'Adjunta el checklist completo.');
        await form.locator('[name=title]').fill('Evidencia revisada');
        await saveContent(page);
        await page.reload();
        assert.match(await page.locator('[data-training-chapter="chapter-2"]').textContent(), /Evidencia revisada/);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('chapter exams preserve their association through creation, publication, editing and reload', async () => {
    const { page, program, errors, requests, fail } = await fixture();
    try {
        assert.equal(await page.locator('[data-program-tab=exams]').count(), 0);
        await addModule(page); await addChapter(page); await addChapter(page, 'Segundo capítulo');
        await page.locator('[data-create-chapter-exam="chapter-1"]').click();
        const form = page.locator('[data-new-exam-form]');
        await form.waitFor({ state: 'visible' });
        await form.locator('[name=name]').fill('Evaluación de calidad');
        fail(true); await form.getByRole('button', { name: 'Guardar borrador' }).click();
        await form.locator('[data-exam-persistence-error]:visible').waitFor();
        assert.equal(await form.locator('[name=chapterId]').inputValue(), 'chapter-1');
        fail(false);
        await form.getByRole('button', { name: 'Guardar borrador' }).click();
        await form.waitFor({ state: 'hidden' });
        assert.equal(program.exam_definitions[0].status, 'Borrador');
        assert.equal(program.exam_definitions[0].chapterId, 'chapter-1');
        assert.equal(program.exam_definitions[0].moduleId, 'module-test');
        assert.equal(requests.at(-1).method, 'PUT');
        await page.reload();
        const examId = program.exam_definitions[0].id;
        assert.equal(await page.locator('[data-exam-id="' + examId + '"]').count(), 1);
        await page.locator('[data-edit-chapter-exam="' + examId + '"]').click();
        const edit = page.locator('[data-exam-editor-form]');
        await edit.locator('[name=status]').selectOption('Publicado');
        await edit.locator('[data-exam-question-text]').fill('¿Identificas el producto?');
        await edit.locator('[data-exam-option-text]').nth(0).fill('Sí');
        await edit.locator('[data-exam-option-text]').nth(1).fill('No');
        await edit.getByRole('button', { name: 'Guardar cambios' }).click();
        await edit.waitFor({ state: 'hidden' });
        assert.equal(program.exam_definitions[0].status, 'Publicado');
        await page.reload();
        assert.match(await page.locator('[data-training-chapter="chapter-1"]').textContent(), /Publicado/);
        assert.equal(await page.locator('[data-training-chapter="chapter-2"] [data-chapter-activity-id]').count(), 0);
        const info = await page.locator('[data-training-chapter="chapter-1"] .training-chapter-body').boundingBox();
        const actions = await page.locator('[data-training-chapter="chapter-1"] [data-chapter-activities=exam]').boundingBox();
        assert.ok(actions.x > info.x, 'exam menu is to the right of chapter information');
        const taskMenu = await page.locator('[data-training-chapter="chapter-1"] [data-chapter-activities=task]').boundingBox();
        assert.ok(taskMenu.x + taskMenu.width <= 1366, 'both activity menus fit the page');
        if (process.env.TRAINING_SCREENSHOTS) await page.screenshot({ path: path.join(process.env.TRAINING_SCREENSHOTS, 'training-chapter-activities.png'), fullPage: true });
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});
