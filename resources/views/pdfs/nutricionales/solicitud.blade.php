@php
    $variables = [];
    foreach ($arreglo_resultado as $input_completo) {
        $variables['variable' . $input_completo['input_id']] = $input_completo['valor'];
    }

    function ajustarUnidad($unidad, $npt) {
        if ($npt === 'ADULT') {
            if ($unidad === 'g/Kg') {
                return 'g/día';
            } elseif ($unidad === 'mEq/Kg') {
                return 'mEq/día';
            }
        }
        return $unidad; // Devuelve la unidad original si no se cumplen las condiciones.
    }


    $npt = $solicitud_detalles->solicitud_detail['npt'];
    $tipo = ($npt === 'ADULT') ? 'ADULTO' : 'PEDIÁTRICO';

@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Solicitud</title>
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
            font-size: 13px;
        }

        /* Contenedor principal con borde negro */
        .contenedor {
            border: 2px solid black;
            padding: 0 1px;
            font-family: "Arial", sans-serif;
        }

        /* Estilos para el texto introductorio */
        .introduccion table {
            width: 100%;
            /* Ajusta esto según necesites */
            border-collapse: collapse;
            border: none;
        }

        .linea-input {
            border-top-width: 0px;
            border-right-width: 0px;
            border-left-width: 0px;
            border-bottom-width: 2px;
            border-style: solid;
        }

        .border-top {
            border-right: 1px solid #000;
            border-left: 1px solid #000;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .border-top td {
            padding-left: 4px;
        }

        .border-bottom {
            border-right: 1px solid #000;
            border-left: 1px solid #000;
            border-top: none;
            border-bottom: 1px solid #000;
        }

        .border-bottom td {
            padding-left: 4px;
        }

        .bold {
            font-weight: bold;
        }

        .m-0 {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <!-- Contenedor principal con borde negro -->
        <div class="introduccion">
            <table style="margin-top: 1rem; margin-bottom: 1rem">
                <tr>
                    <td style="width: 20%">
                        <img style="width: 10rem;" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                    </td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>CENTRAL DE MEZCLAS ESTÉRILES PRODIFEM <br> NUTRICIONES PARENTERALES</strong>
                    </td>
                    <td style="width: 20%">

                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="border-top: 2px solid black; text-align: left; color: blue; width: 50%;">
                        No. {{ $solicitud_detalles->id }}
                    </td>
                    <td style="border-top: 2px solid black; text-align: right; color: blue; width: 50%;">
                        FTO-NPT-023-005
                    </td>
                </tr>
            </table>
            <table>
                <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                    <td style="text-align: center; ">SOLICITUD DE NUTRICIÓN PARENTERAL</td>
                </tr>
            </table>
            <table>
                <tr class="border-top">
                    <td style="width: 50%; border-right: 1px solid #000;">
                        Institucion/hospital: <span>{{ $solicitud_detalles->solicitud_detail->hospital_destino ? $solicitud_detalles->solicitud_detail->hospital_destino : $solicitud_detalles->user->hospital->name }}
                        </span></td>

                    <td style="width: 50%">Fecha de la solicitud: <span>{{ date('d-m-Y H:i', strtotime( $solicitud_detalles->created_at)) }}h</span></td>
                </tr>
            </table>
            <table>
                <tr class="border-bottom">
                    <td style="width: 40%; border-right: 1px solid #000;">Paciente:
                        {{ $solicitud_detalles->solicitud_patient['nombre_paciente'] }}
                        {{ $solicitud_detalles->solicitud_patient['apellidos_paciente'] }}</td>
                    <td style="width: 20%; border-right: 1px solid #000;">Servicio:
                        {{ $solicitud_detalles->solicitud_patient['servicio'] }}</td>
                    <td style="width: 20%; border-right: 1px solid #000;">Cama:
                        {{ $solicitud_detalles->solicitud_patient['cama'] }}</td>
                    <td style="width: 20%">Piso: {{ $solicitud_detalles->solicitud_patient['piso'] }}</td>
                </tr>
            </table>
            <table>
                <tr class="border-bottom">
                    <td style="width: 50%; border-right: 1px solid #000;">Registro:
                        {{ $solicitud_detalles->solicitud_patient['registro'] }}</td>
                    <td style="width: 50%">Diagnóstico: {{ $solicitud_detalles->solicitud_patient['diagnostico'] }}
                    </td>
                </tr>
            </table>
            <table>
                <tr class="border-bottom">
                    <td style="width: 25%; border-right: 1px solid #000;">Edad:
                        {{ $solicitud_detalles->solicitud_patient['edad'] }}</td>
                    <td style="width: 25%; border-right: 1px solid #000;">Peso:
                        {{ $solicitud_detalles->solicitud_patient['peso'] }} kg</td>
                    <td style="width: 25%; border-right: 1px solid #000;">Sexo:
                        {{ $solicitud_detalles->solicitud_patient['sexo'] }}</td>
                    <td style="width: 25%">Fecha de nacimiento:
                        {{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_patient['fecha_nacimiento'])) }}</td>
                </tr>
            </table>
            <table>
                <tr class="border-bottom">
                    <td style="width: 33.33%; border-right: 1px solid #000;">Vía de administración:
                        {{ $solicitud_detalles->solicitud_patient['via_administracion'] }}</td>
                    <td style="width: 33.33%; border-right: 1px solid #000;">Tiempo de infusión:
                        {{ $solicitud_detalles->solicitud_patient['tiempo_infusion_min'] }}</td>
                    <td style="width: 33.33%">No. Bolsa: 1</td>
                </tr>
                <tr class="border-bottom">
                    <td style="width: 33.33%; border-right: 1px solid #000;">Sobrellenado:
                        {{ $solicitud_detalles->solicitud_detail['sobrellenado_ml'] }}</td>
                    <td style="width: 33.33%; border-right: 1px solid #000;">Vol. Total:
                        {{ $solicitud_detalles->solicitud_detail['volumen_total'] }}</td>
                    <td style="width: 33.33%">NPT: {{ $tipo }}</td>
                </tr>
            </table>
            <table style="margin-top: 0.5rem">
                <tr>
                    <td class="bold" style="border-bottom: 2px solid #1F4E78; text-align:left; width: 100%">
                        MACRONUTRIENTES</td>
                    <td></td>
                </tr>
            </table>
            <table style="margin-top: 1.5rem">
                <tr>
                    <td class="bold">AMINOÁCIDOS</td>
                </tr>
                <tr>
                    <td>
                    @foreach ($inputs_solicitud as $input_completo)
                        @if ($input_completo->input->category_id == 1)

                        {{$input_completo->input->description}}: <strong>{{$input_completo->valor}} {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>


                        @endif
                    @endforeach
                    @foreach ($inputs_solicitud as $input_completo)
                    @if ($input_completo->input->category_id == 8)

                    {{$input_completo->input->description}}: <strong>{{$input_completo->valor}} {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>

                    @endif
                    @endforeach
                <br>
                </td>
                </tr>
                <tr >
                    <td class="bold" >CARBOHIDRATOS</td>
                    {{-- <td class="bold" style="width: 50%"></td> --}}
                </tr>
                <tr>
                    <td>
                    @foreach ($inputs_solicitud as $input_completo)
                        @if ($input_completo->input->category_id == 2)

                        {{$input_completo->input->description}}: <strong>{{$input_completo->valor}} {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>

                        @endif
                    @endforeach
                    <br>
                    </td>

                </tr>
                <tr>
                    <td class="bold" >LÍPIDOS</td>
                    {{-- <td class="bold" style="width: 50%"></td> --}}
                </tr>
                <tr>
                    <td>
                    @foreach ($inputs_solicitud as $input_completo)
                        @if ($input_completo->input->category_id == 3)

                        {{$input_completo->input->description}}: <strong>{{$input_completo->valor}}  {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>

                        @endif
                    @endforeach
                    <br>
                    </td>

                </tr>
                <tr>
                    <td class="bold" style="border-bottom: 2px solid #1F4E78; text-align:left; width: 100%">ELECTROLITOS</td>
                    <td></td>
                    {{-- <td class="bold" style="width: 50%"></td> --}}
                </tr>

                <tr>
                    <td>
                    @foreach ($inputs_solicitud as $input_completo)
                        @if ($input_completo->input->category_id == 4)

                        {{$input_completo->input->description}}: <strong>{{$input_completo->valor}}  {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>

                        @endif
                    @endforeach
                    <br>
                    </td>

                </tr>
                <tr>
                    <td class="bold" style="border-bottom: 2px solid #1F4E78; text-align:left; width: 100%">ADITIVOS</td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                    @foreach ($inputs_solicitud as $input_completo)
                        @if ($input_completo->input->category_id == 5)

                        {{$input_completo->input->description}}: <strong>{{$input_completo->valor}}  {{ ajustarUnidad($input_completo->input->unidad, $solicitud_detalles->solicitud_detail['npt']) }}</strong><br>

                        @endif
                    @endforeach
                    <br>
                    </td>
                </tr>
                <tr>
                    <td class="bold" style="border-bottom: 2px solid #1F4E78; text-align:left; width: 100%">SET DE INFUSIÓN</td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        {{-- {{$set_infusion}} --}}
                        @isset($set_infusion)
                        Si <br>
                        @else
                        No <br>
                        @endisset
                    </td>

                </tr>
            </table>
            {{-- <table>
                <tr>
                    <td style="width: 50%">Cloruro de Sodio (3 mEq/mL Na)</td>
                    <td style="width: 50%">Cloruro de Potasio (4 mEq/mL)</td>
                </tr>
            </table> --}}
            <table style="margin-top: 0.5rem">
                <tr>
                    <td class="bold">OBSERVACIONES</td>
                </tr>
                <tr>
                    <td style="border-style: solid; border-width: 1px; height: 4rem;">
                        {{ $solicitud_detalles->solicitud_detail->observaciones }}

                    </td>
                </tr>
            </table>
            <table>
                <tr>

                    <td>Fecha y hora de entrega: {{ date('d-m-Y H:i', strtotime($solicitud_detalles->solicitud_detail->fecha_hora_entrega)) }}h</td>
                    <td>Cédula profesional: {{ $solicitud_detalles->solicitud_detail->cedula }}</td>
                </tr>
                <tr>
                     <td>Nombre de quien entrega:</td>
                    <td>Nombre del médico: {{ $solicitud_detalles->solicitud_detail->nombre_medico }}</td>
                </tr>
                <tr>
                    <td>Temperatura en la que se entrega:</td>
                    <td></td>
                </tr>

            </table>
        </div>
    </div>
</body>

</html>

