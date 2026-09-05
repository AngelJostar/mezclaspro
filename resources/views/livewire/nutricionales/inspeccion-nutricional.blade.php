<div>
    @if ($mostrarModalInspeccion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-y-auto p-6">

                <div class="flex items-start justify-between border-b pb-4 mb-6">
                    <div>
                        <h2 class="mixture-workflow-heading">
                            Inspección nutricional | {{ $mixtureContext }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Verificacion fisica del contenedor, contenido y liberacion de la solicitud nutricional.
                        </p>
                    </div>

                    <button type="button"
                        wire:click="$set('mostrarModalInspeccion', false)"
                        aria-label="Cerrar inspección"
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
                                    Inspeccion del contenedor y contenido
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

                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Datos de inspeccion
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
                                    Peso de la nutricion (g)
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Inspecciono
                                    </label>

                                    <input type="text" wire:model.defer="reviso_nombre"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                        readonly>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Aprobo
                                    </label>

                                    <input type="text" wire:model.defer="aprobo_nombre"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                        readonly>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                                Al guardar la inspeccion, la solicitud pasara automaticamente al estado
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
                        Guardar inspeccion
                    </button>
                </div>
            </div>
        </div>
    @endif

    @push('js')
        <script>
            function marcarDefaultNutricional() {
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
