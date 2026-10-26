function renderConcludeTrainingButtons(row) {
    const actions = row?.querySelector('[data-conclude-training-actions]');
    if (!actions) return;
    const error = actions.querySelector('[data-conclude-error]');
    actions.querySelectorAll('[data-conclude-training]').forEach(button => button.remove());
    row.querySelectorAll('[data-current-program-id]').forEach(program => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'training-add-training-button';
        button.dataset.concludeTraining = '';
        button.dataset.programId = program.dataset.currentProgramId;
        button.dataset.startDate = program.dataset.programStartDate;
        button.textContent = 'Concluir';
        button.title = program.dataset.programName;
        button.setAttribute('aria-label', 'Concluir ' + program.dataset.programName + ' para ' + row.dataset.studentName);
        actions.insertBefore(button, error);
    });
}

@if ($showStudentConclusion)
root.addEventListener('click', async event => {
    const button = event.target.closest('[data-conclude-training]');
    if (!button || button.disabled) return;
    const row = button.closest('[data-student-row]');
    const error = row.querySelector('[data-conclude-error]');
    const actions = Array.from(row.querySelectorAll('[data-conclude-training]'));
    const parts = String(button.dataset.startDate || '').split('/');
    const startDate = parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : button.dataset.startDate;
    error.hidden = true;
    actions.forEach(action => { action.disabled = true; });
    button.textContent = 'Concluyendo…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
        const response = await fetch(@json(route('admin.capacitaciones.alumnos.conclude')), {
            method: 'POST', credentials: 'same-origin', signal: controller.signal,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ student_key: row.dataset.studentKey, program_id: button.dataset.programId, start_date: startDate,
                browser_assignments: (row.dataset.studentId ? [] : (readStoredAssignments()[row.dataset.studentName] || [])).map(assignment => ({
                    programId: assignment.programId, startDate: assignment.startDate, progress: Math.min(100, Math.max(0, Number(assignment.progress) || 0)),
                })),
            }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'No se pudo concluir la capacitación. Intenta nuevamente.');
        // Remove only this course/date from the legacy cache; the server remains authoritative after reload.
        const assignments = readStoredAssignments();
        assignments[row.dataset.studentName] = (assignments[row.dataset.studentName] || []).filter(assignment =>
            assignment.programId !== button.dataset.programId || assignment.startDate !== startDate);
        persistAssignments(assignments);
        window.location.assign(data.redirect);
    } catch (failure) {
        error.textContent = failure.name === 'AbortError'
            ? 'No se recibió la respuesta. Puedes volver a intentar sin duplicar la conclusión.'
            : failure.message;
        error.hidden = false;
        button.textContent = 'Concluir';
        actions.forEach(action => { action.disabled = false; });
    } finally { clearTimeout(timeout); }
});
@endif
