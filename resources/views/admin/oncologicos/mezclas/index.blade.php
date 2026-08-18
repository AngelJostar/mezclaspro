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
            <div class="mt-4">
                <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
                    href="{{ route('admin.oncologicos.mezclas.remision', $solicitud) }}" target="_blank">Remisión</a>
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
                <span class="font-semibold text-gray-900">Fecha de Entrega:</span>
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
                    <th class="px-6 py-3">Hospital</th>
                    <th class="px-6 py-3">Paciente</th>
                    <th class="px-6 py-3">Fecha y hora de solicitud</th>
                    <th class="px-6 py-3">Fecha y hora de entrega</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3 text-center">Ver</th>
                    <th class="px-6 py-3 text-center">Editar</th>
                    <th class="px-6 py-3 text-center">Preparada</th>
                    <th class="px-6 py-3 text-center">Inspeccion</th>
                    <th class="px-6 py-3 text-center">Entregada</th>
                    <th class="px-6 py-3">Remisión</th>
                    <th class="px-6 py-3">Lote</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitud->mezclas as $mezcla)
                    <tr @class([
                        'border-b dark:bg-gray-800 dark:border-gray-700',
                        'bg-gray-200 text-black' => $mezcla->estado === 'pendiente',
                        'bg-yellow-200 text-black font-semibold' => $mezcla->estado === 'aprobada',
                        'bg-cyan-200 text-black font-semibold' => $mezcla->estado === 'preparada',
                        'bg-indigo-200 text-black font-semibold' => $mezcla->estado === 'revisada',
                        'bg-red-200 text-black font-bold' => $mezcla->estado === 'cancelada',
                        'bg-green-200 text-black font-bold' => $mezcla->estado === 'entregada',
                        'bg-white' => !in_array($mezcla->estado, [
                            'pendiente',
                            'aprobada',
                            'preparada',
                            'revisada',
                            'cancelada',
                            'entregada',
                        ]),
                    ])>
                        <td class="px-6 py-4">{{ $mezcla->id }}</td>
                        <td class="px-6 py-4">{{ $solicitud->hospital->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{{ $solicitud->nombre_paciente }}</td>
                        <td class="px-6 py-4">
                            {{ optional($solicitud->created_at)->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $solicitud->fecha_entrega
                                ? \Carbon\Carbon::parse($solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i')
                                : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ match ($mezcla->estado) {
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    'aprobada' => 'bg-green-100 text-green-800',
                                    'cancelada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ ucfirst($mezcla->estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.oncologicos.mezclas.show', $mezcla->id) }}" icon="fa-solid fa-eye">
                                Ver
                            </x-table-action-link>
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.oncologicos.mezclas.edit', $mezcla->id) }}" icon="fa-solid fa-pen">
                                Editar
                            </x-table-action-link>
                        </td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if ($mezcla->estado === 'aprobada')
                                <form id="formPreparar-{{ $mezcla->id }}"
                                    action="{{ route('admin.oncologicos.mezclas.update', $mezcla->id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="accion" value="preparada">
                                    <x-table-action-button onclick="confirmarPreparada({{ $mezcla->id }})" variant="green">
                                        Preparada
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
                                    <input type="hidden" name="accion" value="entregada">
                                    <x-table-action-button onclick="confirmarEntregada({{ $mezcla->id }})" variant="green">
                                        Entregada
                                    </x-table-action-button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $mezcla->remision ?? '—' }}</td>
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
                    title: '¿Estás seguro?',
                    text: '¿Estás seguro de que esta mezcla ya fue preparada?',
                    icon: 'warning',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    },
                    confirmButtonText: 'Sí, marcar como preparada',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formPreparar-' + id).submit();
                    }
                });
            }

            function confirmarEntregada(id) {
                Swal.fire({
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
