@php
    require_once app_path('Helpers/helpers.php');

    $aguaCalculada = 0.0;

    foreach ($inputs as $inputCalc) {
        if ($inputCalc->category_id == 7) {
            $aguaCalculada = (float) renderInputMLSection($inputCalc->input_id, $inputs_solicitud);
        }
    }

    $volumenTotalFinal = $solicitud->solicitud_detail->volumen_total_final;

    $bolsaSeleccionada = $inputs
        ->filter(function ($input) use ($volumenTotalFinal) {
            $presentations = $input->presentations_disponibles ?? collect();

            return $input->category_id == 6 &&
                $presentations->contains(function ($presentation) use ($volumenTotalFinal) {
                    return (float) $presentation->presentacion_ml >= (float) $volumenTotalFinal;
                });
        })
        ->sortBy(function ($input) {
            $presentations = $input->presentations_disponibles ?? collect();
            return optional($presentations->first())->presentacion_ml ?? 999999;
        })
        ->first();

    $bolsaSeleccionadaId = $bolsaSeleccionada ? $bolsaSeleccionada->input_id : null;

    $bolsaEvaSolicitud = collect($inputs_solicitud)->first(function ($item) {
        return optional($item->input)->category_id == 6;
    });

    if ($bolsaEvaSolicitud) {
        $bolsaSeleccionadaId = $bolsaEvaSolicitud->input_id;
    }

    $renderInputRow = function ($input) use ($inputs_solicitud) {
        $inputValue = old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud));
        $hasData = $inputValue !== null && $inputValue !== '';

        $presentations = $input->presentations_disponibles ?? collect();

        $presentation = null;

        if ($input->presentation_precargada_id ?? null) {
            $presentation = $presentations->firstWhere('id', $input->presentation_precargada_id);
        }

        if (!$presentation && ($input->presentation_activa_id ?? null)) {
            $presentation = $presentations->firstWhere('id', $input->presentation_activa_id);
        }

        if (!$presentation && $presentations->count() > 0) {
            $presentation = $presentations->first();
        }

        $loteValue = old(
            'l_' . $input->input_id,
            $input->lote_precargado ?? renderLoteSection($input->input_id, $inputs_solicitud),
        );

        $caducidadValue = old(
            'c_' . $input->input_id,
            $input->caducidad_precargada ?? renderCaducidadSection($input->input_id, $inputs_solicitud),
        );

        if ($caducidadValue) {
            try {
                $caducidadValue = \Carbon\Carbon::parse($caducidadValue)->format('Y-m-d');
            } catch (\Throwable $e) {
                $caducidadValue = '';
            }
        }

        return [
            'inputValue' => $inputValue,
            'hasData' => $hasData,
            'presentation' => $presentation,
            'presentationId' => old('p_' . $input->input_id, $presentation?->id),
            'loteValue' => $loteValue,
            'caducidadValue' => $caducidadValue,
        ];
    };

    $secciones = [
        'AMINOÁCIDOS' => [1, 8],
        'CARBOHIDRATOS' => [2],
        'LÍPIDOS' => [3],
        'ELECTROLITOS' => [4],
        'ADITIVOS' => [5],
    ];

    $estadoSolicitud = str_replace('-', '_', mb_strtolower(trim((string) ($solicitud->estado ?? 'pendiente'))));
    $isPendingApproval = $estadoSolicitud === 'pendiente';
    $isApprovalMode = request()->boolean('approval') || $isPendingApproval;
    $returnTo = old('return_to', request('return_to', ''));
    $closeHref = $returnTo !== '' ? $returnTo : route('admin.nutricionales.solicitudes.index');
    $formTitle = $isApprovalMode
        ? 'Aprobación de mezcla #'.$solicitud->id
        : 'SOLICITUD DE NUTRICIÓN PARENTERAL';
    $calculationPreview = session('nutrition_calculation_preview', []);
    $displayOverfill = $calculationPreview['sobrellenado_ml']
        ?? old('sobrellenado_ml', $solicitud->solicitud_detail->sobrellenado_ml);
    $displayTotalVolume = $calculationPreview['volumen_total']
        ?? old('volumen_total', $solicitud->solicitud_detail->volumen_total);
    $displayCalculatedVolume = $calculationPreview['suma_volumen']
        ?? $solicitud->solicitud_detail->suma_volumen;
    $displayFinalVolume = $calculationPreview['volumen_total_final']
        ?? $solicitud->solicitud_detail->volumen_total_final;
@endphp

<x-admin-layout>
    <div class="flex flex-col items-center">
        <div class="mt-2 mb-4 flex w-full flex-wrap items-center gap-4">
            <h1 data-workflow-heading class="mixture-workflow-heading">
                {{ $isApprovalMode ? 'Aprobación' : 'Nutrición parenteral' }} | {{ \App\Support\MixtureWorkflowContext::label($solicitud->id, $solicitud->user?->hospital) }}
            </h1>
            <div data-workflow-approval-actions class="ml-auto flex flex-wrap items-center justify-end gap-3">
                @if ($isApprovalMode && $isPendingApproval)
                    <x-button form="solicitudForm" type="submit" class="bg-blue-600 hover:bg-blue-700"
                        onclick="document.getElementById('accion_input').value = 'actualizar'">
                        GUARDAR CAMBIOS
                    </x-button>
                    <x-button form="solicitudForm" type="button" class="bg-green-600 hover:bg-green-700"
                        onclick="updateAccion('aprobar')">
                        APROBAR MEZCLA
                    </x-button>
                    <x-button form="solicitudForm" type="button" formnovalidate class="bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:ring-red-500"
                        onclick="updateAccion('rechazar')">
                        RECHAZAR MEZCLA
                    </x-button>
                @endif
                <a href="{{ $closeHref }}"
                    data-workflow-popup-close
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border-2 border-red-600 text-2xl font-semibold leading-none text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                    title="Cerrar formato de solicitud"
                    aria-label="Cerrar formato de solicitud">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-4 text-red-700 w-full">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li class="whitespace-pre-line">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="w-full mb-6 text-sm text-gray-700 bg-gray-50 border rounded p-4">
            @if ($displayOverfill !== null && $displayOverfill !== '')
                <p>Sobrellenado ingresado: <strong>{{ $displayOverfill }} mL</strong></p>
            @else
                <p>El usuario no ingresó sobrellenado.</p>
            @endif

            @if ($displayTotalVolume !== null && $displayTotalVolume !== '')
                <p>Volumen total ingresado: <strong>{{ $displayTotalVolume }} mL</strong></p>
                <p>Suma de elementos en mL:
                    <strong>{{ number_format((float) $displayCalculatedVolume, 3, '.', '') }}</strong>
                </p>

                @if ((float) $displayTotalVolume < (float) $displayCalculatedVolume)
                    <h2 class="text-red-500 font-bold">
                        El volumen total es menor a la suma calculada. Verifica los valores.
                    </h2>
                @endif

                @if (
                    (float) $displayTotalVolume > 0 &&
                        60 < ($aguaCalculada / (float) $displayTotalVolume) * 100)
                    <h2 class="text-red-500 font-bold">
                        El agua calculada supera el 60% del volumen total. Reajusta el volumen total.
                    </h2>
                @endif
            @else
                <p>El usuario no ingresó volumen total.</p>
            @endif

            <p>Volumen total final:
                <strong>{{ number_format((float) $displayFinalVolume, 2, '.', '') }} mL</strong>
            </p>
        </div>

        <form id="solicitudForm" action="{{ route('admin.nutricionales.solicitudes.update', $solicitud) }}" method="POST"
            class="bg-white rounded-lg p-6 shadow-lg w-full">

            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @if (old('approval_popup', request('approval_popup')))
                <input type="hidden" name="approval_popup" value="1">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label class="mb-2 font-bold">Paciente Nombre(s):</x-label>
                    <x-input-solicitud
                        value="{{ old('nombre_paciente', $solicitud->solicitud_patient->nombre_paciente) }}"
                        name="nombre_paciente" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Paciente Apellidos:</x-label>
                    <x-input-solicitud
                        value="{{ old('apellidos_paciente', $solicitud->solicitud_patient->apellidos_paciente) }}"
                        name="apellidos_paciente" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Servicio:</x-label>
                    <x-input-solicitud value="{{ old('servicio', $solicitud->solicitud_patient->servicio) }}"
                        name="servicio" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Registro:</x-label>
                    <x-input-solicitud value="{{ old('registro', $solicitud->solicitud_patient->registro) }}"
                        name="registro" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Cama:</x-label>
                    <x-input-solicitud value="{{ old('cama', $solicitud->solicitud_patient->cama) }}" name="cama"
                        class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Piso:</x-label>
                    <x-input-solicitud value="{{ old('piso', $solicitud->solicitud_patient->piso) }}" name="piso"
                        class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Diagnóstico:</x-label>
                    <x-input-solicitud value="{{ old('diagnostico', $solicitud->solicitud_patient->diagnostico) }}"
                        name="diagnostico" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Peso:</x-label>
                    <div class="flex">
                        <x-input-solicitud type="number"
                            value="{{ old('peso', $solicitud->solicitud_patient->peso) }}" step="0.001"
                            name="peso" class="w-full" />
                        <span class="ml-2">Kg</span>
                    </div>
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Sexo:</x-label>
                    <x-select class="w-full" name="sexo">
                        <option value="" disabled>Seleccionar Sexo</option>
                        <option value="Femenino" @selected(old('sexo', $solicitud->solicitud_patient->sexo) == 'Femenino')>Femenino</option>
                        <option value="Masculino" @selected(old('sexo', $solicitud->solicitud_patient->sexo) == 'Masculino')>Masculino</option>
                    </x-select>
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Fecha de nacimiento:</x-label>
                    <x-input-solicitud type="date"
                        value="{{ old('fecha_nacimiento', $solicitud->solicitud_patient->fecha_nacimiento) }}"
                        max="{{ date('Y-m-d') }}" name="fecha_nacimiento" class="w-full"
                        onchange="calcularEdad(this.value)" />
                </div>
            </div>

            <hr class="my-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-label class="mb-2 font-bold">Vía de administración:</x-label>
                    <x-select class="w-full" name="via_administracion">
                        <option value="Central" @selected(old('via_administracion', $solicitud->solicitud_detail->via_administracion) == 'Central')>Central</option>
                        <option value="Periférica" @selected(old('via_administracion', $solicitud->solicitud_detail->via_administracion) == 'Periférica')>Periférica</option>
                    </x-select>
                </div>

                @php
                    $inputValueVelocidad = old('velocidad_infusion', $solicitud->solicitud_detail->velocidad_infusion);
                    $hasVelocidad = $inputValueVelocidad ? true : false;
                @endphp

                <div>
                    <x-label class="mb-2 font-bold">Tiempo de infusión (h):</x-label>
                    <x-input-solicitud type="number"
                        value="{{ $hasVelocidad ? '' : old('tiempo_infusion_min', $solicitud->solicitud_detail->tiempo_infusion_min) }}"
                        name="tiempo_infusion_min" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Velocidad de infusión ml/hr:</x-label>
                    <x-input-solicitud type="number"
                        value="{{ old('velocidad_infusion', $solicitud->solicitud_detail->velocidad_infusion) }}"
                        step="0.001" name="velocidad_infusion" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Sobrellenado (mL):</x-label>
                    <x-input-solicitud type="number"
                        value="{{ old('sobrellenado_ml', $solicitud->solicitud_detail->sobrellenado_ml) }}"
                        step="0.0001" name="sobrellenado_ml" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Volumen total (mL):</x-label>
                    <x-input-solicitud type="number"
                        value="{{ old('volumen_total', $solicitud->solicitud_detail->volumen_total) }}"
                        name="volumen_total" step="0.0001" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">NPT:</x-label>
                    <x-select class="w-full" name="npt" id="npt-select">
                        <option value="INF" @selected(old('npt', $solicitud->solicitud_detail->npt) == 'INF')>PEDIÁTRICO</option>
                        <option value="ADULT" @selected(old('npt', $solicitud->solicitud_detail->npt) == 'ADULT')>ADULTO</option>
                    </x-select>
                </div>
            </div>

            <hr class="my-6">

            @foreach ($secciones as $titulo => $categorias)
                <h2 class="mb-4 mt-6 font-bold text-lg">{{ $titulo }}</h2>
                <hr class="mb-4">

                @foreach ($inputs as $input)
                    @if (in_array($input->category_id, $categorias))
                        @php
                            $row = $renderInputRow($input);

                            $unidadClass = '';
                            $unidadData = '';

                            if (in_array($input->category_id, [1, 2, 3])) {
                                $unidadClass = 'unidad-span';
                                $unidadData = $input->unidad;
                            } elseif ($input->category_id == 4) {
                                $unidadClass = 'unidad-span-electrolitos';
                                $unidadData = $input->unidad;
                            }
                        @endphp

                        <div
                            class="mb-4 flex flex-wrap items-baseline gap-2 w-full {{ $row['hasData'] ? 'bg-yellow-200' : '' }} p-2 rounded">
                            <div class="flex w-[28%]">
                                <x-label class="mb-2 whitespace-nowrap font-bold">
                                    {{ $input->description }}:
                                </x-label>

                                <div class="flex w-full">
                                    <x-input-solicitud type="number" class="w-full"
                                        value="{{ $row['inputValue'] }}" name="i_{{ $input->input_id }}"
                                        id="i_{{ $input->input_id }}" step="0.0001" />

                                    <span class="{{ $unidadClass }}"
                                        @if ($unidadData !== '') data-original-unidad="{{ $unidadData }}" @endif>
                                        {{ $input->unidad }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex w-[8%] justify-center items-stretch">
                                <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                <p class="flex border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                    {{ fmt3(renderInputMLSection($input->input_id, $inputs_solicitud)) }}
                                </p>
                            </div>

                            <div class="flex w-[14%] justify-center items-stretch">
                                <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                <p class="flex border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                    {{ fmt3(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud)) }}
                                </p>
                            </div>

                            @include('admin.nutricionales.solicitudes.partials.presentacion-lote-select')
                        </div>
                    @endif
                @endforeach
            @endforeach

            @foreach ($inputs as $input)
                @if ($input->category_id == 10)
                    @php
                        $row = $renderInputRow($input);
                    @endphp

                    <h2 class="mb-4 mt-6 font-bold text-lg">MATERIAL</h2>

                    <div class="mb-4 flex flex-wrap items-baseline gap-2 w-full p-2 rounded">
                        <div class="flex w-[25%]">
                            <x-label class="mb-2 whitespace-nowrap font-bold">
                                {{ $input->description }}:
                            </x-label>

                            <x-select class="w-full" name="i_{{ $input->input_id }}" id="i_{{ $input->input_id }}">
                                <option value="0" @selected(old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) == '0')>No</option>
                                <option value="1" @selected(old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) == '1')>Sí</option>
                            </x-select>
                        </div>

                        @include('admin.nutricionales.solicitudes.partials.presentacion-lote-select')
                    </div>
                @endif
            @endforeach

            @php
                $bolsaInputSeleccionada = $inputs->firstWhere('input_id', $bolsaSeleccionadaId);
                $rowBolsa = $bolsaInputSeleccionada ? $renderInputRow($bolsaInputSeleccionada) : null;
            @endphp

            <h2 class="mb-4 mt-6 font-bold text-lg">BOLSA EVA</h2>

            <div class="flex flex-wrap gap-2 items-center">
                <div class="flex items-center w-4/12">
                    <x-label class="mb-2 whitespace-nowrap">Bolsa Eva:</x-label>

                    <x-select class="w-full" name="bolsa_eva" id="bolsa_eva">
                        <option value="" disabled>Seleccionar Bolsa Eva</option>

                        @foreach ($inputs as $inputBolsa)
                            @if ($inputBolsa->category_id == 6)
                                <option value="{{ $inputBolsa->input_id }}" @selected(old('bolsa_eva', $bolsaSeleccionadaId) == $inputBolsa->input_id)>
                                    {{ $inputBolsa->description }}
                                </option>
                            @endif
                        @endforeach
                    </x-select>
                </div>

                @if ($bolsaInputSeleccionada && $rowBolsa)
                    @php
                        $input = $bolsaInputSeleccionada;
                        $row = $rowBolsa;
                    @endphp

                    @include('admin.nutricionales.solicitudes.partials.presentacion-lote-select')
                @endif
            </div>

            <div class="mb-4 mt-6">
                <x-label class="mb-2 font-bold">OBSERVACIONES</x-label>
                <textarea class="border-2 border-solid w-full resize-x overflow-auto h-20" name="observaciones">{{ old('observaciones', $solicitud->solicitud_detail->observaciones) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label class="mb-2 font-bold">Fecha y hora de entrega:</x-label>
                    <x-input-solicitud type="datetime-local"
                        value="{{ old('fecha_hora_entrega', $solicitud->solicitud_detail->fecha_hora_entrega) }}"
                        min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" name="fecha_hora_entrega"
                        class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Nombre del médico:</x-label>
                    <x-input-solicitud value="{{ old('nombre_medico', $solicitud->solicitud_detail->nombre_medico) }}"
                        name="nombre_medico" class="w-full" />
                </div>

                <div>
                    <x-label class="mb-2 font-bold">Cédula profesional:</x-label>
                    <x-input-solicitud value="{{ old('cedula', $solicitud->solicitud_detail->cedula) }}"
                        name="cedula" class="w-full" />
                </div>
            </div>

            <input type="hidden" name="accion" id="accion_input" value="actualizar">

            @if (! $isApprovalMode)
                <div class="flex justify-end gap-5 mt-6 mb-3">
                    <x-button>ACTUALIZAR</x-button>
                </div>
            @endif
        </form>
    </div>

    <script>
        window.inventarioPorInput = @json($inventarioPorInput);
    </script>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const inventarioPorInput = window.inventarioPorInput || {};

                function cargarLotes(inputId) {
                    const presentationSelect = document.getElementById(`p_${inputId}`);
                    const loteSelect = document.getElementById(`l_${inputId}`);
                    const caducidadInput = document.getElementById(`c_${inputId}`);

                    if (!presentationSelect || !loteSelect || !caducidadInput) return;

                    const presentationId = parseInt(presentationSelect.value || 0);
                    const selectedLote = loteSelect.dataset.selectedLote || '';

                    loteSelect.innerHTML = '<option value="">Seleccione lote</option>';
                    caducidadInput.value = '';

                    const inputData = inventarioPorInput[inputId];

                    if (!inputData || !inputData.presentations) return;

                    const presentation = inputData.presentations.find(p => parseInt(p.id) === presentationId);

                    if (!presentation || !presentation.stocks || presentation.stocks.length === 0) return;

                    presentation.stocks.forEach((stock, index) => {
                        const option = document.createElement('option');

                        option.value = stock.lote;
                        option.dataset.caducidad = stock.caducidad;
                        option.textContent = stock.is_remainder
                            ? `${stock.lote} - REMANENTE: ${stock.stock_ml_actual} mL - Usar antes de: ${stock.usable_until}`
                            : `${stock.lote} - Cad: ${stock.caducidad} - Stock cerrado: ${stock.stock_ml_actual} mL`;

                        if (selectedLote) {
                            option.selected = stock.lote === selectedLote;
                        } else if (index === 0) {
                            option.selected = true;
                        }

                        loteSelect.appendChild(option);
                    });

                    actualizarCaducidad(inputId);
                }

                function actualizarCaducidad(inputId) {
                    const loteSelect = document.getElementById(`l_${inputId}`);
                    const caducidadInput = document.getElementById(`c_${inputId}`);

                    if (!loteSelect || !caducidadInput) return;

                    const selectedOption = loteSelect.options[loteSelect.selectedIndex];
                    caducidadInput.value = selectedOption?.dataset?.caducidad || '';
                }

                document.querySelectorAll('.presentation-select').forEach(select => {
                    cargarLotes(select.dataset.inputId);

                    select.addEventListener('change', function() {
                        const inputId = this.dataset.inputId;
                        const loteSelect = document.getElementById(`l_${inputId}`);

                        if (loteSelect) {
                            loteSelect.dataset.selectedLote = '';
                        }

                        cargarLotes(inputId);
                    });
                });

                document.querySelectorAll('.lote-select').forEach(select => {
                    select.addEventListener('change', function() {
                        actualizarCaducidad(this.dataset.inputId);
                    });
                });
            });

            function calcularEdad(fechaNacimiento) {
                const fechaNacimientoDate = new Date(fechaNacimiento);
                const fechaActual = new Date();

                let edadAnios = fechaActual.getFullYear() - fechaNacimientoDate.getFullYear();
                let edadMeses = fechaActual.getMonth() - fechaNacimientoDate.getMonth();
                let edadDias = fechaActual.getDate() - fechaNacimientoDate.getDate();

                if (edadDias < 0) {
                    edadMeses--;
                    const ultimoDiaMesAnterior = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 0).getDate();
                    edadDias = ultimoDiaMesAnterior + edadDias;
                }

                if (edadMeses < 0) {
                    edadAnios--;
                    edadMeses = 12 + edadMeses;
                }
            }

            function updateAccion(value) {
                const form = document.getElementById('solicitudForm');
                const accionInput = document.getElementById('accion_input');
                const isRejectAction = value === 'rechazar';

                Swal.fire({
                    title: `¿Seguro que deseas ${value === 'aprobar' ? 'aprobar' : 'rechazar'} esta mezcla?`,
                    text: isRejectAction ? 'La mezcla quedará fuera del proceso de preparación.' : '',
                    icon: isRejectAction ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonText: value === 'aprobar' ? 'Sí, aprobar' : 'Sí, rechazar',
                    cancelButtonText: "Cancelar",
                }).then((result) => {
                    if (result.isConfirmed) {
                        accionInput.value = value;

                        if (isRejectAction || form.checkValidity()) {
                            form.submit();
                        } else {
                            const primerCampoInvalido = form.querySelector(':invalid');

                            if (primerCampoInvalido) {
                                primerCampoInvalido.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });

                                setTimeout(() => {
                                    primerCampoInvalido.focus();
                                    primerCampoInvalido.reportValidity();
                                }, 300);
                            }
                        }
                    }
                });
            }

            document.addEventListener("DOMContentLoaded", function() {
                const inputTiempo = document.querySelector('input[name="tiempo_infusion_min"]');
                const inputVelocidad = document.querySelector('input[name="velocidad_infusion"]');

                function toggleInputState() {
                    if (inputTiempo?.value) {
                        inputVelocidad.disabled = true;
                    } else if (inputVelocidad?.value) {
                        inputTiempo.disabled = true;
                    } else {
                        inputVelocidad.disabled = false;
                        inputTiempo.disabled = false;
                    }
                }

                toggleInputState();

                inputTiempo?.addEventListener('input', toggleInputState);
                inputVelocidad?.addEventListener('input', toggleInputState);
            });

            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');

                function actualizarUnidades() {
                    document.querySelectorAll('.unidad-span').forEach((unidad) => {
                        unidad.textContent = selectNPT.value === 'ADULT' ?
                            'g/día' :
                            unidad.getAttribute('data-original-unidad');
                    });

                    document.querySelectorAll('.unidad-span-electrolitos').forEach((unidad) => {
                        unidad.textContent = selectNPT.value === 'ADULT' ?
                            'mEq/día' :
                            unidad.getAttribute('data-original-unidad');
                    });
                }

                actualizarUnidades();
                selectNPT?.addEventListener('change', actualizarUnidades);
            });
        </script>
    @endpush
</x-admin-layout>
