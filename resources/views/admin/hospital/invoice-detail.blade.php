@php use App\Services\HospitalInvoiceLedger as Ledger; @endphp
<x-admin-layout>
    <section class="hospital-tools ht-invoice-detail" data-invoice-detail>
        <div class="ht-log-heading"><h1 data-workflow-heading>Factura {{ $invoice['folio'] }}</h1><a class="ht-icon" href="{{ route('admin.hospital.herramientas', ['tab' => 'facturacion']) }}" data-workflow-popup-close aria-label="Cerrar detalle" title="Cerrar detalle"><i data-tools-icon="x"></i></a></div>
        <p>Hospital: {{ auth()->user()->hospital?->name }}</p>
        <dl class="ht-history-context">
            @foreach (['Emisión' => $invoice['date']?->format('d/m/Y') ?? 'Sin registrar', 'Vencimiento' => $invoice['due']?->format('d/m/Y') ?? '—', 'Importe MXN' => Ledger::money($invoice['total']), 'Pagos aplicados' => Ledger::money($invoice['paid']), 'Saldo' => Ledger::money($invoice['balance'])] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
        </dl>
        <div class="ht-invoice-detail-actions">@include('admin.hospital._invoice-status')<span>{{ Ledger::CLARIFICATIONS[$invoice['clarification']] ?? 'Sin aclaración' }}</span>@include('admin.hospital._invoice-documents')</div>
        @if ($invoice['account']?->clarification_notes)<p class="ht-history-reason">{{ $invoice['account']->clarification_notes }}</p>@endif
        <h2>Mezclas de la factura</h2>
        <div class="ht-table-scroll"><table class="ht-table ht-invoice-lines"><thead><tr><th>ID mezcla</th><th>No. solicitud</th><th>Paciente</th><th>Importe</th><th data-command-column>Detalle</th></tr></thead><tbody>
            @foreach ($invoice['lines'] as $line)<tr><td>{{ $line['id'] }}<small class="ht-kind">{{ $line['kind'] }}</small></td><td>{{ $line['request_id'] }}</td><td>{{ $line['patient'] }}</td><td>{{ Ledger::money(Ledger::cents($line['billing']->precio_total)) }}</td><td><a class="ht-view" href="{{ $line['url'] }}" target="_blank" rel="noopener">Ver mezcla</a></td></tr>@endforeach
        </tbody></table></div>
        <div class="ht-log-heading ht-payment-heading"><h2>Pagos reportados</h2>@if ($invoice['balance'] > 0)<button type="button" class="ht-primary" data-open-payment>Reportar pago</button>@endif</div>
        <div class="ht-table-scroll"><table class="ht-table ht-invoice-lines"><thead><tr><th>Fecha</th><th>Referencia</th><th>Importe MXN</th><th>Estado</th></tr></thead><tbody>
            @forelse ($invoice['payments'] as $payment)<tr><td>{{ $payment->paid_at->format('d/m/Y') }}</td><td>{{ $payment->reference }}</td><td>{{ Ledger::money(Ledger::cents($payment->amount)) }}</td><td>{{ ['pending' => 'Pendiente de validación', 'approved' => 'Aplicado', 'rejected' => 'Rechazado'][$payment->status] ?? 'Sin validar' }}</td></tr>
            @empty<tr><td colspan="4">Sin pagos reportados.</td></tr>@endforelse
        </tbody></table></div>
        @if ($invoice['balance'] > 0)
        <form class="ht-payment-form" data-payment-form action="{{ route('admin.hospital.facturacion.pago', $invoice['key']) }}" method="POST" @if (!request()->boolean('reportar')) hidden @endif>
            @csrf <input type="hidden" name="submission_key" value="{{ Str::uuid() }}">
            <h2>Reportar pago</h2>
            <div class="ht-payment-fields"><label>Importe MXN<input name="amount" type="number" min="0.01" step="0.01" max="{{ max(0, $invoice['balance'] - $invoice['pending_reports']) / 100 }}" required></label>
                <label>Fecha de pago<input name="paid_at" type="date" max="{{ today()->toDateString() }}" required></label>
                <label>Referencia<input name="reference" maxlength="150" required></label>
            </div>
            <label>Observaciones<textarea name="notes" maxlength="2000" rows="3"></textarea></label>
            <p data-payment-feedback role="status" aria-live="polite" hidden></p>
            <div class="ht-payment-submit"><button type="button" class="ht-secondary" data-cancel-payment>Cancelar</button><button class="ht-primary" type="submit" @disabled($invoice['pending_reports'] >= $invoice['balance'])>Enviar reporte</button></div>
        </form>
        @endif
        <p class="ht-billing-note"><i data-tools-icon="info" aria-hidden="true"></i> Los pagos reportados se aplican al saldo después de su validación.</p>
    </section>
</x-admin-layout>
