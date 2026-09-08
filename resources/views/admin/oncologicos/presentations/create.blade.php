<x-admin-layout>
    <h1 class="text-2xl font-semibold mb-4">
        Nueva presentación de: {{ $catalog->denominacion }} (#{{ $catalog->id }})
    </h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
            <div class="font-semibold mb-1">Revisa los campos:</div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.oncologicos.medicines.catalog.presentations.store', $catalog) }}"
        method="POST" class="space-y-6">
        @csrf

        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Agrega una o más presentaciones. Los lotes/caducidades se gestionan por laboratorio desde Inventario.
            </div>

            <button type="button" id="addRow"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                + Agregar presentación
            </button>
        </div>

        <div id="rows" class="space-y-4"></div>

        <div class="text-right">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                Guardar presentaciones
            </button>
        </div>
    </form>

    <template id="row-tpl">
        <div class="p-4 border rounded-lg bg-white presentation-row">
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold text-gray-700">
                    Presentación #__n__
                </div>

                <button type="button" class="rm-row px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded">
                    Eliminar
                </button>
            </div>

            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 md:col-span-4">
                    <label class="text-sm">Presentación</label>
                    <input class="w-full border rounded p-2"
                        name="presentations[__i__][presentacion]"
                        value="__presentacion__"
                        required>
                </div>

                <div class="col-span-12 md:col-span-4">
                    <label class="text-sm">Marca</label>
                    <input class="w-full border rounded p-2"
                        name="presentations[__i__][marca]"
                        value="__marca__"
                        required>
                </div>

                <div class="col-span-12 md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <input type="text"
                        name="presentations[__i__][fabricante]"
                        value="__fabricante__"
                        class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200 focus:outline-none">
                </div>

                <div class="col-span-6 md:col-span-2">
                    <label class="text-sm">Contenido (valor)</label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        step="0.01"
                        min="0"
                        name="presentations[__i__][contenido_valor]"
                        value="__contenido_valor__"
                        required>
                </div>

                <div class="col-span-6 md:col-span-2">
                    <label class="text-sm">Unidad</label>
                    <select class="w-full border rounded p-2" name="presentations[__i__][contenido_unidad]">
                        <option value="mg">mg</option>
                        <option value="g">g</option>
                        <option value="ml">ml</option>
                        <option value="UI">UI</option>
                        <option value="smg">smg</option>
                    </select>
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="text-sm">Concentración de medicamento (mg) (opcional)</label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        step="0.01"
                        min="0"
                        name="presentations[__i__][cantidad_medicamento]"
                        value="__cantidad_medicamento__">
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="text-sm">Volumen del diluyente (mL) (opcional)</label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        step="0.01"
                        min="0"
                        name="presentations[__i__][volumen_diluyente]"
                        value="__volumen_diluyente__">
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="text-sm">Estabilidad reconstituido (horas) <span class="text-red-600">*</span></label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        min="1"
                        max="8760"
                        required
                        name="presentations[__i__][stability_hours]"
                        value="__stability_hours__"
                        placeholder="Ej. 24">
                    <div class="text-xs text-gray-500 mt-1">
                        Tiempo máximo de uso recomendado.
                    </div>
                </div>

                <div class="col-span-6 md:col-span-2">
                    <label class="text-sm">Temp. mín (°C) (opcional)</label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        min="0"
                        name="presentations[__i__][temp_min_c]"
                        value="__temp_min_c__"
                        placeholder="Ej. 8">
                </div>

                <div class="col-span-6 md:col-span-2">
                    <label class="text-sm">Temp. máx (°C) (opcional)</label>
                    <input class="w-full border rounded p-2"
                        type="number"
                        min="0"
                        name="presentations[__i__][temp_max_c]"
                        value="__temp_max_c__"
                        placeholder="Ej. 20">
                </div>

                <div class="col-span-12 md:col-span-5">
                    <label class="text-sm">Forma de reconstitución (opcional)</label>
                    <input class="w-full border rounded p-2"
                        type="text"
                        name="presentations[__i__][forma_reconstitucion]"
                        value="__forma_reconstitucion__"
                        placeholder="Ej. Disolver el contenido en 500 mL de agua estéril">
                </div>

                <div class="col-span-12 md:col-span-5">
                    <label class="text-sm">Leyenda / Indicaciones (opcional)</label>
                    <textarea class="w-full border rounded p-2"
                        rows="2"
                        name="presentations[__i__][legend]"
                        placeholder="Ej. Refrigerar. Proteger de la luz. No agitar.">__legend__</textarea>
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="text-sm">Disponible</label>
                    <select class="w-full border rounded p-2" name="presentations[__i__][is_available]">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="mb-4 col-span-12 md:col-span-4">
                    <div class="p-3 rounded bg-blue-50 border border-blue-200 text-blue-800 text-sm">
                        <div class="font-semibold">Lotes y caducidades</div>
                        <div class="text-xs mt-1">
                            Los lotes/caducidades se administran por laboratorio desde <b>Oncológicos → Inventario</b>.
                            Aquí solo capturas los datos fijos de la presentación.
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('admin.oncologicos.inventory.selectLaboratory') }}"
                                class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Ir a Inventario
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @php
        $oldPresentations = old('presentations', [
            [
                'presentacion' => '',
                'marca' => '',
                'fabricante' => '',
                'contenido_valor' => '',
                'contenido_unidad' => 'mg',
                'cantidad_medicamento' => '',
                'volumen_diluyente' => '',
                'stability_hours' => '',
                'temp_min_c' => '',
                'temp_max_c' => '',
                'forma_reconstitucion' => '',
                'legend' => '',
                'is_available' => '1',
            ],
        ]);
    @endphp

    <script>
        const rows = document.getElementById('rows');
        const tpl = document.getElementById('row-tpl').innerHTML;
        const add = document.getElementById('addRow');

        const oldRows = @json($oldPresentations);

        let i = 0;

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');

        const addRow = (data = {}) => {
            const html = tpl
                .replaceAll('__i__', i)
                .replaceAll('__n__', (i + 1).toString())
                .replaceAll('__presentacion__', escapeHtml(data.presentacion))
                .replaceAll('__marca__', escapeHtml(data.marca))
                .replaceAll('__fabricante__', escapeHtml(data.fabricante))
                .replaceAll('__contenido_valor__', escapeHtml(data.contenido_valor))
                .replaceAll('__cantidad_medicamento__', escapeHtml(data.cantidad_medicamento))
                .replaceAll('__volumen_diluyente__', escapeHtml(data.volumen_diluyente))
                .replaceAll('__stability_hours__', escapeHtml(data.stability_hours))
                .replaceAll('__temp_min_c__', escapeHtml(data.temp_min_c))
                .replaceAll('__temp_max_c__', escapeHtml(data.temp_max_c))
                .replaceAll('__forma_reconstitucion__', escapeHtml(data.forma_reconstitucion))
                .replaceAll('__legend__', escapeHtml(data.legend));

            rows.insertAdjacentHTML('beforeend', html);

            const row = rows.lastElementChild;
            row.querySelector('[name="presentations[' + i + '][contenido_unidad]"]').value = data.contenido_unidad || 'mg';
            row.querySelector('[name="presentations[' + i + '][is_available]"]').value = String(data.is_available ?? '1');

            i++;
        };

        add.addEventListener('click', () => addRow());

        rows.addEventListener('click', (e) => {
            if (e.target.classList.contains('rm-row')) {
                e.target.closest('.presentation-row').remove();
            }
        });

        oldRows.forEach((row) => addRow(row));
    </script>
</x-admin-layout>
