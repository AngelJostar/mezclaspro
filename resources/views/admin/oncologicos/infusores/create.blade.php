<x-admin-layout>
    <div class="mt-2 max-w-2xl">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">Crear infusor</h1>

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.oncologicos.infusores.store') }}" method="POST"
            class="bg-white p-4 rounded shadow space-y-4">
            @csrf

            {{-- Nombre genérico --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre genérico
                </label>
                <input type="text" name="nombre_generico" value="{{ old('nombre_generico') }}"
                    class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>

            {{-- Nombre comercial --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre comercial
                </label>
                <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial') }}"
                    class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>
            {{-- Precio --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Precio
                </label>
                <input type="number" name="precio" step="0.01" min="0" value="{{ old('precio', '0.00') }}"
                    class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none"
                    placeholder="0.00">
            </div>


            {{-- Lote --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lote
                </label>
                <input type="text" name="lote" value="{{ old('lote') }}"
                    class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>

            {{-- Fecha de caducidad --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha de caducidad
                </label>
                <input type="date" name="caducidad" value="{{ old('caducidad') }}"
                    class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>

            {{-- Activo --}}
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">
                    Activo
                </label>
            </div>

            {{-- Botones --}}
            <div class="text-right">
                <a href="{{ route('admin.oncologicos.infusores.index') }}"
                    class="px-4 py-2 mr-2 border rounded text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
