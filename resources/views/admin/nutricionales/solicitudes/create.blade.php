<x-admin-layout>

    <div class="flex flex-col items-center">
        <div class="mt-2 mb-4 flex w-full items-start justify-between gap-4">
            <span class="h-10 w-10 shrink-0" aria-hidden="true"></span>
            <h1 class="flex-1 text-center text-2xl font-medium text-gray-800">SOLICITUD DE NUTRICIÓN PARENTERAL</h1>
            <div class="flex shrink-0 items-center gap-2">
                @role('Super Admin')
                    <button type="button" id="toggle-macro-layout"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-600 text-emerald-700 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        title="Editar acomodo del formulario" aria-label="Editar acomodo del formulario">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                    </button>
                @endrole
                <a href="{{ route('admin.nutricionales.solicitudes.index') }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border-2 border-red-600 text-2xl font-semibold leading-none text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                    title="Cerrar formato de solicitud"
                    aria-label="Cerrar formato de solicitud">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
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

        <form id="nutrition-request-form" action="{{ route('admin.nutricionales.solicitudes.store') }}" method="POST"
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
                            <x-input-solicitud type="number" value="{{ old('peso') }}" step="0.001" min="0.001" max="1000" name="peso" class="w-full" placeholder="" />
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
                        min="0.001" max="1000" name="tiempo_infusion_min" class="w-full" placeholder="" />
                    @error('tiempo_infusion_min')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-baseline gap-2 w-full">
                    <x-label class="mb-2 whitespace-nowrap font-bold">
                        Velocidad de infusión ml/hr:
                    </x-label>
                    <x-input-solicitud type="number" value="{{ old('velocidad_infusion') }}" step="0.001"
                        min="0.001" max="100000" name="velocidad_infusion" class="w-full" placeholder="" />
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
                            min="0" max="100000" name="sobrellenado_ml" class="w-32" placeholder="" />
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
                        step="0.0001" min="0" max="100000" class="w-full" placeholder="" />
                    @error('volumen_total')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                    <div id="volume-components-error" class="mt-1 hidden text-sm font-semibold text-red-600"
                        role="alert"></div>
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

            <div id="macro-layout-grid" data-layout-grid="macronutrients" class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                @foreach ([1 => 'left', 2 => 'right'] as $columnNumber => $columnName)
                    <div data-layout-column="{{ $columnName }}" class="flex min-h-24 flex-col gap-4 rounded-md">
                        @foreach ($inputs->whereIn('category_id', [1, 2, 3, 8])->where('layout_column', $columnNumber)->sortBy('orden_enum') as $input)
                            @php
                                $macroCategoryLabel = match ((int) $input->category_id) {
                                    1, 8 => 'Aminoácidos',
                                    2 => 'Carbohidratos',
                                    3 => 'Lípidos',
                                    default => 'Macronutriente',
                                };
                            @endphp
                            @include('admin.nutricionales.solicitudes.partials.input-row-create', [
                                'input' => $input,
                                'oldPrefix' => 'i_',
                                'layoutSortable' => true,
                                'layoutCategoryLabel' => $macroCategoryLabel,
                            ])
                        @endforeach
                    </div>
                @endforeach
            </div>

            <h2 class="mb-4">ELECTROLITOS</h2>
            <hr>

            <div class="mt-4">
                <div id="electrolyte-layout-grid" data-layout-grid="electrolytes" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @foreach ([1 => 'left', 2 => 'right'] as $columnNumber => $columnName)
                        <div data-layout-column="{{ $columnName }}" class="flex min-h-24 flex-col gap-4 rounded-md">
                            @foreach ($inputs->where('category_id', 4)->where('layout_column', $columnNumber)->sortBy('orden_enum') as $input)
                                @include('admin.nutricionales.solicitudes.partials.input-row-create', [
                                    'input' => $input,
                                    'oldPrefix' => 'i_',
                                    'layoutSortable' => true,
                                    'layoutCategoryLabel' => 'Electrolito',
                                ])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <h2 class="mb-4">ADITIVOS:</h2>
            <hr>

            <div class="mt-4">
                <div id="additive-layout-grid" data-layout-grid="additives" class="grid grid-cols-1 xl:grid-cols-2 gap-4 w-full">
                    @foreach ([1 => 'left', 2 => 'right'] as $columnNumber => $columnName)
                        <div data-layout-column="{{ $columnName }}" class="flex min-h-24 flex-col gap-4 rounded-md">
                            @foreach ($inputs->where('category_id', 5)->where('layout_column', $columnNumber)->sortBy('orden_enum') as $input)
                                @include('admin.nutricionales.solicitudes.partials.input-row-create', [
                                    'input' => $input,
                                    'oldPrefix' => 'i_',
                                    'layoutSortable' => true,
                                    'layoutCategoryLabel' => 'Aditivo',
                                ])
                            @endforeach
                        </div>
                    @endforeach

                    @foreach ($inputs as $input)
                        @if ($input->category_id == 10)
                            <div data-npt-field data-tipo-input="{{ $input->tipo_input ?: 'ambos' }}">
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
                                    min="{{ \Carbon\Carbon::now()->addMinutes(210)->format('Y-m-d\TH:i') }}" name="fecha_hora_entrega"
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
                <x-button id="nutrition-request-submit">
                    GUARDAR SOLICITUD
                </x-button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('nutrition-request-form');
                const submitButton = document.getElementById('nutrition-request-submit');
                const totalVolumeInput = form?.querySelector('[name="volumen_total"]');
                const patientWeightInput = form?.querySelector('[name="peso"]');
                const nptSelect = form?.querySelector('[name="npt"]');
                const volumeError = document.getElementById('volume-components-error');

                if (!form || !submitButton) return;

                const calculatedComponentsVolume = () => {
                    const weight = Number.parseFloat(patientWeightInput?.value || '0');
                    const isAdult = nptSelect?.value === 'ADULT';

                    return Array.from(form.querySelectorAll('[data-nutrition-component]')).reduce((sum, card) => {
                        const inputId = Number.parseInt(card.dataset.inputId || '0', 10);
                        if (inputId === 40) return sum;

                        const input = card.querySelector('input[name^="i_"]');
                        if (!input || input.disabled) return sum;

                        const quantity = Number.parseFloat(input?.value || '0');
                        const multiplier = Number.parseFloat(card.dataset.mult || '0');
                        const divisor = Number.parseFloat(card.dataset.div || '0');
                        const category = Number.parseInt(card.dataset.categoryId || '0', 10);
                        if (!Number.isFinite(quantity) || quantity <= 0 || !Number.isFinite(divisor) || divisor === 0) {
                            return sum;
                        }

                        const usesPatientWeight = !isAdult && [1, 2, 3, 4, 8].includes(category);
                        const weightFactor = usesPatientWeight ? weight : 1;
                        const componentVolume = quantity * weightFactor * multiplier / divisor;

                        return Number.isFinite(componentVolume) && componentVolume > 0
                            ? sum + componentVolume
                            : sum;
                    }, 0);
                };

                const validateTotalVolume = () => {
                    if (!totalVolumeInput || totalVolumeInput.value === '') {
                        totalVolumeInput?.setCustomValidity('');
                        volumeError?.classList.add('hidden');
                        return true;
                    }

                    const totalVolume = Number.parseFloat(totalVolumeInput.value);
                    const componentsVolume = calculatedComponentsVolume();
                    const invalid = Number.isFinite(totalVolume) && totalVolume + 0.0001 < componentsVolume;
                    const message = invalid
                        ? `El volumen total no puede ser menor que la suma calculada de los componentes (${componentsVolume.toFixed(2)} mL).`
                        : '';

                    totalVolumeInput.setCustomValidity(message);
                    if (volumeError) {
                        volumeError.textContent = message;
                        volumeError.classList.toggle('hidden', !invalid);
                    }

                    return !invalid;
                };

                totalVolumeInput?.addEventListener('input', validateTotalVolume);
                patientWeightInput?.addEventListener('input', validateTotalVolume);
                nptSelect?.addEventListener('change', validateTotalVolume);
                form.querySelectorAll('[data-nutrition-component] input[name^="i_"]').forEach((input) => {
                    input.addEventListener('input', validateTotalVolume);
                });

                form.addEventListener('submit', function(event) {
                    if (!validateTotalVolume()) {
                        event.preventDefault();
                        totalVolumeInput.reportValidity();
                        totalVolumeInput.focus();
                        return;
                    }

                    if (!form.checkValidity() || submitButton.disabled) return;

                    submitButton.disabled = true;
                    submitButton.classList.add('cursor-not-allowed', 'opacity-60');
                    submitButton.textContent = 'GUARDANDO...';
                });
            });

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

            document.addEventListener('DOMContentLoaded', function() {
                const selectNPT = document.getElementById('npt-select');
                const fields = Array.from(document.querySelectorAll('[data-npt-field]'));

                const normalize = (value) => String(value || '')
                    .trim()
                    .toLocaleLowerCase('es-MX')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');

                function actualizarCamposPorNpt() {
                    const npt = selectNPT?.value || '';

                    fields.forEach((field) => {
                        const type = normalize(field.dataset.tipoInput || 'ambos');
                        const allowed = npt === ''
                            || type === 'ambos'
                            || (npt === 'ADULT' && type === 'adulto')
                            || (npt === 'INF' && ['nino', 'pediatrico'].includes(type));

                        field.classList.toggle('hidden', !allowed);
                        field.querySelectorAll('input, select, textarea').forEach((control) => {
                            control.disabled = !allowed;
                        });
                    });
                }

                actualizarCamposPorNpt();
                selectNPT?.addEventListener('change', actualizarCamposPorNpt);
            });

            document.addEventListener('DOMContentLoaded', function() {
                const toggle = document.getElementById('toggle-macro-layout');
                const grids = Array.from(document.querySelectorAll('[data-layout-grid]'));
                if (!toggle || grids.length === 0) return;

                let editing = false;
                let draggedCard = null;
                let activeGrid = null;
                let dropColumn = null;
                let dropTarget = null;
                let dropPosition = null;
                const cards = (grid) => Array.from(grid.querySelectorAll('[data-layout-card]'));
                const clearDropIndicators = () => {
                    grids.forEach((layoutGrid) => cards(layoutGrid).forEach((card) => {
                        card.style.borderTop = '';
                        card.style.borderBottom = '';
                        delete card.dataset.dropPosition;
                    }));
                    grids.forEach((layoutGrid) => layoutGrid.querySelectorAll('[data-layout-column]').forEach((column) => {
                        column.classList.remove('bg-emerald-50', 'ring-2', 'ring-emerald-400');
                    }));
                };

                const setEditing = (enabled) => {
                    editing = enabled;
                    toggle.innerHTML = enabled
                        ? '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6" /></svg>'
                        : '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>';
                    toggle.title = enabled ? 'Guardar acomodo' : 'Editar acomodo del formulario';
                    toggle.setAttribute('aria-label', toggle.title);
                    grids.forEach((grid) => {
                        grid.classList.toggle('rounded-lg', enabled);
                        grid.classList.toggle('ring-2', enabled);
                        grid.classList.toggle('ring-emerald-400', enabled);
                        grid.classList.toggle('p-2', enabled);

                        cards(grid).forEach((card) => {
                            const handle = card.querySelector('[data-drag-handle]');
                            card.classList.toggle('shadow-md', enabled);
                            if (!handle) return;
                            handle.classList.toggle('hidden', !enabled);
                            handle.classList.toggle('flex', enabled);
                            handle.style.cursor = enabled ? 'grab' : '';
                            handle.style.touchAction = enabled ? 'none' : '';
                        });
                    });
                };

                grids.forEach((grid) => {
                    grid.querySelectorAll('[data-drag-handle]').forEach((handle) => {
                        handle.addEventListener('pointerdown', (event) => {
                            if (!editing || (event.button !== undefined && event.button !== 0)) return;
                            event.preventDefault();
                            draggedCard = handle.closest('[data-layout-card]');
                            activeGrid = grid;
                            dropColumn = null;
                            dropTarget = null;
                            dropPosition = null;
                            draggedCard.classList.add('opacity-50');
                            handle.style.cursor = 'grabbing';
                            document.body.style.userSelect = 'none';
                            document.body.style.cursor = 'grabbing';
                        });
                    });
                });

                document.addEventListener('pointermove', (event) => {
                    if (!editing || !draggedCard || !activeGrid) return;
                    event.preventDefault();

                    if (event.clientY < 90) window.scrollBy(0, -14);
                    if (event.clientY > window.innerHeight - 90) window.scrollBy(0, 14);

                    const pointedElement = document.elementFromPoint(event.clientX, event.clientY);
                    const target = pointedElement?.closest?.('[data-layout-card]');
                    const pointedColumn = pointedElement?.closest?.('[data-layout-column]');
                    if (!pointedColumn || pointedColumn.closest('[data-layout-grid]') !== activeGrid) {
                        clearDropIndicators();
                        dropColumn = null;
                        dropTarget = null;
                        dropPosition = null;
                        return;
                    }

                    clearDropIndicators();
                    dropColumn = pointedColumn;
                    if (target === draggedCard) {
                        dropTarget = null;
                        dropPosition = null;
                        return;
                    }
                    if (!target) {
                        dropTarget = null;
                        dropPosition = 'append';
                        pointedColumn.classList.add('bg-emerald-50', 'ring-2', 'ring-emerald-400');
                        return;
                    }

                    const rect = target.getBoundingClientRect();
                    dropPosition = event.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
                    dropTarget = target;
                    if (dropPosition === 'before') {
                        target.style.borderTop = '5px solid #10B981';
                    } else {
                        target.style.borderBottom = '5px solid #10B981';
                    }
                }, { passive: false });

                const finishPointerDrag = () => {
                    if (!draggedCard) return;

                    if (dropTarget && dropColumn) {
                        dropColumn.insertBefore(
                            draggedCard,
                            dropPosition === 'before' ? dropTarget : dropTarget.nextSibling,
                        );
                    } else if (dropColumn && dropPosition === 'append') {
                        dropColumn.appendChild(draggedCard);
                    }

                    draggedCard.classList.remove('opacity-50');
                    draggedCard.querySelector('[data-drag-handle]').style.cursor = 'grab';
                    document.body.style.userSelect = '';
                    document.body.style.cursor = '';
                    draggedCard = null;
                    activeGrid = null;
                    dropColumn = null;
                    dropTarget = null;
                    dropPosition = null;
                    clearDropIndicators();
                };

                document.addEventListener('pointerup', finishPointerDrag);
                document.addEventListener('pointercancel', finishPointerDrag);

                toggle.addEventListener('click', async () => {
                    if (!editing) {
                        setEditing(true);
                        return;
                    }

                    toggle.disabled = true;
                    try {
                        const response = await fetch(@json(route('admin.nutricionales.inputs.reorder-form-layout')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({
                                sections: Object.fromEntries(grids.map((grid) => [
                                    grid.dataset.layoutGrid,
                                    Object.fromEntries(Array.from(grid.querySelectorAll('[data-layout-column]')).map((column) => [
                                        column.dataset.layoutColumn,
                                        cards(column).map((card) => Number(card.dataset.inputId)),
                                    ])),
                                ])),
                            }),
                        });
                        if (!response.ok) throw new Error('No fue posible guardar el acomodo.');
                        setEditing(false);
                        if (window.Swal) {
                            Swal.fire({ icon: 'success', title: 'Acomodo guardado', timer: 1400, showConfirmButton: false });
                        }
                    } catch (error) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó el acomodo', text: error.message });
                    } finally {
                        toggle.disabled = false;
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
