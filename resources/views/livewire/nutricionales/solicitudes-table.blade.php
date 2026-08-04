<div>
    <form wire:submit.prevent="aplicarBusqueda">
        <div class="mb-4 flex items-center gap-2">
            <input type="text" wire:model="buscar" placeholder="Buscar ..."
                class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-1/3 p-2">

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-800">
                Buscar
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 bg-gray-50 uppercase">
                <tr>
                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('id')">
                        ID
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('user_id')">
                        Hospital
                        <span class="{{ $sortField === 'user_id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'user_id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center">Paciente</th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('created_at')">
                        Fecha y hora de solicitud
                        <span class="{{ $sortField === 'created_at' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer"
                        wire:click="sortBy('solicitud_details.fecha_hora_entrega')">
                        Fecha y hora programada de entrega
                        <span
                            class="{{ $sortField === 'solicitud_details.fecha_hora_entrega' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'solicitud_details.fecha_hora_entrega' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('estado')">
                        Estado operativo
                        <span class="{{ $sortField === 'estado' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'estado' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('remision')">
                        Remision

                        <span class="hidden {{ $sortField === 'remision' ? 'font-bold text-blue-700' : 'text-gray-400' }}">

                            {!! $sortField === 'remision' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}

                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('lote')">
                        Lote

                        <span class="{{ $sortField === 'lote' ? 'font-bold text-blue-700' : 'text-gray-400' }}">

                            {!! $sortField === 'lote' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}

                        </span>
                    </th>

                    <th class="px-2 py-2 text-center">Acciones</th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Solicitud Completa
                    </th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Registros de Envio
                    </th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Remision
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($solicitudes as $solicitud)
                    @php
                        $estado = $solicitud->estado ?? 'pendiente';

                        $estadoClasses = [
                            'pendiente' => 'bg-yellow-100 text-yellow-700',
                            'aprobada' => 'bg-green-100 text-green-700',
                            'enproceso' => 'bg-blue-100 text-blue-700',
                            'preparada' => 'bg-blue-100 text-blue-700',
                            'revisada' => 'bg-purple-100 text-purple-700',
                            'finalizada' => 'bg-gray-200 text-gray-700',
                            'entregada' => 'bg-gray-200 text-gray-700',
                            'cancelada' => 'bg-red-100 text-red-700',
                            'no_aprobada' => 'bg-red-200 text-red-800',
                        ];

                        $estadoLabel = [
                            'pendiente' => 'Pendiente',
                            'aprobada' => 'Aprobada',
                            'enproceso' => 'Preparada',
                            'preparada' => 'Preparada',
                            'revisada' => 'inspeccionada',
                            'finalizada' => 'Entregada',
                            'entregada' => 'Entregada',
                            'cancelada' => 'Cancelada',
                            'no_aprobada' => 'No Aprobada',
                        ];
                    @endphp

                    <tr class="border-b">
                        <td class="px-2 py-2 text-center">{{ $solicitud->id }}</td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->user->hospital->name ?? 'N/A' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->solicitud_patient->nombre_paciente ?? '' }}
                            {{ $solicitud->solicitud_patient->apellidos_paciente ?? '' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ optional($solicitud->created_at)->format('Y-m-d H:i') }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->solicitud_detail?->fecha_hora_entrega
                                ? \Carbon\Carbon::parse($solicitud->solicitud_detail->fecha_hora_entrega)->format('Y-m-d H:i')
                                : '—' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            <span
                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $estadoClasses[$estado] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $estadoLabel[$estado] ?? ucfirst($estado) }}
                            </span>
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->remision ?? '—' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->lote ?? '' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            <x-row-actions>
                                <a href="{{ route('admin.nutricionales.solicitudes.show', $solicitud) }}"
                                    class="">
                                    <i class="fa-solid fa-eye pr-1"></i> Ver
                                </a>

                                @hasanyrole('Admin|Super Admin')
                                    @if (in_array($estado, ['pendiente'], true))
                                        <a href="{{ route('admin.nutricionales.solicitudes.edit', $solicitud) }}"
                                            class="">
                                            <i class="fa-solid fa-pen pr-1"></i> Aprobar
                                        </a>
                                    @elseif (in_array($estado, ['aprobada', 'preparada', 'revisada'], true))
                                        <a href="{{ route('admin.nutricionales.solicitudes.edit', $solicitud) }}"
                                            class="">
                                            <i class="fa-solid fa-pen pr-1"></i> Editar
                                        </a>
                                    @endif

                                    @if ($estado === 'aprobada')
                                        <form method="POST"
                                            action="{{ route('admin.nutricionales.solicitudes.preparar', $solicitud) }}"
                                            class="form-confirmar-preparar">
                                            @csrf

                                            <button type="submit">
                                                Preparada
                                            </button>
                                        </form>
                                    @endif

                                    @if ($estado === 'preparada')
                                        <button type="button"
                                            wire:click="$dispatch('abrir-modal-inspeccion-nutricional', { solicitudId: {{ $solicitud->id }} })">
                                            Inspeccionar
                                        </button>
                                    @endif

                                    @if ($estado === 'revisada')
                                        <form method="POST"
                                            action="{{ route('admin.nutricionales.solicitudes.entregar', $solicitud) }}"
                                            class="form-confirmar-entregar">
                                            @csrf

                                            <button type="submit">
                                                Entregar
                                            </button>
                                        </form>
                                    @endif

                                    {{-- ADMIN / SUPER ADMIN --}}
                                    @hasanyrole('Admin|Super Admin')
                                        @if (in_array($estado, ['pendiente', 'aprobada', 'preparada', 'revisada'], true))
                                            <form method="POST"
                                                action="{{ route('admin.nutricionales.solicitudes.cancelar', $solicitud) }}"
                                                class="form-confirmar-cancelar">
                                                @csrf

                                                <button type="submit" class="action-danger">
                                                    No aprobar
                                                </button>
                                            </form>
                                        @endif
                                    @endhasanyrole
                                @endhasanyrole
                                @hasanyrole('Cliente|Institucion')
                                    @if ($estado === 'pendiente')
                                        <form method="POST"
                                            action="{{ route('admin.nutricionales.solicitudes.cancelar', $solicitud) }}"
                                            class="form-confirmar-cancelar">
                                            @csrf

                                            <button type="submit" class="action-danger">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                @endhasanyrole
                            </x-row-actions>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.solicitud', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Solicitud Completa
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.envio', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Registros de Envio
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.nutricionales.solicitudes.remision', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Remision
                            </a>
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

@push('js')
    <script>
        function inicializarConfirmacionesSolicitudes() {
            function confirmacionSweet(selector, config) {
                document.querySelectorAll(selector).forEach(form => {
                    if (form.dataset.swalReady === '1') {
                        return;
                    }

                    form.dataset.swalReady = '1';

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: config.title,
                            text: config.text,
                            icon: config.icon,
                            showCancelButton: true,
                            confirmButtonColor: config.confirmButtonColor,
                            cancelButtonColor: '#9CA3AF',
                            confirmButtonText: config.confirmButtonText,
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                            background: '#ffffff',
                            customClass: {
                                popup: 'rounded-2xl shadow-2xl',
                                confirmButton: 'px-5 py-2 rounded-lg',
                                cancelButton: 'px-5 py-2 rounded-lg'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            }

            confirmacionSweet('.form-confirmar-preparar', {
                title: '¿Marcar solicitud como preparada?',
                text: 'La solicitud pasará al estado PREPARADA.',
                icon: 'question',
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Sí, preparar'
            });

            confirmacionSweet('.form-confirmar-revisar', {
                title: '¿Marcar solicitud como revisada?',
                text: 'La solicitud pasará al estado REVISADA.',
                icon: 'question',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Sí, revisar'
            });

            confirmacionSweet('.form-confirmar-entregar', {
                title: '¿Marcar solicitud como entregada?',
                text: 'La solicitud quedará como ENTREGADA.',
                icon: 'success',
                confirmButtonColor: '#374151',
                confirmButtonText: 'Sí, entregar'
            });

            confirmacionSweet('.form-confirmar-cancelar', {
                title: '¿Cancelar solicitud?',
                text: 'Si ya se descontó inventario, será devuelto automáticamente.',
                icon: 'warning',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sí, cancelar'
            });
        }

        document.addEventListener('DOMContentLoaded', inicializarConfirmacionesSolicitudes);
        document.addEventListener('livewire:navigated', inicializarConfirmacionesSolicitudes);
        document.addEventListener('livewire:update', inicializarConfirmacionesSolicitudes);
    </script>
@endpush
