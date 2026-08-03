<x-admin-layout>

    <div class="flex flex-col items-center">
        <div class="mt-2 mb-4">
            <h1 class="text-2xl font-medium text-gray-800 text-center">SOLICITUD DE NUTRICIÓN PARENTERAL</h1>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-4 text-red-700 w-full max-w-6xl">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.nutricionales.solicitudes.store') }}" method="POST"
            class="bg-white rounded-lg p-6 shadow-lg">
            @csrf

            <div class="flex gap-4">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Paciente Nombre(s):*
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('nombre_paciente') }}" name="nombre_paciente" class="w-full" placeholder="" />
                        @error('nombre_paciente')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Paciente Apellidos:*
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('apellidos_paciente') }}" name="apellidos_paciente" class="w-full" placeholder="" />
                        @error('apellidos_paciente')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex gap-4 ">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Servicio:*
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('servicio') }}" name="servicio" class="" placeholder="" />
                        @error('servicio')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Cama:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('cama') }}" name="cama" class="" placeholder="" />
                        @error('cama')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Piso:
                    </x-label>
                    <div class="flex flex-col w-full">
                        <x-input-solicitud value="{{ old('piso') }}" name="piso" class="" placeholder="" />
                        @error('piso')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Registro:
                    </x-label>
                    <x-input-solicitud value="{{ old('registro') }}" name="registro" class="w-full" placeholder="" />
                    @error('registro')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Diagnóstico:
                    </x-label>
                    <x-input-solicitud value="{{ old('diagnostico') }}" name="diagnostico" class="w-full" placeholder="" />
                    @error('diagnostico')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Peso:*
                    </x-label>
                    <div class="flex flex-col w-full">
                        <div class="flex">
                            <x-input-solicitud type="number" value="{{ old('peso') }}" step="0.001" name="peso" class="w-full" placeholder="" />
                            <div>Kg</div>
                        </div>
                        @error('peso')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        Sexo:
                    </x-label>
                    <x-select class="w-full" name="sexo">
                        <option value="" disabled selected>Seleccionar Sexo</option>
                        <option value="Femenino" @if (old('sexo') == 'Femenino') selected @endif>Femenino</option>
                        <option value="Masculino" @if (old('sexo') == 'Masculino') selected @endif>Masculino</option>
                    </x-select>
                    @error('sexo')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Fecha de nacimiento:*
                    </x-label>
                    <div class="flex flex-col">
                        <x-input-solicitud type="date" value="{{ old('fecha_nacimiento') }}"
                            max="{{ date('Y-m-d') }}" name="fecha_nacimiento" class="" placeholder=""
                            onchange="calcularEdad(this.value)" />
                        @error('fecha_nacimiento')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Vía de administración:
                    </x-label>
                    <x-select class="w-full" name="via_administracion">
                        <option value="Central" @if (old('via_administracion') == 'Central') selected @endif>Central</option>
                        <option value="Periférica" @if (old('via_administracion') == 'Periférica') selected @endif>Periférica</option>
                    </x-select>
                    @error('via_administracion')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Tiempo de infusión (h):
                    </x-label>
                    <x-input-solicitud type="number" value="{{ old('tiempo_infusion_min') }}"
                        name="tiempo_infusion_min" class="w-full" placeholder="" />
                    @error('tiempo_infusion_min')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Velocidad de infusión ml/hr:
                    </x-label>
                    <x-input-solicitud type="number" value="{{ old('velocidad_infusion') }}" step="0.001"
                        name="velocidad_infusion" class="w-full" placeholder="" />
                    @error('velocidad_infusion')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <div>
                    <div class="mb-4 flex items-baseline gap-2 w-full">
                        <x-label class="mb-2 whitespace-nowrap font-bold">
                            Sobrellenado (mL):
                        </x-label>
                        <x-input-solicitud type="number" value="{{ old('sobrellenado_ml') }}" step="0.0001"
                            name="sobrellenado_ml" class="w-32" placeholder="" />
                        @error('sobrellenado_ml')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Volumen total (mL):
                    </x-label>
                    <x-input-solicitud type="number" value="{{ old('volumen_total') }}" name="volumen_total"
                        step="0.0001" class="w-full" placeholder="" />
                    @error('volumen_total')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-stretch gap-2 w-full">
                    <x-label class="mb-2 font-bold">
                        NPT:*
                    </x-label>
                    <x-select class="w-full" name="npt" id="npt-select">
                        <option value="" disabled selected>Seleccionar NPT</option>
                        <option value="INF" @if (old('npt') == 'INF') selected @endif>PEDIÁTRICO</option>
                        <option value="ADULT" @if (old('npt') == 'ADULT') selected @endif>ADULTO</option>
                    </x-select>
                    @error('npt')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <h2 class="mb-4">MACRONUTRIENTES:</h2>
            <hr>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                <div class="w-full">
                    <h3 class="mt-4">AMINOÁCIDOS</h3>

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 1)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 8)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach
                </div>

                <div class="w-full">
                    <h3>CARBOHIDRATOS:</h3>

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 2)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach

                    <h3>LÍPIDOS:</h3>

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 3)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach
                </div>
            </div>

            <h2 class="mb-4">ELECTROLITOS</h2>
            <hr>

            <div class="mt-4">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 4)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach
                </div>
            </div>

            <h2 class="mb-4">ADITIVOS:</h2>
            <hr>

            <div class="mt-4">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 w-full">
                    @foreach ($inputs as $input)
                        @if ($input->category_id == 5)
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', ['input' => $input, 'oldPrefix' => 'i_'])
                        @endif
                    @endforeach

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 10)
                            <div>
                                <div class="mb-4 flex items-baseline gap-2 w-full">
                                    <x-label class="mb-2 whitespace-nowrap font-bold">
                                        {{ $input->description }}:
                                    </x-label>

                                    <div class="flex w-full">
                                        <x-select class="w-full"
                                            name="i_{{ $input->input_id }}_{{ $input->unidad }}"
                                            id="i_{{ $input->input_id }}_{{ $input->unidad }}">
                                            <option value="0" @if (old('i_' . $input->input_id . '_' . $input->unidad) == '0') selected @endif>No</option>
                                            <option value="1" @if (old('i_' . $input->input_id . '_' . $input->unidad) == '1') selected @endif>Sí</option>
                                        </x-select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="mb-4">
                <x-label class="mb-2 font-bold">
                    OBSERVACIONES
                </x-label>
                <textarea class="border-2 border-solid w-full resize-x overflow-auto h-20" name="observaciones">{{ old('observaciones') }}</textarea>
                @error('observaciones')
                    <div class="text-red-500 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start w-full">
                <div class="w-full">
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2 font-bold">
                                Fecha y hora de entrega:*
                            </x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud type="datetime-local" value="{{ old('fecha_hora_entrega') }}"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" name="fecha_hora_entrega"
                                    class="" placeholder="" />
                                @error('fecha_hora_entrega')
                                    <div class="text-red-500 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <div class="w-full">
                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2 font-bold">
                                Nombre del médico:*
                            </x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud value="{{ old('nombre_medico') }}" name="nombre_medico"
                                    class="w-full" placeholder="" />
                                @error('nombre_medico')
                                    <div class="text-red-500 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="mb-4 flex items-baseline gap-2 w-full">
                            <x-label class="mb-2 font-bold">
                                Cédula profesional:*
                            </x-label>
                            <div class="flex flex-col w-full">
                                <x-input-solicitud value="{{ old('cedula') }}" name="cedula" class="w-full"
                                    placeholder="" />
                                @error('cedula')
                                    <div class="text-red-500 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-5">
                <x-button>
                    GUARDAR SOLICITUD
                </x-button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            function calcularEdad(fechaNacimiento) {
                var fechaNacimiento = new Date(fechaNacimiento);
                var fechaActual = new Date();

                var edadAnios = fechaActual.getFullYear() - fechaNacimiento.getFullYear();
                var edadMeses = fechaActual.getMonth() - fechaNacimiento.getMonth();
                var edadDias = fechaActual.getDate() - fechaNacimiento.getDate();

                if (edadDias < 0) {
                    edadMeses--;
                    var ultimoDiaMesAnterior = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 0).getDate();
                    edadDias = ultimoDiaMesAnterior + edadDias;
                }

                if (edadMeses < 0) {
                    edadAnios--;
                    edadMeses = 12 + edadMeses;
                }

                var edad = '';

                if (edadAnios > 0) {
                    edad += edadAnios + ' año(s) ';
                }

                if (edadMeses > 0) {
                    edad += edadMeses + ' mes(es) ';
                }

                if (edadDias > 0) {
                    edad += edadDias + ' día(s)';
                }

                console.log('Edad: ' + edad);
            }

            const numericInputs = document.querySelectorAll('.numeric-input');
            numericInputs.forEach(input => {
                input.addEventListener('input', function(event) {
                    let inputValue = this.value;
                    this.value = inputValue.replace(/\D/g, '');
                });
            });

            const inputTiempo = document.querySelector('input[name="tiempo_infusion_min"]');
            const inputVelocidad = document.querySelector('input[name="velocidad_infusion"]');

            function toggleInputState() {
                if (inputTiempo.value) {
                    inputVelocidad.disabled = true;
                } else if (inputVelocidad.value) {
                    inputTiempo.disabled = true;
                } else {
                    inputVelocidad.disabled = false;
                    inputTiempo.disabled = false;
                }
            }

            inputTiempo.addEventListener('input', toggleInputState);
            inputVelocidad.addEventListener('input', toggleInputState);

            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');
                const unidades = document.querySelectorAll('.unidad-span');

                function actualizarUnidades() {
                    const selectedValue = selectNPT.value;
                    unidades.forEach((unidad) => {
                        if (selectedValue === 'ADULT') {
                            unidad.textContent = 'g/día';
                        } else if (selectedValue === 'INF') {
                            unidad.textContent = unidad.getAttribute('data-original-unidad');
                        }
                    });
                }

                actualizarUnidades();
                selectNPT.addEventListener('change', actualizarUnidades);
            });

            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');
                const unidades = document.querySelectorAll('.unidad-span-electrolitos');

                function actualizarUnidadesElectrolitos() {
                    const selectedValue = selectNPT.value;
                    unidades.forEach((unidad) => {
                        if (selectedValue === 'ADULT') {
                            unidad.textContent = 'mEq/día';
                        } else if (selectedValue === 'INF') {
                            unidad.textContent = unidad.getAttribute('data-original-unidad');
                        }
                    });
                }

                actualizarUnidadesElectrolitos();
                selectNPT.addEventListener('change', actualizarUnidadesElectrolitos);
            });
        </script>
    @endpush
</x-admin-layout>
