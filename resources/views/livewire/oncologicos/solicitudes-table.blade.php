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

    <div id="oncology-requests-table-scroll" class="overflow-x-auto">
        <table id="oncology-requests-table" class="w-full min-w-max text-xs text-left text-gray-500">
            <thead class="whitespace-nowrap text-[11px] text-gray-700 bg-gray-50 uppercase">
                <tr>
                    <x-filterable-table-header column="0" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[64px] cursor-pointer" wire:click="sortBy('id')">
                        ID
                        <span class="{{ $sortField === 'id' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="1" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[220px] cursor-pointer" wire:click="sortBy('hospital_name')">
                        Hospital
                        <span
                            class="{{ $sortField === 'hospital_name' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'hospital_name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="2" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[250px] cursor-pointer" wire:click="sortBy('nombre_paciente')">
                        Paciente
                        <span
                            class="{{ $sortField === 'nombre_paciente' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'nombre_paciente' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="3" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[185px] cursor-pointer" wire:click="sortBy('created_at')">
                        Fecha y hora de solicitud
                        <span class="{{ $sortField === 'created_at' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="4" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[225px] cursor-pointer" wire:click="sortBy('fecha_entrega')">
                        Fecha y hora programada de entrega
                        <span
                            class="{{ $sortField === 'fecha_entrega' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'fecha_entrega' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="5" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[150px] cursor-pointer" wire:click="sortBy('estado')">
                        Estado operativo
                        <span class="{{ $sortField === 'estado' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'estado' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="6" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[105px] cursor-pointer" wire:click="sortBy('remision')">
                        Remision
                        <span class="{{ $sortField === 'remision' ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {!! $sortField === 'remision' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' !!}
                        </span>
                    </x-filterable-table-header>

                    <x-filterable-table-header column="7" trigger-class="js-oncology-request-column-filter"
                        align="center" class="min-w-[150px]">
                        Lote
                    </x-filterable-table-header>

                    <th class="px-2 py-2 text-center whitespace-nowrap">Ver</th>
                    <th class="px-2 py-2 text-center whitespace-nowrap">Cancelar</th>
                    <th class="px-2 py-2 text-center whitespace-nowrap">No aprobar</th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Solicitud Completa
                    </th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Registros de Envio
                    </th>

                    <th class="px-4 py-2 text-center whitespace-nowrap">
                        Remision
                    </th>

                    <th class="min-w-[190px] px-4 py-2 text-center whitespace-nowrap">
                        Remision Subdistribuidor
                    </th>
                </tr>
            </thead>

            <tbody class="whitespace-nowrap text-xs">
                @foreach ($solicitudes as $solicitud)
                    <tr class="js-oncology-request-filter-row border-b">
                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->id }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->hospital->name ?? 'N/A' }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            {{ $solicitud->nombre_paciente }}
                        </td>

                        <td class="px-2 py-2 text-center">
                            @if ($solicitud->created_at)
                                {{ $solicitud->created_at->timezone('America/Mexico_City')->format('Y-m-d H:i') }}
                            @else
                                &mdash;
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
                            <x-operational-status-badge :status="$solicitud->estado" />
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

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            <x-table-action-link href="{{ route('admin.oncologicos.solicitudes.edit', $solicitud->id) }}" icon="fa-solid fa-eye">
                                Ver
                            </x-table-action-link>
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @hasanyrole('Cliente|Institucion')
                                @if ($solicitud->estado === 'pendiente')
                                    <form method="POST"
                                        action="{{ route('admin.oncologicos.solicitudes.cancelar', $solicitud) }}"
                                        class="form-confirmar-cancelar">
                                        @csrf

                                        <x-table-action-button type="submit" variant="red">
                                            Cancelar
                                        </x-table-action-button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endhasanyrole
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @hasanyrole('Admin|Super Admin')
                                @if (in_array($solicitud->estado, ['pendiente', 'aprobada', 'preparada', 'revisada'], true))
                                    <form method="POST"
                                        action="{{ route('admin.oncologicos.solicitudes.cancelar', $solicitud) }}"
                                        class="form-confirmar-cancelar">
                                        @csrf

                                        <x-table-action-button type="submit" variant="red">
                                            No aprobar
                                        </x-table-action-button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endhasanyrole
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

                        <td @class([
                            'px-4 py-2 text-center whitespace-nowrap',
                            'bg-gray-50' => !$solicitud->has_subdistributor_remission,
                        ])>
                            @if ($solicitud->has_subdistributor_remission)
                                <a href="{{ route('admin.oncologicos.mezclas.remision', ['solicitud' => $solicitud, 'subdistribuidor' => 1]) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                    Remision
                                </a>
                            @else
                                <button type="button" disabled aria-disabled="true"
                                    title="El hospital no tiene una lista de precios con subdistribuidor"
                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500">
                                    Remision
                                </button>
                            @endif
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
