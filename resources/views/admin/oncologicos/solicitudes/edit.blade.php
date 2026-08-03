<x-admin-layout>
    <div class="flex flex-col ">
        <div class="mt-2 mb-4">
            <h1 class="text-2xl font-medium text-gray-800">Editar Solicitud</h1>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <strong>Se encontraron los siguientes errores:</strong>
                    <ul class="list-disc pl-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <form id="formularioSolicitud" action="{{ route('admin.oncologicos.solicitudes.update', $solicitud->id) }}"
            method="POST" class="bg-white rounded-lg p-6 shadow-lg">
            @csrf
            @method('PUT')

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
                    <input type="text" name="servicio" id="servicio"
                        value="{{ old('servicio', $solicitud->servicio) }}"
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
                    <input type="number" name="peso" id="peso" value="{{ old('peso', $solicitud->peso) }}"
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

            <!-- Mezclas -->
            <div class="mt-4">
                <div id="contenedorMezclas"></div>
                <input type="hidden" name="mezclas" id="mezclas_json">
            </div>
            <div class="my-4">
                <button type="button" onclick="agregarMezcla()"
                    class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-plus"></i> Agregar Mezcla
                </button>
            </div>

            <div class="flex justify-end gap-5 mt-4">
                <x-button>
                    ACTUALIZAR SOLICITUD
                </x-button>
            </div>
        </form>

    </div>

    <script>
        const medicamentos = @json($medicamentos); // SOLO genéricos permitidos
        const infoAdicional = @json($infoAdicional); // diluyentes + vías por medicine_onco_id
        const mezclasCargadas = @json($solicitud->mezclas ?? []);
        const infusors = @json($infusors ?? []);

        let idInternoMezcla = 0;
        let contadorFilasGlobal = 0;

        // ---------- Helpers Infusor vs Set ----------
        function requiereInfusorParaMed(medId) {
            if (!medId) return false;
            const med = medicamentos.find(m => String(m.id) === String(medId));
            if (med && typeof med.requires_infusor !== 'undefined') {
                return Number(med.requires_infusor) === 1;
            }
            const info = infoAdicional[medId];
            if (info && typeof info.requires_infusor !== 'undefined') {
                return Number(info.requires_infusor) === 1;
            }
            return false;
        }

        function mezclaAdmiteInfusor(idMezcla) {
            const filas = document.querySelectorAll(`#medicamentos_mezcla_${idMezcla} tr`);
            for (const fila of filas) {
                const medSel = fila.querySelector('[data-name="medicamento"]') || fila.querySelector('.medicamento-select');
                if (medSel && medSel.value && requiereInfusorParaMed(medSel.value)) {
                    return true;
                }
            }
            return false;
        }

        function updateInfusorDisponibilidad(idMezcla) {
            const mezclaDiv = document.querySelector(`#contenedorMezclas > .border[data-id-interno="${idMezcla}"]`) ||
                document.querySelector(`#contenedorMezclas > .border[data-idinterno="${idMezcla}"]`);
            if (!mezclaDiv) return;

            const setCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');

            const admite = mezclaAdmiteInfusor(idMezcla);

            if (setCb && setCb.checked) {
                selInf.value = '';
                selInf.disabled = true;
                return;
            }

            selInf.disabled = !admite;
            if (!admite) selInf.value = '';
        }

        function toggleSetInfusion(checkbox, idMezcla) {
            const mezclaDiv = checkbox.closest('.border');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');

            if (checkbox.checked) {
                selInf.value = '';
                selInf.disabled = true;
            } else {
                selInf.disabled = !mezclaAdmiteInfusor(idMezcla);
            }
        }

        function toggleInfusorSelect(select, idMezcla) {
            const mezclaDiv = select.closest('.border');
            const setCb = mezclaDiv.querySelector('[data-name="set_infusion"]');

            if (select.value) {
                setCb.checked = false;
                setCb.disabled = true;
            } else {
                setCb.disabled = false;
            }
        }
        // --------------------------------------------

        // ====== Compatibilidad de medicamentos dentro de la mezcla ======
        function actualizarOpcionesMedicamentos(idMezcla) {
            const selects = document.querySelectorAll(
                `#medicamentos_mezcla_${idMezcla} select[data-name="medicamento"]`
            );

            const seleccionados = Array.from(selects)
                .map(s => s.value)
                .filter(v => v);

            const diluyentesSet = new Set();
            const viasSet = new Set();

            seleccionados.forEach(id => {
                const info = infoAdicional[id];
                if (info) {
                    info.diluyentes.forEach(d => diluyentesSet.add(String(d.id)));
                    info.vias.forEach(v => viasSet.add(String(v.id)));
                }
            });

            selects.forEach(select => {
                const valorActual = select.value;
                select.innerHTML = '<option value="">Seleccione</option>';

                medicamentos.forEach(m => {
                    const mId = String(m.id);
                    const info = infoAdicional[mId] || infoAdicional[m.id];
                    if (!info) return;

                    const yaSeleccionado = seleccionados.includes(mId) && mId !== valorActual;

                    if (yaSeleccionado) return;

                    let esCompatible = true;

                    if (seleccionados.length > 0 && !valorActual) {
                        const tieneDiluyenteComun = info.diluyentes.some(d => diluyentesSet.has(String(d
                            .id)));
                        const tieneViaComun = info.vias.some(v => viasSet.has(String(v.id)));
                        esCompatible = tieneDiluyenteComun && tieneViaComun;
                    }

                    if (esCompatible || mId === valorActual || seleccionados.length === 0) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.presentacion ? `${m.denominacion} (${m.presentacion})` : m.denominacion;
                        if (mId === valorActual) opt.selected = true;
                        select.appendChild(opt);
                    }
                });
            });
        }
        // ================================================================

        function agregarMezcla() {
            idInternoMezcla++;
            const mezclaDiv = document.createElement('div');
            mezclaDiv.classList.add("oncology-mixture", "border", "border-slate-300", "p-4", "relative");
            mezclaDiv.dataset.idInterno = idInternoMezcla;

            mezclaDiv.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold mezcla-titulo">Mezcla</h3>
            <button type="button" onclick="eliminarMezcla(this)" class="bg-red-600 text-white px-3 py-1 rounded text-sm">
                <i class="fas fa-trash"></i> Eliminar Mezcla
            </button>
        </div>
        <div class="oncology-medicine-table-wrap">
        <table class="oncology-medicine-table table-auto w-full border text-center">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-xs">MEDICAMENTO</th>
                    <th class="border px-4 py-2 text-xs">DOSIS</th>
                    <th class="border px-4 py-2 text-xs">DILUYENTE</th>
                    <th class="border px-4 py-2 text-xs">VÍA DE ADMINISTRACIÓN</th>
                </tr>
            </thead>
            <tbody id="medicamentos_mezcla_${idInternoMezcla}"></tbody>
        </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label>Volumen total de dilución (ml)*</label>
                <input type="number" step="0.01" data-name="volumen_dilucion" class="w-full border rounded px-2 py-1 text-sm">
            </div>
            <div>
                <label>Tiempo de infusión (min)*</label>
                <input type="number" data-name="tiempo_infusion" class="w-full border rounded px-2 py-1 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="flex items-center gap-2">
                <input type="checkbox" data-name="set_infusion" class="w-4 h-4"
                       onchange="toggleSetInfusion(this, ${idInternoMezcla})">
                <label class="select-none">Set de infusión</label>
            </div>
            <div>
                <label>Infusor</label>
                <select data-name="infusor_id" class="w-full border rounded px-2 py-1 text-sm"
                        onchange="toggleInfusorSelect(this, ${idInternoMezcla})" disabled>
                    <option value="">Selecciona infusor</option>
                    ${infusors.map(i => `<option value="${i.id}">${i.nombre_generico ?? i.nombre_comercial ?? ('Infusor #'+i.id)}</option>`).join('')}
                </select>
                <p class="text-xs text-gray-500">Se habilita si la mezcla contiene medicamento(s) que admiten infusor.</p>
            </div>
        </div>

        <div class="mt-4">
            <button type="button" class="btn-agregar-medicamento bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1 px-2 rounded">
                + Agregar Medicamento
            </button>
        </div>
        `;

            document.getElementById('contenedorMezclas').appendChild(mezclaDiv);
            actualizarNumeracionMezclas();
            updateInfusorDisponibilidad(idInternoMezcla);
        }

        function eliminarMezcla(btn) {
            const mezcla = btn.closest(".border");
            mezcla.remove();
            actualizarNumeracionMezclas();
        }

        function agregarMezclaCargada(m) {
            idInternoMezcla++;
            const mezclaDiv = document.createElement('div');
            mezclaDiv.classList.add("oncology-mixture", "border", "border-slate-300", "p-4", "relative", "mezcla-existente");
            mezclaDiv.dataset.idInterno = idInternoMezcla;

            mezclaDiv.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold mezcla-titulo">Mezcla</h3>
        </div>

        <div class="oncology-medicine-table-wrap">
        <table class="oncology-medicine-table table-auto w-full border text-center">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-xs">MEDICAMENTO</th>
                    <th class="border px-4 py-2 text-xs">DOSIS</th>
                    <th class="border px-4 py-2 text-xs">DILUYENTE</th>
                    <th class="border px-4 py-2 text-xs">VÍA DE ADMINISTRACIÓN</th>
                </tr>
            </thead>
            <tbody id="medicamentos_mezcla_${idInternoMezcla}"></tbody>
        </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label>Volumen total de dilución (ml)*</label>
                <input type="number" step="0.01" data-name="volumen_dilucion" class="w-full border rounded px-2 py-1 text-sm" value="${m.volumen_dilucion ?? ''}" disabled>
            </div>
            <div>
                <label>Tiempo de infusión (min)*</label>
                <input type="number" data-name="tiempo_infusion" class="w-full border rounded px-2 py-1 text-sm" value="${m.tiempo_infusion ?? ''}" disabled>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="flex items-center gap-2">
                <input type="checkbox" data-name="set_infusion" class="w-4 h-4"
                       ${m.set_infusion ? 'checked' : ''}
                       onchange="toggleSetInfusion(this, ${idInternoMezcla})">
                <label class="select-none">Set de infusión</label>
            </div>
            <div>
                <label>Infusor</label>
                <select data-name="infusor_id" class="w-full border rounded px-2 py-1 text-sm"
                        onchange="toggleInfusorSelect(this, ${idInternoMezcla})">
                    <option value="">Selecciona infusor</option>
                    ${infusors.map(i => `<option value="${i.id}" ${Number(m.infusor_id||'')===Number(i.id)?'selected':''}>${i.nombre_generico ?? i.nombre_comercial ?? ('Infusor #'+i.id)}</option>`).join('')}
                </select>
                <p class="text-xs text-gray-500">Se habilita si la mezcla contiene medicamento(s) que admiten infusor.</p>
            </div>
        </div>
        `;

            document.getElementById('contenedorMezclas').appendChild(mezclaDiv);

            const tbody = mezclaDiv.querySelector(`#medicamentos_mezcla_${idInternoMezcla}`);
            (m.medicamentos || []).forEach(med => {
                contadorFilasGlobal++;
                const fila = document.createElement('tr');
                fila.id = `fila_m${idInternoMezcla}_f${contadorFilasGlobal}`;

                const data = infoAdicional[med.medicamento_id] || {
                    diluyentes: [],
                    vias: []
                };

                const diluyenteOptions = data.diluyentes.map(d =>
                    `<option value="${d.id}" ${d.id == med.diluyente_id ? 'selected' : ''}>${d.name}</option>`
                ).join('');

                const viaOptions = data.vias.map(v =>
                    `<option value="${v.id}" ${v.id == med.via_administracion_id ? 'selected' : ''}>${v.name}</option>`
                ).join('');

                fila.innerHTML = `
            <td class="border">
                <select class="medicamento-select min-w-52 w-full border px-2 py-1 text-sm" data-name="medicamento" disabled>
                    ${medicamentos.map(x =>
                        `<option value="${x.id}" ${x.id == med.medicamento_id ? 'selected' : ''}>${x.presentacion ? `${x.denominacion} (${x.presentacion})` : x.denominacion}</option>`
                    ).join('')}
                </select>
            </td>
            <td class="border">
                <input type="number" value="${med.dosis}" class="min-w-28 w-full border px-2 py-1 text-sm" disabled>
            </td>
            <td class="border">
                <select data-name="diluyente" class="min-w-44 w-full border rounded px-2 py-1 text-sm" disabled>
                    <option value="">Diluyentes</option>
                    ${diluyenteOptions}
                </select>
            </td>
            <td class="border">
                <select data-name="via_administracion" class="min-w-44 w-full border rounded px-2 py-1 text-sm" disabled>
                    <option value="">Vía de admin</option>
                    ${viaOptions}
                </select>
            </td>
            `;

                tbody.appendChild(fila);
            });

            const setCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');

            if (selInf.value) {
                setCb.checked = false;
                setCb.disabled = true;
            }
            if (setCb.checked) {
                selInf.value = '';
                selInf.disabled = true;
            }

            actualizarNumeracionMezclas();
            updateInfusorDisponibilidad(idInternoMezcla);
        }

        function actualizarNumeracionMezclas() {
            document.querySelectorAll('#contenedorMezclas > .border').forEach((mezcla, index) => {
                mezcla.querySelector('.mezcla-titulo').textContent = `Mezcla #${index + 1}`;
            });
        }

        function actualizarDiluentesYVias(selectElem, idMezcla, idFila) {
            const medicamentoId = selectElem.value;
            const fila = document.getElementById(`fila_m${idMezcla}_f${idFila}`);
            const data = infoAdicional[medicamentoId] || {
                diluyentes: [],
                vias: []
            };

            const selectDiluyente = fila.querySelector('[data-name="diluyente"]');
            const selectVia = fila.querySelector('[data-name="via_administracion"]');

            selectDiluyente.innerHTML = `<option value="">Diluyentes</option>` +
                data.diluyentes.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            selectVia.innerHTML = `<option value="">Vía de admin</option>` +
                data.vias.map(v => `<option value="${v.id}">${v.name}</option>`).join('');

            actualizarOpcionesMedicamentos(idMezcla);
            updateInfusorDisponibilidad(idMezcla);
        }

        // Handler para agregar filas en mezclas nuevas
        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("btn-agregar-medicamento")) {
                const mezclaDiv = e.target.closest(".border");
                const idMezcla = mezclaDiv.dataset.idInterno;
                const tbody = mezclaDiv.querySelector(`#medicamentos_mezcla_${idMezcla}`);

                contadorFilasGlobal++;
                const fila = document.createElement("tr");
                fila.id = `fila_m${idMezcla}_f${contadorFilasGlobal}`;

                fila.innerHTML = `
            <td class="border">
                <select class="medicamento-select min-w-52 w-full border px-2 py-1 text-sm" data-name="medicamento"
                        onchange="actualizarDiluentesYVias(this, ${idMezcla}, ${contadorFilasGlobal})">
                    <option value="">Seleccione</option>
                    ${medicamentos.map(med => `<option value="${med.id}">${med.presentacion ? `${med.denominacion} (${med.presentacion})` : med.denominacion}</option>`).join('')}
                </select>
            </td>
            <td class="border">
                <input type="number" data-name="dosis" class="min-w-28 w-full border px-2 py-1 text-sm">
            </td>
            <td class="border">
                <select data-name="diluyente" class="min-w-44 w-full border rounded px-2 py-1 text-sm">
                    <option value="">Diluyentes</option>
                </select>
            </td>
            <td class="border">
                <select data-name="via_administracion" class="min-w-44 w-full border rounded px-2 py-1 text-sm">
                    <option value="">Vía de admin</option>
                </select>
            </td>
            `;

                tbody.appendChild(fila);

                actualizarOpcionesMedicamentos(idMezcla);
                updateInfusorDisponibilidad(idMezcla);
            }
        });

        // Submit: validar mezclas y enviar JSON
        document.getElementById("formularioSolicitud").addEventListener("submit", function(e) {
            e.preventDefault();
            const mezclas = [];
            let errorMezclaInvalida = null;

            document.querySelectorAll('#contenedorMezclas > .border').forEach((mezclaDiv, idx) => {
                const numMezcla = idx + 1;
                const idInterno = mezclaDiv.dataset.idInterno;
                const volumen = mezclaDiv.querySelector(`[data-name="volumen_dilucion"]`)?.value;
                const tiempo = mezclaDiv.querySelector(`[data-name="tiempo_infusion"]`)?.value;

                const setInfusionCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
                const selInfusor = mezclaDiv.querySelector('[data-name="infusor_id"]');
                const set_infusion = !!(setInfusionCb && setInfusionCb.checked);
                const infusor_id = selInfusor && selInfusor.value ? selInfusor.value : null;

                const medicamentosArr = [];
                let diluyenteRef = null;
                let viaRef = null;
                let hayMedQueAdmiteInfusor = false;

                mezclaDiv.querySelectorAll(`#medicamentos_mezcla_${idInterno} tr`).forEach((fila, i) => {
                    const medSel = fila.querySelector('[data-name="medicamento"]') || fila
                        .querySelector('.medicamento-select');
                    const dosisInp = fila.querySelector('[data-name="dosis"]');
                    const dilSel = fila.querySelector('[data-name="diluyente"]');
                    const viaSel = fila.querySelector('[data-name="via_administracion"]');

                    if (!medSel || !medSel.value || !dosisInp || !dosisInp.value) return;

                    const diluyente = dilSel?.value || null;
                    const via = viaSel?.value || null;

                    if (i === 0) {
                        diluyenteRef = diluyente;
                        viaRef = via;
                    } else {
                        if (diluyente !== diluyenteRef || via !== viaRef) {
                            errorMezclaInvalida =
                                `Mezcla #${numMezcla}: todos los medicamentos deben tener el mismo diluyente y la misma vía de administración.`;
                        }
                    }

                    if (requiereInfusorParaMed(medSel.value)) {
                        hayMedQueAdmiteInfusor = true;
                    }

                    medicamentosArr.push({
                        medicamento_id: medSel.value,
                        nombre: (medSel.options && medSel.options[medSel.selectedIndex]) ?
                            medSel.options[medSel.selectedIndex].text : '',
                        dosis: dosisInp.value,
                        diluyente_id: diluyente,
                        via_administracion_id: via
                    });
                });

                if (medicamentosArr.length === 0) {
                    return;
                }

                if (set_infusion && infusor_id) {
                    errorMezclaInvalida =
                        `Mezcla #${numMezcla}: selecciona set de infusión o un infusor, no ambos.`;
                }

                if (infusor_id && !hayMedQueAdmiteInfusor) {
                    errorMezclaInvalida =
                        `Mezcla #${numMezcla}: para usar un infusor, al menos un medicamento de la mezcla debe admitirlo.`;
                }

                const mezclaObj = {
                    volumen_dilucion: volumen,
                    tiempo_infusion: tiempo,
                    set_infusion: set_infusion,
                    infusor_id: infusor_id,
                    medicamentos: medicamentosArr
                };

                if (mezclaDiv.classList.contains("mezcla-existente")) {
                    mezclaObj.existente = true;
                }

                mezclas.push(mezclaObj);
            });

            if (errorMezclaInvalida) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en mezcla',
                    text: errorMezclaInvalida,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    }
                });
                return;
            }

            document.getElementById("mezclas_json").value = JSON.stringify(mezclas);

            Swal.fire({
                title: '¿Actualizar solicitud?',
                text: 'Se guardarán los cambios realizados',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
            if (mezclasCargadas.length > 0) {
                mezclasCargadas.forEach(m => agregarMezclaCargada(m));
            }
        });
    </script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            });
        </script>
    @endif
</x-admin-layout>
