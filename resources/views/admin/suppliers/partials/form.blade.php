@php
    $categoryOptions = [
        'Medicamentos',
        'Material de curacion',
        'Red fria',
        'Reactivos',
        'Empaque',
        'Servicios',
        'Insumos',
    ];
    $subcategoryOptions = [
        'Oncologicos',
        'Nutricionales',
        'Antibioticos',
        'Insumos',
        'Material de curacion',
        'Reactivos',
        'Red fria',
        'Empaque',
        'Servicios',
        'General',
    ];
    $states = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de Mexico', 'Coahuila', 'Colima', 'Durango', 'Estado de Mexico',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacan', 'Morelos', 'Nayarit',
        'Nuevo Leon', 'Oaxaca', 'Puebla', 'Queretaro', 'Quintana Roo', 'San Luis Potosi',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatan', 'Zacatecas',
    ];
    $bankOptions = [
        'BBVA', 'Banamex', 'Banorte', 'Santander', 'HSBC', 'Scotiabank', 'Banco Azteca',
        'Bajio', 'Inbursa', 'Afirme', 'Banregio', 'Multiva', 'Monex', 'Otro',
    ];
    $inputClass = 'mt-1 h-9 w-full rounded-md border-gray-300 px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500';
    $labelClass = 'block text-xs font-semibold text-gray-700';
    $activeStatus = old('status', $supplier->status ?: \App\Models\Supplier::STATUS_ACTIVE);
@endphp

<x-validation-errors class="mb-4" />

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
    <fieldset class="p-4">
        <legend class="sr-only">Datos generales</legend>
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">1</span>
            <h2 class="text-sm font-bold text-gray-950">Datos generales</h2>
        </div>

        <div class="grid grid-cols-1 gap-x-5 gap-y-3 md:grid-cols-2 xl:grid-cols-3">
            <label class="{{ $labelClass }}">
                Comprador asignado <span class="text-red-600">*</span>
                <select name="assigned_buyer_id" required class="{{ $inputClass }}">
                    <option value="">Seleccionar comprador...</option>
                    @foreach ($buyers as $buyer)
                        @php($buyerName = trim($buyer->name.' '.$buyer->lastname))
                        <option value="{{ $buyer->id }}" @selected((string) old('assigned_buyer_id', $supplier->assigned_buyer_id) === (string) $buyer->id)>
                            {{ $buyerName }}{{ $buyer->username ? ' ('.$buyer->username.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] font-normal leading-4 text-gray-500">
                    El proveedor estar&aacute; disponible para las &oacute;rdenes de compra del comprador asignado.
                </span>
            </label>

            <label class="{{ $labelClass }}">
                Raz&oacute;n social <span class="text-red-600">*</span>
                <input name="name" value="{{ old('name', $supplier->name) }}" required maxlength="255"
                    placeholder="Nombre fiscal del proveedor" class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                RFC <span class="text-red-600">*</span>
                <input name="rfc" value="{{ old('rfc', $supplier->rfc) }}" required maxlength="20"
                    placeholder="RFC con homoclave" class="{{ $inputClass }} uppercase">
            </label>

            <label class="{{ $labelClass }}">
                Nombre comercial
                <input name="commercial_name" value="{{ old('commercial_name', $supplier->commercial_name) }}" maxlength="255"
                    placeholder="Nombre comercial del proveedor" class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                Giro de proveedur&iacute;a <span class="text-red-600">*</span>
                <select name="category" required class="{{ $inputClass }}">
                    <option value="">Seleccionar giro...</option>
                    @foreach ($categoryOptions as $categoryOption)
                        <option value="{{ $categoryOption }}" @selected(old('category', $supplier->category) === $categoryOption)>{{ $categoryOption }}</option>
                    @endforeach
                </select>
            </label>

            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-5">
                <label class="{{ $labelClass }}">
                    Subcategor&iacute;a <span class="text-red-600">*</span>
                    <select name="subcategory" required class="{{ $inputClass }}">
                        <option value="">Seleccionar...</option>
                        @foreach ($subcategoryOptions as $subcategoryOption)
                            <option value="{{ $subcategoryOption }}" @selected(old('subcategory', $supplier->subcategory) === $subcategoryOption)>{{ $subcategoryOption }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="min-w-28">
                    <span class="{{ $labelClass }}">Proveedor activo</span>
                    <input type="hidden" name="status" value="{{ \App\Models\Supplier::STATUS_INACTIVE }}">
                    <label class="mt-3 inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="status" value="{{ \App\Models\Supplier::STATUS_ACTIVE }}"
                            class="peer sr-only" @checked($activeStatus === \App\Models\Supplier::STATUS_ACTIVE)>
                        <span class="relative h-5 w-9 rounded-full bg-gray-300 transition peer-checked:bg-emerald-600 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
                        <span class="text-xs font-medium text-gray-700">Activo</span>
                    </label>
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset class="border-t border-gray-200 p-4">
        <legend class="sr-only">Contacto y domicilio</legend>
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">2</span>
            <h2 class="text-sm font-bold text-gray-950">Contacto y domicilio</h2>
        </div>

        <div class="grid grid-cols-1 gap-x-5 gap-y-3 md:grid-cols-2 xl:grid-cols-3">
            <label class="{{ $labelClass }}">
                Nombre del contacto <span class="text-red-600">*</span>
                <input name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" required maxlength="255"
                    class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                Tel&eacute;fono <span class="text-red-600">*</span>
                <input name="phone" value="{{ old('phone', $supplier->phone) }}" required maxlength="40"
                    inputmode="tel" class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                Correo electr&oacute;nico <span class="text-red-600">*</span>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" required maxlength="255"
                    class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                Estado <span class="text-red-600">*</span>
                <select name="location" required class="{{ $inputClass }}">
                    <option value="">Seleccionar estado...</option>
                    @foreach ($states as $stateOption)
                        <option value="{{ $stateOption }}" @selected(old('location', $supplier->location) === $stateOption)>{{ $stateOption }}</option>
                    @endforeach
                </select>
            </label>

            <label class="{{ $labelClass }}">
                Municipio o alcald&iacute;a <span class="text-red-600">*</span>
                <input name="municipality" value="{{ old('municipality', $supplier->municipality) }}" required maxlength="150"
                    class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                C&oacute;digo postal <span class="text-red-600">*</span>
                <input name="postal_code" value="{{ old('postal_code', $supplier->postal_code) }}" required maxlength="10"
                    inputmode="numeric" class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }} md:col-span-2 xl:col-span-3">
                Domicilio fiscal <span class="text-red-600">*</span>
                <input name="address" value="{{ old('address', $supplier->address) }}" required maxlength="2000"
                    placeholder="Calle, n&uacute;mero, colonia y referencias" class="{{ $inputClass }}">
            </label>
        </div>
    </fieldset>

    <fieldset class="border-t border-gray-200 p-4">
        <legend class="sr-only">Datos bancarios</legend>
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">3</span>
            <h2 class="text-sm font-bold text-gray-950">Datos bancarios</h2>
        </div>

        <div class="grid grid-cols-1 gap-x-5 gap-y-3 md:grid-cols-2 xl:grid-cols-3">
            <label class="{{ $labelClass }}">
                Banco <span class="text-red-600">*</span>
                <select name="bank_name" required class="{{ $inputClass }}">
                    <option value="">Seleccionar banco...</option>
                    @foreach ($bankOptions as $bankOption)
                        <option value="{{ $bankOption }}" @selected(old('bank_name', $supplier->bank_name) === $bankOption)>{{ $bankOption }}</option>
                    @endforeach
                </select>
            </label>

            <label class="{{ $labelClass }}">
                Cuenta <span class="text-red-600">*</span>
                <input name="bank_account" value="{{ old('bank_account', $supplier->bank_account) }}" required maxlength="50"
                    placeholder="N&uacute;mero de cuenta" inputmode="numeric" class="{{ $inputClass }}">
            </label>

            <label class="{{ $labelClass }}">
                CLABE <span class="text-red-600">*</span>
                <input name="bank_clabe" value="{{ old('bank_clabe', $supplier->bank_clabe) }}" required minlength="18" maxlength="18"
                    pattern="[0-9]{18}" placeholder="18 d&iacute;gitos" inputmode="numeric" class="{{ $inputClass }}">
                <span class="mt-1 block text-[11px] font-normal text-gray-500">La CLABE debe tener 18 d&iacute;gitos.</span>
            </label>

            <label class="{{ $labelClass }} md:col-span-2 xl:col-span-3">
                Referencia <span class="text-red-600">*</span>
                <input name="bank_reference" value="{{ old('bank_reference', $supplier->bank_reference) }}" required maxlength="255"
                    placeholder="Referencia bancaria o l&iacute;nea de captura" class="{{ $inputClass }}">
            </label>
        </div>
    </fieldset>

    <fieldset class="border-t border-gray-200 p-4">
        <legend class="sr-only">Documentacion</legend>
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">4</span>
            <h2 class="text-sm font-bold text-gray-950">Documentaci&oacute;n</h2>
            <span class="text-xs italic text-gray-400">Opcional</span>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ([
                ['tax_certificate', 'Constancia de situaci&oacute;n fiscal', 'application/pdf', $supplier->tax_certificate_path],
                ['bank_cover', 'Car&aacute;tula bancaria', 'application/pdf,image/jpeg,image/png', $supplier->bank_cover_path],
                ['additional_document', 'Documentaci&oacute;n adicional', '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx', $supplier->additional_document_path],
            ] as [$field, $label, $accept, $currentPath])
                <label class="block rounded-md border border-gray-200 bg-gray-50 p-3">
                    <span class="block text-xs font-semibold text-gray-700">{!! $label !!}</span>
                    <input type="file" name="{{ $field }}" accept="{{ $accept }}"
                        class="mt-2 block w-full text-xs text-gray-500 file:mr-3 file:rounded-md file:border file:border-blue-300 file:bg-white file:px-3 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-50">
                    @if ($currentPath)
                        <span class="mt-2 block truncate text-[11px] text-gray-500">Actual: {{ basename($currentPath) }}</span>
                    @endif
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
