<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Etiqueta</title>

    <style>
        @page {
            size: 7.5cm 5cm;
            margin: 0;
        }

        html,
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        .label {
            position: absolute;
            top: 0;
            left: 0;
            width: 7.5cm;
            height: 4.98cm;
            margin: 0;
            border: 0;
            padding: 2pt 3pt;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        td {
            padding: 0;
            vertical-align: top;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 4pt;
            line-height: 1.06;
            color: #000;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 6pt;
            line-height: 1;
            padding-bottom: 0.5pt;
        }

        .header-table {
            margin-bottom: 7pt;
        }

        .row {
            padding-bottom: 0.2pt;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .section {
            font-weight: 700;
            font-size: inherit;
            padding-top: 0.45pt;
        }

        .strong {
            font-weight: 700;
        }

        .spacer-sm {
            height: 0.2pt;
        }

        .spacer-md {
            height: 0.45pt;
        }

        .meta-main {
            font-size: inherit;
            line-height: 1.04;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        .meta-side,
        .admin-cell {
            text-align: right;
            font-size: inherit;
            line-height: 1.02;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .meta-side {
            padding-left: 1pt;
        }

        .main-columns {
            margin-top: 0.3pt;
        }

        .column-left {
            width: 56%;
            padding-right: 1.5pt;
        }

        .column-right {
            width: 44%;
            padding-left: 1.5pt;
        }

        .column-left .row,
        .column-left .med-line,
        .column-left .dil-line,
        .column-left .obs-line,
        .column-left .legend-line,
        .column-left .cond-line {
            font-size: inherit;
            line-height: 1.05;
        }

        .column-right .row {
            font-size: inherit;
            line-height: 1.04;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .med-line,
        .dil-line,
        .obs-line,
        .legend-line,
        .cond-line,
        .prep-line {
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .secondary-text {
            font-size: inherit;
            line-height: 1.03;
        }

        .tiny-text {
            font-size: inherit;
            line-height: 1.02;
        }

        .footer-compact td {
            font-size: inherit;
            line-height: 1.02;
        }

        .title-cell {
            width: 100%;
        }

        .qr-corner {
            position: absolute;
            right: 30pt;
            bottom: 20pt;
            width: 42px;
            height: 42px;
        }

        .qr-corner img {
            width: 42px;
            height: 42px;
            display: block;
        }
    </style>
</head>

@php
    use Carbon\Carbon;

    $dash = '—';

    $fmtDate = function ($value) use ($dash) {
        if (!$value) {
            return $dash;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return $dash;
        }
    };

    $fmtNum = function ($value, $decimals = 2, $suffix = '') use ($dash) {
        if (!is_numeric($value)) {
            return $dash;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.');

        return $suffix !== '' ? $formatted . $suffix : $formatted;
    };

    $fmtMg = function ($value) use ($fmtNum, $dash) {
        if (!is_numeric($value)) {
            return $dash;
        }

        return $fmtNum($value, 2, ' mg');
    };

    $edadTexto = $dash;
    if (!empty($solicitud->fecha_nacimiento)) {
        try {
            $fnac = Carbon::parse($solicitud->fecha_nacimiento);
            $hoy = Carbon::now();
            $diff = $fnac->diff($hoy);

            if ($diff->y > 0) {
                $edadTexto = $diff->y . ' años';
            } elseif ($diff->m > 0) {
                $edadTexto = $diff->m . ' meses';
            } else {
                $edadTexto = $diff->d . ' días';
            }
        } catch (\Throwable $e) {
            $edadTexto = $dash;
        }
    }

    $prep = $fechaPreparacion
        ? $fechaPreparacion
        : (!empty($aprobada?->fecha_hora_preparacion)
            ? Carbon::parse($aprobada->fecha_hora_preparacion)
            : null);

    $legend = $legendEtiqueta ?: $dash;
    $tempMin = $tempMinEtiqueta;
    $tempMax = $tempMaxEtiqueta;
    $stability = $stabilityEtiqueta;
    $observacionTexto = !empty($observaciones) ? trim((string) $observaciones) : $dash;

    $volumen = is_numeric($mezcla->volumen_dilucion ?? null) ? (float) $mezcla->volumen_dilucion : null;
    $minutos = is_numeric($mezcla->tiempo_infusion ?? null) ? (float) $mezcla->tiempo_infusion : null;
    $velInf = $volumen !== null && $minutos !== null && $minutos > 0 ? $volumen / $minutos : null;

    $condiciones = [];
    if ($tempMin !== null || $tempMax !== null) {
        $condiciones[] = 'Temp. ' . ($tempMin !== null ? $tempMin : $dash) . '–' . ($tempMax !== null ? $tempMax : $dash) . ' °C';
    }
    if ($stability) {
        $condiciones[] = 'Estabilidad ' . $stability . ' h';
    }
@endphp

<body>
    <div class="label">
        <table class="header-table">
            <tr>
                <td class="title title-cell">MEZCLAS ESTÉRILES ONCOLÓGICAS</td>
            </tr>
        </table>

        @if (!empty($qrImage))
            <div class="qr-corner"><img src="{{ $qrImage }}" alt="QR mezcla"></div>
        @endif

        <table class="main-columns">
            <colgroup><col style="width: 56%;"><col style="width: 44%;"></colgroup>
            <tr>
                <td class="column-left">
                    <table>
                        <tr><td class="row"><strong>Institución:</strong> {{ $cliente ?? $dash }}</td></tr>
                        <tr><td class="row"><strong>Paciente:</strong> {{ $solicitud->nombre_paciente ?? $dash }}</td></tr>
                        <tr><td class="row"><strong>Edad:</strong> {{ $edadTexto }} · <strong>Alergias:</strong> {{ $solicitud->alergias ?? $dash }}</td></tr>
                        <tr><td class="row"><strong>Médico:</strong> {{ $solicitud->nombre_medico ?? $dash }}</td></tr>
                        <tr><td class="section">Medicamentos:</td></tr>
                        @forelse ($medicamentos as $med)
                            <tr><td class="med-line row">{{ $med->nombre ?? $dash }} {{ $fmtMg($med->dosis ?? null) }}@if ($showLabelLotExpiry ?? false) | Lote: {{ $med->lote ?? $dash }} | Cad: {{ $fmtDate($med->cad ?? null) }}@endif</td></tr>
                        @empty
                            <tr><td class="med-line row">{{ $dash }}</td></tr>
                        @endforelse
                        <tr><td class="section">Diluyente:</td></tr>
                        <tr><td class="dil-line row">{{ $diluyenteTexto ?? $dash }}@if ($showLabelLotExpiry ?? false) | Lote: {{ $diluyenteLote ?? $dash }} | Cad: {{ $fmtDate($diluyenteCad ?? null) }}@endif</td></tr>
                        <tr><td class="section">Observaciones:</td></tr>
                        <tr><td class="obs-line row">{{ $observacionTexto }}</td></tr>
                        <tr><td class="row secondary-text"><strong>Leyenda de protección:</strong></td></tr>
                        <tr><td class="legend-line row secondary-text">{{ $legend }}</td></tr>
                        <tr><td class="cond-line row"><span class="strong">Condiciones:</span> {{ count($condiciones) ? implode(' | ', $condiciones) : $dash }}</td></tr>
                    </table>
                </td>
                <td class="column-right">
                    <table>
                        <tr><td class="row"><span class="strong">Lote mezcla:</span> {{ $mezcla->lote ?? $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">F. Nac:</span> {{ $fmtDate($solicitud->fecha_nacimiento ?? null) }}</td></tr>
                        <tr><td class="row"><span class="strong">No. Registro:</span> {{ $solicitud->registro_paciente ?? $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Género:</span> {{ $solicitud->sexo ?? $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Preparación:</span> {{ $prep ? $prep->format('d/m/Y H:i') : $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Límite de uso:</span> {{ isset($fechaLimiteUso) && $fechaLimiteUso ? $fechaLimiteUso->format('d/m/Y H:i') : $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Administrar en:</span> {{ is_numeric($mezcla->tiempo_infusion ?? null) ? $fmtNum($mezcla->tiempo_infusion, 0, ' min') : $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Vel. infusión:</span> {{ $velInf !== null ? $fmtNum($velInf, 3, ' mL/min') : $dash }}</td></tr>
                        <tr><td class="row"><span class="strong">Preparada por:</span> {{ $preparadaPor ?? $dash }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
