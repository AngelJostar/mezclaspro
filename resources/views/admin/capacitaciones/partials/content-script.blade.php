const contentPrograms = @json(collect($projects)->keyBy('id')->all());
const contentTasks = @json($programTasks);
const contentDialog = root.querySelector('[data-content-dialog]');
const contentForm = root.querySelector('[data-content-form]');
const contentFields = root.querySelector('[data-content-fields]');
const contentError = root.querySelector('[data-content-error]');
const contentFeedback = root.querySelector('[data-content-feedback]');
const contentConfirm = root.querySelector('[data-content-confirm]');
let contentEditing = null;
let contentBusy = false;
let chapterToDelete = null;
let chapterDeleteStep = 0;
let chapterOrderBusy = false;
const chapterOrderMessages = new Map();
let pendingVideoUrl = null;
const chapterExamDialog = document.createElement('dialog');
chapterExamDialog.className = 'training-program-modal training-chapter-exam-dialog';
chapterExamDialog.dataset.chapterExamDialog = '';
chapterExamDialog.setAttribute('aria-label', 'Crear examen del capítulo');
root.append(chapterExamDialog);
chapterExamDialog.append(examCreateView);
// Editors must remain visible while the module tab is active.
root.append(examEditorModal, examViewModal);
chapterExamDialog.addEventListener('cancel', event => { event.preventDefault(); closeNewExamBuilder(); });
@php($chapterVideoUrl = route('admin.capacitaciones.programas.video', ['program' => '__PROGRAM__', 'module' => '__MODULE__', 'chapter' => '__CHAPTER__']))
const chapterVideoRoute = @json($chapterVideoUrl);

function selectedContentModule() {
    const active = root.querySelector('[data-module-item].is-active');
    return moduleDefaults.find(module => module.project === programSelector?.value && module.id === active?.dataset.moduleId);
}
function chapterUrl(programId, moduleId, chapterId) {
    return chapterVideoRoute.replace('__PROGRAM__', encodeURIComponent(programId)).replace('__MODULE__', encodeURIComponent(moduleId)).replace('__CHAPTER__', encodeURIComponent(chapterId));
}
function contentField(label, name, type, value, attributes = {}) {
    const wrapper = document.createElement('label');
    const caption = document.createElement('span');
    caption.textContent = label;
    const input = document.createElement(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input');
    if (!['textarea', 'select'].includes(type)) input.type = type;
    input.name = name;
    if (type === 'checkbox') input.checked = !!value;
    else if (type !== 'file') input.value = value ?? '';
    Object.entries(attributes).forEach(([key, val]) => { if (key === 'wide') wrapper.classList.toggle('is-wide', val); else input[key] = val; });
    wrapper.append(caption, input);
    contentFields.append(wrapper);
    return input;
}
function contentOptions(input, options, selected) {
    options.forEach(([value, text]) => input.add(new Option(text, value)));
    input.value = selected ?? options[0]?.[0] ?? '';
}
function contentNotice(text) {
    if (contentFeedback) { contentFeedback.textContent = text; contentFeedback.hidden = false; }
}
function clearVideoPreview() {
    if (pendingVideoUrl) URL.revokeObjectURL(pendingVideoUrl);
    pendingVideoUrl = null;
}
function closeContentDialog() {
    if (contentBusy) return;
    contentDialog.close();
    contentEditing = null;
    clearVideoPreview();
}
function openContent(kind, entry = null, resume = null, chapter = null) {
    const programId = programSelector?.value;
    if (!canManagePrograms || !programId || !contentDialog || contentBusy || chapterOrderBusy) return;
    const module = selectedContentModule();
    if (kind === 'chapter' && !module) return;
    contentEditing = { kind, id: entry?.id || '', programId, moduleId: module?.id || '', chapterId: chapter?.id || entry?.chapterId || '', resume };
    clearVideoPreview();
    contentFields.replaceChildren();
    contentError.hidden = true;
    root.querySelector('[data-content-program]').textContent = contentPrograms[programId]?.title || '';
    root.querySelector('#training-content-title').textContent = ({ module: entry ? 'Editar módulo' : 'Agregar módulo', chapter: entry ? 'Editar capítulo' : 'Agregar capítulo', task: entry ? 'Editar tarea' : 'Nueva tarea' })[kind];
    root.querySelector('[data-content-save]').disabled = false;
    if (kind === 'module') {
        if (resume) {
            const note = document.createElement('p'); note.className = 'is-wide';
            note.textContent = 'Primero crea el módulo. Después podrás continuar con ' + (resume === 'exam' ? 'el examen.' : 'la tarea.');
            contentFields.append(note);
        }
        contentField('Etiqueta', 'stage', 'text', entry?.stage || 'Módulo ' + (moduleDefaults.filter(m => m.project === programId).length + 1), { required: true, maxLength: 60 });
        contentField('Nombre del módulo', 'title', 'text', entry?.title, { required: true, maxLength: 150 });
        contentField('Descripción', 'description', 'textarea', entry?.description, { rows: 4, maxLength: 2000, wide: true });
    } else if (kind === 'chapter') {
        contentField('Título del capítulo', 'title', 'text', entry?.title, { required: true, maxLength: 150 });
        const duration = contentField('Duración (minutos)', 'duration_minutes', 'number', Math.round((entry?.duration_seconds || 0) / 60 * 100) / 100, { required: true, min: 0, max: 1440, step: '0.01' });
        contentField('Objetivo', 'objective', 'textarea', entry?.objective, { rows: 3, maxLength: 2000, wide: true });
        contentField('Enlace del video', 'video', 'url', entry?.video, { maxLength: 2000, placeholder: 'https://', wide: true });
        const file = contentField('Adjuntar video (MP4, MOV o WebM; hasta 50 MB)', 'video_file', 'file', '', { accept: 'video/mp4,video/quicktime,video/webm', wide: true });
        const help = document.createElement('small');
        help.textContent = 'Puedes usar un enlace o adjuntar un archivo. La duración se detecta al seleccionar el video y también puede capturarse manualmente.';
        file.parentElement.append(help);
        if (entry?.video_path) {
            const existing = document.createElement('a');
            existing.href = chapterUrl(programId, module.id, entry.id); existing.target = '_blank'; existing.rel = 'noopener';
            existing.textContent = 'Ver archivo actual: ' + (entry.video_name || 'Video');
            file.parentElement.append(existing);
            contentField('Quitar archivo actual al guardar', 'remove_video', 'checkbox', false, { wide: true });
        }
        file.addEventListener('change', () => {
            clearVideoPreview();
            file.setCustomValidity('');
            const selected = file.files[0];
            if (!selected) return;
            if (selected.size > 50 * 1024 * 1024) { file.setCustomValidity('El video debe pesar como máximo 50 MB.'); file.reportValidity(); return; }
            const video = document.createElement('video');
            const url = pendingVideoUrl = URL.createObjectURL(selected);
            video.preload = 'metadata';
            video.onloadedmetadata = () => { if (pendingVideoUrl === url && Number.isFinite(video.duration)) { duration.value = Math.round(video.duration / 60 * 100) / 100; clearVideoPreview(); } };
            video.onerror = () => { if (pendingVideoUrl === url) { help.textContent = 'No se pudo detectar la duración. Captúrala manualmente antes de guardar.'; clearVideoPreview(); } };
            video.src = url;
        });
    } else {
        contentField('Nombre de la tarea', 'title', 'text', entry?.title, { required: true, maxLength: 150, wide: true });
        const modules = moduleDefaults.filter(m => m.project === programId);
        const select = contentField('Módulo', 'moduleId', 'select', '', { required: true });
        contentOptions(select, modules.map(m => [m.id, m.stage + ' - ' + m.title]), entry?.moduleId || module?.id);
        const chapters = contentField('Capítulo', 'chapterId', 'select', '', { required: !entry || !!entry.chapterId });
        const fillChapters = () => {
            chapters.replaceChildren();
            const selected = modules.find(m => m.id === select.value);
            const options = (selected?.chapter_definitions || []).map((c, i) => [c.id, 'Capítulo ' + (i + 1) + ' · ' + c.title]);
            options.unshift(['', entry && !entry.chapterId ? 'Actividad general del módulo (existente)' : 'Selecciona un capítulo']);
            contentOptions(chapters, options, contentEditing.chapterId);
        };
        fillChapters(); select.addEventListener('change', () => { contentEditing.chapterId = ''; fillChapters(); });
        if (chapter) { select.disabled = true; chapters.disabled = true; }
        const status = contentField('Estatus', 'status', 'select', '', { required: true });
        contentOptions(status, ['Activa', 'Borrador', 'Inactiva'].map(value => [value, value]), entry?.status || 'Borrador');
        contentField('Instrucciones y evidencia esperada', 'instructions', 'textarea', entry?.instructions, { required: true, rows: 5, maxLength: 5000, wide: true });
        contentField('Fecha límite (opcional)', 'due_date', 'date', entry?.due_date);
        contentField('Requiere adjuntar evidencia', 'requires_file', 'checkbox', entry?.requires_file ?? true);
        if (!modules.length) {
            const message = document.createElement('p'); message.className = 'is-wide'; message.textContent = 'Primero agrega un módulo para asociar esta tarea.';
            const create = document.createElement('button'); create.type = 'button'; create.className = 'training-secondary-button'; create.textContent = 'Agregar módulo';
            create.addEventListener('click', () => openContent('module', null, 'task'));
            contentFields.append(message, create); root.querySelector('[data-content-save]').disabled = true;
        }
    }
    if (!contentDialog.open) contentDialog.showModal();
    contentFields.querySelector('input, textarea, select')?.focus();
}

function updateContentActions(programId, count) {
    root.querySelectorAll('[data-add-module], [data-edit-module], [data-add-chapter], [data-new-task], [data-edit-task], [data-new-exam], [data-edit-exam]').forEach(button => { button.hidden = !canManagePrograms; button.disabled = !programId; });
    let empty = root.querySelector('[data-module-empty]');
    if (!empty && moduleEditor) { empty = document.createElement('p'); empty.dataset.moduleEmpty = ''; moduleEditor.parentElement.append(empty); }
    if (empty) { empty.hidden = count > 0; empty.textContent = programId ? 'Este programa aún no tiene módulos. Usa “Agregar módulo” para comenzar.' : 'Selecciona o crea un programa para comenzar.'; }
}
function renderModuleChapters(item) {
    const list = root.querySelector('[data-chapter-list]');
    if (!list) return;
    list.replaceChildren();
    const module = moduleDefaults.find(m => m.project === item.dataset.programId && m.id === item.dataset.moduleId);
    const chapters = module?.chapter_definitions || [];
    const canReorder = canManagePrograms && module?.can_reorder_chapters && chapters.length > 1;
    if (canReorder) {
        const help = createExamElement('p', 'training-chapter-order-help', 'Arrastra los capítulos para cambiar su orden. Se guarda automáticamente.');
        const status = createExamElement('span', 'training-chapter-order-status', chapterOrderMessages.get(module.project + '/' + module.id) || '');
        status.dataset.chapterOrderStatus = ''; status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        help.append(status); list.append(help);
    }
    if (!chapters.length) { const empty = document.createElement('p'); empty.textContent = 'Este módulo aún no tiene capítulos.'; list.append(empty); }
    chapters.forEach((chapter, index) => {
        const article = createExamElement('article', 'training-chapter-row'); article.dataset.trainingChapter = chapter.id;
        const body = createExamElement('div', 'training-chapter-body');
        const heading = createExamElement('div', 'training-chapter-row-heading');
        heading.append(createExamElement('strong', '', 'Capítulo ' + (index + 1) + ' · ' + chapter.title));
        if (canManagePrograms) {
            const edit = createExamElement('button', 'training-secondary-button', 'Editar capítulo'); edit.type = 'button'; edit.dataset.editChapter = chapter.id;
            const remove = createExamElement('button', 'training-icon-button', '×'); remove.type = 'button'; remove.dataset.deleteChapter = chapter.id; remove.setAttribute('aria-label', 'Eliminar capítulo ' + chapter.title);
            edit.addEventListener('click', () => openContent('chapter', chapter));
            remove.addEventListener('click', () => {
                if (chapterOrderBusy) return;
                chapterToDelete = { programId: item.dataset.programId, moduleId: module.id, id: chapter.id, title: chapter.title };
                showChapterDeleteStep(1);
            });
            heading.append(edit, remove);
        }
        body.append(heading, createExamElement('p', '', chapter.objective || 'Sin objetivo registrado.'), createExamElement('small', '', 'Duración: ' + chapter.duration));
        const addLink = (url, title) => { const a = document.createElement('a'); a.href = url; a.target = '_blank'; a.rel = 'noopener'; a.textContent = title; body.append(a); };
        if (/^https?:\/\//i.test(chapter.video || '')) addLink(chapter.video, 'Ver video');
        if (chapter.video_path) addLink(chapterUrl(item.dataset.programId, module.id, chapter.id), 'Ver archivo: ' + (chapter.video_name || 'Video'));
        article.append(body);
        if (canReorder) {
            const controls = createExamElement('div', 'training-chapter-order-controls');
            const handle = createExamElement('button', 'training-chapter-drag', '⠿'); handle.type = 'button';
            handle.dataset.chapterDrag = chapter.id; handle.draggable = !chapterOrderBusy;
            handle.setAttribute('aria-label', 'Reordenar capítulo ' + chapter.title);
            handle.title = 'Arrastra para reordenar. Con teclado, usa las flechas arriba y abajo.';
            handle.addEventListener('keydown', event => {
                if (!['ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault(); moveChapterBy(module, chapter.id, event.key === 'ArrowUp' ? -1 : 1);
            });
            controls.append(handle);
            [[-1, '↑', 'Subir'], [1, '↓', 'Bajar']].forEach(([delta, icon, label]) => {
                const button = createExamElement('button', 'training-chapter-move', icon); button.type = 'button';
                button.setAttribute('aria-label', label + ' capítulo ' + chapter.title);
                button.disabled = chapterOrderBusy || index + delta < 0 || index + delta >= chapters.length;
                button.addEventListener('click', () => moveChapterBy(module, chapter.id, delta)); controls.append(button);
            });
            body.prepend(controls);
            article.draggable = !chapterOrderBusy;
            article.addEventListener('pointerdown', event => {
                article.draggable = !chapterOrderBusy && !event.target.closest('button:not([data-chapter-drag]), a, input, select, textarea');
            });
        }
        if (canManagePrograms) article.append(chapterActivityMenu(module, chapter, 'exam'), chapterActivityMenu(module, chapter, 'task'));
        list.append(article);
    });
    list.setAttribute('aria-busy', String(chapterOrderBusy));
    if (chapterOrderBusy) list.querySelectorAll('button').forEach(button => { button.disabled = true; });
    installChapterDrag(list, canReorder ? module : null);
    const legacyExams = Object.values(examRecords).filter(e => e.projectId === module?.project && e.moduleId === module?.id && !e.chapterId);
    const legacyTasks = contentTasks.filter(t => t.projectId === module?.project && t.moduleId === module?.id && !t.chapterId);
    if (canManagePrograms && (legacyExams.length || legacyTasks.length)) {
        const legacy = createExamElement('section', 'training-chapter-legacy');
        legacy.append(createExamElement('h4', '', 'Actividades generales del módulo'), createExamElement('p', '', 'Estas actividades se crearon antes de organizar por capítulo. Puedes editar su capítulo de destino.'));
        legacy.append(chapterActivityMenu(module, null, 'exam', legacyExams), chapterActivityMenu(module, null, 'task', legacyTasks));
        list.append(legacy);
    }
}
function moveChapterBy(module, id, delta) {
    const order = module.chapter_definitions.map(chapter => chapter.id);
    const index = order.indexOf(id), target = index + delta;
    if (index < 0 || target < 0 || target >= order.length) return;
    order.splice(index, 1); order.splice(target, 0, id);
    saveChapterOrder(module, order, id);
}
async function saveChapterOrder(module, order, movedId) {
    if (chapterOrderBusy || contentBusy || !canManagePrograms || !module.can_reorder_chapters) return;
    const original = module.chapter_definitions;
    const expected = original.map(chapter => chapter.id);
    if (expected.every((id, index) => id === order[index])) return;
    const key = module.project + '/' + module.id;
    const render = () => {
        const selected = root.querySelector('[data-module-item].is-active');
        if (selected?.dataset.moduleId === module.id && selected.dataset.programId === module.project) renderModuleChapters(selected);
    };
    chapterOrderBusy = true;
    module.chapter_definitions = order.map(id => original.find(chapter => chapter.id === id));
    chapterOrderMessages.set(key, 'Guardando orden…'); render();
    try {
        await requestProgram('/' + encodeURIComponent(module.project) + '/modulos/' + encodeURIComponent(module.id) + '/capitulos/ordenar', 'PATCH',
            { chapter_ids: order, expected_order: expected });
        chapterOrderMessages.set(key, 'Orden de capítulos guardado.');
    } catch (error) {
        module.chapter_definitions = original;
        chapterOrderMessages.set(key, 'No se guardó el nuevo orden. ' + error.message);
    } finally {
        chapterOrderBusy = false; refreshChapterActivities();
        const selected = selectedContentModule();
        if (selected === module) {
            const handle = [...root.querySelectorAll('[data-chapter-drag]')].find(button => button.dataset.chapterDrag === movedId);
            handle?.focus({ preventScroll: true });
        }
    }
}
function installChapterDrag(list, module) {
    let dragged = null;
    const clearMarkers = () => list.querySelectorAll('.is-drop-before, .is-drop-after').forEach(row => row.classList.remove('is-drop-before', 'is-drop-after'));
    const reset = () => {
        clearMarkers(); list.querySelectorAll('.is-dragging').forEach(row => row.classList.remove('is-dragging')); dragged = null;
    };
    list.ondragstart = event => {
        const row = event.target.closest('[data-training-chapter]');
        if (!module || chapterOrderBusy || contentBusy || !row) { event.preventDefault(); return; }
        dragged = row.dataset.trainingChapter;
        event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', dragged);
        event.dataTransfer.setDragImage(row, 25, 25); row.classList.add('is-dragging');
    };
    list.ondragover = event => {
        const row = event.target.closest('[data-training-chapter]');
        clearMarkers();
        if (!dragged || !row || row.dataset.trainingChapter === dragged) return;
        event.preventDefault(); event.dataTransfer.dropEffect = 'move';
        const box = row.getBoundingClientRect();
        row.classList.add(event.clientY < box.top + box.height / 2 ? 'is-drop-before' : 'is-drop-after');
    };
    list.ondrop = event => {
        const row = event.target.closest('[data-training-chapter]');
        if (!dragged || !row) return;
        event.preventDefault();
        const id = dragged, target = row.dataset.trainingChapter;
        const after = event.clientY >= row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2;
        reset();
        if (id === target) return;
        const order = module.chapter_definitions.map(chapter => chapter.id).filter(chapterId => chapterId !== id);
        order.splice(order.indexOf(target) + (after ? 1 : 0), 0, id);
        saveChapterOrder(module, order, id);
    };
    list.ondragend = reset;
    list.ondragleave = event => { if (!list.contains(event.relatedTarget)) clearMarkers(); };
}
function refreshChapterActivities() {
    const item = root.querySelector('[data-module-item].is-active');
    if (item) renderModuleChapters(item);
}
function chapterActivityMenu(module, chapter, kind, legacy = null) {
    const isExam = kind === 'exam';
    const panel = createExamElement('section', 'training-chapter-activities');
    panel.dataset.chapterActivities = kind;
    const title = createExamElement('h4');
    title.append(createExamElement('i', isExam ? 'fa-regular fa-clipboard' : 'fa-regular fa-file-lines'), document.createTextNode(isExam ? 'Exámenes' : 'Tareas'));
    panel.append(title);
    if (chapter) {
        const create = createExamElement('button', 'training-secondary-button', isExam ? '+ Crear examen' : '+ Crear tarea');
        create.type = 'button'; create.dataset[isExam ? 'createChapterExam' : 'createChapterTask'] = chapter.id;
        create.addEventListener('click', () => isExam ? openNewExamBuilder({ moduleId: module.id, chapterId: chapter.id }) : openContent('task', null, null, chapter));
        panel.append(create);
    }
    const entries = legacy || (isExam ? Object.values(examRecords) : contentTasks).filter(a => a.projectId === module.project && a.moduleId === module.id && a.chapterId === chapter.id);
    entries.forEach(entry => {
        const row = createExamElement('div', 'training-chapter-activity');
        row.dataset.chapterActivityId = entry.id;
        const details = createExamElement('span'); details.append(createExamElement('strong', '', isExam ? entry.name : entry.title), createExamElement('small', '', entry.status));
        const edit = createExamElement('button', 'training-secondary-button', isExam ? 'Editar examen' : 'Editar tarea'); edit.type = 'button';
        edit.dataset[isExam ? 'editChapterExam' : 'editChapterTask'] = entry.id;
        edit.addEventListener('click', () => isExam ? openExamEditor(entry.id) : openContent('task', entry, null, chapter));
        row.append(details, edit); panel.append(row);
    });
    if (!entries.length) panel.append(createExamElement('small', '', isExam ? 'Sin exámenes' : 'Sin tareas'));
    return panel;
}
function examChapterOptions(form, module, selected, allowLegacy = false) {
    let select = form.elements.chapterId;
    if (!select) {
        const label = createExamElement('label'); label.append(createExamElement('span', '', 'Capítulo'));
        select = document.createElement('select'); select.name = 'chapterId'; label.append(select);
        form.elements.module.closest('label').after(label);
    }
    select.replaceChildren(); select.required = !allowLegacy;
    select.add(new Option(allowLegacy ? 'Actividad general del módulo (existente)' : 'Selecciona un capítulo', ''));
    (module?.chapter_definitions || []).forEach((chapter, index) => select.add(new Option('Capítulo ' + (index + 1) + ' · ' + chapter.title, chapter.id)));
    select.value = selected || '';
}
function reloadContent(message, moduleId, resume) {
    try {
        sessionStorage.setItem('mezclaspro.training.content-feedback', message);
        if (moduleId) localStorage.setItem('mezclaspro.training.module.' + programSelector.value, moduleId);
        if (resume) sessionStorage.setItem('mezclaspro.training.content-resume', resume);
    } catch (_) {}
    location.reload();
}
function setContentBusy(busy) {
    contentBusy = busy;
    [contentDialog, contentConfirm].forEach(dialog => dialog?.querySelectorAll('button').forEach(button => { button.disabled = busy; }));
    const save = root.querySelector('[data-content-save]'); if (save) save.textContent = busy ? 'Guardando…' : 'Guardar cambios';
}
root.querySelector('[data-add-module]')?.addEventListener('click', () => openContent('module'));
root.querySelector('[data-edit-module]')?.addEventListener('click', () => openContent('module', selectedContentModule()));
root.querySelector('[data-add-chapter]')?.addEventListener('click', () => openContent('chapter'));
root.querySelector('[data-new-task]')?.addEventListener('click', () => openContent('task'));
root.querySelectorAll('[data-edit-task]').forEach(button => button.addEventListener('click', () => openContent('task', contentTasks.find(task => task.projectId === programSelector.value && task.id === button.dataset.editTask))));
root.querySelectorAll('[data-close-content]').forEach(button => button.addEventListener('click', closeContentDialog));
contentDialog?.addEventListener('cancel', event => { event.preventDefault(); closeContentDialog(); });
contentDialog?.addEventListener('close', clearVideoPreview);
contentForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!contentEditing || contentBusy || !contentForm.reportValidity()) return;
    const current = { ...contentEditing };
    const data = new FormData(contentForm);
    let path = '/' + encodeURIComponent(current.programId);
    let method = current.id ? 'PATCH' : 'POST';
    if (current.kind === 'module') path += '/modulos' + (current.id ? '/' + encodeURIComponent(current.id) : '');
    if (current.kind === 'task') {
        path += '/tareas' + (current.id ? '/' + encodeURIComponent(current.id) : '');
        data.set('requires_file', contentForm.elements.requires_file.checked ? '1' : '0');
        data.set('moduleId', contentForm.elements.moduleId.value);
        data.set('chapterId', contentForm.elements.chapterId.value);
    }
    if (current.kind === 'chapter') {
        path += '/modulos/' + encodeURIComponent(current.moduleId) + '/capitulos' + (current.id ? '/' + encodeURIComponent(current.id) : '');
        method = 'POST'; data.set('duration_seconds', Math.round(Number(data.get('duration_minutes')) * 60));
        data.set('remove_video', contentForm.elements.remove_video?.checked ? '1' : '0');
        if (!contentForm.elements.video_file.files.length) data.delete('video_file');
    }
    setContentBusy(true); contentError.hidden = true;
    try {
        await catalogReady;
        let saved;
        if (current.kind === 'chapter') {
            const response = await fetch(programCatalogUrl + path, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: data });
            saved = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(saved.errors || {}).flat()[0] || saved.message || 'No se pudo guardar el capítulo. Revisa el tamaño del video e inténtalo nuevamente.');
        } else saved = await requestProgram(path, method, Object.fromEntries(data.entries()));
        reloadContent(({ module: 'Módulo guardado.', chapter: 'Capítulo guardado.', task: 'Tarea guardada.' })[current.kind], current.kind === 'module' ? saved.id : current.moduleId, current.resume);
    } catch (error) { contentError.textContent = error.message; contentError.hidden = false; }
    finally { setContentBusy(false); }
});
function showChapterDeleteStep(step) {
    chapterDeleteStep = step;
    root.querySelector('#training-content-confirm-title').textContent = step === 1 ? 'Eliminar capítulo' : 'Confirmar eliminación';
    root.querySelector('[data-content-confirm-message]').textContent = step === 1
        ? '¿Estás seguro que quieres eliminar el capítulo “' + chapterToDelete.title + '”?'
        : 'Al eliminar el capítulo no podrás recuperar la información. ¿Estás seguro que quieres eliminarlo?';
    root.querySelector('[data-content-delete-error]').hidden = true;
    if (!contentConfirm.open) contentConfirm.showModal();
    contentConfirm.querySelector('footer [data-cancel-content-delete]').focus();
}
function cancelChapterDelete() {
    if (contentBusy) return;
    chapterToDelete = null; chapterDeleteStep = 0;
    contentConfirm.close();
}
root.querySelectorAll('[data-cancel-content-delete]').forEach(button => button.addEventListener('click', cancelChapterDelete));
contentConfirm?.addEventListener('cancel', event => { event.preventDefault(); cancelChapterDelete(); });
root.querySelector('[data-content-confirm-form]')?.addEventListener('submit', async event => {
    event.preventDefault(); if (!chapterToDelete || contentBusy) return;
    if (chapterDeleteStep === 1) { showChapterDeleteStep(2); return; }
    if (chapterDeleteStep !== 2) return;
    root.querySelector('[data-content-delete-error]').hidden = true;
    setContentBusy(true);
    try {
        const { programId, moduleId, id } = chapterToDelete;
        await requestProgram('/' + encodeURIComponent(programId) + '/modulos/' + encodeURIComponent(moduleId) + '/capitulos/' + encodeURIComponent(id), 'DELETE', { confirmed: true });
        reloadContent('Capítulo eliminado.', moduleId);
    } catch (error) { const message = root.querySelector('[data-content-delete-error]'); message.textContent = error.message; message.hidden = false; }
    finally { setContentBusy(false); }
});

async function saveExamToServer(exam, form) {
    if (!canManagePrograms || form.dataset.saving === 'true') return false;
    let error = form.querySelector('[data-exam-persistence-error]');
    if (!error) { error = document.createElement('p'); error.dataset.examPersistenceError = ''; error.setAttribute('role', 'alert'); form.append(error); }
    error.hidden = true; form.dataset.saving = 'true';
    const controls = [...form.querySelectorAll('button'), ...root.querySelectorAll('[data-program-carousel-item], [data-edit-program], [data-program-tab]')];
    const previous = controls.map(button => button.disabled); controls.forEach(button => { button.disabled = true; });
    try {
        await catalogReady; await legacyExamsReady;
        const saved = await requestProgram('/' + encodeURIComponent(exam.projectId) + '/examenes/' + encodeURIComponent(exam.id), 'PUT', normaliseExam(exam));
        Object.assign(exam, saved);
        return true;
    } catch (reason) { error.textContent = reason.message; error.hidden = false; error.scrollIntoView({ block: 'nearest' }); return false; }
    finally { form.dataset.saving = 'false'; controls.forEach((button, index) => { button.disabled = previous[index]; }); }
}

// Import previous browser edits once. The server rejects stale overwrites after an exam is saved.
const legacyExamsReady = (async () => {
    if (!canManagePrograms) return;
    let old;
    try { old = JSON.parse(localStorage.getItem(examStorageKey) || '{}'); } catch (_) { return; }
    if (!old || typeof old !== 'object' || !Object.keys(old).length) return;
    try {
        await catalogReady;
        for (const programId of availableProgramIds) {
            const exams = Object.values(old).filter(exam => exam.projectId === programId).map(normaliseExam);
            if (!exams.length) continue;
            const result = await requestProgram('/' + encodeURIComponent(programId) + '/examenes/importar', 'POST', { exams });
            result.exams.forEach(exam => { examRecords[exam.id] = normaliseExam(exam); updateExamTableRow(examRecords[exam.id]); });
        }
        localStorage.removeItem(examStorageKey);
        filterProgramRows('exams', programSelector?.value || '');
    } catch (error) { contentNotice('No se pudieron recuperar algunos exámenes guardados en este navegador: ' + error.message); }
})();
Promise.resolve().then(() => {
    try {
        const message = sessionStorage.getItem('mezclaspro.training.content-feedback');
        if (message) { contentNotice(message); sessionStorage.removeItem('mezclaspro.training.content-feedback'); }
        const resume = sessionStorage.getItem('mezclaspro.training.content-resume');
        sessionStorage.removeItem('mezclaspro.training.content-resume');
        if (resume === 'task') { activateProgramTab('tasks'); openContent('task'); }
        if (resume === 'exam') { activateProgramTab('exams'); openNewExamBuilder(); }
    } catch (_) {}
    if (!canManagePrograms) root.querySelectorAll('[data-add-module], [data-edit-module], [data-add-chapter], [data-new-task], [data-edit-task], [data-new-exam], [data-edit-exam]').forEach(button => { button.hidden = true; });
});
