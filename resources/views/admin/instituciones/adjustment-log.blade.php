<x-admin-layout>
    <h1 class="mb-4 text-2xl font-medium text-gray-800">Panel Administrativo</h1>
    @include('admin.instituciones.partials.administration-carousel', ['administrationSection' => 'ajustes'])
    <section class="hospital-tools mt-6">
        <div class="ht-log-heading"><h2>Bitácora de ajustes por mezcla</h2></div>
        <p class="mb-4 text-sm text-gray-500">Cada fila conserva una versión del ajuste. La revisión de IA previa se muestra como contexto; no acredita que haya originado el cambio ni sustituye una autorización médica.</p>
        <form class="ht-filters" method="GET">
            <input type="hidden" name="seccion" value="ajustes">
            <label>Mezcla o motivo<input type="search" name="search" maxlength="150" value="{{ $search }}"></label>
            <label>Estado<select name="estado"><option value="">Todos</option>@foreach (['requested' => 'Solicitado', 'authorized' => 'Autorizado', 'approved' => 'Aplicado', 'declined' => 'No autorizado', 'rejected' => 'Rechazado', 'cancelled' => 'Cancelado'] as $value => $label)<option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="ht-primary">Filtrar</button><a class="ht-clear" href="{{ route('admin.instituciones.reportes', ['seccion' => 'ajustes']) }}">Limpiar</a>
        </form>
        <div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Bitácora de ajustes">
            <table class="ht-table" style="min-width: 2600px">
                <thead><tr><th>Folio ajuste</th><th>Tipo</th><th>ID mezcla</th><th>No. solicitud</th><th>Institución</th><th>Hospital</th><th>Paciente</th><th>Lote</th><th>Fecha del ajuste</th><th>Motivo</th><th>Cambios propuestos / aplicados</th><th>Revisión IA previa</th><th>Autorización médica de la revisión</th><th>Solicitado por</th><th>Autorización del hospital</th><th>Respuesta del hospital</th><th>Aprobación de la central</th><th>Respuesta de la central</th><th>Cancelación</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($a = $row['adjustment'])
                        <tr>
                            <td>AJ-{{ $a->id }}</td><td>{{ ['nutricionales' => 'Nutricional', 'oncologicos' => 'Oncológica', 'antibioticos' => 'Antibiótica'][$a->kind] ?? $a->kind }}</td><td>{{ $a->target_id }}</td>
                            <td>{{ $a->kind === 'nutricionales' ? $a->target_id : ($row['target']?->solicitud_id ?? '—') }}</td>
                            <td>{{ $row['hospital']?->instituciones->pluck('nombre')->implode(', ') ?: 'Sin institución' }}</td><td>{{ $row['hospital']?->name ?: '—' }}</td><td>{{ $row['patient'] ?: '—' }}</td><td>{{ $row['target']?->lote ?: '—' }}</td>
                            <td>{{ $a->created_at?->format('d/m/Y H:i') }}</td><td>{{ $a->description }}</td>
                            <td>@forelse ($a->review ?? [] as $change)@if ($change['changed'] ?? true)<div><strong>{{ $change['label'] ?? 'Cambio' }}:</strong> {{ $change['before'] ?? 'Sin valor previo registrado' }} → {{ $change['value'] ?? '—' }}</div>@endif @empty Sin detalle registrado @endforelse</td>
                            <td>@if ($row['unreadable'])No se puede descifrar la revisión.@elseif ($row['review'])<div>{{ $row['review']->created_at->format('d/m/Y H:i') }} · {{ $row['review']->purpose }}</div><div>{{ $row['ai']['summary'] ?? 'Sin resumen' }}</div>@foreach ($row['ai']['findings'] ?? [] as $finding)<div>{{ is_string($finding) ? $finding : json_encode($finding, JSON_UNESCAPED_UNICODE) }}</div>@endforeach @else Sin revisión previa registrada @endif</td>
                            <td>@if ($row['unreadable'])No disponible.@elseif ($row['authorization']){{ $row['authorization']['doctor_name'] ?? 'Sin nombre' }} · Cédula: {{ $row['authorization']['doctor_license'] ?? 'No registrada' }}@else No registrada @endif</td>
                            <td>{{ $users->get($a->requested_by)?->name ?: '—' }}</td>
                            <td>{{ $users->get($a->authorized_by)?->name ?: 'No registrada' }}<div>{{ $a->authorized_at?->format('d/m/Y H:i') }}</div></td><td>{{ $a->hospital_response ?: '—' }}</td>
                            <td>{{ $users->get($a->approved_by)?->name ?: 'No registrada' }}<div>{{ $a->approved_at?->format('d/m/Y H:i') }}</div></td><td>{{ $a->central_response ?: '—' }}</td>
                            <td>{{ $users->get($a->cancelled_by)?->name ?: '—' }}<div>{{ $a->cancelled_at?->format('d/m/Y H:i') }}</div></td><td>{{ $a->log_label }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="20" class="ht-empty">No hay ajustes registrados para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $adjustments->links() }}</div>
    </section>
</x-admin-layout>
