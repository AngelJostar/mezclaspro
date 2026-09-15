import { createIcons, Plus, Trash2, Undo2, X } from 'lucide';
import '../css/adjustment-proposal.css';

const text = value => value == null || value === '' ? '-' : String(value);
const equivalent = (a, b) => String(a ?? '').trim() === String(b ?? '').trim() ||
    (a !== '' && a != null && b !== '' && b != null && Number.isFinite(Number(a)) && Number(a) === Number(b));
const node = (tag, content, className) => {
    const element = document.createElement(tag);
    if (content != null) element.textContent = content;
    if (className) element.className = className;
    return element;
};

function initProposalEditor() {
    const dialog = document.querySelector('[data-adjustment-proposal]');
    const config = window.mixtureProposalConfig;
    if (!dialog || !config || dialog.dataset.initialized) return;
    dialog.dataset.initialized = '1';
    const sourceForm = document.getElementById(config.formId);
    const form = dialog.querySelector('[data-proposal-form]');
    const fields = dialog.querySelector('[data-proposal-fields]');
    const reason = dialog.querySelector('#proposal-reason');
    const error = dialog.querySelector('[data-proposal-error]');
    const send = form.querySelector('[type="submit"]');
    const opener = document.querySelector('[data-adjustment-proposal-open]');
    const isNutrition = config.kind === 'nutricionales';
    const icons = () => createIcons({ icons: { Plus, Trash2, Undo2, X }, nameAttr: 'data-proposal-icon' });
    const medicines = config.medicines || [];
    const medInfo = id => config.info?.[id] || {};
    const medicineName = id => medicines.find(m => String(m.id) === String(id))?.denominacion || text(id);
    const optionName = (options, id) => options?.find(o => String(o.id) === String(id))?.name || text(id);
    let draft, items, nutritionFields, previousFocus;

    const iconButton = (icon, label, action) => {
        const button = node('button', null, 'proposal-icon-button');
        button.type = 'button'; button.title = label; button.setAttribute('aria-label', label);
        const symbol = node('i'); symbol.dataset.proposalIcon = icon; symbol.setAttribute('aria-hidden', 'true');
        button.append(symbol); button.addEventListener('click', action);
        return button;
    };

    function row(label, original, control, value, baseline, suffix = '') {
        const tr = node('tr');
        const th = node('th', label); th.scope = 'row';
        const before = node('td', text(original)); before.dataset.originalValue = '';
        const cell = node('td'); cell.dataset.proposedCell = '';
        const mark = () => { cell.dataset.changed = String(!equivalent(value(), baseline)); };
        if (control) {
            control.setAttribute('aria-label', label + suffix);
            control.addEventListener('input', mark); control.addEventListener('change', mark);
            cell.append(control);
        } else cell.textContent = text(value());
        mark(); tr.append(th, before, cell); fields.append(tr);
        return tr;
    }

    function input(value, change, type = 'number', min = '0.000001') {
        const field = node('input'); field.type = type; field.value = value ?? '';
        field.required = true;
        if (type === 'number') { field.min = min; field.step = 'any'; }
        field.addEventListener('input', () => change(field.value));
        return field;
    }

    function select(options, value, change, required = true) {
        const field = node('select'); field.required = required;
        field.append(new Option('Selecciona', ''));
        options.forEach(option => field.append(new Option(option.name, String(option.id))));
        field.value = value ?? '';
        field.addEventListener('change', () => change(field.value));
        return field;
    }

    function normalizedMedicine(medicine) {
        const id = config.catalogIds?.[medicine.medicamento_id] ?? medicine.medicamento_id;
        return { medicamento_id: id, dosis: medicine.dosis, diluyente_id: medicine.diluyente_id,
            via_administracion_id: medicine.via_administracion_id,
            charge_by: medicine.charge_by || medicines.find(m => String(m.id) === String(id))?.charge_by || 'frasco',
            precio_mg: medicine.precio_mg_snapshot ?? '' };
    }

    function captureDraft() {
        const source = config.original;
        draft = Object.fromEntries(['volumen_dilucion', 'tiempo_infusion', 'infusor_id'].map(key =>
            [key, sourceForm.querySelector(`[data-name="${key}"]`)?.value ?? source[key] ?? '']));
        draft.set_infusion = sourceForm.querySelector('[data-name="set_infusion"]')?.checked ?? Boolean(source.set_infusion);
        const original = (source.medicamentos || []).map(normalizedMedicine);
        items = [...sourceForm.querySelectorAll('#medicamentos_mezcla .medicamento-row')].map((tr, index) => {
            const id = tr.id.replace('fila_', '');
            return { original: original[index], value: {
                medicamento_id: tr.querySelector('[data-name="medicamento"]')?.value || '',
                dosis: tr.querySelector('.dosis-input')?.value || '',
                diluyente_id: document.getElementById(`diluyente_fila_${id}`)?.value || '',
                via_administracion_id: document.getElementById(`detalle_fila_${id}`)?.querySelector('[data-name="via_administracion"]')?.value || '',
                charge_by: tr.querySelector('[data-name="charge_label"]')?.textContent.trim().toLowerCase() || 'frasco',
                precio_mg: tr.querySelector('[data-name="precio_mg"]')?.value || '',
            } };
        });
        if (config.previous) {
            try {
                const previous = JSON.parse(config.previous);
                draft = previous;
                items = previous.medicamentos.map((value, index) => ({ original: original[index], value }));
                original.slice(items.length).forEach(value => items.push({ original: value, value: { ...value }, removed: true }));
            } catch { /* A malformed draft is replaced by the current form values. */ }
            config.previous = null;
        }
    }

    function renderOncology() {
        fields.replaceChildren();
        items.forEach((item, index) => {
            const { value, original = {} } = item;
            const suffix = ` - Medicamento ${index + 1}`;
            const group = node('tr', null, 'proposal-group');
            const heading = node('th'); heading.colSpan = 3;
            const title = node('div', `Medicamento ${index + 1}`, 'proposal-group-title');
            const remove = iconButton(item.removed ? 'undo-2' : 'trash-2', item.removed ? `Restaurar medicamento ${index + 1}` : `Quitar medicamento ${index + 1}`, () => {
                item.removed = !item.removed; renderOncology();
            });
            remove.disabled = !item.removed && items.filter(entry => !entry.removed).length === 1;
            title.append(remove); heading.append(title); group.append(heading); fields.append(group);
            if (item.removed) {
                row('Medicamento', medicineName(original.medicamento_id), null, () => 'No incluido', original.medicamento_id);
                return;
            }
            const med = select(medicines.map(m => ({ id: m.id, name: m.denominacion })), value.medicamento_id, id => {
                value.medicamento_id = id;
                const details = medInfo(id);
                if (!(details.diluyentes || []).some(d => String(d.id) === String(value.diluyente_id))) value.diluyente_id = '';
                if (!(details.vias || []).some(v => String(v.id) === String(value.via_administracion_id))) value.via_administracion_id = '';
                value.charge_by = medicines.find(m => String(m.id) === id)?.charge_by || 'frasco';
                value.precio_mg = '';
                renderOncology();
                fields.querySelector(`[aria-label="Medicamento${suffix}"]`)?.focus();
            });
            row('Medicamento', original.medicamento_id ? medicineName(original.medicamento_id) : '-', med, () => value.medicamento_id, original.medicamento_id, suffix);
            row('Dosis', original.dosis, input(value.dosis, v => { value.dosis = v; }), () => value.dosis, original.dosis, suffix);
            row('Unidad', original.medicamento_id ? 'mg' : '-', null, () => 'mg', original.medicamento_id ? 'mg' : '');
            const details = medInfo(value.medicamento_id);
            const oldDetails = medInfo(original.medicamento_id);
            for (const [label, key, choices, oldChoices] of [
                ['Diluyente', 'diluyente_id', details.diluyentes, oldDetails.diluyentes],
                ['V\u00eda de administraci\u00f3n', 'via_administracion_id', details.vias, oldDetails.vias],
            ]) {
                row(label, optionName(oldChoices, original[key]), select(choices || [], value[key], v => { value[key] = v; }), () => value[key], original[key], suffix);
            }
        });
        for (const [label, key] of [['Volumen de diluci\u00f3n (ml)', 'volumen_dilucion'], ['Tiempo de infusi\u00f3n (min)', 'tiempo_infusion']]) {
            row(label, config.original[key], input(draft[key], v => { draft[key] = v; }), () => draft[key], config.original[key]);
        }
        const toggle = node('label', null, 'proposal-toggle');
        const checkbox = node('input'); checkbox.type = 'checkbox'; checkbox.checked = Boolean(draft.set_infusion);
        checkbox.setAttribute('aria-label', 'Set de infusi\u00f3n');
        const indicator = node('span', checkbox.checked ? 'Si' : 'No');
        checkbox.addEventListener('change', () => { draft.set_infusion = checkbox.checked; indicator.textContent = checkbox.checked ? 'Si' : 'No'; });
        toggle.append(checkbox, indicator);
        row('Set de infusi\u00f3n', config.original.set_infusion ? 'Si' : 'No', toggle, () => Boolean(draft.set_infusion), Boolean(config.original.set_infusion));
        const infusorOptions = (config.infusors || []).map(i => ({ id: i.id, name: i.nombre_generico || i.nombre_comercial || `Infusor #${i.id}` }));
        const hasInfusor = items.some(i => !i.removed && Number(medInfo(i.value.medicamento_id).requires_infusor || medicines.find(m => String(m.id) === String(i.value.medicamento_id))?.requires_infusor) === 1);
        if (!hasInfusor) draft.infusor_id = '';
        const infusor = select(infusorOptions, draft.infusor_id, v => { draft.infusor_id = v; }, false);
        infusor.disabled = !hasInfusor;
        row('Infusor', optionName(infusorOptions, config.original.infusor_id), infusor, () => draft.infusor_id, config.original.infusor_id);
        icons();
    }

    function renderNutrition() {
        fields.replaceChildren();
        nutritionFields = [];
        for (const spec of config.fields || []) {
            const source = sourceForm.elements.namedItem(spec.name);
            if (!source || source.readOnly) continue;
            const control = source.cloneNode(true);
            for (const attribute of [...control.attributes]) {
                if (attribute.name.startsWith('on') || attribute.name.startsWith('x-') || ['id', 'name', 'class', 'style'].includes(attribute.name)) control.removeAttribute(attribute.name);
            }
            control.value = source.value;
            control.disabled = false;
            if (control.type === 'number') control.min = '0';
            const before = source.tagName === 'SELECT'
                ? [...source.options].find(option => String(option.value) === String(spec.value))?.textContent || text(spec.value)
                : spec.value;
            row(spec.label, before, control, () => control.value, spec.value);
            nutritionFields.push({ name: spec.name, control });
        }
        const time = nutritionFields.find(field => field.name === 'tiempo_infusion_min')?.control;
        const speed = nutritionFields.find(field => field.name === 'velocidad_infusion')?.control;
        if (time && speed) {
            const sync = () => {
                time.disabled = speed.value !== '';
                if (time.disabled) time.value = '';
            };
            speed.addEventListener('input', sync); sync();
        }
    }

    function open() {
        if (dialog.open) return;
        previousFocus = document.activeElement;
        error.hidden = true; send.disabled = false;
        if (isNutrition) renderNutrition();
        else { captureDraft(); renderOncology(); }
        const add = dialog.querySelector('[data-proposal-add]'); add.hidden = isNutrition;
        dialog.showModal();
        icons();
        fields.querySelector('input:not(:disabled), select:not(:disabled)')?.focus();
        dialog.querySelector('.adjustment-proposal-body').scrollTop = 0;
    }
    opener?.addEventListener('click', open);
    dialog.querySelectorAll('[data-proposal-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => { reason.value = ''; previousFocus?.focus(); });
    dialog.addEventListener('keydown', event => { if (event.key === 'Escape') event.stopPropagation(); });
    dialog.querySelector('[data-proposal-add]').addEventListener('click', () => {
        items.push({ value: { medicamento_id: '', dosis: '', diluyente_id: '', via_administracion_id: '', charge_by: 'frasco', precio_mg: '' } });
        renderOncology(); fields.querySelector(`[aria-label="Medicamento - Medicamento ${items.length}"]`)?.focus();
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (send.disabled) return;
        error.hidden = true;
        if (reason.value.trim().length < 5) {
            error.textContent = 'Captura un motivo de al menos 5 caracteres.'; error.hidden = false; reason.focus(); return;
        }
        const payload = new FormData(sourceForm);
        if (isNutrition) {
            nutritionFields.forEach(({ name, control }) => payload.set(name, control.value));
        } else {
            const proposed = items.filter(i => !i.removed).map(i => ({ ...i.value }));
            if (!proposed.length || proposed.some(m => String(m.diluyente_id) !== String(proposed[0].diluyente_id) || String(m.via_administracion_id) !== String(proposed[0].via_administracion_id))) {
                error.textContent = 'Todos los medicamentos deben tener el mismo diluyente y la misma v\u00eda de administraci\u00f3n.';
                error.hidden = false; return;
            }
            payload.set('mezcla_json', JSON.stringify({ ...draft, medicamentos: proposed }));
        }
        payload.set('accion', 'ajustar'); payload.set('adjustment_description', reason.value.trim());
        // Submit a separate form so cancellation and proposal edits never mutate the approval form.
        const submission = document.createElement('form');
        submission.method = sourceForm.method; submission.action = sourceForm.action; submission.hidden = true;
        for (const [name, value] of payload) {
            if (typeof value !== 'string') continue;
            const field = document.createElement('input'); field.type = 'hidden'; field.name = name; field.value = value; submission.append(field);
        }
        document.body.append(submission); send.disabled = true;
        HTMLFormElement.prototype.submit.call(submission);
    });
    if (config.reopen) {
        open();
        if (config.errors?.length) { error.textContent = config.errors.join(' '); error.hidden = false; }
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initProposalEditor, { once: true });
else initProposalEditor();
document.addEventListener('livewire:navigated', initProposalEditor);
