<h4><span>4</span> Programas de capacitación</h4>
<p class="personnel-training-help">Selecciona los programas y módulos a los que tendrá acceso.</p>
@php
    $trainingChoices = collect($projects)->map(fn ($program) => [
        'id' => $program['id'], 'title' => $program['title'],
        'modules' => array_map(fn ($module) => \Illuminate\Support\Arr::only($module, ['id', 'stage', 'title']), $program['module_definitions']),
    ])->values()->all();
@endphp
<div data-personnel-training-access data-catalog-url="{{ route('admin.capacitaciones.personal.programs') }}">
        <p class="personnel-training-help" data-training-catalog-status role="status" hidden></p>
        <button type="button" class="personnel-training-retry" data-training-catalog-retry hidden>Reintentar actualización</button>
        <label class="personnel-training-select-all" @if (!count($projects)) hidden @endif>
            <input type="checkbox" data-training-select-all> Seleccionar todo
        </label>
        <div class="personnel-training-programs">
            @foreach ($projects as $program)
                @php
                    $wholeProgram = in_array($program['id'], old('training_all', []), true);
                    $selectedModuleIds = old('training_modules.'.$program['id'], []);
                @endphp
                <fieldset class="personnel-training-program" data-training-access-program data-program-id="{{ $program['id'] }}">
                    <legend>{{ $program['title'] }}</legend>
                    <label class="personnel-training-program-all">
                        <input type="checkbox" name="training_all[]" value="{{ $program['id'] }}"
                            data-training-program-all @checked($wholeProgram)>
                        {{ count($program['module_definitions']) ? 'Seleccionar todos los módulos' : 'Dar acceso al programa' }}
                    </label>
                    @forelse ($program['module_definitions'] as $module)
                        <label class="personnel-training-module">
                            <input type="checkbox" name="training_modules[{{ $program['id'] }}][]" value="{{ $module['id'] }}"
                                data-training-module @checked($wholeProgram || in_array($module['id'], $selectedModuleIds, true))>
                            <span>{{ $module['stage'] }} — {{ $module['title'] }}</span>
                        </label>
                    @empty
                        <p class="personnel-training-help">Este programa todavía no tiene módulos.</p>
                    @endforelse
                </fieldset>
            @endforeach
        </div>
        <p class="personnel-training-help" data-training-access-count aria-live="polite"></p>
        <p class="personnel-training-help" data-training-catalog-empty @if (count($projects)) hidden @endif>No hay programas disponibles. Puedes crearlos en la sección Programas.</p>
</div>
@push('css')
<style>
    .personnel-training-help { color: #52687f; font-size: .8rem; margin: .35rem 0 .75rem; }
    .personnel-training-select-all, .personnel-training-program-all, .personnel-training-module { display: flex; align-items: center; gap: .65rem; font-size: .85rem; cursor: pointer; }
    .personnel-training-select-all { font-weight: 700; padding: .65rem 0; }
    .personnel-training-programs { max-height: 22rem; overflow-y: auto; padding-right: .35rem; }
    .personnel-training-program { border: 1px solid #dce5ee; border-radius: 8px; padding: .75rem 1rem; margin: .5rem 0 1rem; }
    .personnel-training-program legend { padding: 0 .35rem; font-size: .88rem; font-weight: 700; }
    .personnel-training-program-all { color: #007d67; margin-bottom: .6rem; }
    .personnel-training-module { padding: .4rem 0; }
    [data-personnel-training-access] input[type=checkbox] { width: 1rem; height: 1rem; flex: 0 0 1rem; accent-color: #009b82; }
    [data-personnel-training-access] [hidden] { display: none; }
    .personnel-training-retry { color: #007d67; font-size: .85rem; text-decoration: underline; margin-bottom: .5rem; }
</style>
@endpush
@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const field = document.querySelector('[data-personnel-training-access]');
    const all = field?.querySelector('[data-training-select-all]');
    if (!all) return;
    const form = field.closest('form'), modal = field.closest('dialog');
    const list = field.querySelector('.personnel-training-programs');
    const status = field.querySelector('[data-training-catalog-status]');
    const retry = field.querySelector('[data-training-catalog-retry]');
    let signature = JSON.stringify(@json($trainingChoices));
    let pending = null, interval = null, checking = false, allowSubmit = false;
    const groups = () => Array.from(field.querySelectorAll('[data-training-access-program]'));
    function updateSelection() {
        let programs = 0, modules = 0;
        groups().forEach(group => {
            const checkbox = group.querySelector('[data-training-program-all]');
            const items = Array.from(group.querySelectorAll('[data-training-module]'));
            const checked = items.filter(item => item.checked).length;
            if (items.length) checkbox.checked = checked === items.length;
            checkbox.indeterminate = checked > 0 && checked < items.length;
            if (checked || checkbox.checked) programs++;
            modules += checked;
        });
        all.checked = groups().length > 0 && groups().every(group => group.querySelector('[data-training-program-all]').checked);
        all.indeterminate = programs > 0 && !all.checked;
        all.closest('label').hidden = !groups().length;
        field.querySelector('[data-training-catalog-empty]').hidden = groups().length > 0;
        field.querySelector('[data-training-access-count]').textContent = programs + ' programas y ' + modules + ' módulos seleccionados';
    }
    function notice(message) { status.textContent = message; status.hidden = !message; }
    function selections() {
        return new Map(groups().map(group => [group.dataset.programId, {
            full: group.querySelector('[data-training-program-all]').checked,
            modules: Array.from(group.querySelectorAll('[data-training-module]:checked'), input => input.value),
        }]));
    }
    function render(programs) {
        const selected = selections(), scroll = list.scrollTop, focused = document.activeElement;
        const focusName = field.contains(focused) ? focused.name : null, focusValue = focused?.value;
        let removed = false;
        selected.forEach((selection, id) => {
            const current = programs.find(program => program.id === id);
            if (!current && (selection.full || selection.modules.length)) removed = true;
            if (current && selection.modules.some(id => !current.modules.some(module => module.id === id))) removed = true;
        });
        const fragment = document.createDocumentFragment();
        const label = (container, text, className, name, value, checked, dataKey) => {
            const wrapper = document.createElement('label'); wrapper.className = className;
            const input = document.createElement('input'); input.type = 'checkbox'; input.name = name; input.value = value; input.checked = checked; input.dataset[dataKey] = '';
            const caption = document.createElement('span'); caption.textContent = text;
            wrapper.append(input, caption); container.append(wrapper);
        };
        programs.forEach(program => {
            const selection = selected.get(program.id);
            const group = document.createElement('fieldset'); group.className = 'personnel-training-program'; group.dataset.trainingAccessProgram = ''; group.dataset.programId = program.id;
            const legend = document.createElement('legend'); legend.textContent = program.title; group.append(legend);
            label(group, program.modules.length ? 'Seleccionar todos los módulos' : 'Dar acceso al programa', 'personnel-training-program-all', 'training_all[]', program.id, !!selection?.full, 'trainingProgramAll');
            program.modules.forEach(module => label(group, module.stage + ' — ' + module.title, 'personnel-training-module', 'training_modules[' + program.id + '][]', module.id, !!selection?.full || !!selection?.modules.includes(module.id), 'trainingModule'));
            if (!program.modules.length) {
                const empty = document.createElement('p'); empty.className = 'personnel-training-help'; empty.textContent = 'Este programa todavía no tiene módulos.'; group.append(empty);
            }
            fragment.append(group);
        });
        list.replaceChildren(fragment); updateSelection(); list.scrollTop = scroll;
        if (focusName) Array.from(field.querySelectorAll('input')).find(input => input.name === focusName && input.value === focusValue)?.focus({ preventScroll: true });
        return removed;
    }
    function refresh(showLoading = false) {
        if (pending) return pending;
        if (showLoading) notice('Actualizando programas de capacitación…');
        field.setAttribute('aria-busy', 'true'); retry.hidden = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        pending = (async () => {
            try {
                const response = await fetch(field.dataset.catalogUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error('Unavailable catalog');
                const data = await response.json();
                if (!Array.isArray(data.programs) || data.programs.some(program => !Array.isArray(program.modules))) throw new Error('Invalid catalog');
                const nextSignature = JSON.stringify(data.programs);
                const changed = signature !== nextSignature;
                const removed = changed ? render(data.programs) : false;
                signature = nextSignature;
                field.dataset.catalogState = 'ready';
                if (changed) notice(removed ? 'Catálogo actualizado. Se retiraron selecciones que ya no están disponibles. Revisa los programas antes de guardar.' : 'Programas y módulos actualizados. Se conservaron tus selecciones disponibles.');
                else if (showLoading || field.dataset.catalogError) notice('');
                delete field.dataset.catalogError;
                return { ok: true, removed };
            } catch (_) {
                field.dataset.catalogState = 'error'; field.dataset.catalogError = 'true'; retry.hidden = false;
                notice('No se pudieron actualizar los programas. Tus datos están conservados. Reintenta antes de guardar.');
                return { ok: false };
            } finally {
                clearTimeout(timeout); field.setAttribute('aria-busy', 'false'); pending = null;
            }
        })();
        return pending;
    }
    field.addEventListener('change', event => {
        if (event.target === all) field.querySelectorAll('input[type=checkbox]').forEach(item => { item.checked = all.checked; });
        else if (event.target.matches('[data-training-program-all]')) {
            event.target.closest('[data-training-access-program]').querySelectorAll('[data-training-module]').forEach(item => { item.checked = event.target.checked; });
        }
        updateSelection();
    });
    field.closest('form')?.addEventListener('reset', () => setTimeout(updateSelection));
    function startRefresh() {
        refresh(true);
        clearInterval(interval);
        interval = setInterval(() => { if (modal.open && !document.hidden) refresh(); }, 15000);
    }
    modal.addEventListener('personnel-create-open', startRefresh);
    modal.addEventListener('close', () => clearInterval(interval));
    window.addEventListener('focus', () => { if (modal.open) refresh(); });
    document.addEventListener('visibilitychange', () => { if (modal.open && !document.hidden) refresh(); });
    window.addEventListener('storage', event => { if (modal.open && event.key === 'mezclaspro.training.catalog-revision') refresh(); });
    retry.addEventListener('click', () => refresh(true));
    form.addEventListener('submit', async event => {
        if (allowSubmit) return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (checking) return;
        checking = true;
        const save = form.querySelector('[data-save-personnel]'), caption = save.querySelector('span');
        const previousLabel = caption.textContent;
        save.disabled = true; caption.textContent = 'Verificando programas…';
        const result = await refresh(true);
        save.disabled = false; caption.textContent = previousLabel; checking = false;
        if (!result.ok || !modal.open) return;
        if (result.removed) {
            notice('El catálogo cambió. Revisa los programas seleccionados y vuelve a pulsar Guardar personal.');
            status.scrollIntoView({ block: 'center' }); return;
        }
        allowSubmit = true;
        form.requestSubmit(event.submitter || undefined);
        allowSubmit = false;
    }, true);
    updateSelection();
    if (modal.open) startRefresh();
});
</script>
@endpush
