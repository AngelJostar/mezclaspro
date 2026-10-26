(() => {
    const section = root.querySelector('[data-own-assignments]');
    if (!section) return;
    const home = section.closest('[data-learner-panel]');
    const role = section.closest('[data-role-panel]');
    const list = section.querySelector('[data-own-assignment-list]');
    const status = section.querySelector('[data-own-assignment-status]');
    const refresh = section.querySelector('[data-refresh-assignments]');
    const dialog = root.querySelector('[data-assigned-reader]');
    const moduleSelect = dialog.querySelector('[data-assigned-module]');
    const chapters = dialog.querySelector('[data-assigned-chapters]');
    const canManage = @json($canManagePrograms);
    let assignments = [], active = null, loading = false, starting = false;
    const visible = () => !role.hidden && !home.hidden && !document.hidden;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const stopVideo = () => dialog.querySelectorAll('video').forEach(video => video.pause());
    const updateFlow = () => {
        if (!home.hidden && (!canManage || assignments.length)) root.querySelector('.training-obligation-flow').hidden = true;
    };
    const close = () => { stopVideo(); dialog.close(); active = null; };
    dialog.querySelector('[data-close-assigned-reader]').addEventListener('click', close);
    dialog.addEventListener('close', stopVideo);

    async function request(url, method = 'GET') {
        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 20000);
        try {
            const response = await fetch(url, { method, credentials: 'same-origin', signal: abort.signal,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()) } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo cargar la capacitación.');
            return data;
        } finally { clearTimeout(timeout); }
    }

    function renderModule() {
        stopVideo();
        chapters.replaceChildren();
        const module = active?.modules.find(item => item.id === moduleSelect.value);
        dialog.querySelector('[data-assigned-description]').textContent = module?.description || '';
        if (!module) return;
        if (!module.chapters.length) chapters.append(element('p', 'Este módulo aún no tiene capítulos publicados.'));
        module.chapters.forEach(chapter => {
            const card = element('article');
            card.append(element('h4', chapter.title), element('p', chapter.objective), element('small', chapter.duration));
            if (chapter.file_url) {
                const video = element('video'); video.controls = true; video.preload = 'metadata'; video.src = chapter.file_url;
                video.setAttribute('aria-label', chapter.title); card.append(video);
            } else if (chapter.video_url) {
                const link = element('a', 'Abrir video de capacitación'); link.href = chapter.video_url; link.target = '_blank'; link.rel = 'noopener noreferrer'; card.append(link);
            }
            chapters.append(card);
        });
    }
    moduleSelect.addEventListener('change', renderModule);
    function openReader(assignment) {
        active = assignment;
        dialog.querySelector('[data-assigned-reader-title]').textContent = assignment.title;
        moduleSelect.replaceChildren(...assignment.modules.map(module => {
            const option = element('option', module.stage + ' · ' + module.title); option.value = module.id; return option;
        }));
        renderModule();
        if (!dialog.open) dialog.showModal();
    }

    function render() {
        list.replaceChildren();
        // The signed-in employee's home never uses another learner's browser preview.
        Array.from(home.children).filter(child => child !== section && child !== dialog).forEach(child => {
            child.hidden = !canManage || assignments.length > 0;
        });
        const current = assignments.filter(item => item.status !== 'completed');
        updateFlow();
        status.textContent = current.length ? current.length + (current.length === 1 ? ' capacitación pendiente o en curso.' : ' capacitaciones pendientes o en curso.') : 'No tienes capacitaciones pendientes asignadas.';
        current.forEach(assignment => {
            const card = element('article', '', 'training-own-assignment-card');
            const details = element('div');
            details.append(element('h3', assignment.title), element('p', assignment.description),
                element('small', (assignment.status === 'assigned' ? 'Por iniciar' : 'En curso') + ' · ' + assignment.modules.length + (assignment.modules.length === 1 ? ' módulo' : ' módulos') + ' · Fecha de inicio: ' + assignment.start_date.split('-').reverse().join('/')));
            const button = element('button', assignment.started_at ? 'Continuar capacitación' : 'Iniciar capacitación', 'training-primary-button');
            button.type = 'button'; button.dataset.startAssignment = assignment.id;
            button.disabled = !assignment.modules.length;
            if (!assignment.modules.length) details.append(element('p', 'El administrador debe actualizar los módulos de esta capacitación.'));
            button.addEventListener('click', async () => {
                if (starting) return;
                starting = true; button.disabled = true; status.textContent = 'Abriendo capacitación…';
                try {
                    const data = await request(assignment.start_url, 'POST');
                    assignments = assignments.map(item => item.id === data.assignment.id ? data.assignment : item);
                    render(); openReader(data.assignment);
                } catch (error) {
                    status.textContent = error.name === 'AbortError' ? 'No se recibió respuesta. Intenta nuevamente.' : error.message;
                    button.disabled = false;
                } finally { starting = false; }
            });
            card.append(details, button); list.append(card);
        });
    }

    async function load() {
        if (loading || starting) return;
        loading = true; refresh.disabled = true;
        try {
            const data = await request(@json(route('admin.capacitaciones.asignaciones.index')));
            assignments = data.assignments; render();
            if (dialog.open) {
                const updated = assignments.find(item => item.id === active?.id && item.status !== 'completed');
                if (!updated) { close(); status.textContent = 'La capacitación fue concluida o retirada por el administrador.'; }
            }
        } catch (error) {
            list.replaceChildren();
            if (dialog.open) close();
            status.textContent = 'No se pudieron cargar tus capacitaciones. Pulsa Actualizar para intentar nuevamente.';
        } finally { loading = false; refresh.disabled = false; }
    }
    // Do not let the preview curriculum obscure assignments when no continuous access has been granted.
    if (!canManage) Array.from(home.children).filter(child => child !== section && child !== dialog).forEach(child => { child.hidden = true; });
    refresh.addEventListener('click', load);
    root.addEventListener('training-role-change', () => { updateFlow(); if (visible()) load(); });
    root.addEventListener('training-learner-tab', () => { updateFlow(); if (visible()) load(); });
    window.addEventListener('focus', () => { if (visible()) load(); });
    document.addEventListener('visibilitychange', () => { if (visible()) load(); });
    setInterval(() => { if (visible()) load(); }, 30000);
    if (visible()) load();
})();
