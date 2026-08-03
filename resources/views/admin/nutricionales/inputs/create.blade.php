<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Agregar input nutricional</h1>
    </div>

    <form action="{{ route('admin.nutricionales.inputs.store') }}" method="POST"
        class="bg-white rounded-lg p-6 shadow-lg lg:w-[60rem] mx-auto">
        @csrf

        <x-validation-errors class="mb-4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-label class="mb-2">Descripcion</x-label>
                <x-input name="description" value="{{ old('description') }}" class="w-full" required />
            </div>

            <div>
                <x-label class="mb-2">Categoria</x-label>
                <x-select name="category_id" class="w-full" required>
                    <option value="">Seleccione una categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <x-label class="mb-2">Unidad</x-label>
                <x-input name="unidad" value="{{ old('unidad') }}" class="w-full" placeholder="Ej. g/Kg, mEq/Kg, mL" required />
            </div>

            <div>
                <x-label class="mb-2">Tipo de input</x-label>
                <x-select name="tipo_input" class="w-full" required>
                    <option value="ambos" @selected(old('tipo_input', 'ambos') === 'ambos')>Ambos</option>
                    <option value="adulto" @selected(old('tipo_input') === 'adulto')>Adulto</option>
                    <option value="niño" @selected(old('tipo_input') === 'niño')>Nino</option>
                </x-select>
            </div>

            <div>
                <x-label class="mb-2">Orden</x-label>
                <x-input type="number" name="orden_enum" value="{{ old('orden_enum', 0) }}" class="w-full" min="0" required />
            </div>

            <div>
                <x-label class="mb-2">Activo</x-label>
                <x-select name="is_active" class="w-full" required>
                    <option value="1" @selected(old('is_active', '1') == '1')>Si</option>
                    <option value="0" @selected(old('is_active') == '0')>No</option>
                </x-select>
            </div>

            <div>
                <x-label class="mb-2">Multiplicador</x-label>
                <x-input type="number" step="0.001" min="0" name="mult" value="{{ old('mult', 1) }}" class="w-full" required />
            </div>

            <div>
                <x-label class="mb-2">Divisor</x-label>
                <x-input type="number" step="0.00001" min="0.00001" name="div" value="{{ old('div', 1) }}" class="w-full" required />
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.nutricionales.inputs.index') }}"
                class="px-4 py-2 rounded border text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <x-button>Guardar input</x-button>
        </div>
    </form>
</x-admin-layout>
