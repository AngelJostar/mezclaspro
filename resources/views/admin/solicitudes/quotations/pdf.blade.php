<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizaci&oacute;n {{ $folio }}</title>
    <style>
        @page { margin: 30px 32px 48px; }
        body { font-family: DejaVu Sans, sans-serif; color: #15354e; font-size: 10px; line-height: 1.45; }
        .brand { border-bottom: 3px solid #09877d; padding-bottom: 12px; margin-bottom: 16px; }
        .quotation-brand { display: table; width: 100%; }
        .quotation-brand-logo-cell { display: table-cell; width: 208px; vertical-align: middle; }
        .quotation-brand-logo { position: relative; width: 190px; height: 39.2px; overflow: hidden; }
        .quotation-brand-logo img { position: absolute; width: 283.63px; height: 212.73px; left: -52.1px; top: -83.89px; }
        .quotation-brand-details { display: table-cell; vertical-align: middle; font-size: 9px; line-height: 1.45; }
        .quotation-brand-details strong { display: block; font-size: 12px; margin-bottom: 3px; }
        .quotation-brand-details p { margin: 2px 0 0; }
        .recipient { width: 100%; margin-bottom: 18px; }
        .recipient td { vertical-align: top; }
        .recipient-name { font-size: 14px; font-weight: bold; }
        .muted { color: #60748a; font-size: 9px; }
        .eyebrow { color: #008b86; font-weight: bold; margin: 0 0 5px; }
        .right { text-align: right; }
        .intro { margin: 0 0 14px; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items thead { display: table-header-group; }
        .items th { background: #15354b; color: #fff; font-size: 9px; padding: 9px 5px; }
        .items td { border: 1px solid #d0dfe7; padding: 10px 5px; vertical-align: middle; overflow-wrap: break-word; }
        .items tr { page-break-inside: avoid; }
        .items tbody tr:nth-child(even) { background: #f0f6f8; }
        .center { text-align: center; }
        .description { text-align: left; }
        .amount { white-space: nowrap; }
        .summary { page-break-inside: avoid; margin-top: 16px; }
        .tax { text-align: right; margin: 0 0 4px; }
        .total { width: 100%; background: #f0f6f8; }
        .total td { padding: 11px 9px; font-weight: bold; }
        .total .right { color: #008b86; font-size: 16px; }
        .words { margin: 5px 0 0; font-size: 9px; color: #60748a; }
        .considerations { margin-top: 22px; }
        .considerations p { margin: 0 0 7px; }
        .signature { margin-top: 24px; page-break-inside: avoid; }
        footer { position: fixed; bottom: -28px; left: 0; right: 0; border-top: 1px solid #d0dfe7; padding-top: 7px; font-size: 8px; color: #60748a; }
        .page { float: right; }
        .page:after { content: counter(page); }
    </style>
</head>
<body>
@php
    $money = fn ($value, $decimals = 2) => $value === null ? 'Sin registrar' : '$'.number_format((float) $value, $decimals);
    $quantity = fn ($value) => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
@endphp
<footer>{{ $issuer['brand'] }} | {{ $folio }}<span class="page">P&aacute;gina </span></footer>
<div class="brand">@include('admin.solicitudes.quotations._brand', ['issuer' => $issuer])</div>
<table class="recipient" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:58%">
            <div class="eyebrow">DIRIGIDA A</div>
            <div class="recipient-name">{{ $hospital }}</div>
            <div class="muted">{{ $institution }}</div>
        </td>
        <td class="right">
            <div>{{ $document['date'] }}</div>
            <div class="muted">Moneda: pesos mexicanos (MXN)</div>
            <strong>Folio: {{ $folio }}</strong>
        </td>
    </tr>
</table>
<p class="intro">Por medio de la presente, ponemos a su consideraci&oacute;n la siguiente cotizaci&oacute;n:</p>
<table class="items">
    <thead><tr>
        <th style="width:10%">Cantidad</th>
        <th style="width:10%">Unidad</th>
        <th class="description" style="width:39%">Descripci&oacute;n</th>
        <th class="right" style="width:20%">Precio unitario</th>
        <th class="right" style="width:21%">Importe</th>
    </tr></thead>
    <tbody>
    @forelse ($lines as $line)
        <tr>
            <td class="center">{{ $quantity($line['quantity']) }}</td>
            <td class="center">{{ $line['unit'] }}</td>
            <td class="description">
                @if ($line['mixture_number'])<div class="muted">Mezcla {{ $line['mixture_number'] }}</div>@endif
                <strong>{{ $line['description'] }}</strong>
                @if ($line['presentation'] !== '')<div class="muted">{{ $line['presentation'] }}</div>@endif
                @if ($line['vat'] > 0)<div class="muted">IVA incluido: {{ $money($line['vat']) }}</div>@endif
            </td>
            <td class="right amount">{{ $money($line['unit_price'], round((float) $line['unit_price'], 2) == $line['unit_price'] ? 2 : 4) }}</td>
            <td class="right amount"><strong>{{ $money($line['total']) }}</strong></td>
        </tr>
    @empty
        <tr><td colspan="5">Sin desglose registrado.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="summary">
    @if ($vat > 0 && $total !== null)
        <p class="tax">Subtotal: {{ $money((float) $total - $vat) }} &nbsp; | &nbsp; IVA: {{ $money($vat) }}</p>
    @endif
    <table class="total" cellspacing="0"><tr>
        <td>TOTAL COTIZADO</td><td class="right">{{ $money($total) }}{{ $total !== null ? ' MXN' : '' }}</td>
    </tr></table>
    @if ($total !== null && $document['total_in_words'])<p class="words">{{ $document['total_in_words'] }}</p>@endif
</div>
<div class="considerations">
    <p class="eyebrow">CONSIDERACIONES</p>
    @foreach ($lines as $line)
        @if ($line['unit'] === 'Servicio')
            <p>@if ($line['mixture_number'])Mezcla {{ $line['mixture_number'] }}: @endif{{ $line['description'] }}: {{ $money($line['total']) }} MXN.@if ($line['vat'] > 0) IVA incluido.@endif</p>
        @endif
    @endforeach
    @if ($vat > 0)<p>Los importes y el total cotizado incluyen el IVA desglosado.</p>@endif
    <p>Vigencia y condiciones de pago y entrega: por confirmar.</p>
</div>
<div class="signature">Atentamente,<br><strong>{{ $issuer['brand'] }}</strong></div>
</body>
</html>
