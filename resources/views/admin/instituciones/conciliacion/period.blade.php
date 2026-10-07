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
        data-send-url="{{ route('admin.instituciones.conciliacion-periodos.send') }}">
        <div class="cp-list-actions">
            @include('admin.instituciones.conciliacion._status-tabs')
            <button type="button" class="cp-primary cp-create-launch" data-period-create-open aria-haspopup="dialog">Crear periodo</button>
        </div>
        @if (request()->query('creado') && $groups->contains('period_id', (int) request()->query('creado')))<p role="status" class="cp-created">Periodo creado. Abre Ver para revisar las remisiones antes de enviarlo.</p>@endif
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
        <p class="conciliation-help">Monto total: suma de las remisiones con IVA incluido. Nuevo monto: remisiones marcadas Sí.</p>
        <p data-period-feedback role="status" hidden></p>
        <div class="ht-table-scroll" data-sticky-x-position="viewport" data-sticky-x-native data-sticky-x-always-visible tabindex="0" aria-label="Conciliaciones por periodo">
            <table class="ht-table cp-table conciliation-table" id="conciliation-period-table" data-disable-column-filters>
                <thead><tr>
                    @foreach (['Institución', 'Hospital', 'Periodo de la conciliación', 'Monto total de la conciliación (IVA incluido)', 'Detalle', 'Nuevo monto de conciliación', 'Enviar'] as $label)
                        @php($headerLines = match ($loop->index) {
                            2 => ['Periodo de la', 'conciliación'],
                            3 => ['Monto total de la', 'conciliación (IVA incluido)'],
                            5 => ['Nuevo monto de', 'conciliación'],
                            default => [$label],
                        })
                        <th><div class="conciliation-column"><span>@foreach ($headerLines as $line)<span>{{ $line }} </span>@endforeach</span><button type="button" class="cp-filter conciliation-column-filter" data-table-column-trigger data-column="{{ $loop->index }}" aria-label="Filtrar {{ $label }}" title="Filtrar {{ $label }}" aria-expanded="false"><span aria-hidden="true">&#9660;</span></button></div></th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr data-period-row="{{ $group['key'] }}">
                            <td>{{ $group['institution'] }}</td><td>{{ $group['hospital'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($group['from'])->format('d/m/Y') }} –<br>{{ \Carbon\Carbon::parse($group['to'])->format('d/m/Y') }}</td>
                            <td data-period-total>{{ \App\Services\HospitalConciliationSummary::money($group['total_cents']) }}</td>
                            <td data-filter-value="Ver"><button type="button" class="ht-view" data-period-open aria-label="Ver {{ $group['hospital'] }} del {{ $group['from'] }} al {{ $group['to'] }}">Ver</button></td>
                            <td data-filter-value="{{ \App\Services\HospitalConciliationSummary::money($group['new_cents']) }}" data-period-new-cell><output class="cp-amount" data-period-new>{{ \App\Services\HospitalConciliationSummary::money($group['new_cents']) }}</output></td>
                            <td data-filter-value="{{ $group['sent_folio'] ? 'Enviada' : 'Pendiente de envío' }}" data-period-send-cell>
                                <button type="button" class="cp-primary" data-period-send @disabled($group['sent_folio'] || $group['missing_prices'])>{{ $group['sent_folio'] ? 'Enviada' : 'Enviar' }}</button>
                                <small data-period-folio>{{ $group['sent_folio'] ?: ($group['missing_prices'] ? 'Revisar precios faltantes' : '') }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ht-empty">No hay remisiones de entrega que coincidan con los filtros seleccionados.</td></tr>
                    @endforelse
                    @if ($groups->isNotEmpty())<tr data-period-filter-empty hidden><td colspan="7" class="ht-empty">No hay periodos que coincidan con los filtros seleccionados.</td></tr>@endif
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
        @include('admin.instituciones.conciliacion._period-create')
    </section>
    <script>@include('admin.catalogo-listas.partials.column-filter-script')</script>
</x-admin-layout>
