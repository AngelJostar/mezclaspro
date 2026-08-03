<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Agregar nuevo hospital</h1>
    </div>

    <form action="{{ route('admin.hospitals.store') }}" method="POST" class="bg-white rounded-lg p-6 shadow-lg">
        @csrf

        <x-validation-errors class="mb-4" />

        <div class="mb-4">
            <x-label class="mb-2">Laboratorio de mezclas</x-label>

            <select name="laboratory_id" class="w-full rounded border-gray-300">
                <option value="">-- Selecciona un laboratorio --</option>
                @foreach ($laboratories as $lab)
                    <option value="{{ $lab->id }}" {{ old('laboratory_id') == $lab->id ? 'selected' : '' }}>
                        {{ $lab->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <x-label class="mb-2">
                Nombre
            </x-label>
            <x-input value="{{ old('name_hp') }}" name="name_hp" class="w-full"
                placeholder="Escriba el nombre del hospital" />
        </div>

        <div class="mb-4">
            <x-label class="mb-2">
                Direccion
            </x-label>
            <x-input value="{{ old('adress') }}" name="adress" class="w-full"
                placeholder="Tlacotalpan 59, Col. Roma Sur, Cuauhtemoc, CDMX, 06760" />
        </div>

        <div class="mb-4">
            <label for="onco_medicine_list_id" class="block text-sm font-medium text-gray-700">
                Lista de medicamentos oncologica
            </label>

            <select name="onco_medicine_list_id" id="onco_medicine_list_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                <option value="">Seleccione una lista</option>

                @foreach ($oncoMedicineLists as $list)
                    <option value="{{ $list->id }}" {{ old('onco_medicine_list_id') == $list->id ? 'selected' : '' }}>
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
                    <option value="{{ $list->id }}" {{ old('nutri_medicine_list_id') == $list->id ? 'selected' : '' }}>
                        {{ $list->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end">
            <x-button>
                Crear hospital
            </x-button>
        </div>
    </form>
</x-admin-layout>
