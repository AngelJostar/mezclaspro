<x-admin-layout>
    <div class="mt-2 max-w-4xl">
        <h1 class="text-2xl font-semibold text-gray-800">Agregar presentación de diluyente</h1>
        <p class="mb-4 mt-1 text-sm text-gray-600">Selecciona un genérico existente para agregarle una presentación, o usa “Otro” para crear el genérico y su primera presentación.</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-3 text-red-800"><ul class="ml-5 list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('admin.oncologicos.diluents.store') }}" method="POST" class="space-y-5 rounded bg-white p-5 shadow" id="diluent-form">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Denominación genérica</label>
                <select name="diluent_id" id="diluent_id" class="w-full rounded border-gray-300">
                    <option value="">Seleccionar diluyente...</option>
                    @foreach ($diluents as $diluent)
                        <option value="{{ $diluent->id }}" @selected(old('diluent_id') == $diluent->id)>{{ $diluent->denominacion_generica }}</option>
                    @endforeach
                    <option value="other" @selected(old('generic_mode') === 'new')>Otro: registrar nuevo diluyente</option>
                </select>
                <input type="hidden" name="generic_mode" id="generic_mode" value="{{ old('generic_mode', 'existing') }}">
                <div id="new-generic-wrapper" class="mt-3" hidden>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nueva denominación genérica</label>
                    <input type="text" name="denominacion_generica" id="denominacion_generica" value="{{ old('denominacion_generica') }}" class="w-full rounded border px-3 py-2">
                </div>
            </div>

            <section class="border-t pt-5">
                <h2 class="mb-3 text-lg font-semibold">Presentación comercial</h2>
                <div class="grid gap-4 rounded border bg-slate-50 p-4 md:grid-cols-2">
                    <label class="text-sm">Presentación<input name="catalog_presentation[presentation]" value="{{ old('catalog_presentation.presentation') }}" required class="mt-1 w-full rounded border-slate-300"></label>
                    <label class="text-sm">Nombre comercial<input name="catalog_presentation[commercial_name]" value="{{ old('catalog_presentation.commercial_name') }}" class="mt-1 w-full rounded border-slate-300"></label>
                    <label class="text-sm">Fabricante<input name="catalog_presentation[manufacturer]" value="{{ old('catalog_presentation.manufacturer') }}" class="mt-1 w-full rounded border-slate-300"></label>
                    <label class="text-sm">Volumen (mL)<input name="catalog_presentation[volume_ml]" type="number" min="0" step="0.01" value="{{ old('catalog_presentation.volume_ml') }}" class="mt-1 w-full rounded border-slate-300"></label>
                </div>
            </section>

            <div class="text-right"><a href="{{ route('admin.catalogo-listas.catalog', 'diluyentes') }}" class="mr-2 rounded border px-4 py-2 text-gray-700 hover:bg-gray-50">Cancelar</a><button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Guardar presentación</button></div>
        </form>
    </div>

    <script>
        const diluentSelect = document.getElementById('diluent_id');
        const modeInput = document.getElementById('generic_mode');
        const newGeneric = document.getElementById('new-generic-wrapper');
        const genericName = document.getElementById('denominacion_generica');
        function updateGenericMode() {
            const isNew = diluentSelect.value === 'other';
            newGeneric.hidden = !isNew;
            genericName.required = isNew;
            modeInput.value = isNew ? 'new' : 'existing';
            if (isNew) diluentSelect.name = '';
            else diluentSelect.name = 'diluent_id';
        }
        diluentSelect.addEventListener('change', updateGenericMode);
        updateGenericMode();
    </script>
</x-admin-layout>
