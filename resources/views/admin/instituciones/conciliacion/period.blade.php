<x-admin-layout>
    @push('css')
        @include('admin.instituciones.conciliacion._period-style')
        @include('admin.instituciones.conciliacion._layout-style')
    @endpush
    <div class="mt-2 mb-4"><h1 class="text-2xl font-medium text-gray-800">Panel Administrativo</h1></div>
    @include('admin.instituciones.conciliacion._toolbar')
    @include('admin.instituciones.conciliacion._mode-tabs', ['conciliationMode' => 'periodo'])
    <section class="hospital-tools cp-panel conciliation-view" data-conciliation-view data-conciliation-period data-active-tab="{{ $activeTab }}" aria-label="Conciliación por periodo"
        data-detail-url="{{ route('admin.instituciones.conciliacion-periodos.detail') }}"
        data-accept-url="{{ route('admin.instituciones.conciliacion-periodos.accept') }}"
        data-authorize-url="{{ route('admin.instituciones.conciliacion-periodos.authorize-changes') }}"
        data-send-url="{{ route('admin.instituciones.conciliacion-periodos.send') }}">
        <div class="cp-list-actions">
            @include('admin.instituciones.conciliacion._status-tabs')
            <button type="button" class="cp-primary cp-create-launch" data-period-create-open aria-haspopup="dialog">Crear periodo</button>
        </div>
        @if ($createdCount)<p role="status" class="cp-created">{{ $createdCount === 1 ? 'Periodo creado.' : $createdCount.' periodos creados, uno por hospital.' }} Abre Ver en cada renglón para revisar las remisiones antes de enviar.</p>@endif
        @if ($errors->any())<p role="alert" class="cp-error">{{ $errors->first() }}</p>@endif
        <form class="conciliation-filters" method="GET" action="{{ route('admin.instituciones.reportes') }}">
            <input type="hidden" name="seccion" value="conciliacion"><input type="hidden" name="modalidad" value="periodo">
            <input type="hidden" name="bandeja" value="{{ $activeTab }}">
            <div class="conciliation-field"><label for="cp-institution">Institución</label><select id="cp-institution" name="institucion_id">
                <option value="">Todas las instituciones</option>
                @foreach ($institutions as $institution)<option value="{{ $institution->id }}" @selected(($filters['institucion_id'] ?? '') == $institution->id)>{{ $institution->nombre }}</option>@endforeach
            </select></div>
            <div class="conciliation-field"><label for="cp-hospital">Hospital</label><select id="cp-hospital" name="hospital_id">
                <option value="">Todos los hospitales</option>
                @foreach ($hospitals as $hospital)<option value="{{ $hospital->id }}" data-institutions="{{ json_encode($hospital->instituciones->modelKeys()) }}" @selected(($filters['hospital_id'] ?? '') == $hospital->id)>{{ $hospital->name }}</option>@endforeach
            </select></div>
            <div class="conciliation-filter-detail conciliation-filter-detail--dates">
                <div class="conciliation-field"><label for="cp-from">Desde</label><input id="cp-from" type="date" name="desde" value="{{ $filters['desde'] }}" required></div>
                <div class="conciliation-field"><label for="cp-to">Hasta</label><input id="cp-to" type="date" name="hasta" value="{{ $filters['hasta'] }}" required></div>
            </div>
            <div class="conciliation-filter-actions">
                <button type="submit" class="ht-primary">Aplicar filtros</button>
                <a class="ht-clear" href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'modalidad' => 'periodo']) }}">Limpiar</a>
            </div>
        </form>
        <p data-period-feedback role="status" hidden></p>
        <div class="ht-table-scroll" data-sticky-x-position="viewport" data-sticky-x-native data-sticky-x-always-visible tabindex="0" aria-label="Conciliaciones por periodo">
            <table class="ht-table cp-table conciliation-table" id="conciliation-period-table" data-disable-column-filters>
                <thead><tr>
                    @foreach (['Institución', 'Hospital', 'Periodo de la conciliación', 'Monto total de la conciliación (IVA incluido)', 'Detalle', 'Monto de Conciliación', 'Estatus de Conciliación', 'Observaciones', 'Autorización de cambios', 'Enviar'] as $label)
                        @php($headerLines = match ($loop->index) {
                            2 => ['Periodo de la', 'conciliación'],
                            3 => ['Monto total de la', 'conciliación (IVA incluido)'],
                            5 => ['Monto de', 'Conciliación'],
                            6 => ['Estatus de', 'Conciliación'],
                            8 => ['Autorización de', 'cambios'],
                            default => [$label],
                        })
                        <th><div class="conciliation-column"><span>@foreach ($headerLines as $line)<span>{{ $line }} </span>@endforeach</span><button type="button" class="cp-filter conciliation-column-filter" data-table-column-trigger data-column="{{ $loop->index }}" aria-label="Filtrar {{ $label }}" title="Filtrar {{ $label }}" aria-expanded="false"><span aria-hidden="true">&#9660;</span></button></div></th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($groups as $group)
                        @php($observationsDisabled = $group['conciliation_status'] === 'Pendiente' || ($group['conciliation_status'] === 'Aceptada' && !$group['changes_count']))
                        <tr data-period-row="{{ $group['key'] }}">
                            <td>{{ $group['institution'] }}</td><td>{{ $group['hospital'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($group['from'])->format('d/m/Y') }} –<br>{{ \Carbon\Carbon::parse($group['to'])->format('d/m/Y') }}</td>
                            <td data-period-total>{{ \App\Services\HospitalConciliationSummary::money($group['total_cents']) }}</td>
                            <td data-filter-value="Ver"><button type="button" class="ht-view" data-period-open aria-label="Ver {{ $group['hospital'] }} del {{ $group['from'] }} al {{ $group['to'] }}">Ver</button></td>
                            <td data-filter-value="{{ \App\Services\HospitalConciliationSummary::money($group['new_cents']) }}" data-period-new-cell><output class="cp-amount" data-period-new title="Suma automática de las remisiones conciliables del periodo, con IVA incluido">{{ \App\Services\HospitalConciliationSummary::money($group['new_cents']) }}</output></td>
                            <td data-period-status-cell data-filter-value="{{ $group['conciliation_status'] }}"><button type="button" class="cp-reconciliation-status" data-period-observations data-period-status="{{ $group['conciliation_status'] }}" @disabled($observationsDisabled) aria-haspopup="dialog">{{ $group['conciliation_status'] }}</button></td>
                            <td data-filter-value="Ver"><button type="button" class="ht-view" data-period-observations @disabled($observationsDisabled) aria-label="Ver observaciones de {{ $group['hospital'] }} del {{ $group['from'] }} al {{ $group['to'] }}" aria-haspopup="dialog">Ver</button></td>
                            <td data-period-authorization-cell data-filter-value="{{ $group['authorization'] ? 'Autorizados' : ($group['changes_count'] ? 'Por autorizar' : 'Sin cambios') }}"><button type="button" class="cp-secondary cp-authorize" data-period-authorize-open @disabled(!$group['can_authorize']) aria-haspopup="dialog">{{ $group['authorization'] ? 'Autorizados' : 'Autorizar cambios' }}</button></td>
                            <td data-filter-value="{{ $group['sent_folio'] ? 'Enviada' : 'Pendiente de envío' }}" data-period-send-cell>
                                <button type="button" class="cp-primary" data-period-send @disabled($group['sent_folio'] || $group['missing_prices'])>{{ $group['sent_folio'] ? 'Enviada' : 'Enviar' }}</button>
                                <small data-period-folio>{{ $group['sent_folio'] ?: ($group['missing_prices'] ? 'Revisar precios faltantes' : '') }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="ht-empty">No hay remisiones de entrega que coincidan con los filtros seleccionados.</td></tr>
                    @endforelse
                    @if ($groups->isNotEmpty())<tr data-period-filter-empty hidden><td colspan="10" class="ht-empty">No hay periodos que coincidan con los filtros seleccionados.</td></tr>@endif
                </tbody>
            </table>
        </div>
        <div class="conciliation-footer"><p>Mostrando <span data-period-visible-count>{{ $groups->count() }}</span> periodos en una sola página. Incluye periodos creados y remisiones sin asignar agrupadas por mes; el envío se realiza por fila.</p></div>
        <script type="application/json" data-period-groups>@json($groups->map(fn ($group) => \Illuminate\Support\Arr::except($group, ['rows']))->values())</script>
        <dialog class="cp-dialog" aria-labelledby="cp-dialog-title" data-period-dialog>
            <header><h2 id="cp-dialog-title" tabindex="-1">Detalle de conciliación</h2><button class="cp-close" type="button" data-period-close aria-label="Cerrar detalle">×</button></header>
            <p data-period-loading role="status" hidden>Cargando remisiones…</p>
            <div data-period-detail></div>
            <p data-period-error role="alert" class="cp-error" hidden></p>
            <footer><button type="button" class="cp-secondary" data-period-close>Cancelar</button><button type="button" class="cp-primary" data-period-accept disabled>Aceptar y continuar</button></footer>
        </dialog>
        <dialog class="cp-dialog" aria-labelledby="cp-observations-title" data-period-observations-dialog>
            <header><h2 id="cp-observations-title" tabindex="-1">Observaciones y cambios</h2><button type="button" class="cp-close" data-period-observations-close aria-label="Cerrar observaciones">×</button></header>
            <p data-period-observations-loading role="status" hidden>Cargando observaciones…</p>
            <div data-period-observations-detail></div>
            <p data-period-observations-error role="alert" class="cp-error" hidden></p>
            <footer><button type="button" class="cp-secondary" data-period-observations-close>Cerrar</button><button type="button" class="cp-primary" data-period-authorize hidden>Autorizar cambios</button></footer>
        </dialog>
        @include('admin.instituciones.conciliacion._period-create')
    </section>
    <script>@include('admin.catalogo-listas.partials.column-filter-script')</script>
</x-admin-layout>
