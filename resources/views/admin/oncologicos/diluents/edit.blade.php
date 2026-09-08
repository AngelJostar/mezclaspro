<x-admin-layout>
    <div class="mt-2 max-w-2xl">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">Editar diluyente</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.oncologicos.diluents.update', $diluent) }}" method="POST" class="bg-white p-4 rounded shadow space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="{{ request('return_to') }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Denominación genérica
                </label>
                <input type="text" name="denominacion_generica"
                       value="{{ old('denominacion_generica', $diluent->denominacion_generica) }}" required
                       class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
            </div>

            <div class="border-t pt-5">
                <div class="mb-3 flex items-center justify-between"><h2 class="text-lg font-semibold">Presentaciones comerciales</h2><span class="text-sm text-slate-500">{{ $diluent->catalogPresentations->count() }} registradas</span></div>
                @if ($presentation)
                    <div class="grid gap-4 rounded border bg-slate-50 p-4 md:grid-cols-2">
                        <input type="hidden" name="catalog_presentation[id]" value="{{ $presentation->id }}">
                        <label class="text-sm">Presentación<input name="catalog_presentation[presentation]" value="{{ old('catalog_presentation.presentation', $presentation->presentation) }}" class="mt-1 w-full rounded border-slate-300"></label>
                        <label class="text-sm">Nombre comercial<input name="catalog_presentation[commercial_name]" value="{{ old('catalog_presentation.commercial_name', $presentation->commercial_name) }}" class="mt-1 w-full rounded border-slate-300"></label>
                        <label class="text-sm">Fabricante<input name="catalog_presentation[manufacturer]" value="{{ old('catalog_presentation.manufacturer', $presentation->manufacturer) }}" class="mt-1 w-full rounded border-slate-300"></label>
                        <label class="text-sm">Volumen (mL)<input name="catalog_presentation[volume_ml]" type="number" step="0.01" value="{{ old('catalog_presentation.volume_ml', $presentation->volume_ml) }}" class="mt-1 w-full rounded border-slate-300"></label>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Selecciona una presentación desde el inventario para editarla.</p>
                @endif
            </div>

            <div class="text-right">
                <a href="{{ route('admin.oncologicos.diluents.index') }}"
                   class="px-4 py-2 mr-2 border rounded text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Actualizar</button>
            </div>
        </form>
    </div>
</x-admin-layout>
