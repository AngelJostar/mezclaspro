<x-admin-layout>
    <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-md">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Editar Lista de Medicamentos: {{ $lista->name }}
        </h1>

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

        <form action="{{ route('admin.oncologicos.medicines.update', $lista->id) }}" method="POST"
            enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Datos generales --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la lista:</label>
                <input type="text" name="name" value="{{ old('name', $lista->name) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción:</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200">{{ old('description', $lista->description) }}</textarea>
            </div>

            {{-- Switch: Activar marcas --}}
            <div class="flex items-center">
                <input type="hidden" name="active_brands" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="active_brands" value="1" class="sr-only peer"
                        {{ old('active_brands', $lista->active_brands) ? 'checked' : '' }}>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 relative
                               after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                               after:bg-white after:border-gray-300 after:border after:rounded-full
                               after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Activar marcas</span>
                </label>
            </div>

            <div class="max-w-xs">
                <label for="charge_by" class="mb-1 block text-sm font-medium text-gray-700">Tipo de cobro</label>
                <select name="charge_by" id="charge_by"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="mg" @selected(old('charge_by', $lista->charge_by ?? 'mg') === 'mg')>Por mg</option>
                    <option value="ml" @selected(old('charge_by', $lista->charge_by ?? 'mg') === 'ml')>Por mL</option>
                    <option value="frasco" @selected(old('charge_by', $lista->charge_by ?? 'mg') === 'frasco')>Por frasco completo</option>
                </select>
            </div>

            <div class="flex items-center">
                <input type="hidden" name="show_label_lot_expiry" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="show_label_lot_expiry" value="1" class="sr-only peer"
                        {{ old('show_label_lot_expiry', $lista->show_label_lot_expiry ?? false) ? 'checked' : '' }}>
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
                            class="sr-only peer"
                            {{ old('has_mixing_service', $lista->has_mixing_service ?? false) ? 'checked' : '' }}>
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
                    class="{{ old('has_mixing_service', $lista->has_mixing_service ?? false) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio del servicio de mezclado</label>
                    <input type="number" step="0.0001" min="0" name="mixing_service_price"
                        value="{{ old('mixing_service_price', $lista->mixing_service_price ?? 0) }}"
                        class="w-full md:w-72 px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200 text-right"
                        placeholder="0.0000">
                    <p class="mt-1 text-xs text-gray-500">
                        Este importe se cobrará por mezcla en remisiones y facturación oncológica.
                    </p>
                </div>
            </div>

            @php
                $distributor = $lista->distributor ?? null;
                $distEnabledOld = (int) old('distributor_enabled', $distributor ? 1 : 0);
            @endphp

            {{-- ================= DISTRIBUIDOR ================= --}}
            <div class="border rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Distribuidor</h2>
                        <p class="text-xs text-gray-500">
                            Estos datos se usarán para la segunda hoja de remisión.
                        </p>
                    </div>

                    <button type="button" id="btn-toggle-distributor"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-2 rounded">
                        {{ $distEnabledOld ? 'Ocultar' : 'Agregar' }} distribuidor
                    </button>
                </div>

                <input type="hidden" name="distributor_enabled" id="distributor_enabled"
                    value="{{ $distEnabledOld }}">

                <div id="distributor_form" class="{{ $distEnabledOld ? '' : 'hidden' }} mt-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del distribuidor</label>
                            <input type="text" name="distributor_nombre" id="distributor_nombre"
                                value="{{ old('distributor_nombre', $distributor->nombre ?? '') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200"
                                placeholder="Ej. Distribuidora ABC">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input type="text" name="distributor_direccion" id="distributor_direccion"
                                value="{{ old('distributor_direccion', $distributor->direccion ?? '') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200"
                                placeholder="Calle, número, colonia, ciudad">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Logo (PNG/JPG)</label>
                            <input type="file" name="distributor_logo" id="distributor_logo" accept="image/*"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <p class="text-xs text-gray-500 mt-1">Si no subes uno nuevo, se conserva el actual.</p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if (!empty($lista->distributor?->logo_path))
                                <img src="{{ asset('storage/' . $lista->distributor->logo_path) }}"
                                    alt="Logo distribuidor" class="h-16 object-contain">
                            @endif

                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="distributor_delete" value="1"
                                    class="rounded border-gray-300">
                                Eliminar distribuidor
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ================= /DISTRIBUIDOR ================= --}}

            {{-- ================= TABLA DE MEDICAMENTOS ================= --}}
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
                        {{-- se llenará por JS --}}
                    </tbody>
                </table>

                <button type="button" id="btn-add-grupo"
                    class="mt-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded">
                    + Agregar medicamento
                </button>

                {{-- ========= PLANTILLAS OCULTAS ========= --}}

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
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    @php
        // Para el JS: presentaciones por catálogo => [{id, text}, ...]
        $presentacionesPorCatalogo = $catalogos->mapWithKeys(function ($c) {
            return [
                (string) $c->id => $c->presentations
                    ->map(function ($p) {
                        $marca = trim((string) ($p->marca ?? ''));
                        $pres = trim((string) ($p->presentacion ?? ''));
                        $contenidoValor = trim(rtrim(rtrim((string) ($p->contenido_valor ?? ''), '0'), '.'));
                        $contenidoUnidad = trim((string) ($p->contenido_unidad ?? ''));
                        $fabricante = trim((string) ($p->fabricante ?? ''));

                        $segmentos = array_values(array_filter([
                            $marca,
                            $pres,
                            ($contenidoValor !== '' && $contenidoUnidad !== '') ? "{$contenidoValor}{$contenidoUnidad}" : null,
                            $fabricante,
                        ], fn($value) => $value !== null && $value !== ''));

                        $text = implode(' - ', $segmentos);
                        if ($text === '') {
                            $text = $pres !== '' ? $pres : 'Presentación sin descripción';
                        }

                        return [
                            'id' => $p->id,
                            'text' => $text,
                        ];
                    })
                    ->values(),
            ];
        });
    @endphp

    @push('js')
        <script>
            // ===== Datos globales =====
            window.PRESENTACIONES_POR_CATALOGO = @json($presentacionesPorCatalogo);
            window.LISTA_ITEMS = @json($listaItems ?? []);
            window.OLD_ITEMS = @json(old('medicamentos', null));

            document.addEventListener('DOMContentLoaded', function() {

                // ===== Toggle Distribuidor =====
                const btnToggleDist = document.getElementById('btn-toggle-distributor');
                const distForm = document.getElementById('distributor_form');
                const distEnabled = document.getElementById('distributor_enabled');

                if (btnToggleDist && distForm && distEnabled) {
                    btnToggleDist.addEventListener('click', () => {
                        const isHidden = distForm.classList.contains('hidden');
                        if (isHidden) {
                            distForm.classList.remove('hidden');
                            distEnabled.value = '1';
                            btnToggleDist.textContent = 'Ocultar distribuidor';
                        } else {
                            distForm.classList.add('hidden');
                            distEnabled.value = '0';
                            btnToggleDist.textContent = 'Agregar distribuidor';
                        }
                    });
                }

                // ===== Tabla dinámica =====
                const tbody = document.getElementById('tbody-medicamentos');
                const tplGrupoHeader = document.getElementById('tpl-grupo-header');
                const tplPresentacionRow = document.getElementById('tpl-presentacion-row');

                let globalIndex = 0;
                let groupCounter = 0;

                const PRESENT = window.PRESENTACIONES_POR_CATALOGO || {};
                const PRESET_ITEMS = window.LISTA_ITEMS || [];
                const OLD_ITEMS = window.OLD_ITEMS;

                // ✅ lista maestra de genéricos (value/text) tomada del template
                const MASTER_GENERIC = (() => {
                    const tmp = tplGrupoHeader.querySelector('[data-role="generico"]');
                    return Array.from(tmp.options).map(o => ({
                        value: o.value,
                        text: o.text
                    }));
                })();

                // ===== Switch de cobro global =====
                const chargeByHidden = document.getElementById('charge_by');
                const chargeSwitch = document.getElementById('charge_by_switch');
                const chargeLabel = document.getElementById('charge_by_label');

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

                // ===== Helpers =====
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

                // --- Genéricos usados (en otros grupos) ---
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
                        const cur = sel.value || '';
                        refreshGenericSelect(sel, groupId, cur);
                    });
                }

                // --- Presentaciones usadas por catálogo (global) ---
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

                // ===== Grupos / Filas =====
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

                    if (presetItem && presetItem.precio !== undefined && presetItem.precio !== null) {
                        inputPrecio.value = presetItem.precio;
                    } else {
                        inputPrecio.value = '';
                    }

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

                // ===== Inicialización: old() tiene prioridad =====
                document.getElementById('btn-add-grupo')?.addEventListener('click', function() {
                    addGrupo();
                });

                const SOURCE_ITEMS = (OLD_ITEMS && Object.keys(OLD_ITEMS).length) ?
                    Object.values(OLD_ITEMS) :
                    (PRESET_ITEMS || []);

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
