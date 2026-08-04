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

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 bg-gray-50 uppercase">
                <tr>
                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('id')">
                        ID
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('hospital_name')">
                        Hospital
                        <span
                            class="{{ $sortField === 'hospital_name' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'hospital_name' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('nombre_paciente')">
                        Paciente
                        <span
                            class="{{ $sortField === 'nombre_paciente' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'nombre_paciente' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('created_at')">
                        Fecha y hora de solicitud
                        <span class="{{ $sortField === 'created_at' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('fecha_entrega')">
                        Fecha y hora programada de entrega
                        <span
                            class="{{ $sortField === 'fecha_entrega' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'fecha_entrega' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center cursor-pointer" wire:click="sortBy('estado')">
                        Estado operativo
                        <span class="{{ $sortField === 'estado' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'estado' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-6 py-3">
                        Acciones
                    </th>

                    <th class="px-6 py-3 cursor-pointer" wire:click="sortBy('remision')">
                        RemisiÃ³n
                        <span class="{{ $sortField === 'remision' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'remision' ? ($sortDirection === 'asc' ? 'â–²' : 'â–¼') : 'â†•' !!}
                        </span>
                    </th>

                    <th class="px-2 py-2 text-center whitespace-nowrap">
                        Lote
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
                    <tr class="border-b">
                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->id }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->hospital->name ?? 'N/A' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->nombre_paciente }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $solicitud->created_at?->timezone('America/Mexico_City')->format('Y-m-d H:i') ?? 'â€”' }}
                        </td>

                        <td class="px-6 py-4">
                            @if ($solicitud->fecha_entrega)
                                {{ \Carbon\Carbon::parse($solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i') }}
                            @else
                                â€”
                            @endif
                        </td>

                        <td class="px-2 py-2 text-center">
                            @if ($solicitud->fecha_entrega)
                                {{ \Carbon\Carbon::parse($solicitud->fecha_entrega)->timezone('America/Mexico_City')->format('Y-m-d H:i') }}
                            @else
                                &mdash;
                            @endif
                        </td>

                        <td class="px-2 py-2 text-center">
                            @php
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

                                $estadoLabels = [
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

                            <span
                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $estadoClasses[$solicitud->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $estadoLabels[$solicitud->estado] ?? ucfirst($solicitud->estado) }}
                            </span>
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->remision ?? '-' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            @php
                                $lotes = $solicitud->mezclas
                                    ->pluck('lote')
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->join(', ');
                            @endphp

                            {{ $lotes !== '' ? $lotes : '-' }}
                        </td>

                        <td class="px-2 py-2 text-center">
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

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Solicitud Completa
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.oncologicos.mezclas.envio', $solicitud) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Registros de Envio
                            </a>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('admin.oncologicos.mezclas.remision', $solicitud) }}"
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
