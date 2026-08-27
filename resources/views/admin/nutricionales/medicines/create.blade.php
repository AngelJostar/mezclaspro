<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Agregar nuevo medicamento</h1>
    </div>

    <form action="{{ route('admin.nutricionales.medicines.store') }}" method="POST"
        class="bg-white rounded-lg p-6 shadow-lg lg:w-[70rem] mx-auto">
        @csrf

        <x-validation-errors class="mb-4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <x-label class="mb-2">
                    Denominación genérica
                </x-label>
                <x-input value="{{ old('denominacion_generica') }}" name="denominacion_generica" class="w-full"
                    placeholder="Escriba la denominación genérica del medicamento" />
            </div>

            <div class="mb-4">
                <x-label class="mb-2">
                    Categoría
                </x-label>
                <x-select class="w-full" name="category_id">
                    <option value="">Seleccione una categoría</option>
                    @foreach ($categories as $category)
                        <option @selected(old('category_id') == $category->id) value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="mb-4">
                <x-label class="mb-2">
                    Input
                </x-label>
                <x-select class="w-full" name="input_id">
                    <option value="">Seleccione un input</option>
                    @foreach ($inputs as $input)
                        <option @selected(old('input_id') == $input->id) value="{{ $input->id }}">
                            {{ $input->description }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="mb-4">
                <x-label class="mb-2">
                    Osmolaridad
                </x-label>
                <x-input type="number" step="0.001" min="0" value="{{ old('osmolaridad') }}"
                    name="osmolaridad" class="w-full" placeholder="Escriba la osmolaridad" />
            </div>

            <div class="mb-4">
                <x-label class="mb-2">
                    Activo
                </x-label>
                <x-select class="w-full" name="is_active">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>No</option>
                </x-select>
            </div>
        </div>

        <hr class="my-6">

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Presentaciones comerciales</h2>
            <button type="button" id="add-presentation"
                class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center">
                <i class="fa-solid fa-plus pr-1"></i> Agregar presentación
            </button>
        </div>

        @php
            $oldPresentations = old('presentations', [
                [
                    'denominacion_comercial' => '',
                    'fabricante' => '',
                    'presentacion' => '',
                    'presentacion_ml' => '',
                    'stability_hours' => '',
                    'is_available' => 1,
                ],
            ]);
        @endphp

        <div id="presentations-container" class="space-y-4">
            @foreach ($oldPresentations as $index => $presentation)
                <div class="presentation-item border rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-700">Presentación #{{ $index + 1 }}</h3>
                        <button type="button"
                            class="remove-presentation text-red-600 hover:text-red-800 font-semibold">
                            Eliminar
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-label class="mb-2">
                                Denominación comercial
                            </x-label>
                            <x-input name="presentations[{{ $index }}][denominacion_comercial]"
                                value="{{ $presentation['denominacion_comercial'] ?? '' }}" class="w-full"
                                placeholder="Ej. AA-ven 10%" />
                        </div>
                        <div>
                            <x-label class="mb-2">
                                Fabricante
                            </x-label>
                            <x-input name="presentations[{{ $index }}][fabricante]"
                                value="{{ $presentation['fabricante'] ?? '' }}" class="w-full"
                                placeholder="Ej. Prodifem" />
                        </div>

                        <div>
                            <x-label class="mb-2">
                                Presentación
                            </x-label>
                            <x-input name="presentations[{{ $index }}][presentacion]"
                                value="{{ $presentation['presentacion'] ?? '' }}" class="w-full"
                                placeholder="Ej. Frasco 250 ml" />
                        </div>

                        <div>
                            <x-label class="mb-2">
                                ML por presentación
                            </x-label>
                            <x-input type="number" step="0.01" min="0"
                                name="presentations[{{ $index }}][presentacion_ml]"
                                value="{{ $presentation['presentacion_ml'] ?? '' }}" class="w-full"
                                placeholder="Ej. 250" />
                        </div>

                        <div>
                            <x-label class="mb-2">
                                Disponible
                            </x-label>
                            <x-select class="w-full" name="presentations[{{ $index }}][is_available]">
                                <option value="1"
                                    {{ ($presentation['is_available'] ?? 1) == 1 ? 'selected' : '' }}>Sí</option>
                                <option value="0"
                                    {{ ($presentation['is_available'] ?? 1) == 0 ? 'selected' : '' }}>No</option>
                            </x-select>
                        </div>
                        <div>
                            <x-label class="mb-2">Estabilidad después de abrir (horas)</x-label>
                            <x-input type="number" min="0" max="8760"
                                name="presentations[{{ $index }}][stability_hours]"
                                value="{{ $presentation['stability_hours'] ?? '' }}" class="w-full"
                                placeholder="Ej. 24" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end mt-6">
            <x-button>
                Crear medicamento
            </x-button>
        </div>
    </form>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('presentations-container');
                const addBtn = document.getElementById('add-presentation');

                function updateTitles() {
                    const items = container.querySelectorAll('.presentation-item');
                    items.forEach((item, index) => {
                        const title = item.querySelector('h3');
                        if (title) {
                            title.textContent = `Presentación #${index + 1}`;
                        }
                    });
                }

                function bindRemoveButtons() {
                    const buttons = container.querySelectorAll('.remove-presentation');
                    buttons.forEach(button => {
                        button.onclick = function() {
                            const items = container.querySelectorAll('.presentation-item');
                            if (items.length === 1) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Mínimo requerido',
                                    text: 'Debes capturar al menos una presentación.'
                                });
                                return;
                            }

                            this.closest('.presentation-item').remove();
                            updateTitles();
                        };
                    });
                }

                addBtn.addEventListener('click', function() {
                    const index = container.querySelectorAll('.presentation-item').length;

                    const html = `
                        <div class="presentation-item border rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-gray-700">Presentación #${index + 1}</h3>
                                <button type="button"
                                    class="remove-presentation text-red-600 hover:text-red-800 font-semibold">
                                    Eliminar
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Denominación comercial
                                    </label>
                                    <input
                                        name="presentations[${index}][denominacion_comercial]"
                                        class="w-full rounded border-gray-300"
                                        placeholder="Ej. AA-ven 10%" />
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Fabricante
                                    </label>
                                    <input
                                        name="presentations[${index}][fabricante]"
                                        class="w-full rounded border-gray-300"
                                        placeholder="Ej. Prodifem" />
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Presentación
                                    </label>
                                    <input
                                        name="presentations[${index}][presentacion]"
                                        class="w-full rounded border-gray-300"
                                        placeholder="Ej. Frasco 250 ml" />
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        ML por presentación
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="presentations[${index}][presentacion_ml]"
                                        class="w-full rounded border-gray-300"
                                        placeholder="Ej. 250" />
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Disponible
                                    </label>
                                    <select class="w-full rounded border-gray-300" name="presentations[${index}][is_available]">
                                        <option value="1" selected>Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Estabilidad después de abrir (horas)
                                    </label>
                                    <input type="number" min="0" max="8760"
                                        name="presentations[${index}][stability_hours]"
                                        class="w-full rounded border-gray-300"
                                        placeholder="Ej. 24" />
                                </div>
                            </div>
                        </div>
                    `;

                    container.insertAdjacentHTML('beforeend', html);
                    bindRemoveButtons();
                    updateTitles();
                });

                bindRemoveButtons();
                updateTitles();
            });
        </script>
    @endpush
</x-admin-layout>
