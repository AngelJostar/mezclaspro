<div>
    @if ($mostrarModalInspeccion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-y-auto p-6">

                <div class="flex items-start justify-between border-b pb-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">
                            Inspección de Nutrición Parenteral
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Verificación física del contenedor, contenido y liberación de la solicitud nutricional.
                        </p>
                    </div>

                    <button type="button"
                        wire:click="$set('mostrarModalInspeccion', false)"
                        class="text-gray-400 hover:text-gray-700 text-2xl leading-none">
                        &times;
                    </button>
                </div>

                @error('estado')
                    <div class="mb-4 p-3 rounded bg-red-100 text-red-700 border border-red-300">
                        {{ $message }}
                    </div>
                @enderror

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-100 text-red-700 border border-red-300">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-end mb-4">
                    <button type="button" onclick="marcarDefaultNutricional()"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-check-double mr-1"></i>
                        Marcar default
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    {{-- Columna izquierda --}}
                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Verificación general
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Tipo de contenedor
                                </label>

                                <select wire:model.defer="tipo_contenedor"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                                    <option value="">Seleccione...</option>
                                    <option value="Bolsa">Bolsa</option>
                                    <option value="Frasco">Frasco</option>
                                    <option value="Jeringa">Jeringa</option>
                                    <option value="Infusor">Infusor</option>
                                </select>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold mb-4 text-gray-800">
                                    Inspección del contenedor y contenido
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
                                        'mezcla_aprobada' => '¿Nutrición aprobada?',
                                    ] as $field => $label)
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox"
                                                class="check-inspeccion-nutricional rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                wire:model="{{ $field }}">

                                            <span class="text-sm text-gray-700">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Columna derecha --}}
                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Datos de inspección
                        </h3>

                        <div class="space-y-4">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Dosis / volumen total (mL)
                                </label>

                                <input type="number" step="0.01" min="0.01" wire:model.defer="dosis_volumen" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                @error('dosis_volumen')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Peso de la nutrición (g)
                                </label>

                                <input type="number" step="0.01" min="0.01" wire:model.defer="peso_mezcla" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                @error('peso_mezcla')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Observaciones
                                </label>

                                <textarea wire:model.defer="observaciones" rows="4" placeholder="N.A."
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Inspeccionó
                                    </label>

                                    <input type="text" wire:model.defer="reviso_nombre"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                        readonly>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Aprobó
                                    </label>

                                    <input type="text" wire:model.defer="aprobo_nombre"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                        readonly>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                                Al guardar la inspección, la solicitud pasará automáticamente al estado
                                <strong>Revisada</strong>.
                            </div>

                        </div>
                    </div>

                </div>

                <div class="flex justify-end mt-8 border-t pt-4">
                    <button type="button" wire:click="$set('mostrarModalInspeccion', false)"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg mr-2 hover:bg-gray-300">
                        Cancelar
                    </button>

                    <button type="button" wire:click="guardarInspeccion"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fa-solid fa-floppy-disk mr-1"></i>
                        Guardar inspección
                    </button>
                </div>

            </div>
        </div>
    @endif

    @push('js')
        <script>
            function marcarDefaultNutricional() {
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

                    coloracion_apropiada: true,
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
                            new Event('change', {
                                bubbles: true
                            })
                        );
                    }
                });
            }

            window.addEventListener('nutricional-inspeccionada', () => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Solicitud nutricional inspeccionada correctamente!',
                    text: 'La solicitud ahora está en estado Revisada.',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            });
        </script>
    @endpush
</div>
