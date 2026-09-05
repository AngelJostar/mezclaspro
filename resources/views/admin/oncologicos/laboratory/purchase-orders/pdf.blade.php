@php
    $preparedBy = $order->prepared_by ?: trim(($order->creator?->name ?? '') . ' ' . ($order->creator?->lastname ?? ''));
    $orderFontRegularPath = 'C:/Windows/Fonts/ARIALN.TTF';
    $orderFontBoldPath = 'C:/Windows/Fonts/ARIALNB.TTF';
    $orderFontRegular = file_exists($orderFontRegularPath)
        ? 'data:font/truetype;base64,' . base64_encode(file_get_contents($orderFontRegularPath))
        : null;
    $orderFontBold = file_exists($orderFontBoldPath)
        ? 'data:font/truetype;base64,' . base64_encode(file_get_contents($orderFontBoldPath))
        : null;
    $notesFontRegularPath = 'C:/Windows/Fonts/calibri.ttf';
    $notesFontBoldPath = 'C:/Windows/Fonts/calibrib.ttf';
    $notesFontRegular = file_exists($notesFontRegularPath)
        ? 'data:font/truetype;base64,' . base64_encode(file_get_contents($notesFontRegularPath))
        : null;
    $notesFontBold = file_exists($notesFontBoldPath)
        ? 'data:font/truetype;base64,' . base64_encode(file_get_contents($notesFontBoldPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $order->folio }} - Orden de compra</title>
    <style>
        @if ($orderFontRegular)
        @font-face {
            font-family: 'Order Arial Narrow';
            font-style: normal;
            font-weight: 400;
            src: url('{!! $orderFontRegular !!}') format('truetype');
        }
        @endif

        @if ($orderFontBold)
        @font-face {
            font-family: 'Order Arial Narrow';
            font-style: normal;
            font-weight: 700;
            src: url('{!! $orderFontBold !!}') format('truetype');
        }
        @endif

        @if ($notesFontRegular)
        @font-face {
            font-family: 'Order Calibri';
            font-style: normal;
            font-weight: 400;
            src: url('{!! $notesFontRegular !!}') format('truetype');
        }
        @endif

        @if ($notesFontBold)
        @font-face {
            font-family: 'Order Calibri';
            font-style: normal;
            font-weight: 700;
            src: url('{!! $notesFontBold !!}') format('truetype');
        }
        @endif

        @page { size: 612pt 792pt; margin: 0; }

        * { box-sizing: border-box; }

        html,
        body {
            width: 612pt;
            margin: 0;
            padding: 0;
            color: #000;
            font-family: 'Order Arial Narrow', 'Arial Narrow', Helvetica, Arial, sans-serif;
        }

        .page {
            position: relative;
            width: 612pt;
            height: 792pt;
            margin: 0;
            overflow: hidden;
            page-break-after: always;
        }

        .page:last-child { page-break-after: auto; }

        .template {
            position: absolute;
            inset: 0;
            width: 612pt;
            height: 792pt;
        }

        .cover,
        .value {
            position: absolute;
        }

        .cover { background: #fff; }

        .value {
            z-index: 2;
            overflow: hidden;
            font-size: 8.98pt;
            line-height: 11.2pt;
            white-space: normal;
        }

        .value.small {
            font-size: 8.02pt;
            line-height: 9.5pt;
        }

        .value.micro {
            font-size: 6.95pt;
            line-height: 8.1pt;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .blue { color: #0000ff; text-decoration: underline; }
        .red { color: #f00000; font-weight: bold; }

        .line-clamp-2 {
            max-height: 20pt;
            overflow: hidden;
        }

        .notes {
            font-family: 'Order Calibri', Calibri, Arial, sans-serif;
            font-size: 6.25pt;
            line-height: 8pt;
        }

        .note-row {
            display: block;
            min-height: 8pt;
            max-height: 16pt;
            overflow: hidden;
            white-space: normal;
        }
    </style>
</head>
<body>
@php
    $templatePath = public_path('img/purchase-orders/oc-template.png');
    $template = file_exists($templatePath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($templatePath))
        : null;

    $items = collect($order->items ?: []);
    if ($items->isEmpty() && $order->details) {
        $items = collect(preg_split('/\r\n|\r|\n/', $order->details))
            ->filter()
            ->values()
            ->map(fn ($line) => [
                'description' => $line,
                'quantity' => 1,
                'unit_price' => 0,
                'subtotal' => 0,
            ]);
    }

    if ($items->isEmpty()) {
        $items = collect([null]);
    }

    $pages = $items->chunk(2)->values();
    $spanishMonths = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    $shortDate = function ($date) use ($spanishMonths) {
        if (!$date) return '-';
        return $date->format('d') . '-' . $spanishMonths[(int) $date->format('n')] . '-' . $date->format('y');
    };

    $longDate = function ($date) use ($spanishMonths) {
        if (!$date) return '-';
        return $date->format('d') . '-' . $spanishMonths[(int) $date->format('n')] . '-' . $date->format('Y');
    };

    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $money = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
    $noteLines = collect(preg_split('/\r\n|\r|\n/', (string) $order->notes))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->take(8)
        ->values();
@endphp

@foreach ($pages as $pageIndex => $pageItems)
    @php
        $isLastPage = $pageIndex === $pages->count() - 1;
    @endphp
    <div class="page">
        @if ($template)
            <img class="template" src="{{ $template }}" alt="">
        @endif

        {{-- Datos generales --}}
        <div class="cover" style="left:165.3pt; top:129.8pt; width:117.2pt; height:11.8pt"></div>
        <div class="value center" style="left:165.3pt; top:130.1pt; width:117.2pt; height:11.4pt">{{ $order->folio }}</div>

        <div class="cover" style="left:460.4pt; top:129.8pt; width:124.1pt; height:11.8pt"></div>
        <div class="value center" style="left:460.4pt; top:130.1pt; width:124.1pt; height:11.4pt">{{ $shortDate($order->requested_at) }}</div>

        <div class="cover" style="left:86.7pt; top:148.6pt; width:497.7pt; height:11.9pt"></div>
        <div class="value small" style="left:87.2pt; top:149.1pt; width:113pt; height:11.2pt">{{ $order->department ?: '-' }}</div>
        <div class="value small" style="left:201pt; top:149.1pt; width:112pt; height:11.2pt"><span class="bold">Central:</span> {{ $order->deliveryLaboratory?->nombre ?: $order->laboratory->nombre }}</div>
        <div class="value small" style="left:314pt; top:149.1pt; width:126pt; height:11.2pt"><span class="bold">Almac&eacute;n:</span> {{ $order->warehouse?->name ?: '-' }}</div>
        <div class="value small" style="left:441pt; top:149.1pt; width:143pt; height:11.2pt"><span class="bold">Subalmac&eacute;n:</span> {{ $order->inventoryDestinationLabel() }}</div>

        {{-- Proveedor --}}
        <div class="cover" style="left:86.7pt; top:167.2pt; width:254pt; height:31.3pt"></div>
        <div class="value line-clamp-2" style="left:87.2pt; top:172.0pt; width:252.6pt">
            {{ $order->supplier }}@if($order->supplier_rfc)<br>RFC: {{ $order->supplier_rfc }}@endif
        </div>

        <div class="cover" style="left:86.7pt; top:199.9pt; width:254pt; height:25.4pt"></div>
        <div class="value small line-clamp-2" style="left:87.2pt; top:199.8pt; width:252.6pt">{!! nl2br(e($order->supplier_bank_details ?: '-')) !!}</div>

        <div class="cover" style="left:86.7pt; top:226.8pt; width:254pt; height:25.4pt"></div>
        <div class="value small line-clamp-2" style="left:87.2pt; top:227.8pt; width:252.6pt">{!! nl2br(e($order->supplier_address ?: '-')) !!}</div>

        <div class="cover" style="left:86.7pt; top:253.7pt; width:254pt; height:24.1pt"></div>
        <div class="value" style="left:87.2pt; top:253.1pt; width:252.6pt; height:24pt">{{ $order->supplier_contact ?: '-' }}<br>{{ $order->supplier_phone ?: '-' }}</div>

        <div class="cover" style="left:461.3pt; top:167.2pt; width:123.2pt; height:29.6pt"></div>
        <div class="value center" style="left:461.3pt; top:175.0pt; width:123.2pt; height:13pt">{{ $order->quotation_number ?: '-' }}</div>

        <div class="cover" style="left:461.3pt; top:198.2pt; width:123.2pt; height:25.4pt"></div>
        <div class="value center" style="left:461.3pt; top:204.6pt; width:123.2pt; height:13pt">{{ $order->order_type ?: '-' }}</div>

        <div class="cover" style="left:461.3pt; top:225.1pt; width:123.2pt; height:25.4pt"></div>
        <div class="value center blue" style="left:461.3pt; top:231.5pt; width:123.2pt; height:13pt">{{ $order->supplier_email ?: '-' }}</div>

        <div class="cover" style="left:461.3pt; top:252.0pt; width:123.2pt; height:25.7pt"></div>
        <div class="value center blue" style="left:461.3pt; top:261.0pt; width:123.2pt; height:13pt">{{ $order->supplier_fax ?: '-' }}</div>

        {{-- Entrega y facturación --}}
        <div class="cover" style="left:165.6pt; top:280.5pt; width:57.7pt; height:13.9pt"></div>
        <div class="value small center" style="left:165.6pt; top:282.7pt; width:57.7pt; height:10pt">{{ $longDate($order->proposed_delivery_at) }}</div>

        <div class="cover" style="left:520.5pt; top:280.5pt; width:63.9pt; height:13.9pt"></div>
        <div class="value center" style="left:520.5pt; top:282.4pt; width:63.9pt; height:10.5pt">{{ $order->urgent_delivery_time ?: '-' }}</div>

        <div class="cover" style="left:86.7pt; top:297.2pt; width:497.7pt; height:11.6pt"></div>
        <div class="value bold" style="left:87.2pt; top:297.3pt; width:496.4pt; height:11.2pt">{{ $order->invoice_to }}</div>

        <div class="cover" style="left:86.7pt; top:310.3pt; width:497.7pt; height:11.6pt"></div>
        <div class="value small" style="left:87.2pt; top:310.5pt; width:496.4pt; height:11.0pt">{{ $order->invoice_address }}</div>

        <div class="cover" style="left:86.7pt; top:323.4pt; width:497.7pt; height:11.6pt"></div>
        <div class="value" style="left:87.2pt; top:323.5pt; width:496.4pt; height:11.0pt">{{ $order->invoice_rfc }}</div>

        <div class="cover" style="left:86.7pt; top:336.5pt; width:497.7pt; height:11.6pt"></div>
        <div class="value small" style="left:87.2pt; top:336.7pt; width:496.4pt; height:11.0pt"><span class="bold">e-mail facturas:</span> {{ $order->invoice_emails ?: '-' }}</div>

        <div class="cover" style="left:86.7pt; top:349.5pt; width:497.7pt; height:22.7pt"></div>
        <div class="value small" style="left:87.2pt; top:349.3pt; width:496.4pt; height:22.5pt">
            <span class="bold">{{ $order->delivery_attention ?: $order->laboratory->nombre }} / {{ $order->inventoryDestinationLabel() }}</span><br>
            {{ $order->delivery_address }}@if($order->delivery_schedule) <span class="bold">Horario:</span> {{ $order->delivery_schedule }}@endif
        </div>

        {{-- Partidas: la plantilla original dispone exactamente de dos renglones por hoja. --}}
        @foreach ([0, 1] as $rowIndex)
            @php
                $item = $pageItems->get($rowIndex);
                $itemNumber = ($pageIndex * 2) + $rowIndex + 1;
                $rowTop = $rowIndex === 0 ? 420.9 : 442.9;
                $rowHeight = $rowIndex === 0 ? 20.5 : 19.6;
            @endphp

            <div class="cover" style="left:19.0pt; top:{{ $rowTop }}pt; width:65.9pt; height:{{ $rowHeight }}pt"></div>
            <div class="cover" style="left:86.8pt; top:{{ $rowTop }}pt; width:312.9pt; height:{{ $rowHeight }}pt"></div>
            <div class="cover" style="left:401.3pt; top:{{ $rowTop }}pt; width:57.7pt; height:{{ $rowHeight }}pt"></div>
            <div class="cover" style="left:460.5pt; top:{{ $rowTop }}pt; width:57.6pt; height:{{ $rowHeight }}pt"></div>
            <div class="cover" style="left:520.6pt; top:{{ $rowTop }}pt; width:62.8pt; height:{{ $rowHeight }}pt"></div>

            @if ($item)
                <div class="value center" style="left:19.0pt; top:{{ $rowTop + 4.6 }}pt; width:65.9pt; height:11pt">{{ $itemNumber }}</div>
                <div class="value small" style="left:87.2pt; top:{{ $rowTop + 4.4 }}pt; width:312.1pt; height:11pt">{{ $item['description'] }}</div>
                <div class="value center bold" style="left:401.3pt; top:{{ $rowTop + 4.4 }}pt; width:57.7pt; height:11pt">{{ $quantity($item['quantity']) }}</div>
                <div class="value right" style="left:460.5pt; top:{{ $rowTop + 4.4 }}pt; width:55.7pt; height:11pt">{{ $money($item['unit_price']) }}</div>
                <div class="value right" style="left:520.6pt; top:{{ $rowTop + 4.4 }}pt; width:61.0pt; height:11pt">{{ $money($item['subtotal'] ?? ((float) $item['quantity'] * (float) $item['unit_price'])) }}</div>
            @endif
        @endforeach

        {{-- Importes --}}
        @php
            $amounts = $isLastPage
                ? [
                    $money($order->subtotal),
                    $money($order->discount),
                    $money((float) $order->subtotal - (float) $order->discount),
                    $money($order->tax_amount),
                    $money($order->total),
                ]
                : ['', '', '', '', ''];
            $amountTops = [463.9, 477.0, 490.1, 503.2, 516.3];
        @endphp
        @foreach ($amounts as $amountIndex => $amount)
            <div class="cover" style="left:520.6pt; top:{{ $amountTops[$amountIndex] }}pt; width:62.8pt; height:11.7pt"></div>
            @if ($amount !== '')
                <div class="value right {{ $amountIndex === 4 ? 'bold' : '' }}" style="left:520.6pt; top:{{ $amountTops[$amountIndex] + 0.6 }}pt; width:61.0pt; height:10.8pt">{{ $amount }}</div>
            @endif
        @endforeach

        {{-- Observaciones --}}
        <div class="cover" style="left:165.2pt; top:582.3pt; width:294.8pt; height:64.6pt"></div>
        @if ($isLastPage)
            <div class="value notes" style="left:166.0pt; top:582.6pt; width:293.2pt; height:63.5pt">
                @forelse ($noteLines as $noteIndex => $line)
                    @php
                        $openingParenthesis = strpos($line, '(');
                        $colon = strpos($line, ':');
                    @endphp
                    <span class="note-row">
                        @if ($noteIndex === 0 && $openingParenthesis !== false)
                            <span class="red">* {{ trim(substr($line, 0, $openingParenthesis)) }}</span>
                            {{ substr($line, $openingParenthesis) }}
                        @elseif (str_starts_with(mb_strtoupper($line), 'PLAZO DE PAGO:') && $colon !== false)
                            * {{ substr($line, 0, $colon + 1) }} <strong>{{ trim(substr($line, $colon + 1)) }}</strong>
                        @else
                            * {{ $line }}
                        @endif
                    </span>
                @empty
                    <span class="note-row">Sin observaciones.</span>
                @endforelse
            </div>
        @endif

        @if ($isLastPage && $preparedBy)
            <div class="value small center" style="left:220pt; top:715pt; width:172pt; height:12pt">{{ $preparedBy }}</div>
        @endif
    </div>
@endforeach
</body>
</html>
