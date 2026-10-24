@php
    $preparedBy = $order->prepared_by ?: trim(($order->creator?->name ?? '').' '.($order->creator?->lastname ?? ''));
    $logoSource = public_path('img/promesa-logo.png');
    $items = collect($order->items ?: []);
    if ($items->isEmpty() && $order->details) {
        $items = collect(preg_split('/\r\n|\r|\n/', $order->details))->filter()->values()
            ->map(fn ($line) => ['description' => $line, 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0]);
    }
    $pages = ($items->isEmpty() ? collect([null]) : $items)->chunk(10)->values();
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $money = fn ($value) => '$'.number_format((float) $value, 2, '.', ',');
    $date = fn ($value) => $value?->format('d/m/Y') ?: '-';
    $noteLines = collect(preg_split('/\r\n|\r|\n/', (string) $order->notes))->map(fn ($line) => trim($line))->filter()->values();
    // Large notes flow outside the summary table, so Dompdf can paginate them.
    $extendedNotes = mb_strlen((string) $order->notes) > 900 || $noteLines->count() > 9;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $order->folio }} - Orden de compra</title>
    @include('admin.oncologicos.laboratory.purchase-orders.document-styles')
    <style>
        @page { size: letter portrait; margin: 14pt 18pt; }
        body.po-document { margin: 0; font-size: 8pt; line-height: 1.08; }
        .po-brand-art { width: 180pt; height: 37.1pt; }
        .po-brand-art img { position: relative; width: 268.7pt; height: 201.5pt; left: -49.4pt; top: -79.4pt; }
        .po-document-title { margin-bottom: 4pt; }
        .po-page + .po-page { page-break-before: always; }
        .po-document td, .po-document th { word-wrap: break-word; }
        .po-section-title { page-break-after: avoid; }
        .po-section-box { padding: 3pt; }
        .po-info-table th, .po-info-table td { padding: 1.5pt 4pt; line-height: 1.08; }
        .po-field-value { min-height: 10pt; padding: 2pt 4pt; }
        .po-inline-fields th, .po-inline-fields td { padding: 2pt 3pt; }
        .po-split td { vertical-align: top; }
        .po-split .po-split-right { padding-left: 8pt; }
        .po-products { margin-top: 4pt; }
        .po-products thead { display: table-header-group; }
        .po-document .po-products td { height: 10pt; padding: 1pt 4pt; font-size: 8pt; }
        .po-document .po-products th { padding: 4pt 2pt; }
        .po-bottom { margin-top: 6pt; }
        .po-bottom .po-notes-cell { width: 64%; padding-right: 8pt; vertical-align: top; }
        .po-bottom .po-totals-cell { width: 36%; vertical-align: top; }
        .po-notes-list { font-size: 7.5pt; line-height: 1.1; padding: 2pt 3pt 2pt 11pt; }
        .po-notes-list li { margin: 0 0 1pt; }
        .po-note-line { margin: 3pt 6pt; font-size: 8pt; }
        .po-signature { page-break-inside: avoid; margin-top: 6pt; line-height: 1; }
        .po-signature-name { min-height: 9pt; padding-bottom: 0; }
        .po-signature-label { padding-top: 2pt; font-size: 9pt; }
        .po-page-reference { margin: 6pt 0 0; text-align: right; font-size: 7pt; color: #5c7790; }
    </style>
</head>
<body class="po-document">
@foreach ($pages as $pageIndex => $pageItems)
    <div class="po-page">
        @include('admin.oncologicos.laboratory.purchase-orders.document-header')
        <div class="po-section">
            <h3 class="po-section-title"><span>1. Datos de la orden</span></h3>
            <div class="po-section-box">
                <table class="po-inline-fields"><tr>
                    <th style="width:27%">No. de Orden de Compra:</th><td style="width:31%"><div class="po-field-value">{{ $order->folio }}</div></td>
                    <th style="width:25%">Fecha de elaboración:</th><td><div class="po-field-value">{{ $date($order->requested_at) }}</div></td>
                </tr></table>
                <table class="po-inline-fields"><tr>
                    <td style="width:27%"><strong>Depto solicita:</strong><div class="po-field-value">{{ $order->department }}</div></td>
                    <td style="width:27%"><strong>Central:</strong><div class="po-field-value">{{ $order->deliveryLaboratory?->nombre ?: $order->laboratory?->nombre ?: '-' }}</div></td>
                    <td style="width:23%"><strong>Almacén:</strong><div class="po-field-value">{{ $order->warehouse?->name ?: '-' }}</div></td>
                    <td><strong>Subalmacén:</strong><div class="po-field-value">{{ $order->inventoryDestinationLabel() }}</div></td>
                </tr></table>
            </div>
        </div>
        <div class="po-section">
            <h3 class="po-section-title"><span>2. Proveedor y cotización</span></h3>
            <div class="po-section-box"><table class="po-split"><tr><td style="width:53%">
                <table class="po-info-table">
                    @foreach (['Proveedor:' => $order->supplier, 'RFC:' => $order->supplier_rfc, 'Datos bancarios:' => $order->supplier_bank_details, 'Dirección:' => $order->supplier_address, 'Contacto:' => $order->supplier_contact, 'Teléfono:' => $order->supplier_phone] as $label => $value)
                        <tr><th>{{ $label }}</th><td>{!! nl2br(e($value ?: '-')) !!}</td></tr>
                    @endforeach
                </table>
            </td><td class="po-split-right">
                <table class="po-info-table">
                    @foreach (['No. Cotización:' => $order->quotation_number, 'Tipo:' => $order->order_type, 'E-mail:' => $order->supplier_email, 'Fax:' => $order->supplier_fax] as $label => $value)
                        <tr><th>{{ $label }}</th><td style="height:15pt">{{ $value ?: '-' }}</td></tr>
                    @endforeach
                </table>
            </td></tr></table></div>
        </div>
        <div class="po-section">
            <h3 class="po-section-title"><span>3. Entrega</span></h3>
            <div class="po-section-box"><table class="po-info-table"><tr>
                <th style="width:28%">Fecha de entrega propuesta:</th><td style="width:19%">{{ $date($order->proposed_delivery_at) }}</td>
                <th style="width:37%">Hora de entrega acordada de pedido “Urgente”:</th><td>{{ $order->urgent_delivery_time ?: '-' }}</td>
            </tr></table></div>
        </div>
        <div class="po-section">
            <h3 class="po-section-title"><span>4. Facturación y destino</span></h3>
            <div class="po-section-box"><table class="po-split"><tr><td style="width:59%">
                <table class="po-info-table">
                    @foreach (['Facturar a:' => $order->invoice_to, 'Dirección:' => $order->invoice_address, 'RFC:' => $order->invoice_rfc, 'E-mail facturas:' => $order->invoice_emails] as $label => $value)
                        <tr><th>{{ $label }}</th><td>{!! nl2br(e($value ?: '-')) !!}</td></tr>
                    @endforeach
                </table>
            </td><td class="po-split-right">
                <table class="po-info-table">
                    <tr><th>Entregar en:</th><td>{{ $order->delivery_attention ?: $order->laboratory?->nombre }} / {{ $order->inventoryDestinationLabel() }}</td></tr>
                    <tr><th>Dirección de entrega:</th><td>{!! nl2br(e($order->delivery_address)) !!}</td></tr>
                    <tr><th>Horario:</th><td>{{ $order->delivery_schedule ?: '-' }}</td></tr>
                </table>
            </td></tr></table></div>
        </div>
        <div class="po-section">
            <h3 class="po-section-title"><span>5. Productos</span></h3>
            <table class="po-products">
                <thead><tr><th style="width:8%">Partida</th><th style="width:47%">DESCRIPCIÓN</th><th style="width:12%">CANTIDAD</th><th style="width:14%">PRECIO<br>UNITARIO</th><th style="width:19%">SUBTOTAL POR<br>PARTIDA</th></tr></thead>
                <tbody>
                @foreach (range(0, 9) as $rowIndex)
                    @php($item = $pageItems->values()->get($rowIndex))
                    <tr><td class="po-part">{{ $pageIndex * 10 + $rowIndex + 1 }}</td><td>{{ $item['description'] ?? '' }}</td>
                        <td class="po-center">{{ $item ? $quantity($item['quantity']) : '' }}</td>
                        <td class="po-right">{{ $item ? $money($item['unit_price']) : '' }}</td>
                        <td class="po-right">{{ $item ? $money($item['subtotal'] ?? ((float) $item['quantity'] * (float) $item['unit_price'])) : '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($loop->last)
            <table class="po-bottom"><tr><td class="po-notes-cell">
                @unless ($extendedNotes)
                    <h3 class="po-section-title"><span>6. Observaciones</span></h3>
                    <div class="po-section-box"><ul class="po-notes-list">@forelse ($noteLines as $line)<li>{{ $line }}</li>@empty<li>Sin observaciones.</li>@endforelse</ul></div>
                @endunless
            </td><td class="po-totals-cell">
                <table class="po-totals">
                    <tr><th>SUBTOTAL ORDEN</th><td>{{ $money($order->subtotal) }}</td></tr>
                    <tr><th>DESCUENTO</th><td>{{ $money($order->discount) }}</td></tr>
                    <tr><th>SUBTOTAL CON DESC.</th><td>{{ $money((float) $order->subtotal - (float) $order->discount) }}</td></tr>
                    <tr><th>I.V.A. {{ $quantity($order->tax_rate) }}%</th><td>{{ $money($order->tax_amount) }}</td></tr>
                    <tr class="po-total"><th>TOTAL</th><td>{{ $money($order->total) }}</td></tr>
                </table>
            </td></tr></table>
            @if ($extendedNotes)
                <h3 class="po-section-title"><span>6. Observaciones</span></h3>
                @foreach ($noteLines as $line)<p class="po-note-line">{{ $line }}</p>@endforeach
            @endif
            <div class="po-signature"><div class="po-signature-name">{{ $preparedBy }}</div><div class="po-signature-label">Elaboró.</div></div>
        @else
            <p class="po-page-reference">{{ $order->folio }} · Partidas {{ $pageIndex * 10 + 1 }}-{{ ($pageIndex + 1) * 10 }} · Continúa en la siguiente hoja</p>
        @endif
    </div>
@endforeach
</body>
</html>
