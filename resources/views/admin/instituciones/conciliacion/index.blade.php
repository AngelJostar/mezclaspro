<x-admin-layout>
    <div class="mt-2 mb-4"><h1 class="text-2xl font-medium text-gray-800">Panel Administrativo</h1></div>
    <div class="conciliation-agent-toolbar" data-conciliation-agent>
        @include('admin.instituciones.partials.administration-carousel', ['administrationSection' => 'conciliacion'])
        <button type="button" class="ca-primary ca-launch" data-agent-open aria-haspopup="dialog"
            data-url="{{ route('admin.instituciones.conciliaciones.agent', request()->only(['institucion_id', 'hospital_id', 'search'])) }}"><i data-ca-icon="sparkles" aria-hidden="true"></i> Agente de IA</button>
        <dialog class="ca-dialog" data-agent-dialog aria-labelledby="conciliation-agent-title">
            <header class="ca-heading"><span class="ca-symbol"><i data-ca-icon="sparkles" aria-hidden="true"></i></span><div><h2 id="conciliation-agent-title" tabindex="-1">Agente de conciliación</h2><p>Administración · Conciliación</p></div><button class="ca-icon" type="button" data-agent-close aria-label="Cerrar agente" title="Cerrar"><i data-ca-icon="x" aria-hidden="true"></i></button></header>
            <div class="ca-body"><p data-agent-loading role="status" hidden>Cargando agente...</p><div data-agent-content></div><p data-agent-error role="alert" hidden></p><button type="button" class="ca-secondary" data-agent-retry hidden>Reintentar</button></div>
        </dialog>
    </div>
    <section class="hospital-tools" data-conciliation-inbox aria-labelledby="conciliation-inbox-title">
        <div class="ht-log-heading"><h2 id="conciliation-inbox-title">Solicitudes de conciliación recibidas</h2></div>
        <form class="ht-filters ht-received-filters" method="GET" action="{{ route('admin.instituciones.reportes') }}"
            x-data="{ institution: '{{ $institutionId ?? '' }}' }">
            <input type="hidden" name="seccion" value="conciliacion">
            <div class="ht-received-filter">
                <label for="conciliation-institution">Institución</label>
                <select id="conciliation-institution" name="institucion_id" x-model="institution" @change="$refs.hospital.value = ''">
                    <option value="">Todas las instituciones</option>
                    @foreach ($institutions as $institution)<option value="{{ $institution->id }}" @selected($institutionId === $institution->id)>{{ $institution->nombre }}</option>@endforeach
                </select>
            </div>
            <div class="ht-received-filter">
                <label for="conciliation-hospital">Hospital</label>
                <select id="conciliation-hospital" name="hospital_id" x-ref="hospital">
                    <option value="">Todos los hospitales</option>
                    @foreach ($hospitals as $hospital)
                        @php($institutionIds = $hospital->instituciones->modelKeys())
                        <option value="{{ $hospital->id }}" @selected($hospitalId === $hospital->id)
                            @disabled($institutionId && !in_array($institutionId, $institutionIds))
                            @if ($institutionId && !in_array($institutionId, $institutionIds)) hidden @endif
                            :disabled="institution !== '' && !{{ json_encode($institutionIds) }}.includes(Number(institution))"
                            :hidden="institution !== '' && !{{ json_encode($institutionIds) }}.includes(Number(institution))">{{ $hospital->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ht-received-filter"><label for="conciliation-search">Hospital o remitente</label><input id="conciliation-search" name="search" value="{{ $search }}" maxlength="150" type="search"></div>
            <button class="ht-primary" type="submit">Aplicar filtros</button>
            <a class="ht-clear" href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion']) }}">Limpiar</a>
        </form>
        <div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Solicitudes de conciliación recibidas">
            <table class="ht-table">
                <thead><tr><th>Folio</th><th>Fecha de envío</th><th>Institución</th><th>Hospital</th><th>Enviado por</th><th>Periodo</th><th>Mezclas</th><th>Conciliables Sí</th><th>Conciliables No</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        @php($noCount = $submission->mixture_count - $submission->conciliable_count)
                        <tr><td>{{ $submission->folio() }}</td><td class="ht-nowrap">{{ $submission->created_at->format('d/m/Y H:i') }}</td><td>{{ $institutionNames->get($submission->hospital_id) ?: 'Sin institución' }}</td><td>{{ $submission->hospital_name }}</td><td>{{ $submission->sender_name }}</td><td>{{ $submission->periodLabel() }}</td><td>{{ $submission->mixture_count }}</td><td>{{ $submission->conciliable_count }}</td><td data-column-filter-value="{{ $noCount }}">@if ($noCount > 0)<span class="ht-no-count" title="{{ $noCount }} mezclas no conciliables">{{ $noCount }}</span>@else 0 @endif</td>
                            <td><div class="ht-invoice-actions"><a class="ht-primary" data-open-conciliation aria-haspopup="dialog" href="{{ route('admin.instituciones.conciliaciones.show', $submission) }}">Ver solicitud</a><a class="ht-secondary" href="{{ route('admin.instituciones.conciliaciones.download', $submission) }}">Descargar reporte</a></div></td></tr>
                    @empty
                        <tr><td colspan="10" class="ht-empty">No hay solicitudes de conciliación recibidas{{ $search || $institutionId || $hospitalId ? ' para los filtros seleccionados' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $submissions->links() }}</div>
        <dialog class="ht-review-dialog" data-conciliation-review aria-labelledby="conciliation-review-title">
            <header class="ht-review-heading"><h2 id="conciliation-review-title" tabindex="-1">Resumen de conciliación</h2><button type="button" class="ht-icon" data-close-review aria-label="Cerrar resumen" title="Cerrar"><i data-tools-icon="x" aria-hidden="true"></i></button></header>
            <div class="ht-review-body">
                <p role="status" data-review-loading hidden>Cargando conciliación...</p>
                <div data-review-error hidden><p role="alert" class="ht-error"></p><button type="button" class="ht-secondary" data-review-retry>Reintentar</button></div>
                <div data-review-content></div>
            </div>
            <footer class="ht-review-footer"><span>Consulta de la conciliación enviada por el hospital</span><button type="button" class="ht-primary" data-close-review>Cerrar</button></footer>
        </dialog>
    </section>
</x-admin-layout>
