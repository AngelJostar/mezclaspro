@php use App\Services\HospitalInvoiceLedger as Ledger; @endphp
<div class="ht-log-heading ht-invoice-heading">
    <div><h2>Seguimiento de facturación</h2><p>Hospital: {{ auth()->user()->hospital?->name }}</p></div>
    <small>Importes en MXN</small>
</div>
<div class="ht-invoice-summary">
    @foreach (['total' => ['Total facturado', 'file-text', 'blue'], 'paid' => ['Pagos aplicados', 'check', 'green'], 'balance' => ['Saldo pendiente', 'clock', 'amber'], 'overdue' => ['Saldo vencido', 'circle-alert', 'red']] as $key => [$label, $icon, $color])
        <article class="ht-invoice-metric ht-metric-{{ $color }}"><span><i data-tools-icon="{{ $icon }}" aria-hidden="true"></i></span><div><h3>{{ $label }}</h3><strong>{{ Ledger::money($summary[$key]) }}</strong></div></article>
    @endforeach
</div>
@if ($summary['incomplete'])<p class="ht-error">{{ $summary['incomplete'] }} factura(s) sin importe registrado; sus importes no están incluidos en los totales.</p>@endif
<div class="ht-invoice-toolbar">
    <label class="ht-clarification">Estado de pago:
        <select name="pago_estado" form="tools-filters" data-invoice-filter>
        @foreach (Ledger::STATES as $key => $label)
            <option value="{{ $key }}" @selected($filterQuery['pago_estado'] === $key)>{{ $label }} ({{ $counts[$key] }})</option>
        @endforeach
        </select>
    </label>
    <label class="ht-clarification">Aclaraciones:
        <select name="aclaracion" form="tools-filters" data-invoice-filter>
            @foreach (['all' => 'Todas'] + Ledger::CLARIFICATIONS as $key => $label)<option value="{{ $key }}" @selected($filterQuery['aclaracion'] === $key)>{{ $label }}</option>@endforeach
        </select>
    </label>
    <div class="ht-invoice-search"><input type="search" name="folio" value="{{ $filterQuery['folio'] }}" form="tools-filters" placeholder="Buscar folio de factura" aria-label="Buscar folio de factura" maxlength="150"><button type="submit" form="tools-filters" class="ht-icon" aria-label="Buscar factura" title="Buscar factura"><i data-tools-icon="search"></i></button></div>
    <a class="ht-primary ht-invoice-statement" href="{{ route('admin.hospital.facturacion.estado-cuenta', $filterQuery) }}"><i data-tools-icon="file-text" aria-hidden="true"></i> Estado de cuenta</a>
    <a class="ht-export" href="{{ route('admin.hospital.facturacion.exportar', $filterQuery) }}"><i data-tools-icon="file-spreadsheet" aria-hidden="true"></i> Exportar a Excel</a>
</div>
<div class="ht-table-scroll" tabindex="0" aria-label="Facturas del hospital">
    <table class="ht-table ht-invoice-table">
        <thead><tr><th>Factura</th><th>Emisión</th><th>Vencimiento</th><th>Importe</th><th>Pagos aplicados</th><th>Saldo</th><th>Estado de pago</th><th>Aclaración</th><th data-command-column>Documentos</th><th data-command-column>Acciones</th></tr></thead>
        <tbody>
            @forelse ($rows as $invoice)
                <tr>
                    <td>{{ $invoice['folio'] }}</td><td>{{ $invoice['date']?->format('d/m/Y') ?? 'Sin registrar' }}</td><td>{{ $invoice['due']?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ Ledger::money($invoice['total']) }}</td><td>{{ Ledger::money($invoice['paid']) }}</td><td>{{ Ledger::money($invoice['balance']) }}</td>
                    <td>@include('admin.hospital._invoice-status') @if ($invoice['overdue'])<small class="ht-overdue">{{ $invoice['days_overdue'] }} {{ $invoice['days_overdue'] === 1 ? 'día vencida' : 'días vencida' }}</small>@endif</td>
                    <td><span class="ht-invoice-status ht-clarification-{{ $invoice['clarification'] }}">{{ Ledger::CLARIFICATIONS[$invoice['clarification']] ?? 'Sin aclaración' }}</span></td>
                    <td><div class="ht-invoice-documents">@include('admin.hospital._invoice-documents')</div></td>
                    <td><div class="ht-invoice-actions"><a class="ht-primary" href="{{ route('admin.hospital.facturacion.detalle', ['invoice' => $invoice['key'], 'approval_popup' => 1]) }}" data-approval-popup aria-label="Ver detalle {{ $invoice['folio'] }}">Ver detalle</a>
                        @if ($invoice['balance'] > 0)<a class="ht-secondary" href="{{ route('admin.hospital.facturacion.detalle', ['invoice' => $invoice['key'], 'approval_popup' => 1, 'reportar' => 1]) }}" data-approval-popup aria-label="Reportar pago {{ $invoice['folio'] }}">Reportar pago</a>@endif
                    </div></td>
                </tr>
            @empty <tr><td colspan="10" class="ht-empty">No hay facturas que coincidan con los filtros.</td></tr> @endforelse
        </tbody>
    </table>
</div>
