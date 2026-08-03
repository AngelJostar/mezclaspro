@php
    use Carbon\Carbon;

    // Helpers seguros
    $safe = function ($v, $fallback = '—') {
        return isset($v) && $v !== '' ? $v : $fallback;
    };
    $mark = function ($cond) {
        return $cond === true ? 'X' : '';
    };
    // Hora 24h
    $fmtTime = function ($v) {
        if (!$v) {
            return '—';
        }
        try {
            return Carbon::parse($v)->format('H:i');
        } catch (\Exception $e) {
            return '—';
        }
    };
    // Fecha: si trae hora distinta de 00:00, usa d/m/Y H:i; si no, d/m/Y
    $fmtDateSmart = function ($v) {
        if (!$v) {
            return '—';
        }
        try {
            $c = Carbon::parse($v);
            return $c->format('H:i') !== '00:00' ? $c->format('d/m/Y H:i') : $c->format('d/m/Y');
        } catch (\Exception $e) {
            return '—';
        }
    };
    $val = function ($obj, $key, $default = null) {
        return data_get($obj, $key, $default);
    };

    $ins = $inspeccion ?? (object) [];

    // Listas de campos con etiquetas legibles
    $camposFisicos = [
        'esta_rotulado' => 'Está rotulado',
        'numero_lote' => 'Número de lote visible',
        'medicamento' => 'Nombre del medicamento',
        'dosis_volumen_total' => 'Dosis / Volumen total',
        'volumen_medicamento' => 'Volumen del medicamento',
        'rubrica_preparador' => 'Rúbrica del preparador',
        'sello_seguridad' => 'Sello de seguridad',
        'presenta_grietas' => 'Presenta grietas',
        'presenta_fugas' => 'Presenta fugas',
        'esta_roto' => 'Está roto',
    ];

    $camposContenido = [
        'coloracion_apropiada' => 'Coloración apropiada',
        'contenido_homogeneo' => 'Contenido homogéneo',
        'presenta_particulas' => 'Presenta partículas',
        'presenta_turbidez' => 'Presenta turbidez',
        'volumen_correcto' => 'Volumen correcto',
    ];

    // Tipo contenedor actual
    $tipoCont = $val($ins, 'tipo_contenedor');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Inspección</title>

    <style>
        @page {
            margin: 1rem;
        }

        /* Estilos básicos */
        body {
            margin: 0;
            padding: 20px;
            /* Espacio alrededor del contenedor para que el borde no toque los bordes de la ventana del navegador */
            background-color: white;
            /* Fondo blanco para el body */
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }

        .salto-pagina {
            page-break-before: always;
        }

        /* Contenedor principal con borde negro */
        .contenedor {
            padding: 0;
            font-family: "Arial", sans-serif;
        }

        /* Estilos para el texto introductorio */
        .introduccion table {
            width: 100%;
            /* Ajusta esto según necesites */
            border-collapse: collapse;
            border: none;
        }

        /* Estilos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 0px solid black;
            padding: 0px;
            font-size: 12px
        }

        .border-1 {
            border: 1px solid black;
        }

        .mx-1 {
            margin-left: 0.25rem;
            margin-right: 0.5rem;
        }

        .mt-8 {
            margin-top: 2rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .px-1 {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }

        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        th {
            background-color: #f2f2f2;
        }

        p {
            font-size: 11px
        }

        .liberacion-area td {
            margin: 0;
            padding: 0 8px;
        }

        .firmas td {

            text-align: center;
        }

        .elementos td {
            margin: 0;
            padding: 0 8px;

        }

        .border-r-0 {
            border-right: none;
        }

        .border-l-0 {
            border-left: none;
        }

        .border-t-0 {
            border-top: none;
        }

        .border-b-0 {
            border-bottom: none;
        }

        .border-l-1 {
            border-left: 1px solid black;
        }

        .border-r-1 {
            border-right: 1px solid black;
        }

        .border-0 {
            border: none;
        }

        .border-1 {
            border: 1px solid black;
        }

        .border-t-1 {
            border-top: 1px solid black;
        }

        .border-b-1 {
            border-bottom: 1px solid black;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fond-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center
        }
    </style>

</head>


<body>
    <div class="contenedor border-1 px-1">
        <!-- Encabezado -->
        <div class="introduccion">
            <table>
                <tr>
                    <td style="width: 20%"><img style="width: 10rem" src="{{ asset('img/logo-cbta.jpg') }}"
                            alt=""></td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>INSPECCIÓN <br> MEZCLAS ESTÉRILES ONCOLÓGICAS</strong>
                    </td>
                    <td style="width: 20%"></td>
                </tr>
            </table>
        </div>

        <table>
            <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                <td style="text-align: center;">INSPECCIÓN DE MEZCLAS ÉSTERILES ONCOLÓGICAS</td>
            </tr>
        </table>

        <div class="mx-1">
            <table>
                <tr>
                    <td style="width: 70%" class="text-right">No. de lote:</td>
                    <td style="width: 30%" class="border-b-1">{{ $safe($val($mezcla ?? null, 'lote')) }}</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="width: 5%">Fecha: </td>
                    <td style="width: 15%" class="border-b-1">{{ $fmtDateSmart($val($ins, 'fecha_inspeccion')) }}</td>
                    <td style="width: 35%"></td>
                    <td style="width: 20%" class="text-right">Hora de inspección:</td>
                    <td style="width: 30%" class="border-b-1">{{ $fmtTime($val($ins, 'hora_inspeccion')) }}</td>
                </tr>
            </table>

            <table class="mt-2">
                <tr>
                    <td style="width: 20%" class="text-left"><strong>Tipo de contenedor</strong></td>
                    <td style="width: 10%" class="text-left"><strong>Frasco/bolsa</strong></td>
                    <td style="width: 5%" class="text-center">Frasco</td>
                    <td style="width: 5%" class="border-1 text-center">{{ $mark($tipoCont === 'Frasco') }}</td>
                    <td style="width: 5%" class="text-center">Bolsa</td>
                    <td style="width: 5%" class="border-1 text-center">{{ $mark($tipoCont === 'Bolsa') }}</td>
                    <td style="width: 5%" class="text-center">Jeringa</td>
                    <td style="width: 5%" class="border-1 text-center">{{ $mark($tipoCont === 'Jeringa') }}</td>
                    <td style="width: 5%" class="text-center">Infusor</td>
                    <td style="width: 5%" class="border-1 text-center">{{ $mark($tipoCont === 'Infusor') }}</td>
                </tr>
            </table>

            <!-- Inspección física del contenedor -->
            <table class="mt-2">
                <tr>
                    <td style="width: 48%">
                        <table>
                            <tr>
                                <td class="border-1 px-1 text-center"><strong>Inspección física del contenedor</strong>
                                </td>
                            </tr>
                        </table>
                        <table>
                            <tr>
                                <td style="width: 70%" class="border-l-1 px-1"></td>
                                <td style="width: 15%" class=" text-center"><strong>Si</strong></td>
                                <td style="width: 15%" class="border-r-1 text-center"><strong>No</strong></td>
                            </tr>

                            @foreach ($camposFisicos as $campo => $etiqueta)
                                @php $valor = $val($ins, $campo); @endphp
                                <tr>
                                    <td class="border-1 px-1">{{ $etiqueta }}</td>
                                    <td class="border-1 text-center">{{ $mark($valor) }}</td>
                                    <td class="border-1 text-center">{{ $mark($valor === false) }}</td>
                                </tr>
                            @endforeach
                        </table>

                        <table class="border-1 mt-2">
                            @php $aprCont = $val($ins, 'aprueba_contenedor'); @endphp
                            <tr>
                                <td style="width: 70%" class="border-1 px-1">¿Aprueba la inspección física del
                                    contenedor?</td>
                                <td style="width: 15%" class="border-1 px-1 text-center">{{ $mark($aprCont) }}</td>
                                <td style="width: 15%" class="border-1 px-1 text-center">
                                    {{ $mark($aprCont === false) }}</td>
                            </tr>
                        </table>
                    </td>

                    <td style="width: 4%"></td>

                    <!-- Inspección del contenido -->
                    <td style="width: 48%">
                        <table>
                            <tr>
                                <td class="border-1 px-1 text-center"><strong>Inspección del contenido</strong></td>
                            </tr>
                        </table>

                        <table>
                            <tr>
                                <td style="width: 70%" class="px-1 border-l-1"></td>
                                <td style="width: 15%" class="text-center px-1"><strong>Si</strong></td>
                                <td style="width: 15%" class="text-center px-1 border-r-1"><strong>No</strong></td>
                            </tr>

                            @foreach ($camposContenido as $campo => $etiqueta)
                                @php $valor = $val($ins, $campo); @endphp
                                <tr>
                                    <td class="border-1 px-1">{{ $etiqueta }}</td>
                                    <td class="border-1 text-center">{{ $mark($valor) }}</td>
                                    <td class="border-1 text-center">{{ $mark($valor === false) }}</td>
                                </tr>
                            @endforeach
                        </table>

                        <table class="mt-2">
                            @php $aprConten = $val($ins, 'aprueba_contenido'); @endphp
                            <tr>
                                <td style="width: 70%" class="border-1 px-1">¿Aprueba la inspección del contenido?</td>
                                <td style="width: 15%" class="border-1 px-1 text-center">{{ $mark($aprConten) }}</td>
                                <td style="width: 15%" class="border-1 px-1 text-center">
                                    {{ $mark($aprConten === false) }}</td>
                            </tr>
                        </table>

                        <table class="mt-2">
                            <tr>
                                <td style="width: 50%" class="text-right">Dosis en volumen (mL):</td>
                                <td style="width: 50%" class="border-b-1">{{ $safe($val($ins, 'dosis_volumen')) }}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%" class="text-right">Peso de la mezcla (g):</td>
                                <td style="width: 50%" class="border-b-1">{{ $safe($val($ins, 'peso_mezcla')) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="mt-2">
                <table class="px-1">
                    @php $aprobada = $val($ins, 'mezcla_aprobada'); @endphp
                    <tr>
                        <td class="border-1 px-1" style="width: 60%">LA MEZCLA SE CONSIDERA APROBADA:</td>
                        <td class="border-1 text-center" style="width: 5%"><strong>Si</strong></td>
                        <td class="border-1 text-center" style="width: 5%">{{ $mark($aprobada) }}</td>
                        <td class="border-1 text-center" style="width: 5%"><strong>No</strong></td>
                        <td class="border-1 text-center" style="width: 5%">{{ $mark($aprobada === false) }}</td>
                        <td style="width: 20%"></td>
                    </tr>
                </table>
            </div>

            <div class="mt-2 border-1">
                <table class="px-1">
                    <tr>
                        <td>Observaciones:</td>
                    </tr>
                    <tr>
                        <td>{{ $safe($val($ins, 'observaciones'), '&nbsp;') }}</td>
                    </tr>
                </table>
            </div>

            <table class="mt-2" style="margin-top: 4rem; margin-bottom: 3rem;">
                <tr>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="border-b-1 text-center">{{ $safe($val($ins, 'reviso_nombre')) }}
                    </td>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="border-b-1 text-center">{{ $safe($val($ins, 'aprobo_nombre')) }}
                    </td>
                    <td style="width: 10%"></td>
                </tr>
                <tr>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="text-center">Inspecciono</td>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="text-center">Aprobó</td>
                    <td style="width: 10%"></td>
                </tr>
                <tr>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="text-center">Unidad de Calidad</td>
                    <td style="width: 10%"></td>
                    <td style="width: 35%" class="text-center">Unidad de Calidad</td>
                    <td style="width: 10%"></td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
