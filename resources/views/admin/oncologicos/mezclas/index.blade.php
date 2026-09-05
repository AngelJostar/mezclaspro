<x-admin-layout>
    @php
        $requestListRoute = $solicitud->tipo_solicitud === 'antibioticos'
            ? 'admin.antibioticos.solicitudes.index'
            : 'admin.oncologicos.solicitudes.index';
    @endphp
    <div class="mt-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Detalle de Solicitud #{{ $solicitud->id }}</h1>
        <div class="flex">
            <div class="mt-4">
                <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                    href="{{ route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                    target="_blank">Solicitud Completa</a>
            </div>
            <div class="mt-4">
                <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                    href="{{ route('admin.oncologicos.mezclas.envio', $solicitud) }}" target="_blank">Registros de
                    Envio</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-white shadow rounded-lg p-6">
        <div>
            <p class="text-gray-700"><span class="font-semibold text-gray-900">Hospital:</span>
                {{ $solicitud->hospital->name ?? 'N/A' }}</p>
            <p class="text-gray-700"><span class="font-semibold text-gray-900">Paciente:</span>
                {{ $solicitud->nombre_paciente }}</p>
        </div>
        <div>
            <p class="text-gray-700">
                <span class="font-semibold text-gray-900">Fecha de Solicitud:</span>
                {{ optional($solicitud->created_at)->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? '—' }}
            </p>
            <p class="text-gray-700">
                <span class="font-semibold text-gray-900">Primera fecha de entrega:</span>
                {{ $solicitud->fecha_entrega
                    ? \Carbon\Carbon::parse($solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i')
                    : '—' }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.oncologicos.solicitudes.edit', $solicitud->id) }}"
                class="inline-block px-6 py-3 bg-yellow-500 text-white text-base font-semibold rounded-lg hover:bg-yellow-600 transition duration-200">
                Ver Solicitud Completa
            </a>

        </div>

    </div>

    <div class="relative overflow-x-auto rounded-lg shadow">
        <table class="w-full text-sm text-left text-gray-700 bg-white">
            <thead class="text-xs uppercase bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-6 py-3">No. Mezcla</th>
                    <th class="px-6 py-3">Medicamento</th>
                    <th class="px-6 py-3">Dosis y volumen de la mezcla</th>
                    <th class="px-6 py-3">Fecha y hora de solicitud</th>
                    <th class="px-6 py-3">Fecha y hora de entrega</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3 text-center">Ver</th>
                    <th class="px-6 py-3 text-center">Aprobación</th>
                    <th class="px-6 py-3 text-center">Preparada</th>
                    <th class="px-6 py-3 text-center">Inspeccion</th>
                    <th class="px-6 py-3 text-center">Entregada</th>
                    <th class="px-6 py-3">Remisión</th>
                    <th class="px-6 py-3">Lote</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitud->mezclas as $mezcla)
                    @php
                        $estado = str_replace('-', '_', mb_strtolower(trim((string) ($mezcla->estado ?? 'pendiente'))));
                        $requestListUrl = route($requestListRoute);
                        $approvalUrl = $estado === 'pendiente'
                            ? route('admin.oncologicos.mezclas.edit', [
                                'mezcla' => $mezcla->id,
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

                        $medicamentosTexto = $mezcla->medicamentos
                            ->map(function ($medicamento) {
                                return $medicamento->medicamentoOnco->catalog->denominacion
                                    ?? $medicamento->nombre_medicamento
                                    ?? '—';
                            })
                            ->filter()
                            ->implode(', ');

                        $dosisYVolumenTexto = $mezcla->medicamentos
                            ->map(function ($medicamento) use ($mezcla) {
                                if (!is_numeric($medicamento->dosis ?? null)) {
                                    return null;
                                }

                                $dosis = rtrim(rtrim(number_format((float) $medicamento->dosis, 2, '.', ''), '0'), '.');
                                $volumen = is_numeric($mezcla->volumen_dilucion ?? null)
                                    ? rtrim(rtrim(number_format((float) $mezcla->volumen_dilucion, 2, '.', ''), '0'), '.') . ' mL'
                                    : '—';

                                return $dosis . ' mg / ' . $volumen;
                            })
                            ->filter()
                            ->implode(', ');
                    @endphp
                    <tr data-mixture-context="{{ \App\Support\MixtureWorkflowContext::label($mezcla->id, $solicitud->hospital) }}" @class([
                        'border-b dark:bg-gray-800 dark:border-gray-700',
                        'bg-gray-200 text-black' => $mezcla->estado === 'pendiente',
                        'bg-yellow-200 text-black font-semibold' => $mezcla->estado === 'aprobada',
                        'bg-sky-200 text-black font-semibold' => $mezcla->estado === 'dispensada',
                        'bg-cyan-200 text-black font-semibold' => $mezcla->estado === 'preparada',
                        'bg-indigo-200 text-black font-semibold' => $mezcla->estado === 'revisada',
                        'bg-red-200 text-black font-bold' => $mezcla->estado === 'cancelada',
                        'bg-green-200 text-black font-bold' => $mezcla->estado === 'entregada',
                        'bg-white' => !in_array($mezcla->estado, [
                            'pendiente',
                            'aprobada',
                            'dispensada',
                            'preparada',
                            'revisada',
                            'cancelada',
                            'entregada',
                        ]),
                    ])>
                        <td class="px-6 py-4">{{ $mezcla->id }}</td>
                        <td class="px-6 py-4">{{ $medicamentosTexto !== '' ? $medicamentosTexto : '—' }}</td>
                        <td class="px-6 py-4">{{ $dosisYVolumenTexto !== '' ? $dosisYVolumenTexto : '—' }}</td>
                        <td class="px-6 py-4">
                            {{ optional($solicitud->created_at)->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ ($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)
                                ? \Carbon\Carbon::parse($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i')
                                : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ match ($mezcla->estado) {
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    'aprobada' => 'bg-green-100 text-green-800',
                                    'dispensada' => 'bg-sky-100 text-sky-800',
                                    'revisada' => 'bg-indigo-100 text-indigo-800',
                                    'cancelada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ match ($mezcla->estado) {
                                    'revisada' => 'Inspeccionada',
                                    'dispensada' => 'Dispensada',
                                    default => ucfirst($mezcla->estado),
                                } }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.oncologicos.mezclas.show', $mezcla->id) }}" icon="fa-solid fa-eye">
                                Ver
                            </x-table-action-link>
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if ($approvalUrl)
                                <x-table-action-link href="{{ $approvalUrl }}"
                                    data-approval-popup="approval-{{ $solicitud->tipo_solicitud ?? 'oncologicos' }}-{{ $mezcla->id }}"
                                    variant="yellow" icon="fa-solid fa-check">
                                    Aprobar
                                </x-table-action-link>
                            @else
                                <button type="button" disabled
                                    @class([
                                        'inline-flex cursor-not-allowed items-center justify-center whitespace-nowrap rounded-full px-3 py-2 text-xs font-semibold',
                                        'bg-green-600 text-white' => $approvalStateLabel === 'Aprobada',
                                        'bg-gray-300 text-gray-500 opacity-80' => $approvalStateLabel !== 'Aprobada',
                                    ])>
                                    {{ $approvalStateLabel }}
                                </button>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if ($mezcla->estado === 'aprobada')
                                <x-table-action-link
                                    href="{{ route('admin.oncologicos.mezclas.edit', [
                                        'mezcla' => $mezcla->id,
                                        'modo' => 'dispensacion',
                                        'dispensing_popup' => 1,
                                        'return_to' => $requestListUrl,
                                    ]) }}"
                                    data-dispensing-popup="dispensing-{{ $solicitud->tipo_solicitud ?? 'oncologicos' }}-{{ $mezcla->id }}"
                                    icon="fa-solid fa-box-open">
                                    Dispensar
                                </x-table-action-link>
                            @elseif ($mezcla->estado === 'dispensada')
                                <form id="formPreparar-{{ $mezcla->id }}"
                                    action="{{ route('admin.oncologicos.mezclas.update', $mezcla->id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="return_to" value="{{ $requestListUrl }}">
                                    <input type="hidden" name="accion" value="preparada">
                                    <x-table-action-button onclick="confirmarPreparada({{ $mezcla->id }})" variant="green">
                                        Preparar
                                    </x-table-action-button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if ($mezcla->estado === 'preparada')
                                <x-table-action-button
                                    onclick="window.dispatchEvent(new CustomEvent('abrir-modal-inspeccion', { detail: [{{ $mezcla->id }}] }))">
                                    Inspección
                                </x-table-action-button>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if ($mezcla->estado === 'revisada')
                                <form id="formEntregar-{{ $mezcla->id }}"
                                    action="{{ route('admin.oncologicos.mezclas.update', $mezcla->id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="return_to" value="{{ $requestListUrl }}">
                                    <input type="hidden" name="accion" value="entregada">
                                    <x-table-action-button onclick="confirmarEntregada({{ $mezcla->id }})" variant="green">
                                        Entregada
                                    </x-table-action-button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($mezcla->remision)
                                <x-table-action-link
                                    href="{{ route('admin.oncologicos.mezclas.remision', ['solicitud' => $solicitud, 'mezcla' => $mezcla]) }}"
                                    target="_blank">
                                    Remisión {{ $mezcla->remision }}
                                </x-table-action-link>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $mezcla->lote ?? '—' }}</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="px-6 py-4 text-center text-gray-500">No hay mezclas registradas.</td>
                    </tr>
                @endforelse


            </tbody>
        </table>
        @livewire('oncologicos.inspeccion-mezcla')
    </div>

    <div class="mt-6">
        <a href="{{ route($requestListRoute) }}"
            class="inline-block px-4 py-2 text-sm text-blue-600 hover:underline">
            &laquo; Volver a listado
        </a>
    </div>

    @push('js')
        <script>
            function confirmarPreparada(id) {
                Swal.fire({
                    width: 880,
                    didOpen: (popup) => window.showMixtureConfirmationContext(popup, document.getElementById('formPreparar-' + id)),
                    title: 'Marcar como preparada?',
                    text: '',
                    icon: 'warning',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    },
                    confirmButtonText: 'Si',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formPreparar-' + id).submit();
                    }
                });
            }

            function confirmarEntregada(id) {
                Swal.fire({
                    width: 880,
                    didOpen: (popup) => window.showMixtureConfirmationContext(popup, document.getElementById('formEntregar-' + id)),
                    title: '¿Confirmar entrega?',
                    text: 'Esta mezcla será marcada como entregada.',
                    icon: 'question',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    },
                    confirmButtonText: 'Sí, entregar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formEntregar-' + id).submit();
                    }
                });
            }
        </script>
    @endpush

</x-admin-layout>
