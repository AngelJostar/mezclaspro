<x-admin-layout>
    @php
        $states = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
            'Chihuahua', 'Ciudad de Mexico', 'Coahuila', 'Colima', 'Durango', 'Estado de Mexico',
            'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacan', 'Morelos', 'Nayarit',
            'Nuevo Leon', 'Oaxaca', 'Puebla', 'Queretaro', 'Quintana Roo', 'San Luis Potosi',
            'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatan', 'Zacatecas',
        ];
        $days = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $selectedDays = old('operation_days', old('operation_days_submitted') ? [] : $days);
        $inputClass = 'mt-0.5 h-8 w-full rounded border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:ring-blue-500';
        $labelClass = 'block text-xs font-medium text-gray-700';
        $institutionFiscalData = [
            'fiscal_name' => $institucion?->razon_social ?: $institucion?->nombre,
            'rfc' => $institucion?->rfc,
            'billing_phone' => $institucion?->telefono,
        ];
    @endphp

    <section class="mx-auto max-w-7xl overflow-hidden rounded-lg bg-white shadow-lg">
        <div class="border-b border-gray-200 px-5 py-4">
            <h1 class="text-2xl font-semibold text-gray-900">Alta de nuevo hospital</h1>
            <a href="{{ $cancelRoute }}" class="mt-1 inline-flex text-sm font-medium text-blue-600 hover:text-blue-800">
                &larr; Volver a hospitales
            </a>
        </div>

        <form action="{{ $formAction }}" method="POST">
            @csrf

            <div class="px-5 pt-4">
                <x-validation-errors class="mb-4" />

                @if ($institucion)
                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                            <span class="text-sm font-bold" aria-hidden="true">I</span>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Instituci&oacute;n: {{ $institucion->nombre }}</p>
                            <p class="mt-1 flex items-center gap-2 text-xs text-gray-600">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Activa
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 px-5 py-4 xl:grid-cols-[minmax(0,1fr)_240px]">
                <div class="rounded-lg border border-gray-200 p-4">
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-900">Datos generales</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-3">
                            <label class="{{ $labelClass }}">
                                Nombre oficial del hospital <span class="text-red-600">*</span>
                                <input id="hospital-name" name="name_hp" value="{{ old('name_hp') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Nombre corto
                                <input name="short_name" value="{{ old('short_name') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Clave interna
                                <input name="internal_key" value="{{ old('internal_key') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Tipo de unidad <span class="text-red-600">*</span>
                                <select name="unit_type" required class="{{ $inputClass }}">
                                    <option value="">Selecciona una opci&oacute;n</option>
                                    @foreach (['Hospital general', 'Hospital regional', 'Hospital municipal', 'Hospital comunitario', 'Hospital materno infantil', 'Hospital de especialidades', 'Clinica'] as $type)
                                        <option value="{{ $type }}" @selected(old('unit_type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }}">
                                Nivel de atenci&oacute;n
                                <select name="care_level" class="{{ $inputClass }}">
                                    <option value="">Selecciona una opci&oacute;n</option>
                                    @foreach (['Primer nivel', 'Segundo nivel', 'Tercer nivel'] as $level)
                                        <option value="{{ $level }}" @selected(old('care_level') === $level)>{{ $level }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div>
                                <span class="{{ $labelClass }}">Estatus</span>
                                <input type="hidden" name="is_active" value="0">
                                <label class="mt-2 inline-flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                                        @checked((string) old('is_active', '1') === '1')>
                                    <span class="relative h-5 w-9 rounded-full bg-gray-300 transition peer-checked:bg-blue-800 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
                                    <span class="text-sm text-gray-700">Activo</span>
                                </label>
                            </div>

                            <label class="{{ $labelClass }}">
                                CLUES
                                <input name="clues" value="{{ old('clues') }}" maxlength="30" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2 xl:col-span-3">
                                Texto libre
                                <textarea name="free_text" rows="2" maxlength="10000"
                                    placeholder="Agrega cualquier informaci&oacute;n adicional sobre el hospital."
                                    class="mt-0.5 min-h-14 w-full resize-y rounded border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500">{{ old('free_text') }}</textarea>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Datos fiscales del hospital</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-3">
                            <label class="{{ $labelClass }}">
                                Raz&oacute;n social
                                <input name="fiscal_name" value="{{ old('fiscal_name') }}" maxlength="255"
                                    data-fiscal-field="fiscal_name" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                RFC
                                <input name="rfc" value="{{ old('rfc') }}" maxlength="20"
                                    data-fiscal-field="rfc" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                R&eacute;gimen fiscal
                                <input name="fiscal_regime" value="{{ old('fiscal_regime') }}" maxlength="255"
                                    class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Uso CFDI
                                <input name="cfdi_use" value="{{ old('cfdi_use') }}" maxlength="255"
                                    class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Correo de facturaci&oacute;n
                                <input type="email" name="billing_email" value="{{ old('billing_email') }}" maxlength="150"
                                    class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Tel&eacute;fono de facturaci&oacute;n
                                <input name="billing_phone" value="{{ old('billing_phone') }}" maxlength="30"
                                    data-fiscal-field="billing_phone" class="{{ $inputClass }}">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Ubicaci&oacute;n</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-4">
                            <label class="{{ $labelClass }}">
                                Pa&iacute;s <span class="text-red-600">*</span>
                                <input name="country" value="{{ old('country', 'México') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Estado <span class="text-red-600">*</span>
                                <select name="state" required class="{{ $inputClass }}">
                                    <option value="">Selecciona un estado</option>
                                    @foreach ($states as $stateOption)
                                        <option value="{{ $stateOption }}" @selected(old('state') === $stateOption)>{{ $stateOption }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }}">
                                Municipio o alcald&iacute;a <span class="text-red-600">*</span>
                                <input name="municipality" value="{{ old('municipality') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                C&oacute;digo postal <span class="text-red-600">*</span>
                                <input name="postal_code" value="{{ old('postal_code') }}" required maxlength="10" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Colonia
                                <input name="neighborhood" value="{{ old('neighborhood') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Calle y n&uacute;mero <span class="text-red-600">*</span>
                                <input name="street_number" value="{{ old('street_number') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Link de ubicaci&oacute;n de Google Maps
                                <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}"
                                    placeholder="https://maps.google.com/..." class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Coordenadas
                                <input name="coordinates" value="{{ old('coordinates') }}" maxlength="80"
                                    placeholder="19.432608, -99.133209" class="{{ $inputClass }}">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Contacto y operaci&oacute;n</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-6">
                            <label class="{{ $labelClass }} md:col-span-2">
                                Responsable del hospital
                                <input name="contact_name" value="{{ old('contact_name') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Cargo
                                <input name="contact_position" value="{{ old('contact_position') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Tel&eacute;fono
                                <input name="phone" value="{{ old('phone') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Correo electr&oacute;nico
                                <input type="email" name="email" value="{{ old('email') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Horario de recepci&oacute;n
                                <input name="reception_hours" value="{{ old('reception_hours', '24/7') }}" placeholder="24/7" class="{{ $inputClass }}">
                            </label>

                            <div class="md:col-span-2">
                                <span class="{{ $labelClass }}">D&iacute;as de operaci&oacute;n</span>
                                <input type="hidden" name="operation_days_submitted" value="1">
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-2">
                                    @foreach ($days as $day)
                                        <label class="inline-flex items-center gap-1 text-xs text-gray-700">
                                            <input type="checkbox" name="operation_days[]" value="{{ $day }}"
                                                class="rounded border-gray-300 text-blue-800 focus:ring-blue-500"
                                                @checked(in_array($day, $selectedDays, true))>
                                            {{ substr($day, 0, 3) }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span class="{{ $labelClass }}">L&iacute;neas de servicio</span>
                            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-emerald-300 bg-emerald-50 px-3 text-sm font-medium text-emerald-800">
                                    <input type="hidden" name="service_oncology" value="0">
                                    <input type="checkbox" name="service_oncology" value="1"
                                        class="rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500"
                                        @checked((bool) old('service_oncology', true))>
                                    Oncol&oacute;gicos
                                </label>
                                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-red-300 bg-red-50 px-3 text-sm font-medium text-red-800">
                                    <input type="hidden" name="service_antibiotics" value="0">
                                    <input type="checkbox" name="service_antibiotics" value="1"
                                        class="rounded border-red-400 text-red-600 focus:ring-red-500"
                                        @checked((bool) old('service_antibiotics', true))>
                                    Antibi&oacute;ticos
                                </label>
                                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-blue-300 bg-blue-50 px-3 text-sm font-medium text-blue-800">
                                    <input type="hidden" name="service_nutrition" value="0">
                                    <input type="checkbox" name="service_nutrition" value="1"
                                        class="rounded border-blue-400 text-blue-600 focus:ring-blue-500"
                                        @checked((bool) old('service_nutrition', true))>
                                    Nutricionales
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Configuraci&oacute;n operativa</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                            <label class="{{ $labelClass }}">
                                Central de mezclas
                                <select name="laboratory_id" class="{{ $inputClass }}">
                                    <option value="">Sin asignar</option>
                                    @foreach ($laboratories as $lab)
                                        <option value="{{ $lab->id }}" @selected((string) old('laboratory_id') === (string) $lab->id)>{{ $lab->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </fieldset>

                    <section class="mt-5 border-t border-gray-200 pt-4" aria-labelledby="hospital-access-title">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 id="hospital-access-title" class="text-sm font-semibold text-gray-900">Acceso al panel de solicitudes</h2>
                            <button type="button" id="suggest-hospital-access"
                                class="inline-flex h-8 items-center justify-center gap-2 self-start rounded border border-blue-800 px-3 text-xs font-semibold text-blue-900 hover:bg-blue-50"
                                title="Proponer otro usuario y contrasena">
                                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                Proponer acceso
                            </button>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2">
                            <label class="{{ $labelClass }}">
                                Usuario <span class="text-red-600">*</span>
                                <input id="access-username" name="access_username" value="{{ old('access_username') }}"
                                    required maxlength="255" autocomplete="off" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Contrase&ntilde;a <span class="text-red-600">*</span>
                                <span class="relative mt-0.5 block">
                                    <input id="access-password" type="text" name="access_password" required
                                        minlength="8" maxlength="20" autocomplete="new-password"
                                        class="h-8 w-full rounded border-gray-300 py-1 pl-2 pr-9 text-xs focus:border-blue-500 focus:ring-blue-500">
                                    <button type="button" id="toggle-access-password"
                                        class="absolute inset-y-0 right-0 inline-flex w-8 items-center justify-center text-gray-500 hover:text-blue-800"
                                        title="Ocultar contrasena" aria-label="Ocultar contrasena">
                                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                                    </button>
                                </span>
                            </label>
                        </div>
                    </section>
                </div>

                <aside class="rounded-lg border border-gray-200 p-4 xl:self-start">
                    <h2 class="text-base font-semibold text-gray-900">Vinculaci&oacute;n</h2>

                    <div class="mt-4 flex items-center gap-3 border-b border-gray-200 pb-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                            <span class="text-sm font-bold" aria-hidden="true">I</span>
                        </span>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $institucion?->nombre ?? 'Sin institucion asignada' }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-800">
                            <span class="text-sm font-bold" aria-hidden="true">H</span>
                        </span>
                        <span class="text-sm font-medium text-gray-900">Hospital nuevo</span>
                    </div>

                    @if ($institucion)
                        <label class="mt-5 flex cursor-pointer items-start gap-2 text-xs text-gray-700">
                            <input id="use-institution-fiscal-data" type="checkbox" name="use_institution_fiscal_data" value="1"
                                class="mt-0.5 rounded border-gray-300 text-blue-800 focus:ring-blue-500"
                                @checked(old('use_institution_fiscal_data'))>
                            Usar los datos fiscales de la instituci&oacute;n
                        </label>
                    @endif
                </aside>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ $cancelRoute }}"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-blue-800 px-5 text-sm font-semibold text-blue-900 hover:bg-blue-50">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="h-10 rounded-lg bg-azul-prodifem px-5 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                        Crear hospital
                    </button>
                </div>
            </div>
        </form>
    </section>

    @push('js')
        <script>
            (() => {
                const checkbox = document.getElementById('use-institution-fiscal-data');

                if (!checkbox) return;

                const fiscalData = @json($institutionFiscalData);
                const fields = {
                    fiscal_name: document.querySelector('[data-fiscal-field="fiscal_name"]'),
                    rfc: document.querySelector('[data-fiscal-field="rfc"]'),
                    billing_phone: document.querySelector('[data-fiscal-field="billing_phone"]'),
                };

                function applyInstitutionFiscalData(onlyEmpty = false) {
                    Object.entries(fields).forEach(([field, input]) => {
                        if (!input) return;
                        if (onlyEmpty && input.value.trim() !== '') return;

                        input.value = fiscalData[field] ?? '';
                    });
                }

                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        applyInstitutionFiscalData();
                    }
                });

                if (checkbox.checked) {
                    applyInstitutionFiscalData(true);
                }
            })();

            (() => {
                const nameInput = document.getElementById('hospital-name');
                const usernameInput = document.getElementById('access-username');
                const passwordInput = document.getElementById('access-password');
                const suggestButton = document.getElementById('suggest-hospital-access');
                const toggleButton = document.getElementById('toggle-access-password');

                if (!nameInput || !usernameInput || !passwordInput || !suggestButton || !toggleButton) return;

                let suggestedUsername = usernameInput.value.trim();
                let suggestedPassword = '';
                let usernameWasEdited = suggestedUsername !== '';
                let passwordWasEdited = false;
                let passwordNumber = randomNumber();

                function randomNumber() {
                    if (window.crypto?.getRandomValues) {
                        const values = new Uint16Array(1);
                        window.crypto.getRandomValues(values);
                        return String(1000 + (values[0] % 9000));
                    }

                    return String(Math.floor(1000 + Math.random() * 9000));
                }

                function wordsFromHospital() {
                    return nameInput.value
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, ' ')
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean);
                }

                function credentials() {
                    const words = wordsFromHospital();

                    if (words.length === 0) return null;

                    const username = words.join('').slice(0, 24);
                    const passwordRoot = (words[0] + 'hosp').slice(0, 6);
                    const password = passwordRoot.charAt(0).toUpperCase()
                        + passwordRoot.slice(1)
                        + passwordNumber
                        + '!';

                    return { username, password };
                }

                function applySuggestion(force = false) {
                    const proposal = credentials();

                    if (!proposal) return;

                    if (force || !usernameWasEdited) {
                        suggestedUsername = proposal.username;
                        usernameInput.value = suggestedUsername;
                    }

                    if (force || !passwordWasEdited) {
                        suggestedPassword = proposal.password;
                        passwordInput.value = suggestedPassword;
                    }
                }

                nameInput.addEventListener('input', () => applySuggestion());
                usernameInput.addEventListener('input', () => {
                    usernameWasEdited = usernameInput.value !== suggestedUsername;
                });
                passwordInput.addEventListener('input', () => {
                    passwordWasEdited = passwordInput.value !== suggestedPassword;
                });
                suggestButton.addEventListener('click', () => {
                    usernameWasEdited = false;
                    passwordWasEdited = false;
                    passwordNumber = randomNumber();
                    applySuggestion(true);
                    usernameInput.focus();
                });
                toggleButton.addEventListener('click', () => {
                    const showPassword = passwordInput.type === 'password';
                    passwordInput.type = showPassword ? 'text' : 'password';
                    toggleButton.title = showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena';
                    toggleButton.setAttribute('aria-label', toggleButton.title);
                    toggleButton.querySelector('i')?.classList.toggle('fa-eye', !showPassword);
                    toggleButton.querySelector('i')?.classList.toggle('fa-eye-slash', showPassword);
                });

                applySuggestion();
            })();
        </script>
    @endpush
</x-admin-layout>
