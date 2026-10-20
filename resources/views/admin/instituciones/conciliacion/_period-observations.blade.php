<p class="cp-subtitle">{{ $group['institution'] }} · {{ $group['hospital'] }}<br>{{ \Carbon\Carbon::parse($group['from'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($group['to'])->format('d/m/Y') }}</p>
<p class="cp-note">{{ $group['changes_count'] }} {{ $group['changes_count'] === 1 ? 'remisión con cambios' : 'remisiones con cambios' }}.</p>
<div class="cp-detail-scroll" tabindex="0" aria-label="Observaciones de las remisiones">
    <table class="ht-table cp-observations-table" data-disable-column-filters>
        <thead><tr><th>No. de remisión</th><th>Paciente</th><th>Cambios</th><th>Observaciones</th><th>Motivo de los cambios</th></tr></thead>
        <tbody>
            @forelse (collect($group['rows'])->filter(fn ($row) => !empty($row['changes']) || !empty($row['observations']) || !empty($row['reason'])) as $row)
                <tr>
                    <td>{{ $row['remision'] }}</td><td>{{ $row['patient'] }}</td>
                    <td>@forelse ($row['changes'] ?? [] as $change)<p>{{ $change }}</p>@empty Sin cambios registrados. @endforelse</td>
                    <td class="cp-observation-text">{{ ($row['observations'] ?? '') ?: 'Sin observaciones registradas.' }}</td>
                    <td class="cp-observation-text">{{ ($row['reason'] ?? '') ?: 'Sin motivo registrado.' }}@if ($row['client_request'] ?? null)<small>Solicitado por {{ $row['client_request']['by'] }} · {{ $row['client_request']['at'] }}<br>{{ $row['client_request']['folio'] }}</small>@elseif ($row['reason_folio'] ?? null)<small>Recibido en {{ $row['reason_folio'] }}</small>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="ht-empty">No hay observaciones ni cambios registrados en las remisiones de este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($group['authorization'])
    <p class="cp-authorized">Cambios autorizados por {{ $group['authorization']['by'] }} · {{ $group['authorization']['at'] }}.</p>
@elseif ($group['changes_count'])
    <p class="cp-note">Solo el personal de PROMESA Prodifem puede autorizar estos cambios. Al autorizar se aplican las selecciones solicitadas por el cliente y se registra quién realizó la autorización.</p>
@endif
@if ($group['missing_prices'])<p class="cp-error">Hay remisiones sin precio válido. Regístralo antes de autorizar los cambios.</p>@endif
