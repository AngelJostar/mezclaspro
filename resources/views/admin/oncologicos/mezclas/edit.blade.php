<x-admin-layout>
    @php
        $requestType = $solicitud->tipo_solicitud ?? 'oncologicos';
        $closeRoute = $requestType === 'antibioticos'
            ? 'admin.antibioticos.solicitudes.index'
            : 'admin.oncologicos.solicitudes.index';
        $isDispensingMode = request('modo') === 'dispensacion';
        $isPendingApproval = ($mezcla->estado ?? 'pendiente') === 'pendiente';
        $isApprovalMode = request()->boolean('approval') || (!$isDispensingMode && $isPendingApproval);
        $isWorkflowPopup = request()->boolean('approval_popup') || request()->boolean('dispensing_popup');
        $returnTo = old('return_to', request('return_to', ''));
        $closeHref = $returnTo !== '' ? $returnTo : route($closeRoute);
        $formTitle = $isDispensingMode
            ? 'Dispensar Mezcla'
            : ($isApprovalMode ? 'Aprobación de mezcla' : 'Editar Mezcla');
    @endphp

    <div class="mb-4 flex items-start justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <h1 data-workflow-heading class="mixture-workflow-heading">{{ $formTitle }} #{{ $mezcla->id }} | {{ \App\Support\MixtureWorkflowContext::destination($solicitud->hospital ?? null) }}</h1>
            @if ($isApprovalMode && $isPendingApproval)
                <x-button form="formularioMezcla" type="submit" class="bg-green-600 hover:bg-green-700"
                    onclick="document.getElementById('accion').value='aprobar'">
                    APROBAR MEZCLA
                </x-button>
                <x-button form="formularioMezcla" type="submit" formnovalidate class="bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:ring-red-500"
                    onclick="document.getElementById('accion').value='rechazar'">
                    RECHAZAR MEZCLA
                </x-button>
            @endif
        </div>
        <a href="{{ $closeHref }}"
            @if ($isWorkflowPopup) data-workflow-popup-close @endif
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border-2 border-red-600 text-2xl font-semibold leading-none text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
            title="Cerrar formato de solicitud"
            aria-label="Cerrar formato de solicitud">
            <span aria-hidden="true">&times;</span>
        </a>
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
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @if (old('approval_popup', request('approval_popup')))
            <input type="hidden" name="approval_popup" value="1">
        @endif
        @if (old('dispensing_popup', request('dispensing_popup')))
            <input type="hidden" name="dispensing_popup" value="1">
        @endif

        @if ($isDispensingMode)
            @include('admin.oncologicos.mezclas._dispensing-patient')
        @else
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
                    value="{{ old('fecha_entrega', ($mezcla->fecha_entrega ?? $solicitud->fecha_entrega) ? \Carbon\Carbon::parse($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)->format('Y-m-d\TH:i') : '') }}"
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

        @endif

        <div id="contenedorMezcla"></div>
        <input type="hidden" name="mezcla_json" id="mezcla_json">

        <div class="flex justify-end mt-4 gap-4">
            <input type="hidden" name="accion" id="accion" value="actualizar">

            @if ($isDispensingMode)
                <x-button type="submit" class="bg-slate-700 hover:bg-slate-800"
                    onclick="document.getElementById('accion').value='dispensar'">
                    Guardar Dispensacion
                </x-button>
            @elseif (! $isApprovalMode)
                <x-button type="submit" onclick="document.getElementById('accion').value='actualizar'">
                    ACTUALIZAR MEZCLA
                </x-button>

                @if ($mezcla->estado === 'pendiente')
                    <x-button type="submit" class="bg-green-600 hover:bg-green-700"
                        onclick="document.getElementById('accion').value='aprobar'">
                        APROBAR MEZCLA
                    </x-button>
                @endif
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
        const isDispensingMode = @json($isDispensingMode);

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

            if (['mg', 'ml', 'frasco', 'pieza'].includes(val)) return val;
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
            const chargeLabel = fila.querySelector('[data-name="charge_label"]');
            const chargeBy = getChargeBy(catalogIdOrOncoId);

            if (chargeLabel) {
                chargeLabel.textContent = chargeBy.toUpperCase();
            }

            if (!isDispensingMode) {
                if (btnConfig) btnConfig.classList.add('hidden');
                if (wrap) wrap.classList.add('hidden');
                if (precioHelp) precioHelp.textContent = 'La seleccion de presentacion se realiza en dispensacion.';
                return;
            }

            if (btnConfig) btnConfig.classList.add('hidden');
            if (wrap) wrap.classList.remove('hidden');

            if (isCobroPorMg(catalogIdOrOncoId)) {
                if (precioMgInput) {
                    precioMgInput.disabled = false;
                    precioMgInput.classList.remove('bg-gray-100', 'text-gray-400');
                    if (precioMgInput.value === '') {
                        precioMgInput.value = formatPrice(getPrecioMgDefault(catalogIdOrOncoId));
                    }
                }
                if (precioHelp) precioHelp.textContent = 'Cobro por mg. Las existencias se muestran como referencia de fabricacion.';
            } else {
                if (precioMgInput) {
                    precioMgInput.value = '';
                    precioMgInput.disabled = true;
                    precioMgInput.classList.add('bg-gray-100', 'text-gray-400');
                }
                if (precioHelp) {
                    precioHelp.textContent = chargeBy === 'ml'
                        ? 'El precio se captura por mL en las presentaciones.'
                        : 'El precio se edita por frasco en presentaciones.';
                }
            }
        }

        // ===========================
        // Helpers: Stock (presentaciones)
        // ===========================
        function presentacionTieneStock(p) {
            if (Array.isArray(p?.batches)) {
                return p.batches.some(b =>
                    b && b.id && String(b.lote || '').trim() !== '' && String(b.caducidad || '').trim() !== ''
                );
            }

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
            const filas = document.querySelectorAll('#medicamentos_mezcla tr.medicamento-row');
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

            // En aprobacion/edicion no se selecciona inventario; eso queda para dispensacion.
            if (isDispensingMode) {
                const okStock = catalogTieneAlgunaPresentacionConStock(catalogId);
                inicializarPresentacionesFila(filaId, catalogId);

                if (!isCobroPorMg(catalogId) && !okStock) {
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
                    if (wrap) wrap.classList.remove('hidden');
                } else {
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
            wrap.classList.remove('hidden');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function formatDisplayNumber(value, decimals = 2) {
            const number = Number(value || 0);
            return Number.isFinite(number) ? number.toFixed(decimals) : Number(0).toFixed(decimals);
        }

        function formatDisplayDate(value) {
            if (!value) return '—';

            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString();
        }

        function resaltarPresentacionFila(filaId) {
            const selector = document.getElementById(`presentacion_selector_${filaId}`);
            const selectedPresentationId = selector?.value || '';

            document.querySelectorAll(`#presentaciones_body_${filaId} .presentacion-row`).forEach(row => {
                const isSelected = selectedPresentationId !== '' && String(row.dataset.presentationId) === String(selectedPresentationId);
                row.classList.toggle('bg-blue-50', isSelected);
                row.classList.toggle('ring-1', isSelected);
                row.classList.toggle('ring-blue-200', isSelected);
            });
        }

        function inicializarPresentacionesFila(filaId, catalogIdOrOncoId, presentacionesGuardadas = []) {
            const catalogId = toCatalogId(catalogIdOrOncoId);
            if (!catalogId) return;

            const lista = presentacionesPorCatalogo?.[catalogId] || [];
            const wrap = document.getElementById(`presentaciones_wrap_${filaId}`);
            const selector = document.getElementById(`presentacion_selector_${filaId}`);
            const tbody = document.getElementById(`presentaciones_body_${filaId}`);
            if (!tbody) return;

            tbody.innerHTML = "";

            if (wrap) wrap.classList.toggle('hidden', !isDispensingMode);

            if (selector) {
                selector.innerHTML = '<option value="">Todas las presentaciones</option>' + lista.map(p => {
                    const contenido = [p.contenido_valor, p.contenido_unidad].filter(Boolean).join(' ');
                    const label = `${p.presentacion ?? 'Presentación'}${contenido ? ' - ' + contenido : ''}${p.marca ? ' - ' + p.marca : ''}`;
                    return `<option value="${p.id}">${escapeHtml(label)}</option>`;
                }).join('');
            }

            if (!lista.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-4 py-4 text-center text-xs text-gray-500">
                            Este medicamento no tiene presentaciones disponibles.
                        </td>
                    </tr>
                `;
                return;
            }

            lista.forEach(p => {
                const batches = p.batches || [];

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
                    const cad = formatDisplayDate(b.caducidad);
                    const ingreso = formatDisplayDate(b.fecha_ingreso);

                    return `
                        <option value="${b.id}"
                            ${selected}
                            data-lote="${escapeHtml(b.lote ?? '')}"
                            data-caducidad-text="${cad}"
                            data-fecha-ingreso-text="${ingreso}"
                            data-stock-inicial="${formatDisplayNumber(b.stock_inicial)}"
                            data-stock="${formatDisplayNumber(b.stock_actual)}"
                            data-stock-reservado="${formatDisplayNumber(b.stock_reservado)}"
                            data-remanente="${formatDisplayNumber(b.remanente_ml)}">
                            ${escapeHtml(b.lote ?? 'Sin lote')}
                        </option>
                    `;
                }).join('');

                const selectedBatch = batches.find(b => Number(b.id) === Number(selectedBatchId)) || batches[0] ||
                    null;
                const hasUsableBatch = !!selectedBatch && (Number(selectedBatch.stock_actual || 0) > 0 || Number(selectedBatch.remanente_ml || 0) > 0);
                const hasClosedStock = !!selectedBatch && Number(selectedBatch.stock_actual || 0) > 0;
                const hasRemainder = !!selectedBatch && Number(selectedBatch.remanente_ml || 0) > 0.0001;

                const statusBadge = hasUsableBatch ?
                    `<span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 batch-status">Disponible</span>` :
                    `<span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 batch-status">Sin stock</span>`;

                const tr = document.createElement('tr');
                tr.classList.add('presentacion-row', 'bg-white', 'border-b', 'last:border-b-0');
                tr.dataset.presentationId = p.id;
                tr.dataset.cantidadMg = p.cantidad_medicamento || 0;
                tr.dataset.marca = p.marca || '';
                tr.dataset.batchId = selectedBatch?.id || selectedBatchId || '';
                tr.dataset.hasStock = hasUsableBatch ? '1' : '0';
                tr.dataset.remanenteMl = selectedBatch?.remanente_ml || 0;

                tr.innerHTML = `
            <td class="px-4 py-3 align-top font-medium text-gray-800">
                <div>${escapeHtml(p.presentacion || '—')}</div>
                <div class="text-xs text-gray-500">
                    ${escapeHtml(p.contenido_valor || p.cantidad_medicamento || '')}
                    ${escapeHtml(p.contenido_unidad || 'mg')}
                </div>
            </td>
            <td class="px-4 py-3 align-top">${escapeHtml(p.marca || '—')}</td>
            <td class="min-w-[190px] px-4 py-3 align-top">
                ${
                    batches.length
                        ? `<select class="batch-select w-full rounded border-gray-300 text-sm"
                                onchange="actualizarBatchPresentacion(this, ${filaId})">
                                ${batchOptions}
                           </select>`
                        : '<span class="text-xs text-gray-400">Sin lotes</span>'
                }
            </td>
            <td class="min-w-[260px] px-4 py-3 align-top">
                ${
                    selectedBatch
                        ? `<div class="text-xs text-gray-700 lote-info">
                                <div><span class="font-semibold">Caducidad:</span> <span class="info-caducidad">${formatDisplayDate(selectedBatch.caducidad)}</span></div>
                                <div><span class="font-semibold">Ingreso:</span> <span class="info-fecha-ingreso">${formatDisplayDate(selectedBatch.fecha_ingreso)}</span></div>
                                <div><span class="font-semibold">Stock actual:</span> <span class="font-semibold text-green-700 info-stock-actual">${formatDisplayNumber(selectedBatch.stock_actual)}</span> frascos</div>
                                <div><span class="font-semibold">Inicial:</span> <span class="info-stock-inicial">${formatDisplayNumber(selectedBatch.stock_inicial)}</span> / <span class="text-gray-500">Reservado:</span> <span class="info-stock-reservado">${formatDisplayNumber(selectedBatch.stock_reservado)}</span></div>
                           </div>`
                        : '<span class="text-xs text-gray-400">Sin información</span>'
                }
            </td>
            <td class="px-4 py-3 text-center align-top">
                ${
                    selectedBatch
                        ? `<div class="font-semibold text-green-700 selected-stock">${formatDisplayNumber(selectedBatch.stock_actual)} frascos</div>`
                        : '<div class="text-xs text-gray-400 selected-stock">Sin stock</div>'
                }
            </td>
            <td class="px-4 py-3 text-center align-top">${statusBadge}</td>
            <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                <span class="font-semibold text-amber-700 selected-remainder">
                    ${formatDisplayNumber(selectedBatch?.remanente_ml)} mL
                </span>
            </td>
            <td class="px-4 py-3 text-center align-top whitespace-nowrap">
                <button type="button"
                    class="remainder-waste-button inline-flex items-center justify-center rounded-full px-3 py-2 text-xs font-semibold ${hasRemainder ? 'bg-red-600 text-white' : 'cursor-not-allowed bg-gray-300 text-gray-600'}"
                    ${hasRemainder ? '' : 'disabled'}>
                    Merma
                </button>
            </td>
            <td class="px-4 py-3 align-top">
                <input type="hidden"
                    class="input-precio-frasco"
                    value="${hasUsableBatch ? formatPrice(precioFrascoValue) : formatPrice(0)}"
                    ${hasUsableBatch ? '' : 'disabled'}>
                <input type="number" min="0" step="1"
                    class="w-16 rounded border px-1 py-0.5 text-right input-frascos ${hasClosedStock ? '' : 'bg-gray-100 text-gray-400'}"
                    value="${hasClosedStock ? frascosValue : 0}"
                    ${hasClosedStock ? '' : 'disabled'}
                    title="${hasClosedStock ? 'Frascos a usar' : 'Sin frascos cerrados disponibles'}"
                    oninput="recalcularResumenPresentaciones(${filaId})">
            </td>
        `;

                tbody.appendChild(tr);
            });

            resaltarPresentacionFila(filaId);
            recalcularResumenPresentaciones(filaId);
        }


        function actualizarBatchPresentacion(select, filaId) {
            const row = select.closest('.presentacion-row');
            const option = select.selectedOptions[0];

            if (!row || !option) return;

            row.dataset.batchId = option.value;
            row.dataset.remanenteMl = option.dataset.remanente || 0;

            const stockActual = Number(option.dataset.stock || 0);
            const remanente = Number(option.dataset.remanente || 0);
            const disponible = stockActual > 0 || remanente > 0.0001;

            row.dataset.hasStock = disponible ? '1' : '0';

            const values = {
                '.info-caducidad': option.dataset.caducidadText || '—',
                '.info-fecha-ingreso': option.dataset.fechaIngresoText || '—',
                '.info-stock-inicial': option.dataset.stockInicial || '0.00',
                '.info-stock-actual': option.dataset.stock || '0.00',
                '.info-stock-reservado': option.dataset.stockReservado || '0.00',
                '.selected-stock': `${option.dataset.stock || '0.00'} frascos`,
                '.selected-remainder': `${formatDisplayNumber(remanente)} mL`,
            };

            Object.entries(values).forEach(([selector, value]) => {
                const el = row.querySelector(selector);
                if (el) el.textContent = value;
            });

            const status = row.querySelector('.batch-status');
            if (status) {
                status.textContent = disponible ? 'Disponible' : 'Sin stock';
                status.classList.toggle('bg-gray-100', disponible);
                status.classList.toggle('text-gray-700', disponible);
                status.classList.toggle('bg-red-100', !disponible);
                status.classList.toggle('text-red-700', !disponible);
            }

            const precioInput = row.querySelector('.input-precio-frasco');
            if (precioInput) {
                precioInput.disabled = !disponible;
                precioInput.classList.toggle('bg-gray-100', !disponible);
                precioInput.classList.toggle('text-gray-400', !disponible);
            }

            const frascosInput = row.querySelector('.input-frascos');
            if (frascosInput) {
                const hasClosedStock = stockActual > 0;
                frascosInput.disabled = !hasClosedStock;
                frascosInput.classList.toggle('bg-gray-100', !hasClosedStock);
                frascosInput.classList.toggle('text-gray-400', !hasClosedStock);
                if (!hasClosedStock) frascosInput.value = 0;
            }

            const remainderButton = row.querySelector('.remainder-waste-button');
            if (remainderButton) {
                const hasRemainder = remanente > 0.0001;
                remainderButton.disabled = !hasRemainder;
                remainderButton.classList.toggle('bg-red-600', hasRemainder);
                remainderButton.classList.toggle('text-white', hasRemainder);
                remainderButton.classList.toggle('cursor-not-allowed', !hasRemainder);
                remainderButton.classList.toggle('bg-gray-300', !hasRemainder);
                remainderButton.classList.toggle('text-gray-600', !hasRemainder);
            }

            recalcularResumenPresentaciones(filaId);
        }

        function recalcularResumenPresentaciones(filaId) {
            const fila = document.getElementById(`fila_${filaId}`);
            if (!fila) return;

            const dosisInput = fila.querySelector('.dosis-input');
            const objetivo = parseFloat(dosisInput?.value || "0");

            const rows = document.querySelectorAll(`#presentaciones_body_${filaId} .presentacion-row`);
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
        function getDiluyentePorFila(fila) {
            return document.getElementById(`diluyente_${fila.id}`);
        }

        function actualizarEtiquetasDiluyentes() {
            if (!isDispensingMode) return;
            const filas = [...document.querySelectorAll('#medicamentos_mezcla tr.medicamento-row')];
            const label = document.getElementById('diluyentes_label');
            if (label && filas.length) label.htmlFor = getDiluyentePorFila(filas[0]).id;
            filas.forEach((fila, index) => {
                const labelMedicamento = document.getElementById(`diluyente_label_${fila.id}`);
                const medicamento = fila.querySelector('[data-name="medicamento"]');
                if (!labelMedicamento) return;
                labelMedicamento.hidden = filas.length === 1;
                labelMedicamento.textContent = medicamento?.value
                    ? medicamento.selectedOptions[0].textContent.trim()
                    : `Medicamento ${index + 1}`;
            });
        }

        function posicionarDiluyente(fila) {
            if (!isDispensingMode) return;
            const select = getDiluyentePorFila(fila);
            const cell = select.closest('td');
            const field = document.createElement('div');
            field.className = 'min-w-0';
            const label = document.createElement('label');
            label.id = `diluyente_label_${fila.id}`;
            label.htmlFor = select.id;
            label.className = 'text-xs text-gray-600';
            select.classList.add('rounded');
            // Move the same control so its options, selected value and events stay intact.
            field.append(label, select);
            document.getElementById('diluyentes_mezcla').appendChild(field);
            cell.remove();
            actualizarEtiquetasDiluyentes();
        }

        function getDiluyenteComunId() {
            const filas = document.querySelectorAll('#medicamentos_mezcla tr.medicamento-row');
            let diluyenteComun = null;

            for (const fila of filas) {
                const selDil = getDiluyentePorFila(fila);
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
            if (!isDispensingMode) return;

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
            if (!isDispensingMode) return;

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
                        ${isDispensingMode ? '' : '<th class="border px-2 py-1 text-xs">DILUYENTE</th>'}
                    </tr>
                </thead>
                <tbody id="medicamentos_mezcla"></tbody>
            </table>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label>Volumen de dilución (ml)*</label>
                    <input type="number" step="0.01" data-name="volumen_dilucion"
                        class="w-full border rounded px-2 py-1 text-sm"
                        value="${mezclaData.volumen_dilucion ?? ''}">
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 ${isDispensingMode ? '' : 'hidden'}" data-diluent-fields>
                <div class="min-w-0">
                    <label id="diluyentes_label">Diluyente</label>
                    <div id="diluyentes_mezcla" class="space-y-2"></div>
                </div>
                <div class="min-w-0 md:col-span-2">
                    <label for="diluent_presentation_id">Presentación del diluyente</label>
                    <select id="diluent_presentation_id" data-name="diluent_presentation_id"
                        class="w-full border rounded px-2 py-1 text-sm">
                        ${opcionesPresentacionDiluyente}
                    </select>
                    <p class="text-xs text-gray-500" id="diluent_presentation_hint">
                        Se sugerirá en función del volumen de dilución de la mezcla.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4" data-infusion-fields>
                <div class="flex items-center gap-2">
                    <input type="checkbox"
                        data-name="set_infusion"
                        class="w-4 h-4"
                        ${mezclaData.set_infusion ? 'checked' : ''}
                        onchange="toggleSetInfusion(this)">
                    <label class="select-none">Set de infusión</label>
                </div>
                <div class="min-w-0">
                    <label for="tiempo_infusion">Tiempo de infusión (min)*</label>
                    <input id="tiempo_infusion" type="number" data-name="tiempo_infusion"
                        class="w-full border rounded px-2 py-1 text-sm"
                        value="${mezclaData.tiempo_infusion ?? ''}">
                </div>
                <div class="min-w-0">
                    <label for="infusor_id">Infusor</label>
                    <select id="infusor_id" data-name="infusor_id"
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

                fila.classList.add('medicamento-row');
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
                            Cobro: <span data-name="charge_label" class="font-semibold">${String(chargeBy).toUpperCase()}</span>
                        </span>
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
                    <select id="diluyente_fila_${contadorFilas}" name="diluyente_existente[]"
                        data-name="diluyente"
                        class="w-full border px-2 py-1 text-sm"
                        onchange="onDiluyenteChange(this)">
                        <option value="">Diluyentes</option>
                        ${diluyenteOptions}
                    </select>
                </td>
            `;

                const detalleFila = document.createElement('tr');
                detalleFila.id = `detalle_fila_${contadorFilas}`;
                detalleFila.classList.add('medicamento-detail-row');
                detalleFila.innerHTML = `
                <td colspan="${isDispensingMode ? 3 : 4}" class="border border-t-0 bg-gray-50 p-3 text-left">
                    <div id="presentaciones_wrap_${contadorFilas}" class="${isDispensingMode ? '' : 'hidden'}">
                        <div class="mb-2 max-w-xl">
                            <label class="text-xs font-semibold text-gray-700">Seleccionar presentación</label>
                            <select id="presentacion_selector_${contadorFilas}"
                                class="mt-1 w-full rounded border-gray-300 text-sm"
                                onchange="resaltarPresentacionFila(${contadorFilas})">
                                <option value="">Todas las presentaciones</option>
                            </select>
                        </div>
                        <div class="overflow-x-auto rounded border border-gray-200 bg-white">
                            <table class="w-full text-left text-xs text-gray-600">
                                <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">Presentación</th>
                                        <th class="px-4 py-3">Marca</th>
                                        <th class="px-4 py-3">Lote</th>
                                        <th class="px-4 py-3">Detalle del lote</th>
                                        <th class="px-4 py-3 text-center">Stock lote seleccionado</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 text-center">Remanente</th>
                                        <th class="px-4 py-3 text-center">Merma remanente</th>
                                        <th class="px-4 py-3 text-center">Frascos</th>
                                    </tr>
                                </thead>
                                <tbody id="presentaciones_body_${contadorFilas}"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 max-w-xl">
                        <label class="text-xs font-semibold text-gray-700">Vía de administración</label>
                        <select name="via_existente[]"
                            data-name="via_administracion"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Vía de admin</option>
                            ${viaOptions}
                        </select>
                    </div>
                </td>
            `;

                tbody.appendChild(fila);
                tbody.appendChild(detalleFila);
                posicionarDiluyente(fila);

                // Inicializaciones
                toggleUIByChargeBy(contadorFilas, catalogId);

                if (isDispensingMode) {
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
            const detalleFila = document.getElementById(`detalle_fila_${filaId}`);
            const data = infoAdicional?.[catalogId] || {
                diluyentes: [],
                vias: []
            };

            const selectDiluyente = getDiluyentePorFila(fila);
            const selectVia = detalleFila?.querySelector('[data-name="via_administracion"]');

            selectDiluyente.innerHTML =
                `<option value="">Diluyentes</option>` +
                (data.diluyentes || []).map(d =>
                    `<option value="${d.id}">${d.name ?? d.denominacion_generica ?? '—'}</option>`
                ).join('');

            actualizarEtiquetasDiluyentes();

            if (selectVia) {
                selectVia.innerHTML =
                    `<option value="">Vía de admin</option>` +
                    (data.vias || []).map(v =>
                        `<option value="${v.id}">${v.name}</option>`
                    ).join('');
            }

            if (isDispensingMode) {
                inicializarPresentacionesFila(filaId, catalogId);
            }

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
                fila.classList.add('medicamento-row');

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
                        <span class="text-[11px] text-gray-500">
                            Cobro: <span data-name="charge_label" class="font-semibold">FRASCO</span>
                        </span>
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
                    <select id="diluyente_fila_${contadorFilas}" name="nuevo_diluyente[]"
                        data-name="diluyente"
                        class="w-full border px-2 py-1 text-sm"
                        onchange="onDiluyenteChange(this)">
                        <option value="">Diluyentes</option>
                    </select>
                </td>
            `;

                const detalleFila = document.createElement('tr');
                detalleFila.id = `detalle_fila_${contadorFilas}`;
                detalleFila.classList.add('medicamento-detail-row');
                detalleFila.innerHTML = `
                <td colspan="${isDispensingMode ? 3 : 4}" class="border border-t-0 bg-gray-50 p-3 text-left">
                    <div id="presentaciones_wrap_${contadorFilas}" class="${isDispensingMode ? '' : 'hidden'}">
                        <div class="mb-2 max-w-xl">
                            <label class="text-xs font-semibold text-gray-700">Seleccionar presentación</label>
                            <select id="presentacion_selector_${contadorFilas}"
                                class="mt-1 w-full rounded border-gray-300 text-sm"
                                onchange="resaltarPresentacionFila(${contadorFilas})">
                                <option value="">Todas las presentaciones</option>
                            </select>
                        </div>
                        <div class="overflow-x-auto rounded border border-gray-200 bg-white">
                            <table class="w-full text-left text-xs text-gray-600">
                                <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">Presentación</th>
                                        <th class="px-4 py-3">Marca</th>
                                        <th class="px-4 py-3">Lote</th>
                                        <th class="px-4 py-3">Detalle del lote</th>
                                        <th class="px-4 py-3 text-center">Stock lote seleccionado</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 text-center">Remanente</th>
                                        <th class="px-4 py-3 text-center">Merma remanente</th>
                                        <th class="px-4 py-3 text-center">Frascos</th>
                                    </tr>
                                </thead>
                                <tbody id="presentaciones_body_${contadorFilas}"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 max-w-xl">
                        <label class="text-xs font-semibold text-gray-700">Vía de administración</label>
                        <select name="nueva_via[]"
                            data-name="via_administracion"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Vía de admin</option>
                        </select>
                    </div>
                </td>
            `;

                tbody.appendChild(fila);
                tbody.appendChild(detalleFila);
                posicionarDiluyente(fila);

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

            const accion = document.getElementById('accion')?.value || 'actualizar';

            if (accion === 'rechazar') {
                if (!window.Swal) {
                    e.target.submit();
                    return;
                }

                Swal.fire({
                    title: '¿Rechazar mezcla?',
                    text: 'La mezcla quedará fuera del proceso de preparación.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, rechazar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    }
                }).then((result) => {
                    if (result.isConfirmed) e.target.submit();
                });

                return;
            }

            const requiresInventorySelection = accion === 'dispensar';
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
                const filaId = fila.id.replace('fila_', '');
                const presRows = document.querySelectorAll(`#presentaciones_body_${filaId} .presentacion-row`);
                const arr = [];
                presRows.forEach(r => {
                    const hasStock = String(r.dataset.hasStock || '0') === '1';
                    const frascos = parseFloat(r.querySelector('.input-frascos')?.value || "0");
                    const precioFrasco = r.querySelector('.input-precio-frasco')?.value || '';
                    const batchSelect = r.querySelector('.batch-select');
                    const batchId = batchSelect?.value || r.dataset.batchId || null;
                    const remanenteMl = parseFloat(r.dataset.remanenteMl || '0');

                    // ✅ si no hay stock, forzar 0 (por seguridad)
                    if (!hasStock) {
                        if (frascos > 0) r.querySelector('.input-frascos').value = 0;
                        return;
                    }

                    if ((frascos > 0 || remanenteMl > 0) && batchId) {
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

            document.querySelectorAll('#medicamentos_mezcla tr.medicamento-row').forEach(tr => {
                const selMed = tr.querySelector('[data-name="medicamento"]');
                if (!selMed || !selMed.value) return;

                const catalogId = toCatalogId(selMed.value);
                const dosisInput = tr.querySelector('.dosis-input');
                const dosis = dosisInput ? dosisInput.value : '';

                const selDil = getDiluyentePorFila(tr);
                const filaId = tr.id.replace('fila_', '');
                const detalleFila = document.getElementById(`detalle_fila_${filaId}`);
                const selVia = detalleFila?.querySelector('[data-name="via_administracion"]');

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

                if (requiresInventorySelection && chargeBy !== 'mg') {
                    // Acepta frascos cerrados o un remanente vigente.
                    medObj.presentaciones = buildPresentacionesFromFila(row.tr);

                    if (medObj.presentaciones.length <= 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Falta seleccionar inventario',
                            text: `Para "${row.nombre}" debes seleccionar un lote con frascos o remanente vigente.`
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

            const confirmTitle = requiresInventorySelection
                ? '¿Guardar dispensacion?'
                : (accion === 'aprobar' ? '¿Aprobar mezcla?' : '¿Actualizar mezcla?');
            const confirmText = requiresInventorySelection
                ? 'Se guardara la seleccion de presentaciones y la mezcla quedara dispensada.'
                : (accion === 'aprobar'
                    ? 'La mezcla se marcara como aprobada sin seleccionar presentacion.'
                    : 'Se guardaran los cambios realizados.');
            const confirmButtonText = requiresInventorySelection
                ? 'Guardar Dispensacion'
                : (accion === 'aprobar' ? 'Si, aprobar' : 'Si, actualizar');

            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText,
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
            }).then(() => {
                document.dispatchEvent(new CustomEvent('workflow-popup-dialog-closed'));
            });
        @endif
    </script>

</x-admin-layout>
