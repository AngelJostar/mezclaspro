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
        $selectedDays = old('operation_days', ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']);
        $inputClass = 'mt-1 h-9 w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500';
        $labelClass = 'block text-xs font-medium text-gray-700';
    @endphp

    <section class="overflow-hidden rounded-lg bg-white shadow-lg">
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

            <div class="grid grid-cols-1 gap-4 px-5 py-4 xl:grid-cols-[minmax(0,1fr)_280px]">
                <div class="rounded-lg border border-gray-200 p-4">
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-900">Datos generales</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2">
                            <label class="{{ $labelClass }}">
                                Nombre oficial del hospital <span class="text-red-600">*</span>
                                <input name="name_hp" value="{{ old('name_hp') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Nombre corto
                                <input name="short_name" value="{{ old('short_name') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                Clave interna <span class="text-red-600">*</span>
                                <input name="internal_key" value="{{ old('internal_key') }}" required class="{{ $inputClass }}">
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
                                RFC
                                <input name="rfc" value="{{ old('rfc') }}" maxlength="20" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }}">
                                CLUES
                                <input name="clues" value="{{ old('clues') }}" maxlength="30" class="{{ $inputClass }}">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Ubicaci&oacute;n</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-6">
                            <label class="{{ $labelClass }} md:col-span-2">
                                Estado <span class="text-red-600">*</span>
                                <select name="state" required class="{{ $inputClass }}">
                                    <option value="">Selecciona un estado</option>
                                    @foreach ($states as $stateOption)
                                        <option value="{{ $stateOption }}" @selected(old('state') === $stateOption)>{{ $stateOption }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Municipio o alcald&iacute;a <span class="text-red-600">*</span>
                                <input name="municipality" value="{{ old('municipality') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                C&oacute;digo postal <span class="text-red-600">*</span>
                                <input name="postal_code" value="{{ old('postal_code') }}" required maxlength="10" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Colonia
                                <input name="neighborhood" value="{{ old('neighborhood') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-4">
                                Calle y n&uacute;mero <span class="text-red-600">*</span>
                                <input name="street_number" value="{{ old('street_number') }}" required class="{{ $inputClass }}">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Contacto y operaci&oacute;n</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-6">
                            <label class="{{ $labelClass }} md:col-span-2">
                                Responsable del hospital <span class="text-red-600">*</span>
                                <input name="contact_name" value="{{ old('contact_name') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Cargo
                                <input name="contact_position" value="{{ old('contact_position') }}" class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Tel&eacute;fono <span class="text-red-600">*</span>
                                <input name="phone" value="{{ old('phone') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Correo electr&oacute;nico <span class="text-red-600">*</span>
                                <input type="email" name="email" value="{{ old('email') }}" required class="{{ $inputClass }}">
                            </label>

                            <label class="{{ $labelClass }} md:col-span-2">
                                Horario de recepci&oacute;n
                                <input name="reception_hours" value="{{ old('reception_hours') }}" placeholder="08:00 - 18:00" class="{{ $inputClass }}">
                            </label>

                            <div class="md:col-span-2">
                                <span class="{{ $labelClass }}">D&iacute;as de operaci&oacute;n</span>
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
                                    <input type="checkbox" name="service_oncology" value="1"
                                        class="rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500"
                                        @checked(old('service_oncology'))>
                                    Oncol&oacute;gicos
                                </label>
                                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-red-300 bg-red-50 px-3 text-sm font-medium text-red-800">
                                    <input type="checkbox" name="service_antibiotics" value="1"
                                        class="rounded border-red-400 text-red-600 focus:ring-red-500"
                                        @checked(old('service_antibiotics'))>
                                    Antibi&oacute;ticos
                                </label>
                                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-blue-300 bg-blue-50 px-3 text-sm font-medium text-blue-800">
                                    <input type="checkbox" name="service_nutrition" value="1"
                                        class="rounded border-blue-400 text-blue-600 focus:ring-blue-500"
                                        @checked(old('service_nutrition'))>
                                    Nutricionales
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mt-5 border-t border-gray-200 pt-4">
                        <legend class="text-sm font-semibold text-gray-900">Configuraci&oacute;n operativa</legend>
                        <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                            <label class="{{ $labelClass }}">
                                Laboratorio de mezclas
                                <select name="laboratory_id" class="{{ $inputClass }}">
                                    <option value="">Sin asignar</option>
                                    @foreach ($laboratories as $lab)
                                        <option value="{{ $lab->id }}" @selected((string) old('laboratory_id') === (string) $lab->id)>{{ $lab->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }}">
                                Lista oncol&oacute;gica
                                <select name="onco_medicine_list_id" class="{{ $inputClass }}">
                                    <option value="">Sin asignar</option>
                                    @foreach ($oncoMedicineLists as $list)
                                        <option value="{{ $list->id }}" @selected((string) old('onco_medicine_list_id') === (string) $list->id)>{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }}">
                                Lista nutricional
                                <select name="nutri_medicine_list_id" class="{{ $inputClass }}">
                                    <option value="">Sin asignar</option>
                                    @foreach ($nutriMedicineLists as $list)
                                        <option value="{{ $list->id }}" @selected((string) old('nutri_medicine_list_id') === (string) $list->id)>{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $labelClass }}">
                                Lista de antibi&oacute;ticos
                                <select name="antibiotic_medicine_list_id" class="{{ $inputClass }}">
                                    <option value="">Sin asignar</option>
                                    @foreach ($antibioticMedicineLists as $list)
                                        <option value="{{ $list->id }}" @selected((string) old('antibiotic_medicine_list_id') === (string) $list->id)>{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </fieldset>
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
                            <input type="checkbox" name="use_institution_fiscal_data" value="1"
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
                    <button type="submit" name="submission" value="draft"
                        class="h-10 rounded-lg border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        Guardar borrador
                    </button>
                    <button type="submit" name="submission" value="create"
                        class="h-10 rounded-lg bg-azul-prodifem px-5 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                        Crear hospital
                    </button>
                </div>
            </div>
        </form>
    </section>
</x-admin-layout>
