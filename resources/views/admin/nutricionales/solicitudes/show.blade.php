@php
    // Incluye manualmente el archivo helpers.php
    require_once app_path('Helpers/helpers.php');

    $aguaCalculada = 0.0; // Inicializa la variable

    foreach ($inputs as $input) {
        if ($input->category_id == 7) {
            // Tu helper ya devuelve float limpio
            $aguaCalculada = renderInputMLSection($input->input_id, $inputs_solicitud);
            break;
        }
    }

    $bolsaSeleccionadaId = null;
    $volumenTotalFinal = $solicitud->solicitud_detail->volumen_total_final;

    $bolsaSeleccionada = $inputs
        ->filter(function ($input) use ($volumenTotalFinal) {
            return $input->category_id == 6 && (float) ($input->presentation_ml ?? 0) >= (float) $volumenTotalFinal;
        })
        ->sortBy('presentation_ml')
        ->first();

    $bolsaSeleccionadaId = $bolsaSeleccionada ? $bolsaSeleccionada->input_id : null;

    $loteBolsaEva = $bolsaSeleccionada ? $bolsaSeleccionada->lote : null;

    $caducidadBolsaEva =
        $bolsaSeleccionada && $bolsaSeleccionada->caducidad
            ? \Carbon\Carbon::parse($bolsaSeleccionada->caducidad)->format('Y-m-d')
            : null;
@endphp

<x-admin-layout>

    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Detalles de solicitud</h1>
    </div>

    @hasanyrole('Admin|Super Admin|Cliente|Institucion')
        <div class="flex mb-8 justify-end items-baseline">
            <div class="mt-4">
                <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                    href="{{ route('admin.nutricionales.solicitudes.solicitud', $solicitud) }}" target="_blank">Solicitud</a>
            </div>
            @if (in_array($solicitud->estado, ['aprobada', 'preparada', 'revisada']))
                @hasanyrole('Admin|Super Admin')
                    <div class="mt-4">
                        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                            href="{{ route('admin.nutricionales.solicitudes.ordenPreparacion', $solicitud) }}"
                            target="_blank">Orden de
                            preparación</a>
                    </div>
                    <div class="mt-5">
                        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                            href="{{ route('admin.nutricionales.solicitudes.remision', $solicitud) }}"
                            target="_blank">Remisión</a>
                    </div>
                    <div class="mt-5">
                        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                            href="{{ route('admin.nutricionales.solicitudes.envio', $solicitud) }}" target="_blank">Envío</a>
                    </div>
                    <div class="mt-5">
                        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                            href="{{ route('admin.nutricionales.solicitudes.etiqueta', $solicitud) }}"
                            target="_blank">Etiqueta</a>
                    </div>
                @endhasanyrole
            @else
                <div class="flex items-center">
                    <!-- Contenido para solicitudes no aprobadas -->
                </div>
            @endif
        </div>
    @endhasanyrole

    {{-- Para visualizar la solicitud en cualquier caso, es decir, si fue aceptada o no, no se puede editar --}}
    <div class="flex flex-col items-center">
        <div class="mt-2 mb-4">
            <h1 class="text-2xl font-medium text-gray-800 text-center">SOLICITUD DE NUTRICIÓN PARENTERAL</h1>
        </div>

        @if ($solicitud->solicitud_detail->sobrellenado_ml !== null)
            <p>El usuario ingreso un valor en el campo de sobrellenado:
                {{ $solicitud->solicitud_detail->sobrellenado_ml }} </p>
            @if ($solicitud->solicitud_detail->volumen_total !== null)
                <p>Volumen total ingresado por el usuario: {{ $solicitud->solicitud_detail->volumen_total }}</p>
                <p>Suma de elementos ingresados por el usuario en mL:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen, 3, '.', '') }}
                <p>Agua calculada:
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 7)
                            {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                        @endif
                    @endforeach
                </p>
                @if ($solicitud->solicitud_detail->volumen_total < $solicitud->solicitud_detail->suma_volumen)
                    <h2 class="text-red-500">El volumen total en mL que ingresó el usuario es menor a la suma total en
                        mL
                        de los elementos calculada. <br>
                        Verifica los valores, el cálculo del agua es negativo.</h2>
                @endif
                @if (60 < ($aguaCalculada / $solicitud->solicitud_detail->volumen_total) * 100)
                    <h2 class="text-red-500">El volumen total en mL que se genero es mayor al 60% del volumen total de
                        la
                        mezcla. <br>
                        Reajusta el volumen total para generar un nuevo valor para el agua.</h2>
                @endif
                <p>Volumen total ingresado por el usuario con sobrellenado:
                    {{ number_format($solicitud->solicitud_detail->volumen_total_final, 2, '.', '') }}</p>
                <p>Suma total de los elementos ingresados por el usuario con sobrellenado:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen_sobrellenado, 3, '.', '') }}</p>
                <p>Agua calculada con sobrellenado:
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 7)
                            {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                        @endif
                    @endforeach
                </p>
            @else
                <p>El usuario no ingreso un volumen total.</p>
                <p>Suma de elementos ingresados por el usuario en mL:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen, 3, '.', '') }}</p>
                <p>Suma de elementos ingresados por el usuario en mL con sobrellenado:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen_sobrellenado, 3, '.', '') }}</p>
            @endif
        @else
            <p>El usuario no ingresó un valor en sobrellenado.</p>
            @if ($solicitud->solicitud_detail->volumen_total !== null)
                <p>Volumen total ingresado por el usuario: {{ $solicitud->solicitud_detail->volumen_total }}</p>
                <p>Suma de elementos ingresados por el usuario en mL:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen, 3, '.', '') }}</p>
                <p>Agua calculada:
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 7)
                            {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                        @endif
                    @endforeach
                </p>
                @if ($solicitud->solicitud_detail->volumen_total < $solicitud->solicitud_detail->suma_volumen)
                    <h2 class="text-red-500">El volumen total en mL que ingresó el usuario es menor a la suma total en
                        mL
                        de los elementos calculada. <br>
                        Verifica los valores, el cálculo del agua es negativo.</h2>
                @endif
                @if (60 < ($aguaCalculada / $solicitud->solicitud_detail->volumen_total) * 100)
                    <h2 class="text-red-500">El volumen total en mL que se genero es mayor al 60% del volumen total de
                        la
                        mezcla. <br>
                        Reajusta el volumen total para generar un nuevo valor para el agua.</h2>
                @endif
            @else
                <p>El usuario no ingreso un volumen total.</p>
                <p>Suma de elementos ingresados por el usuario en mL:
                    {{ number_format($solicitud->solicitud_detail->suma_volumen, 3, '.', '') }}</p>
            @endif
        @endif

        <div class="mb-4">
            <span class="font-bold">Estado:</span>

            <span
                class="
        px-3 py-1 rounded text-white
        @if ($solicitud->estado === 'pendiente') bg-yellow-500
        @elseif($solicitud->estado === 'aprobada') bg-blue-500
        @elseif($solicitud->estado === 'preparada') bg-indigo-500
        @elseif($solicitud->estado === 'revisada') bg-green-600
        @elseif($solicitud->estado === 'cancelada') bg-red-600 @endif
    ">
                {{ strtoupper($solicitud->estado) }}
            </span>
        </div>
        <form id="solicitudForm" class="bg-white rounded-lg p-6 shadow-lg">
            @csrf
            @method('PUT')
            <x-validation-errors class="mb-4" />

            <div class="flex gap-4">
                <div class="mb-4 flex  items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Paciente Nombre(s):
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud
                            value="{{ old('nombre_paciente', $solicitud->solicitud_patient->nombre_paciente) }}"
                            name="nombre_paciente" class="w-full" placeholder="" disabled />
                    </div>
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Paciente Apellidos:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud
                            value="{{ old('apellidos_paciente', $solicitud->solicitud_patient->apellidos_paciente) }}"
                            name="apellidos_paciente" class="w-full" placeholder="" disabled />
                    </div>
                </div>
            </div>

            <div class="flex gap-4 ">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Servicio:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('servicio', $solicitud->solicitud_patient->servicio) }}"
                            name="servicio" class="" placeholder="" disabled />
                    </div>
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Cama:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('cama', $solicitud->solicitud_patient->cama) }}"
                            name="cama" class="" placeholder="" disabled />
                    </div>
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Piso:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('piso', $solicitud->solicitud_patient->piso) }}"
                            name="piso" class="" placeholder="" disabled />
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Registro:
                    </x-label>
                    <x-input-solicitud value="{{ old('registro', $solicitud->solicitud_patient->registro) }}"
                        name="registro" class="w-full" placeholder="" disabled />
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Diagnóstico:
                    </x-label>
                    <x-input-solicitud value="{{ old('diagnostico', $solicitud->solicitud_patient->diagnostico) }}"
                        name="diagnostico" class="w-full" placeholder="" disabled />
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2">
                        Peso:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud type="number"
                            value="{{ old('peso', $solicitud->solicitud_patient->peso) }}" step="0.001"
                            name="peso" class="w-full" placeholder="" disabled />
                    </div>
                </div>
                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2">
                        Sexo:
                    </x-label>
                    <x-select class="w-full" name="sexo" disabled>
                        <option value="Femenino" @if (old('sexo', $solicitud->solicitud_patient->sexo) == 'Femenino') selected @endif>Femenino</option>
                        <option value="Masculino" @if (old('sexo', $solicitud->solicitud_patient->sexo) == 'Masculino') selected @endif>Masculino</option>
                    </x-select>
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Fecha de nacimiento*:
                    </x-label>
                    <div class="flex flex-col">
                        <x-input-solicitud type="date"
                            value="{{ old('fecha_nacimiento', $solicitud->solicitud_patient->fecha_nacimiento) }}"
                            max="{{ date('Y-m-d') }}" name="fecha_nacimiento" class="" placeholder=""
                            onchange="calcularEdad(this.value)" disabled />
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Vía de administración:
                    </x-label>
                    <x-select class="w-full" name="via_administracion" disabled>
                        <option value="Central" @if (old('via_administracion', $solicitud->solicitud_detail->via_administracion) == 'Central') selected @endif>Central</option>
                        <option value="Periférica" @if (old('via_administracion', $solicitud->solicitud_detail->via_administracion) == 'Periférica') selected @endif>Periférica
                        </option>
                    </x-select>
                </div>
                @php
                    $inputValue = old('velocidad_infusion', $solicitud->solicitud_detail->velocidad_infusion);
                    $hasData = $inputValue ? true : false;
                @endphp
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Tiempo de infusión (h):
                    </x-label>
                    <x-input-solicitud type="number"
                        value="{{ $hasData ? '' : old('tiempo_infusion_min', $solicitud->solicitud_detail->tiempo_infusion_min) }}"
                        name="tiempo_infusion_min" class="w-full" placeholder="" disabled />
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Velocidad de infusión ml/hr:
                    </x-label>
                    <x-input-solicitud type="number"
                        value="{{ old('velocidad_infusion', $solicitud->solicitud_detail->velocidad_infusion) }}"
                        step="0.001" name="velocidad_infusion" class="w-full" placeholder="" disabled />
                </div>
            </div>

            <div class="flex gap-4">
                <div>
                    <div class="mb-4 flex items-baseline gap-2 w-full">
                        <x-label class="mb-2 whitespace-nowrap">
                            Sobrellenado (mL):
                        </x-label>
                        <x-input-solicitud type="number"
                            value="{{ old('sobrellenado_ml', $solicitud->solicitud_detail->sobrellenado_ml) }}"
                            step="0.0001" name="sobrellenado_ml" class="w-32" placeholder="" disabled />
                    </div>
                </div>
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap">
                        Volumen total (mL):
                    </x-label>
                    <x-input-solicitud type="number"
                        value="{{ old('volumen_total', $solicitud->solicitud_detail->volumen_total) }}"
                        name="volumen_total" step="0.0001" class="w-full" placeholder="" disabled />
                </div>
                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2">
                        NPT:
                    </x-label>
                    <x-select class="w-full" name="npt" id="npt-select" disabled>
                        <option value="" disabled selected>Seleccionar NPT</option>
                        <option value="INF" @if (old('npt', $solicitud->solicitud_detail->npt) == 'INF') selected @endif>PEDIÁTRICO</option>
                        <option value="ADULT" @if (old('npt', $solicitud->solicitud_detail->npt) == 'ADULT') selected @endif>ADULTO</option>
                    </x-select>
                </div>
            </div>

            <h2 class="mb-4 font-medium">MACRONUTRIENTES:</h2>
            <hr>
            <div class=" gap-4 items-center">
                <div class="w-full">
                    <h3 class="mt-4 font-medium">AMINOÁCIDOS</h3>
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 1)
                            @php
                                $inputValue = old(
                                    'i_' . $input->input_id,
                                    renderInputSection($input->input_id, $inputs_solicitud),
                                );
                                $hasData = $inputValue;
                            @endphp

                            @if ($hasData)
                                <div>
                                    <div class="mb-4 flex items-baseline gap-2 w-full">
                                        <div class="flex w-[40%]">
                                            <x-label class="mb-2 whitespace-nowrap">
                                                {{ $input->description }}:
                                            </x-label>
                                            <div class="flex w-full">
                                                <x-input-solicitud type="number" class="w-full"
                                                    value="{{ old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) }}"
                                                    name="i_{{ $input->input_id }}" id="i_{{ $input->input_id }}"
                                                    step="0.0001" placeholder="" disabled />
                                                <span data-original-unidad="{{ $input->unidad }}"
                                                    class="unidad-span">{{ $input->unidad }}</span>
                                            </div>
                                        </div>

                                        <div class="flex w-[10%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        <div class="flex w-[20%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>

                                        @hasanyrole('Admin|Super Admin')
                                            <div class="flex w-[15%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                        id="l_{{ $input->input_id }}" placeholder=""
                                                        value="{{ $input->lote ?? '' }}" />
                                                </div>
                                            </div>
                                            <div class="flex w-[20%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="date"
                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}"
                                                        placeholder=""
                                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>
                                        @endhasanyrole
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 8)
                            @php
                                $inputValue = old(
                                    'i_' . $input->input_id,
                                    renderInputSection($input->input_id, $inputs_solicitud),
                                );
                                $hasData = $inputValue;
                            @endphp

                            @if ($hasData)
                                <div>
                                    <div class="mb-4 flex items-baseline gap-2 w-full">
                                        <div class="flex w-[40%]">
                                            <x-label
                                                class="mb-2 whitespace-nowrap">{{ $input->description }}:</x-label>
                                            <div class="flex w-full">
                                                <x-input-solicitud type="number" class="w-full"
                                                    value="{{ old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) }}"
                                                    name="i_{{ $input->input_id }}" id="i_{{ $input->input_id }}"
                                                    step="0.0001" placeholder="" disabled />
                                                <span>{{ $input->unidad }}</span>
                                            </div>
                                        </div>
                                        <div class="flex w-[10%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        <div class="flex w-[20%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        @hasanyrole('Admin|Super Admin')
                                            <div class="flex w-[15%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                        id="l_{{ $input->input_id }}" placeholder=""
                                                        value="{{ $input->lote ?? '' }}" />
                                                </div>
                                            </div>
                                            <div class="flex w-[20%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="date"
                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}"
                                                        placeholder=""
                                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>
                                        @endhasanyrole
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>

                <div class="w-full">
                    <h3 class="mt-4 font-medium">CARBOHIDRATOS:</h3>
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 2)
                            @php
                                $inputValue = old(
                                    'i_' . $input->input_id,
                                    renderInputSection($input->input_id, $inputs_solicitud),
                                );
                                $hasData = $inputValue;
                            @endphp

                            @if ($hasData)
                                <div>
                                    <div class="mb-4 flex items-baseline gap-2 w-full">
                                        <div class="flex w-[40%]">
                                            <x-label
                                                class="mb-2 whitespace-nowrap">{{ $input->description }}:</x-label>
                                            <div class="flex w-full">
                                                <x-input-solicitud type="number" class="w-full"
                                                    value="{{ old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) }}"
                                                    name="i_{{ $input->input_id }}" id="i_{{ $input->input_id }}"
                                                    step="0.0001" placeholder="" disabled />
                                                <span data-original-unidad="{{ $input->unidad }}"
                                                    class="unidad-span">{{ $input->unidad }}</span>
                                            </div>
                                        </div>
                                        <div class="flex w-[10%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        <div class="flex w-[20%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        @hasanyrole('Admin|Super Admin')
                                            <div class="flex w-[15%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                        id="l_{{ $input->input_id }}" placeholder=""
                                                        value="{{ $input->lote ?? '' }}" />
                                                </div>
                                            </div>
                                            <div class="flex w-[20%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="date"
                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}"
                                                        placeholder=""
                                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>
                                        @endhasanyrole
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach

                    <h3 class="mt-4 font-medium">LÍPIDOS:</h3>
                    <div class="w-full">
                        @foreach ($inputs as $input)
                            @if ($input->category_id == 3)
                                @php
                                    $inputValue = old(
                                        'i_' . $input->input_id,
                                        renderInputSection($input->input_id, $inputs_solicitud),
                                    );
                                    $hasData = $inputValue;
                                @endphp

                                @if ($hasData)
                                    <div class="w-full">
                                        <div class="mb-4 flex flex-wrap items-baseline gap-2 w-full">
                                            <div class="flex w-[40%]">
                                                <x-label
                                                    class="mb-2 whitespace-nowrap">{{ $input->description }}:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="number" class="w-full"
                                                        value="{{ old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) }}"
                                                        name="i_{{ $input->input_id }}"
                                                        id="i_{{ $input->input_id }}" step="0.0001" placeholder=""
                                                        disabled />
                                                    <span data-original-unidad="{{ $input->unidad }}"
                                                        class="unidad-span">{{ $input->unidad }}</span>
                                                </div>
                                            </div>
                                            <div class="flex w-[10%] justify-center items-stretch">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                                <p
                                                    class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                    {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                                </p>
                                            </div>
                                            <div class="flex w-[20%] justify-center items-stretch">
                                                <x-label
                                                    class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                                <p
                                                    class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                    {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                                </p>
                                            </div>
                                            @hasanyrole('Admin|Super Admin')
                                                <div class="flex w-[15%]">
                                                    <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                    <div class="flex w-full">
                                                        <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                            id="l_{{ $input->input_id }}" placeholder=""
                                                            value="{{ $input->lote ?? '' }}" />
                                                    </div>
                                                </div>
                                                <div class="flex w-[20%]">
                                                    <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                    <div class="flex w-full">
                                                        <x-input-solicitud type="date"
                                                            min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                            id="c_{{ $input->input_id }}"
                                                            name="c_{{ $input->input_id }}" placeholder=""
                                                            value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                    </div>
                                                </div>
                                            @endhasanyrole
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <h2 class="mb-4 font-medium">ELECTROLITOS</h2>
            <hr>
            <div class="gap-4 items-start mt-4">
                <div class=" grid-flow-col gap-4">
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 4)
                            @php
                                $inputValue = old(
                                    'i_' . $input->input_id,
                                    renderInputSection($input->input_id, $inputs_solicitud),
                                );
                                $hasData = $inputValue;
                            @endphp

                            @if ($hasData)
                                <div>
                                    <div class="mb-4 flex items-baseline gap-2 w-full">
                                        <div class="flex w-[40%]">
                                            <x-label
                                                class="mb-2 whitespace-nowrap">{{ $input->description }}:</x-label>
                                            <div class="flex w-full">
                                                <x-input-solicitud type="number" class="w-full"
                                                    value="{{ $inputValue }}" name="i_{{ $input->input_id }}"
                                                    id="i_{{ $input->input_id }}" step="0.0001" placeholder=""
                                                    disabled />
                                                <span data-original-unidad="{{ $input->unidad }}"
                                                    class="unidad-span-electrolitos">{{ $input->unidad }}</span>
                                            </div>
                                        </div>

                                        <div class="flex w-[10%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        <div class="flex w-[20%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>

                                        @hasanyrole('Admin|Super Admin')
                                            <div class="flex w-[15%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                        id="l_{{ $input->input_id }}" placeholder=""
                                                        value="{{ $input->lote ?? '' }}" />
                                                </div>
                                            </div>
                                            <div class="flex w-[20%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="date"
                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}"
                                                        placeholder=""
                                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>
                                        @endhasanyrole
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>

            <h2 class="mb-4 font-medium">ADITIVOS:</h2>
            <hr>
            <div class=" gap-4 items-start mt-4">
                <div class="  gap-4 w-full">
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 5)
                            @php
                                $inputValue = old(
                                    'i_' . $input->input_id,
                                    renderInputSection($input->input_id, $inputs_solicitud),
                                );
                                $hasData = $inputValue;
                            @endphp

                            @if ($hasData)
                                <div>
                                    <div class="mb-4 flex items-baseline gap-2 w-full">
                                        <div class="flex w-[40%]">
                                            <x-label
                                                class="mb-2 whitespace-nowrap">{{ $input->description }}:</x-label>
                                            <div class="flex w-full">
                                                <x-input-solicitud type="number" class="w-full"
                                                    value="{{ $inputValue }}" name="i_{{ $input->input_id }}"
                                                    id="i_{{ $input->input_id }}" step="0.0001" placeholder=""
                                                    disabled />
                                                <span>{{ $input->unidad }}</span>
                                            </div>
                                        </div>

                                        <div class="flex w-[10%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">ML:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        <div class="flex w-[20%] justify-center items-stretch">
                                            <x-label class="mb-2 whitespace-nowrap font-bold">Sobrellenado:</x-label>
                                            <p
                                                class="flex border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5 w-full pl-2 border-[#6b7280]">
                                                {{ number_format(renderInputMLSobrellenadoSection($input->input_id, $inputs_solicitud), 3, '.', '') }}
                                            </p>
                                        </div>
                                        @hasanyrole('Admin|Super Admin')
                                            <div class="flex w-[15%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Lote:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud class="w-full" name="l_{{ $input->input_id }}"
                                                        id="l_{{ $input->input_id }}" placeholder=""
                                                        value="{{ $input->lote ?? '' }}" />
                                                </div>
                                            </div>
                                            <div class="flex w-[20%]">
                                                <x-label class="mb-2 whitespace-nowrap font-bold">Caducidad:</x-label>
                                                <div class="flex w-full">
                                                    <x-input-solicitud type="date"
                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}"
                                                        placeholder=""
                                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>
                                        @endhasanyrole
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>

            @foreach ($inputs as $input)
                @if ($input->category_id == 10)
                    <div>
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2 whitespace-nowrap">
                                {{ $input->description }}:
                            </x-label>
                            <div class="flex w-full">
                                <x-select class="w-full" name="i_{{ $input->input_id }}" disabled
                                    id="i_{{ $input->input_id }}_{{ $input->unidad }}">
                                    <option value="0" @if (old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) == '0') selected @endif>No
                                    </option>
                                    <option value="1" @if (old('i_' . $input->input_id, renderInputSection($input->input_id, $inputs_solicitud)) == '1') selected @endif>Si
                                    </option>
                                </x-select>
                            </div>
                            @hasanyrole('Admin|Super Admin')
                                <x-label class="mb-2 whitespace-nowrap">Lote:</x-label>
                                <div class="flex w-full">
                                    <x-input-solicitud class="w-full" value="{{ $input->lote ?? '' }}"
                                        name="l_{{ $input->input_id }}" id="l_{{ $input->input_id }}" step="0.0001"
                                        placeholder="" disabled />
                                </div>

                                <x-label class="mb-2 whitespace-nowrap">Caducidad:</x-label>
                                <div class="flex w-full">
                                    <x-input-solicitud type="date"
                                        value="{{ $input->caducidad ? \Carbon\Carbon::parse($input->caducidad)->format('Y-m-d') : '' }}"
                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                        id="c_{{ $input->input_id }}" name="c_{{ $input->input_id }}" class=""
                                        placeholder="" disabled />
                                </div>
                            @endhasanyrole
                        </div>
                    </div>
                @endif
            @endforeach

            @hasanyrole('Admin|Super Admin')
                <div class="flex">
                    <div class="flex items-center w-6/12">
                        <x-label class="mb-2 whitespace-nowrap">Bolsa Eva:</x-label>
                        <div class="flex w-full">
                            <x-select class="w-full" name="bolsa_eva" id="bolsa_eva">
                                <option value="" disabled selected>Seleccionar Bolsa Eva</option>
                                @foreach ($inputs as $input)
                                    @if ($input->category_id == 6)
                                        <option value="{{ $input->input_id }}"
                                            @if (old('bolsa_eva', $bolsaSeleccionadaId) == $input->input_id) selected @endif>
                                            {{ $input->description }}
                                        </option>
                                    @endif
                                @endforeach
                            </x-select>
                        </div>
                        @error('bolsa_eva')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center w-3/12">
                        <x-label class="mb-2 whitespace-nowrap">Lote:</x-label>
                        <div class="flex w-full">
                            <x-input-solicitud class="w-full" value="{{ old('lote_bolsa_eva', $loteBolsaEva) }}"
                                name="lote_bolsa_eva" id="lote_bolsa_eva" step="0.0001" placeholder="" />
                        </div>
                        @error('lote_bolsa_eva')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center w-3/12">
                        <x-label class="mb-2 whitespace-nowrap">Caducidad:</x-label>
                        <div class="flex w-full">
                            <x-input-solicitud type="date"
                                value="{{ old('caducidad_bolsa_eva', $caducidadBolsaEva) }}"
                                min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" name="caducidad_bolsa_eva"
                                id="caducidad_bolsa_eva" class="" placeholder="" />
                        </div>
                        @error('caducidad_bolsa_eva')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endhasanyrole

            <div class="mb-4">
                <x-label class="mb-2">OBSERVACIONES</x-label>
                <textarea class="border-2 border-solid w-full resize-x overflow-auto h-20" name="observaciones" disabled>{{ old('observaciones', $solicitud->solicitud_detail->observaciones) }}</textarea>
            </div>

            <div class="flex flex-row gap-4 items-start w-full">
                <div class="w-full">
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2">Fecha y hora de entrega:</x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud type="datetime-local"
                                    value="{{ old('fecha_hora_entrega', $solicitud->solicitud_detail->fecha_hora_entrega) }}"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                    name="fecha_hora_entrega" class="" placeholder="" disabled />
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="w-full">
                            <div class="mb-4 flex items-baseline gap-2 w-full">
                                <x-label class="mb-2 font-bold">
                                    Fecha y hora de preparación:
                                </x-label>

                                <div class="flex flex-col w-full">

                                    @if ($solicitud->fecha_hora_preparacion)
                                        <x-input-solicitud type="datetime-local"
                                            value="{{ \Carbon\Carbon::parse($solicitud->fecha_hora_preparacion)->format('Y-m-d\TH:i') }}"
                                            min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                            name="fecha_hora_preparacion" class="" placeholder="" disabled />
                                    @else
                                        <x-input-solicitud type="datetime-local" value=""
                                            min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                            name="fecha_hora_preparacion" class="" placeholder="" disabled />
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2">Hospital Destino:</x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud
                                    value="{{ old('hospital_destino', $solicitud->solicitud_detail->hospital_destino) }}"
                                    name="hospital_destino" class="w-full" placeholder="" disabled />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2">Nombre del médico:</x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud
                                    value="{{ old('nombre_medico', $solicitud->solicitud_detail->nombre_medico) }}"
                                    name="nombre_medico" class="w-full" placeholder="" disabled />
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2">Cédula profesional:</x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud value="{{ old('cedula', $solicitud->solicitud_detail->cedula) }}"
                                    name="cedula" class="w-full" placeholder="" disabled />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');
                const unidades = document.querySelectorAll('.unidad-span');

                function actualizarUnidades(valorSeleccionado) {
                    unidades.forEach((unidad) => {
                        if (valorSeleccionado === 'ADULT') {
                            unidad.textContent = 'g/día';
                        } else if (valorSeleccionado === 'INF') {
                            unidad.textContent = unidad.getAttribute('data-original-unidad');
                        }
                    });
                }

                actualizarUnidades(selectNPT.value);

                selectNPT.addEventListener('change', function() {
                    actualizarUnidades(selectNPT.value);
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');
                const unidades = document.querySelectorAll('.unidad-span-electrolitos');

                function actualizarUnidades(valorSeleccionado) {
                    unidades.forEach((unidad) => {
                        if (valorSeleccionado === 'ADULT') {
                            unidad.textContent = 'mEq/día';
                        } else if (valorSeleccionado === 'INF') {
                            unidad.textContent = unidad.getAttribute('data-original-unidad');
                        }
                    });
                }

                actualizarUnidades(selectNPT.value);

                selectNPT.addEventListener('change', function() {
                    actualizarUnidades(selectNPT.value);
                });
            });
        </script>
    @endpush
</x-admin-layout>

