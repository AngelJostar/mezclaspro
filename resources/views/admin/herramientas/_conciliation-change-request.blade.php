<form data-client-request-form action="{{ route('admin.herramientas.conciliaciones.request-changes', $submission) }}" method="POST">
    @csrf
    <p><strong>{{ $submission->folio() }} · {{ $submission->hospital_name }}</strong><br>{{ $submission->periodLabel() }}</p>
    <p class="my-3">Escribe el motivo en las remisiones que requieren cambios. PROMESA Prodifem revisará la solicitud antes de autorizarla.</p>
    <div class="ht-table-scroll" tabindex="0" aria-label="Remisiones del periodo recibido">
        <table class="ht-table client-change-table" data-disable-column-filters>
            <thead><tr><th>No. de remisión</th><th>Paciente</th><th>Monto con IVA</th><th>Conciliable recibido</th><th>Cambio solicitado</th><th>Observaciones y motivo</th></tr></thead>
            <tbody>
                @foreach ($submission->snapshot as $row)
                    @php($key = $row['kind'].'-'.$row['id'])
                    <tr data-client-change-row data-key="{{ $key }}">
                        <td>{{ $row['remision'] ?? $row['cells']['id'] }}</td><td>{{ $row['cells']['patient'] }}</td>
                        <td>{{ \App\Services\HospitalConciliationSummary::money($row['amount_cents'] ?? null) }}</td>
                        <td>{{ ($row['conciliable'] ?? ($row['cells']['conciliable'] !== 'No')) ? 'Sí' : 'No' }}</td>
                        <td><select data-client-change-choice aria-label="Cambio solicitado para remisión {{ $row['remision'] ?? $row['id'] }}"><option value="">Sin cambio</option><option value="1">Conciliable: Sí</option><option value="0">Conciliable: No</option></select></td>
                        <td><textarea data-client-change-reason maxlength="2000" rows="2" aria-label="Motivo para remisión {{ $row['remision'] ?? $row['id'] }}" placeholder="Describe el cambio y su motivo"></textarea></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <footer><button type="button" class="ht-secondary" data-client-request-close>Cancelar</button><button type="submit" class="ht-primary">Enviar solicitud de cambios</button></footer>
</form>
