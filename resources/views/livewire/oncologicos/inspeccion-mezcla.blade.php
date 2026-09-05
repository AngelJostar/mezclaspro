<div>
    @if ($mostrarModalInspeccion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl max-h-[90vh] overflow-y-auto p-6">

                <div class="flex items-center justify-between gap-4 border-b pb-3 mb-4">
                    <h2 class="mixture-workflow-heading">Inspección | {{ $mixtureContext }}</h2>
                    <button type="button" wire:click="$set('mostrarModalInspeccion', false)"
                        aria-label="Cerrar inspección" class="shrink-0 w-8 h-8 border border-red-600 rounded text-red-600 hover:bg-red-50">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="flex justify-end mb-4">
                    <button onclick="marcarDefault()"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">
                        Marcar default
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Lote de la mezcla
                            </label>

                            <input type="text"
                                value="{{ $lote_mezcla !== '' ? $lote_mezcla : 'S/D' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-800"
                                readonly>
                        </div>

                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Verificacion general
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
                                Inspeccion del contenido
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                                @foreach ([
                                    'esta_rotulado' => 'Esta rotulado?',
                                    'numero_lote' => 'Numero de lote visible?',
                                    'medicamento' => 'Incluye medicamento correcto?',
                                    'dosis_volumen_total' => 'Dosis y volumen total correctos?',
                                    'volumen_medicamento' => 'Volumen de medicamento correcto?',
                                    'rubrica_preparador' => 'Tiene rubrica del preparador?',
                                    'sello_seguridad' => 'Tiene sello de seguridad?',
                                    'presenta_fugas' => 'Presenta fugas?',
                                    'esta_roto' => 'Esta roto?',
                                    'coloracion_apropiada' => 'Coloracion apropiada?',
                                    'contenido_homogeneo' => 'Contenido homogeneo?',
                                    'presenta_particulas' => 'Presenta particulas?',
                                    'presenta_turbidez' => 'Presenta turbidez?',
                                    'aprueba_contenido' => 'Aprueba la inspeccion del contenido?',
                                    'aprueba_contenedor' => 'Aprueba contenedor?',
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
                                    LA MEZCLA SE CONSIDERA APROBADA
                                </label>

                                <select wire:model.defer="mezcla_aprobada"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Inspecciono
                                </label>

                                <input type="text"
                                    wire:model.defer="reviso_nombre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                    readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Aprobo
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
                    esta_rotulado: true,
                    numero_lote: true,
                    medicamento: true,
                    dosis_volumen_total: true,
                    volumen_medicamento: true,
                    rubrica_preparador: true,
                    sello_seguridad: true,
                    presenta_fugas: false,
                    esta_roto: false,
                    coloracion_apropiada: true,
                    contenido_homogeneo: true,
                    presenta_particulas: false,
                    presenta_turbidez: false,
                    aprueba_contenido: true,
                    aprueba_contenedor: true,
                    mezcla_aprobada: true
                };

                Object.keys(defaults).forEach(name => {
                    const checkbox = document.querySelector(`[wire\\:model="${name}"]`);
                    const select = document.querySelector(`[wire\\:model\\.defer="${name}"]`);

                    if (checkbox) {
                        checkbox.checked = defaults[name];
                        checkbox.dispatchEvent(new Event('input', { bubbles: true }));
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    if (select) {
                        select.value = defaults[name] ? '1' : '0';
                        select.dispatchEvent(new Event('input', { bubbles: true }));
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        </script>
    @endpush
</div>
