<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Remisión</title>

    <style>
        @page {
            margin: 1rem;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: white;
            font-family: "Arial", sans-serif;
        }

        .bg-cbta {
            background-color: #1F4E78;
            color: white;
        }

        .contenedor {
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 0px solid black;
            padding: 0px;
            font-size: 12px;
        }

        .border-1 {
            border: 1px solid black;
        }

        .border-x-1 {
            border-left: 1px solid black;
            border-right: 1px solid black;
        }

        .border-l-0 {
            border-left: none;
        }

        .border-r-0 {
            border-right: none;
        }

        .border-t-1 {
            border-top: 1px solid black;
        }

        .border-b-1 {
            border-bottom: 1px solid black;
        }

        .px-1 {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }

        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mt-12 {
            margin-top: 3rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
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

        .font-bold {
            font-weight: bold;
        }

        .fila-presentacion td {
            font-size: 11px;
            background-color: #E9F2FF;
        }

        .bg-presentacion {
            background-color: #E9F2FF;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    @php
        // ===== Helpers de formato (sin "use Carbon\Carbon") =====
        $fmtDate = function ($v) {
            if (!$v) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($v)->format('d/m/Y');
            } catch (\Exception $e) {
                return '—';
            }
        };

        $fmtDateTime = function ($v) {
            if (!$v) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($v)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return '—';
            }
        };

        $money = function ($v) {
            return '$' . number_format((float) $v, 2, '.', ',');
        };

        // ===== Datos del paciente / solicitud =====
        $pacienteNombre = $solicitud->nombre_paciente ?? '—';
        $fechaNac = $solicitud->fecha_nacimiento ? $fmtDate($solicitud->fecha_nacimiento) : '—';
        $observaciones = $solicitud->observaciones ?? '—';

        $edad =
            $solicitud->edad ??
            ($solicitud->fecha_nacimiento ? \Carbon\Carbon::parse($solicitud->fecha_nacimiento)->age : '—');

        $sexo = $solicitud->sexo === 'M' ? 'Masculino' : ($solicitud->sexo === 'F' ? 'Femenino' : '—');
        $alergias = $solicitud->alergias ?? '—';
        $diagnostico = $solicitud->diagnostico ?? '—';
        $servicio = $solicitud->servicio ?? '—';
        $expediente = $solicitud->registro_paciente ?? '—';
        $medico = $solicitud->nombre_medico ?? '—';

        // ===== Emisor 1: Prodifem (siempre) =====
        $prodifemLogoFile = public_path('img/logo-cbta.jpg');
        $prodifemLogoSrc = file_exists($prodifemLogoFile) ? 'file://' . $prodifemLogoFile : null;

        // ===== Emisor 2: Distribuidor (opcional) =====
        $distNombre = $distributor->nombre ?? ($distributor->name ?? null);
        $distDireccion = $distributor->direccion ?? ($distributor->address ?? null);
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
        if (!$distLogoSrc) {
            $distLogoSrc = $prodifemLogoSrc;
        }

        // Contador para la tabla
        $contador = 1;
    @endphp

    {{-- =========================================================
        HOJA 1: PRODIFEM
    ========================================================== --}}
    @unless ($subdistributorOnly ?? false)
    <div class="contenedor border-1">
        <!-- Encabezado -->
        <div class="introduccion">
            <table style="padding-top: 0.5rem">
                <tr>
                    <td style="width: 20%">
                        @if ($prodifemLogoSrc)
                            <img style="width: 10rem;" src="{{ $prodifemLogoSrc }}" alt="Logo Prodifem">
                        @else
                            <span style="font-size:10px;">LOGO NO DISPONIBLE</span>
                        @endif
                    </td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>REMISIÓN <br> MEZCLAS ESTÉRILES ONCOLÓGICAS</strong>
                    </td>
                    <td style="width: 20%"></td>
                </tr>
            </table>
        </div>

        <table>
            <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                <td style="text-align: center;">ENTREGA DE LAS MEZCLAS ONCOLÓGICAS PREPARADAS EN CMP</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="px-1">
                    Fecha de envío:
                    <strong>{{ $fmtDateTime($fechaEmision) }}</strong>
                </td>
                <td class="px-1 text-right">No. Remisión: {{ $mezclas->first()?->remision ?? '—' }}</td>
            </tr>
        </table>

        @if ($contractNumber)
            <table>
                <tr>
                    <td class="px-1"><strong>Contrato:</strong> {{ $contractNumber }}</td>
                    <td class="px-1 text-right">{!! nl2br(e($contractInformation)) !!}</td>
                </tr>
            </table>
        @endif

        <!-- Datos del paciente -->
        <table>
            <tr>
                <td class="px-1 text-center">DATOS DEL PACIENTE</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 border-l-0 px-1 bg-cbta text-center font-bold">Nombre completo</td>
                <td class="border-1 px-1 bg-cbta text-center font-bold">Fecha de nacimiento</td>
                <td class="border-1 px-1 bg-cbta text-center font-bold">Edad</td>
                <td class="border-1 px-1 bg-cbta text-center font-bold">Genero</td>
                <td class="border-1 border-r-0 px-1 bg-cbta text-center font-bold">Alergias</td>
            </tr>
            <tr>
                <td class="border-x-1 border-l-0 px-1 text-center">{{ $pacienteNombre }}</td>
                <td class="border-x-1 px-1 text-center">{{ $fechaNac }}</td>
                <td class="border-x-1 px-1 text-center">{{ $edad }}</td>
                <td class="border-x-1 px-1 text-center">{{ $sexo }}</td>
                <td class="border-x-1 border-r-0 px-1 text-center">{{ $alergias }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 border-l-0 px-1 bg-cbta text-center font-bold">Diagnóstico</td>
                <td class="border-1 px-1 bg-cbta text-center font-bold">Servicio</td>
                <td class="border-1 px-1 bg-cbta text-center font-bold">No. de Expediente</td>
                <td class="border-1 border-r-0 px-1 bg-cbta text-center font-bold">Médico tratante</td>
            </tr>
            <tr>
                <td class="border-1 border-l-0 px-1 text-center">{{ $diagnostico }}</td>
                <td class="border-1 px-1 text-center">{{ $servicio }}</td>
                <td class="border-1 px-1 text-center">{{ $expediente }}</td>
                <td class="border-1 border-r-0 px-1 text-center">{{ $medico }}</td>
            </tr>
        </table>

        <!-- Encabezado tabla remisión -->
        <table>
            <tr>
                <td class="text-center">DATOS DE LAS MEZCLAS</td>
                <td class="text-center">COSTO MEDICAMENTO</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 border-l-0 px-1 text-center bg-cbta font-bold">No</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Medicamento</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Dosis (mg)</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Diluyente</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Volumen mezcla</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Lote de la mezcla</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Unidad de cobro</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Cantidad</td>
                <td class="border-1 px-1 text-center bg-cbta font-bold">Precio unitario</td>
                <td class="border-1 border-r-0 px-1 text-center bg-cbta font-bold">Subtotal</td>
            </tr>

            @foreach ($mezclas as $mezcla)
                @php
                    $loteMezcla = $mezcla->lote ?? '—';
                    $volumenMezcla = isset($mezcla->volumen_dilucion) ? $mezcla->volumen_dilucion . ' ml' : '—';
                    $remisionMezcla = $mezcla->remision ?? '—';
                @endphp

                @forelse ($mezcla->medicamentos as $med)
                    @php
                        // ✅ SNAPSHOT primero, luego fallback
                        $denom =
                            $med->denominacion_snapshot ??
                            null ??
                            (optional(optional($med->medicamentoOnco)->catalog)->denominacion ??
                                ($med->nombre_medicamento ?? '—'));

                        $dosis = $med->dosis ?? 0;
                        $dosisFmt = is_numeric($dosis)
                            ? rtrim(rtrim(number_format($dosis, 2, '.', ''), '0'), '.')
                            : $dosis;

                        $diluyente = optional($med->diluyente)->denominacion_generica ?? '—';

                        $unidadCobro = $med->unidad_cobro ?? '—';
                        $cantidad = $med->cantidad_cobro ?? 0;
                        $precioUnit = $med->precio_unitario_calculado ?? 0;
                        $subtotal = $med->subtotal_calculado ?? 0;

                        $presentaciones = $med->presentacionesUsadas ?? collect();

                        // ✅ Presentaciones: preferir presentacion_snapshot
                        $presentacionesTexto = $presentaciones
                            ->map(function ($pres) {
                                $batch = $pres->batch;

                                // ✅ 1) Snapshot (nuevo)
                                $nombrePres = trim($pres->presentacion_snapshot ?? '');

                                // ✅ 2) Fallback a relación viva si no hay snapshot
                                if ($nombrePres === '') {
                                    $presBase = $pres->presentation;
                                    $nombrePres = trim($presBase->presentacion ?? '');
                                }

                                $linea1Partes = [];
                                if ($pres->unidades_usadas) {
                                    $linea1Partes[] = $pres->unidades_usadas . ' pza(s)';
                                }
                                if ($nombrePres) {
                                    $linea1Partes[] = $nombrePres;
                                }

                                $linea1 = '• ' . implode(' - ', $linea1Partes);

                                $lote = $pres->lote_usado ?? $batch?->lote;
                                $cad = $pres->caducidad_usada ?? $batch?->caducidad;

                                $linea2Partes = [];
                                if ($lote) {
                                    $linea2Partes[] = 'Lote ' . $lote;
                                }

                                if ($cad) {
                                    try {
                                        $linea2Partes[] = 'Cad. ' . \Carbon\Carbon::parse($cad)->format('d/m/Y');
                                    } catch (\Exception $e) {
                                        $linea2Partes[] = 'Cad. ' . $cad;
                                    }
                                }

                                $linea2 = count($linea2Partes) ? implode(' · ', $linea2Partes) : '';

                                return $linea2 ? $linea1 . '<br>&nbsp;&nbsp;' . $linea2 : $linea1;
                            })
                            ->implode('<br>');

                        if ($presentacionesTexto === '') {
                            $presentacionesTexto = 'Sin detalle de presentación.';
                        }
                    @endphp

                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                        <td class="border-1 px-1 text-center">{{ $denom }}</td>
                        <td class="border-1 px-1 text-center">
                            {{ is_numeric($dosisFmt) ? $dosisFmt . ' mg' : $dosisFmt }}
                        </td>
                        <td class="border-1 px-1 text-center">{{ $diluyente }}</td>
                        <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                        <td class="border-1 px-1 text-center">{{ $loteMezcla }}</td>
                        <td class="border-1 px-1 text-center">{{ ucfirst($unidadCobro) }}</td>
                        <td class="border-1 px-1 text-center">
                            @if ($unidadCobro === 'mg')
                                {{ rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.') }}
                            @else
                                {{ $cantidad }}
                            @endif
                        </td>
                        <td class="border-1 px-1 text-center">{{ $money($precioUnit) }}</td>
                        <td class="border-1 border-r-0 px-1 text-center">{{ $money($subtotal) }}</td>
                    </tr>

                    <tr class="fila-presentacion">
                        <td class="border-1 border-l-0 px-1"></td>
                        <td class="border-1 px-1 text-right font-bold">Presentaciones usadas:</td>
                        <td class="border-1 border-r-0 px-1 text-left bg-presentacion" colspan="9">
                            {!! $presentacionesTexto !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center" colspan="11">
                            Sin medicamentos en esta mezcla.
                        </td>
                    </tr>
                @endforelse

                {{-- ✅ Renglón extra: INFUSOR (si aplica en esta mezcla) --}}
                @if (($mezcla->infusor_aplica ?? false) && (float) ($mezcla->infusor_subtotal ?? 0) > 0)
                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                        <td class="border-1 px-1 text-center">
                            {{ 'Infusor: ' . ($mezcla->infusor_nombre ?? '—') }}
                        </td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                        <td class="border-1 px-1 text-center">{{ $loteMezcla }}</td>
                        <td class="border-1 px-1 text-center">Pieza</td>
                        <td class="border-1 px-1 text-center">1</td>
                        <td class="border-1 px-1 text-center">{{ $money($mezcla->infusor_precio ?? 0) }}</td>
                        <td class="border-1 border-r-0 px-1 text-center">{{ $money($mezcla->infusor_subtotal ?? 0) }}
                        </td>
                    </tr>
                @endif

                @if ((float) ($mezcla->mixing_service_total ?? 0) > 0)
                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                        <td class="border-1 px-1 text-center">Servicio de mezclado (IVA incluido)</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">Servicio</td>
                        <td class="border-1 px-1 text-center">1</td>
                        <td class="border-1 px-1 text-center">{{ $money($mezcla->mixing_service_total) }}</td>
                        <td class="border-1 border-r-0 px-1 text-center">{{ $money($mezcla->mixing_service_total) }}</td>
                    </tr>
                @endif
            @endforeach

            @if ((float) ($totalServicioMezclado ?? 0) > 0)
                <tr>
                    <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                    <td class="border-1 px-1 text-center">Servicio de Mezclado</td>
                    <td class="border-1 px-1 text-center">—</td>
                    <td class="border-1 px-1 text-center">—</td>
                    <td class="border-1 px-1 text-center">—</td>
                    <td class="border-1 px-1 text-center">—</td>
                    <td class="border-1 px-1 text-center">Servicio</td>
                    <td class="border-1 px-1 text-center">{{ $cantidadServiciosMezclado ?? 1 }}</td>
                    <td class="border-1 px-1 text-center">{{ $money($precioUnitarioServicioMezclado ?? 0) }}</td>
                    <td class="border-1 border-r-0 px-1 text-center">{{ $money($totalServicioMezclado ?? 0) }}</td>
                </tr>
            @endif
        </table>

        <table>
            <tr>
                <td class="text-right">Subtotal antes de IVA {{ $money($remisionTotals['subtotal_before_vat'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="text-right">IVA medicamentos seleccionados (16%) {{ $money($remisionTotals['medication_vat'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="text-right">IVA servicio de mezclado (16%) {{ $money($remisionTotals['service_vat'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="text-right">IVA insumos gravados (16%) {{ $money($remisionTotals['supplies_vat'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="text-right"><strong>Total IVA incluido {{ $money($totalRemision) }}</strong></td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 px-1" style="width: 10%">Observaciones:</td>
                <td class="border-1 px-1">{{ $observaciones }}</td>
            </tr>
        </table>

        <table class="mt-12">
            <tr>
                <td style="width: 35%"></td>
                <td style="width: 30%" class="border-b-1"></td>
                <td style="width: 35%"></td>
            </tr>
        </table>
        <table class="mt-2 mb-4">
            <tr>
                <td style="width: 35%"></td>
                <td style="width: 30%" class="text-center">Nombre completo/firma<br>Fecha de recibido</td>
                <td style="width: 35%"></td>
            </tr>
        </table>
    </div>
    @endunless

    {{-- =========================================================
        HOJA 2: DISTRIBUIDOR (solo si existe)
    ========================================================== --}}
    @if (!empty($distributor))
        @php $contador = 1; @endphp
        @unless ($subdistributorOnly ?? false)
            <div class="page-break"></div>
        @endunless

        <div class="contenedor border-1">
            <!-- Encabezado DISTRIBUIDOR -->
            <div class="introduccion">
                <table style="padding-top: 0.5rem">
                    <tr>
                        <td style="width: 20%">
                            @if ($distLogoSrc)
                                <img style="width: 10rem;" src="{{ $distLogoSrc }}" alt="Logo distribuidor">
                            @else
                                <span style="font-size:10px;">LOGO NO DISPONIBLE</span>
                            @endif
                        </td>

                        <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                            <strong>REMISIÓN <br> MEZCLAS ESTÉRILES ONCOLÓGICAS</strong>
                            <br>{{ $distNombre ?: 'Subdistribuidor' }}
                            @if ($distRfc)
                                <br><span style="font-size: 9px">RFC: {{ $distRfc }}</span>
                            @endif
                        </td>

                        <td style="width: 20%"></td>
                    </tr>
                </table>
            </div>

            <table>
                <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                    <td style="text-align: center;">ENTREGA DE LAS MEZCLAS ONCOLÓGICAS PREPARADAS EN CMP</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="px-1">
                        Fecha de envío:
                        <strong>{{ $fmtDateTime($fechaEmision) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="px-1">{{ $distContacto ? 'Contacto: ' . $distContacto : '' }}</td>
                    <td class="px-1 text-right">{{ $distDireccion ?: '—' }}</td>
                </tr>
            </table>

            @if ($distAdditionalInformation)
                <table>
                    <tr>
                        <td class="px-1">{!! nl2br(e($distAdditionalInformation)) !!}</td>
                    </tr>
                </table>
            @endif

            @if ($contractNumber)
                <table>
                    <tr>
                        <td class="px-1"><strong>Contrato:</strong> {{ $contractNumber }}</td>
                        <td class="px-1 text-right">{!! nl2br(e($contractInformation)) !!}</td>
                    </tr>
                </table>
            @endif

            <table>
                <tr>
                    <td class="px-1 text-center">DATOS DEL PACIENTE</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="border-1 border-l-0 px-1 bg-cbta text-center font-bold">Nombre completo</td>
                    <td class="border-1 px-1 bg-cbta text-center font-bold">Fecha de nacimiento</td>
                    <td class="border-1 px-1 bg-cbta text-center font-bold">Edad</td>
                    <td class="border-1 px-1 bg-cbta text-center font-bold">Genero</td>
                    <td class="border-1 border-r-0 px-1 bg-cbta text-center font-bold">Alergias</td>
                </tr>
                <tr>
                    <td class="border-x-1 border-l-0 px-1 text-center">{{ $pacienteNombre }}</td>
                    <td class="border-x-1 px-1 text-center">{{ $fechaNac }}</td>
                    <td class="border-x-1 px-1 text-center">{{ $edad }}</td>
                    <td class="border-x-1 px-1 text-center">{{ $sexo }}</td>
                    <td class="border-x-1 border-r-0 px-1 text-center">{{ $alergias }}</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="border-1 border-l-0 px-1 bg-cbta text-center font-bold">Diagnóstico</td>
                    <td class="border-1 px-1 bg-cbta text-center font-bold">Servicio</td>
                    <td class="border-1 px-1 bg-cbta text-center font-bold">No. de Expediente</td>
                    <td class="border-1 border-r-0 px-1 bg-cbta text-center font-bold">Médico tratante</td>
                </tr>
                <tr>
                    <td class="border-1 border-l-0 px-1 text-center">{{ $diagnostico }}</td>
                    <td class="border-1 px-1 text-center">{{ $servicio }}</td>
                    <td class="border-1 px-1 text-center">{{ $expediente }}</td>
                    <td class="border-1 border-r-0 px-1 text-center">{{ $medico }}</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="text-center">DATOS DE LAS MEZCLAS</td>
                    <td class="text-center">COSTO MEDICAMENTO</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="border-1 border-l-0 px-1 text-center bg-cbta font-bold">No</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Medicamento</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Dosis (mg)</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Diluyente</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Volumen mezcla</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Lote de la mezcla</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Unidad de cobro</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Cantidad</td>
                    <td class="border-1 px-1 text-center bg-cbta font-bold">Precio unitario</td>
                    <td class="border-1 border-r-0 px-1 text-center bg-cbta font-bold">Subtotal</td>
                </tr>

                @foreach ($mezclas as $mezcla)
                    @php
                        $loteMezcla = $mezcla->lote ?? '—';
                        $volumenMezcla = isset($mezcla->volumen_dilucion) ? $mezcla->volumen_dilucion . ' ml' : '—';
                    @endphp

                    @forelse ($mezcla->medicamentos as $med)
                        @php
                            $denom =
                                $med->denominacion_snapshot ??
                                null ??
                                (optional(optional($med->medicamentoOnco)->catalog)->denominacion ??
                                    ($med->nombre_medicamento ?? '—'));

                            $dosis = $med->dosis ?? 0;
                            $dosisFmt = is_numeric($dosis)
                                ? rtrim(rtrim(number_format($dosis, 2, '.', ''), '0'), '.')
                                : $dosis;

                            $diluyente = optional($med->diluyente)->denominacion_generica ?? '—';

                            $unidadCobro = $med->unidad_cobro ?? '—';
                            $cantidad = $med->cantidad_cobro ?? 0;
                            $precioUnit = $med->precio_unitario_calculado ?? 0;
                            $subtotal = $med->subtotal_calculado ?? 0;

                            $presentaciones = $med->presentacionesUsadas ?? collect();

                            $presentacionesTexto = $presentaciones
                                ->map(function ($pres) {
                                    $batch = $pres->batch;

                                    $nombrePres = trim($pres->presentacion_snapshot ?? '');
                                    if ($nombrePres === '') {
                                        $presBase = $pres->presentation;
                                        $nombrePres = trim($presBase->presentacion ?? '');
                                    }

                                    $linea1Partes = [];
                                    if ($pres->unidades_usadas) {
                                        $linea1Partes[] = $pres->unidades_usadas . ' pza(s)';
                                    }
                                    if ($nombrePres) {
                                        $linea1Partes[] = $nombrePres;
                                    }

                                    $linea1 = '• ' . implode(' - ', $linea1Partes);

                                    $lote = $pres->lote_usado ?? $batch?->lote;
                                    $cad = $pres->caducidad_usada ?? $batch?->caducidad;

                                    $linea2Partes = [];
                                    if ($lote) {
                                        $linea2Partes[] = 'Lote ' . $lote;
                                    }
                                    if ($cad) {
                                        try {
                                            $linea2Partes[] = 'Cad. ' . \Carbon\Carbon::parse($cad)->format('d/m/Y');
                                        } catch (\Exception $e) {
                                            $linea2Partes[] = 'Cad. ' . $cad;
                                        }
                                    }

                                    $linea2 = count($linea2Partes) ? implode(' · ', $linea2Partes) : '';

                                    return $linea2 ? $linea1 . '<br>&nbsp;&nbsp;' . $linea2 : $linea1;
                                })
                                ->implode('<br>');

                            if ($presentacionesTexto === '') {
                                $presentacionesTexto = 'Sin detalle de presentación.';
                            }
                        @endphp

                        <tr>
                            <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                            <td class="border-1 px-1 text-center">{{ $denom }}</td>
                            <td class="border-1 px-1 text-center">
                                {{ is_numeric($dosisFmt) ? $dosisFmt . ' mg' : $dosisFmt }}</td>
                            <td class="border-1 px-1 text-center">{{ $diluyente }}</td>
                            <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                            <td class="border-1 px-1 text-center">{{ $loteMezcla }}</td>
                            <td class="border-1 px-1 text-center">{{ ucfirst($unidadCobro) }}</td>
                            <td class="border-1 px-1 text-center">
                                @if ($unidadCobro === 'mg')
                                    {{ rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.') }}
                                @else
                                    {{ $cantidad }}
                                @endif
                            </td>
                            <td class="border-1 px-1 text-center">{{ $money($precioUnit) }}</td>
                            <td class="border-1 border-r-0 px-1 text-center">{{ $money($subtotal) }}</td>
                        </tr>

                        <tr class="fila-presentacion">
                            <td class="border-1 border-l-0 px-1"></td>
                            <td class="border-1 px-1 text-right font-bold">Presentaciones usadas:</td>
                            <td class="border-1 border-r-0 px-1 text-left bg-presentacion" colspan="9">
                                {!! $presentacionesTexto !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="border-1 border-l-0 px-1 text-center" colspan="11">
                                Sin medicamentos en esta mezcla.
                            </td>
                        </tr>
                    @endforelse

                    @if (($mezcla->infusor_aplica ?? false) && (float) ($mezcla->infusor_subtotal ?? 0) > 0)
                        <tr>
                            <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                            <td class="border-1 px-1 text-center">
                                {{ 'Infusor: ' . ($mezcla->infusor_nombre ?? '—') }}
                            </td>
                            <td class="border-1 px-1 text-center">—</td>
                            <td class="border-1 px-1 text-center">—</td>
                            <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                            <td class="border-1 px-1 text-center">{{ $loteMezcla }}</td>
                            <td class="border-1 px-1 text-center">Pieza</td>
                            <td class="border-1 px-1 text-center">1</td>
                            <td class="border-1 px-1 text-center">{{ $money($mezcla->infusor_precio ?? 0) }}</td>
                            <td class="border-1 border-r-0 px-1 text-center">
                                {{ $money($mezcla->infusor_subtotal ?? 0) }}</td>
                        </tr>
                    @endif

                    @if ((float) ($mezcla->mixing_service_total ?? 0) > 0)
                        <tr>
                            <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                            <td class="border-1 px-1 text-center">Servicio de mezclado (IVA incluido)</td>
                            <td class="border-1 px-1 text-center">—</td>
                            <td class="border-1 px-1 text-center">—</td>
                            <td class="border-1 px-1 text-center">{{ $volumenMezcla }}</td>
                            <td class="border-1 px-1 text-center">—</td>
                            <td class="border-1 px-1 text-center">Servicio</td>
                            <td class="border-1 px-1 text-center">1</td>
                            <td class="border-1 px-1 text-center">{{ $money($mezcla->mixing_service_total) }}</td>
                            <td class="border-1 border-r-0 px-1 text-center">{{ $money($mezcla->mixing_service_total) }}</td>
                        </tr>
                    @endif
                @endforeach

                @if ((float) ($totalServicioMezclado ?? 0) > 0)
                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                        <td class="border-1 px-1 text-center">Servicio de Mezclado</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">—</td>
                        <td class="border-1 px-1 text-center">Servicio</td>
                        <td class="border-1 px-1 text-center">{{ $cantidadServiciosMezclado ?? 1 }}</td>
                        <td class="border-1 px-1 text-center">{{ $money($precioUnitarioServicioMezclado ?? 0) }}</td>
                        <td class="border-1 border-r-0 px-1 text-center">{{ $money($totalServicioMezclado ?? 0) }}</td>
                    </tr>
                @endif
            </table>

            <table>
                <tr>
                    <td class="text-right">Subtotal antes de IVA {{ $money($remisionTotals['subtotal_before_vat'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="text-right">IVA medicamentos seleccionados (16%) {{ $money($remisionTotals['medication_vat'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="text-right">IVA servicio de mezclado (16%) {{ $money($remisionTotals['service_vat'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="text-right">IVA insumos gravados (16%) {{ $money($remisionTotals['supplies_vat'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Total IVA incluido {{ $money($totalRemision) }}</strong></td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="border-1 px-1" style="width: 10%">Observaciones:</td>
                    <td class="border-1 px-1">{{ $observaciones }}</td>
                </tr>
            </table>

            <table class="mt-12">
                <tr>
                    <td style="width: 35%"></td>
                    <td style="width: 30%" class="border-b-1"></td>
                    <td style="width: 35%"></td>
                </tr>
            </table>
            <table class="mt-2 mb-4">
                <tr>
                    <td style="width: 35%"></td>
                    <td style="width: 30%" class="text-center">Nombre completo/firma<br>Fecha de recibido</td>
                    <td style="width: 35%"></td>
                </tr>
            </table>
        </div>
    @endif

</body>


</html>
