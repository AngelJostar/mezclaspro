@php
    $columns = \App\Support\HospitalConciliationTable::COLUMNS;
    $columnConfig = $conciliation + ['columns' => array_keys($columns), 'url' => route('admin.hospital.herramientas', $filterQuery)];
@endphp
<h2 class="ht-table-title">Conciliación · Mezclas solicitadas</h2>
<script type="application/json" id="conciliation-filters">@json($columnConfig)</script>
<div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Mezclas para conciliación">
    <table class="ht-table ht-conciliation-table" data-server-column-filters="conciliation-filters">
        <thead><tr>
            @foreach ($columns as $field => $lines)
                <th @if ($field === 'view') data-command-column @else data-force-column-filter aria-sort="{{ $conciliation['sort'] === $field ? ($conciliation['direction'] === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif>
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
                        <td data-column-filter-value="{{ $row['cells'][$field] ?? '' }}">
                            @switch($field)
                                @case('type') @include('admin.solicitudes._type-badge', ['type' => $row['kind']]) @break
                                @case('view') <a class="ht-view" href="{{ $row['url'] }}" aria-label="Ver mezcla {{ $row['id'] }}">Ver</a> @break
                                @case('approval')
                                    <button type="button" disabled class="ht-approval ht-approval-{{ ['Aprobada' => 'approved', 'Rechazada' => 'rejected', 'Pendiente' => 'pending'][$row['approval']] }}">{{ $row['approval'] }}</button>
                                    @break
                                @case('status') @include('admin.solicitudes._status-badge', ['status' => $row['status']]) @break
                                @case('conciliable')
                                    <label class="ht-switch"><input type="checkbox" role="switch" aria-label="Conciliable {{ $row['kind'] }} mezcla {{ $row['id'] }}" @checked($row['conciliable']) data-conciliable data-previous="{{ $row['billing']?->conciliable }}" data-url="{{ route('admin.hospital.conciliable', ['kind' => $row['kind'], 'target' => $row['id']]) }}"><span aria-hidden="true"></span><output>{{ $row['cells']['conciliable'] }}</output></label>
                                    @break
                                @default {{ $row['cells'][$field] }}
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="ht-empty">No hay mezclas que coincidan con los filtros del periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
