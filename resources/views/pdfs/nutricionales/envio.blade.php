<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Registro de Envío</title>

    <style>
        @page {
            margin: 1rem;
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
            padding: 2px 3px;
            font-size: ninepx;
            font-size: 9px;
        }

        th {
            background-color: #DEEAF6;
        }

        p {
            font-size: 10px
        }

        .tabla-format table {
            table-layout: fixed;
            border: 1px solid black;
        }

        .tabla-format td {
            border: 1px solid black;
        }

        .tabla-format td {
            word-wrap: break-word;
            overflow: auto;
        }

        .text-center {
            text-align: center
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <div>
            <table class="introduccion">
                <tr>
                    <td style="width: 25%">
                        <img style="width: 10rem; margin: 1rem 0" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                    </td>
                    <td style="width: 50%; margin: 0 auto; text-align: center; font-weight: bold; font-size: 15px">
                        <strong>{{ $solicitud_detalles->solicitud_detail->hospital_destino ? $solicitud_detalles->solicitud_detail->hospital_destino : $solicitud_detalles->user->hospital->name }}</strong>
                    </td>
                    <td style="width: 25%"></td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="border: none; border-top: 1px solid black; font-weight: bold">
                        Fecha de envío:
                        <span>{{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_detail['fecha_hora_entrega'])) }}</span>
                    </td>
                    <td style="text-align: right; border: none; border-top: 1px solid black;">DOMICILIO CLIENTE
                        RECEPTOR:</td>
                </tr>
                <tr>
                    <td style="border: none; font-weight: bold">
                        No. {{ str_pad($solicitud_detalles->id, 6, '0', STR_PAD_LEFT) }}
                    </td>
                    <td style="text-align: right; border: none">{{ $solicitud_detalles->user->hospital->adress }}</td>
                </tr>
            </table>

            <table style="tabla-format">
                <tr>
                    <td
                        style="text-align: center; border-top: 1px solid black; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold; padding: 8px 0">
                        <strong>DATOS DEL PACIENTE</strong>
                    </td>
                </tr>
            </table>

            <table style="tabla-format">
                <tr>
                    <th style="background: #D9E2F3; width: 40%; padding: 8px 0; text-align: center">NOMBRE COMPLETO</th>
                    <th style="background: #D9E2F3; width: 20%; text-align: center">FECHA DE NACIMIENTO</th>
                    <th style="background: #D9E2F3; width: 10%; text-align: center">EDAD(a)</th>
                    <th style="background: #D9E2F3; width: 10%; text-align: center">GENERO</th>
                    <th style="background: #D9E2F3; width: 20%; text-align: center">SUPERFICIE CORPORAL (m2)</th>
                </tr>
                <tr>
                    <td>{{ $solicitud_detalles->solicitud_patient['nombre_paciente'] }}
                        {{ $solicitud_detalles->solicitud_patient['apellidos_paciente'] }}</td>
                    <td style="text-align: center">
                        {{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_patient['fecha_nacimiento'])) }}</td>
                    <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['edad'] }}</td>
                    <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['sexo'] }}</td>
                    <td style="text-align: center">S/D</td>
                </tr>
            </table>

            <table style="tabla-format">
                <tr>
                    <th style="background: #D9E2F3; border-top: none; width: 40%; padding: 8px 0; text-align: center">
                        DIAGNOSTICO</th>
                    <th style="background: #D9E2F3; border-top: none; width: 20%; text-align: center">SERVICIOS</th>
                    <th style="background: #D9E2F3; border-top: none; width: 20%; text-align: center">No. De EXPEDIENTE
                    </th>
                    <th style="background: #D9E2F3; border-top: none; width: 20%; text-align: center">MÉDICO TRATANTE
                    </th>
                </tr>
                <tr>
                    <td>{{ $solicitud_detalles->solicitud_patient['diagnostico'] }}</td>
                    <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['servicio'] }}</td>
                    <td style="text-align: center"></td>
                    <td style="text-align: center">{{ $solicitud_detalles->solicitud_detail['nombre_medico'] }}</td>
                </tr>
            </table>

            {{-- Tira de encabezado doble como en Remisión --}}
            <table style="tabla-format">
                <tr>
                    <td
                        style="text-align: center; border-top: 1px solid black; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold; padding: 8px 0; width: 50%">
                        <strong>DATOS DE LAS MEZCLAS</strong>
                    </td>
                    <td
                        style="text-align: center; border-top: 1px solid black; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold; padding: 8px 0; width: 50%">
                        <strong>COSTO MEDICAMENTO</strong>
                    </td>
                </tr>
            </table>

            <table class="tg">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Medicamentos</th>
                        <th class="text-center">Dosis</th>
                        <th class="text-center">Lote</th>
                        <th class="text-center">Volumen</th>
                        <th class="text-center">Volumen con Overfill</th>
                        <th class="text-center">PRECIO (ml)</th>
                        <th class="text-center">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Volumen total mostrado al pie (tal como ya lo tenías)
                        if (
                            empty($solicitud_detalles->solicitud_detail['volumen_total']) ||
                            $solicitud_detalles->solicitud_detail['volumen_total'] == 0
                        ) {
                            $vol_total = (float) ($solicitud_detalles->solicitud_detail['suma_volumen'] ?? 0);
                        } else {
                            $vol_total = (float) ($solicitud_detalles->solicitud_detail['volumen_total'] ?? 0);
                        }
                        $lote = $solicitud_detalles->lote ?? '';

                        // Acumulador de totales de costo
                        $total = 0.0;
                    @endphp

                    @foreach ($inputs_solicitud as $input_completo)
                        @php
                            // Dosis formateada (0 o 3 decimales según traiga punto)
                            $valor_formateado =
                                strpos((string) ($input_completo['valor'] ?? ''), '.') !== false
                                    ? number_format((float) $input_completo['valor'], 3, '.', '')
                                    : number_format((float) ($input_completo['valor'] ?? 0), 0);

                            // Cantidades de volumen
                            $vol_ml = (float) number_format((float) ($input_completo->valor_ml ?? 0), 3, '.', '');
                            $vol_over = (float) number_format(
                                (float) ($input_completo->valor_sobrellenado ?? 0),
                                3,
                                '.',
                                '',
                            );

                            // Cantidad final a cobrar (usa sobrellenado si existe, igual que en Remisión)
                            $cantidadFinalMl = $vol_over > 0 ? $vol_over : $vol_ml;

                            $precioMl = 0.0;

                            if ((float) ($input_completo?->precio_ml ?? 0) > 0) {
                                $precioMl = (float) $input_completo->precio_ml;
                            } elseif (
                                (float) ($input_completo?->presentation?->listItems?->first()?->precio_ml ?? 0) > 0
                            ) {
                                $precioMl = (float) $input_completo->presentation->listItems->first()->precio_ml;
                            } else {
                                $precioMl =
                                    (float) ($input_completo?->input?->nutritionMedicineCatalog?->presentations
                                        ?->first()
                                        ?->listItems?->first()?->precio_ml ?? 0);
                            }

                            $subtotal = $cantidadFinalMl * $precioMl;
                            $total += $subtotal;

                            $nombreGenericoDoc =
                                $input_completo?->presentation?->catalog?->denominacion_generica ??
                                ($input_completo?->input?->nutritionMedicineCatalog?->denominacion_generica ??
                                    ($input_completo?->input?->description ?? 'Medicamento no disponible'));

                            $nombreComercialDoc =
                                $input_completo?->presentation?->denominacion_comercial ??
                                ($input_completo?->input?->nutritionMedicineCatalog?->presentations?->first()?->denominacion_comercial ?? null);

                            $nombreMedicamentoDoc = $nombreGenericoDoc;

                            if (($imprimirMarcas ?? false) && !empty(trim((string) $nombreComercialDoc)) && strcasecmp($nombreGenericoDoc, $nombreComercialDoc) !== 0) {
                                $nombreMedicamentoDoc = "{$nombreGenericoDoc} ({$nombreComercialDoc})";
                            }
                        @endphp

                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>

                            <td>
                                {{ $nombreMedicamentoDoc }}
                            </td>

                            <td class="text-center">
                                {{ $valor_formateado }}
                                {{ isset($input_completo->input->unidad) ? explode('/', $input_completo->input->unidad)[0] : '' }}
                            </td>
                            {{-- Lote en una sola celda con rowspan --}}
                            @if ($loop->first)
                                <td class="text-center" rowspan="{{ count($inputs_solicitud) }}">{{ $lote }}
                                </td>
                            @endif

                            <td>
                                {{ rtrim(rtrim(number_format($vol_ml, 3, '.', ''), '0'), '.') }} mL
                            </td>

                            <td>
                                @if ($vol_over == 0)
                                    {{ rtrim(rtrim(number_format($vol_ml, 3, '.', ''), '0'), '.') }} mL
                                @else
                                    {{ rtrim(rtrim(number_format($vol_over, 3, '.', ''), '0'), '.') }} mL
                                @endif
                            </td>

                            <td class="text-center">
                                ${{ number_format($precioMl, 3, '.', '') }}
                            </td>

                            <td class="text-center">
                                ${{ number_format($subtotal, 3, '.', '') }}
                            </td>


                        </tr>
                    @endforeach
                    @php
                        // Número consecutivo después de los medicamentos
                        $contador = count($inputs_solicitud) + 1;
                    @endphp

                    @isset($servicio_preparacion)
                        <tr>
                            <td class="text-center">{{ $contador }}</td>
                            <td>
                                <strong>{{ $servicio_preparacion->denominacion_generica }}</strong>
                            </td>
                            {{-- Dosis --}}
                            <td class="text-center"></td>
                            {{-- Volumen --}}
                            <td></td>
                            {{-- Volumen con Overfill --}}
                            <td></td>

                            <td></td>

                            {{-- PRECIO (ml) --}}
                            <td class="text-center">
                                ${{ number_format((float) ($servicio_preparacion->precio_ml ?? 0), 3, '.', '') }}
                            </td>

                            {{-- SUBTOTAL (para este servicio es el mismo precio unitario) --}}
                            <td class="text-center">
                                @php
                                    $subtotal_servicio = (float) ($servicio_preparacion->precio_ml ?? 0);
                                    $total += $subtotal_servicio; // Se suma al total general
                                @endphp
                                ${{ number_format($subtotal_servicio, 3, '.', '') }}
                            </td>

                            {{-- Lote (vacío, porque el lote ya se mostró en el rowspan de las mezclas) --}}

                        </tr>
                    @endisset

                    {{-- Fila de volúmenes totales (se mantiene) --}}
                    <tr>
                        <td></td>
                        <td>VOLUMEN TOTAL</td>
                        <td></td>
                        <td></td>
                        <td>{{ number_format($vol_total, 2) }} mL</td>
                        <td>{{ $solicitud_detalles->solicitud_detail->volumen_total_final }} mL</td>
                        <td></td>
                        <td></td>
                    </tr>

                    {{-- Fila de TOTAL en dinero --}}
                    <tr>
                        <td colspan="8" style="text-align: right; background: #F2F2F2">
                            <strong>Total ${{ number_format($total, 3, '.', '') }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table>
                <tr>
                    <td style="padding-left: 5px">
                        <strong>FECHA/HORA DE PREPARACIÓN:

                            @if ($solicitud_detalles->fecha_hora_preparacion)
                                {{ \Carbon\Carbon::parse($solicitud_detalles->fecha_hora_preparacion)->format('d-m-Y H:i') }}h
                            @else
                                â€”
                            @endif

                        </strong>
                    </td>

                    <td style="padding-left: 5px">
                        <strong>FECHA/HORA LÍMITE DE USO:

                            @if ($solicitud_detalles->fecha_hora_limite_uso)
                                {{ \Carbon\Carbon::parse($solicitud_detalles->fecha_hora_limite_uso)->format('d-m-Y H:i') }}h
                            @else
                                â€”
                            @endif

                        </strong>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="border: none"><strong>Observaciones:</strong></td>
                </tr>
                <tr>
                    <td>{{ $solicitud_detalles->solicitud_detail->observaciones }}</td>
                </tr>
            </table>
            <br>

            <table style="margin: 0 9rem; margin-bottom: 1rem;">
                <tr>
                    <td style="border: none; font-size: 11px"><strong>Recepcion Institucion</strong></td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 11px">
                        Fecha:_____________________________________________________________</td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 11px">Hora de
                        recibido:_____________________________________________________</td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 11px">
                        Temperatura:________________________________________________________</td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 11px">Nombre completo/firma y
                        sello:__________________________________________</td>
                </tr>
            </table>

            <table style="width: 80%; margin: 0 auto;">
                <tr>
                    <td style="text-align: left; border: none; padding-top: 2rem; font-size: 8px;">
                        <strong>NOTA IMPORTANTE:</strong>
                        La institucion reconoce que la mezcla estéril entregada debe ser mantenida bajo condiciones
                        adecuadas de almacenamiento, asegurando la conservación de la red fría en todo momento.
                        <br>
                        El centro de mezcla no asume ninguna responsabilidad por el deterioro o pérdida de eficacia del
                        producto debido a un manejo inadecuado posterior a la entrega.
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>


