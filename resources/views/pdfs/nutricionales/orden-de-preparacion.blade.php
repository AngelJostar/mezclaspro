<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Orden de preparación</title>

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

        .introduccion td {
            border: none;
        }

        /* Estilos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
            font-size: 11px
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

        .tabla-npt {
            table-layout: fixed;
        }

        .tabla-npt th,
        .tabla-npt td {
            padding: 3px 5px;
            vertical-align: middle;
        }

        .tabla-npt .col-no {
            width: 4%;
            text-align: center;
        }

        .tabla-npt .col-volumen {
            width: 12%;
        }

        .tabla-npt .col-dosis {
            width: 8%;
        }

        .tabla-npt .col-generica {
            width: 24%;
        }

        .tabla-npt .col-comercial {
            width: 20%;
        }

        .tabla-npt .col-presentacion {
            width: 12%;
            text-align: center;
        }

        .tabla-npt .col-lote {
            width: 11%;
            white-space: nowrap;
        }

        .tabla-npt .col-caducidad {
            width: 9%;
            white-space: nowrap;
            text-align: center;
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
    </style>

</head>


<body>

    <div class="contenedor">
        <!-- Contenedor principal con borde negro -->
        <div class="introduccion" style="border: 1px solid black; border-bottom: none; ">
            <table>
                <tr>
                    <td style="width: 20%">
                        <img style="width: 10rem" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                    </td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>CENTRAL DE MEZCLAS ESTÉRILES PRODIFEM <br> NUTRICIONES PARENTERALES</strong>
                    </td>
                    <td style="width: 20%">

                    </td>
                </tr>
            </table>
        </div>
        <table>
            <tr>
                <td style="text-align: right; color: blue; padding: 2px 8px;">
                    FTO-NPT-025-005
                </td>
            </tr>
            <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                <td style="text-align: center;">ORDEN DE PREPARACIÓN DE NUTRICIÓN PARENTERAL TOTAL</td>
            </tr>
        </table>

        <div style="border-left: 1px solid black; border-right: 1px solid black;margin-bottom: 0rem;">
            <table>
                <tr>
                    <td style="border: none; text-align: right"><strong>Fecha de entrega:</strong>
                        <span>{{ date('d/m/Y H:i', strtotime($solicitud_detalles->solicitud_detail['fecha_hora_entrega'])) }}</span>
                    </td>
                </tr>
            </table>

            <div style=" border: 1px solid black; margin-bottom: 0rem;">
                <table style="padding: 0; margin: 0">
                    <tr style="margin: 0; padding: 0">

                        <td style="border: none; padding: 2px 8px">
                            <strong>No. de lote de la mezcla:</strong>

                            <span>
                                {{ $solicitud_detalles->lote }}
                            </span>
                        </td>

                        <td style="border: none; padding: 2px 8px">
                            <strong>No. de orden de preparación:</strong>

                            NUT{{ substr($solicitud_detalles->lote, 1) }}
                        </td>

                    </tr>
                </table>
                <p style="padding: 2px 8px; border-top: 1px solid black; border-bottom: 1px solid black; margin: 0;">
                    <strong>DATOS DEL
                        PACIENTE</strong>
                </p>
                <table style="margin: 0; padding-top: 2px">
                    <tr style="padding: 0">
                        <td style="border: none; padding: 0 auto;"><strong>Nombre:</strong>
                            <span>{{ $solicitud_detalles->solicitud_patient['nombre_paciente'] }}
                                {{ $solicitud_detalles->solicitud_patient['apellidos_paciente'] }}</span>
                        </td>
                        <td style="border: none; padding: 0 auto;"><strong>Médico tratante:
                            </strong><span>{{ $solicitud_detalles->solicitud_detail->nombre_medico }}</span></td>
                    </tr>
                    <tr style="padding: 0">
                        <td style="border: none; padding: 0 auto;"><strong>Registro:</strong>
                            <span>{{ $solicitud_detalles->solicitud_patient['registro'] }}</span>
                        </td>
                        <td style="border: none; padding: 0 auto;"><strong>Diagnostico:</strong> <span>
                                {{ $solicitud_detalles->solicitud_patient['diagnostico'] }}</span></td>
                    </tr>
                </table>
                <table style="width: 100%; border-collapse: collapse; padding-bottom: 2px">
                    <tr>
                        <td style="border: none; padding: 0 auto;"><strong>Fecha de nacimiento:</strong>
                            <span>{{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_patient['fecha_nacimiento'])) }}</span>
                        </td>
                        <td style="border: none; padding: 0 auto;"><strong>Genero:</strong>
                            {{ $solicitud_detalles->solicitud_patient['sexo'] }}<span></span>
                        </td>
                        <td style="border: none; padding: 0 auto;"><strong>Talla:</strong> <span>S/D</span>
                        </td>
                        <td style="border: none; padding: 0 auto;"><strong>Peso:</strong> <span>
                                {{ $solicitud_detalles->solicitud_patient['peso'] }}</span></td>
                        <td style="border: none; padding: 0 auto;"><strong>SC:</strong> <span>S/D</span></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0 auto;" colspan="5"><strong>Sitio de procedencia:</strong>
                            <span>{{ $solicitud_detalles->solicitud_detail->hospital_destino ? $solicitud_detalles->solicitud_detail->hospital_destino : $solicitud_detalles->user->hospital->name }}</span>
                        </td>

                    </tr>
                </table>
            </div>
            <p style="padding: 2px 4px; border-left: 1px solid black; border-right: 1px solid black; margin: 0;">
                <strong>NUTRICIÓN PARENTERAL TOTAL</strong>
            </p>
            <table class="elementos tabla-npt">
                <thead>
                    <tr>
                        <th class="col-no" style="background: #D9E2F3;">No</th>
                        <th class="col-volumen" style="background: #D9E2F3;">Volumen(mL)</th>
                        <th class="col-dosis" style="background: #D9E2F3;">Dosis</th>
                        <th class="col-generica" style="background: #D9E2F3;">Denominaci&oacute;n g&eacute;nerica</th>
                        <th class="col-comercial" style="background: #D9E2F3;">Denominaci&oacute;n comercial</th>
                        <th class="col-presentacion" style="background: #D9E2F3;">Presentaci&oacute;n</th>
                        <th class="col-lote" style="background: #D9E2F3;">Lote</th>
                        <th class="col-caducidad" style="background: #D9E2F3;">Caducidad</th>
                    </tr>
                </thead>
                @php
                    // Definimos el orden deseado
                    $orden = [
                        4,
                        5,
                        6,
                        7,
                        8,
                        9,
                        10,
                        11,
                        12,
                        13,
                        14,
                        15,
                        16,
                        17,
                        18,
                        19,
                        20,
                        21,
                        22,
                        23,
                        24,
                        25,
                        26,
                        27,
                        28,
                        29,
                        30,
                        31,
                        32,
                        33,
                        34,
                        35,
                        36,
                        37,
                        38,
                        39,
                        40,
                        41,
                        42,
                        43,
                        44,
                        45,
                        46,
                        47,
                    ];

                    // Creamos un mapa para localizar rápido el índice de cada input_id
                    $ordenMap = array_flip($orden);

                    // Reordenamos la colección según ese mapa
                    $inputs_ordenados = $inputs_solicitud->sortBy(function ($item) use ($ordenMap) {
                        return $ordenMap[$item->input_id] ?? PHP_INT_MAX; // si no está en la lista, lo manda al final
                    });
                @endphp
                <tbody>
                    @foreach ($inputs_ordenados as $input_completo)
                        @if ($input_completo->input_id != 40)
                            <tr>
                                <td class="col-no">{{ $loop->iteration }}</td>
                                @php
                                    // Inicializamos la variable total
                                    $valor_final = 0;

                                    if (
                                        $solicitud_detalles->solicitud_detail['sobrellenado_ml'] == null ||
                                        $solicitud_detalles->solicitud_detail['sobrellenado_ml'] == 0
                                    ) {
                                        $valor_final = number_format($input_completo['valor_ml'], 3, '.', '');
                                    } else {
                                        $valor_final = number_format($input_completo['valor_sobrellenado'], 3, '.', '');
                                    }
                                @endphp

                                <td class="col-volumen" style="font-size: 8px">{{ $valor_final }} mL</td>

                                @php
                                    // Lógica para formatear el valor sin ceros innecesarios
                                    $valor_formateado =
                                        strpos($input_completo['valor'], '.') !== false
                                            ? number_format($input_completo['valor'], 3, '.', '')
                                            : number_format($input_completo['valor'], 0);
                                @endphp

                                <td class="col-dosis" style="font-size: 8px">
                                    {{ $valor_formateado }}
                                    {{ explode('/', $input_completo->input->unidad)[0] }}
                                </td>

                                <td class="col-generica" style="font-size: 8px">
                                    {{ $input_completo->presentation?->catalog?->denominacion_generica ??
                                        ($input_completo->input?->nutritionMedicineCatalog?->denominacion_generica ??
                                            ($input_completo->input?->description ?? 'Medicamento no disponible')) }}
                                </td>

                                <td class="col-comercial" style="font-size: 8px">
                                    {{ $input_completo->presentation?->denominacion_comercial ??
                                        ($input_completo->input?->nutritionMedicineCatalog?->presentations?->first()?->denominacion_comercial ??
                                            'Medicamento no disponible') }}
                                </td>

                                <td class="col-presentacion" style="font-size: 8px">
                                    {{ $input_completo->presentation?->presentacion ??
                                        ($input_completo->input?->nutritionMedicineCatalog?->presentations?->first()?->presentacion ?? 'â€”') }}
                                </td>



                                @php
                                    $stockLote =
                                        $input_completo->presentation?->stocks?->first() ??
                                        $input_completo->input?->nutritionMedicineCatalog?->presentations
                                            ?->first()
                                            ?->stocks?->first();

                                    $loteReferencia = ($lotesPorPresentacion ?? collect())->get(
                                        $input_completo->nutrition_medicine_presentation_id,
                                    );

                                    $loteMostrar = $input_completo->lote ?? $loteReferencia?->lote ?? $stockLote?->lote;
                                    $caducidadMostrar =
                                        $input_completo->caducidad ?? $loteReferencia?->caducidad ?? $stockLote?->caducidad;
                                @endphp

                                <td class="col-lote" style="font-size: 8px">
                                    {{ $loteMostrar ?? '—' }}
                                </td>

                                <td class="col-caducidad" style="font-size: 8px">
                                    {{ $caducidadMostrar ? \Carbon\Carbon::parse($caducidadMostrar)->format('d-m-Y') : '—' }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>

            </table>
            <table style="padding: 0; margin: 0">
                <tr style="padding: 0; margin: 0">
                    <td style="border-top: none; padding: 2px 8px; margin: 0">Volumen Total <span
                            style="font-weight: bold;">{{ number_format($solicitud_detalles->solicitud_detail['volumen_total_final'], 2) }}
                            ml</span></td>
                    <td style="border-top: none; padding: 2px 8px; margin: 0">Contenedor <span
                            style="font-weight: bold;">{{ $bolsa_eva?->presentation?->presentacion_ml
                                ? number_format((float) $bolsa_eva->presentation->presentacion_ml, 2) . ' mL'
                                : 'â€”' }}</span>
                    </td>
                </tr>
            </table>
            <p
                style="padding: 2px 8px; text-align: center; margin: 0; border-left: 1px solid black; border-right: 1px solid black;">
                <strong>Bolsa EVA</strong>
            </p>
            <table style="padding: 0; margin: 0">
                <tr style="padding: 0; margin: 0">
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>No. de lote</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Caducidad</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Nombre comercial</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Nombre genérico</strong></td>
                </tr>
                @php
                    $bolsaReferencia = ($lotesPorPresentacion ?? collect())->get(
                        $bolsa_eva?->nutrition_medicine_presentation_id,
                    );
                    $bolsaLoteMostrar = $bolsa_eva?->lote ?? $bolsaReferencia?->lote;
                    $bolsaCaducidadMostrar = $bolsa_eva?->caducidad ?? $bolsaReferencia?->caducidad;
                @endphp
                <tr>
                    <td style="padding: 2px 8px; margin: 0">{{ $bolsaLoteMostrar ?? 'N/A' }}</td>
                    <td style="padding: 2px 8px; margin: 0">
                        {{ $bolsaCaducidadMostrar ? \Carbon\Carbon::parse($bolsaCaducidadMostrar)->format('d-m-Y') : 'N/A' }}
                    </td>
                    <td style="padding: 2px 8px; margin: 0">
                        {{ $bolsa_eva?->presentation?->denominacion_comercial ?? '—' }}
                    </td>
                    <td style="padding: 2px 8px; margin: 0">
                        {{ $bolsa_eva?->presentation?->catalog?->denominacion_generica ?? 'â€”' }}
                    </td>
                </tr>
            </table>
            <p
                style="padding: 2px 8px; text-align: center; margin: 0; border-left: 1px solid black; border-right: 1px solid black;">
                <strong>Set de infusión</strong>
            </p>
            <table>
                <tr>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>No. de lote</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Caducidad</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Nombre comercial</strong></td>
                    <td style="background: #D9E2F3; padding: 2px 8px; margin: 0"><strong>Nombre genérico</strong></td>
                </tr>
                <tr>
                    <td style="padding: 2px 8px; margin: 0">1</td>
                    @php
                        $setReferencia = ($lotesPorPresentacion ?? collect())->get(
                            $set_infusion?->nutrition_medicine_presentation_id,
                        );
                        $setLoteMostrar = $set_infusion?->lote ?? $setReferencia?->lote;
                        $setCaducidadMostrar = $set_infusion?->caducidad ?? $setReferencia?->caducidad;
                    @endphp
                    <td style="padding: 2px 8px; margin: 0">
                        @isset($set_infusion)
                            {{ $setLoteMostrar ?? 'N/A' }}
                        @else
                            N/A
                        @endisset
                    </td>
                    <td style="padding: 2px 8px; margin: 0">
                        @isset($set_infusion)
                            {{ $setCaducidadMostrar ? \Carbon\Carbon::parse($setCaducidadMostrar)->format('d-m-Y') : 'N/A' }}
                        @else
                            N/A
                        @endisset
                    </td>
                    <td style="padding: 2px 8px; margin: 0">
                        @isset($set_infusion)
                            {{ $set_infusion?->presentation?->denominacion_comercial ?? 'N/A' }}
                        @else
                            N/A
                        @endisset
                    </td>
                    <td style="padding: 2px 8px; margin: 0">
                        @isset($set_infusion)
                            {{ $set_infusion?->presentation?->catalog?->denominacion_generica ?? 'N/A' }}
                        @else
                            N/A
                        @endisset
                    </td>
                </tr>
            </table>
            <div style="border: 1px solid black; margin-bottom: 0rem; border-top: none;">
                <table class="firmas" style="padding-top: 2rem">
                    <tr>
                        <td style="width: 2%; border: none;"></td>
                        <td style="border: none; border-top: 1px solid black;">
                            Elaboró <br>
                            {{ $elaboroNombre ?: 'Nombre y firma' }}
                        </td>
                        <td style="width: 2%; border: none;"></td>
                        <td style="border: none; border-top: 1px solid black;">
                            Validó <br>
                            {{ $validoNombre ?: 'Nombre y firma' }}
                        </td>
                        <td style="width: 2%; border: none;"></td>
                        <td style="border: none; border-top: 1px solid black;">
                            Preparó <br>
                            {{ $preparoNombre ?: 'Nombre y firma' }}
                        </td>
                        <td style="width: 2%; border: none;"></td>
                    </tr>
                </table>


                <table class="border:none">
                    <tr>
                        <td style="width: 60%; border: none;"></td>
                        <td style="width: 40%; border: none; border-top: 0 solid black;"><strong>Fecha y hora de preparación:
                                {{ $solicitud_detalles->fecha_hora_preparacion
                                    ? \Carbon\Carbon::parse($solicitud_detalles->fecha_hora_preparacion)->format('d/m/Y H:i')
                                    : 'N/A' }}</strong>
                        </td>
                    </tr>
                </table>

            </div>

        </div>
        {{-- separar hojas --}}
        <div class="salto-pagina contenedor border-1">
            <div class="introduccion">
                <table>
                    <tr>
                        <td style="width: 20%">
                            <img style="width: 10rem" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                        </td>
                        <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                            <strong>CENTRAL DE MEZCLAS ESTÉRILES PRODIFEM <br> NUTRICIONES PARENTERALES</strong>
                        </td>
                        <td style="width: 20%">

                        </td>
                    </tr>
                </table>
            </div>
            <table>
                <tr>
                    <td style="text-align: right; color: blue; padding: 2px 8px;">
                        FTO-NPT-023-005
                    </td>
                </tr>
                <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                    <td style="text-align: center;">INSPECCIÓN DE MEZCLAS ESTÉRILES NUTRICIONALES</td>
                </tr>
            </table>
            <div>
                <table>
                    <tr>
                        <td class="border-0" style="width: 50%">Fecha:
                            <span>{{ date('d-m-Y', strtotime($solicitud_detalles->created_at)) }}</span>
                        </td>
                        <td class="border-0 text-right" style="width: 50%">Hora de inspección: _____________ </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="width: 15%" class="border-0"><strong>Tipo de contenedor</strong></td>
                        <td style="width: 10%" class="border-0">Bolsa</td>
                        <td style="width: 5%"></td>
                        <td class="border-0" style="widows: 70%"></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="border-0">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td colspan="3" class="text-center"><strong>Inpección de la bolsa</strong></td>
                                </tr>
                                <tr>
                                    <td style="width: 60%"></td>
                                    <td class="text-center" style="width: 20%">si</td>
                                    <td class="text-center" style="width: 20%">no</td>
                                </tr>
                                <tr>
                                    <td>¿Esta rotulado?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>No. De lote</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Rubrica del preparador</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Tiene sello de seguridad?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Presenta fugas?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-center"><strong>Inspección física del set de
                                            infusión</strong></td>
                                </tr>
                                <tr>
                                    <td>¿Set conectado?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Pinza de seguridad cerrada?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Regulador de goteo cerrado?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Aprueba la inspección física del contenedor?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>
                        </td>
                        <td class="border-0"></td>
                        <td class="border-0">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td colspan="3" class="text-center"><strong>Inspección del contenido</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 60%"></td>
                                    <td class="text-center" style="width: 20%">si</td>
                                    <td class="text-center" style="width: 20%">no</td>
                                </tr>
                                <tr>
                                    <td>¿La coloración es apropiada?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Es homogéneo?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Presenta partículas?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Presenta turbidez?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Volumen correcto?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>¿Aprueba la inspección del contenido?</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="border-0"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="border-0">Peso de la mezcla (g): ______________</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <table style="width: 70%; border-collapse: collapse; padding: 0.5rem">
                    <tr>
                        <td class="text-right"><strong>LA MEZCLA SE CONSIDERA APROBADA:</strong></td>
                        <td>SI</td>
                        <td>NO</td>
                    </tr>
                </table>
                <table style="margin-top: 0.5rem; padding: 0.5rem">
                    <tr>
                        <td colspan="1">Observaciones:</td>
                    </tr>
                    <tr>
                        <td class="border-0"></td>
                    </tr>
                </table>
                <table style="margin-top: 1rem;">
                    <tr>
                        <td class="border-0" style="width: 40%">
                            <table>
                                <tr>
                                    <td class="border-0 border-t-1" style="margin: 0; padding: 0;"></td>
                                </tr>
                                <tr>
                                    <td class="text-center border-0" style="margin: 0; padding: 0">
                                        <strong>Inspeccionó</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center border-0" style="margin: 0; padding: 0">Sello o nombre y
                                        rúbrica</td>
                                </tr>
                                <tr>
                                    <td class="border-0" style="margin: 0; padding: 0">.</td>
                                </tr>
                            </table>
                        </td>
                        <td class="border-0" style="width: 20%"></td>
                        <td class="border-0" style="width: 40%">
                            <table>
                                <tr>
                                    <td class="border-0 border-t-1" style="margin: 0; padding: 0"></td>
                                </tr>
                                <tr>
                                    <td class="text-center border-0" style="margin: 0; padding: 0">
                                        <strong>Aprobó</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center border-0" style="margin: 0; padding: 0">Unidad de Calidad
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center border-0" style="margin: 0; padding: 0">Nombre o sello y
                                        firma</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>



</body>

</html>

