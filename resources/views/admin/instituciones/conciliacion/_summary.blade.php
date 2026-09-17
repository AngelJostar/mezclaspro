<div class="ht-review-summary">
    <dl class="ht-review-context">
        <div><dt>Hospital</dt><dd>{{ $submission->hospital_name }}</dd></div>
        <div><dt>Institución</dt><dd>{{ $institution }}</dd></div>
        <div><dt>Enviado por</dt><dd>{{ $submission->sender_name }}</dd></div>
        <div><dt>Fecha de envío</dt><dd>{{ $submission->created_at->format('d/m/Y H:i') }}</dd></div>
        <div><dt>Periodo</dt><dd>{{ $submission->periodLabel() }}</dd></div>
    </dl>
    <div class="ht-review-metrics">
        <div class="ht-review-total"><i data-review-icon="flask-conical" aria-hidden="true"></i><dl><dt>Total de mezclas</dt><dd>{{ $summary['total']['count'] }}</dd></dl></div>
        <div class="ht-review-yes"><i data-review-icon="circle-check" aria-hidden="true"></i><dl><dt>Conciliables · Sí</dt><dd>{{ $summary['yes']['count'] }}</dd></dl></div>
        <div class="ht-review-no"><i data-review-icon="circle-x" aria-hidden="true"></i><dl><dt>No conciliables · No</dt><dd>{{ $summary['no']['count'] }}</dd></dl></div>
        <div class="ht-review-amount"><i data-review-icon="receipt" aria-hidden="true"></i><dl><dt>Monto conciliable</dt><dd>{{ \App\Services\HospitalConciliationSummary::money($summary['yes']['amount_cents']) }}@if($summary['yes']['missing_amounts'])<small>{{ $summary['yes']['missing_amounts'] }} mezclas sin importe registrado</small>@endif</dd></dl></div>
    </div>
    <h3>Listado de solicitudes del hospital</h3>
    <div class="ht-review-toolbar">
        <nav aria-label="Filtrar mezclas de la conciliación">
            @foreach (['todas' => ['Todas', 'total'], 'si' => ['Conciliables', 'yes'], 'no' => ['No conciliables', 'no']] as $key => [$label, $group])
                <a class="ht-secondary" data-review-page @if($state === $key) aria-current="page" @endif href="{{ route('admin.instituciones.conciliaciones.show', [$submission, 'estado' => $key, 'search' => $search]) }}">{{ $label }} ({{ $summary[$group]['count'] }})</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('admin.instituciones.conciliaciones.show', $submission) }}" data-review-search>
            <input type="hidden" name="estado" value="{{ $state }}">
            <div class="ht-invoice-search"><input type="search" name="search" value="{{ $search }}" maxlength="150" aria-label="Buscar solicitud o paciente" placeholder="Buscar solicitud o paciente"><button type="submit" class="ht-icon" title="Buscar" aria-label="Buscar"><i data-review-icon="search" aria-hidden="true"></i></button></div>
        </form>
        <a class="ht-secondary" href="{{ route('admin.instituciones.conciliaciones.download', $submission) }}"><i data-review-icon="download" aria-hidden="true"></i> Descargar reporte</a>
    </div>
    <div class="ht-review-table-scroll" tabindex="0" aria-label="Mezclas de la conciliación enviada">
        <table class="ht-table ht-review-table" data-disable-column-filters>
            <colgroup>@foreach([105, 65, 85, 155, 125, 175, 145, 145, 100, 70, 90, 140, 120, 100, 145, 230] as $width)<col style="width: {{ $width }}px">@endforeach</colgroup>
            <thead><tr><th>Tipo</th><th>ID mezcla</th><th>No. solicitud</th><th>Institución</th><th>Hospital</th><th>Paciente</th><th>Fecha y hora de solicitud</th><th>Entrega programada</th><th>Lote</th><th>Ver</th><th>Aprobación</th><th>Ajustes</th><th>Estado de proceso</th><th>Conciliable</th><th>Importe (MXN)</th><th>Motivo no conciliable</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>@include('admin.solicitudes._type-badge', ['type' => $row['kind']])</td>
                        @foreach(['id', 'request_id', 'institution', 'hospital', 'patient', 'date', 'delivery_date', 'lot'] as $field)<td>{{ $row['cells'][$field] }}</td>@endforeach
                        <td>@if($row['url'])<a class="ht-view" href="{{ $row['url'] }}" target="_blank" rel="noopener" aria-label="Ver mezcla {{ $row['id'] }}" title="Ver mezcla en otra pestaña">Ver</a>@else — @endif</td>
                        <td><span class="ht-review-badge {{ $row['cells']['approval'] === 'Aprobada' ? 'is-yes' : ($row['cells']['approval'] === 'Rechazada' ? 'is-no' : '') }}">{{ $row['cells']['approval'] }}</span></td>
                        <td><span class="ht-review-badge {{ ($row['adjustment'] ?? '') === 'Aprobada con Ajuste' ? 'is-yes' : '' }}">{{ $row['adjustment'] ?? 'Sin registrar' }}</span></td>
                        <td><span class="ht-review-badge is-process">{{ $row['cells']['status'] }}</span></td>
                        <td><span class="ht-review-locked"><span class="ht-review-badge {{ $row['conciliable'] ? 'is-yes' : 'is-no' }}">{{ $row['conciliable'] ? 'Sí' : 'No' }}</span><i data-review-icon="lock-keyhole" aria-label="Solo lectura" role="img"></i></span></td>
                        <td>{{ \App\Services\HospitalConciliationSummary::money($row['amount_cents'] ?? null) }}</td>
                        <td class="ht-reason">{{ ($row['reason'] ?? '') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="16" class="ht-empty">No hay mezclas que coincidan con los filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ht-pagination">
        <span>Mostrando {{ $rows->count() }} mezclas</span>
    </div>
</div>
