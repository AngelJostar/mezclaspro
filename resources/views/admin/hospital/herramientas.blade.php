<x-admin-layout>
    <section class="hospital-tools" data-hospital-tools>
        <h1>Herramientas</h1>
        @php
            $tabs = ['conciliacion' => ['Conciliación', 'clipboard-list'], 'facturacion' => ['Facturación', 'file-text'], 'ajustes' => ['Bitácora de ajustes', 'settings']];
            $query = ['periodo' => $period, 'desde' => $from, 'hasta' => $to];
        @endphp
        <nav class="ht-tabs" aria-label="Herramientas del hospital">
            <button type="button" class="ht-icon ht-round" data-tab-scroll="-1" aria-label="Pestañas anteriores" title="Pestañas anteriores"><i data-tools-icon="chevron-left"></i></button>
            <div class="ht-tabs-scroll">
                @foreach ($tabs as $key => [$label, $icon])
                    <a href="{{ route('admin.hospital.herramientas', array_merge($key === $tab ? $filterQuery : $query, ['tab' => $key])) }}" @if ($tab === $key) aria-current="page" @endif>
                        <span class="ht-tab-icon"><i data-tools-icon="{{ $icon }}" aria-hidden="true"></i></span>
                        <span>{{ $label }}@if ($tab === $key)<small><i data-tools-icon="check" aria-hidden="true"></i> Seleccionada</small>@endif</span>
                    </a>
                @endforeach
            </div>
            <button type="button" class="ht-icon ht-round" data-tab-scroll="1" aria-label="Pestañas siguientes" title="Pestañas siguientes"><i data-tools-icon="chevron-right"></i></button>
        </nav>
        @if ($tab === 'conciliacion')
            <nav class="ht-quick-filters" aria-label="Filtros de conciliación">
                @foreach (['todas' => 'Todas', 'conciliables' => 'Conciliables', 'no_conciliables' => 'No conciliables'] as $key => $label)
                    <a href="{{ route('admin.hospital.herramientas', array_merge($filterQuery, ['conciliacion_estado' => $key])) }}" @if ($filterQuery['conciliacion_estado'] === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        @elseif ($tab === 'facturacion')
            <nav class="ht-quick-filters" aria-label="Filtros de facturación">
                @foreach (\App\Services\HospitalInvoiceLedger::DOCUMENT_STATES as $key => $label)
                    <a href="{{ route('admin.hospital.herramientas', array_merge($filterQuery, ['tramite_estado' => $key])) }}" @if ($filterQuery['tramite_estado'] === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        @endif
        <form id="tools-filters" method="GET" action="{{ route('admin.hospital.herramientas') }}" class="ht-filters" data-tools-filters>
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if ($tab === 'conciliacion')<input type="hidden" name="conciliacion_estado" value="{{ $filterQuery['conciliacion_estado'] }}">@endif
            @if (in_array($tab, ['conciliacion', 'ajustes']))
                @php($tableFilters = $tab === 'ajustes' ? $adjustmentTable : $conciliation)
                <input type="hidden" name="orden" value="{{ $tableFilters['sort'] }}"><input type="hidden" name="direccion" value="{{ $tableFilters['direction'] }}">
                @foreach ($tableFilters['selected'] as $field => $values) @foreach ($values as $value)<input type="hidden" name="columnas[{{ $field }}][]" value="{{ $value }}">@endforeach @endforeach
            @endif
            @if ($tab === 'ajustes')<input type="hidden" name="ajuste_estado" value="{{ $adjustmentState }}">@endif
            @if ($tab === 'facturacion')<input type="hidden" name="tramite_estado" value="{{ $filterQuery['tramite_estado'] }}">@endif
            <fieldset class="ht-period"><legend class="sr-only">Periodo</legend><span aria-hidden="true">{{ $tab === 'facturacion' ? 'Fecha de emisión' : 'Periodo' }}</span>
                <div class="ht-segments">
                    @foreach (['dia' => 'Día', 'mes' => 'Mes', 'anio' => 'Año'] as $value => $label)
                        <label><input type="radio" name="periodo" value="{{ $value }}" @checked($period === $value)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            @foreach (['desde' => ['Desde', $from], 'hasta' => ['Hasta', $to]] as $name => [$label, $value])
                <div class="ht-date-field"><label for="tools-{{ $name }}">{{ $label }}</label><div class="ht-date-input">
                    <input id="tools-{{ $name }}" type="date" name="{{ $name }}" value="{{ $value }}" @disabled($allHistory)>
                    <button type="button" class="ht-icon" data-open-range="{{ $name }}" aria-label="Seleccionar rango: {{ strtolower($label) }}" title="Seleccionar rango" aria-controls="tools-range" aria-expanded="false" @disabled($allHistory)><i data-tools-icon="calendar-days"></i></button>
                </div></div>
            @endforeach
            @if ($tab === 'ajustes')
                <input type="hidden" name="todo_historial" value="0">
                <label class="ht-all-history"><input type="checkbox" name="todo_historial" value="1" data-all-history @checked($allHistory)> Todo el historial</label>
            @endif
            <button class="ht-primary" type="submit">Aplicar filtros</button>
            <a class="ht-clear" href="{{ route('admin.hospital.herramientas', ['tab' => $tab, 'periodo' => 'dia', 'desde' => '', 'hasta' => '']) }}">Limpiar</a>
            @if ($tab === 'conciliacion')
                <div class="ht-report-actions">
                    <button type="submit" class="ht-export" formaction="{{ route('admin.hospital.conciliacion.exportar') }}" title="Descargar reporte de conciliación del periodo en Excel"><i data-tools-icon="file-spreadsheet" aria-hidden="true"></i> Descargar reporte</button>
                    <button type="button" class="ht-primary" data-send-conciliation aria-haspopup="dialog"><i data-tools-icon="send" aria-hidden="true"></i> Enviar a proveedor</button>
                </div>
            @endif
            @if ($tab === 'facturacion')<span class="ht-cutoff">Corte: {{ now()->format('d/m/Y') }}</span>@endif
        </form>
        @if ($errors->any())<p role="alert" class="ht-error">{{ $errors->first() }}</p>@endif
        <p data-tools-status role="status" aria-live="polite" class="ht-feedback" hidden></p>
        @if ($tab === 'conciliacion')
            @include('admin.hospital._conciliation-send')
        @endif

        <div id="tools-range" class="ht-calendar" role="dialog" aria-modal="true" aria-labelledby="tools-range-title" hidden>
            <h2 id="tools-range-title">Seleccionar rango</h2>
            <div class="ht-calendar-nav">
                <button type="button" class="ht-icon ht-round" data-calendar-step="-1" aria-label="Mes anterior" title="Mes anterior"><i data-tools-icon="chevron-left"></i></button>
                <input type="month" data-calendar-month aria-label="Mes del calendario">
                <button type="button" class="ht-icon ht-round" data-calendar-step="1" aria-label="Mes siguiente" title="Mes siguiente"><i data-tools-icon="chevron-right"></i></button>
            </div>
            <div class="ht-weekdays" aria-hidden="true">@foreach (['L','M','M','J','V','S','D'] as $day)<span>{{ $day }}</span>@endforeach</div>
            <div class="ht-days" data-calendar-days role="group" aria-label="Días del mes"></div>
            <div class="ht-calendar-footer"><output data-range-summary aria-live="polite"></output><div>
                <button type="button" class="ht-secondary" data-cancel-range>Cancelar</button>
                <button type="button" class="ht-primary" data-apply-range>Aplicar rango</button>
            </div></div>
        </div>

        @if ($tab === 'ajustes')
            @include('admin.hospital._adjustment-log')
        @elseif ($tab === 'facturacion')
            @include('admin.hospital._invoices')
        @else
            @include('admin.hospital._conciliation')
        @endif
        <footer class="ht-pagination"><span>{{ $rows->total() ? 'Mostrando '.$rows->firstItem().' a '.$rows->lastItem().' de '.$rows->total() : '0' }} {{ $tab === 'ajustes' ? 'mezclas con ajustes' : ($tab === 'facturacion' ? 'facturas' : 'mezclas') }}</span>
            <nav aria-label="Paginación de herramientas">
                @if ($rows->onFirstPage())<button class="ht-icon" disabled aria-label="Página anterior"><i data-tools-icon="chevron-left"></i></button>@else<a class="ht-icon" href="{{ $rows->previousPageUrl() }}" aria-label="Página anterior"><i data-tools-icon="chevron-left"></i></a>@endif
                <span aria-current="page">{{ $rows->currentPage() }}</span>
                @if ($rows->hasMorePages())<a class="ht-icon" href="{{ $rows->nextPageUrl() }}" aria-label="Página siguiente"><i data-tools-icon="chevron-right"></i></a>@else<button class="ht-icon" disabled aria-label="Página siguiente"><i data-tools-icon="chevron-right"></i></button>@endif
            </nav>
        </footer>
        @if ($tab === 'facturacion')<p class="ht-billing-note"><i data-tools-icon="info" aria-hidden="true"></i> Los pagos reportados se aplican al saldo después de su validación.</p>@endif
    </section>
</x-admin-layout>
