<div>
    @if ($mostrarModalInspeccion && ! $mostrarModalRechazo)
        <div wire:key="inspection-form" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-3 sm:p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="inspection-review-title"
                x-data x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.closeInspection.focus())"
                @keydown.escape.prevent.stop="$wire.set('mostrarModalInspeccion', false)"
                class="inspection-review bg-white rounded-lg shadow-lg w-full max-w-6xl">

                <header class="inspection-review-header flex items-start justify-between gap-4 border-b">
                    <div class="min-w-0">
                        <h2 id="inspection-review-title">Inspección de mezcla #{{ $mezclaId }}</h2>
                        <p class="mt-1 break-words text-xs text-gray-600">{{ $inspectionSummary['destination'] ?? '' }}</p>
                    </div>
                    <button type="button" wire:click="$set('mostrarModalInspeccion', false)"
                        x-ref="closeInspection"
                        aria-label="Cerrar inspección" title="Cerrar inspección"
                        class="inline-flex shrink-0 items-center justify-center w-8 h-8 border border-red-600 rounded text-red-600 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                        <span aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshInspectionIcons?.($el))">
                            <i data-inspection-icon="x" class="h-4 w-4 not-italic text-xl leading-none">&times;</i>
                        </span>
                    </button>
                </header>

                <div class="inspection-review-body">
                @error('mezclaId')
                    <p role="alert" class="mb-4 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <section class="inspection-context-band" aria-labelledby="inspection-patient-title">
                    <h3 id="inspection-patient-title" class="inspection-context-title">
                        <span wire:ignore aria-hidden="true" x-init="$nextTick(() => window.refreshInspectionIcons?.($el))"><i data-inspection-icon="user-round"></i></span>
                        Datos del paciente
                    </h3>
                    <dl class="inspection-patient-grid">
                        @foreach (['name' => 'Paciente', 'record' => 'Expediente', 'sex' => 'Sexo', 'age' => 'Edad', 'weight' => 'Peso'] as $key => $label)
                            <div><dt>{{ $label }}</dt><dd>{{ $inspectionSummary['patient'][$key] ?? '—' }}</dd></div>
                        @endforeach
                    </dl>
                    <dl class="inspection-care-grid">
                        @foreach (['service' => 'Servicio', 'location' => 'Piso / Cama', 'doctor' => 'Médico tratante'] as $key => $label)
                            <div><dt>{{ $label }}</dt><dd>{{ $inspectionSummary['patient'][$key] ?? '—' }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                <section class="inspection-context-band" aria-labelledby="inspection-mixture-title">
                    <h3 id="inspection-mixture-title" class="inspection-context-title">
                        <span wire:ignore aria-hidden="true" x-init="$nextTick(() => window.refreshInspectionIcons?.($el))"><i data-inspection-icon="syringe"></i></span>
                        Información de la mezcla
                    </h3>
                    <dl class="inspection-mixture-meta">
                        <div><dt>Tipo:</dt><dd>{{ $inspectionSummary['type'] ?? '—' }}</dd></div>
                        <div><dt>Lote de la mezcla:</dt><dd>{{ $lote_mezcla ?: '—' }}</dd></div>
                    </dl>
                    <div class="overflow-x-auto" data-disable-sticky-x tabindex="0" role="region" aria-label="Detalle de medicamentos">
                        <table class="inspection-summary-table" aria-label="Medicamentos de la mezcla">
                            <thead><tr>
                                <th scope="col">Medicamento</th><th scope="col">Dosis prescrita</th>
                                <th scope="col">Volumen de medicamento</th><th scope="col">Diluyente</th>
                                <th scope="col">Volumen total</th><th scope="col">Vía</th>
                            </tr></thead>
                            <tbody>
                                @forelse ($inspectionSummary['medications'] ?? [] as $medication)
                                    <tr>
                                        <td>{{ $medication['name'] }}</td><td>{{ $medication['dose'] }}</td>
                                        <td>{{ $medication['volume'] }}</td><td>{{ $medication['diluent'] }}</td>
                                        @if ($loop->first)
                                            <td rowspan="{{ count($inspectionSummary['medications']) }}">{{ $inspectionSummary['total_volume'] }}</td>
                                        @endif
                                        <td>{{ $medication['route'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">No hay medicamentos registrados en esta mezcla.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">
                            Verificación general
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <label for="inspection-container" class="block text-sm font-medium text-gray-700">
                                    Tipo de contenedor
                                </label>

                                <select id="inspection-container" wire:model.defer="tipo_contenedor"
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
                                    'esta_rotulado' => '¿Está rotulado?',
                                    'numero_lote' => '¿El número de lote es visible?',
                                    'medicamento' => '¿Incluye el medicamento correcto?',
                                    'dosis_volumen_total' => '¿La dosis y el volumen total son correctos?',
                                    'volumen_medicamento' => '¿El volumen del medicamento es correcto?',
                                    'rubrica_preparador' => '¿Tiene rúbrica del preparador?',
                                    'sello_seguridad' => '¿Tiene sello de seguridad?',
                                    'presenta_fugas' => '¿Presenta fugas?',
                                    'esta_roto' => '¿Está roto?',
                                    'coloracion_apropiada' => '¿La coloración es apropiada?',
                                    'contenido_homogeneo' => '¿El contenido es homogéneo?',
                                    'presenta_particulas' => '¿Presenta partículas?',
                                    'presenta_turbidez' => '¿Presenta turbidez?',
                                    'aprueba_contenido' => '¿Aprueba la inspección del contenido?',
                                    'aprueba_contenedor' => '¿Aprueba el contenedor?',
                                ] as $field => $label)
                                    <label class="inspection-check-item" wire:key="inspection-check-{{ $field }}">
                                        <span class="inspection-answer">
                                            <input type="checkbox" role="switch" wire:model="{{ $field }}"
                                                aria-labelledby="inspection-question-{{ $field }}">
                                            <span class="inspection-answer-track" aria-hidden="true">
                                                <span class="inspection-answer-yes">Sí</span>
                                                <span class="inspection-answer-no">No</span>
                                            </span>
                                        </span>
                                        <span id="inspection-question-{{ $field }}" class="text-sm text-gray-700">
                                            {{ $label }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="inspection-volume" class="block text-sm font-medium text-gray-700">
                                    Dosis / Volumen (mL)
                                </label>

                                <input id="inspection-volume" type="number"
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
                                <label for="inspection-weight" class="block text-sm font-medium text-gray-700">
                                    Peso de la mezcla (g)
                                </label>

                                <input id="inspection-weight" type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model.defer="peso_mezcla"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">

                                @error('peso_mezcla')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                            <div>
                                <label for="inspection-observations" class="block text-sm font-medium text-gray-700">
                                    Observaciones
                                </label>

                                <textarea id="inspection-observations" wire:model.defer="observaciones"
                                    rows="3"
                                    placeholder="N.A."
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"></textarea>
                                @error('observaciones')
                                    <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="inspection-reviewer" class="block text-sm font-medium text-gray-700">
                                    Inspeccionó
                                </label>

                                <input id="inspection-reviewer" type="text"
                                    wire:model.defer="reviso_nombre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 bg-gray-100"
                                    readonly>
                            </div>

                            <div>
                                <label for="inspection-approver" class="block text-sm font-medium text-gray-700">
                                    Aprobó
                                </label>

                                <p class="mt-1 text-xs text-gray-500">
                                    Solo Responsable sanitario o Auxiliar de responsable sanitario.
                                </p>

                                <select id="inspection-approver" wire:model.defer="aprobo_nombre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                                    <option value="">Seleccione quién aprobó...</option>
                                    @foreach ($aprobadores as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>

                                @error('aprobo_nombre')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <footer class="inspection-review-actions grid grid-cols-3 sm:flex sm:justify-end gap-2 border-t">
                    <button type="button" wire:click="rechazarInspeccion"
                        wire:loading.attr="disabled" wire:target="guardarInspeccion,rechazarInspeccion"
                        class="px-2 sm:px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50">
                        Rechazada
                    </button>
                    <button type="button" wire:click="$set('mostrarModalInspeccion', false)"
                        class="px-2 sm:px-4 py-2 text-sm font-semibold bg-gray-300 text-gray-800 rounded">
                        Cancelar
                    </button>

                    <button type="button" wire:click="guardarInspeccion"
                        wire:loading.attr="disabled" wire:target="guardarInspeccion,rechazarInspeccion"
                        class="px-2 sm:px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                        Aprobada
                    </button>
                </footer>

            </section>
        </div>
    @endif

    @if ($mostrarModalRechazo)
        <div wire:key="inspection-rejection" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <section role="dialog" aria-modal="true" aria-labelledby="inspection-rejection-title"
                x-data x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.reason.focus())"
                @keydown.escape.prevent.stop="$wire.cancelarRechazo()"
                class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-lg bg-white p-6 shadow-lg">
                <h2 id="inspection-rejection-title" class="text-lg font-semibold text-gray-900">Rechazar inspección</h2>
                <p class="mt-2 break-words text-sm text-gray-600">Mezcla #{{ $mezclaId }} · Lote {{ $lote_mezcla }}</p>
                <form wire:submit="guardarRechazo" class="mt-4">
                    <label for="inspection-rejection-reason" class="block text-sm font-medium text-gray-700">Motivo del rechazo</label>
                    <textarea id="inspection-rejection-reason" x-ref="reason" wire:model="motivoRechazo"
                        rows="4" required maxlength="2000" aria-describedby="inspection-rejection-errors"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                    <div id="inspection-rejection-errors" role="alert" class="mt-2 space-y-1 text-sm text-red-600">
                        @foreach ($errors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="cancelarRechazo" wire:loading.attr="disabled" wire:target="guardarRechazo"
                            class="rounded bg-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-400 disabled:opacity-50">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="guardarRechazo"
                            class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700 disabled:opacity-50">Guardar</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($rechazoGuardado)
        <div wire:key="inspection-rejection-success" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <section role="alertdialog" aria-modal="true" aria-labelledby="inspection-success-title" aria-describedby="inspection-success-message"
                x-data x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.ok.focus())"
                class="w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
                <h2 id="inspection-success-title" class="text-xl font-semibold text-green-700">Éxito</h2>
                <p id="inspection-success-message" class="mt-3 text-sm text-gray-700">El rechazo se registró correctamente.</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" x-ref="ok" wire:click="volverASolicitudes" wire:loading.attr="disabled"
                        class="rounded bg-green-600 px-5 py-2 text-white hover:bg-green-700 disabled:opacity-50">OK</button>
                </div>
            </section>
        </div>
    @endif

    @push('js')
        <script>
            window.addEventListener('mezcla-inspeccionada', () => {
                window.location.reload();
            });

        </script>
    @endpush
</div>
