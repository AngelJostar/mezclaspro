<dialog data-seller-hospitals-dialog aria-labelledby="seller-hospitals-title" style="width:min(900px,95vw);max-height:85dvh;border:1px solid #dbe3ef;border-radius:12px;padding:0">
    <div style="padding:20px;display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #dbe3ef">
        <div><h2 id="seller-hospitals-title" class="text-lg font-semibold">Asignar hospitales</h2><p data-seller-name class="text-sm text-gray-500"></p></div>
        <button type="button" data-seller-close aria-label="Cerrar asignación">✕</button>
    </div>
    <div data-seller-body style="padding:20px">
        <p data-seller-message role="status" class="mb-3 text-sm"></p>
        <div class="grid gap-3 sm:grid-cols-2">
            <label>Institución<select data-seller-institution class="mt-1 w-full rounded border-gray-300"><option value="">Todas las instituciones</option></select></label>
            <label>Hospital<input data-seller-search type="search" placeholder="Buscar hospital" class="mt-1 w-full rounded border-gray-300"></label>
        </div>
        <p class="my-3 text-sm text-gray-500">Pulsa + para desplegar los hospitales. Marca la institución para seleccionar todos sus hospitales, o elige cada hospital individualmente.</p>
        <div data-seller-list></div>
    </div>
    <footer style="padding:16px 20px;border-top:1px solid #dbe3ef;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <span data-seller-count>0 hospitales seleccionados</span>
        <div class="flex gap-2"><button type="button" data-seller-close class="rounded border px-3 py-2">Cancelar</button><button type="button" data-seller-save class="rounded bg-teal-600 px-3 py-2 text-white">Guardar asignación</button></div>
    </footer>
</dialog>
<style>
    [data-seller-hospitals-dialog] { height: min(720px, 85dvh); overflow: hidden; }
    [data-seller-hospitals-dialog][open] { display: flex; flex-direction: column; }
    [data-seller-hospitals-dialog] > div:first-child,
    [data-seller-hospitals-dialog] > footer { flex-shrink: 0; }
    [data-seller-body] { display: flex; flex-direction: column; flex: 1; min-height: 0; }
    [data-seller-body] > :not([data-seller-list]) { flex-shrink: 0; }
    [data-seller-list] { flex: 1; min-height: 0; overflow: auto; scrollbar-gutter: stable; overflow-anchor: none; }
    .seller-hospitals-collapse { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows 650ms cubic-bezier(.25,.1,.25,1), opacity 650ms ease; }
    .seller-hospitals-collapse.is-open { grid-template-rows: 1fr; opacity: 1; }
    .seller-hospitals-inner { min-height: 0; overflow: hidden; padding-left: 32px; }
    @media (prefers-reduced-motion: reduce) {
        .seller-hospitals-collapse { transition: none; }
    }
</style>
@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-seller-hospitals-dialog]');
    if (!dialog) return;
    const list = dialog.querySelector('[data-seller-list]');
    const message = dialog.querySelector('[data-seller-message]');
    const institution = dialog.querySelector('[data-seller-institution]');
    const search = dialog.querySelector('[data-seller-search]');
    const save = dialog.querySelector('[data-seller-save]');
    let data, selected = new Set(), endpoint, busy = false, controller;
    let expanded = new Set();
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    function render() {
        const scrollTop = list.scrollTop;
        list.replaceChildren();
        if (!data) return;
        const visible = data.hospitals.filter(h => (!institution.value || h.institutions.map(Number).includes(Number(institution.value))) && normalize(h.name).includes(normalize(search.value.trim())));
        function option(parent, text, checked, change, indeterminate = false) {
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #edf2f7';
            const input = document.createElement('input'); input.type = 'checkbox'; input.checked = checked; input.indeterminate = indeterminate;
            input.addEventListener('change', () => { change(input.checked); render(); });
            const span = document.createElement('span'); span.textContent = text; label.append(input, span); parent.append(label);
        }
        const groups = [...data.institutions].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
        if (!institution.value && data.hospitals.some(h => !h.institutions.length)) groups.push({ id: 'unassigned', nombre: 'Sin institución' });
        groups.filter(i => !institution.value || Number(i.id) === Number(institution.value)).forEach(i => {
            const hospitals = data.hospitals.filter(h => i.id === 'unassigned' ? !h.institutions.length : h.institutions.map(Number).includes(Number(i.id)));
            const matching = hospitals.filter(h => visible.includes(h));
            const ids = hospitals.map(h => Number(h.id));
            if (!ids.length) return;
            if (!matching.length) return;
            const count = ids.filter(id => selected.has(id)).length;
            const group = document.createElement('section');
            const heading = document.createElement('div'); heading.style.cssText = 'display:flex;align-items:center;background:#f5f8fc;border-bottom:1px solid #dbe3ef';
            const isOpen = expanded.has(String(i.id)) || !!search.value.trim();
            const toggle = document.createElement('button'); toggle.type = 'button'; toggle.textContent = isOpen ? '−' : '+';
            toggle.style.cssText = 'padding:10px 14px;font-size:20px;color:#163c80';
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', (isOpen ? 'Ocultar hospitales de ' : 'Mostrar hospitales de ') + i.nombre);
            const children = document.createElement('div'); children.id = 'seller-institution-' + i.id;
            children.className = 'seller-hospitals-collapse' + (isOpen ? ' is-open' : '');
            children.inert = !isOpen; children.setAttribute('aria-hidden', String(!isOpen));
            const inner = document.createElement('div'); inner.className = 'seller-hospitals-inner'; children.append(inner);
            toggle.addEventListener('click', () => {
                const open = toggle.getAttribute('aria-expanded') !== 'true';
                open ? expanded.add(String(i.id)) : expanded.delete(String(i.id));
                toggle.textContent = open ? '−' : '+';
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', (open ? 'Ocultar hospitales de ' : 'Mostrar hospitales de ') + i.nombre);
                children.classList.toggle('is-open', open);
                children.inert = !open; children.setAttribute('aria-hidden', String(!open));
            });
            toggle.setAttribute('aria-controls', children.id); heading.append(toggle);
            option(heading, i.nombre + ' (' + count + '/' + ids.length + ' hospitales seleccionados)', count === ids.length,
                checked => ids.forEach(id => checked ? selected.add(id) : selected.delete(id)), count > 0 && count < ids.length);
            heading.lastChild.style.cssText += ';flex:1;cursor:pointer;font-weight:600';
            matching.forEach(h => option(inner, h.name, selected.has(Number(h.id)), checked => checked ? selected.add(Number(h.id)) : selected.delete(Number(h.id))));
            group.append(heading, children); list.append(group);
        });
        if (!visible.length) { const p = document.createElement('p'); p.textContent = 'No hay hospitales para los filtros seleccionados.'; list.append(p); }
        dialog.querySelector('[data-seller-count]').textContent = selected.size + ' hospitales seleccionados';
        list.scrollTop = scrollTop;
    }
    document.querySelectorAll('[data-assign-seller-hospitals]').forEach(button => button.addEventListener('click', async event => {
        event.preventDefault();
        const form = button.closest('form');
        if (!button.dataset.sellerEndpoint && !form.querySelector('[name="positions[]"][value="Vendedor"]:checked')) {
            alert('Selecciona y guarda el puesto Vendedor antes de asignar hospitales.'); return;
        }
        endpoint = button.dataset.sellerEndpoint || form.action.replace(/\/$/, '') + '/hospitales';
        controller?.abort(); controller = new AbortController();
        data = null; selected = new Set(); expanded = new Set(); list.replaceChildren(); search.value = ''; institution.replaceChildren(new Option('Todas las instituciones', ''));
        dialog.querySelector('[data-seller-name]').textContent = '';
        message.textContent = 'Cargando hospitales…'; save.disabled = true; dialog.showModal();
        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, signal: controller.signal });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'No se pudo cargar la asignación.');
            data = result; selected = new Set(data.selected.map(Number));
            dialog.querySelector('[data-seller-name]').textContent = data.name;
            data.institutions.forEach(i => institution.add(new Option(i.nombre, i.id)));
            message.textContent = ''; save.disabled = false; render();
        } catch (error) { if (error.name !== 'AbortError') message.textContent = error.message; }
    }));
    institution.addEventListener('change', render); search.addEventListener('input', render);
    dialog.querySelectorAll('[data-seller-close]').forEach(button => button.addEventListener('click', () => { if (!busy) { controller?.abort(); dialog.close(); } }));
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); else controller?.abort(); });
    save.addEventListener('click', async () => {
        if (busy || !data) return;
        busy = true; save.disabled = true; message.textContent = 'Guardando asignación…';
        try {
            const csrf = document.querySelector('[data-personnel-edit-form] [name="_token"]').value;
            const response = await fetch(endpoint, { method: 'PUT', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ hospital_ids: [...selected] }) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'No se pudo guardar la asignación.'));
            dialog.close();
        } catch (error) { message.textContent = error.message; }
        finally { busy = false; save.disabled = false; }
    });
});
</script>
@endpush
