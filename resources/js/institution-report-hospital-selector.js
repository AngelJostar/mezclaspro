const dataElement = document.getElementById('institution-report-hospitals-data');
const popover = document.getElementById('hospital-report-selector-popover');

if (dataElement && popover) {
    const hospitalGroups = JSON.parse(dataElement.textContent || '{}');
    const controls = Array.from(document.querySelectorAll('[data-report-hospital-control]'));
    const searchInput = popover.querySelector('[data-hospital-search]');
    const allCheckbox = popover.querySelector('[data-hospital-all]');
    const optionsContainer = popover.querySelector('[data-hospital-options]');
    const emptyMessage = popover.querySelector('[data-hospital-empty]');
    const selectionCount = popover.querySelector('[data-hospital-count]');
    const applyButton = popover.querySelector('[data-hospital-apply]');
    const cancelButton = popover.querySelector('[data-hospital-cancel]');
    const selectedByControl = new WeakMap();
    let activeControl = null;
    let draftSelection = new Set();

    const hospitalsFor = (control) => hospitalGroups[control.dataset.institutionId] || [];
    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('es')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

    const labelFor = (count) => count === 1
        ? '1 hospital seleccionado'
        : `${count} hospitales seleccionados`;

    const updateDownload = (control, selection) => {
        const link = control.querySelector('[data-hospital-download]');
        const label = control.querySelector('[data-hospital-trigger-label]');

        if (label) label.textContent = labelFor(selection.size);
        if (!link) return;

        if (selection.size === 0) {
            link.removeAttribute('href');
            link.setAttribute('aria-disabled', 'true');
            link.classList.add('pointer-events-none', 'text-slate-400');
            link.classList.remove('text-blue-700', 'hover:text-blue-800');
            return;
        }

        const url = new URL(control.dataset.downloadBase, window.location.origin);
        url.searchParams.delete('hospital_ids[]');
        Array.from(selection).forEach((hospitalId) => url.searchParams.append('hospital_ids[]', hospitalId));
        link.href = url.toString();
        link.removeAttribute('aria-disabled');
        link.classList.remove('pointer-events-none', 'text-slate-400');
        link.classList.add('text-blue-700', 'hover:text-blue-800');
    };

    const updateAllCheckbox = () => {
        const hospitals = hospitalsFor(activeControl);
        allCheckbox.checked = hospitals.length > 0 && draftSelection.size === hospitals.length;
        allCheckbox.indeterminate = draftSelection.size > 0 && draftSelection.size < hospitals.length;
        selectionCount.textContent = labelFor(draftSelection.size);
    };

    const renderOptions = () => {
        const hospitals = hospitalsFor(activeControl);
        const query = normalize(searchInput.value);
        let visible = 0;

        optionsContainer.innerHTML = '';
        hospitals.forEach((hospital) => {
            if (query && !normalize(hospital.name).includes(query)) return;

            const label = document.createElement('label');
            const checkbox = document.createElement('input');
            const text = document.createElement('span');

            label.className = 'flex cursor-pointer items-start gap-2 rounded px-1 py-1.5 hover:bg-slate-100';
            checkbox.type = 'checkbox';
            checkbox.value = String(hospital.id);
            checkbox.checked = draftSelection.has(String(hospital.id));
            checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500';
            text.className = 'min-w-0 break-words text-xs leading-5';
            text.textContent = hospital.name;

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) draftSelection.add(checkbox.value);
                else draftSelection.delete(checkbox.value);
                updateAllCheckbox();
            });

            label.append(checkbox, text);
            optionsContainer.appendChild(label);
            visible += 1;
        });

        emptyMessage.classList.toggle('hidden', visible !== 0);
        updateAllCheckbox();
    };

    const closePopover = () => {
        popover.classList.add('hidden');
        activeControl?.querySelector('[data-hospital-trigger]')?.setAttribute('aria-expanded', 'false');
        activeControl = null;
        searchInput.value = '';
    };

    const positionPopover = (trigger) => {
        const rect = trigger.getBoundingClientRect();
        const width = Math.min(320, window.innerWidth - 16);
        popover.style.width = `${width}px`;
        popover.classList.remove('hidden');

        const height = popover.offsetHeight;
        const left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);
        const fitsBelow = rect.bottom + 8 + height <= window.innerHeight;
        const top = fitsBelow ? rect.bottom + 6 : Math.max(8, rect.top - height - 6);

        popover.style.left = `${left}px`;
        popover.style.top = `${top}px`;
    };

    const openPopover = (control) => {
        const hospitals = hospitalsFor(control);
        if (hospitals.length === 0) return;

        activeControl?.querySelector('[data-hospital-trigger]')?.setAttribute('aria-expanded', 'false');
        activeControl = control;
        draftSelection = new Set(selectedByControl.get(control) || hospitals.map((hospital) => String(hospital.id)));
        searchInput.value = '';
        renderOptions();

        const trigger = control.querySelector('[data-hospital-trigger]');
        trigger.setAttribute('aria-expanded', 'true');
        positionPopover(trigger);
        window.setTimeout(() => searchInput.focus(), 0);
    };

    controls.forEach((control) => {
        const hospitals = hospitalsFor(control);
        const initialSelection = new Set(hospitals.map((hospital) => String(hospital.id)));
        const trigger = control.querySelector('[data-hospital-trigger]');

        selectedByControl.set(control, initialSelection);
        updateDownload(control, initialSelection);
        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (activeControl === control && !popover.classList.contains('hidden')) closePopover();
            else openPopover(control);
        });
    });

    searchInput.addEventListener('input', renderOptions);
    allCheckbox.addEventListener('change', () => {
        draftSelection = allCheckbox.checked
            ? new Set(hospitalsFor(activeControl).map((hospital) => String(hospital.id)))
            : new Set();
        renderOptions();
    });
    applyButton.addEventListener('click', () => {
        if (!activeControl) return;
        selectedByControl.set(activeControl, new Set(draftSelection));
        updateDownload(activeControl, draftSelection);
        closePopover();
    });
    cancelButton.addEventListener('click', closePopover);
    document.addEventListener('click', (event) => {
        if (!popover.classList.contains('hidden') && !popover.contains(event.target)) closePopover();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePopover();
    });
    window.addEventListener('resize', closePopover);
    document.addEventListener('scroll', (event) => {
        if (!popover.classList.contains('hidden') && !popover.contains(event.target)) closePopover();
    }, true);
}
