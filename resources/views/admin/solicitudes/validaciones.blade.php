<x-admin-layout>
    <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-medium text-gray-800">Validaciones</h1>
            <p class="mt-1 text-xs text-gray-500">Demo: solicitudes rechazadas</p>
            @include('admin.solicitudes._type-selector', [
                'typeSelectorRoute' => 'admin.solicitudes.validaciones.index',
            ])
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 pb-1">
            @if (auth()->user()?->can('nutricionales_solicitudes_create') || auth()->user()?->can('oncologicos_solicitudes_create'))
                <div class="relative" x-data="{ expanded: false }" @keydown.escape.window="expanded = false">
                    <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()"
                        aria-controls="validation-create-options"
                        class="inline-flex items-center rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                        Agregar
                    </button>
                    <div id="validation-create-options" x-cloak x-show="expanded" @click.outside="expanded = false"
                        class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        @can('nutricionales_solicitudes_create')
                            <a href="{{ route('admin.nutricionales.solicitudes.create') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Nutricional</a>
                        @endcan
                        @can('oncologicos_solicitudes_create')
                            <a href="{{ route('admin.oncologicos.solicitudes.create', ['tipo_solicitud' => 'oncologicos']) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Oncológica</a>
                            <a href="{{ route('admin.oncologicos.solicitudes.create', ['tipo_solicitud' => 'antibioticos']) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Antibiótico</a>
                        @endcan
                    </div>
                </div>
            @endif
            <a href="{{ route('admin.solicitudes.validaciones.exportar', ['tipo' => $selectedType, 'estado' => $statusFilter]) }}"
                class="inline-flex items-center rounded-full bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800">
                Exportar a Excel
            </a>
        </div>
    </div>

    @include('admin.solicitudes._status-selector', [
        'activeStatus' => $statusFilter,
        'pendingApprovalCount' => 0,
    ])

    @php
        $validationColumns = [
            ['Categoría'], ['ID mezcla'], ['No. solicitud'], ['Hospital'], ['Paciente'],
            ['Fecha de', 'solicitud'], ['Entrega', 'programada'],
            ['Estado', 'operativo'], ['Lote'], ['Ver'], ['Aprobación'], ['Próximo', 'proceso'],
            ['Solicitud', 'completa'], ['Inspección'], ['Tipo'], ['Observaciones'], ['Ajustes'],
        ];
        if ($isHospitalView) {
            $validationColumns = array_slice($validationColumns, 0, 11);
        }
    @endphp
    <div class="mt-4 overflow-x-auto" data-sticky-x-position="viewport">
        <table id="solicitud-validations-table" class="w-full text-sm text-left text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    @foreach ($validationColumns as $column)
                        <th class="px-2 py-3 text-center leading-tight" data-force-column-filter>
                            @foreach ($column as $line)
                                <span class="block">{{ $line }}</span>
                            @endforeach
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($validations as $row)
                    <tr class="border-b" data-validation-row>
                        <td class="px-2 py-3 text-center whitespace-nowrap">@include('admin.solicitudes._type-badge', ['type' => $row['type']])</td>
                        <td class="px-2 py-3 text-center">{{ $row['id'] ?? '—' }}</td>
                        <td class="px-2 py-3 text-center">{{ $row['request_id'] }}</td>
                        <td class="min-w-[10rem] px-2 py-3 text-center">{{ $row['hospital'] }}</td>
                        <td class="min-w-[10rem] px-2 py-3 text-center">{{ $row['patient'] }}</td>
                        <td class="px-2 py-3 text-center whitespace-nowrap">{{ $row['requested_at']?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-2 py-3 text-center whitespace-nowrap">{{ $row['delivery_at']?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-2 py-3 text-center">@include('admin.solicitudes._status-badge', ['status' => $row['status']])</td>
                        <td class="px-2 py-3 text-center">{{ $row['lot'] ?: '—' }}</td>
                        <td class="px-2 py-3 text-center">
                            <a href="{{ $row['url'] }}" class="inline-flex rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Ver</a>
                        </td>
                        <td class="px-2 py-3 text-center"><span class="inline-flex rounded-full bg-red-100 px-3 py-2 text-xs font-semibold text-red-700">Rechazada</span></td>
                        @unless ($isHospitalView)
                            <td class="px-2 py-3 text-center">—</td>
                            <td class="px-2 py-3 text-center whitespace-nowrap">
                                <a href="{{ $row['document_url'] }}" target="_blank" rel="noopener" class="inline-flex rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Solicitud completa</a>
                            </td>
                            <td class="px-2 py-3 text-center">
                                @if ($row['inspection_url'])
                                    <a href="{{ $row['inspection_url'] }}" target="_blank" rel="noopener" class="inline-flex rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Inspección</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="min-w-[9rem] px-2 py-3 text-center">{{ $row['validation_type'] }}</td>
                            <td class="min-w-[14rem] max-w-xs break-words px-2 py-3">{{ $row['observations'] ?: 'Sin observaciones registradas' }}</td>
                            <td class="px-2 py-3 text-center">—</td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($validations->isEmpty())
        <p role="status" class="px-4 py-10 text-center text-sm text-gray-400">
            No hay solicitudes rechazadas para estos filtros.
        </p>
    @endif
</x-admin-layout>
