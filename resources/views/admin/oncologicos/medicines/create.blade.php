<x-admin-layout>
    <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-md">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Crear Nueva Lista de Medicamentos</h1>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong class="font-bold">¡Oops! Algo salió mal:</strong>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                @if ($errors->has('medicamentos'))
                    <p class="mt-2 text-sm font-semibold">{{ $errors->first('medicamentos') }}</p>
                @endif
            </div>
        @endif

        <form action="{{ route('admin.oncologicos.medicines.store') }}" method="POST" enctype="multipart/form-data"
            class="space-y-6">
            @csrf

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la lista:</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200">
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción:</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200">{{ old('description') }}</textarea>
            </div>

            {{-- Switch: Activar marcas --}}
            <div class="flex items-center">
                <input type="hidden" name="active_brands" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="active_brands" value="1" class="sr-only peer"
                        {{ old('active_brands', false) ? 'checked' : '' }}>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                               after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                               after:bg-white after:border-gray-300 after:border after:rounded-full
                               after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Activar marcas</span>
                </label>
            </div>

            {{-- Switch: tipo de cobro global (mg / frasco) --}}
            <div class="flex items-center">
                <input type="hidden" name="charge_by" id="charge_by" value="{{ old('charge_by', 'mg') }}">

                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="charge_by_switch" class="sr-only peer"
                        {{ old('charge_by', 'mg') === 'frasco' ? 'checked' : '' }}>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                               after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                               after:bg-white after:border-gray-300 after:border after:rounded-full
                               after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        Cobrar por <span id="charge_by_label">
                            {{ old('charge_by', 'mg') === 'frasco' ? 'frasco' : 'mg' }}
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex items-center">
                <input type="hidden" name="show_label_lot_expiry" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="show_label_lot_expiry" value="1" class="sr-only peer"
                        {{ old('show_label_lot_expiry', false) ? 'checked' : '' }}>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                               after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                               after:bg-white after:border-gray-300 after:border after:rounded-full
                               after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        Mostrar lote y caducidad en etiqueta
                    </span>
                </label>
            </div>

            <div class="border rounded-lg p-4 bg-gray-50 space-y-4">
                <div class="flex items-center">
                    <input type="hidden" name="has_mixing_service" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="has_mixing_service" id="has_mixing_service" value="1"
                            class="sr-only peer" {{ old('has_mixing_service', false) ? 'checked' : '' }}>
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                                   after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                   after:bg-white after:border-gray-300 after:border after:rounded-full
                                   after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Activar servicio de mezclado
                        </span>
                    </label>
                </div>

                <div id="mixing_service_price_wrap"
                    class="{{ old('has_mixing_service', false) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio del servicio de mezclado</label>
                    <input type="number" step="0.0001" min="0" name="mixing_service_price"
                        value="{{ old('mixing_service_price', 0) }}"
                        class="w-full md:w-72 px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200 text-right"
                        placeholder="0.0000">
                    <p class="mt-1 text-xs text-gray-500">
                        Este importe se cobrará por mezcla en remisiones y facturación oncológica.
                    </p>
                </div>
            </div>

            {{-- ================= DISTRIBUTOR (opcional) ================= --}}
            <div class="border rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Distribuidor</h2>
                        <p class="text-xs text-gray-500">
                            Opcional. Estos datos se usarán para la segunda hoja de remisión.
                        </p>
                    </div>

                    <button type="button" id="btn-toggle-distributor"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded">
                        + Agregar distribuidor
                    </button>
                </div>

                <div id="distributor-form" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">

                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del distribuidor</label>
                        <input type="text" name="distributor_name" id="distributor_name"
                            value="{{ old('distributor_name') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200"
                            placeholder="Ej. Centro Sequoia">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="distributor_address" id="distributor_address"
                            value="{{ old('distributor_address') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200"
                            placeholder="Calle, número, colonia, ciudad...">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo (opcional)</label>
                        <input type="file" name="distributor_logo" id="distributor_logo" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">

                        <div id="distributor-logo-preview-wrap" class="mt-3 hidden">
                            <p class="text-xs text-gray-500 mb-2">Vista previa:</p>
                            <img id="distributor-logo-preview" class="h-20 w-auto rounded border bg-white"
                                alt="Logo preview">
                        </div>

                        <div class="mt-3 flex gap-2">
                            <button type="button" id="btn-clear-distributor"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold px-3 py-2 rounded">
                                Quitar distribuidor
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-500">
                            Nota: por seguridad del navegador, el input de archivo no puede “persistir” con old(); si
                            falla la validación tendrás que volver a elegir el logo.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ================= TABLA MEDICAMENTOS ================= --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Medicamentos:</label>

                <div class="mb-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Filtrar por inicial</div>
                            <div class="mt-2 flex flex-wrap gap-1 price-list-alpha-filter">
                                <button type="button" data-alpha=""
                                    class="alpha-filter-btn rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">Todos</button>
                                @foreach (range('A', 'Z') as $letter)
                                    <button type="button" data-alpha="{{ $letter }}"
                                        class="alpha-filter-btn rounded bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100">{{ $letter }}</button>
                                @endforeach
                            </div>
                        </div>

                        <button type="button" id="btn-sort-alpha"
                            class="rounded bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-900">
                            Ordenar A-Z
                        </button>
                    </div>
                </div>

                <table class="w-full text-sm text-left text-gray-600 border">
                    <thead class="text-xs uppercase bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 w-1/12 text-center">#</th>
                            <th class="px-4 py-2 w-4/12">Medicamento (genérico)</th>
                            <th class="px-4 py-2 w-3/12">Presentación</th>
                            <th class="px-4 py-2 w-2/12 text-center">Precio (según cobro global)</th>
                            <th class="px-4 py-2 w-2/12 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-medicamentos">
                        {{-- JS agregará aquí los grupos y filas --}}
                    </tbody>
                </table>

                <button type="button" id="btn-add-grupo"
                    class="mt-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded">
                    + Agregar medicamento
                </button>

                {{-- ================= Plantillas ocultas ================= --}}

                {{-- Encabezado de grupo (genérico) --}}
                <table class="hidden">
                    <tbody>
                        <tr id="tpl-grupo-header">
                            <td class="px-4 py-2 text-center font-bold bg-gray-50 border-t" colspan="5">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs uppercase text-gray-500 mr-2">Medicamento genérico:</span>

                                    <select class="select-generico border-gray-300 rounded text-sm"
                                        data-role="generico">
                                        <option value="">Seleccione un medicamento...</option>
                                        @foreach ($catalogos as $cat)
                                            <option value="{{ $cat->id }}">
                                                {{ $cat->denominacion }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="button"
                                        class="btn-add-presentacion ml-auto bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded"
                                        data-role="add-presentacion">
                                        + Agregar presentación
                                    </button>

                                    <button type="button"
                                        class="btn-remove-grupo bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded"
                                        data-role="remove-grupo">
                                        Quitar medicamento
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Fila de presentación --}}
                <table class="hidden">
                    <tbody>
                        <tr id="tpl-presentacion-row" class="fila-presentacion border-t" data-group="">
                            <td class="px-4 py-2 text-center align-top" data-role="row-index">1</td>

                            <td class="px-4 py-2 align-top text-gray-800 text-sm" data-role="generico-label"></td>

                            <td class="px-4 py-2 align-top min-w-[320px]">
                                <select class="w-full min-w-[320px] border-gray-300 rounded text-sm select-presentacion"
                                    data-role="presentacion">
                                    <option value="">Seleccione presentación...</option>
                                </select>
                                <input type="hidden" data-role="input-catalog-id">
                            </td>

                            <td class="px-4 py-2 text-center align-top">
                                <input type="number" step="0.0001" min="0"
                                    class="w-full border border-gray-300 rounded text-sm text-right"
                                    placeholder="Precio" data-role="precio">
                            </td>

                            <td class="px-4 py-2 text-center align-top">
                                <button type="button"
                                    class="btn-remove-row bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded"
                                    data-role="remove-row">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                @php
                    $presentacionesPorCatalogo = $catalogos->mapWithKeys(function ($c) {
                        return [
                            (string) $c->id => $c->presentations
                                ->map(function ($p) {
                                    $marca = trim((string) ($p->marca ?? ''));
                                    $pres = trim((string) ($p->presentacion ?? ''));
                                    $text = $marca !== '' ? "{$marca} - {$pres}" : $pres;

                                    return [
                                        'id' => $p->id,
                                        'text' => $text,
                                    ];
                                })
                                ->values(),
                        ];
                    });
                @endphp

                <script>
                    window.PRESENTACIONES_POR_CATALOGO = @json($presentacionesPorCatalogo);
                    window.OLD_MEDICAMENTOS = @json(old('medicamentos', []));
                </script>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                    Guardar Lista
                </button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // ======================
                // Distribuidor (UI)
                // ======================
                const btnToggleDist = document.getElementById('btn-toggle-distributor');
                const distForm = document.getElementById('distributor-form');
                const btnClearDist = document.getElementById('btn-clear-distributor');

                const distName = document.getElementById('distributor_name');
                const distAddress = document.getElementById('distributor_address');
                const distLogo = document.getElementById('distributor_logo');

                const logoWrap = document.getElementById('distributor-logo-preview-wrap');
                const logoImg = document.getElementById('distributor-logo-preview');

                function showDistForm() {
                    distForm?.classList.remove('hidden');
                    if (btnToggleDist) btnToggleDist.textContent = 'Ocultar distribuidor';
                }

                function hideDistForm() {
                    distForm?.classList.add('hidden');
                    if (btnToggleDist) btnToggleDist.textContent = '+ Agregar distribuidor';
                }

                function clearDistForm() {
                    if (distName) distName.value = '';
                    if (distAddress) distAddress.value = '';
                    if (distLogo) distLogo.value = '';
                    if (logoWrap) logoWrap.classList.add('hidden');
                    if (logoImg) logoImg.src = '';
                    hideDistForm();
                }

                if (btnToggleDist && distForm) {
                    btnToggleDist.addEventListener('click', () => {
                        const isHidden = distForm.classList.contains('hidden');
                        if (isHidden) showDistForm();
                        else hideDistForm();
                    });
                }

                btnClearDist?.addEventListener('click', clearDistForm);

                distLogo?.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (!file) {
                        logoWrap?.classList.add('hidden');
                        if (logoImg) logoImg.src = '';
                        return;
                    }
                    const url = URL.createObjectURL(file);
                    if (logoImg) logoImg.src = url;
                    logoWrap?.classList.remove('hidden');
                });

                // Si viene old() de distribuidor, lo mostramos
                const hasOldDist =
                    (distName && distName.value.trim() !== '') ||
                    (distAddress && distAddress.value.trim() !== '') ||
                    (distLogo && distLogo.value);

                if (hasOldDist) showDistForm();

                // ======================
                // Tabla dinámica
                // ======================
                const tbody = document.getElementById('tbody-medicamentos');
                const tplGrupoHeader = document.getElementById('tpl-grupo-header');
                const tplPresentacionRow = document.getElementById('tpl-presentacion-row');

                const chargeByHidden = document.getElementById('charge_by');
                const chargeSwitch = document.getElementById('charge_by_switch');
                const chargeLabel = document.getElementById('charge_by_label');

                let globalIndex = 0;
                let groupCounter = 0;

                const PRESENT = window.PRESENTACIONES_POR_CATALOGO || {};
                const OLD_MEDS = window.OLD_MEDICAMENTOS || {};

                // ===== Switch global
                function syncChargeGlobalLabel() {
                    if (!chargeByHidden || !chargeSwitch || !chargeLabel) return;
                    const value = chargeSwitch.checked ? 'frasco' : 'mg';
                    chargeByHidden.value = value;
                    chargeLabel.textContent = value;
                }

                if (chargeSwitch && chargeByHidden && chargeLabel) {
                    chargeSwitch.addEventListener('change', syncChargeGlobalLabel);
                    syncChargeGlobalLabel();
                }

                const mixingServiceToggle = document.getElementById('has_mixing_service');
                const mixingServiceWrap = document.getElementById('mixing_service_price_wrap');

                function syncMixingServiceVisibility() {
                    if (!mixingServiceToggle || !mixingServiceWrap) return;
                    mixingServiceWrap.classList.toggle('hidden', !mixingServiceToggle.checked);
                }

                if (mixingServiceToggle && mixingServiceWrap) {
                    mixingServiceToggle.addEventListener('change', syncMixingServiceVisibility);
                    syncMixingServiceVisibility();
                }

                // ===== Catálogo master de genéricos
                const MASTER_GENERIC = (() => {
                    const tmp = tplGrupoHeader.querySelector('[data-role="generico"]');
                    return Array.from(tmp.options).map(o => ({
                        value: o.value,
                        text: o.text
                    }));
                })();

                function renumerarFilas() {
                    let n = 1;
                    tbody.querySelectorAll('tr.fila-presentacion').forEach(row => {
                        const cellIndex = row.querySelector('[data-role="row-index"]');
                        if (cellIndex) cellIndex.textContent = n++;
                    });
                }

                let selectedAlpha = '';

                const normalizeText = (value) => String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                function getGroupHeaderText(headerRow) {
                    const select = headerRow.querySelector('[data-role="generico"]');
                    return select?.selectedOptions?.[0]?.textContent || '';
                }

                function getGroupRows(headerRow) {
                    const rows = [headerRow];
                    let next = headerRow.nextElementSibling;

                    while (next && next.classList.contains('fila-presentacion')) {
                        rows.push(next);
                        next = next.nextElementSibling;
                    }

                    return rows;
                }

                function applyAlphabetFilter() {
                    const headers = Array.from(tbody.querySelectorAll('tr[data-group]:not(.fila-presentacion)'));

                    headers.forEach((header) => {
                        const firstLetter = normalizeText(getGroupHeaderText(header)).charAt(0).toUpperCase();
                        const visible = selectedAlpha === '' || firstLetter === selectedAlpha;

                        getGroupRows(header).forEach((row) => {
                            row.classList.toggle('hidden', !visible);
                        });
                    });
                }

                function sortGroupsAlphabetically() {
                    const groups = Array.from(tbody.querySelectorAll('tr[data-group]:not(.fila-presentacion)'))
                        .map((header) => ({
                            rows: getGroupRows(header),
                            text: normalizeText(getGroupHeaderText(header)),
                        }))
                        .sort((a, b) => a.text.localeCompare(b.text, 'es'));

                    groups.forEach((group) => {
                        group.rows.forEach((row) => tbody.appendChild(row));
                    });

                    renumerarFilas();
                    applyAlphabetFilter();
                }

                document.querySelectorAll('.alpha-filter-btn').forEach((button) => {
                    button.addEventListener('click', function() {
                        selectedAlpha = this.dataset.alpha || '';

                        document.querySelectorAll('.alpha-filter-btn').forEach((btn) => {
                            btn.classList.remove('bg-blue-600', 'text-white');
                            btn.classList.add('bg-white', 'text-gray-700', 'ring-1', 'ring-gray-200');
                        });

                        this.classList.add('bg-blue-600', 'text-white');
                        this.classList.remove('bg-white', 'text-gray-700', 'ring-1', 'ring-gray-200');

                        applyAlphabetFilter();
                    });
                });

                document.getElementById('btn-sort-alpha')?.addEventListener('click', sortGroupsAlphabetically);

                // --- Genéricos usados (en otros grupos)
                function getSelectedCatalogIds(exceptGroupId = null) {
                    const used = [];
                    tbody.querySelectorAll('tr[data-group]').forEach(headerRow => {
                        if (headerRow.classList.contains('fila-presentacion')) return;

                        const groupId = headerRow.dataset.group;
                        if (exceptGroupId && String(groupId) === String(exceptGroupId)) return;

                        const sel = headerRow.querySelector('[data-role="generico"]');
                        if (sel && sel.value) used.push(String(sel.value));
                    });
                    return used;
                }

                function refreshGenericSelect(selectEl, groupId, currentValue = '') {
                    const used = getSelectedCatalogIds(groupId);
                    const cur = String(currentValue || '');

                    selectEl.innerHTML = '';
                    MASTER_GENERIC.forEach(optData => {
                        if (!optData.value) {
                            const opt = document.createElement('option');
                            opt.value = '';
                            opt.textContent = optData.text || 'Seleccione un medicamento...';
                            selectEl.appendChild(opt);
                            return;
                        }

                        const val = String(optData.value);
                        if (used.includes(val) && val !== cur) return;

                        const opt = document.createElement('option');
                        opt.value = optData.value;
                        opt.textContent = optData.text;
                        if (val === cur) opt.selected = true;
                        selectEl.appendChild(opt);
                    });
                }

                function refreshAllGenericSelects() {
                    tbody.querySelectorAll('tr[data-group]').forEach(headerRow => {
                        if (headerRow.classList.contains('fila-presentacion')) return;
                        const groupId = headerRow.dataset.group;
                        const sel = headerRow.querySelector('[data-role="generico"]');
                        if (!sel) return;
                        refreshGenericSelect(sel, groupId, sel.value || '');
                    });
                }

                // --- Presentaciones usadas por catálogo (global)
                function getSelectedPresentationIdsByCatalog(catalogId, exceptSelectEl = null) {
                    const used = [];
                    tbody.querySelectorAll('tr.fila-presentacion').forEach(row => {
                        const inputCatalog = row.querySelector('[data-role="input-catalog-id"]');
                        const selPres = row.querySelector('[data-role="presentacion"]');
                        if (!inputCatalog || !selPres) return;

                        if (String(inputCatalog.value) !== String(catalogId)) return;
                        if (!selPres.value) return;
                        if (exceptSelectEl && selPres === exceptSelectEl) return;

                        used.push(String(selPres.value));
                    });
                    return used;
                }

                function syncPresentationDetail(selectEl) {
                    const selectedText = selectEl.value ?
                        (selectEl.selectedOptions?.[0]?.textContent || '').trim() :
                        '';

                    selectEl.title = selectedText;
                }

                function cargarPresentacionesEnSelect(selectEl, catalogId, keepValue = null) {
                    const currentValue = keepValue ? String(keepValue) : (selectEl.value ? String(selectEl.value) : '');
                    selectEl.innerHTML = '<option value="">Seleccione presentación...</option>';

                    if (!catalogId) {
                        selectEl.disabled = true;
                        syncPresentationDetail(selectEl);
                        return;
                    }

                    const usados = getSelectedPresentationIdsByCatalog(catalogId, selectEl);
                    const lista = PRESENT[String(catalogId)] || [];

                    lista.forEach(p => {
                        const idStr = String(p.id);
                        if (usados.includes(idStr) && idStr !== currentValue) return;

                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.text;
                        if (idStr === currentValue) opt.selected = true;
                        selectEl.appendChild(opt);
                    });

                    selectEl.disabled = false;
                    syncPresentationDetail(selectEl);
                }

                function refreshAllPresentationsForCatalog(catalogId) {
                    tbody.querySelectorAll('tr.fila-presentacion').forEach(row => {
                        const inputCatalog = row.querySelector('[data-role="input-catalog-id"]');
                        const selPres = row.querySelector('[data-role="presentacion"]');
                        if (!inputCatalog || !selPres) return;
                        if (String(inputCatalog.value) !== String(catalogId)) return;

                        const cur = selPres.value || null;
                        cargarPresentacionesEnSelect(selPres, catalogId, cur);
                    });
                }

                // ===== Grupos / Filas
                function addGrupo(initialCatalogId = null) {
                    const groupId = 'g' + (groupCounter++);

                    const header = tplGrupoHeader.cloneNode(true);
                    header.id = '';
                    header.dataset.group = groupId;
                    tbody.appendChild(header);

                    const selectGenerico = header.querySelector('[data-role="generico"]');
                    const btnAddPres = header.querySelector('[data-role="add-presentacion"]');
                    const btnRemoveGrupo = header.querySelector('[data-role="remove-grupo"]');

                    refreshGenericSelect(selectGenerico, groupId, initialCatalogId ? String(initialCatalogId) : '');
                    if (initialCatalogId) selectGenerico.value = String(initialCatalogId);

                    selectGenerico.addEventListener('change', function() {
                        const catalogId = this.value;

                        tbody.querySelectorAll('tr.fila-presentacion[data-group="' + groupId + '"]').forEach(
                            row => {
                                const label = row.querySelector('[data-role="generico-label"]');
                                const inputCatalog = row.querySelector('[data-role="input-catalog-id"]');
                                const selPres = row.querySelector('[data-role="presentacion"]');

                                label.textContent = this.options[this.selectedIndex]?.text || '';
                                inputCatalog.value = catalogId;

                                selPres.value = '';
                                cargarPresentacionesEnSelect(selPres, catalogId, null);
                            });

                        refreshAllGenericSelects();
                    });

                    btnAddPres.addEventListener('click', function() {
                        if (!selectGenerico.value) {
                            alert('Selecciona primero el medicamento genérico.');
                            return;
                        }
                        addPresentacionRow(groupId, selectGenerico, null);
                    });

                    btnRemoveGrupo.addEventListener('click', function() {
                        const oldCatalogId = selectGenerico.value;

                        tbody.querySelectorAll('tr.fila-presentacion[data-group="' + groupId + '"]').forEach(
                            r => r.remove());
                        header.remove();
                        renumerarFilas();

                        refreshAllGenericSelects();
                        if (oldCatalogId) refreshAllPresentationsForCatalog(oldCatalogId);
                    });

                    refreshAllGenericSelects();
                    applyAlphabetFilter();
                    return groupId;
                }

                function addPresentacionRow(groupId, selectGenerico, presetItem = null) {
                    const row = tplPresentacionRow.cloneNode(true);
                    row.id = '';
                    row.dataset.group = groupId;

                    const labelGenerico = row.querySelector('[data-role="generico-label"]');
                    const selectPresent = row.querySelector('[data-role="presentacion"]');
                    const inputCatalog = row.querySelector('[data-role="input-catalog-id"]');
                    const inputPrecio = row.querySelector('[data-role="precio"]');
                    const btnRemoveRow = row.querySelector('[data-role="remove-row"]');

                    const catalogId = selectGenerico.value;

                    // insertar debajo del último renglón del grupo
                    let lastRowOfGroup = null;
                    tbody.querySelectorAll('tr.fila-presentacion').forEach(r => {
                        if (r.dataset.group === groupId) lastRowOfGroup = r;
                    });

                    if (lastRowOfGroup) lastRowOfGroup.insertAdjacentElement('afterend', row);
                    else {
                        const header = tbody.querySelector('tr[data-group="' + groupId + '"]');
                        header.insertAdjacentElement('afterend', row);
                    }

                    const idx = globalIndex++;
                    labelGenerico.textContent = selectGenerico.options[selectGenerico.selectedIndex]?.text || '';
                    inputCatalog.value = catalogId;

                    inputCatalog.name = `medicamentos[${idx}][catalog_id]`;
                    selectPresent.name = `medicamentos[${idx}][presentation_id]`;
                    inputPrecio.name = `medicamentos[${idx}][precio]`;

                    const presetPresentationId = presetItem?.presentation_id ? String(presetItem.presentation_id) :
                    null;
                    inputPrecio.value = (presetItem && presetItem.precio !== undefined && presetItem.precio !== null) ?
                        presetItem.precio : '';

                    cargarPresentacionesEnSelect(selectPresent, catalogId, presetPresentationId);
                    if (presetPresentationId) selectPresent.value = presetPresentationId;

                    selectPresent.addEventListener('change', function() {
                        refreshAllPresentationsForCatalog(inputCatalog.value);
                        syncPresentationDetail(this);
                    });

                    btnRemoveRow.addEventListener('click', function() {
                        const oldCatalogId = inputCatalog.value;
                        row.remove();
                        renumerarFilas();
                        if (oldCatalogId) refreshAllPresentationsForCatalog(oldCatalogId);
                        applyAlphabetFilter();
                    });

                    renumerarFilas();
                    applyAlphabetFilter();
                }

                // ===== Inicialización / old()
                document.getElementById('btn-add-grupo')?.addEventListener('click', function() {
                    addGrupo();
                });

                const SOURCE_ITEMS = (OLD_MEDS && Object.keys(OLD_MEDS).length) ? Object.values(OLD_MEDS) : [];

                if (SOURCE_ITEMS.length === 0) {
                    addGrupo();
                } else {
                    const groupsByCatalog = {};
                    const order = [];

                    SOURCE_ITEMS.forEach(item => {
                        const cid = String(item.catalog_id || '');
                        if (!cid) return;

                        if (!groupsByCatalog[cid]) {
                            groupsByCatalog[cid] = [];
                            order.push(cid);
                        }

                        groupsByCatalog[cid].push({
                            catalog_id: cid,
                            presentation_id: item.presentation_id ? String(item.presentation_id) : '',
                            precio: item.precio ?? '',
                        });
                    });

                    if (!order.length) {
                        addGrupo();
                        return;
                    }

                    order.forEach((catalogId) => {
                        const groupId = addGrupo(catalogId);

                        const header = tbody.querySelector('tr[data-group="' + groupId + '"]');
                        const selectGenerico = header.querySelector('[data-role="generico"]');

                        refreshGenericSelect(selectGenerico, groupId, catalogId);
                        selectGenerico.value = catalogId;

                        groupsByCatalog[catalogId].forEach(item => {
                            addPresentacionRow(groupId, selectGenerico, item);
                        });

                        refreshAllGenericSelects();
                        refreshAllPresentationsForCatalog(catalogId);
                    });

                    sortGroupsAlphabetically();
                }
            });
        </script>
    @endpush
</x-admin-layout>
