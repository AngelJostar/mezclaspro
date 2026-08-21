<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-medium text-gray-800">
            Editar Central
        </h1>

        <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratory->id]) }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST"
              action="{{ route('admin.oncologicos.laboratory.update', $laboratory) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre *
                    </label>
                    <input type="text"
                           name="nombre"
                           value="{{ old('nombre', $laboratory->nombre) }}"
                           required
                           class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('nombre')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado
                    </label>
                    <input type="text"
                           name="estado"
                           value="{{ old('estado', $laboratory->estado) }}"
                           class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('estado')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dirección --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Dirección
                    </label>
                    <input type="text"
                           name="direccion"
                           value="{{ old('direccion', $laboratory->direccion) }}"
                           class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('direccion')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Activo --}}
                <div class="flex items-center mt-4">
                    @php
                        $checked = old('activo', $laboratory->activo) ? true : false;
                    @endphp

                    <input type="checkbox"
                           name="activo"
                           value="1"
                           {{ $checked ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">
                        Central activa
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratory->id]) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
