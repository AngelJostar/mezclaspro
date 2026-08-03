<x-admin-layout>
    <div class="mt-2 max-w-2xl">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">Crear diluyente</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.oncologicos.diluents.store') }}" method="POST" class="bg-white p-4 rounded shadow space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Denominación genérica
                </label>
                <input type="text" name="denominacion_generica" value="{{ old('denominacion_generica') }}" required
                       class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>

            <div class="text-right">
                <a href="{{ route('admin.oncologicos.diluents.index') }}"
                   class="px-4 py-2 mr-2 border rounded text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar</button>
            </div>
        </form>
    </div>
</x-admin-layout>
