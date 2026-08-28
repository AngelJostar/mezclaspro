@php
    function ajustarUnidad($unidad, $npt)
    {
        if ($npt === 'ADULT') {
            if ($unidad === 'g/Kg') {
                return 'g/día';
            } elseif ($unidad === 'mEq/Kg') {
                return 'mEq/día';
            }
        }

        return $unidad;
    }

    function nombreGenericoNutri($item)
    {
        return $item?->presentation?->catalog?->denominacion_generica ??
            ($item?->input?->nutritionMedicineCatalog?->denominacion_generica ??
                ($item?->input?->description ?? 'Medicamento no disponible'));
    }

    function nombreComercialNutri($item)
    {
        return $item?->presentation?->denominacion_comercial ??
            ($item?->input?->nutritionMedicineCatalog?->presentations?->first()?->denominacion_comercial ??
                'Medicamento no disponible');
    }

    function nombreDocumentoNutri($item, $imprimirMarcas = false)
    {
        $descripcionRemision = descripcionRemisionNutri($item);

        if ($descripcionRemision !== '') {
            return $descripcionRemision;
        }

        $generico = nombreGenericoNutri($item);

        if (!$imprimirMarcas) {
            return $generico;
        }

        $comercial = trim((string) nombreComercialNutri($item));

        if ($comercial === '' || $comercial === 'Medicamento no disponible' || strcasecmp($generico, $comercial) === 0) {
            return $generico;
        }

        return "{$generico} ({$comercial})";
    }

    function descripcionRemisionNutri($item)
    {
        $listItem = $item?->presentation?->listItems?->first();

        if (!$listItem) {
            $presentations = $item?->input?->nutritionMedicineCatalog?->presentations ?? collect();
            $listItem = $presentations
                ->map(fn ($presentation) => $presentation?->listItems?->first())
                ->filter()
                ->first();
        }

        return trim((string) ($listItem?->descripcion_remision ?? ''));
    }

    function presentacionNutri($item)
    {
        return $item?->presentation?->presentacion ??
            ($item?->input?->nutritionMedicineCatalog?->presentations?->first()?->presentacion ?? '—');
    }

    function precioNutri($item)
    {
        // 1. Precio guardado directamente en solicitud_inputs
        if ((float) ($item?->precio_ml ?? 0) > 0) {
            return (float) $item->precio_ml;
        }

        // 2. Precio desde presentación directa
        $precioDirecto = $item?->presentation?->listItems?->first()?->precio_ml;

        if ((float) ($precioDirecto ?? 0) > 0) {
            return (float) $precioDirecto;
        }

        // 3. Precio desde la primera presentación del catálogo
        $precioCatalogo = $item?->input?->nutritionMedicineCatalog?->presentations?->first()?->listItems?->first()
            ?->precio_ml;

        if ((float) ($precioCatalogo ?? 0) > 0) {
            return (float) $precioCatalogo;
        }

        return 0;
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remisión</title>

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
            font-size: 9px;
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

        .text-center {
            text-align: center;
        }

        .salto-pagina {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $prodifemLogoFile = public_path('img/logo-cbta.jpg');
        $prodifemLogoSrc = file_exists($prodifemLogoFile) ? 'file://' . $prodifemLogoFile : asset('img/logo-cbta.jpg');

        $distNombre = $distributor->nombre ?? null;
        $distDireccion = $distributor->direccion ?? null;
        $distRfc = $distributor->rfc ?? null;
        $distContacto = $distributor->contacto ?? null;
        $distAdditionalInformation = $distributor->informacion_adicional ?? null;
        $contractNumber = !empty($priceList?->has_contract) ? ($priceList->contract_number ?? null) : null;
        $contractInformation = !empty($priceList?->has_contract) ? ($priceList->contract_information ?? null) : null;
        $distLogoSrc = null;

        if (!empty($distributor?->logo_path)) {
            $distLogoFile = public_path('storage/' . $distributor->logo_path);
            if (file_exists($distLogoFile)) {
                $distLogoSrc = 'file://' . $distLogoFile;
            }
        }

        $mostrarDistribuidor =
            !empty(trim((string) $distNombre)) ||
            !empty(trim((string) $distDireccion)) ||
            !empty(trim((string) $distAdditionalInformation)) ||
            !empty($distLogoSrc);

        $paginas = $mostrarDistribuidor ? [1, 2] : [1];
    @endphp

    @foreach ($paginas as $pagina)
        @php
            $esDistribuidor = $pagina === 2;
            $nombreEncabezado = $esDistribuidor
                ? ($distNombre ?: 'Distribuidor')
                : ($solicitud_detalles->solicitud_detail->hospital_destino
                    ? $solicitud_detalles->solicitud_detail->hospital_destino
                    : $solicitud_detalles->user->hospital->name);
            $direccionEncabezado = $esDistribuidor
                ? ($distDireccion ?: '—')
                : ($solicitud_detalles->user->hospital->adress ?? '—');
            $logoEncabezado = $esDistribuidor ? ($distLogoSrc ?: $prodifemLogoSrc) : $prodifemLogoSrc;
        @endphp
        <div class="{{ $pagina === 2 ? 'salto-pagina' : '' }}">
            <div class="contenedor">
                <div>
                    <table class="introduccion"
                        style="margin-top: {{ $pagina === 1 ? '1rem' : '0.5rem' }}; margin-bottom: {{ $pagina === 1 ? '1rem' : '0.5rem' }}">
                        <tr>
                            <td style="width: 25%">
                                <img style="width: 10rem" src="{{ $logoEncabezado }}" alt="">
                            </td>
                            <td
                                style="width: 50%; margin: 0 auto; text-align: center; font-weight: bold; font-size: 15px">
                                <strong>
                                    {{ $nombreEncabezado }}
                                </strong>
                            </td>
                            <td style="width: 25%"></td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td style="border: none; border-top: 1px solid black; font-weight: bold">
                                Fecha de envío:
                                {{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_detail['fecha_hora_entrega'])) }}
                            </td>
                            <td style="text-align: right; border: none; border-top: 1px solid black;">
                                DOMICILIO INSTITUCION RECEPTORA:
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; font-weight: bold">
                                No.
                                {{ str_pad($solicitud_detalles->id, 6, '0', STR_PAD_LEFT) }}
                            </td>
                            <td style="text-align: right; border: none">
                                {{ $direccionEncabezado }}
                            </td>
                        </tr>
                    </table>

                    @if ($esDistribuidor && ($distRfc || $distContacto))
                        <table>
                            <tr>
                                <td style="border: none"><strong>RFC:</strong> {{ $distRfc ?: '—' }}</td>
                                <td style="border: none; text-align: right"><strong>Contacto:</strong> {{ $distContacto ?: '—' }}</td>
                            </tr>
                        </table>
                    @endif

                    @if ($esDistribuidor && $distAdditionalInformation)
                        <table>
                            <tr>
                                <td style="border: none">{!! nl2br(e($distAdditionalInformation)) !!}</td>
                            </tr>
                        </table>
                    @endif

                    @if ($contractNumber)
                        <table>
                            <tr>
                                <td style="border: none"><strong>Contrato:</strong> {{ $contractNumber }}</td>
                                <td style="border: none; text-align: right">{!! nl2br(e($contractInformation)) !!}</td>
                            </tr>
                        </table>
                    @endif

                    <table>
                        <tr>
                            <td
                                style="text-align: center; border-top: 1px solid black; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold;">
                                <strong>DATOS DEL PACIENTE</strong>
                            </td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <th style="background: #D9E2F3; width: 40%; text-align: center">NOMBRE COMPLETO</th>
                            <th style="background: #D9E2F3; width: 20%; text-align: center">FECHA DE NACIMIENTO</th>
                            <th style="background: #D9E2F3; width: 10%; text-align: center">EDAD(a)</th>
                            <th style="background: #D9E2F3; width: 10%; text-align: center">GENERO</th>
                            <th style="background: #D9E2F3; width: 20%; text-align: center">SUPERFICIE CORPORAL (m2)
                            </th>
                        </tr>
                        <tr>
                            <td style="text-align: center">
                                {{ $solicitud_detalles->solicitud_patient['nombre_paciente'] }}
                                {{ $solicitud_detalles->solicitud_patient['apellidos_paciente'] }}
                            </td>
                            <td style="text-align: center">
                                {{ date('d-m-Y', strtotime($solicitud_detalles->solicitud_patient['fecha_nacimiento'])) }}
                            </td>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['edad'] }}</td>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['sexo'] }}</td>
                            <td style="text-align: center">S/D</td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <th style="border-top: none; background: #D9E2F3; width: 40%; text-align: center">
                                DIAGNOSTICO</th>
                            <th style="border-top: none; background: #D9E2F3; width: 20%; text-align: center">SERVICIOS
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 20%; text-align: center">No. De
                                EXPEDIENTE</th>
                            <th style="border-top: none; background: #D9E2F3; width: 20%; text-align: center">MÉDICO
                                TRATANTE</th>
                        </tr>
                        <tr>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['diagnostico'] }}
                            </td>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['servicio'] }}
                            </td>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_patient['registro'] }}
                            </td>
                            <td style="text-align: center">{{ $solicitud_detalles->solicitud_detail['nombre_medico'] }}
                            </td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td
                                style="border-top: 1px solid black; width: 50%; text-align: center; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold; padding: 8px 0">
                                <strong>DATOS DE LAS MEZCLAS</strong>
                            </td>
                            <td
                                style="border-top: 1px solid black; width: 50%; text-align: center; border-bottom: none; background-color: #1F4E78; color: white; font-weight: bold; padding: 8px 0">
                                <strong>COSTO MEDICAMENTO</strong>
                            </td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <th style="border-top: none; background: #D9E2F3; width: 5%; text-align: center;">
                                <strong>No</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 30%; text-align: center">
                                <strong>MEDICAMENTO</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 9%; text-align: center">
                                <strong>DOSIS</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 12%; text-align: center">
                                <strong>ALMACÉN</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 12%; text-align: center">
                                <strong>PRESENTACIÓN</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 9%; text-align: center">
                                <strong>CANTIDAD</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 8%; text-align: center">
                                <strong>PRECIO (ml)</strong>
                            </th>
                            <th style="border-top: none; background: #D9E2F3; width: 15%; text-align: center">
                                <strong>SUBTOTAL</strong>
                            </th>
                        </tr>

                        @php
                            $total = 0;
                            $contador = 0;

                            if (
                                empty($solicitud_detalles->solicitud_detail['volumen_total']) ||
                                $solicitud_detalles->solicitud_detail['volumen_total'] == 0
                            ) {
                                $vol_total = (float) ($solicitud_detalles->solicitud_detail['suma_volumen'] ?? 0);
                            } else {
                                $vol_total = (float) ($solicitud_detalles->solicitud_detail['volumen_total'] ?? 0);
                            }
                        @endphp

                        @foreach ($inputs_solicitud as $input_completo)
                            <tr>
                                <td style="text-align: center">
                                    {{ $loop->iteration }}
                                    @php
                                        $contador = $loop->iteration;
                                    @endphp
                                </td>

                                <td>
                                    <strong>
                                        {{ nombreDocumentoNutri($input_completo, $imprimirMarcas ?? false) }}
                                    </strong>
                                </td>

                                @php
                                    $valor = (string) ($input_completo['valor'] ?? '');
                                    $valor_formateado =
                                        strpos($valor, '.') !== false
                                            ? number_format((float) $valor, 3, '.', '')
                                            : number_format((float) $valor, 0);
                                @endphp

                                <td style="text-align: center">
                                    {{ $valor_formateado }}
                                    {{ ajustarUnidad($input_completo->input->unidad ?? '', $solicitud_detalles->solicitud_detail['npt']) }}
                                </td>

                                <td style="text-align: center">
                                    {{ $almacenesPorSolicitudInput[$input_completo->id] ?? '—' }}
                                </td>

                                <td style="text-align: center">
                                    {{ presentacionNutri($input_completo) }}
                                </td>

                                @php
                                    if (
                                        $solicitud_detalles->solicitud_detail['sobrellenado_ml'] == null ||
                                        $solicitud_detalles->solicitud_detail['sobrellenado_ml'] == 0
                                    ) {
                                        $valor_final = number_format((float) $input_completo['valor_ml'], 3, '.', '');
                                    } else {
                                        $valor_final = number_format(
                                            (float) $input_completo['valor_sobrellenado'],
                                            3,
                                            '.',
                                            '',
                                        );
                                    }

                                    $precioInput = precioNutri($input_completo);
                                    $subtotalInput = ((float) $valor_final) * $precioInput;

                                    $total += $subtotalInput;
                                @endphp

                                <td>{{ $valor_final }} mL</td>

                                <td>
                                    ${{ number_format($precioInput, 3, '.', '') }}
                                </td>

                                <td style="text-align: center">
                                    ${{ number_format($subtotalInput, 3, '.', '') }}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            @php
                                $contador++;
                                $precioBolsaEva = precioNutri($bolsa_eva);
                                $total += $precioBolsaEva;
                            @endphp

                            <td style="text-align: center">{{ $contador }}</td>
                            <td>
                                <strong>
                                    {{ nombreDocumentoNutri($bolsa_eva, $imprimirMarcas ?? false) }}
                                    <span style="font-size: 8px; font-weight: normal;">(IVA incluido)</span>
                                </strong>
                            </td>
                            <td style="text-align: center;"></td>
                            <td style="text-align: center;">
                                {{ $almacenesPorSolicitudInput[$bolsa_eva?->id] ?? '—' }}
                            </td>
                            <td style="text-align: center">
                                {{ presentacionNutri($bolsa_eva) }}
                            </td>
                            <td>1 pza</td>
                            <td>${{ number_format($precioBolsaEva, 3, '.', '') }}</td>
                            <td style="text-align: center">
                                ${{ number_format($precioBolsaEva, 3, '.', '') }}
                            </td>
                        </tr>

                        @if ($set_infusion)
                            <tr>
                                @php
                                    $contador++;
                                    $precioSet = precioNutri($set_infusion);
                                    $total += $precioSet;
                                @endphp

                                <td style="text-align: center">{{ $contador }}</td>
                                <td>
                                    <strong>
                                        {{ nombreDocumentoNutri($set_infusion, $imprimirMarcas ?? false) }}
                                        <span style="font-size: 8px; font-weight: normal;">(IVA incluido)</span>
                                    </strong>
                                </td>
                                <td style="text-align: center"></td>
                                <td style="text-align: center">
                                    {{ $almacenesPorSolicitudInput[$set_infusion?->id] ?? '—' }}
                                </td>
                                <td style="text-align: center">
                                    {{ presentacionNutri($set_infusion) }}
                                </td>
                                <td>1 pza</td>
                                <td>${{ number_format($precioSet, 3, '.', '') }}</td>
                                <td style="text-align: center">
                                    ${{ number_format($precioSet, 3, '.', '') }}
                                </td>
                            </tr>
                        @endif

                        @foreach (($pricingSummary['additional_charge_lines'] ?? collect()) as $charge)
                            @php
                                $contador++;
                                $precioCargo = (float) ($charge['total'] ?? 0);
                                $total += $precioCargo;
                            @endphp
                            <tr>
                                <td style="text-align: center">{{ $contador }}</td>
                                <td><strong>{{ $charge['description'] }} <span style="font-size: 8px; font-weight: normal;">(IVA incluido)</span></strong></td>
                                <td style="text-align: center"></td>
                                <td style="text-align: center"></td>
                                <td style="text-align: center"></td>
                                <td>1 {{ $charge['unit_label'] ?? 'pieza' }}</td>
                                <td>${{ number_format($precioCargo, 3, '.', '') }}</td>
                                <td style="text-align: center">${{ number_format($precioCargo, 3, '.', '') }}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <td></td>
                            <td style="text-align: left;"><strong>VOLUMEN TOTAL</strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ number_format((float) $solicitud_detalles->solicitud_detail->volumen_total_final, 2) }}
                                mL</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td style="text-align: right; border-top: none">
                                Subtotal antes de IVA
                                ${{ number_format((float) ($pricingSummary['subtotal_before_vat'] ?? 0), 2, '.', ',') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-top: none">
                                IVA cargos adicionales (16%)
                                ${{ number_format((float) ($pricingSummary['additional_charges_vat'] ?? 0), 2, '.', ',') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-top: none">
                                IVA insumos gravados (16%)
                                ${{ number_format((float) ($pricingSummary['supplies_vat'] ?? 0), 2, '.', ',') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-top: none">
                                <strong>
                                    Total IVA incluido
                                    ${{ number_format((float) ($pricingSummary['total_iva_included'] ?? $total), 2, '.', ',') }}
                                </strong>
                            </td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td style="border: none; padding-top: 4px;">
                                <strong>Lote de la mezcla:</strong> {{ $solicitud_detalles->lote ?? '—' }}
                            </td>
                        </tr>
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
                                Fecha:_____________________________________________________________
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; font-size: 11px">
                                Hora de recibido:_____________________________________________________
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; font-size: 11px">
                                Temperatura:________________________________________________________
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; font-size: 11px">
                                Nombre completo/firma y sello:__________________________________________
                            </td>
                        </tr>
                    </table>
                </div>

                <table style="width: 80%; margin: 0 auto;">
                    <tr>
                        <td style="text-align: left; border: none; padding-top: 2rem; font-size: 8px;">
                            <strong>NOTA IMPORTANTE:</strong>
                            La institucion reconoce que la mezcla estéril entregada debe ser mantenida bajo condiciones
                            adecuadas de almacenamiento, asegurando la conservación de la red fría en todo momento.
                            <br>
                            El centro de mezcla no asume ninguna responsabilidad por el deterioro o pérdida de eficacia
                            del producto debido a un manejo inadecuado posterior a la entrega.
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>

</html>
