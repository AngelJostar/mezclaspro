<div>
    <div class="overflow-x-auto" data-sticky-x-position="viewport">
        <table class="w-full text-left text-sm text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                @include('admin.solicitudes._table-header', [
                    'sortFields' => [
                        'id' => 'id',
                        'request_id' => 'request_id',
                        'hospital' => 'hospital_name',
                        'patient' => 'nombre_paciente',
                        'requested_at' => 'created_at',
                        'delivery_at' => 'fecha_entrega',
                        'status' => 'estado',
                        'lot' => 'lote',
                    ],
                    'tableSortField' => $sortField,
                    'tableSortDirection' => $sortDirection,
                ])
            </thead>

            <tbody>
                @forelse ($mezclas as $mezcla)
                    @php
                        $solicitud = $mezcla->solicitud;
                        $estado = $mezcla->operational_status;
                    @endphp

                    <tr class="border-b">
                        <td class="whitespace-nowrap px-2 py-2 text-center">
                            @include('admin.solicitudes._type-badge', ['type' => $solicitud->tipo_solicitud])
                        </td>

                        <td class="px-2 py-2 text-center">{{ $mezcla->id }}</td>
                        <td class="px-2 py-2 text-center">{{ $solicitud->id }}</td>
                        <td class="px-2 py-2 text-center">{{ $solicitud->hospital->name ?? 'N/A' }}</td>
                        <td class="px-2 py-2 text-center">{{ $solicitud->nombre_paciente }}</td>

                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] whitespace-nowrap px-2 py-2 text-center">
                            {{ $solicitud->created_at?->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? '—' }}
                        </td>

                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] whitespace-nowrap px-2 py-2 text-center">
                            {{ ($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)
                                ? \Carbon\Carbon::parse($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i')
                                : '—' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            @include('admin.solicitudes._status-badge', ['status' => $estado])
                        </td>
                        <td class="px-2 py-2 text-center">{{ $mezcla->lote ?? '—' }}</td>

                        <td class="whitespace-nowrap px-2 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.show', $mezcla) }}"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Ver
                            </a>
                        </td>

                        <td class="whitespace-nowrap px-2 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Ver
                            </a>
                        </td>

                        <td class="whitespace-nowrap px-2 py-2 text-center">
                            @hasanyrole('Admin|Super Admin')
                                @if ($estado === 'pendiente')
                                    <a href="{{ route('admin.oncologicos.mezclas.edit', $mezcla) }}"
                                        class="inline-flex items-center justify-center rounded-full bg-amber-400 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-200">
                                        Aprobar
                                    </a>
                                @elseif (in_array($estado, ['aprobada', 'preparada', 'revisada'], true))
                                    <a href="{{ route('admin.oncologicos.mezclas.edit', $mezcla) }}"
                                        class="inline-flex items-center justify-center rounded-full bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                                        Editar
                                    </a>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                        Editar
                                    </button>
                                @endif
                            @else
                                <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                    Editar
                                </button>
                            @endhasanyrole
                        </td>

                        <td class="whitespace-nowrap px-2 py-2 text-center">
                            @hasanyrole('Admin|Super Admin')
                                @if ($estado === 'aprobada')
                                    <form method="POST" action="{{ route('admin.oncologicos.mezclas.update', $mezcla) }}"
                                        class="inline-block" data-request-process-form
                                        data-confirm-title="¿Marcar mezcla como preparada?"
                                        data-confirm-text="La mezcla pasará al estado PREPARADA."
                                        data-confirm-button="Sí, preparar">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="accion" value="preparada">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-full bg-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300">
                                            Preparada
                                        </button>
                                    </form>
                                @elseif ($estado === 'preparada')
                                    <button type="button"
                                        onclick="window.dispatchEvent(new CustomEvent('abrir-modal-inspeccion', { detail: [{{ $mezcla->id }}] }))"
                                        class="inline-flex items-center justify-center rounded-full bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
                                        Inspeccionar
                                    </button>
                                @elseif ($estado === 'revisada')
                                    <form method="POST" action="{{ route('admin.oncologicos.mezclas.update', $mezcla) }}"
                                        class="inline-block" data-request-process-form
                                        data-confirm-title="¿Marcar mezcla como entregada?"
                                        data-confirm-text="La mezcla quedará como ENTREGADA."
                                        data-confirm-icon="success" data-confirm-color="#374151"
                                        data-confirm-button="Sí, entregar">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="accion" value="entregada">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                                            Entregar
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                        Proceso
                                    </button>
                                @endif
                            @else
                                <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                    Proceso
                                </button>
                            @endhasanyrole
                        </td>

                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Solicitud completa
                            </a>
                        </td>

                        @foreach ([
                            ['label' => 'Inspección', 'route' => 'admin.oncologicos.mezclas.inspeccion'],
                            ['label' => 'Etiqueta', 'route' => 'admin.oncologicos.mezclas.etiqueta'],
                            ['label' => 'Orden de preparación', 'route' => 'admin.oncologicos.mezclas.ordenPreparacion'],
                        ] as $documentAction)
                            <td class="whitespace-nowrap px-4 py-2 text-center">
                                <a href="{{ route($documentAction['route'], $mezcla) }}" target="_blank" rel="noopener"
                                    title="{{ $documentAction['label'] }} de la mezcla #{{ $mezcla->id }}"
                                    class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                    {{ $documentAction['label'] }}
                                </a>
                            </td>
                        @endforeach

                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.envio', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Registros de envío
                            </a>
                        </td>

                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.remision', ['solicitud' => $solicitud, 'mezcla' => $mezcla->id]) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Remisión
                            </a>
                        </td>

                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            <a href="{{ route('admin.oncologicos.mezclas.remision', ['solicitud' => $solicitud, 'mezcla' => $mezcla->id, 'subdistribuidor' => 1]) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Remision Subdistribuidor
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="px-4 py-10 text-center text-sm text-gray-500">
                            No se encontraron mezclas para esta vista.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $mezclas->links() }}
        </div>
    </div>
</div>
