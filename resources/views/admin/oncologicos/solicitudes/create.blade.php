<x-admin-layout>
    <div class="flex flex-col ">
        <div class="mt-2 mb-4">
            <h1 class="text-2xl font-medium text-gray-800">
                Crear Nueva Solicitud {{ ($requestType ?? 'oncologicos') === 'antibioticos' ? 'de Antibioticos' : 'Oncologica' }}
            </h1>

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

        <form id="formularioSolicitud" action="{{ route('admin.oncologicos.solicitudes.store') }}" method="POST"
            class="bg-white rounded-lg p-6 shadow-lg">
            @csrf
            <input type="hidden" name="tipo_solicitud" value="{{ $requestType ?? 'oncologicos' }}">


            <div class="flex justify-between mb-4 gap-4">
                <div class="w-1/3">
                    <label for="paciente_nombre">Paciente Nombre(s)</label>
                    <input type="text" name="paciente_nombre" id="paciente_nombre"
                        value="{{ old('paciente_nombre') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Nombre(s) del Paciente">
                </div>
                <div class="w-1/3">
                    <label for="servicio">Servicio*</label>
                    <input type="text" name="servicio" id="servicio" value="{{ old('servicio') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Servicio">
                </div>
                <div class="w-1/3">
                    <label for="registro">Registro*</label>
                    <input type="text" name="registro" id="registro" value="{{ old('registro') }}"
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
                        <option value="M" @selected(old('sexo') === 'M')>Masculino</option>
                        <option value="F" @selected(old('sexo') === 'F')>Femenino</option>
                    </select>
                </div>
                <div class="w-1/5">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                        value="{{ old('fecha_nacimiento') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div class="w-1/5">
                    <label for="peso">Peso*</label>
                    <input type="text" name="peso" id="peso" value="{{ old('peso') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Peso">
                </div>
                <div class="w-1/5">
                    <label for="piso">Piso*</label>
                    <input type="text" name="piso" id="piso" value="{{ old('piso') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Piso">
                </div>
                <div class="w-1/5">
                    <label for="cama">Cama*</label>
                    <input type="text" name="cama" id="cama" value="{{ old('cama') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Cama">
                </div>
            </div>

            <div class="flex justify-between mb-4 gap-4">
                <div class="w-1/5">
                    <label for="diagnostico">Diagnóstico</label>
                    <input type="text" name="diagnostico" id="diagnostico" value="{{ old('diagnostico') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Diagnóstico">
                </div>
                <div class="w-1/5">
                    <label for="alergias">Alergias</label>
                    <input type="text" name="alergias" id="alergias" value="{{ old('alergias') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Alergias del paciente (si aplica)">
                </div>
                <div class="w-1/5">
                    <label for="medico_nombre">Nombre del Médico*</label>
                    <input type="text" name="medico_nombre" id="medico_nombre"
                        value="{{ old('medico_nombre') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Nombre del Médico">
                </div>
                <div class="w-1/5">
                    <label for="medico_cedula">Cédula del Médico*</label>
                    <input type="text" name="medico_cedula" id="medico_cedula"
                        value="{{ old('medico_cedula') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        placeholder="Cédula del Médico">
                </div>
                <div class="w-1/5">
                    <label for="fecha_entrega">Fecha de entrega*</label>
                    <input type="datetime-local" name="fecha_entrega" id="fecha_entrega"
                        value="{{ old('fecha_entrega') }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
            </div>

            <div class="mb-4">
                <label for="observaciones">Observaciones</label>
                <input type="text" name="observaciones" id="observaciones" value="{{ old('observaciones') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    placeholder="Observaciones">
            </div>

            <!-- Mezclas -->
            <div class="mt-4">
                <div id="contenedorMezclas"></div>
                <input type="hidden" name="mezclas" id="mezclas_json">

                <div class="my-4">
                    <button type="button" onclick="agregarMezcla()"
                        class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-plus"></i> Agregar Mezcla
                    </button>
                </div>
            </div>

            <div class="flex justify-end gap-5 mt-4">
                <x-button>
                    GUARDAR SOLICITUD
                </x-button>
            </div>
        </form>
    </div>

    <script>
        // 👇 Aquí deben venir SOLO medicamentos genéricos (medicines_catalog)
        // con campos: id, denominacion, requires_infusor, etc.
        const medicamentos = @json($medicamentos);
        const infoAdicional = @json($infoAdicional);
        const mezclasOld = @json(old('mezclas') ? json_decode(old('mezclas'), true) : []);
        const infusors = @json($infusors ?? []);

        let idInternoMezcla = 0;
        let contadorFilasGlobal = 0;

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
                    <th class="min-w-20 border px-4 py-2 text-xs">UNIDAD</th>
                    <th class="border px-4 py-2 text-xs">DILUYENTE</th>
                    <th class="border px-4 py-2 text-xs">VÍA DE ADMINISTRACIÓN</th>
                    <th class="border px-4 py-2 text-xs">ACCIÓN</th>
                </tr>
            </thead>
            <tbody id="medicamentos_mezcla_${idInternoMezcla}"></tbody>
        </table>
        </div>

        <div class="my-2">
            <button type="button" onclick="agregarFilaMedicamento(${idInternoMezcla})" class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-plus"></i> Agregar Medicamento
            </button>
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

            <!-- WRAPPER DE INFUSOR: INICIA OCULTO -->
            <div data-name="infusor_wrapper" style="display:none">
                <label>Infusor</label>
                <select data-name="infusor_id" class="w-full border rounded px-2 py-1 text-sm"
                        onchange="toggleInfusorSelect(this, ${idInternoMezcla})" disabled>
                    <option value="">Selecciona infusor</option>
                    ${infusors.map(i => `<option value="${i.id}">${i.nombre_generico ?? i.nombre_comercial ?? ('Infusor #'+i.id)}</option>`).join('')}
                </select>
                <p class="text-xs text-gray-500" data-name="infusor_help">
                    Aparece solo si la mezcla contiene medicamento(s) que admiten infusor.
                </p>
            </div>
        </div>
        `;

            document.getElementById('contenedorMezclas').appendChild(mezclaDiv);
            agregarFilaMedicamento(idInternoMezcla);
            actualizarNumeracionMezclas();
            updateInfusorDisponibilidad(idInternoMezcla);
        }

        function agregarMezclaDesdeOld(mezclaData) {
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
                    <th class="min-w-20 border px-4 py-2 text-xs">UNIDAD</th>
                    <th class="border px-4 py-2 text-xs">DILUYENTE</th>
                    <th class="border px-4 py-2 text-xs">VÍA DE ADMINISTRACIÓN</th>
                    <th class="border px-4 py-2 text-xs">ACCIÓN</th>
                </tr>
            </thead>
            <tbody id="medicamentos_mezcla_${idInternoMezcla}"></tbody>
        </table>
        </div>

        <div class="my-2">
            <button type="button" onclick="agregarFilaMedicamento(${idInternoMezcla})" class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-plus"></i> Agregar Medicamento
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label>Volumen total de dilución (ml)*</label>
                <input type="number" step="0.01" data-name="volumen_dilucion" value="${mezclaData.volumen_dilucion ?? ''}" class="w-full border rounded px-2 py-1 text-sm">
            </div>
            <div>
                <label>Tiempo de infusión (min)*</label>
                <input type="number" data-name="tiempo_infusion" value="${mezclaData.tiempo_infusion ?? ''}" class="w-full border rounded px-2 py-1 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="flex items-center gap-2">
                <input type="checkbox" data-name="set_infusion" class="w-4 h-4"
                       ${mezclaData.set_infusion ? 'checked' : ''}
                       onchange="toggleSetInfusion(this, ${idInternoMezcla})">
                <label class="select-none">Set de infusión</label>
            </div>

            <!-- WRAPPER DE INFUSOR: INICIA OCULTO, SE MOSTRARÁ SOLO SI ADMITE -->
            <div data-name="infusor_wrapper" style="display:none">
                <label>Infusor</label>
                <select data-name="infusor_id" class="w-full border rounded px-2 py-1 text-sm"
                        onchange="toggleInfusorSelect(this, ${idInternoMezcla})">
                    <option value="">Selecciona infusor</option>
                    ${infusors.map(i => `<option value="${i.id}" ${Number(mezclaData.infusor_id||'')===Number(i.id)?'selected':''}>${i.nombre_generico ?? i.nombre_comercial ?? ('Infusor #'+i.id)}</option>`).join('')}
                </select>
                <p class="text-xs text-gray-500" data-name="infusor_help">
                    Aparece solo si la mezcla contiene medicamento(s) que admiten infusor.
                </p>
            </div>
        </div>
        `;

            document.getElementById('contenedorMezclas').appendChild(mezclaDiv);
            (mezclaData.medicamentos || []).forEach(m => agregarFilaMedicamentoDesdeOld(idInternoMezcla, m));

            // Exclusión inicial
            const setCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');
            if (selInf.value) {
                setCb.checked = false;
                setCb.disabled = true;
            }

            actualizarNumeracionMezclas();
            updateInfusorDisponibilidad(idInternoMezcla);
        }

        function agregarFilaMedicamentoDesdeOld(idMezcla, med) {
            contadorFilasGlobal++;
            const tbody = document.getElementById(`medicamentos_mezcla_${idMezcla}`);
            const fila = document.createElement('tr');
            fila.id = `fila_m${idMezcla}_f${contadorFilasGlobal}`;

            fila.innerHTML = `
        <td class="border">
            <select data-name="medicamento" class="min-w-52 w-full border rounded px-2 py-1 text-sm"
                onchange="actualizarDiluentesYVias(this, ${idMezcla}, ${contadorFilasGlobal}); actualizarOpcionesMedicamentos(${idMezcla})">
                <option value="">Seleccione el medicamento</option>
                ${medicamentos.map(m => `<option value="${m.id}" ${m.id == (med.medicamento_id ?? '') ? 'selected' : ''}>
                            ${m.denominacion}
                        </option>`).join('')}
            </select>
        </td>
        <td class="border">
            <input type="number" data-name="dosis" value="${med.dosis ?? ''}" class="min-w-28 w-full border-none px-2 py-1 text-sm">
        </td>
        <td class="min-w-20 whitespace-nowrap border px-3 py-2 text-xs font-semibold text-gray-700">
            MG
        </td>
        <td class="border">
            <select data-name="diluyente" class="min-w-44 w-full border rounded px-2 py-1 text-sm"></select>
        </td>
        <td class="border">
            <select data-name="via_administracion" class="min-w-44 w-full border rounded px-2 py-1 text-sm"></select>
        </td>
        <td class="border">
            <button type="button" onclick="eliminarFila('${fila.id}'); actualizarOpcionesMedicamentos(${idMezcla})" class="inline-flex items-center justify-center bg-red-500 text-white px-2 py-1 rounded" title="Eliminar medicamento" aria-label="Eliminar medicamento">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.5 2a1.5 1.5 0 0 0-1.415 1H4.75a.75.75 0 0 0 0 1.5h10.5a.75.75 0 0 0 0-1.5h-2.335A1.5 1.5 0 0 0 11.5 2h-3ZM6.28 6.22a.75.75 0 0 1 .78.72l.35 7.5a.75.75 0 0 1-1.5.07l-.35-7.5a.75.75 0 0 1 .72-.79Zm7.44 0a.75.75 0 0 1 .72.79l-.35 7.5a.75.75 0 1 1-1.5-.07l.35-7.5a.75.75 0 0 1 .78-.72ZM9.25 7a.75.75 0 0 1 1.5 0v7.5a.75.75 0 0 1-1.5 0V7Z" clip-rule="evenodd" />
                    <path d="M5.25 5.5h9.5l-.52 10.35A2.25 2.25 0 0 1 11.98 18H8.02a2.25 2.25 0 0 1-2.25-2.15L5.25 5.5Z" />
                </svg>
            </button>
        </td>
        `;

            tbody.appendChild(fila);

            actualizarDiluentesYVias(fila.querySelector('[data-name="medicamento"]'), idMezcla, contadorFilasGlobal);

            setTimeout(() => {
                fila.querySelector('[data-name="diluyente"]').value = med.diluyente_id ?? '';
                fila.querySelector('[data-name="via_administracion"]').value = med.via_administracion_id ?? '';
            }, 100);

            actualizarOpcionesMedicamentos(idMezcla);
        }

        function agregarFilaMedicamento(idMezcla) {
            agregarFilaMedicamentoDesdeOld(idMezcla, {});
        }

        function eliminarMezcla(btn) {
            const mezcla = btn.closest(".border");
            const idMezcla = mezcla.dataset.idInterno;
            mezcla.remove();
            actualizarNumeracionMezclas();
            actualizarOpcionesMedicamentos(idMezcla);
            updateInfusorDisponibilidad(idMezcla);
        }

        function eliminarFila(idFila) {
            const fila = document.getElementById(idFila);
            const idMezcla = fila.closest("tbody").id.split("_").pop();
            fila.remove();
            actualizarOpcionesMedicamentos(idMezcla);
            updateInfusorDisponibilidad(idMezcla);
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
            selectDiluyente.innerHTML = `<option value="">Diluyentes</option>` +
                data.diluyentes.map(d => `<option value="${d.id}">${d.name}</option>`).join('');

            const selectVia = fila.querySelector('[data-name="via_administracion"]');
            selectVia.innerHTML = `<option value="">Vía de admin</option>` +
                data.vias.map(v => `<option value="${v.id}">${v.name}</option>`).join('');

            // Recalcular si la mezcla admite infusor (mostrar/ocultar wrapper)
            updateInfusorDisponibilidad(idMezcla);
        }

        function actualizarOpcionesMedicamentos(idMezcla) {
            const selects = document.querySelectorAll(`#medicamentos_mezcla_${idMezcla} select[data-name="medicamento"]`);
            const seleccionados = Array.from(selects).map(s => s.value).filter(v => v);

            const diluyentesSet = new Set();
            const viasSet = new Set();

            seleccionados.forEach(id => {
                const info = infoAdicional[id];
                if (info) {
                    info.diluyentes.forEach(d => diluyentesSet.add(d.id));
                    info.vias.forEach(v => viasSet.add(v.id));
                }
            });

            selects.forEach(select => {
                const valorActual = select.value;
                select.innerHTML = '<option value="">Seleccione el medicamento</option>';

                medicamentos.forEach(m => {
                    const mId = m.id.toString();
                    const info = infoAdicional[m.id];
                    if (!info) return;

                    const yaSeleccionado = seleccionados.includes(mId) && mId !== valorActual;
                    const esPrimero = seleccionados.length === 0 || (seleccionados.length === 1 &&
                        valorActual === mId);

                    const compatibleDiluyente = info.diluyentes.some(d => diluyentesSet.has(d.id));
                    const compatibleVia = info.vias.some(v => viasSet.has(v.id));
                    const esCompatible = compatibleDiluyente && compatibleVia;

                    if (yaSeleccionado) return;

                    if (esPrimero || esCompatible || mId === valorActual) {
                        const option = document.createElement('option');
                        option.value = m.id;
                        option.text = `${m.denominacion}`;
                        if (mId === valorActual) option.selected = true;
                        select.appendChild(option);
                    }
                });
            });
        }

        // ====== Lógica Infusor vs Set (mostrar/ocultar select) ======

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
                const medSel = fila.querySelector('[data-name="medicamento"]');
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
            const wrapper = mezclaDiv.querySelector('[data-name="infusor_wrapper"]');

            const admite = mezclaAdmiteInfusor(idMezcla);

            // Mostrar/ocultar el bloque completo según admita
            if (admite) {
                wrapper.style.display = '';
            } else {
                wrapper.style.display = 'none';
                // Al ocultar: limpiar y deshabilitar
                selInf.value = '';
                selInf.disabled = true;
            }

            // Si set está marcado → siempre deshabilitar select (aunque se vea el wrapper)
            if (setCb && setCb.checked) {
                selInf.value = '';
                selInf.disabled = true;
                return;
            }

            // Si admite y no hay set → habilitar select
            if (admite) {
                selInf.disabled = false;
            }
        }

        function toggleSetInfusion(checkbox, idMezcla) {
            const mezclaDiv = checkbox.closest('.border');
            const selInf = mezclaDiv.querySelector('[data-name="infusor_id"]');

            if (checkbox.checked) {
                selInf.value = '';
                selInf.disabled = true;
            } else {
                // Solo habilitar si la mezcla admite infusor
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

        // ====== Submit ======
        document.getElementById("formularioSolicitud").addEventListener("submit", function(e) {
            e.preventDefault();
            const mezclas = [];
            let errorMezclaInvalida = false;

            document.querySelectorAll('#contenedorMezclas > .border').forEach((mezclaDiv) => {
                const idInterno = mezclaDiv.dataset.idInterno;
                const volumen = mezclaDiv.querySelector('[data-name="volumen_dilucion"]')?.value;
                const tiempo = mezclaDiv.querySelector('[data-name="tiempo_infusion"]')?.value;

                const setInfusionCb = mezclaDiv.querySelector('[data-name="set_infusion"]');
                const selInfusor = mezclaDiv.querySelector('[data-name="infusor_id"]');
                const set_infusion = !!(setInfusionCb && setInfusionCb.checked);
                const infusor_id = selInfusor && selInfusor.value ? selInfusor.value : null;

                const medicamentosArr = [];
                let diluyenteRef = null;
                let viaRef = null;

                mezclaDiv.querySelectorAll(`#medicamentos_mezcla_${idInterno} tr`).forEach((fila,
                    index) => {
                    const medicamentoSelect = fila.querySelector('[data-name="medicamento"]');
                    const dosisInput = fila.querySelector('[data-name="dosis"]');
                    const diluyenteSelect = fila.querySelector('[data-name="diluyente"]');
                    const viaSelect = fila.querySelector('[data-name="via_administracion"]');

                    if (!medicamentoSelect?.value || !dosisInput?.value) return;

                    const diluyente = diluyenteSelect?.value;
                    const via = viaSelect?.value;

                    if (index === 0) {
                        diluyenteRef = diluyente;
                        viaRef = via;
                    } else if (diluyente !== diluyenteRef || via !== viaRef) {
                        errorMezclaInvalida = true;
                    }

                    medicamentosArr.push({
                        medicamento_id: medicamentoSelect.value, // id de catálogo genérico
                        nombre: medicamentoSelect.options[medicamentoSelect.selectedIndex]
                            .text,
                        dosis: dosisInput.value,
                        diluyente_id: diluyente || null,
                        via_administracion_id: via || null
                    });
                });

                if (medicamentosArr.length > 0) {
                    if (set_infusion && infusor_id) errorMezclaInvalida = true;

                    mezclas.push({
                        volumen_dilucion: volumen,
                        tiempo_infusion: tiempo,
                        set_infusion: set_infusion,
                        infusor_id: infusor_id,
                        medicamentos: medicamentosArr
                    });
                }
            });

            if (errorMezclaInvalida) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en mezcla',
                    text: 'Todos los medicamentos de una mezcla deben tener el mismo diluyente y la misma vía; además, elige set de infusión o un infusor, no ambos.',
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    }
                });
                return;
            }

            document.getElementById("mezclas_json").value = JSON.stringify(mezclas);

            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta solicitud será registrada.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
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
            if (mezclasOld.length > 0) {
                mezclasOld.forEach(m => agregarMezclaDesdeOld(m));
            } else {
                agregarMezcla();
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
