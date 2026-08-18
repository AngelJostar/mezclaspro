<x-admin-layout>
    <div class="rounded-xl bg-white p-5 shadow-sm">
        @include('admin.catalogo-listas.partials.section-nav', [
            'categories' => $categories,
            'category' => $category,
            'mode' => $mode,
        ])

        <div class="mb-5 flex flex-col gap-3 border-b border-gray-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Nuevo producto - {{ $categories[$category]['label'] }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Registra el producto y su presentacion comercial en la categoria seleccionada.
                </p>
            </div>

            <a href="{{ route('admin.catalogo-listas.catalog', ['category' => $category]) }}"
                class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                &larr; Volver al catalogo
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold">Revisa la informacion capturada.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ route('admin.catalogo-listas.products.store', ['category' => $category]) }}"
            class="space-y-6">
            @csrf

            <section>
                <h3 class="mb-3 text-sm font-bold text-gray-900">Datos del producto</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="generic_description" class="mb-1 block text-sm font-semibold text-gray-700">
                            Descripci&oacute;n gen&eacute;rica <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="generic_description" name="generic_description"
                            value="{{ old('generic_description') }}" required maxlength="255"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="commercial_name" class="mb-1 block text-sm font-semibold text-gray-700">
                            Descripci&oacute;n distintiva (Marca) <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="commercial_name" name="commercial_name"
                            value="{{ old('commercial_name') }}" required maxlength="255"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="concentration" class="mb-1 block text-sm font-semibold text-gray-700">
                            Concentraci&oacute;n <span class="text-red-600">*</span>
                        </label>
                        <div class="flex rounded-md shadow-sm">
                            <input type="number" id="concentration" name="concentration"
                                value="{{ old('concentration') }}" required min="0.0001" step="0.0001"
                                class="min-w-0 flex-1 rounded-l-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <span
                                class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm font-semibold uppercase text-gray-600">
                                {{ $concentrationUnit }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label for="presentation" class="mb-1 block text-sm font-semibold text-gray-700">
                            Presentaci&oacute;n <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="presentation" name="presentation" value="{{ old('presentation') }}"
                            required maxlength="255" placeholder="Ej. Frasco ampula 50 mg"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="conc_min" class="mb-1 block text-sm font-semibold text-gray-700">
                            Concentraci&oacute;n m&iacute;nima
                        </label>
                        <input type="number" id="conc_min" name="conc_min" value="{{ old('conc_min') }}"
                            min="0" step="0.0001"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="conc_max" class="mb-1 block text-sm font-semibold text-gray-700">
                            Concentraci&oacute;n m&aacute;xima
                        </label>
                        <input type="number" id="conc_max" name="conc_max" value="{{ old('conc_max') }}"
                            min="0" step="0.0001"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </section>

            <section class="grid gap-5 border-t border-gray-200 pt-5 lg:grid-cols-2">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Diluyentes</h3>
                    <p class="mb-3 mt-1 text-xs text-gray-500">Selecciona uno o varios diluyentes permitidos.</p>

                    <div class="max-h-52 overflow-y-auto rounded-md border border-gray-200">
                        @forelse ($diluents as $diluent)
                            <label class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3 py-2 text-sm last:border-b-0 hover:bg-gray-50">
                                <input type="checkbox" name="diluents[]" value="{{ $diluent->id }}"
                                    @checked(in_array($diluent->id, old('diluents', [])))
                                    class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                                <span>{{ $diluent->denominacion_generica }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-4 text-sm text-gray-500">No hay diluyentes registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-900">V&iacute;a de administraci&oacute;n</h3>
                    <p class="mb-3 mt-1 text-xs text-gray-500">Selecciona una o varias vias permitidas.</p>

                    <div class="max-h-52 overflow-y-auto rounded-md border border-gray-200">
                        @forelse ($routes as $route)
                            <label class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3 py-2 text-sm last:border-b-0 hover:bg-gray-50">
                                <input type="checkbox" name="routes[]" value="{{ $route->id }}"
                                    @checked(in_array($route->id, old('routes', [])))
                                    class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                                <span>{{ $route->name }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-4 text-sm text-gray-500">No hay vias registradas.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.catalogo-listas.catalog', ['category' => $category]) }}"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-900 px-5 text-sm font-bold text-white hover:bg-blue-950">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Guardar producto
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
