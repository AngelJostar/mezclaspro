const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { readFileSync, mkdirSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root, 'public/build/manifest.json')));
const css = [manifest['resources/css/app.css'].file, ...manifest['resources/js/app.js'].css]
    .map(file => readFileSync(path.join(root, 'public/build', file), 'utf8')).join('\n');
let browser;
before(async () => { browser = await chromium.launch({ channel: process.env.PLAYWRIGHT_CHANNEL || 'msedge', headless: true }); });
after(async () => { await browser.close(); });

const program = { id: 'real', title: 'Sistema de Gestión de Calidad', description: 'Conoce el sistema de calidad y sus procedimientos.', owner: 'Calidad', status: 'Activo',
    module_definitions: [{ id: 'm1', project: 'real', stage: 'Módulo 1', title: 'Sistema de calidad', description: 'Documentación y buenas prácticas.', chapters: 2, duration: '9 min',
        chapter_definitions: [{ id: 'c1', title: 'Análisis de contexto', objective: 'Identifica el contexto de la organización.', duration: '6 min', video: 'https://www.youtube.com/watch?v=M7lc1UVf-VE' },
            { id: 'c2', title: 'Filosofía empresarial', objective: 'Conoce los valores de la organización.', duration: '3 min', video: null }] }],
    task_definitions: [{ id: 't1', projectId: 'real', stage: 'Módulo 1', tone: 'active', due: '01/11/2026', moduleId: 'm1', title: 'Resumen del sistema', instructions: 'Describe lo aprendido.', status: 'Activa', requires_file: false, due_date: '2026-11-01' }],
    exam_definitions: [{ id: 'e1', projectId: 'real', module: 'Módulo 1', questions: 1, tone: 'active', moduleId: 'm1', name: 'Evaluación de calidad', status: 'Publicado', minimum: 80, duration: 10, attempts: 2,
        items: [{ text: '¿Revisaste los procedimientos?', type: 'single', points: 1, options: ['Sí', 'No'], correct: 0 }] }],
};

async function fixture(chapterActivities = false) {
    const course = structuredClone(program);
    if (chapterActivities) { course.task_definitions[0].chapterId = 'c1'; course.exam_definitions[0].chapterId = 'c1'; }
    let userId = 2, failure = false, loadFailure = false;
    const state = { personnel: [{ id: 2, name: 'Gabriela', lastname: 'Prueba' }],
        assignments: [{ user_id: 2, program_id: 'real', module_ids: ['m1'], start_date: '2026-10-26', status: 'assigned' }], submissions: [], attempts: [] };
    const writes = [], errors = [];
    const page = await browser.newPage({ viewport: { width: 1400, height: 920 } });
    page.on('pageerror', error => errors.push(error.message));
    const render = (type, viewUser) => execFileSync('php', ['tests/Browser/fixtures/training.php', type, '[]', JSON.stringify([course]), 'null', JSON.stringify({ ...state, as_user: userId, view_user: viewUser })],
        { cwd: root, encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
    const api = viewUser => JSON.parse(render('assignments', viewUser));
    await page.route('**/*', async route => {
        const request = route.request(), url = new URL(request.url());
        if (url.hostname !== 'training.test') return route.abort();
        const assigned = state.assignments[0];
        if (request.method() === 'POST') {
            writes.push(url.pathname);
            if (failure) return route.fulfill({ status: 500, json: { message: 'No se pudo guardar el avance.' } });
            if (url.pathname.endsWith('/iniciar')) { assigned.status = 'in_progress'; assigned.started_at = '2026-10-26 12:00:00'; }
            else if (url.pathname.endsWith('/completar')) {
                const chapter = url.pathname.match(/capitulos\/([^/]+)\/completar/)[1];
                assigned.learning_progress ||= { chapters: { m1: [] } }; assigned.learning_progress.chapters.m1.push(chapter);
            } else if (url.pathname.endsWith('/tareas/t1')) {
                state.submissions = [{ assignment_id: 1, module_id: 'm1', task_id: 't1', response: 'Mi respuesta', status: 'submitted' }];
            } else if (url.pathname.endsWith('/revisar')) { state.submissions[0].status = 'approved'; }
            else if (url.pathname.endsWith('/examenes/e1')) {
                state.attempts = [{ assignment_id: 1, module_id: 'm1', exam_id: 'e1', exam_snapshot: course.exam_definitions[0], expires_at: new Date(Date.now() + 600000).toISOString() }];
                return route.fulfill({ json: { attempt: { id: 1, name: 'Evaluación de calidad', expires_at: state.attempts[0].expires_at,
                    submit_url: 'http://training.test/admin/capacitaciones/asignaciones/1/intentos/1/entregar', items: course.exam_definitions[0].items.map(({ correct, ...item }) => item) } } });
            } else if (url.pathname.endsWith('/entregar')) {
                assert.deepEqual(request.postDataJSON().answers, [[0]]);
                Object.assign(state.attempts[0], { submitted_at: '2026-10-26 12:30:00', score: 100, passed: true });
            } else throw new Error('Unexpected request ' + url.pathname);
            if (assigned.learning_progress?.chapters.m1.length === 2 && state.attempts.some(a => a.passed) && state.submissions[0]?.status === 'approved') assigned.status = 'completed';
            return route.fulfill({ json: { assignment: api(2).assignments[0] } });
        }
        if (url.pathname.endsWith('/asignaciones')) return route.fulfill({ status: loadFailure ? 503 : 200, json: loadFailure ? { message: 'Temporal' } : api(url.searchParams.get('user_id') || undefined) });
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="test"><style>' + css + '</style>' + render(url.pathname.endsWith('/alumnos') ? 'alumnos' : 'programas') });
    });
    await page.goto('http://training.test/admin/capacitaciones/programas');
    return { page, state, errors, writes, login: id => { userId = id; }, fail: value => { failure = value; }, failLoad: value => { loadFailure = value; } };
}

test('syllabus, wide single-chapter reader, persistent position, task submission, administrator review and examination', async () => {
    const f = await fixture(), { page } = f;
    try {
        await page.locator('[data-learner-tab="training"]').click();
        await page.locator('[data-start-assignment]').waitFor();
        assert.equal(await page.locator('[data-syllabus-chapter]').count(), 2);
        assert.match(await page.locator('[data-assigned-syllabus]').innerText(), /Estás aquí: Módulo 1 · Análisis de contexto/);
        await page.getByRole('button', { name: 'Iniciar capacitación', exact: true }).click();
        const reader = page.locator('[data-assigned-reader]'); await reader.waitFor({ state: 'visible' });
        assert.equal(await page.locator('[data-assigned-chapters] article').count(), 1);
        assert.equal(await page.locator('[data-current-chapter="c1"]').count(), 1);
        assert.ok((await reader.boundingBox()).width >= 1250);
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-capitulo-amplio.png') });
        f.fail(true); await page.locator('[data-complete-chapter]').click();
        await page.getByText('No se pudo guardar el avance.', { exact: true }).waitFor();
        assert.equal(await page.locator('[data-current-chapter="c1"]').count(), 1);
        f.fail(false); await page.locator('[data-complete-chapter]').click();
        await page.locator('[data-current-chapter="c2"]').waitFor();
        assert.equal(await page.locator('[data-assigned-chapters] article').count(), 1);
        await page.locator('[data-close-assigned-reader]').click();
        await page.reload(); await page.locator('[data-learner-tab="training"]').click(); await page.locator('[data-start-assignment]').waitFor();
        await page.screenshot({ path: path.join(root, 'output/screenshots/capacitacion-temario-avance.png'), fullPage: true });
        assert.match(await page.locator('[data-assigned-syllabus]').innerText(), /25%/);
        await page.getByRole('button', { name: 'Continuar capacitación', exact: true }).click();
        await page.locator('[data-current-chapter="c2"]').waitFor();
        await page.locator('[data-complete-chapter]').click();
        await page.getByRole('button', { name: 'Ir a Tareas', exact: true }).click();
        await page.getByRole('button', { name: 'Entregar tarea', exact: true }).click();
        await page.locator('[data-assigned-activity] textarea').fill('Mi respuesta');
        await page.locator('[data-assigned-activity-submit]').click();
        await page.getByText('Entregada · pendiente de revisión', { exact: true }).waitFor();
        await page.locator('[data-learner-tab="assigned-exams"]').click();
        assert.equal(await page.getByRole('button', { name: 'Iniciar examen', exact: true }).isDisabled(), true);
        f.login(1); await page.goto('http://training.test/admin/capacitaciones/alumnos');
        await page.locator('[data-role-button="learner"]').click();
        const id = await page.locator('[data-learner-selector] option[data-user-id="2"]').getAttribute('value');
        await page.locator('[data-learner-selector]').selectOption(id);
        await page.locator('[data-open-learner-panel]').click();
        await page.locator('[data-dashboard-student-name]').filter({ hasText: 'Gabriela Prueba' }).waitFor();
        await page.locator('[data-learner-tab="tasks"]').click();
        await page.getByRole('button', { name: 'Revisar y aprobar', exact: true }).click();
        await page.locator('[data-assigned-activity-submit]').click();
        await page.locator('[data-assigned-task]').getByText('Aprobada', { exact: true }).waitFor();
        f.login(2); await page.goto('http://training.test/admin/capacitaciones/programas');
        await page.locator('[data-dashboard-value="training"]').waitFor(); await page.locator('[data-learner-tab="assigned-exams"]').click();
        await page.getByRole('button', { name: 'Iniciar examen', exact: true }).click();
        await page.locator('[data-assigned-activity] input[value="0"]').check();
        await page.locator('[data-assigned-activity-submit]').click();
        await page.locator('[data-assigned-exam]').getByText('Aprobado', { exact: true }).waitFor();
        await page.locator('[data-learner-tab="home"]').click();
        await page.locator('[data-dashboard-value="results"]').filter({ hasText: '100%' }).waitFor();
        assert.equal(await page.locator('[data-dashboard-value="certificates"]').innerText(), '1');
        assert.equal(await page.locator('[data-dashboard-value="tasks"]').innerText(), '0');
        assert.equal(await page.locator('[data-dashboard-value="exams"]').innerText(), '0');
        assert.equal(await page.locator('[data-assigned-syllabus]').isVisible(), false);
        await page.locator('[data-learner-tab="training"]').click();
        assert.match(await page.locator('[data-assigned-syllabus]').innerText(), /100%/);
        assert.deepEqual(f.errors, []);
    } catch (error) {
        console.error('Browser errors:', f.errors);
        console.error((await page.locator('body').innerText()).slice(-4000));
        throw error;
    } finally { await page.close(); }
});

test('learner stays on the current chapter for its task and exam before progressing to the next chapter', async () => {
    const f = await fixture(true), { page } = f;
    try {
        await page.locator('[data-learner-tab="training"]').click();
        await page.locator('[data-start-assignment]').click();
        const reader = page.locator('[data-assigned-reader]');
        await reader.locator('[data-current-chapter="c1"]').waitFor();
        await reader.locator('[data-complete-chapter]').click();
        await reader.getByRole('button', { name: 'Entregar tarea', exact: true }).waitFor();
        assert.equal(await reader.locator('[data-current-chapter="c1"]').count(), 1);
        assert.equal(await reader.locator('[data-current-chapter="c2"]').count(), 0);
        assert.equal(await reader.getByRole('button', { name: 'Iniciar examen', exact: true }).isDisabled(), true);
        await reader.getByRole('button', { name: 'Entregar tarea', exact: true }).click();
        await page.locator('[data-assigned-activity] textarea').fill('Mi respuesta');
        await page.locator('[data-assigned-activity-submit]').click();
        await reader.getByText('Pendiente de revisión', { exact: true }).waitFor();
        f.state.submissions[0].status = 'approved';
        await page.locator('[data-close-assigned-reader]').click();
        await page.locator('[data-refresh-assignments]').click();
        await page.locator('[data-assigned-syllabus]').getByText(/Estás aquí:.*Exámenes pendientes/).waitFor();
        await page.locator('[data-start-assignment]').click();
        await reader.getByRole('button', { name: 'Iniciar examen', exact: true }).click();
        await page.locator('[data-assigned-activity] input[value="0"]').check();
        await page.locator('[data-assigned-activity-submit]').click();
        await reader.locator('[data-current-chapter="c2"]').waitFor();
        assert.equal(await reader.locator('[data-current-chapter]').count(), 1);
        await reader.locator('[data-complete-chapter]').click();
        await reader.getByText('Completaste esta capacitación.', { exact: true }).waitFor();
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});

test('home summarizes real assignments, navigates to each section and clears stale data on errors or student changes', async () => {
    const f = await fixture(), { page } = f;
    const value = key => page.locator('[data-dashboard-value="' + key + '"]');
    const refresh = async () => {
        await page.locator('[data-refresh-dashboard]').click();
        await page.waitForFunction(() => document.querySelector('[data-learner-dashboard]').getAttribute('aria-busy') === 'false');
    };
    try {
        await value('training').waitFor();
        assert.equal(await page.locator('[data-learner-tab="home"]').getAttribute('aria-selected'), 'true');
        assert.equal(await page.locator('[data-own-assignments]').isVisible(), false);
        assert.equal(await value('training').innerText(), '1');
        assert.equal(await value('tasks').innerText(), '1');
        assert.equal(await value('exams').innerText(), '1');
        assert.equal(await value('results').innerText(), '—');
        assert.equal(await value('certificates').innerText(), '0');
        assert.match(await page.locator('[data-dashboard-certificates]').innerText(), /Aún no tienes capacitaciones concluidas/);
        mkdirSync(path.join(root, 'output/screenshots'), { recursive: true });
        for (const width of [1366, 390]) {
            await page.setViewportSize({ width, height: 920 });
            const box = await page.locator('[data-learner-dashboard]').boundingBox();
            assert.ok(box.x >= 0 && box.x + box.width <= width, 'dashboard fits viewport');
            assert.ok(await page.locator('[data-learner-dashboard]').evaluate(node => node.scrollWidth <= node.clientWidth + 1), 'dashboard content does not overflow');
            await page.screenshot({ path: path.join(root, 'output/screenshots/learner-dashboard-' + width + '.png'), fullPage: true });
        }
        for (const [key, tab] of [['training', 'training'], ['tasks', 'tasks'], ['exams', 'assigned-exams'], ['results', 'results'], ['certificates', 'certificates']]) {
            await page.locator('[data-dashboard-metric="' + key + '"]').click();
            assert.equal(await page.locator('[data-learner-tab="' + tab + '"]').getAttribute('aria-selected'), 'true');
            if (key === 'training') {
                assert.equal(await page.locator('[data-own-assignments]').isVisible(), true);
                assert.equal(await page.locator('[data-syllabus-chapter]').count(), 2);
            }
            await page.locator('[data-learner-tab="home"]').click();
        }
        f.state.submissions = [{ assignment_id: 1, module_id: 'm1', task_id: 't1', status: 'submitted' }];
        f.state.attempts = [
            { assignment_id: 1, module_id: 'm1', exam_id: 'e1', exam_snapshot: {}, submitted_at: '2026-11-01 12:00:00', expires_at: '2026-11-01 12:10:00', score: 40, passed: false },
            { assignment_id: 1, module_id: 'm1', exam_id: 'e1', exam_snapshot: {}, submitted_at: '2026-11-02 12:00:00', expires_at: '2026-11-02 12:10:00', score: 100, passed: true },
        ];
        await refresh();
        assert.equal(await value('results').innerText(), '100%', 'latest submitted attempt determines the average');
        assert.equal(await value('exams').innerText(), '0');
        assert.match(await page.locator('[data-dashboard-metric="tasks"]').innerText(), /0 por entregar · 1 en revisión/);
        assert.match(await page.locator('[data-dashboard-results] .learner-dashboard-row').first().innerText(), /100% · Aprobado/);
        f.failLoad(true); await refresh();
        assert.equal(await page.locator('[data-dashboard-content]').isVisible(), false);
        assert.match(await page.locator('[data-dashboard-message]').innerText(), /No se pudieron cargar/);
        f.failLoad(false); await refresh();
        assert.equal(await value('training').innerText(), '1');
        f.state.assignments = []; f.state.submissions = []; f.state.attempts = []; await refresh();
        assert.equal(await value('training').innerText(), '0');
        assert.equal(await value('results').innerText(), '—');
        assert.match(await page.locator('[data-dashboard-message]').innerText(), /No tienes capacitaciones asignadas/);
        f.state.assignments = [{ user_id: 2, program_id: 'real', module_ids: ['m1'], start_date: '2026-10-26', status: 'assigned' }];
        f.login(1); await page.goto('http://training.test/admin/capacitaciones/alumnos');
        await page.locator('[data-role-button="learner"]').click();
        const id = await page.locator('[data-learner-selector] option[data-user-id="2"]').getAttribute('value');
        await page.locator('[data-learner-selector]').selectOption(id);
        await page.locator('[data-open-learner-panel]').click();
        await page.locator('[data-dashboard-student-name]').filter({ hasText: 'Gabriela Prueba' }).waitFor();
        assert.equal(await value('training').innerText(), '1');
        await page.locator('[data-learner-tab="training"]').click();
        await page.getByRole('button', { name: 'Ver capítulo actual', exact: true }).waitFor();
        assert.equal(await page.getByRole('button', { name: 'Iniciar capacitación', exact: true }).count(), 0, 'administrator preview remains read only');
        assert.deepEqual(f.writes, [], 'navigation and dashboard never write learning progress');
        assert.deepEqual(f.errors, []);
    } finally { await page.close(); }
});
