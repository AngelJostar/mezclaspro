@php
    $columns = \App\Support\HospitalAdjustmentLogTable::COLUMNS;
    $columnConfig = $adjustmentTable + ['columns' => array_keys($columns), 'url' => route('admin.hospital.herramientas', $filterQuery)];
@endphp
<header class="ht-log-heading">
    <h2>Bitácora de ajustes</h2>
    <a class="ht-export" href="{{ route('admin.hospital.ajustes.exportar', $filterQuery) }}" title="Descargar reporte de bitácora de ajustes en Excel"><i data-tools-icon="file-spreadsheet" aria-hidden="true"></i> Descargar reporte</a>
</header>
<div class="ht-log-toolbar">
    <p>Hospital: <strong>{{ auth()->user()->hospital?->name }}</strong></p>
</div>
<script type="application/json" id="adjustment-log-filters">@json($columnConfig)</script>
<div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Bitácora de ajustes">
    <table class="ht-table ht-log-table" data-server-column-filters="adjustment-log-filters">
        <thead><tr>
            @foreach ($columns as $field => $lines)
                <th @if (in_array($field, ['view', 'messages', 'history'])) data-command-column @else data-force-column-filter aria-sort="{{ $adjustmentTable['sort'] === $field ? ($adjustmentTable['direction'] === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif>
                    @foreach ($lines as $line)
                        <span class="block">{{ $line }}</span>
                    @endforeach
                </th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $field => $lines)
                        @if ($field === 'messages')
                            @include('admin.solicitudes._messages-cell', ['messageTarget' => $row['target'], 'messageKind' => $row['kind'], 'isHospitalView' => true])
                        @else
                            <td data-column-filter-value="{{ $row['cells'][$field] ?? '' }}" @class(['ht-reason' => $field === 'adjustment_reason'])>
                                @switch($field)
                                    @case('type') @include('admin.solicitudes._type-badge', ['type' => $row['kind']]) @break
                                    @case('view') <a class="ht-view" href="{{ $row['url'] }}" aria-label="Ver mezcla {{ $row['id'] }}">Ver</a> @break
                                    @case('approval')
                                        <button type="button" disabled class="ht-approval ht-approval-{{ ['Aprobada' => 'approved', 'Rechazada' => 'rejected', 'Pendiente' => 'pending'][$row['approval']] }}">{{ $row['approval'] }}</button>
                                        @break
                                    @case('status') @include('admin.solicitudes._status-badge', ['status' => $row['status']]) @break
                                    @case('adjustment_status') @include('admin.hospital._adjustment-status', ['version' => $row['version']]) @break
                                    @case('history')
                                        <a class="ht-view ht-history-link" href="{{ route('admin.hospital.ajustes.historial', ['kind' => $row['kind'], 'target' => $row['id'], 'approval_popup' => 1]) }}" data-approval-popup aria-label="Ver historial {{ $row['kind'] }} mezcla {{ $row['id'] }}">Ver historial</a>
                                        @break
                                    @default {{ $row['cells'][$field] }}
                                @endswitch
                            </td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="ht-empty">No hay mezclas con ajustes para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
