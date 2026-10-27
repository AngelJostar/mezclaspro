(() => {
    const section = root.querySelector('[data-own-assignments]');
    if (!section) return;
    root.dataset.persistedLearning = 'true';
    const home = section.closest('[data-learner-panel]');
    const rolePanel = section.closest('[data-role-panel]');
    const list = section.querySelector('[data-own-assignment-list]');
    const syllabus = section.querySelector('[data-assigned-syllabus]');
    const status = section.querySelector('[data-own-assignment-status]');
    const refresh = section.querySelector('[data-refresh-assignments]');
    const dialog = root.querySelector('[data-assigned-reader]');
    const chapters = dialog.querySelector('[data-assigned-chapters]');
    const activity = root.querySelector('[data-assigned-activity]');
    // Keep dialogs outside tab panels so switching to Tasks or Exams cannot hide them.
    rolePanel.append(dialog, activity);
    const form = activity.querySelector('form');
    const activityBody = activity.querySelector('[data-assigned-activity-body]');
    const activityError = activity.querySelector('[data-assigned-activity-error]');
    const submit = activity.querySelector('[data-assigned-activity-submit]');
    const canManage = @json($canManagePrograms);
    const currentUserId = @json(auth()->id());
    const selector = root.querySelector('[data-learner-selector]');
    let assignments = [], selectedId = null, moduleId = null, activeReaderId = null;
    let readOnly = false, canReview = false, loading = false, busy = false, submitAction = null, examTimer = null, generation = 0;
    let viewedUserId = currentUserId;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text !== undefined && text !== null) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const button = (text, action, secondary = false) => {
        const node = element('button', text, secondary ? 'training-secondary-button' : 'training-primary-button');
        node.type = 'button'; node.addEventListener('click', action); return node;
    };
    const badge = (text, state = '') => element('span', text, 'assigned-status is-' + state);
    const progressBar = value => { const node = element('progress'); node.max = 100; node.value = value; node.setAttribute('aria-label', 'Avance ' + value + '%'); return node; };
    const pause = () => dialog.querySelectorAll('video').forEach(video => video.pause());
    const closeReader = () => { pause(); dialog.close(); chapters.replaceChildren(); activeReaderId = null; };
    const closeActivity = () => { if (busy) return; activity.close(); clearInterval(examTimer); };
    dialog.querySelector('[data-close-assigned-reader]').addEventListener('click', closeReader);
    dialog.addEventListener('close', () => { pause(); chapters.replaceChildren(); activeReaderId = null; });
    activity.querySelector('[data-close-assigned-activity]').addEventListener('click', closeActivity);
    activity.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    activity.addEventListener('close', () => clearInterval(examTimer));
    const visible = () => !rolePanel.hidden && !document.hidden;

    // Retain the established navigation while replacing the browser-only learner preview.
    const mounts = {};
    ['tasks', 'training', 'results', 'certificates'].forEach(id => {
        const panel = root.querySelector('[data-learner-panel="' + id + '"]');
        Array.from(panel.children).forEach(child => { child.hidden = true; });
        mounts[id] = element('section', '', 'assigned-activities'); panel.append(mounts[id]);
    });
    mounts.exams = root.querySelector('[data-assigned-exams]');
    mounts.exams.className = 'assigned-activities';
    const applyNavigation = () => {
        Array.from(home.children).filter(child => ![section, dialog, activity].includes(child)).forEach(child => { child.hidden = true; });
        root.querySelector('.training-obligation-flow').hidden = true;
        root.querySelectorAll('[data-learner-tab]').forEach(tab => { tab.hidden = false; });
    };
    applyNavigation();

    async function request(url, method = 'GET', body) {
        const abort = new AbortController(), timer = setTimeout(() => abort.abort(), 20000);
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()) };
        if (body && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
        try {
            const response = await fetch(url, { method, body, headers, credentials: 'same-origin', signal: abort.signal });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar el cambio.');
            return data;
        } finally { clearTimeout(timer); }
    }
    function accept(data) {
        assignments = assignments.map(item => item.id === data.assignment.id ? data.assignment : item);
        render();
        if (dialog.open && activeReaderId === data.assignment.id) renderReader(data.assignment);
    }
    async function mutate(url, body, feedback = status) {
        if (busy) return null;
        busy = true;
        try { const data = await request(url, 'POST', body); accept(data); return data.assignment; }
        catch (error) { feedback.textContent = error.name === 'AbortError' ? 'No se recibió confirmación. Intenta nuevamente.' : error.message; return null; }
        finally { busy = false; }
    }
    function currentAssignment() { return assignments.find(item => item.id === selectedId); }
    function moduleState(module) { return module.state === 'completed' ? 'Completado' : module.state === 'locked' ? 'Pendiente' : 'En curso'; }

    function renderSyllabus() {
        syllabus.replaceChildren();
        const assigned = currentAssignment(); if (!assigned) return;
        const summary = element('section', '', 'assigned-summary');
        const heading = element('div', '', 'assigned-heading');
        const text = element('div');
        text.append(element('h3', assigned.title), element('p', assigned.description), element('p', 'Avance: ' + assigned.progress + '% · ' + assigned.completed_steps + ' de ' + assigned.total_steps + ' actividades completadas'));
        text.append(element('strong', assigned.status === 'completed' ? 'Capacitación concluida' : 'Estás aquí: ' + (assigned.next?.label || 'Contenido por publicar')));
        const start = button(readOnly ? 'Ver capítulo actual' : assigned.started_at ? 'Continuar capacitación' : 'Iniciar capacitación', () => startReader(assigned));
        start.dataset.startAssignment = assigned.id;
        start.disabled = assigned.status === 'completed' || !assigned.modules.length;
        heading.append(text, start); summary.append(heading, progressBar(assigned.progress)); syllabus.append(summary);
        const workspace = element('div', '', 'training-module-workspace');
        const nav = element('aside', '', 'training-module-list');
        const navHeader = element('div', '', 'training-module-list-heading');
        navHeader.append(element('strong', 'Módulos del programa'), element('span', assigned.modules.length + ' módulos')); nav.append(navHeader);
        if (!assigned.modules.some(module => module.id === moduleId)) moduleId = assigned.next?.module_id || assigned.modules[0]?.id;
        assigned.modules.forEach(module => {
            const item = button('', () => { moduleId = module.id; renderSyllabus(); }, true);
            item.className = 'training-module-item' + (moduleId === module.id ? ' is-active' : '');
            item.dataset.syllabusModule = module.id;
            const symbol = element('span', module.state === 'completed' ? '✓' : '▤', 'training-module-symbol');
            const label = element('span'); label.append(element('strong', module.stage + ' · ' + module.title), element('small', module.chapters.length + ' capítulos · ' + module.progress + '% · ' + moduleState(module)));
            item.append(symbol, label); nav.append(item);
        });
        const content = element('section', '', 'training-module-editor');
        const selected = assigned.modules.find(module => module.id === moduleId);
        if (selected) {
            const top = element('div', '', 'training-module-editor-heading');
            const title = element('div'); title.append(element('small', selected.stage), element('h3', selected.title));
            top.append(title, badge(moduleState(selected), selected.state)); content.append(top, element('p', selected.description), element('h4', 'Capítulos'));
            selected.chapters.forEach((chapter, index) => {
                const isCurrent = assigned.next?.module_id === selected.id && assigned.next?.chapter_id === chapter.id;
                const row = element('article', '', 'assigned-chapter' + (isCurrent ? ' is-current' : ''));
                row.dataset.syllabusChapter = chapter.id;
                const details = element('div'); details.append(element('small', 'Capítulo ' + (index + 1)), element('h4', chapter.title), element('p', chapter.objective), element('small', chapter.duration));
                const chapterState = chapter.activity_state;
                if (chapterState) details.append(element('small', chapterState.task_count + ' tareas · ' + chapterState.exam_count + ' exámenes'));
                row.append(details, badge(chapterState?.complete ? 'Completado' : isCurrent ? 'Estás aquí' : chapter.completed ? 'Contenido revisado' : 'Pendiente', chapterState?.complete ? 'completed' : isCurrent ? 'current' : 'locked')); content.append(row);
            });
            if (!selected.chapters.length) content.append(element('p', 'Este módulo aún no tiene capítulos publicados.'));
            content.append(element('p', selected.tasks.length + ' tareas · ' + selected.exams.length + ' exámenes', 'assigned-muted'));
        } else content.append(element('p', 'El administrador debe actualizar los módulos de esta capacitación.'));
        workspace.append(nav, content); syllabus.append(workspace);
    }

    async function startReader(assigned) {
        status.textContent = '';
        selectedId = assigned.id;
        let current = assigned;
        if (!readOnly) { current = await mutate(assigned.start_url); if (!current) return; }
        activeReaderId = current.id;
        renderReader(current);
        if (!dialog.open) dialog.showModal();
    }
    function renderReader(assigned) {
        pause(); chapters.replaceChildren();
        dialog.dataset.learningPosition = JSON.stringify(assigned.next) + ':' + assigned.completed_steps;
        dialog.querySelector('[data-assigned-reader-title]').textContent = assigned.title;
        dialog.querySelector('[data-reader-feedback]').textContent = '';
        const module = assigned.modules.find(item => item.id === assigned.next?.module_id);
        const chapter = module?.chapters.find(item => item.id === assigned.next?.chapter_id);
        const position = dialog.querySelector('[data-assigned-chapter-position]');
        position.textContent = module ? module.stage + ' · ' + module.title : '';
        if (!chapter) {
            const text = assigned.status === 'completed' ? 'Completaste esta capacitación.' : assigned.next?.label || 'No hay capítulos disponibles.';
            chapters.append(element('h4', text));
            if (module?.tasks.some(task => task.submission?.status !== 'approved')) {
                chapters.append(button('Ir a Tareas', () => { closeReader(); activateLearnerTab('tasks', true); }));
            } else if (module?.exams.some(exam => !exam.passed)) {
                chapters.append(button('Ir a Exámenes', () => { closeReader(); activateLearnerTab('assigned-exams', true); }));
            }
            return;
        }
        const index = module.chapters.findIndex(item => item.id === chapter.id);
        position.textContent += ' · Capítulo ' + (index + 1) + ' de ' + module.chapters.length;
        const card = element('article'); card.dataset.currentChapter = chapter.id;
        card.append(element('h4', chapter.title), element('p', chapter.objective), element('small', chapter.duration));
        if (chapter.file_url) {
            const video = element('video'); video.controls = true; video.preload = 'metadata'; video.src = chapter.file_url;
            video.setAttribute('aria-label', chapter.title); card.append(video);
        } else if (chapter.video_url) {
            let embed = null;
            try {
                const url = new URL(chapter.video_url);
                const id = url.hostname === 'youtu.be' ? url.pathname.slice(1) : /(^|\.)youtube\.com$/.test(url.hostname) ? (url.searchParams.get('v') || url.pathname.split('/').pop()) : null;
                if (id && /^[a-zA-Z0-9_-]{11}$/.test(id)) embed = 'https://www.youtube-nocookie.com/embed/' + id;
            } catch (_) {}
            if (embed) { const frame = element('iframe'); frame.src = embed; frame.title = chapter.title; frame.allowFullscreen = true; card.append(frame); }
            const link = element('a', 'Abrir video en otra pestaña'); link.href = chapter.video_url; link.target = '_blank'; link.rel = 'noopener noreferrer'; card.append(link);
        }
        if (!readOnly && !chapter.completed) {
            const complete = button('Marcar capítulo como revisado y continuar', async () => {
                complete.disabled = true;
                const updated = await mutate(chapter.complete_url, undefined, dialog.querySelector('[data-reader-feedback]'));
                if (updated) renderReader(updated); else complete.disabled = false;
            }); complete.dataset.completeChapter = chapter.id; card.append(complete);
        } else if (readOnly) card.append(element('p', 'Vista del alumno. El avance solo puede registrarlo su cuenta.'));
        if (chapter.completed) {
            const tasks = module.tasks.filter(task => task.chapterId === chapter.id);
            const exams = module.exams.filter(exam => exam.chapterId === chapter.id);
            if (tasks.length || exams.length) card.append(element('h4', 'Actividades de este capítulo'));
            tasks.forEach(task => {
                const row = element('section', '', 'assigned-chapter');
                row.append(element('strong', task.title));
                const state = task.submission?.status;
                if (!readOnly && !['approved', 'submitted'].includes(state)) {
                    const action = button(state === 'returned' ? 'Corregir tarea' : 'Entregar tarea', () => openTask(assigned, module, task));
                    action.disabled = !task.available; row.append(action);
                } else row.append(badge(state === 'approved' ? 'Aprobada' : state === 'submitted' ? 'Pendiente de revisión' : 'Pendiente'));
                card.append(row);
            });
            exams.forEach(exam => {
                const row = element('section', '', 'assigned-chapter'); row.append(element('strong', exam.name));
                if (exam.passed) row.append(badge('Aprobado', 'completed'));
                else if (!readOnly) {
                    const live = exam.results.some(result => !result.submitted_at && Date.parse(result.expires_at) > Date.now());
                    const action = button(live ? 'Continuar examen' : 'Iniciar examen', () => openExam(exam));
                    action.disabled = !exam.available || (!live && exam.results.length >= exam.attempts); row.append(action);
                    if (!exam.available) row.append(element('small', 'Espera la aprobación de las tareas de este capítulo.'));
                }
                card.append(row);
            });
        }
        chapters.append(card);
    }

    function activityDialog(title, action, label) {
        clearInterval(examTimer); activityBody.replaceChildren(); activityError.textContent = '';
        activity.querySelector('h3').textContent = title; submitAction = action; submit.textContent = label; submit.disabled = false;
    }
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !submitAction) return;
        submit.disabled = true; activityError.textContent = '';
        try { await submitAction(); } catch (error) { activityError.textContent = error.message; }
        finally { submit.disabled = false; }
    });
    function openTask(assigned, module, task) {
        const submission = task.submission;
        activityDialog(task.title, async () => {
            const updated = await mutate(task.submit_url, new FormData(form), activityError);
            if (updated) activity.close();
        }, 'Entregar tarea');
        activityBody.append(element('p', task.instructions || 'Realiza la actividad indicada por tu administrador.'));
        const label = element('label', 'Respuesta'); const response = element('textarea'); response.name = 'response'; response.maxLength = 10000; response.value = submission?.response || ''; label.append(response);
        const fileLabel = element('label', 'Evidencia (máximo 10 MB)'); const file = element('input'); file.type = 'file'; file.name = 'file'; file.accept = '.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt'; file.required = task.requires_file && !submission?.file_name; fileLabel.append(file);
        activityBody.append(label, fileLabel); if (submission?.feedback) activityBody.append(element('p', 'Comentarios: ' + submission.feedback));
        activity.showModal();
    }
    function openReview(task, approve) {
        const submission = task.submission;
        activityDialog((approve ? 'Aprobar: ' : 'Solicitar corrección: ') + task.title, async () => {
            const updated = await mutate(submission.review_url, { status: approve ? 'approved' : 'returned', feedback: form.elements.feedback.value }, activityError);
            if (updated) activity.close();
        }, approve ? 'Aprobar tarea' : 'Devolver tarea');
        activityBody.append(element('p', submission.response || 'Entrega con archivo adjunto.'));
        if (submission.file_url) { const link = element('a', submission.file_name); link.href = submission.file_url; activityBody.append(link); }
        const label = element('label', 'Comentarios'); const input = element('textarea'); input.name = 'feedback'; input.maxLength = 5000; input.required = !approve; label.append(input); activityBody.append(label);
        activity.showModal();
    }
    async function openExam(exam) {
        if (busy) return; busy = true;
        let attempt;
        try { attempt = (await request(exam.begin_url, 'POST')).attempt; }
        catch (error) { status.textContent = error.message; dialog.querySelector('[data-reader-feedback]').textContent = error.message; mounts.exams.prepend(element('p', error.message)); return; }
        finally { busy = false; }
        activityDialog(attempt.name, async () => {
            const answers = attempt.items.map((item, index) => Array.from(form.querySelectorAll('[name="question-' + index + '"]:checked')).map(input => Number(input.value)));
            const updated = await mutate(attempt.submit_url, { answers }, activityError);
            if (updated) { activity.close(); clearInterval(examTimer); }
        }, 'Entregar examen');
        activityBody.append(element('p', attempt.instructions || 'Selecciona tus respuestas y entrega el examen.'));
        const time = element('p'); activityBody.append(time);
        const tick = () => {
            const seconds = Math.max(0, Math.floor((Date.parse(attempt.expires_at) - Date.now()) / 1000));
            time.textContent = 'Tiempo restante: ' + Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
            if (!seconds) { clearInterval(examTimer); time.textContent = 'El tiempo del examen terminó. Entrega para registrar el resultado.'; }
        };
        tick(); examTimer = setInterval(tick, 1000);
        attempt.items.forEach((item, index) => {
            const field = element('fieldset'); field.append(element('legend', (index + 1) + '. ' + item.text));
            item.options.forEach((option, optionIndex) => {
                const label = element('label'); const input = element('input'); input.type = item.type === 'multiple' ? 'checkbox' : 'radio'; input.name = 'question-' + index; input.value = optionIndex;
                label.append(input, document.createTextNode(option)); field.append(label);
            }); activityBody.append(field);
        });
        activity.showModal();
    }

    function renderActivities() {
        mounts.tasks.replaceChildren(element('h2', 'Tareas'), element('p', 'Entrega las actividades de tus módulos. El administrador revisará la evidencia.'));
        mounts.exams.replaceChildren(element('h2', 'Exámenes'), element('p', 'Los exámenes se habilitan al revisar su capítulo y aprobar las tareas correspondientes.'));
        mounts.results.replaceChildren(element('h2', 'Resultados'));
        let tasks = 0, exams = 0, results = 0;
        assignments.forEach(assigned => assigned.modules.forEach(module => {
            module.tasks.forEach(task => {
                tasks++; const card = element('article'); card.dataset.assignedTask = task.id;
                card.append(element('h3', task.title), element('p', assigned.title + ' · ' + module.stage + ' · ' + module.title + (task.chapter_title ? ' · ' + task.chapter_title : ''), 'assigned-muted'), element('p', task.instructions || 'Consulta las indicaciones de tu administrador.'));
                const submission = task.submission;
                const labels = { submitted: 'Entregada · pendiente de revisión', approved: 'Aprobada', returned: 'Requiere corrección' };
                card.append(badge(submission ? labels[submission.status] : 'Pendiente', submission?.status === 'approved' ? 'completed' : ''));
                if (task.due_date) card.append(element('p', 'Fecha límite: ' + task.due_date.split('-').reverse().join('/')));
                if (submission?.feedback) card.append(element('p', 'Comentarios: ' + submission.feedback));
                if (submission?.file_url) { const link = element('a', submission.file_name); link.href = submission.file_url; card.append(link); }
                if (!readOnly && !['approved', 'submitted'].includes(submission?.status)) {
                    const action = button(submission ? 'Corregir entrega' : 'Entregar tarea', () => openTask(assigned, module, task));
                    action.disabled = !task.available || assigned.status !== 'in_progress'; card.append(action);
                    if (action.disabled) card.append(element('p', 'Inicia la capacitación y revisa los capítulos previos.', 'assigned-muted'));
                }
                if (canReview && submission?.status === 'submitted' && assigned.status !== 'completed') {
                    card.append(button('Revisar y aprobar', () => openReview(task, true)), button('Solicitar corrección', () => openReview(task, false), true));
                }
                mounts.tasks.append(card);
            });
            module.exams.forEach(exam => {
                exams++; const card = element('article'); card.dataset.assignedExam = exam.id;
                card.append(element('h3', exam.name), element('p', assigned.title + ' · ' + module.stage + ' · ' + module.title + (exam.chapter_title ? ' · ' + exam.chapter_title : ''), 'assigned-muted'),
                    element('p', exam.question_count + ' preguntas · Mínimo ' + exam.minimum + '% · ' + (exam.duration || 30) + ' minutos · ' + exam.results.length + '/' + (exam.attempts || 1) + ' intentos'));
                const last = exam.results[0];
                const live = last && !last.submitted_at && Date.parse(last.expires_at) > Date.now();
                card.append(badge(exam.passed ? 'Aprobado' : last?.submitted_at ? 'Último resultado: ' + last.score + '%' : live ? 'Examen en curso' : 'Pendiente', exam.passed ? 'completed' : ''));
                if (!readOnly && !exam.passed) {
                    const action = button(live ? 'Continuar examen' : 'Iniciar examen', () => openExam(exam));
                    action.disabled = !exam.available || assigned.status !== 'in_progress' || (!live && exam.results.length >= (exam.attempts || 1));
                    card.append(action);
                    if (action.disabled) card.append(element('p', !exam.available ? 'Completa los capítulos y las tareas previas.' : 'No hay intentos disponibles o la capacitación no está en curso.', 'assigned-muted'));
                }
                mounts.exams.append(card);
                exam.results.forEach(result => {
                    if (!result.submitted_at) return;
                    results++; const row = element('article'); row.append(element('h3', exam.name), element('p', assigned.title + ' · ' + module.title),
                        badge(result.score + '% · ' + (result.passed ? 'Aprobado' : 'No aprobado'), result.passed ? 'completed' : '')); mounts.results.append(row);
                });
            });
        }));
        if (!tasks) mounts.tasks.append(element('p', 'No hay tareas publicadas para tus módulos asignados.'));
        if (!exams) mounts.exams.append(element('p', 'No hay exámenes publicados para tus módulos asignados.'));
        if (!results) mounts.results.append(element('p', 'Aún no has entregado exámenes.'));
        mounts.training.replaceChildren(element('h2', 'Mi capacitación'), element('p', 'Consulta el temario y retoma tu capítulo actual desde Inicio.'), button('Ir a mi temario', () => activateLearnerTab('home', true)));
        mounts.certificates.replaceChildren(element('h2', 'Certificados'), element('p', 'Los resultados de tus capacitaciones se muestran en Resultados. Solicita al administrador la emisión de tu certificado.'));
    }

    function render() {
        applyNavigation(); list.replaceChildren();
        if (!assignments.some(item => item.id === selectedId)) { selectedId = assignments.find(item => item.status !== 'completed')?.id || assignments[0]?.id; moduleId = null; }
        status.textContent = assignments.length ? 'Selecciona un programa para consultar su temario y avance.' : 'No tienes capacitaciones asignadas.';
        assignments.forEach(assigned => {
            const card = element('article', '', 'training-program-carousel-card' + (assigned.id === selectedId ? ' is-active' : ''));
            const choice = button('', () => { selectedId = assigned.id; moduleId = null; render(); });
            choice.className = 'training-program-carousel-select'; choice.setAttribute('aria-pressed', String(assigned.id === selectedId));
            choice.append(badge(assigned.status === 'completed' ? 'Concluida' : assigned.started_at ? 'En curso' : 'Por iniciar', assigned.status === 'completed' ? 'completed' : ''),
                element('strong', assigned.title), element('span', assigned.description, 'training-program-carousel-description'),
                element('small', assigned.modules.length + ' módulos · Inicio ' + assigned.start_date.split('-').reverse().join('/')), element('b', assigned.progress + '%'), progressBar(assigned.progress));
            card.append(choice); list.append(card);
        });
        renderSyllabus(); renderActivities();
    }
    async function load() {
        if (loading || busy || activity.open) return;
        loading = true; refresh.disabled = true; const version = generation;
        try {
            const url = new URL(@json(route('admin.capacitaciones.asignaciones.index')), location.href);
            if (canManage && viewedUserId) url.searchParams.set('user_id', viewedUserId);
            const data = await request(url);
            if (version !== generation) return;
            assignments = data.assignments; readOnly = data.read_only || false; canReview = data.can_review || false;
            section.querySelector('[data-assigned-student-name]').textContent = data.student?.name || @json(auth()->user()?->name);
            render();
            if (dialog.open) {
                const updated = assignments.find(item => item.id === activeReaderId);
                if (!updated || updated.status === 'completed') closeReader();
                else if (dialog.dataset.learningPosition !== JSON.stringify(updated.next) + ':' + updated.completed_steps) renderReader(updated);
            }
        } catch (error) {
            list.replaceChildren(); syllabus.replaceChildren(); closeReader();
            status.textContent = 'No se pudieron cargar las capacitaciones. Pulsa Actualizar para intentar nuevamente.';
        } finally { loading = false; refresh.disabled = false; if (version !== generation && visible()) load(); }
    }
    root.querySelector('[data-open-learner-panel]')?.addEventListener('click', () => {
        if (!canManage) return;
        viewedUserId = Number(selector?.selectedOptions[0]?.dataset.userId) || currentUserId;
        generation++; assignments = []; selectedId = null; closeReader(); closeActivity(); load();
    });
    refresh.addEventListener('click', load);
    root.addEventListener('training-role-change', () => { applyNavigation(); if (visible()) load(); });
    root.addEventListener('training-learner-tab', () => { applyNavigation(); if (visible()) load(); });
    window.addEventListener('focus', () => { if (visible()) load(); });
    document.addEventListener('visibilitychange', () => { if (visible()) load(); });
    setInterval(() => { if (visible()) load(); }, 30000);
    if (visible()) load();
})();
