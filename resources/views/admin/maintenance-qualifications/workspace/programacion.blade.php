<div class="mw-heading"><h2>Programación de servicios y mantenimientos</h2><button class="mw-button mw-primary" data-maint-open="appointment">＋ Programar servicio</button></div>
<div class="mw-toolbar">@include('admin.maintenance-qualifications.workspace.period')<a class="mw-button" href="{{ route($routePrefix.'export', $filters) }}">Exportar</a></div>
<form class="mw-filters" method="get"><input type="hidden" name="seccion" value="programacion"><input type="hidden" name="fecha" value="{{ $date->toDateString() }}"><input type="hidden" name="vista" value="{{ $mode }}">@if($laboratoryId)<input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">@endif
<input name="q" placeholder="Buscar folio, servicio o activo" aria-label="Buscar folio, servicio o activo" value="{{ $filters['q'] ?? '' }}"><select name="estado" aria-label="Estado"><option value="all">Estado: Todos</option>@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected(($filters['estado'] ?? '') === $key)>{{ $label }}</option>@endforeach</select><button class="mw-button">Filtrar</button><a href="{{ $url(['q'=>null,'estado'=>null,'tipo'=>null,'seleccion'=>null]) }}">Limpiar</a></form>
@include('admin.maintenance-qualifications.workspace.appointments-table')
@if($selectedAppointment)
@php $a=$selectedAppointment; @endphp
<section class="mw-detail" id="maintenance-report"><h3>{{ $a->folio }} · Reporte del servicio</h3>
<dl class="mw-report-grid"><dt>Central</dt><dd>{{ $a->laboratory->nombre }}</dd><dt>Ubicación</dt><dd>{{ $a->location ?: 'Sin especificar' }}</dd><dt>Activo</dt><dd>{{ $a->asset }}</dd><dt>Responsable interno</dt><dd>{{ $a->responsible_name ?: 'Sin asignar' }}</dd><dt>Inicio</dt><dd>{{ $a->starts_at->format('d/m/Y H:i') }}</dd><dt>Fin</dt><dd>{{ $a->ends_at?->format('d/m/Y H:i') ?: 'Sin registrar' }}</dd><dt>Próximo servicio</dt><dd>{{ $a->next_service_at?->format('d/m/Y') ?: 'Por definir' }}</dd><dt>Estado del reporte</dt><dd><span class="mw-status {{ $a->report_status === 'closed' ? 'completed' : 'pending' }}">{{ $reportLabels[$a->report_status] }}</span></dd><dt>Actividades y observaciones</dt><dd class="mw-notes">{{ $a->report_notes ?: 'Sin observaciones registradas' }}</dd></dl>
@include('admin.maintenance-qualifications.workspace.documents', ['documents'=>$a->documents])
<div class="mw-actions">
@if($a->report_status !== 'closed')
    <button class="mw-button" data-maint-open="appointment" data-url="{{ route($routePrefix.'appointments.update', $a) }}" data-record="{{ json_encode($appointmentRecord($a)) }}">Editar / reprogramar</button>
    <button class="mw-button" data-maint-open="document" data-url="{{ route($routePrefix.'documents.store', ['kind'=>'appointment','id'=>$a->id]) }}">Adjuntar reporte</button>
    <button class="mw-button mw-primary" data-maint-open="close" data-url="{{ route($routePrefix.'appointments.close', $a) }}" @disabled($a->status !== 'completed' || ($a->service->report_required && $a->documents->isEmpty()))>Revisar y cerrar</button>
@else<p class="mw-status completed">Reporte cerrado el {{ $a->closed_at?->format('d/m/Y H:i') }}</p>@endif
</div>@if($a->report_status !== 'closed')<p class="mw-hint">Para cerrar, registra el servicio como realizado y adjunta su reporte cuando sea requerido.</p>@endif
</section>
@endif
