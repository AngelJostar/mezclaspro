<x-admin-layout>
    <div class="mb-4">
        <h1 class="text-2xl font-bold">Editar Mezcla #{{ $mezcla->id }}</h1>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Se encontraron errores:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form id="formularioMezcla" action="{{ route('admin.oncologicos.mezclas.update', $mezcla->id) }}" method="POST"
        class="bg-white rounded-lg p-6 shadow">
        @csrf
        @method('PUT')

        {{-- Datos de la solicitud / paciente --}}
        <div class="flex justify-between mb-4 gap-4">
            <div class="w-1/3">
                <label for="paciente_nombre">Paciente Nombre(s)</label>
                <input type="text" name="paciente_nombre" id="paciente_nombre"
                    value="{{ old('paciente_nombre', $solicitud->nombre_paciente) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Nombre(s) del Paciente">
            </div>
            <div class="w-1/3">
                <label for="servicio">Servicio*</label>
                <input type="text" name="servicio" id="servicio" value="{{ old('servicio', $solicitud->servicio) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Servicio">
            </div>
            <div class="w-1/3">
                <label for="registro">Registro*</label>
                <input type="text" name="registro" id="registro"
                    value="{{ old('registro', $solicitud->registro_paciente) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Registro">
            </div>
        </div>

        <div class="flex justify-between mb-4 gap-4">
            <div class="w-1/5">
                <label for="sexo">Sexo</label>
                <select name="sexo" id="sexo"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Seleccione</option>
                    <option value="M" @selected(old('sexo', $solicitud->sexo) === 'M')>Masculino</option>
                    <option value="F" @selected(old('sexo', $solicitud->sexo) === 'F')>Femenino</option>
                </select>
            </div>
            <div class="w-1/5">
                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                    value="{{ old('fecha_nacimiento', $solicitud->fecha_nacimiento ? \Carbon\Carbon::parse($solicitud->fecha_nacimiento)->format('Y-m-d') : '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="w-1/5">
                <label for="peso">Peso*</label>
                <input type="text" name="peso" id="peso" value="{{ old('peso', $solicitud->peso) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Peso">
            </div>
            <div class="w-1/5">
                <label for="piso">Piso*</label>
                <input type="text" name="piso" id="piso" value="{{ old('piso', $solicitud->piso) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Piso">
            </div>
            <div class="w-1/5">
                <label for="cama">Cama*</label>
                <input type="text" name="cama" id="cama" value="{{ old('cama', $solicitud->cama) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Cama">
            </div>
        </div>

        <div class="flex justify-between mb-4 gap-4">
            <div class="w-1/4">
                <label for="diagnostico">Diagnóstico</label>
                <input type="text" name="diagnostico" id="diagnostico"
                    value="{{ old('diagnostico', $solicitud->diagnostico) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Diagnóstico">
            </div>
            <div class="w-1/4">
                <label for="medico_nombre">Nombre del Médico</label>
                <input type="text" name="medico_nombre" id="medico_nombre"
                    value="{{ old('medico_nombre', $solicitud->nombre_medico) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Nombre del Médico">
            </div>
            <div class="w-1/4">
                <label for="medico_cedula">Cédula del Médico</label>
                <input type="text" name="medico_cedula" id="medico_cedula"
                    value="{{ old('medico_cedula', $solicitud->cedula_medico) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Cédula del Médico">
            </div>
            <div class="w-1/4">
                <label for="fecha_entrega">Fecha de entrega*</label>
                <input type="datetime-local" name="fecha_entrega" id="fecha_entrega"
                    value="{{ old('fecha_entrega', $solicitud->fecha_entrega ? \Carbon\Carbon::parse($solicitud->fecha_entrega)->format('Y-m-d\TH:i') : '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="mb-4">
            <label for="observaciones">Observaciones</label>
            <input type="text" name="observaciones" id="observaciones"
                value="{{ old('observaciones', $solicitud->observaciones) }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                placeholder="Observaciones">
        </div>

        <div id="contenedorMezcla"></div>
        <input type="hidden" name="mezcla_json" id="mezcla_json">

        <div class="flex justify-end mt-4 gap-4">
            <input type="hidden" name="accion" id="accion" value="actualizar">

            <x-button type="submit" onclick="document.getElementById('accion').value='actualizar'">
                ACTUALIZAR MEZCLA
            </x-button>

            @if ($mezcla->estado === 'pendiente')
                <x-button type="submit" class="bg-green-600 hover:bg-green-700"
                    onclick="document.getElementById('accion').value='aprobar'">
                    APROBAR MEZCLA
                </x-button>
            @endif
        </div>
    </form>

    <script>
        const medicamentos = @json($medicamentos); // medicines_catalog (id=catalog_id)
        const infoAdicional = @json($infoAdicional); // indexado por catalog_id
        const mezcla = @json($mezcla); // mezcla->medicamentos trae medicamento_id legacy (medicine_oncos.id)
        const infusors = @json($infusors ?? []);
        const presentacionesPorCatalogo = @json($presentacionesPorCatalogo ?? []); // indexado por catalog_id
        const diluentPresentationsPorDiluyente = @json($diluentPresentationsPorDiluyente ?? []);
        const catalogIdPorMedicineOncoId = @json($catalogIdPorMedicineOncoId ?? []); // { onco_id : catalog_id }

        let contadorFilas = 0;

        // ===========================
        // Helper: oncoId -> catalogId
        // ===========================
        function toCatalogId(id) {
            if (!id) return null;
            return catalogIdPorMedicineOncoId?.[id] ?? id;
        }

        // ===========================
        // Helpers: requires_infusor
        // ===========================
        function requiereInfusorParaMed(catalogIdOrOncoId) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return false;

            const med = (medicamentos || []).find(m => String(m.id) === String(catalogId));
            if (med && typeof med.requires_infusor !== 'undefined') return Number(med.requires_infusor) === 1;

            const info = infoAdicional?.[catalogId];
            if (info && typeof info.requires_infusor !== 'undefined') return Number(info.requires_infusor) === 1;

            return false;
        }

        // ===========================
        // Helpers: charge_by
        // ===========================
        function getChargeBy(catalogIdOrOncoId) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return 'frasco';

            const med = (medicamentos || []).find(m => String(m.id) === String(catalogId));
            const raw = med?.charge_by ?? med?.chargeBy ?? infoAdicional?.[catalogId]?.charge_by ?? 'frasco';
            const val = String(raw || 'frasco').toLowerCase().trim();

            if (['mg', 'frasco', 'pieza'].includes(val)) return val;
            return 'frasco';
        }

        function isCobroPorMg(catalogIdOrOncoId) {
            return getChargeBy(catalogIdOrOncoId) === 'mg';
        }

        function getPrecioMgDefault(catalogIdOrOncoId) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return 0;

            const lista = presentacionesPorCatalogo?.[catalogId] || [];
            const item = lista.find(p =>
                p.precio_mg_override !== null &&
                typeof p.precio_mg_override !== 'undefined' &&
                p.precio_mg_override !== ''
            );

            return Number(item?.precio_mg_override || 0);
        }

        function getPrecioFrascoDefault(presentation) {
            return Number(presentation?.precio ?? presentation?.precio_frasco ?? 0);
        }

        function formatPrice(value, decimals = 4) {
            const number = Number(value || 0);
            return Number.isFinite(number) ? number.toFixed(decimals) : Number(0).toFixed(decimals);
        }

        function toggleUIByChargeBy(filaId, catalogIdOrOncoId) {
            const fila = document.getElementById(`fila_${filaId}`);
            if (!fila) return;

            const btnConfig = fila.querySelector('[data-action="toggle-presentaciones"]');
            const wrap = document.getElementById(`presentaciones_wrap_${filaId}`);
            const precioMgInput = fila.querySelector('[data-name="precio_mg"]');
            const precioHelp = fila.querySelector('[data-name="precio_help"]');

            if (isCobroPorMg(catalogIdOrOncoId)) {
                if (btnConfig) btnConfig.classList.add('hidden');
                if (wrap) wrap.classList.add('hidden');
                if (precioMgInput) {
                    precioMgInput.disabled = false;
                    precioMgInput.classList.remove('bg-gray-100', 'text-gray-400');
                    if (precioMgInput.value === '') {
                        precioMgInput.value = formatPrice(getPrecioMgDefault(catalogIdOrOncoId));
                    }
                }
                if (precioHelp) precioHelp.textContent = 'Precio por mg editable para esta mezcla.';
            } else {
                if (btnConfig) btnConfig.classList.remove('hidden');
                if (precioMgInput) {
                    precioMgInput.value = '';
                    precioMgInput.disabled = true;
                    precioMgInput.classList.add('bg-gray-100', 'text-gray-400');
                }
                if (precioHelp) precioHelp.textContent = 'El precio se edita por frasco en presentaciones.';
            }
        }

        // ===========================
        // Helpers: Stock (presentaciones)
        // ===========================
        function presentacionTieneStock(p) {
            // Regla de stock para UI:
            // - debe existir batch_id
            // - y debe traer lote y caducidad (porque tu stock badge depende de eso)
            return !!(p && p.batch_id && String(p.lote || '').trim() !== '' && String(p.caducidad || '').trim() !== '');
        }

        function catalogTieneAlgunaPresentacionConStock(catalogIdOrOncoId) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return false;
            const lista = presentacionesPorCatalogo?.[catalogId] || [];
            return lista.some(p => presentacionTieneStock(p));
        }

        // ===========================
        // Helpers Infusor vs Set
        // ===========================
        function mezclaAdmiteInfusor() {
            const filas = document.querySelectorAll('#medicamentos_mezcla tr');
            for (const fila of filas) {
                const medSel = fila.querySelector('[data-name="medicamento"]') || fila.querySelector('.medicamento-select');
                if (medSel && medSel.value && requiereInfusorParaMed(medSel.value)) return true;
            }
            return false;
        }

        function updateInfusorDisponibilidad() {
            const setCb = document.querySelector('[data-name="set_infusion"]');
            const selInf = document.querySelector('[data-name="infusor_id"]');
            if (!setCb || !selInf) return;

            const admite = mezclaAdmiteInfusor();

            if (setCb.checked) {
                selInf.value = '';
                selInf.disabled = true;
                return;
            }
            selInf.disabled = !admite;
            if (!admite) selInf.value = '';
        }

        function toggleSetInfusion(checkbox) {
            const selInf = document.querySelector('[data-name="infusor_id"]');
            if (!selInf) return;
            if (checkbox.checked) {
                selInf.value = '';
                selInf.disabled = true;
            } else {
                selInf.disabled = !mezclaAdmiteInfusor();
            }
        }

        function toggleInfusorSelect(select) {
            const setCb = document.querySelector('[data-name="set_infusion"]');
            if (!setCb) return;

            if (select.value) {
                setCb.checked = false;
                setCb.disabled = true;
            } else {
                setCb.disabled = false;
            }
        }

        // ===========================
        // Presentaciones por medicamento
        // ===========================
        function onMedicamentoChange(selectElem) {
            const filaId = parseInt(selectElem.closest('tr').id.replace('fila_', ''), 10) || 0;
            const catalogId = toCatalogId(selectElem.value);

            toggleUIByChargeBy(filaId, catalogId);

            // ✅ Si NO es mg, exigir stock para permitir seleccionar el medicamento
            if (!isCobroPorMg(catalogId)) {
                const okStock = catalogTieneAlgunaPresentacionConStock(catalogId);

                if (!okStock) {
                    // reset selección y UI
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin stock',
                        text: 'Este medicamento no tiene presentaciones con stock (lote y caducidad). Primero carga inventario en este laboratorio.'
                    });

                    // Limpia selección y presentaciones
                    selectElem.value = '';
                    const wrap = document.getElementById(`presentaciones_wrap_${filaId}`);
                    const tbody = document.getElementById(`presentaciones_body_${filaId}`);
                    if (tbody) tbody.innerHTML = '';
                    if (wrap) wrap.classList.add('hidden');
                } else {
                    inicializarPresentacionesFila(filaId, catalogId);
                    recalcularResumenPresentaciones(filaId);
                }
            }

            updateInfusorDisponibilidad();
            updateDiluentPresentationSelector();
            autoSelectDiluentPresentationForVolume();
        }

        function togglePresentaciones(filaId) {
            const wrap = document.getElementById(`presentaciones_wrap_${filaId}`);
            if (!wrap) return;
            wrap.classList.toggle('hidden');
        }

        function inicializarPresentacionesFila(filaId, catalogIdOrOncoId, presentacionesGuardadas = []) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return;

            const lista = presentacionesPorCatalogo?.[catalogId] || [];
            const tbody = document.getElementById(`presentaciones_body_${filaId}`);
            if (!tbody) return;

            tbody.innerHTML = "";

            lista.forEach(p => {
                const batches = p.batches || [];
                const hasStock = batches.length > 0;

                const guardada = (presentacionesGuardadas || []).find(g =>
                    Number(g.medicine_presentation_id) === Number(p.id) ||
                    Number(g.batch?.medicine_presentation_id) === Number(p.id)
                );

                const selectedBatchId = guardada?.medicine_batch_id || batches[0]?.id || '';
                const frascosValue = guardada ? Number(guardada.unidades_usadas || 0) : 0;
                const precioFrascoValue = guardada ?
                    Number(guardada.precio_frasco_snapshot || 0) :
                    getPrecioFrascoDefault(p);

                const batchOptions = batches.map(b => {
                    const selected = Number(b.id) === Number(selectedBatchId) ? 'selected' : '';
                    const cad = b.caducidad ? new Date(b.caducidad).toLocaleDateString() : '—';

                    return `
                <option value="${b.id}"
                    ${selected}
                    data-lote="${b.lote ?? ''}"
                    data-caducidad="${b.caducidad ?? ''}"
                    data-caducidad-text="${cad}"
                    data-stock="${b.stock_actual ?? 0}">
                    ${b.lote ?? 'Sin lote'} · Cad. ${cad} · Stock ${b.stock_actual ?? 0}
                </option>
            `;
                }).join('');

                const selectedBatch = batches.find(b => Number(b.id) === Number(selectedBatchId)) || batches[0] ||
                    null;
                const cadTxt = selectedBatch?.caducidad ? new Date(selectedBatch.caducidad).toLocaleDateString() :
                    '—';

                const stockBadge = hasStock ?
                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800">Con stock</span>` :
                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800">Sin stock</span>`;

                const tr = document.createElement('tr');
                tr.classList.add('presentacion-row');
                tr.dataset.presentationId = p.id;
                tr.dataset.cantidadMg = p.cantidad_medicamento || 0;
                tr.dataset.marca = p.marca || '';
                tr.dataset.batchId = selectedBatch?.id || selectedBatchId || '';
                tr.dataset.hasStock = hasStock ? '1' : '0';

                tr.innerHTML = `
            <td class="border px-1 py-1">${p.presentacion ?? '—'}</td>
            <td class="border px-1 py-1">${p.marca ?? '—'}</td>
            <td class="border px-1 py-1 text-right">${p.cantidad_medicamento ?? '—'}</td>
            <td class="border px-1 py-1 text-right">${p.volumen_diluyente ?? '—'}</td>
            <td class="border px-1 py-1">
                <input type="number" min="0" step="0.0001"
                    class="w-24 border rounded px-1 py-0.5 text-right input-precio-frasco ${hasStock ? '' : 'bg-gray-100 text-gray-400'}"
                    value="${hasStock ? formatPrice(precioFrascoValue) : formatPrice(0)}"
                    ${hasStock ? '' : 'disabled'}
                    title="${hasStock ? 'Precio por frasco' : 'Sin stock'}">
            </td>
            <td class="border px-1 py-1">
                ${
                    hasStock
                        ? `<select class="batch-select w-full border rounded px-1 py-0.5 text-[11px]"
                                                        onchange="actualizarBatchPresentacion(this, ${filaId})">
                                                        ${batchOptions}
                                                   </select>`
                        : '—'
                }
            </td>
            <td class="border px-1 py-1 batch-caducidad">${cadTxt}</td>
            <td class="border px-1 py-1 text-center">
                ${stockBadge}
                <div class="text-[10px] text-gray-500 batch-stock">
                    ${selectedBatch?.stock_actual ?? 0} frascos
                </div>
            </td>
            <td class="border px-1 py-1">
                <input type="number" min="0" step="1"
                    class="w-16 border rounded px-1 py-0.5 text-right input-frascos ${hasStock ? '' : 'bg-gray-100 text-gray-400'}"
                    value="${hasStock ? frascosValue : 0}"
                    ${hasStock ? '' : 'disabled'}
                    title="${hasStock ? 'Disponible' : 'Sin stock'}"
                    oninput="recalcularResumenPresentaciones(${filaId})">
            </td>
        `;

                tbody.appendChild(tr);
            });

            recalcularResumenPresentaciones(filaId);
        }


        function actualizarBatchPresentacion(select, filaId) {
            const row = select.closest('.presentacion-row');
            const option = select.selectedOptions[0];

            if (!row || !option) return;

            row.dataset.batchId = option.value;

            const caducidad = row.querySelector('.batch-caducidad');
            const stock = row.querySelector('.batch-stock');

            if (caducidad) {
                caducidad.textContent = option.dataset.caducidadText || '—';
            }

            if (stock) {
                stock.textContent = `${option.dataset.stock || 0} frascos`;
            }

            recalcularResumenPresentaciones(filaId);
        }

        function recalcularResumenPresentaciones(filaId) {
            const fila = document.getElementById(`fila_${filaId}`);
            if (!fila) return;

            const dosisInput = fila.querySelector('.dosis-input');
            const objetivo = parseFloat(dosisInput?.value || "0");

            const rows = fila.querySelectorAll('.presentacion-row');
            let aportada = 0;

            rows.forEach(r => {
                const hasStock = String(r.dataset.hasStock || '0') === '1';
                if (!hasStock) return;

                const mgPorFrasco = parseFloat(r.dataset.cantidadMg || "0");
                const frascos = parseFloat(r.querySelector('.input-frascos')?.value || "0");
                aportada += mgPorFrasco * frascos;
            });

            const resumen = document.getElementById(`resumen_dosis_${filaId}`);
            if (resumen) {
                const diff = objetivo - aportada;
                const textoDiff = diff > 0 ? `faltan ${diff.toFixed(2)} mg` :
                    diff < 0 ? `sobran ${Math.abs(diff).toFixed(2)} mg` :
                    'dosis exacta';

                resumen.innerHTML = `
                Dosis objetivo: ${objetivo.toFixed(2)} mg<br>
                Dosis aportada: ${aportada.toFixed(2)} mg (${textoDiff})
            `;
            }
        }

        // ===========================
        // Diluyente común + presentaciones de diluyente
        // ===========================
        function getDiluyenteComunId() {
            const filas = document.querySelectorAll('#medicamentos_mezcla tr');
            let diluyenteComun = null;

            for (const fila of filas) {
                const selDil = fila.querySelector('[data-name="diluyente"]');
                if (!selDil || !selDil.value) continue;

                if (diluyenteComun === null) {
                    diluyenteComun = selDil.value;
                } else if (String(diluyenteComun) !== String(selDil.value)) {
                    return null;
                }
            }
            return diluyenteComun;
        }

        function updateDiluentPresentationSelector(preservedId = null) {
            const select = document.querySelector('[data-name="diluent_presentation_id"]');
            const hint = document.getElementById('diluent_presentation_hint');
            if (!select) return;

            const dilId = getDiluyenteComunId();

            select.innerHTML = '';
            if (!dilId || !diluentPresentationsPorDiluyente?.[dilId]) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Seleccione un diluyente (y su presentación) para esta mezcla';
                select.appendChild(opt);

                if (hint) hint.textContent = 'Se sugerirá una presentación en función del volumen de dilución.';
                return;
            }

            const lista = diluentPresentationsPorDiluyente[dilId] || [];

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Seleccione una presentación';
            select.appendChild(placeholder);

            lista.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;

                let txt = `${p.presentacion} (${p.volume_ml} mL`;
                if (p.denominacion_comercial) txt += ` · ${p.denominacion_comercial}`;
                if (p.lote) txt += ` · Lote ${p.lote}`;
                if (p.caducidad) txt += ` · Cad. ${p.caducidad}`;
                txt += ')';

                opt.textContent = txt;
                select.appendChild(opt);
            });

            if (preservedId) {
                select.value = String(preservedId);

                if (hint) {
                    const optSel = select.options[select.selectedIndex];
                    hint.textContent = (optSel && optSel.value) ?
                        `Presentación guardada previamente: ${optSel.textContent}.` :
                        'Se sugerirá una presentación en función del volumen de dilución.';
                }
            }
        }

        function autoSelectDiluentPresentationForVolume() {
            const select = document.querySelector('[data-name="diluent_presentation_id"]');
            const hint = document.getElementById('diluent_presentation_hint');
            const volumenInput = document.querySelector('[data-name="volumen_dilucion"]');
            if (!select || !volumenInput) return;

            const dilId = getDiluyenteComunId();
            if (!dilId) return;

            const lista = diluentPresentationsPorDiluyente?.[dilId] || [];
            if (!lista.length) return;

            const vol = parseFloat(volumenInput.value || '0');
            if (!vol || vol <= 0) return;

            const sortedByBestFit = [...lista].sort((a, b) => {
                const aVolume = parseFloat(a.volume_ml || '0');
                const bVolume = parseFloat(b.volume_ml || '0');
                if (aVolume !== bVolume) return aVolume - bVolume;

                const aCad = a.caducidad ? new Date(a.caducidad).getTime() : Number.MAX_SAFE_INTEGER;
                const bCad = b.caducidad ? new Date(b.caducidad).getTime() : Number.MAX_SAFE_INTEGER;
                return aCad - bCad;
            });

            let elegida = null;

            sortedByBestFit.forEach(p => {
                const v = parseFloat(p.volume_ml || '0');
                if (!v || v <= 0) return;
                if (v >= vol && !elegida) {
                    elegida = {
                        ...p,
                        volume_ml: v
                    };
                }
            });

            if (!elegida) {
                sortedByBestFit.forEach(p => {
                    const v = parseFloat(p.volume_ml || '0');
                    if (!v || v <= 0) return;
                    if (!elegida) {
                        elegida = {
                            ...p,
                            volume_ml: v
                        };
                    }
                });
            }

            if (elegida) {
                select.value = String(elegida.id);
                if (hint) {
                    hint.textContent =
                        `Sugerida: ${elegida.presentacion} (${elegida.volume_ml} mL) ` +
                        `para un volumen de dilución de ${vol} mL. Puedes cambiarla si lo requieres.`;
                }
            }
        }

        // ===========================
        // Render
        // ===========================
        function renderMezcla(mezclaData) {
            const mezclaDiv = document.createElement('div');
            mezclaDiv.classList.add("border", "border-black", "p-4", "relative");

            const firstDiluentId = (mezclaData.medicamentos && mezclaData.medicamentos.length > 0) ?
                mezclaData.medicamentos[0].diluyente_id : null;

            const selectedDiluentPresId = mezclaData.diluent_presentation_id || null;

            let opcionesPresentacionDiluyente = '<option value="">Selecciona presentación</option>';

            if (firstDiluentId && diluentPresentationsPorDiluyente?.[firstDiluentId]) {
                diluentPresentationsPorDiluyente[firstDiluentId].forEach(p => {
                    const seleccionado = Number(selectedDiluentPresId) === Number(p.id) ? 'selected' : '';
                    const cad = p.caducidad ? (new Date(p.caducidad)).toLocaleDateString() : '—';

                    opcionesPresentacionDiluyente += `
                    <option value="${p.id}" ${seleccionado}>
                        ${p.presentacion}
                        ${p.denominacion_comercial ? ' · ' + p.denominacion_comercial : ''}
                        ${p.lote ? ' · Lote ' + p.lote : ''}
                        ${cad !== '—' ? ' · Cad. ' + cad : ''}
                    </option>
                `;
                });
            }

            mezclaDiv.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Mezcla</h3>

            <table class="table-auto w-full border text-center mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1 text-xs">MEDICAMENTO</th>
                        <th class="border px-2 py-1 text-xs">DOSIS</th>
                        <th class="min-w-20 border px-2 py-1 text-xs">UNIDAD</th>
                        <th class="border px-2 py-1 text-xs">DILUYENTE</th>
                        <th class="border px-2 py-1 text-xs">VÍA DE ADMINISTRACIÓN</th>
                    </tr>
                </thead>
                <tbody id="medicamentos_mezcla"></tbody>
            </table>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label>Volumen de dilución (ml)*</label>
                    <input type="number" step="0.01" data-name="volumen_dilucion"
                        class="w-full border rounded px-2 py-1 text-sm"
                        value="${mezclaData.volumen_dilucion ?? ''}">
                </div>
                <div>
                    <label>Tiempo de infusión (min)*</label>
                    <input type="number" data-name="tiempo_infusion"
                        class="w-full border rounded px-2 py-1 text-sm"
                        value="${mezclaData.tiempo_infusion ?? ''}">
                </div>
            </div>

            <div class="mb-4">
                <label>Presentación del diluyente</label>
                <select data-name="diluent_presentation_id"
                    class="w-full border rounded px-2 py-1 text-sm">
                    ${opcionesPresentacionDiluyente}
                </select>
                <p class="text-xs text-gray-500" id="diluent_presentation_hint">
                    Se sugerirá en función del volumen de dilución de la mezcla.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox"
                        data-name="set_infusion"
                        class="w-4 h-4"
                        ${mezclaData.set_infusion ? 'checked' : ''}
                        onchange="toggleSetInfusion(this)">
                    <label class="select-none">Set de infusión</label>
                </div>
                <div>
                    <label>Infusor</label>
                    <select data-name="infusor_id"
                        class="w-full border rounded px-2 py-1 text-sm"
                        onchange="toggleInfusorSelect(this)">
                        <option value="">Selecciona infusor</option>
                        ${(infusors || []).map(i => `
                                                        <option value="${i.id}"
                                                            ${Number(mezclaData.infusor_id || '') === Number(i.id) ? 'selected' : ''}>
                                                            ${i.nombre_generico ?? i.nombre_comercial ?? ('Infusor #'+i.id)}
                                                        </option>
                                                    `).join('')}
                    </select>
                    <p class="text-xs text-gray-500">
                        Se habilita si la mezcla contiene medicamento(s) que admiten infusor.
                    </p>
                </div>
            </div>

            <button type="button"
                class="btn-agregar-medicamento bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1 px-2 rounded">
                + Agregar Medicamento
            </button>
        `;

            document.getElementById('contenedorMezcla').innerHTML = '';
            document.getElementById('contenedorMezcla').appendChild(mezclaDiv);

            const tbody = mezclaDiv.querySelector('#medicamentos_mezcla');

            (mezclaData.medicamentos || []).forEach(med => {
                contadorFilas++;
                const fila = document.createElement('tr');
                fila.id = `fila_${contadorFilas}`;

                const oncoId = med.medicamento_id; // legacy onco id
                const catalogId = toCatalogId(oncoId); // catalog_id UI
                const data = infoAdicional?.[catalogId] || {
                    diluyentes: [],
                    vias: []
                };

                const diluyenteOptions = (data.diluyentes || []).map(d =>
                    `<option value="${d.id}" ${String(d.id) === String(med.diluyente_id) ? 'selected' : ''}>
                    ${d.name ?? d.denominacion_generica ?? '—'}
                 </option>`
                ).join('');

                const viaOptions = (data.vias || []).map(v =>
                    `<option value="${v.id}" ${String(v.id) === String(med.via_administracion_id) ? 'selected' : ''}>
                    ${v.name}
                 </option>`
                ).join('');

                const chargeBy = (med.charge_by || med.chargeBy || getChargeBy(catalogId));
                const precioMgValue = med.precio_mg_snapshot ?? getPrecioMgDefault(catalogId);
                const presentacionesGuardadas = med.presentaciones_usadas || med.presentacionesUsadas || [];

                fila.innerHTML = `
                <td class="border align-top">
                    <select class="medicamento-select w-full border px-2 py-1 text-sm"
                        data-name="medicamento"
                        name="medicamento_existente[]"
                        onchange="actualizarDiluentesYVias(this, ${contadorFilas}); onMedicamentoChange(this)">
                        ${medicamentos.map(m =>
                            `<option value="${m.id}" ${String(m.id) === String(catalogId) ? 'selected' : ''}>
                                                            ${m.denominacion}
                                                         </option>`
                        ).join('')}
                    </select>

                    <div class="mt-2 text-left flex items-center gap-2">
                        <span class="text-[11px] text-gray-500">
                            Cobro: <span class="font-semibold">${String(chargeBy).toUpperCase()}</span>
                        </span>

                        <button type="button"
                            data-action="toggle-presentaciones"
                            class="text-xs text-blue-600 underline"
                            onclick="togglePresentaciones(${contadorFilas})">
                            Configurar presentaciones
                        </button>
                    </div>
                </td>

                <td class="border align-top">
                    <input type="number"
                        name="dosis_existente[]"
                        value="${med.dosis ?? ''}"
                        class="w-full border px-2 py-1 text-sm dosis-input"
                        oninput="recalcularResumenPresentaciones(${contadorFilas})">

                    <div class="mt-1 text-sm text-red-600" id="resumen_dosis_${contadorFilas}">
                        Dosis objetivo: ${Number(med.dosis || 0).toFixed(2)} mg<br>
                        Dosis aportada: 0 mg
                    </div>
                    <input type="hidden"
                        data-name="precio_mg"
                        value="${String(chargeBy).toLowerCase() === 'mg' ? formatPrice(precioMgValue) : ''}">
                </td>

                <td class="min-w-20 whitespace-nowrap border px-3 py-2 align-top text-xs font-semibold text-gray-700">
                    MG
                </td>

                <td class="border align-top">
                    <select name="diluyente_existente[]"
                        data-name="diluyente"
                        class="w-full border px-2 py-1 text-sm"
                        onchange="onDiluyenteChange(this)">
                        <option value="">Diluyentes</option>
                        ${diluyenteOptions}
                    </select>
                </td>

                <td class="border align-top">
                    <select name="via_existente[]"
                        data-name="via_administracion"
                        class="w-full border px-2 py-1 text-sm">
                        <option value="">Vía de admin</option>
                        ${viaOptions}
                    </select>

                    <div id="presentaciones_wrap_${contadorFilas}"
                        class="mt-2 border-t pt-2 hidden">
                        <div class="text-xs font-semibold mb-1">Presentaciones disponibles</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-[11px] border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-1 py-1">Presentación</th>
                                        <th class="border px-1 py-1">Marca</th>
                                        <th class="border px-1 py-1">Cant. (mg)</th>
                                        <th class="border px-1 py-1">Vol (mL)</th>
                                        <th class="border px-1 py-1">Precio frasco</th>
                                        <th class="border px-1 py-1">Lote</th>
                                        <th class="border px-1 py-1">Caducidad</th>
                                        <th class="border px-1 py-1">Stock</th>
                                        <th class="border px-1 py-1">Frascos</th>
                                    </tr>
                                </thead>
                                <tbody id="presentaciones_body_${contadorFilas}"></tbody>
                            </table>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            Solo puedes capturar frascos en presentaciones <b>con stock</b> (lote + caducidad).
                        </p>
                    </div>
                </td>
            `;

                tbody.appendChild(fila);

                // Inicializaciones
                toggleUIByChargeBy(contadorFilas, catalogId);

                if (!isCobroPorMg(catalogId)) {
                    // Si el medicamento guardado ya no tiene stock, se mostrará pero inputs deshabilitados
                    inicializarPresentacionesFila(contadorFilas, catalogId, presentacionesGuardadas);
                }

            });

            const setCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');

            if (selInf && selInf.value) {
                setCb.checked = false;
                setCb.disabled = true;
            }
            if (setCb && setCb.checked) {
                if (selInf) {
                    selInf.value = '';
                    selInf.disabled = true;
                }
            }

            const savedDilPresId = mezclaData.diluent_presentation_id || null;
            updateDiluentPresentationSelector(savedDilPresId);
            if (!savedDilPresId) autoSelectDiluentPresentationForVolume();

            const volInput = mezclaDiv.querySelector('[data-name="volumen_dilucion"]');
            if (volInput) {
                volInput.addEventListener('input', () => autoSelectDiluentPresentationForVolume());
            }

            updateInfusorDisponibilidad();
        }

        function actualizarDiluentesYVias(selectElem, filaId) {
            const catalogId = toCatalogId(selectElem.value);
            const fila = document.getElementById(`fila_${filaId}`);
            const data = infoAdicional?.[catalogId] || {
                diluyentes: [],
                vias: []
            };

            const selectDiluyente = fila.querySelector('[data-name="diluyente"]');
            const selectVia = fila.querySelector('[data-name="via_administracion"]');

            selectDiluyente.innerHTML =
                `<option value="">Diluyentes</option>` +
                (data.diluyentes || []).map(d =>
                    `<option value="${d.id}">${d.name ?? d.denominacion_generica ?? '—'}</option>`
                ).join('');

            selectVia.innerHTML =
                `<option value="">Vía de admin</option>` +
                (data.vias || []).map(v =>
                    `<option value="${v.id}">${v.name}</option>`
                ).join('');

            updateInfusorDisponibilidad();
            updateDiluentPresentationSelector();
            autoSelectDiluentPresentationForVolume();
        }

        function onDiluyenteChange() {
            updateDiluentPresentationSelector();
            autoSelectDiluentPresentationForVolume();
        }

        document.addEventListener("DOMContentLoaded", () => {
            renderMezcla(mezcla);
        });

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("btn-agregar-medicamento")) {
                const tbody = document.querySelector("#medicamentos_mezcla");
                contadorFilas++;
                const fila = document.createElement("tr");
                fila.id = `fila_${contadorFilas}`;

                fila.innerHTML = `
                <td class="border align-top">
                    <select class="medicamento-select w-full border px-2 py-1 text-sm"
                        data-name="medicamento"
                        name="nuevo_medicamento[]"
                        onchange="actualizarDiluentesYVias(this, ${contadorFilas}); onMedicamentoChange(this)">
                        <option value="">Seleccione</option>
                        ${medicamentos.map(m => `<option value="${m.id}">${m.denominacion}</option>`).join('')}
                    </select>

                    <div class="mt-2 text-left">
                        <button type="button"
                            data-action="toggle-presentaciones"
                            class="text-xs text-blue-600 underline"
                            onclick="togglePresentaciones(${contadorFilas})">
                            Configurar presentaciones
                        </button>
                    </div>
                </td>

                <td class="border align-top">
                    <input type="number"
                        name="nueva_dosis[]"
                        class="w-full border px-2 py-1 text-sm dosis-input"
                        oninput="recalcularResumenPresentaciones(${contadorFilas})">
                    <div class="mt-1 text-xs text-gray-600" id="resumen_dosis_${contadorFilas}">
                        Dosis objetivo: 0 mg<br>
                        Dosis aportada: 0 mg
                    </div>
                    <input type="hidden" data-name="precio_mg" value="">
                </td>

                <td class="min-w-20 whitespace-nowrap border px-3 py-2 align-top text-xs font-semibold text-gray-700">
                    MG
                </td>

                <td class="border align-top">
                    <select name="nuevo_diluyente[]"
                        data-name="diluyente"
                        class="w-full border px-2 py-1 text-sm"
                        onchange="onDiluyenteChange(this)">
                        <option value="">Diluyentes</option>
                    </select>
                </td>

                <td class="border align-top">
                    <select name="nueva_via[]"
                        data-name="via_administracion"
                        class="w-full border px-2 py-1 text-sm">
                        <option value="">Vía de admin</option>
                    </select>

                    <div id="presentaciones_wrap_${contadorFilas}"
                        class="mt-2 border-t pt-2 hidden">
                        <div class="text-xs font-semibold mb-1">Presentaciones disponibles</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-[11px] border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-1 py-1">Presentación</th>
                                        <th class="border px-1 py-1">Marca</th>
                                        <th class="border px-1 py-1">Cant. (mg)</th>
                                        <th class="border px-1 py-1">Vol (mL)</th>
                                        <th class="border px-1 py-1">Precio frasco</th>
                                        <th class="border px-1 py-1">Lote</th>
                                        <th class="border px-1 py-1">Caducidad</th>
                                        <th class="border px-1 py-1">Stock</th>
                                        <th class="border px-1 py-1">Frascos</th>
                                    </tr>
                                </thead>
                                <tbody id="presentaciones_body_${contadorFilas}"></tbody>
                            </table>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            Solo puedes capturar frascos en presentaciones <b>con stock</b> (lote + caducidad).
                        </p>
                    </div>
                </td>
            `;

                tbody.appendChild(fila);

                updateInfusorDisponibilidad();
                updateDiluentPresentationSelector();
                autoSelectDiluentPresentationForVolume();
            }
        });

        // ===========================
        // Submit: mezcla_json + validaciones de stock
        // ===========================
        document.getElementById("formularioMezcla").addEventListener("submit", function(e) {
            e.preventDefault();

            const setCb = document.querySelector('[data-name="set_infusion"]');
            const selInf = document.querySelector('[data-name="infusor_id"]');
            const selDilPres = document.querySelector('[data-name="diluent_presentation_id"]');

            const mezclaPayload = {
                volumen_dilucion: document.querySelector('[data-name="volumen_dilucion"]').value,
                tiempo_infusion: document.querySelector('[data-name="tiempo_infusion"]').value,
                set_infusion: !!(setCb && setCb.checked),
                infusor_id: (selInf && selInf.value) ? selInf.value : null,
                diluent_presentation_id: (selDilPres && selDilPres.value) ? selDilPres.value : null,
                medicamentos: []
            };

            function buildPresentacionesFromFila(fila) {
                const presRows = fila.querySelectorAll('.presentacion-row');
                const arr = [];
                presRows.forEach(r => {
                    const hasStock = String(r.dataset.hasStock || '0') === '1';
                    const frascos = parseFloat(r.querySelector('.input-frascos')?.value || "0");
                    const precioFrasco = r.querySelector('.input-precio-frasco')?.value || '';
                    const batchSelect = r.querySelector('.batch-select');
                    const batchId = batchSelect?.value || r.dataset.batchId || null;

                    // ✅ si no hay stock, forzar 0 (por seguridad)
                    if (!hasStock) {
                        if (frascos > 0) r.querySelector('.input-frascos').value = 0;
                        return;
                    }

                    if (frascos > 0 && batchId) {
                        arr.push({
                            batch_id: batchId,
                            presentation_id: r.dataset.presentationId,
                            marca: r.dataset.marca || '',
                            frascos: frascos,
                            precio_frasco: precioFrasco
                        });
                    }
                });
                return arr;
            }

            // Recolectar existentes + nuevos de forma uniforme
            const allRows = [];

            document.querySelectorAll('#medicamentos_mezcla tr').forEach(tr => {
                const selMed = tr.querySelector('[data-name="medicamento"]');
                if (!selMed || !selMed.value) return;

                const catalogId = toCatalogId(selMed.value);
                const dosisInput = tr.querySelector('.dosis-input');
                const dosis = dosisInput ? dosisInput.value : '';

                const selDil = tr.querySelector('[data-name="diluyente"]');
                const selVia = tr.querySelector('[data-name="via_administracion"]');

                allRows.push({
                    tr,
                    catalogId,
                    nombre: selMed.options[selMed.selectedIndex]?.text || '',
                    dosis: dosis,
                    precio_mg: tr.querySelector('[data-name="precio_mg"]')?.value || '',
                    diluyente_id: selDil?.value || null,
                    via_administracion_id: selVia?.value || null,
                });
            });

            // ✅ Validación de stock (solo no-mg)
            for (const row of allRows) {
                const chargeBy = getChargeBy(row.catalogId);

                const medObj = {
                    medicamento_id: row.catalogId,
                    nombre: row.nombre,
                    dosis: row.dosis,
                    precio_mg: row.precio_mg,
                    diluyente_id: row.diluyente_id,
                    via_administracion_id: row.via_administracion_id,
                    charge_by: chargeBy,
                    presentaciones: []
                };

                if (chargeBy !== 'mg') {
                    // Exigir al menos una presentación con frascos > 0
                    medObj.presentaciones = buildPresentacionesFromFila(row.tr);

                    const totalFrascos = medObj.presentaciones.reduce((acc, p) => acc + Number(p.frascos || 0), 0);

                    if (totalFrascos <= 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Falta seleccionar stock',
                            text: `Para "${row.nombre}" debes seleccionar al menos 1 frasco en una presentación con stock.`
                        });
                        return;
                    }
                }

                mezclaPayload.medicamentos.push(medObj);
            }

            // Validación: mismo diluyente y vía (si hay medicamentos)
            if (mezclaPayload.medicamentos.length > 0) {
                const refDil = String(mezclaPayload.medicamentos[0].diluyente_id ?? '');
                const refVia = String(mezclaPayload.medicamentos[0].via_administracion_id ?? '');

                for (const med of mezclaPayload.medicamentos) {
                    if (String(med.diluyente_id ?? '') !== refDil || String(med.via_administracion_id ?? '') !==
                        refVia) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Todos los medicamentos de la mezcla deben tener el mismo diluyente y la misma vía de administración.'
                        });
                        return;
                    }
                }
            }


            for (const med of mezclaPayload.medicamentos) {
                if (!med.presentaciones || med.presentaciones.length === 0) continue;

                const marcas = [...new Set(
                    med.presentaciones
                    .map(p => String(p.marca || '').trim().toLowerCase())
                    .filter(Boolean)
                )];

                if (marcas.length > 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Marcas diferentes',
                        text: `Para "${med.nombre}" solo puedes seleccionar frascos de la misma marca.`
                    });
                    return;
                }
            }
            document.getElementById("mezcla_json").value = JSON.stringify(mezclaPayload);

            Swal.fire({
                title: '¿Actualizar mezcla?',
                text: 'Se guardarán los cambios realizados.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) e.target.submit();
            });
        });

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error al actualizar',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            });
        @endif

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            });
        @endif
    </script>

</x-admin-layout>
