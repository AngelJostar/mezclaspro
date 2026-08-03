@php
    function ajustarUnidad($unidad, $npt)
    {
        if ($npt === 'ADULT') {
            if ($unidad === 'g/Kg') {
                return 'g/día';
            }
            if ($unidad === 'mEq/Kg') {
                return 'mEq/día';
            }
        }

        return $unidad;
    }

    function nombreGenericoEtiqueta($item)
    {
        return $item?->presentation?->catalog?->denominacion_generica ??
            ($item?->input?->description ?? 'Medicamento no disponible');
    }

    function osmolaridadEtiqueta($item)
    {
        return (float) ($item?->presentation?->catalog?->osmolaridad ?? 0);
    }

    $sumaDosisDeGlucosa = $inputs_solicitud
        ->filter(fn($input) => isset($input['input']['category_id']) && $input['input']['category_id'] == 2)
        ->sum('valor');

    $sumaDosisDeAA = $inputs_solicitud
        ->filter(fn($input) => isset($input['input']['category_id']) && $input['input']['category_id'] == 1)
        ->sum('valor');

    $sumaDosisDeLipidos = $inputs_solicitud
        ->filter(fn($input) => isset($input['input']['category_id']) && $input['input']['category_id'] == 3)
        ->sum('valor');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Etiqueta</title>

    <style>
        @page {
            margin: 0px;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: white;
        }

        .introduccion table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .introduccion td {
            border: none;
        }

        .contenedor {
            border: 2px solid black;
            padding: 0 2px;
            font-family: "Arial", sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            text-align: left;
            padding: 0px 3px;
            font-size: 8px;
        }

        th {
            background-color: #DEEAF6;
        }

        p {
            font-size: 10px;
        }

        .tabla-format table {
            table-layout: fixed;
            border: 1px solid black;
        }

        .tabla-format td {
            border: 1px solid black;
            word-wrap: break-word;
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <div>
            <table>
                <tr>
                    <td style="text-align: center; width: 100%; border:none; font-size:0.70rem">
                        <strong>CENTRO DE MEZCLAS ESTÉRILES PRODIFEM</strong>
                    </td>
                </tr>
            </table>

            <table class="introduccion">
                <tr>
                    <td style="width: 70%">
                        <strong>
                            Institucion:
                            {{ $solicitud_detalles->solicitud_detail->hospital_destino
                                ? $solicitud_detalles->solicitud_detail->hospital_destino
                                : $solicitud_detalles->user->hospital->name }}
                        </strong>
                    </td>
                    <td style="width: 30%">
                        <strong>
                            Lote:
                            {{ $solicitud_detalles->lote ?? 'â€”' }}
                        </strong>
                    </td>
                </tr>
            </table>

            <table class="introduccion">
                <tr>
                    <td style="width: 70%">
                        <strong>
                            Paciente:
                            {{ $solicitud_detalles->solicitud_patient['nombre_paciente'] }}
                            {{ $solicitud_detalles->solicitud_patient['apellidos_paciente'] }}
                        </strong>
                    </td>
                    <td style="width: 30%">
                        <strong>
                            FN:
                            {{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_patient['fecha_nacimiento'])) }}
                        </strong>
                    </td>
                </tr>
            </table>

            <table class="introduccion">
                <tr>
                    <td style="width: 45%">
                        <strong>Registro: {{ $solicitud_detalles->solicitud_patient['registro'] }}</strong>
                    </td>
                    <td style="width: 27.5%">
                        <strong>Cama: {{ $solicitud_detalles->solicitud_patient['cama'] }}</strong>
                    </td>
                    <td style="width: 27.5%">
                        <strong>Peso: {{ $solicitud_detalles->solicitud_patient['peso'] }} Kg</strong>
                    </td>
                </tr>
            </table>

            <table class="introduccion">
                <tr>
                    <td>
                        <strong>Médico: {{ $solicitud_detalles->solicitud_detail['nombre_medico'] }}</strong>
                    </td>
                </tr>
            </table>

            <table class="introduccion">
                <tr>
                    <td style="width: 50%">
                        <strong>Cédula: {{ $solicitud_detalles->solicitud_detail['cedula'] }}</strong>
                    </td>
                    <td style="width: 50%">
                        <strong>Servicio: {{ $solicitud_detalles->solicitud_patient['servicio'] }}</strong>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="text-align: center; border: none; font-size: 0.55rem; font-style: italic">
                        <strong>NUTRICIÓN PARENTERAL</strong>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="border: none; border-bottom: 1px solid black; text-align: center; width: 80%">
                        <strong>COMPONENTES</strong>
                    </td>
                    <td style="border: none; border-bottom: 1px solid black; text-align: center; width: 20%">
                        <strong>CONTENIDO</strong>
                    </td>
                </tr>

                @php
                    $osmolaridad_total = 0;
                @endphp

                @foreach ($inputs_solicitud as $input_completo)
                    <tr style="padding: 0; margin:0;">
                        <td style="border:none; text-align: left; width: 50%; padding: 0; margin:0;">
                            <strong>{{ nombreGenericoEtiqueta($input_completo) }}</strong>

                            @php
                                $osmolaridad_total += osmolaridadEtiqueta($input_completo);
                            @endphp
                        </td>

                        <td style="border:none; text-align: center; width: 50%; padding: 0; margin:0;">
                            <strong>
                                @php
                                    $valor = (string) ($input_completo['valor'] ?? 0);
                                    $valorFormateado =
                                        strpos($valor, '.') !== false
                                            ? number_format((float) $valor, 3, '.', '')
                                            : number_format((float) $valor, 0);
                                @endphp

                                {{ $valorFormateado }}
                            </strong>
                            {{ ajustarUnidad($input_completo->input->unidad ?? '', $solicitud_detalles->solicitud_detail['npt']) }}
                        </td>
                    </tr>
                @endforeach
            </table>

            <table>
                <tr style="padding: 0; margin: 0">
                    <td style="border: none; border-top: 1px solid black; padding: 0; margin: 0">
                        <strong>Osmolaridad:</strong>

                        @php
                            $volumenTotal = (float) ($solicitud_detalles->solicitud_detail['volumen_total'] ?? 0);
                            $sumaOsmolaridad = 0;

                            if ($volumenTotal > 0) {
                                foreach ($inputs_solicitud as $medicamento) {
                                    $volumen = (float) ($medicamento['valor_ml'] ?? 0);
                                    $osmolaridad = osmolaridadEtiqueta($medicamento);

                                    $sumaOsmolaridad += ($volumen * $osmolaridad) / $volumenTotal;
                                }
                            }
                        @endphp

                        {{ $volumenTotal > 0 ? number_format($sumaOsmolaridad, 2) : 'â€”' }} mOsm/mL
                    </td>

                    <td style="border: none; border-top: 1px solid black; padding: 0; margin: 0">
                        <strong>Vol. total:</strong>
                        @if (
                            $solicitud_detalles->solicitud_detail['volumen_total'] == null ||
                                $solicitud_detalles->solicitud_detail['volumen_total'] == 0)
                            {{ number_format($solicitud_detalles->solicitud_detail['suma_volumen'], 2) }}
                        @else
                            {{ number_format($solicitud_detalles->solicitud_detail['volumen_total'], 2) }}
                        @endif
                        mL
                    </td>
                </tr>

                <tr style="padding: 0; margin: 0">
                    <td style="border: none; padding: 0; margin: 0">
                        <strong>Sobrellenado:</strong>
                        {{ $solicitud_detalles->solicitud_detail['sobrellenado_ml'] ?? 0 }} mL
                    </td>

                    <td style="border: none; padding: 0; margin: 0">
                        <strong>GKM:</strong>
                        {{ isset($sumaDosisDeGlucosa, $solicitud_detalles->solicitud_detail['tiempo_infusion_min']) &&
                        $solicitud_detalles->solicitud_detail['tiempo_infusion_min'] != 0
                            ? number_format(
                                ($sumaDosisDeGlucosa * 1000) / ($solicitud_detalles->solicitud_detail['tiempo_infusion_min'] * 60),
                                2,
                            )
                            : 'â€”' }}
                        mg/kg/min
                    </td>
                </tr>

                <tr style="padding: 0; margin: 0">
                    <td style="border: none; padding: 0; margin: 0">
                        <strong>Calorías totales:</strong>
                        @if ($solicitud_detalles->solicitud_detail['npt'] != 'ADULT')
                            {{ isset(
                                $sumaDosisDeAA,
                                $sumaDosisDeLipidos,
                                $sumaDosisDeGlucosa,
                                $solicitud_detalles->solicitud_patient['peso'],
                            )
                                ? number_format(
                                    $sumaDosisDeAA * $solicitud_detalles->solicitud_patient['peso'] * 4 +
                                        $sumaDosisDeLipidos * $solicitud_detalles->solicitud_patient['peso'] * 9 +
                                        $sumaDosisDeGlucosa * $solicitud_detalles->solicitud_patient['peso'] * 3.4,
                                    2,
                                )
                                : 'â€”' }}
                            kcal
                        @else
                            {{ isset($sumaDosisDeAA, $sumaDosisDeLipidos, $sumaDosisDeGlucosa)
                                ? number_format($sumaDosisDeAA * 4 + $sumaDosisDeLipidos * 9 + $sumaDosisDeGlucosa * 3.4, 2)
                                : 'â€”' }}
                            kcal
                        @endif
                    </td>

                    <td style="border: none; padding: 0; margin: 0">
                        <strong>Densidad calórica:</strong>
                        @if ($solicitud_detalles->solicitud_detail['npt'] != 'ADULT')
                            {{ isset(
                                $sumaDosisDeAA,
                                $sumaDosisDeLipidos,
                                $sumaDosisDeGlucosa,
                                $solicitud_detalles->solicitud_patient['peso'],
                                $solicitud_detalles->solicitud_detail['volumen_total'],
                            ) && $solicitud_detalles->solicitud_detail['volumen_total'] != 0
                                ? number_format(
                                    ($sumaDosisDeAA * $solicitud_detalles->solicitud_patient['peso'] * 4 +
                                        $sumaDosisDeLipidos * $solicitud_detalles->solicitud_patient['peso'] * 9 +
                                        $sumaDosisDeGlucosa * $solicitud_detalles->solicitud_patient['peso'] * 3.4) /
                                        $solicitud_detalles->solicitud_detail['volumen_total'],
                                    2,
                                )
                                : 'â€”' }}
                            kcal/mL
                        @else
                            {{ isset(
                                $sumaDosisDeAA,
                                $sumaDosisDeLipidos,
                                $sumaDosisDeGlucosa,
                                $solicitud_detalles->solicitud_detail['volumen_total'],
                            ) && $solicitud_detalles->solicitud_detail['volumen_total'] != 0
                                ? number_format(
                                    ($sumaDosisDeAA * 4 + $sumaDosisDeLipidos * 9 + $sumaDosisDeGlucosa * 3.4) /
                                        $solicitud_detalles->solicitud_detail['volumen_total'],
                                    2,
                                )
                                : 'â€”' }}
                            kcal/mL
                        @endif
                    </td>
                </tr>

                <tr style="padding: 0; margin: 0">
                    @php
                        if (
                            $solicitud_detalles->solicitud_detail['volumen_total'] == null ||
                            $solicitud_detalles->solicitud_detail['volumen_total'] == 0
                        ) {
                            $vol_total = (float) $solicitud_detalles->solicitud_detail['suma_volumen'];
                        } else {
                            $vol_total = (float) $solicitud_detalles->solicitud_detail['volumen_total'];
                        }
                    @endphp

                    <td style="border: none; padding: 0; margin: 0">
                        <strong>Administrar en:</strong>
                        @isset($solicitud_detalles->solicitud_detail['velocidad_infusion'])
                            {{ ceil($vol_total / $solicitud_detalles->solicitud_detail['velocidad_infusion']) }} h
                        @else
                            {{ $solicitud_detalles->solicitud_detail['tiempo_infusion_min'] }} h
                        @endisset
                    </td>

                    <td style="border: none; padding: 0; margin: 0">
                        <strong>Vel. Infusión:</strong>
                        @isset($solicitud_detalles->solicitud_detail['velocidad_infusion'])
                            {{ $solicitud_detalles->solicitud_detail['velocidad_infusion'] }}
                        @else
                            {{ number_format($vol_total / $solicitud_detalles->solicitud_detail['tiempo_infusion_min'], 2) }}
                        @endisset
                        ml/hr
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="border: none; text-align: center; border-top: 1px solid black;">
                        <strong>
                            MANTENER EN REFRIGERACIÓN DE 2 A 8 GRADOS <br>
                            HASTA UNA HORA ANTES DE SU ADMINISTRACIÓN
                        </strong>
                    </td>
                </tr>

                <tr>
                    <td style="border: none; text-align: center; padding: 0; margin: 0">
                        <strong>Fecha y hora de Solicitud:</strong>
                        {{ date('d-m-Y H:i', strtotime($solicitud_detalles['created_at'])) }}h
                    </td>
                </tr>

                <tr>
                    <td style="border: none; text-align: center; padding: 0; margin: 0">
                        <strong>Fecha y hora de preparación:</strong>

                        {{ !empty($solicitud_detalles->fecha_hora_preparacion)
                            ? date('d-m-Y H:i', strtotime($solicitud_detalles->fecha_hora_preparacion)) . 'h'
                            : 'â€”' }}
                    </td>
                </tr>

                <tr>
                    <td style="border: none; text-align: center; padding: 0; margin: 0">
                        <strong>Fecha y hora límite de uso:</strong>

                        {{ !empty($solicitud_detalles->fecha_hora_limite_uso)
                            ? date('d-m-Y H:i', strtotime($solicitud_detalles->fecha_hora_limite_uso)) . 'h'
                            : 'â€”' }}

                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>


