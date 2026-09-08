<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Etiqueta</title>

    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        .label {
            width: calc(100% - 2pt);
            height: auto;
            margin: 1pt;
            border: 1.2px solid #000;
            padding: 2pt 3pt 2pt;
            overflow: hidden;
            page-break-inside: avoid;
            page-break-before: avoid;
            page-break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            padding: 0;
            vertical-align: top;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 5.1pt;
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
            font-size: 5.45pt;
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

        .meta-left {
            width: 58%;
            padding-right: 0.6pt;
            font-size: 5.2pt;
        }

        .meta-right {
            width: 42%;
            padding-left: 0.3pt;
            text-align: right;
            font-size: 4.7pt;
            line-height: 1.03;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .admin-cell {
            text-align: right;
            font-weight: 700;
            font-size: 4.75pt;
            line-height: 1.02;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
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
            font-size: 4.65pt;
            line-height: 1.03;
        }

        .tiny-text {
            font-size: 4.3pt;
            line-height: 1.02;
        }

        .footer-compact td {
            font-size: 4.45pt;
            line-height: 1.02;
        }

        .title-cell {
            width: 84%;
        }

        .qr-cell {
            width: 16%;
            text-align: right;
            vertical-align: middle;
        }

        .qr-cell img {
            width: 40px;
            height: 40px;
            display: inline-block;
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
        <table>
            <tr>
                <td class="title title-cell">MEZCLAS ESTÉRILES ONCOLÓGICAS</td>
                <td class="qr-cell">
                    @if (!empty($qrImage))
                        <img src="{{ $qrImage }}" alt="QR mezcla">
                    @endif
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="row meta-left">Institución: {{ $cliente ?? $dash }}</td>
                <td class="row meta-right">Lote mezcla: {{ $mezcla->lote ?? $dash }}</td>
            </tr>
            <tr>
                <td class="row meta-left">Paciente: {{ $solicitud->nombre_paciente ?? $dash }}</td>
                <td class="row meta-right">F. Nac: {{ $fmtDate($solicitud->fecha_nacimiento ?? null) }}</td>
            </tr>
            <tr>
                <td class="row meta-left">Edad: {{ $edadTexto }}  Alergias: {{ $solicitud->alergias ?? $dash }}</td>
                <td class="row meta-right">No. Registro: {{ $solicitud->registro_paciente ?? $dash }}</td>
            </tr>
            <tr>
                <td class="row meta-left">Médico: {{ $solicitud->nombre_medico ?? $dash }}</td>
                <td class="row meta-right">Género: {{ $solicitud->sexo ?? $dash }}</td>
            </tr>
        </table>

        <div class="spacer-sm"></div>

        <table>
            <tr>
                <td class="section">Medicamentos:</td>
            </tr>
            @forelse ($medicamentos as $med)
                <tr>
                    <td class="med-line row">
                        {{ $med->nombre ?? $dash }} {{ $fmtMg($med->dosis ?? null) }}
                        @if ($showLabelLotExpiry ?? false)
                            | Lote: {{ $med->lote ?? $dash }} | Cad: {{ $fmtDate($med->cad ?? null) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="med-line row">{{ $dash }}</td>
                </tr>
            @endforelse

            <tr>
                <td class="section">Diluyente:</td>
            </tr>
            <tr>
                <td class="dil-line row">
                    {{ $diluyenteTexto ?? $dash }}
                    @if ($showLabelLotExpiry ?? false)
                        | Lote: {{ $diluyenteLote ?? $dash }} | Cad: {{ $fmtDate($diluyenteCad ?? null) }}
                    @endif
                </td>
            </tr>
        </table>

        <div class="spacer-sm"></div>

        <table>
            <tr>
                <td class="row" style="width: 52%; white-space: nowrap;">
                    Fecha y hora de preparación: {{ $prep ? $prep->format('d/m/Y H:i') : $dash }}
                </td>
                <td class="admin-cell" style="width: 48%;">
                    Administrar en: {{ is_numeric($mezcla->tiempo_infusion ?? null) ? $fmtNum($mezcla->tiempo_infusion, 0, ' min') : $dash }}
                </td>
            </tr>
            <tr>
                <td class="row" style="white-space: nowrap;">
                    Fecha límite de uso: {{ isset($fechaLimiteUso) && $fechaLimiteUso ? $fechaLimiteUso->format('d/m/Y H:i') : $dash }}
                </td>
                <td class="row secondary-text right">
                    Vel. infusión: {{ $velInf !== null ? $fmtNum($velInf, 3, ' mL/min') : $dash }}
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="section">Observaciones:</td>
            </tr>
            <tr>
                <td class="obs-line row">{{ $observacionTexto }}</td>
            </tr>
        </table>

        <div class="spacer-md"></div>

        <table>
            <tr>
                <td class="row secondary-text">Leyenda de protección:</td>
            </tr>
            <tr>
                <td class="legend-line row secondary-text">{{ $legend }}</td>
            </tr>
        </table>

        <div class="spacer-sm"></div>

        <table class="footer-compact">
            <tr>
                <td class="cond-line row" style="width: 60%;">
                    <span class="strong">Condiciones:</span>
                    {{ count($condiciones) ? implode(' | ', $condiciones) : $dash }}
                </td>
                <td class="prep-line row tiny-text right" style="width: 40%; white-space: normal;">
                    Preparada por: {{ $preparadaPor ?? $dash }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
