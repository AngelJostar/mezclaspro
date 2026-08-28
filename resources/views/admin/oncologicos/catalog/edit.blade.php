<x-admin-layout>
    <div class="mx-auto max-w-6xl py-4">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Editar medicamento oncologico</h1>
                <p class="text-sm text-gray-500">Denominacion generica y presentaciones en un solo formulario.</p>
            </div>
            <a class="rounded border px-3 py-2 text-sm font-semibold" href="{{ route('admin.catalogo-listas.catalog', ['category' => 'oncologicos']) }}">Volver al catalogo</a>
        </div>

        <form method="POST" action="{{ route('admin.oncologicos.medicines.catalog.update', $medicamento) }}" class="space-y-6 rounded-lg bg-white p-6 shadow">
            @csrf
            @method('PUT')
            <x-validation-errors class="mb-4" />
            <section class="grid gap-4 md:grid-cols-2">
                <div><x-label>Denominacion generica</x-label><x-input class="mt-1 w-full" name="denominacion" value="{{ old('denominacion', $medicamento->denominacion) }}" required /></div>
                <div class="grid grid-cols-2 gap-3"><label class="text-sm">Conc. minima<x-input class="mt-1 w-full" type="number" step=".01" min="0" name="conc_min" value="{{ old('conc_min', $medicamento->conc_min) }}" /></label><label class="text-sm">Conc. maxima<x-input class="mt-1 w-full" type="number" step=".01" min="0" name="conc_max" value="{{ old('conc_max', $medicamento->conc_max) }}" /></label></div>
                <label class="text-sm"><input type="hidden" name="requires_infusor" value="0"><input type="checkbox" name="requires_infusor" value="1" @checked(old('requires_infusor', $medicamento->requires_infusor))> Requiere infusor</label>
            </section>
            <section class="grid gap-5 md:grid-cols-2">
                <div><p class="mb-2 text-sm font-semibold">Diluyentes permitidos</p><div class="max-h-40 space-y-1 overflow-auto rounded border p-3">@foreach($diluents as $diluent)<label class="block text-sm"><input type="checkbox" name="diluents[]" value="{{ $diluent->id }}" @checked(in_array($diluent->id, old('diluents', $selectedDiluents)))> {{ $diluent->denominacion_generica }}</label>@endforeach</div></div>
                <div><p class="mb-2 text-sm font-semibold">Vias de administracion</p><div class="max-h-40 space-y-1 overflow-auto rounded border p-3">@foreach($routes as $route)<label class="block text-sm"><input type="checkbox" name="routes[]" value="{{ $route->id }}" @checked(in_array($route->id, old('routes', $selectedRoutes)))> {{ $route->name }}</label>@endforeach</div></div>
            </section>
            @php($presentations = old('presentations', $medicamento->presentations->map(fn ($presentation) => $presentation->only(['id', 'presentacion', 'marca', 'fabricante', 'contenido_valor', 'contenido_unidad', 'cantidad_medicamento', 'volumen_diluyente', 'precio_frasco', 'stability_hours', 'is_available']))->values()->all()))
            <section><div class="mb-3 flex justify-between"><h2 class="text-lg font-semibold">Presentaciones</h2><button type="button" id="add-presentation" class="rounded bg-blue-900 px-3 py-2 text-sm font-semibold text-white">Agregar presentacion</button></div><div id="presentation-rows" class="space-y-4"></div></section>
            <div class="flex justify-end"><x-button>Guardar cambios</x-button></div>
        </form>
    </div>

    <template id="presentation-template">
        <div class="presentation-row rounded border bg-gray-50 p-4">
            <div class="mb-3 flex justify-between"><strong class="title">Presentacion</strong><button type="button" class="remove text-sm font-semibold text-red-600">Eliminar</button></div>
            <input type="hidden" data-field="id">
            <div class="grid gap-3 md:grid-cols-3">
                <label class="text-sm">Presentacion<input required class="mt-1 w-full rounded border-gray-300" data-field="presentacion"></label>
                <label class="text-sm">Marca<input class="mt-1 w-full rounded border-gray-300" data-field="marca"></label>
                <label class="text-sm">Fabricante<input class="mt-1 w-full rounded border-gray-300" data-field="fabricante"></label>
                <label class="text-sm">Contenido<input required type="number" step=".0001" min="0" class="mt-1 w-full rounded border-gray-300" data-field="contenido_valor"></label>
                <label class="text-sm">Unidad<select class="mt-1 w-full rounded border-gray-300" data-field="contenido_unidad"><option>mg</option><option>g</option><option>ml</option><option>UI</option><option>smg</option></select></label>
                <label class="text-sm">Medicamento (mg)<input type="number" step=".0001" min="0" class="mt-1 w-full rounded border-gray-300" data-field="cantidad_medicamento"></label>
                <label class="text-sm">Volumen (mL)<input type="number" step=".0001" min="0" class="mt-1 w-full rounded border-gray-300" data-field="volumen_diluyente"></label>
                <label class="text-sm">Precio por frasco<input type="number" step=".0001" min="0" class="mt-1 w-full rounded border-gray-300" data-field="precio_frasco"></label>
                <label class="text-sm">Estabilidad (horas)<input type="number" min="0" class="mt-1 w-full rounded border-gray-300" data-field="stability_hours"></label>
                <label class="text-sm"><input type="hidden" data-field="is_available" value="0"><input type="checkbox" data-field="is_available" value="1"> Disponible</label>
            </div>
        </div>
    </template>

    @push('js')
        <script>
            const initialPresentations = @json($presentations);
            const rows = document.getElementById('presentation-rows');
            const template = document.getElementById('presentation-template');
            function renumber() {
                [...rows.children].forEach((row, index) => {
                    row.querySelector('.title').textContent = `Presentacion #${index + 1}`;
                    row.querySelectorAll('[data-field]').forEach((field) => field.name = `presentations[${index}][${field.dataset.field}]`);
                });
            }
            function addPresentation(data = {}) {
                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('.presentation-row');
                const index = rows.children.length;
                row.querySelectorAll('[data-field]').forEach((field) => {
                    const key = field.dataset.field;
                    field.name = `presentations[${index}][${key}]`;
                    if (field.type === 'checkbox') field.checked = Number(data[key] ?? 1) === 1;
                    else if (!(field.type === 'hidden' && key === 'is_available')) field.value = data[key] ?? '';
                });
                row.querySelector('.remove').onclick = () => { if (rows.children.length > 1) { row.remove(); renumber(); } };
                rows.appendChild(fragment);
                renumber();
            }
            (initialPresentations.length ? initialPresentations : [{}]).forEach(addPresentation);
            document.getElementById('add-presentation').onclick = () => addPresentation({is_available: 1});
        </script>
    @endpush
</x-admin-layout>
