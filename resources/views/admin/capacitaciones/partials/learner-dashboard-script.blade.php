// All dashboard totals use the same authorized, persisted assignments as Mi capacitación.
function renderDashboard() {
    const completed = assignments.filter(assigned => assigned.status === 'completed');
    const active = assignments.filter(assigned => assigned.status !== 'completed');
    const started = active.filter(assigned => assigned.started_at).length;
    const tasks = [], exams = [], results = [], latestScores = [];
    assignments.forEach(assigned => assigned.modules.forEach(module => {
        if (assigned.status !== 'completed') {
            module.tasks.forEach(task => { if (task.submission?.status !== 'approved') tasks.push({ assigned, module, task }); });
            module.exams.forEach(exam => { if (!exam.passed) exams.push({ assigned, module, exam }); });
        }
        module.exams.forEach(exam => {
            const submitted = exam.results.filter(result => result.submitted_at)
                .sort((a, b) => Date.parse(b.submitted_at) - Date.parse(a.submitted_at) || b.id - a.id);
            if (submitted.length) latestScores.push(Number(submitted[0].score));
            submitted.forEach(result => results.push({ assigned, module, exam, result }));
        });
    }));
    const waitingReview = tasks.filter(({ task }) => task.submission?.status === 'submitted').length;
    const availableExams = exams.filter(({ assigned, exam }) => assigned.status === 'in_progress' && exam.available &&
        (exam.results.length < (exam.attempts || 1) || exam.results.some(result => !result.submitted_at && Date.parse(result.expires_at) > Date.now()))).length;
    const average = latestScores.length ? Math.round(latestScores.reduce((sum, score) => sum + score, 0) / latestScores.length) + '%' : '—';
    const icons = {
        training: ['M4 4h6a2 2 0 0 1 2 2v15a3 3 0 0 0-3-2H4z', 'M20 4h-6a2 2 0 0 0-2 2v15a3 3 0 0 1 3-2h5z'],
        tasks: ['M9 4H5v17h14V4h-4', 'M9 2h6v4H9z', 'm8 13 3 3 5-6'],
        exams: ['M14 2H5v20h14V7z', 'M14 2v6h5', 'M8 12h8M8 16h5'],
        results: ['M4 3v17h17', 'M8 16v-4M13 16V8M18 16V5'],
        certificates: ['M8 14 6 22l6-3 6 3-2-8', 'M12 2a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z'],
    };
    const metrics = dashboard.querySelector('[data-dashboard-metrics]'); metrics.replaceChildren();
    const metric = (key, title, value, detail, tab) => {
        const card = button('', () => activateLearnerTab(tab, true)); card.className = 'learner-dashboard-metric'; card.dataset.dashboardMetric = key;
        const label = element('span', '', 'learner-dashboard-metric-label'), icon = element('span', '', 'learner-dashboard-icon');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.7'); svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round'); svg.setAttribute('aria-hidden', 'true');
        icons[key].forEach(d => { const path = document.createElementNS(svg.namespaceURI, 'path'); path.setAttribute('d', d); svg.append(path); });
        icon.append(svg); label.append(icon, document.createTextNode(title));
        const total = element('strong', value); total.dataset.dashboardValue = key;
        card.append(label, total, element('small', detail)); metrics.append(card);
    };
    metric('training', 'Capacitaciones', assignments.length, started + ' en curso · ' + (active.length - started) + ' por iniciar · ' + completed.length + ' concluidas', 'training');
    metric('tasks', 'Tareas pendientes', tasks.length, (tasks.length - waitingReview) + ' por entregar · ' + waitingReview + ' en revisión', 'tasks');
    metric('exams', 'Exámenes pendientes', exams.length, availableExams + ' disponibles para realizar', 'assigned-exams');
    metric('results', 'Resultados', average, latestScores.length ? 'Promedio del último intento entregado de cada examen' : 'Aún no hay exámenes entregados', 'results');
    metric('certificates', 'Certificaciones', completed.length, 'Capacitaciones concluidas · consulta la emisión', 'certificates');
    dashboard.querySelector('[data-dashboard-training-summary]').textContent = !assignments.length
        ? 'Cuando tu administrador te asigne una capacitación, podrás consultar aquí tu resumen.'
        : active.length ? 'Tienes ' + active.length + (active.length === 1 ? ' capacitación pendiente. ' : ' capacitaciones pendientes. ') + 'Revisa el temario y continúa desde tu capítulo actual en Mi capacitación.'
            : 'Tus capacitaciones están concluidas. Puedes consultar el temario y el detalle de tu avance en Mi capacitación.';
    const containers = {};
    ['tasks', 'exams', 'results', 'certificates'].forEach(key => {
        containers[key] = dashboard.querySelector('[data-dashboard-' + key + ']'); containers[key].replaceChildren();
    });
    const row = (key, title, context, label, state = '') => {
        const item = element('div', '', 'learner-dashboard-row'), text = element('div');
        text.append(element('strong', title), element('small', context)); item.append(text, badge(label, state)); containers[key].append(item);
    };
    tasks.sort((a, b) => (a.task.due_date || '9999').localeCompare(b.task.due_date || '9999'));
    tasks.slice(0, 3).forEach(({ assigned, task }) => row('tasks', task.title,
        assigned.title + (task.due_date ? ' · Fecha límite: ' + task.due_date.split('-').reverse().join('/') : ''),
        task.submission?.status === 'submitted' ? 'En revisión' : task.submission?.status === 'returned' ? 'Corregir entrega' : 'Por entregar'));
    exams.slice(0, 3).forEach(({ assigned, exam }) => {
        const live = exam.results.some(result => !result.submitted_at && Date.parse(result.expires_at) > Date.now());
        const exhausted = !live && exam.results.length >= (exam.attempts || 1);
        row('exams', exam.name, assigned.title, live ? 'En curso' : exhausted ? 'Sin intentos' : assigned.status === 'in_progress' && exam.available ? 'Disponible' : 'Completa lo previo');
    });
    results.sort((a, b) => Date.parse(b.result.submitted_at) - Date.parse(a.result.submitted_at) || b.result.id - a.result.id);
    results.slice(0, 3).forEach(({ assigned, exam, result }) => row('results', exam.name,
        assigned.title + ' · ' + new Date(result.submitted_at).toLocaleDateString('es-MX'), result.score + '% · ' + (result.passed ? 'Aprobado' : 'No aprobado'), result.passed ? 'completed' : ''));
    completed.slice(0, 3).forEach(assigned => row('certificates', assigned.title, 'Consulta la emisión con tu administrador', 'Concluida', 'completed'));
    const empty = { tasks: 'No tienes tareas pendientes.', exams: 'No tienes exámenes pendientes.', results: 'Tus calificaciones aparecerán cuando entregues un examen.', certificates: 'Aún no tienes capacitaciones concluidas.' };
    Object.entries(containers).forEach(([key, node]) => { if (!node.childElementCount) node.append(element('p', empty[key], 'learner-dashboard-empty')); });
    dashboardMessage.textContent = assignments.length ? '' : 'No tienes capacitaciones asignadas.';
    dashboardContent.hidden = false;
}
