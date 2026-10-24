// The server catalog is authoritative; browser entries are imported once for compatibility.
const programCatalogUrl = @json(route('admin.capacitaciones.programas.catalogo.store'));
const canManagePrograms = @json($canManagePrograms);
const deleteProgramButton = root.querySelector('[data-delete-program]');
const deleteProgramModal = root.querySelector('[data-delete-program-modal]');
const deleteProgramMessage = root.querySelector('[data-delete-program-message]');
const deleteProgramError = root.querySelector('[data-delete-program-error]');
const cancelDeleteProgram = root.querySelector('[data-cancel-delete-program]');
const confirmDeleteProgram = root.querySelector('[data-confirm-delete-program]');
const programError = document.createElement('p');
programError.className = 'training-program-error';
programError.setAttribute('role', 'alert');
programError.hidden = true;
programForm?.append(programError);
let programBusy = false;
let deleteStage = 1;
let deletion = null;

async function requestProgram(path, method, body) {
    const response = await fetch(programCatalogUrl.replace(/\/$/, '') + path, {
        method, credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar el cambio. Intenta nuevamente.');
    try { localStorage.setItem('mezclaspro.training.catalog-revision', Date.now() + '-' + Math.random()); } catch (_) {}
    return data;
}

function setProgramBusy(busy) {
    programBusy = busy;
    [programModal, deleteProgramModal].forEach(dialog => {
        dialog?.querySelectorAll('button').forEach(button => { button.disabled = busy; });
    });
}

const legacyPrograms = readStoredPrograms();
const catalogReady = canManagePrograms && Object.keys(legacyPrograms).length
    ? requestProgram('/importar', 'POST', { programs: Object.entries(legacyPrograms).map(([id, value]) => ({
        id, title: value.title, description: value.description || '', owner: value.owner, status: value.status,
    })) }).then(() => {
        try { localStorage.removeItem(programStorageKey); } catch (_) { /* Storage may be disabled. */ }
        location.reload();
    }).catch(error => {
        programError.textContent = 'No se pudieron recuperar los programas guardados: ' + error.message;
        programError.hidden = false;
        throw error;
    }) : Promise.resolve();
catalogReady.catch(() => {});

if (!canManagePrograms) {
    root.querySelectorAll('[data-edit-program], [data-new-program], [data-delete-program]').forEach(button => { button.hidden = true; });
}

root.querySelectorAll('[data-close-program-modal]').forEach(button => {
    button.addEventListener('click', () => { if (!programBusy) closeProgramModal(); });
});
programModal?.addEventListener('cancel', event => { if (programBusy) event.preventDefault(); });
programModal?.addEventListener('click', event => {
    if (event.target === programModal && !programBusy) closeProgramModal();
});

programForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!canManagePrograms || programBusy || !programForm.reportValidity()) return;
    const id = programForm.elements.program_id.value;
    const data = { title: programForm.elements.title.value.trim(), description: programForm.elements.description.value.trim(),
        owner: programForm.elements.owner.value.trim(), status: programForm.elements.status.value };
    programError.hidden = true;
    setProgramBusy(true);
    try {
        await catalogReady;
        const saved = await requestProgram(id ? '/' + encodeURIComponent(id) : '', id ? 'PATCH' : 'POST', data);
        try { localStorage.setItem(selectedProgramStorageKey, saved.id); } catch (_) {}
        location.reload();
    } catch (error) {
        programError.textContent = error.message;
        programError.hidden = false;
    } finally { setProgramBusy(false); }
});

deleteProgramButton?.addEventListener('click', () => {
    if (!canManagePrograms || programBusy) return;
    const id = programForm.elements.program_id.value;
    const card = Array.from(root.querySelectorAll('[data-program-card]')).find(card => card.dataset.programId === id);
    if (!card) return;
    deletion = { id, name: card.querySelector('[data-program-title]').textContent.trim() };
    deleteStage = 1;
    deleteProgramError.hidden = true;
    deleteProgramMessage.textContent = '¿Estás seguro que quieres eliminar el programa ' + deletion.name + '?';
    deleteProgramModal.showModal();
    cancelDeleteProgram.focus();
});
cancelDeleteProgram?.addEventListener('click', () => {
    if (!programBusy) { deleteProgramModal.close(); deleteProgramButton.focus(); }
});
deleteProgramModal?.addEventListener('cancel', event => {
    if (programBusy) event.preventDefault();
});
confirmDeleteProgram?.addEventListener('click', async () => {
    if (!deletion || programBusy) return;
    if (deleteStage === 1) {
        deleteStage = 2;
        deleteProgramMessage.textContent = 'Al eliminar el programa ' + deletion.name + ' no podrás recuperarlo. ¿Estás seguro que quieres eliminarlo?';
        cancelDeleteProgram.focus();
        return;
    }
    setProgramBusy(true);
    deleteProgramError.hidden = true;
    try {
        await catalogReady;
        await requestProgram('/' + encodeURIComponent(deletion.id), 'DELETE', { confirmed: true, confirmation_name: deletion.name });
        // Remove cached references as well; subsequent renders also filter by server program IDs.
        try {
            const programs = readStoredPrograms();
            delete programs[deletion.id];
            localStorage.setItem(programStorageKey, JSON.stringify(programs));
            const exams = JSON.parse(localStorage.getItem(examStorageKey) || '{}');
            Object.keys(exams).forEach(key => { if (exams[key].projectId === deletion.id) delete exams[key]; });
            localStorage.setItem(examStorageKey, JSON.stringify(exams));
            const assignments = readStoredAssignments();
            Object.keys(assignments).forEach(key => {
                assignments[key] = assignments[key].filter(item => item.programId !== deletion.id);
            });
            persistAssignments(assignments);
            if (localStorage.getItem(selectedProgramStorageKey) === deletion.id) localStorage.removeItem(selectedProgramStorageKey);
        } catch (_) { /* Database deletion has succeeded even when storage is unavailable. */ }
        location.reload();
    } catch (error) {
        deleteProgramError.textContent = error.message;
        deleteProgramError.hidden = false;
    } finally { setProgramBusy(false); }
});
