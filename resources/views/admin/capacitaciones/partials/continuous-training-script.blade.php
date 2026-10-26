// This reader deliberately has no connection to learnerState or completion actions.
(() => {
    const library = root.querySelector('[data-continuous-library]');
    if (!library) return;
    const panel = library.closest('[data-learner-panel]');
    const programSelect = library.querySelector('[data-continuous-program]');
    const content = library.querySelector('[data-continuous-content]');
    const empty = library.querySelector('[data-continuous-empty]');
    const status = library.querySelector('[data-continuous-status]');
    const refresh = library.querySelector('[data-continuous-refresh]');
    const moduleList = library.querySelector('[data-continuous-modules]');
    const reader = library.querySelector('[data-continuous-reader]');
    let programs = [], selectedModule = '', signature = null, pending = false, refreshAgain = false;
    const visible = () => !panel.hidden && !panel.closest('[data-role-panel]').hidden && !document.hidden;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const pauseVideos = () => library.querySelectorAll('video').forEach(video => video.pause());

    function renderModule(program, moduleId) {
        pauseVideos();
        const module = program.modules.find(item => item.id === moduleId) || program.modules[0];
        selectedModule = module?.id || '';
        moduleList.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.moduleId === selectedModule)));
        reader.replaceChildren();
        if (!module) {
            reader.append(element('p', 'Este programa aún no tiene módulos disponibles.'));
            return;
        }
        reader.append(element('small', module.stage), element('h3', module.title));
        if (module.description) reader.append(element('p', module.description));
        if (!module.chapters.length) reader.append(element('p', 'Este módulo aún no tiene capítulos disponibles.'));
        module.chapters.forEach((chapter, index) => {
            const details = element('details', '', 'continuous-training-chapter');
            details.dataset.continuousChapter = chapter.id;
            const heading = element('summary', (index + 1) + '. ' + chapter.title);
            if (chapter.duration) heading.append(element('small', chapter.duration));
            const body = element('div', '', 'continuous-training-chapter-body');
            if (chapter.objective) body.append(element('p', chapter.objective));
            if (chapter.file_url) {
                const video = element('video');
                video.controls = true;
                video.preload = 'none';
                video.playsInline = true;
                video.src = chapter.file_url;
                video.setAttribute('aria-label', chapter.title);
                const error = element('p', 'No se pudo cargar el video. Actualiza el catálogo e intenta nuevamente.');
                error.hidden = true;
                error.setAttribute('role', 'status');
                video.addEventListener('error', () => { error.hidden = false; });
                body.append(video, error);
            }
            if (chapter.video_url) {
                const link = element('a', 'Ver video en otra pestaña ↗');
                link.href = chapter.video_url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                body.append(link);
            }
            if (!chapter.file_url && !chapter.video_url) body.append(element('p', 'Este capítulo no tiene un video adjunto.'));
            details.append(heading, body);
            details.addEventListener('toggle', () => { if (!details.open) body.querySelectorAll('video').forEach(video => video.pause()); });
            reader.append(details);
        });
    }

    function renderProgram(keepModule = false) {
        const program = programs.find(item => item.id === programSelect.value);
        moduleList.replaceChildren();
        if (!program) return;
        library.querySelector('[data-continuous-description]').textContent = program.description || '';
        program.modules.forEach(module => {
            const button = element('button');
            button.type = 'button';
            button.dataset.moduleId = module.id;
            button.setAttribute('aria-controls', 'continuous-module-reader');
            button.append(element('strong', module.stage + ' · ' + module.title), element('small', module.chapters.length + (module.chapters.length === 1 ? ' capítulo' : ' capítulos')));
            button.addEventListener('click', () => renderModule(program, module.id));
            moduleList.append(button);
        });
        reader.id = 'continuous-module-reader';
        renderModule(program, keepModule ? selectedModule : '');
    }

    async function loadCatalog() {
        if (!visible()) return;
        if (pending) { refreshAgain = true; return; }
        pending = true;
        refresh.disabled = true;
        library.setAttribute('aria-busy', 'true');
        status.classList.remove('is-error');
        if (signature === null) status.textContent = 'Cargando tus programas habilitados…';
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(library.dataset.catalogUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
            });
            if (!response.ok) throw new Error('catalog');
            const data = await response.json();
            if (!Array.isArray(data.programs)) throw new Error('catalog');
            const nextSignature = JSON.stringify(data.programs);
            if (signature !== nextSignature) {
                const previousProgram = programSelect.value;
                pauseVideos();
                programs = data.programs;
                signature = nextSignature;
                programSelect.replaceChildren(...programs.map(program => new Option(program.title, program.id)));
                if (programs.some(program => program.id === previousProgram)) programSelect.value = previousProgram;
                content.hidden = programs.length === 0;
                empty.hidden = programs.length !== 0;
                if (programs.length) renderProgram(programSelect.value === previousProgram);
                else { moduleList.replaceChildren(); reader.replaceChildren(); }
            }
            status.textContent = programs.length ? programs.length + (programs.length === 1 ? ' programa disponible para consultar.' : ' programas disponibles para consultar.') : '';
        } catch (_) {
            // Hide stale content until access can be checked again. Keep the selection for retry.
            pauseVideos();
            content.hidden = true;
            empty.hidden = true;
            signature = null;
            status.classList.add('is-error');
            status.textContent = 'No se pudieron actualizar tus programas. Pulsa Actualizar para volver a intentar.';
        } finally {
            clearTimeout(timeout);
            pending = false;
            refresh.disabled = false;
            library.setAttribute('aria-busy', 'false');
            if (refreshAgain) { refreshAgain = false; loadCatalog(); }
        }
    }

    programSelect.addEventListener('change', () => renderProgram());
    refresh.addEventListener('click', loadCatalog);
    root.addEventListener('training-learner-tab', () => { if (visible()) loadCatalog(); else pauseVideos(); });
    root.addEventListener('training-role-change', () => { if (visible()) loadCatalog(); else pauseVideos(); });
    window.addEventListener('focus', loadCatalog);
    document.addEventListener('visibilitychange', () => { if (visible()) loadCatalog(); else pauseVideos(); });
    window.addEventListener('storage', event => { if (event.key === 'mezclaspro.training.catalog-revision') loadCatalog(); });
    window.setInterval(loadCatalog, 30000);
})();
