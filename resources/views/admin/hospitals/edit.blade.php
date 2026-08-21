<x-admin-layout>
    <div class="mt-2 mb-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">Editar Hospital</h1>
        </div>
        <div>
            <a href="{{ route('admin.hospitals.exportarMezclasOnco', $hospital) }}" target="_blank"
                class="text-white bg-green-600 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                <i class="fa-solid fa-file-excel pr-1"></i> Exportar Mezclas Onco
            </a>
        </div>
    </div>

    <form action="{{ route('admin.hospitals.update', $hospital) }}" method="POST"
        class="bg-white rounded-lg p-6 shadow-lg">
        @csrf
        @method('PUT')

        <x-validation-errors class="mb-4" />

        <select name="laboratory_id" class="w-full rounded border-gray-300">
            <option value="">-- Selecciona un laboratorio --</option>
            @foreach ($laboratories as $lab)
                <option value="{{ $lab->id }}"
                    {{ old('laboratory_id', $hospital->laboratory_id) == $lab->id ? 'selected' : '' }}>
                    {{ $lab->nombre }}
                </option>
            @endforeach
        </select>

        <div class="mb-4">
            <x-label class="mb-2">
                Nombre
            </x-label>
            <x-input name="name" class="w-full" placeholder="Escriba el nombre del hospital"
                value="{{ old('name', $hospital->name) }}" />
        </div>

        <div class="mb-4">
            <x-label class="mb-2">
                Direccion
            </x-label>
            <x-input name="adress" class="w-full" placeholder="Tlacotalpan 59, Col. Roma Sur, Cuauhtemoc, CDMX, 06760"
                value="{{ old('adress', $hospital->adress) }}" />
        </div>

        <div class="mb-4">
            <x-label for="free_text" class="mb-2">
                Texto libre
            </x-label>
            <textarea id="free_text" name="free_text" rows="3" maxlength="10000"
                placeholder="Agrega cualquier informacion adicional sobre el hospital."
                class="w-full resize-y rounded border-gray-300">{{ old('free_text', $hospital->free_text) }}</textarea>
        </div>

        <div class="mb-4">
            <x-label for="google_maps_url" class="mb-2">
                Link de ubicaci&oacute;n de Google Maps
            </x-label>
            <x-input id="google_maps_url" type="url" name="google_maps_url" class="w-full"
                placeholder="https://maps.google.com/..."
                value="{{ old('google_maps_url', $hospital->google_maps_url) }}" />
        </div>

        <div class="mb-4">
            <x-label for="country" class="mb-2">
                Pa&iacute;s
            </x-label>
            <x-input id="country" name="country" class="w-full" required
                value="{{ old('country', $hospital->country ?: 'México') }}" />
        </div>

        <div class="mb-4">
            <x-label class="mb-2">
                Estado
            </x-label>
            <input name="is_active" type="hidden" value="0">
            <label class="relative inline-flex items-center cursor-pointer">
                <input name="is_active" type="checkbox" value="1" class="sr-only peer"
                    @checked(old('is_active', $hospital->is_active) == 1)>
                <div
                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                </div>
                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Activar</span>
            </label>
        </div>

        <div class="mb-4">
            <label for="onco_medicine_list_id" class="block text-sm font-medium text-gray-700">
                Lista de medicamentos oncologica
            </label>

            <select name="onco_medicine_list_id" id="onco_medicine_list_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                <option value="">Seleccione una lista</option>

                @foreach ($oncoMedicineLists as $list)
                    <option value="{{ $list->id }}"
                        {{ old('onco_medicine_list_id', $hospital->onco_medicine_list_id ?? null) == $list->id ? 'selected' : '' }}>
                        {{ $list->name }}
                    </option>
                @endforeach

            </select>
        </div>

        <div class="mb-4">
            <label for="nutri_medicine_list_id" class="block text-sm font-medium text-gray-700">
                Lista nutricional
            </label>
            <select name="nutri_medicine_list_id" id="nutri_medicine_list_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Seleccione una lista</option>
                @foreach ($nutriMedicineLists as $list)
                    <option value="{{ $list->id }}"
                        {{ old('nutri_medicine_list_id', $hospital->nutri_medicine_list_id) == $list->id ? 'selected' : '' }}>
                        {{ $list->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="antibiotic_medicine_list_id" class="block text-sm font-medium text-gray-700">
                Lista de antibioticos
            </label>
            <select name="antibiotic_medicine_list_id" id="antibiotic_medicine_list_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Seleccione una lista</option>
                @foreach ($antibioticMedicineLists as $list)
                    <option value="{{ $list->id }}"
                        {{ old('antibiotic_medicine_list_id', $hospital->antibiotic_medicine_list_id) == $list->id ? 'selected' : '' }}>
                        {{ $list->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end">
            <x-button>
                Actualizar hospital
            </x-button>
        </div>
    </form>
</x-admin-layout>
