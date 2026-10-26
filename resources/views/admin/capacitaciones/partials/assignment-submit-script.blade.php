trainingAssignmentModal?.addEventListener('cancel', event => {
    if (trainingAssignmentForm?.dataset.saving === 'true') event.preventDefault();
});
trainingAssignmentForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (trainingAssignmentForm.dataset.saving === 'true') return;
    const error = trainingAssignmentForm.querySelector('[data-assignment-error]');
    const selected = collectAssignments();
    const userId = Number(assignmentTargetRow?.dataset.studentId);
    error.hidden = true;
    if (!userId || !selected.length) {
        error.textContent = 'Selecciona un alumno registrado y al menos un módulo.';
        error.hidden = false;
        return;
    }
    const controls = Array.from(trainingAssignmentForm.querySelectorAll('button, input, select'));
    const previousDisabled = controls.map(control => control.disabled);
    trainingAssignmentForm.dataset.saving = 'true';
    controls.forEach(control => { control.disabled = true; });
    const abort = new AbortController();
    const timeout = setTimeout(() => abort.abort(), 20000);
    try {
        const response = await fetch(@json(route('admin.capacitaciones.alumnos.assign')), {
            method: 'POST', credentials: 'same-origin', signal: abort.signal,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()) },
            body: JSON.stringify({ user_id: userId, start_date: assignmentStartDate.value,
                programs: selected.map(item => ({ program_id: item.programId, module_ids: item.moduleIds })) }),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo guardar la capacitación.');
        window.location.assign(result.redirect);
    } catch (exception) {
        error.textContent = exception.name === 'AbortError' ? 'No se recibió confirmación. Puedes intentar guardar nuevamente.' : exception.message;
        error.hidden = false;
    } finally {
        clearTimeout(timeout);
        trainingAssignmentForm.dataset.saving = 'false';
        controls.forEach((control, index) => { control.disabled = previousDisabled[index]; });
    }
});
