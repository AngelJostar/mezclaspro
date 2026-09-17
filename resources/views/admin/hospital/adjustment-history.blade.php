<x-admin-layout>
    <section class="hospital-tools ht-history" data-tools-history>
        <header class="ht-log-heading">
            <h1 data-workflow-heading>Historial de ajustes · Mezcla #{{ $row['id'] }}</h1>
            <a class="ht-icon" href="{{ route('admin.hospital.herramientas', ['tab' => 'ajustes']) }}" data-workflow-popup-close aria-label="Cerrar historial" title="Cerrar historial"><i data-tools-icon="x" aria-hidden="true"></i></a>
        </header>
        <dl class="ht-history-context">
            <div><dt>Hospital</dt><dd>{{ $row['hospital'] }}</dd></div>
            <div><dt>Paciente</dt><dd>{{ $row['patient'] }}</dd></div>
            <div><dt>No. solicitud</dt><dd>{{ $row['request_id'] }}</dd></div>
            <div><dt>Estado operativo</dt><dd>@include('admin.solicitudes._status-badge', ['status' => $row['status']])</dd></div>
        </dl>
        @foreach ($versions as $version)
            <details class="ht-version" @if ($loop->first) open @endif>
                <summary><span>Versión #{{ $version->id }}</span><time>{{ $version->created_at?->format('d/m/Y H:i') }}</time>@include('admin.hospital._adjustment-status')</summary>
                <div class="ht-version-body">
                    <h2>Motivo del ajuste</h2><p class="ht-history-reason">{{ $version->description }}</p>
                    <ol class="ht-history-events">
                        <li><strong>Ajuste solicitado</strong><time>{{ $version->created_at?->format('d/m/Y H:i') }}</time><span>{{ $actors->get($version->requested_by)?->name ?? 'Usuario #'.$version->requested_by }}</span></li>
                        @if ($version->authorized_at)
                            <li><strong>{{ $version->status === 'declined' ? 'No autorizado por el hospital' : 'Respuesta del hospital' }}</strong><time>{{ $version->authorized_at->format('d/m/Y H:i') }}</time><span>{{ $actors->get($version->authorized_by)?->name ?? 'Usuario #'.$version->authorized_by }}</span>@if ($version->hospital_response)<p>{{ $version->hospital_response }}</p>@endif</li>
                        @endif
                        @if ($version->approved_at)
                            <li><strong>Aprobada con ajuste por Prodifem</strong><time>{{ $version->approved_at->format('d/m/Y H:i') }}</time><span>{{ $actors->get($version->approved_by)?->name ?? 'Usuario #'.$version->approved_by }}</span></li>
                        @endif
                        @if ($version->cancelled_at)
                            <li><strong>{{ $version->status === 'rejected' ? 'Rechazado por Prodifem' : 'Ajuste cancelado' }}</strong><time>{{ $version->cancelled_at->format('d/m/Y H:i') }}</time><span>{{ $actors->get($version->cancelled_by)?->name ?? 'Usuario #'.$version->cancelled_by }}</span>@if ($version->central_response)<p>{{ $version->central_response }}</p>@endif</li>
                        @endif
                    </ol>
                    <h2>Solicitud y propuesta de ajuste</h2>
                    <div class="ht-table-scroll"><table class="ht-table ht-version-table" data-disable-column-filters>
                        <thead><tr><th>Campo</th><th>Solicitud original</th><th>Ajuste Prodifem</th></tr></thead>
                        <tbody>@forelse ($version->review ?? [] as $field)
                            <tr @class(['ht-field-changed' => $field['changed'] ?? false]) data-adjustment-changed="{{ ($field['changed'] ?? false) ? 'true' : 'false' }}"><td>{{ $field['label'] }}@if ($field['changed'] ?? false)<small>Modificado</small>@endif</td><td>{{ filled($field['before'] ?? null) ? $field['before'] : 'Sin dato' }}</td><td>{{ filled($field['value'] ?? null) ? $field['value'] : 'Sin dato' }}</td></tr>
                        @empty<tr><td colspan="3">Sin campos registrados en esta versión.</td></tr>@endforelse</tbody>
                    </table></div>
                </div>
            </details>
        @endforeach
    </section>
</x-admin-layout>
