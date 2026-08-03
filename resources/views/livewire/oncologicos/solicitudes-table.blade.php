<div>
    <form wire:submit.prevent="aplicarBusqueda">
        <div class="mb-4 flex items-center gap-2">
            <input type="text" wire:model.defer="buscar" placeholder="Buscar ..."
                class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-1/3 p-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-800">
                Buscar
            </button>
        </div>
    </form>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('id')">
                        No. Solicitud
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('hospital_name')">
                        Hospital
                        <span
                            class="{{ $sortField === 'hospital_name' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'hospital_name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('nombre_paciente')">
                        Paciente
                        <span
                            class="{{ $sortField === 'nombre_paciente' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'nombre_paciente' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('created_at')">
                        Fecha y Hora de Solicitud
                        <span class="{{ $sortField === 'created_at' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('fecha_entrega')">
                        Fecha y Hora de Entrega
                        <span
                            class="{{ $sortField === 'fecha_entrega' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'fecha_entrega' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('estado')">
                        Estado
                        <span class="{{ $sortField === 'estado' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'estado' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3">
                        Acciones
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('remision')">
                        Remisión
                        <span class="{{ $sortField === 'remision' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'remision' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($solicitudes as $solicitud)
                    <tr @class([
                        'border-b bg-white dark:bg-gray-800 dark:border-gray-700',
                        'font-bold' => $solicitud->estado === 'finalizada',
                        'font-semibold' => $solicitud->estado === 'enproceso',
                    ])>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $solicitud->id }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $solicitud->hospital->name ?? 'N/A' }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $solicitud->nombre_paciente }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $solicitud->created_at?->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if ($solicitud->fecha_entrega)
                                {{ \Carbon\Carbon::parse($solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i') }}
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @php
                                $estadoClasses = [
                                    'pendiente' => 'bg-yellow-100 text-yellow-700',
                                    'enproceso' => 'bg-blue-100 text-blue-700',
                                    'finalizada' => 'bg-green-100 text-green-700',
                                    'cancelada' => 'bg-red-100 text-red-700',
                                    'no_aprobada' => 'bg-red-100 text-red-700',
                                ];

                                $estadoLabels = [
                                    'pendiente' => 'Pendiente',
                                    'enproceso' => 'En proceso',
                                    'finalizada' => 'Finalizada',
                                    'cancelada' => 'Cancelada',
                                    'no_aprobada' => 'No aprobada',
                                ];
                            @endphp

                            <span
                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $estadoClasses[$solicitud->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $estadoLabels[$solicitud->estado] ?? ucfirst($solicitud->estado) }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <x-row-actions>
                                <a href="{{ route('admin.oncologicos.mezclas.index', $solicitud->id) }}"
                                    class="">
                                    Ver
                                </a>

                                @hasanyrole('Cliente|Institucion')
                                    @if ($solicitud->estado === 'pendiente')
                                        <form method="POST"
                                            action="{{ route('admin.oncologicos.solicitudes.cancelar', $solicitud) }}"
                                            class="form-confirmar-cancelar">
                                            @csrf

                                            <button type="submit" class="action-danger">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                @endhasanyrole

                                @hasanyrole('Admin|Super Admin')
                                    @if (in_array($solicitud->estado, ['pendiente', 'enproceso'], true))
                                        <form method="POST"
                                            action="{{ route('admin.oncologicos.solicitudes.cancelar', $solicitud) }}"
                                            class="form-confirmar-cancelar">
                                            @csrf

                                            <button type="submit" class="action-danger">
                                                No aprobar
                                            </button>
                                        </form>
                                    @endif
                                @endhasanyrole
                            </x-row-actions>
                        </td>

                        <td class="px-6 py-4">
                            {{ $solicitud->remision ?? '—' }}
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
