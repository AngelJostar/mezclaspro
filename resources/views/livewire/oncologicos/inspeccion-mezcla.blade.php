<div>
    @if ($mostrarModalInspeccion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl p-6">

                <h2 class="text-2xl font-semibold mb-6 text-center">Inspección de Mezcla</h2>

                <div class="flex justify-end mb-4">
                    <button onclick="marcarDefault()"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">
                        Marcar default
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    {{-- Columna izquierda --}}
                    <div>

                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Verificación general
                        </h3>

                        <div class="space-y-3">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Tipo de contenedor
                                </label>

                                <select wire:model.defer="tipo_contenedor"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                    <option value="">Seleccione...</option>
                                    <option value="Frasco">Frasco</option>
                                    <option value="Bolsa">Bolsa</option>
                                    <option value="Jeringa">Jeringa</option>
                                    <option value="Infusor">Infusor</option>

                                </select>
                            </div>


                            <h3 class="text-lg font-semibold mb-4 text-gray-800">
                                Inspección del contenido
                            </h3>


                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">

                                @foreach ([
                                    'es_limpia' => '¿Es limpia?',
                                    'es_libre' => '¿Es libre?',
                                    'esta_rotulado' => '¿Está rotulado?',
                                    'numero_lote' => '¿Número de lote visible?',
                                    'medicamento' => '¿Incluye medicamento correcto?',
                                    'dosis_volumen_total' => '¿Dosis y volumen total correctos?',
                                    'volumen_medicamento' => '¿Volumen de medicamento correcto?',
                                    'rubrica_preparador' => '¿Tiene rúbrica del preparador?',
                                    'sello_seguridad' => '¿Tiene sello de seguridad?',
                                    'presenta_grietas' => '¿Presenta grietas?',
                                    'presenta_fugas' => '¿Presenta fugas?',
                                    'esta_roto' => '¿Está roto?',
                                    'coloracion_apropiada' => '¿Coloración apropiada?',
                                    'contenido_homogeneo' => '¿Contenido homogéneo?',
                                    'presenta_particulas' => '¿Presenta partículas?',
                                    'presenta_turbidez' => '¿Presenta turbidez?',
                                    'volumen_correcto' => '¿Volumen correcto?',
                                    'aprueba_contenido' => '¿Aprueba contenido?',
                                    'aprueba_contenedor' => '¿Aprueba contenedor?',
                                    'mezcla_aprobada' => '¿Mezcla aprobada?',
                                ] as $field => $label)

                                    <label class="flex items-center">

                                        <input type="checkbox"
                                            class="check-inspeccion rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            wire:model="{{ $field }}">

                                        <span class="ml-2 text-sm text-gray-700">
                                            {{ $label }}
                                        </span>

                                    </label>

                                @endforeach

                            </div>

                        </div>

                    </div>


                    {{-- Columna derecha --}}
                    <div>

                        <div class="space-y-3">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Dosis Volumen (ml)
                                </label>

                                <input type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model.defer="dosis_volumen"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                @error('dosis_volumen')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Peso de la Mezcla (g)
                                </label>

                                <input type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model.defer="peso_mezcla"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                @error('peso_mezcla')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Observaciones
                                </label>

                                <textarea wire:model.defer="observaciones"
                                    rows="3"
                                    placeholder="N.A."
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"></textarea>

                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Inspeccionó
                                </label>

                                <input type="text"
                                    wire:model.defer="reviso_nombre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                    readonly>
                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Aprobó
                                </label>

                                <input type="text"
                                    wire:model.defer="aprobo_nombre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                    readonly>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="flex justify-end mt-6">

                    <button wire:click="$set('mostrarModalInspeccion', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded mr-2">
                        Cancelar
                    </button>

                    <button wire:click="guardarInspeccion"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar
                    </button>

                </div>

            </div>
        </div>
    @endif


    @push('js')
        <script>

            function marcarDefault() {

                const defaults = {

                    es_limpia: true,
                    es_libre: true,
                    esta_rotulado: true,
                    numero_lote: true,
                    medicamento: true,
                    dosis_volumen_total: true,
                    volumen_medicamento: true,
                    rubrica_preparador: true,
                    sello_seguridad: true,

                    presenta_grietas: false,
                    presenta_fugas: false,
                    esta_roto: false,

                    coloracion_apropiada: false,

                    contenido_homogeneo: true,

                    presenta_particulas: false,
                    presenta_turbidez: false,

                    volumen_correcto: true,

                    aprueba_contenido: true,
                    aprueba_contenedor: true,
                    mezcla_aprobada: true

                };

                Object.keys(defaults).forEach(name => {

                    const checkbox = document.querySelector(`[wire\\:model="${name}"]`);

                    if (checkbox) {

                        checkbox.checked = defaults[name];

                        checkbox.dispatchEvent(
                            new Event('change', { bubbles: true })
                        );

                    }

                });

            }


            window.addEventListener('mezcla-inspeccionada', () => {

                Swal.fire({
                    icon: 'success',
                    title: '¡Mezcla inspeccionada correctamente!',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });

            });

        </script>
    @endpush

</div>
