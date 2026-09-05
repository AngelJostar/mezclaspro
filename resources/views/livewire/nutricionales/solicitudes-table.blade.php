<div>
    <div class="overflow-x-auto" data-sticky-x-position="viewport">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 bg-gray-50 uppercase">
                @include('admin.solicitudes._table-header', [
                    'sortFields' => [
                        'id' => 'id',
                        'request_id' => 'request_id',
                        'hospital' => 'user_id',
                        'requested_at' => 'created_at',
                        'delivery_at' => 'solicitud_details.fecha_hora_entrega',
                        'status' => 'estado',
                        'lot' => 'lote',
                    ],
                    'tableSortField' => $sortField,
                    'tableSortDirection' => $sortDirection,
                ])
            </thead>

            <tbody>
                @foreach ($solicitudes as $solicitud)
                    @php
                        $estado = str_replace('-', '_', mb_strtolower(trim((string) ($solicitud->estado ?? 'pendiente'))));
                        $requestListUrl = route('admin.nutricionales.solicitudes.index');
                        $approvalUrl = $estado === 'pendiente'
                            ? route('admin.nutricionales.solicitudes.edit', [
                                'solicitud' => $solicitud,
                                'approval' => 1,
                                'approval_popup' => 1,
                                'return_to' => $requestListUrl,
                            ])
                            : null;
                        $approvalStateLabel = match (true) {
                            in_array($estado, ['aprobada', 'dispensada', 'preparada', 'revisada', 'entregada'], true) => 'Aprobada',
                            in_array($estado, ['cancelada', 'no_aprobada'], true) => 'Rechazada',
                            default => 'Sin acción',
                        };
                    @endphp

                    <tr class="border-b" data-mixture-context="{{ \App\Support\MixtureWorkflowContext::label($solicitud->id, $solicitud->user?->hospital) }}">
                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @include('admin.solicitudes._type-badge', ['type' => 'nutricionales'])
                        </td>

                        <td class="px-2 py-2 text-center">{{ $solicitud->id }}</td>

                        <td class="px-2 py-2 text-center">{{ $solicitud->id }}</td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->user->hospital->name ?? 'N/A' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->solicitud_patient->nombre_paciente ?? '' }}
                            {{ $solicitud->solicitud_patient->apellidos_paciente ?? '' }}
                        </td>

                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] px-2 py-2 text-center whitespace-nowrap">
                            {{ optional($solicitud->created_at)->format('Y-m-d H:i') }}
                        </td>

                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] px-2 py-2 text-center whitespace-nowrap">
                            {{ $solicitud->solicitud_detail?->fecha_hora_entrega
                                ? \Carbon\Carbon::parse($solicitud->solicitud_detail->fecha_hora_entrega)->format('Y-m-d H:i')
                                : '—' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            @include('admin.solicitudes._status-badge', ['status' => $estado])
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->lote ?? '' }}
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.show', $solicitud) }}"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Ver
                            </a>
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @hasanyrole('Admin|Super Admin')
                                @if ($approvalUrl)
                                    <a href="{{ $approvalUrl }}"
                                        data-approval-popup="approval-nutricionales-{{ $solicitud->id }}"
                                        class="inline-flex items-center justify-center rounded-full bg-amber-400 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-200">
                                        Aprobar
                                    </a>
                                @else
                                    <button type="button" disabled
                                        @class([
                                            'inline-flex cursor-not-allowed items-center justify-center rounded-full px-3 py-2 text-xs font-semibold',
                                            'bg-green-600 text-white' => $approvalStateLabel === 'Aprobada',
                                            'bg-gray-300 text-gray-500 opacity-80' => $approvalStateLabel !== 'Aprobada',
                                        ])>
                                        {{ $approvalStateLabel }}
                                    </button>
                                @endif
                            @else
                                <button type="button" disabled
                                    @class([
                                        'inline-flex cursor-not-allowed items-center justify-center rounded-full px-3 py-2 text-xs font-semibold',
                                        'bg-green-600 text-white' => $approvalStateLabel === 'Aprobada',
                                        'bg-gray-300 text-gray-500 opacity-80' => $approvalStateLabel !== 'Aprobada',
                                    ])>
                                    {{ $approvalStateLabel }}
                                </button>
                            @endhasanyrole
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @hasanyrole('Admin|Super Admin')
                                @if ($estado === 'aprobada')
                                    <form method="POST"
                                        action="{{ route('admin.nutricionales.solicitudes.preparar', $solicitud) }}"
                                        class="inline-block"
                                        data-request-process-form
                                        data-confirm-title="¿Marcar solicitud como preparada?"
                                        data-confirm-text="La solicitud pasará al estado PREPARADA."
                                        data-confirm-button="Sí, preparar">
                                        @csrf

                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-full bg-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300">
                                            Preparada
                                        </button>
                                    </form>
                                @elseif ($estado === 'preparada')
                                    <button type="button"
                                        wire:click="$dispatch('abrir-modal-inspeccion-nutricional', { solicitudId: {{ $solicitud->id }} })"
                                        class="inline-flex items-center justify-center rounded-full bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
                                        Inspeccionar
                                    </button>
                                @elseif ($estado === 'revisada')
                                    <form method="POST"
                                        action="{{ route('admin.nutricionales.solicitudes.entregar', $solicitud) }}"
                                        class="inline-block"
                                        data-request-process-form
                                        data-confirm-title="¿Marcar solicitud como entregada?"
                                        data-confirm-text="La solicitud quedará como ENTREGADA."
                                        data-confirm-icon="success"
                                        data-confirm-color="#374151"
                                        data-confirm-button="Sí, entregar">
                                        @csrf

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

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.solicitud', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Solicitud completa
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.inspeccion', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Inspección
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.etiqueta', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Etiqueta
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.ordenPreparacion', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Orden de preparación
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.envio', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Registros de envío
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.remision', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Remisión
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <button type="button" disabled
                                class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                Remision Subdistribuidor
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $solicitudes->links() }}
        </div>
    </div>
</div>
