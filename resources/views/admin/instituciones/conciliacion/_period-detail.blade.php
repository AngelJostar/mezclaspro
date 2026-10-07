<p class="cp-subtitle">{{ $group['hospital'] }} · {{ \Carbon\Carbon::parse($group['from'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($group['to'])->format('d/m/Y') }}</p>
<div class="cp-institution"><span>Institución</span><p>{{ $group['institution'] }}</p></div>
<div class="cp-summary">
    <div><span>Monto original</span><strong data-period-original>{{ \App\Services\HospitalConciliationSummary::money($group['total_cents']) }}</strong><small>IVA incluido</small></div>
    <div class="cp-excluded"><span>No conciliable</span><strong data-period-excluded>{{ \App\Services\HospitalConciliationSummary::money($group['excluded_cents']) }}</strong><small>IVA incluido</small></div>
    <div class="cp-new"><span>Nuevo monto</span><strong data-period-summary-new>{{ \App\Services\HospitalConciliationSummary::money($group['new_cents']) }}</strong><small>IVA incluido</small></div>
</div>
<div class="cp-detail-scroll" tabindex="0" aria-label="Remisiones del periodo">
    <table class="ht-table cp-detail-table" data-disable-column-filters>
        <thead><tr><th>No. de remisión</th><th>Paciente</th><th>Fecha de remisión</th><th>Descripción</th><th>Cantidad</th><th>Precio unitario<br>(IVA incluido)</th><th>Precio total<br>(IVA incluido)</th><th>Conciliable</th><th>Estatus de conciliación</th></tr></thead>
        <tbody>
            @foreach ($group['rows'] as $row)
                <tr data-period-item="{{ $row['key'] }}">
                    <td>{{ $row['remision'] }}</td><td>{{ $row['patient'] }}</td><td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y H:i') }}</td>
                    <td>@foreach ($row['lines'] as $line)<div class="cp-line">{{ $line['description'] }}</div>@endforeach</td>
                    <td>@foreach ($row['lines'] as $line)<div class="cp-line">{{ $line['quantity'] }}</div>@endforeach</td>
                    <td>@foreach ($row['lines'] as $line)<div class="cp-line">{{ \App\Services\HospitalConciliationSummary::money($line['unit_cents']) }}</div>@endforeach</td>
                    <td>{{ \App\Services\HospitalConciliationSummary::money($row['amount_cents']) }}</td>
                    <td><div class="cp-toggle" role="group" aria-label="Conciliable {{ $row['remision'] }} · {{ $row['patient'] }}">
                        <button type="button" data-period-choice="1" aria-pressed="{{ $row['conciliable'] ? 'true' : 'false' }}">Sí</button>
                        <button type="button" data-period-choice="0" aria-pressed="{{ $row['conciliable'] ? 'false' : 'true' }}">No</button>
                    </div></td>
                    <td><span class="cp-status" data-status="{{ $row['status'] }}">{{ $row['status'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="cp-note" data-period-excluded-count>{{ collect($group['rows'])->where('conciliable', false)->count() }} remisiones marcadas No.</p>
@if ($group['missing_prices'])<p class="cp-error">Hay {{ $group['missing_prices'] }} remisiones sin precio válido. Regístralo en «Por remisión» antes de enviar.</p>@endif
<p class="cp-note">El precio total corresponde al importe de venta de la remisión, incluidos servicios, insumos y ajustes registrados. Aceptar guarda la selección y actualiza el resumen. El envío al hospital se realiza desde el listado.</p>
