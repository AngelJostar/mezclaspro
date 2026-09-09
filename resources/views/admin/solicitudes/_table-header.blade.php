@php
    $sortFields = $sortFields ?? [];
    $tableSortField = $tableSortField ?? null;
    $tableSortDirection = $tableSortDirection ?? 'asc';
    $isHospitalView = $isHospitalView ?? (auth()->user()?->hasAnyRole(['Cliente', 'Institucion']) ?? false);

    $columns = [
        ['key' => 'type', 'lines' => ['Tipo']],
        ['key' => 'id', 'lines' => ['ID mezcla']],
        ['key' => 'request_id', 'lines' => ['No. solicitud']],
        ['key' => 'hospital', 'lines' => ['Hospital']],
        ['key' => 'patient', 'lines' => ['Paciente']],
        ['key' => 'requested_at', 'lines' => ['Fecha y hora', 'de solicitud'], 'date' => true],
        ['key' => 'delivery_at', 'lines' => ['Fecha y hora', 'programada de entrega'], 'date' => true],
        ['key' => 'status', 'lines' => ['Estado operativo']],
        ['key' => 'lot', 'lines' => ['Lote']],
        ['key' => 'view', 'lines' => ['Ver'], 'action' => true],
        ['key' => 'edit', 'lines' => ['Aprobación']],
        ['key' => 'process', 'lines' => ['Próximo', 'proceso']],
        ['key' => 'complete_request', 'lines' => ['Solicitud completa'], 'action' => true],
        ['key' => 'inspection', 'lines' => ['Inspección'], 'action' => true],
        ['key' => 'label', 'lines' => ['Etiqueta'], 'action' => true],
        ['key' => 'preparation_order', 'lines' => ['Orden de preparación'], 'action' => true],
        ['key' => 'shipping_records', 'lines' => ['Registros de envío'], 'action' => true],
        ['key' => 'remission_document', 'lines' => ['Remisión'], 'action' => true],
        ['key' => 'subdistributor_remission', 'lines' => ['Remision', 'Subdistribuidor'], 'action' => true],
    ];

    if ($isHospitalView) {
        $columns = array_filter($columns, fn ($column) => in_array($column['key'], [
            'type', 'id', 'request_id', 'hospital', 'patient', 'requested_at',
            'delivery_at', 'status', 'lot', 'view', 'edit',
        ], true));
    }
@endphp

<tr>
    @foreach ($columns as $column)
        @php
            $backendSortField = $sortFields[$column['key']] ?? null;
            $isSortable = filled($backendSortField);
            $isActiveSort = $isSortable && $tableSortField === $backendSortField;
        @endphp
        <th
            @class([
                'px-2 py-2 text-center whitespace-nowrap',
                'w-[13rem] min-w-[13rem] max-w-[13rem] leading-tight' => $column['date'] ?? false,
                'cursor-pointer' => $isSortable,
            ])
            @if ($column['action'] ?? false) data-command-column @endif
            @if (! ($column['action'] ?? false)) data-force-column-filter @endif
            @if ($isSortable) wire:click="sortBy('{{ $backendSortField }}')" @endif>
            @foreach ($column['lines'] as $line)
                <span @class(['block' => count($column['lines']) > 1])>
                    {{ $line }}
                    @if ($loop->last && $isSortable)
                        <span class="{{ $isActiveSort ? 'font-bold text-blue-700' : 'text-gray-400' }}">
                            {{ $isActiveSort ? ($tableSortDirection === 'asc' ? '↑' : '↓') : '↔' }}
                        </span>
                    @endif
                </span>
            @endforeach
        </th>
    @endforeach
</tr>
