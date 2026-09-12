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

        .bg-cbta {
            background-color: #1F4E78;
            color: white;
        }

        .bg-black {
            background-color: black;
            color: white;
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
            font-size: 11px
        }

        .border-1 {
            border: 1px solid black;
        }

        .border-x-1 {
            border-left: 1px solid black;
            border-right: 1px solid black;
        }

        .border-y-1 {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }

        .border-l-0 {
            border-left: none;
        }

        .border-r-0 {
            border-right: none;
        }

        .mx-1 {
            margin-left: 0.25rem;
            margin-right: 0.5rem;
        }

        .mx-2 {
            margin-left: 0.5rem;
            margin-right: 1rem;
        }

        .header-meta {
            width: 98%;
            margin-left: 0.25rem;
            margin-right: 0.25rem;
        }

        .header-meta .right-safe {
            padding-right: 6px;
            text-align: right;
            white-space: nowrap;
        }

        .mt-8 {
            margin-top: 2rem;
        }

        .mt-12 {
            margin-top: 3rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .px-1 {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }

        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .pt-2 {
            padding-top: 0.5rem;
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

        .font-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center
        }
    </style>


<body>
    @php
        use Carbon\Carbon;

        // Helpers de formato
        $fmtDate = function ($v) {
            if (!$v) {
                return '—';
            }
            try {
                return Carbon::parse($v)->format('d/m/Y');
            } catch (\Exception $e) {
                return '—';
            }
        };
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
        $fmtDateTime = function ($date, $time = null) {
            if (!$date && !$time) {
                return null;
            }
            try {
                if ($date && $time) {
                    return Carbon::parse($date . ' ' . $time)->format('d/m/Y H:i');
                }
                if ($date) {
                    return Carbon::parse($date)->format('d/m/Y H:i');
                }
                return Carbon::parse($time)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return null;
            }
        };

        // Helpers snapshot-friendly
        $fmtNum = function ($n, $dec = 2, $suffix = '') {
            if (!is_numeric($n)) {
                return '—';
            }
            $v = rtrim(rtrim(number_format((float) $n, $dec, '.', ''), '0'), '.');
            return $suffix ? $v . $suffix : $v;
        };

        $horaRecepcion = $mezcla->solicitud?->created_at
            ? Carbon::parse($mezcla->solicitud->created_at)->format('H:i')
            : null;
        $fechaHoraValidacionTraza = $fmtDateTime(
            optional($mezcla->inspeccion)->fecha_validacion,
            optional($mezcla->inspeccion)->hora_validacion
        );
        $fechaHoraPreparacionTraza = $fmtDateTime(
            optional($mezcla->inspeccion)->fecha_preparacion,
            optional($mezcla->inspeccion)->hora_preparacion
        );
        $fechaHoraInspeccionTraza = filled(optional($mezcla->inspeccion)->reviso_nombre)
            ? $fmtDateTime(optional($mezcla->inspeccion)->fecha_inspeccion, optional($mezcla->inspeccion)->hora_inspeccion)
            : null;
        $fechaHoraAprobacionTraza = filled(optional($mezcla->inspeccion)->aprobo_nombre)
            ? $fmtDateTime(optional($mezcla->inspeccion)->fecha_aprobacion, optional($mezcla->inspeccion)->hora_aprobacion)
            : null;

        $detalleAgregarPdf = collect($medicamentos ?? [])
            ->map(function ($m) use ($fmtNum) {
                $nombre = $m->nombre_para_texto ?? ($m->denominacion ?? 'Medicamento');
                $volumenes = collect($m->volumenes_por_frasco ?? [])->filter(fn($v) => (float) $v > 0);

                if ($volumenes->isNotEmpty()) {
                    $partes = $volumenes
                        ->map(fn($v) => round((float) $v, 4))
                        ->countBy()
                        ->map(function ($conteo, $volumen) use ($fmtNum) {
                            return $fmtNum(((float) $volumen) * $conteo, 2, ' mL');
                        })
                        ->values()
                        ->implode(' + ');

                    return trim($partes) !== '' ? ($partes . ' de ' . $nombre) : null;
                }

                $volumenTotal = (float) ($m->volumen_orden_preparacion ?? 0);

                if ($volumenTotal <= 0) {
                    return null;
                }

                return $fmtNum($volumenTotal, 2, ' mL') . ' de ' . $nombre;
            })
            ->filter()
            ->values();

        $lineasAgregarPdf = collect();
        if ($detalleAgregarPdf->isNotEmpty()) {
            if (!empty($ocultarExtraer)) {
                $lineasAgregarPdf->push(
                    $detalleAgregarPdf->implode(' + ') .
                        ' a ' . ($volumen_diluyente_restante ?? '—') . ' mL de ' . ($diluyente_base ?? '—') .
                        ' y transferir al infusor.'
                );
            } else {
                $lineasAgregarPdf->push(
                    $detalleAgregarPdf->implode(' + ') .
                        ' a ' . ($volumen_diluyente_restante ?? '—') . ' mL de ' . ($diluyente_base ?? '—')
                );
            }
        }
    @endphp



    <div class="contenedor border-1">
        <!-- Contenedor principal con borde negro -->
        <div class="introduccion">
            <table class="mt-2">
                <tr>
                    <td style="width: 20%">
                        <img style="width: 10rem" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                    </td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>CENTRAL DE MEZCLAS ESTÉRILES PRODIFEM</strong>
                    </td>
                    <td style="width: 20%"></td>
                </tr>
            </table>
        </div>

        <table>
            <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                <td style="text-align: center;">ORDEN DE PREPARACIÓN <br> MEZCLAS ESTÉRILES ONCOLÓGICAS</td>
            </tr>
        </table>

        <table class="header-meta">
            <tr>
                <td></td>
                <td class="right-safe">
                    <strong>Fecha de elaboración: {{ $fmtDate($fecha_preparacion ?? null) }}</strong>
                </td>
            </tr>
        </table>

        <table class="header-meta">
            <tr>
                <td style="width: 50%">
                    <strong>No. de Lote de la mezcla:</strong>
                    {{ $mezcla->lote ?? '—' }}
                </td>
                <td style="width: 50%" class="right-safe">
                    <strong>Remisión:</strong>
                    {{ $mezcla->remision ?? '—' }}
                </td>
            </tr>
        </table>

        <table class="mx-1 mt-2">
            <tr>
                <td><strong>DATOS DEL PACIENTE</strong></td>
            </tr>
        </table>

        <div class="border-1 mx-1 px-1 py-1">
            <table>
                <tr>
                    <td style="width: 5%">Nombre: </td>
                    <td style="width: 40%" class="border-b-1">{{ $mezcla->solicitud['nombre_paciente'] ?? '—' }}</td>
                    <td style="width: 11.5%">Médico tratante:</td>
                    <td style="width: 43%" class="border-b-1">{{ $mezcla->solicitud['nombre_medico'] ?? '—' }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width: 5%">Registro:</td>
                    <td style="width: 30%" class="border-b-1">{{ $mezcla->solicitud['registro_paciente'] ?? '—' }}</td>
                    <td style="width: 5%">Diagnóstico:</td>
                    <td class="border-b-1">{{ $mezcla->solicitud['diagnostico'] ?? '—' }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width: 15.4%">Fecha de nacimiento:</td>
                    <td class="border-b-1">{{ $fmtDate($mezcla->solicitud['fecha_nacimiento'] ?? null) }}</td>
                    <td style="width: 5%">Género:</td>
                    <td class="border-b-1">{{ $mezcla->solicitud['sexo'] ?? '—' }}</td>
                    <td style="width: 5%">Alergias:</td>
                    <td class="border-b-1">{{ $mezcla->solicitud['alergias'] ?? '—' }}</td>
                    <td style="width: 4%">Peso:</td>
                    <td class="border-b-1">
                        {{ isset($mezcla->solicitud['peso']) ? $mezcla->solicitud['peso'] . ' Kg' : '—' }}
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width: 15%">Hospital que solicita:</td>
                    <td class="border-b-1">{{ $hospital ?? '—' }}</td>
                    <td style="width: 10%">Dosis/Volumen:</td>
                    <td class="border-b-1">
                        {{ is_numeric($mezcla->volumen_dilucion ?? null) ? $fmtNum($mezcla->volumen_dilucion, 2, ' mL') : '—' }}
                    </td>
                </tr>
            </table>
        </div>

        <p class="mx-1"><strong>MEZCLA ESTÉRIL ONCOLÓGICA</strong></p>

        <div class="border-1 mx-1 px-1 py-1">
            <table>
                <tr>
                    <td colspan="1">
                        <strong>Hora de recepci&oacute;n:</strong>
                        {{ $horaRecepcion ?? '—' }}
                    </td>
                    <td colspan="2" class="border-b-1"></td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="text-center border-x-1 border-t-1 bg-cbta"><strong>Medicamentos</strong></td>
                </tr>
            </table>

            {{-- TABLA 1 agrupada por medicamento --}}
            <table>
                <tr>
                    <td class="border-1 text-center" style="width: 1%"><strong>#</strong></td>
                    <td class="border-1 text-center" style="width: 7%"><strong>No. de lote</strong></td>
                    <td class="border-1 text-center" style="width: 8%"><strong>Caducidad</strong></td>
                    <td class="border-1 text-center" style="width: 6%"><strong>Piezas</strong></td>
                    <td class="border-1 text-center" style="width: 22%"><strong>Medicamento</strong></td>
                    <td class="border-1 text-center" style="width: 14%"><strong>Presentación</strong></td>
                    <td class="border-1 text-center" style="width: 8%"><strong>Dosis (mg)</strong></td>
                    <td class="border-1 text-center" style="width: 16%"><strong>Volumen</strong></td>
                </tr>

                @php $contador = 1; @endphp

                @foreach ($medicamentosAgrupados as $grupo)
                    @php
                        $primerMed = $grupo->first();
                        $rowspan = $grupo->count();

                        $dosis = $primerMed->dosis ?? '—';
                        // Una misma dosis puede surtirse con varias presentaciones o lotes.
                        // El volumen mostrado debe reflejar la suma total del medicamento.
                        $vop = $grupo->sum(fn ($med) => (float) ($med->volumen_orden_preparacion ?? 0));
                    @endphp

                    @foreach ($grupo as $index => $med)
                        @php
                            $nombreMed = $med->nombre_para_texto ?? ($med->denominacion ?? '—');
                            $presentacion = $med->presentacion ?? '—';
                        @endphp

                        <tr>
                            <td class="border-1 border-t-0 text-center">
                                <strong>{{ $contador }}</strong>
                            </td>

                            <td class="border-1 border-t-0 text-center">
                                {{ $med->lote ?? '—' }}
                            </td>

                            <td class="border-1 border-t-0 text-center">
                                {{ $fmtDate($med->caducidad ?? null) }}
                            </td>
                            <td class="border-1 border-t-0 text-center">
                                {{ $med->unidades_usadas ?? '—' }}
                            </td>

                            <td class="border-1 border-t-0 text-center">
                                {{ $nombreMed }}
                            </td>

                            <td class="border-1 border-t-0 text-center">
                                {{ $presentacion }}
                            </td>

                            @if ($index === 0)
                                <td class="border-1 border-t-0 text-center" rowspan="{{ $rowspan }}">
                                    {{ is_numeric($dosis) ? $fmtNum($dosis, 2) : ($dosis ?: '—') }}
                                </td>

                                <td class="border-1 border-t-0 text-center" rowspan="{{ $rowspan }}">
                                    {{ is_numeric($vop) ? $fmtNum($vop, 2, ' mL') : '—' }}
                                </td>
                            @endif
                        </tr>

                        @php $contador++; @endphp
                    @endforeach
                @endforeach
            </table>

            <table>
                <tr>
                    <td class="text-right border-x-1 border-b-1">
                        <strong>Contenedor y volumen final: {{ $mezcla->volumen_dilucion ?? '—' }} ML</strong>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="text-right border-x-1 border-b-1">
                        <strong>
                            Concentración final de la mezcla:
                            {{ $concentracion_final !== '—' ? $concentracion_final . ' mg/mL' : '—' }}
                        </strong>
                    </td>
                </tr>
            </table>

            <table class="mt-2">
                <tr>
                    <td class="border-1 text-center bg-cbta"><strong>Solución y volumen</strong></td>
                </tr>
                <tr>
                    <td class="border-1 text-center">
                        <em>Verificar que los lotes y caducidades coincidan con la solución entregada</em>
                    </td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>#</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>No. de lote</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>Caducidad</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>Solución</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>Presentación</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>Volumen total</strong></td>
                        <td class="border-x-1 border-b-1 text-center bg-black"><strong>Volumen del diluyente</strong>
                        </td>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td class="text-center border-b-1 border-l-1">1</td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ $mezcla->diluentPresentation->lote ?? '—' }}
                        </td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ $fmtDate($mezcla->diluentPresentation->caducidad ?? null) }}
                        </td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ $mezcla->diluentPresentation->diluent->denominacion_generica ?? ($diluyente_base ?? '—') }}
                        </td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ $mezcla->diluentPresentation->presentacion ?? '—' }}
                        </td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ is_numeric($mezcla->volumen_dilucion ?? null) ? $fmtNum($mezcla->volumen_dilucion, 2, ' mL') : '—' }}
                        </td>

                        <td class="text-center border-b-1 border-r-1">
                            {{ isset($volumen_diluyente_restante) && is_numeric($volumen_diluyente_restante)
                                ? $fmtNum($volumen_diluyente_restante, 2, ' mL')
                                : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="mt-2">
                <tr>
                    <td class="border-x-1 border-t-1 bg-cbta" colspan="8" style="text-align: center">
                        <strong>Equipo de infusión/Infusor</strong>
                    </td>
                </tr>

                <tr>
                    <td rowspan="2" class="border-1 text-center"><strong>1</strong></td>
                    <td colspan="1" class="border-1 text-center"><strong>No. de lote</strong></td>
                    <td colspan="1" class="border-1 text-center"><strong>Caducidad</strong></td>
                    <td colspan="2" class="border-1 text-center"><strong>Nombre comercial</strong></td>
                    <td colspan="3" class="border-1 text-center"><strong>Nombre genérico</strong></td>
                </tr>

                <tr>
                    <td colspan="1" class="border-1 text-center">
                        {{ $equipoInfusion->lote ?? '—' }}
                    </td>
                    <td colspan="1" class="border-1 text-center">
                        {{ $fmtDate($equipoInfusion->caducidad ?? null) }}
                    </td>
                    <td colspan="2" class="border-1 text-center">
                        {{ $equipoInfusion->nombre_comercial ?? '—' }}
                    </td>
                    <td colspan="3" class="border-1 text-center">
                        {{ $equipoInfusion->nombre_generico ?? '—' }}
                    </td>
                </tr>

                @if (($equipoInfusion->tipo ?? null) === 'set')
                    <tr>
                        <td colspan="8" class="border-x-1 border-b-1 text-center" style="font-size: 10px;">
                            <em>Nota: Para Set de infusión no se registra lote/caducidad en el sistema actualmente.</em>
                        </td>
                    </tr>
                @endif
            </table>

            <p>
                <strong>Concentración calculada:</strong>
                {{ $fmtNum($dosis_total_mg ?? null, 2) }} mg /
                {{ $fmtNum($volumen_final_ml ?? null, 2) }} mL =
                {{ $concentracion_final !== '—' ? $concentracion_final . ' mg/mL' : '—' }}
            </p>

            <p><strong>Forma de preparación:</strong></p>

            <p>
                <strong>
                    Forma de reconstitución:
                </strong>
            </p>
            @foreach ($medicamentos as $m)
                @if (!empty($m->forma_reconstitucion))
                    <p>
                        <strong>{{ $m->nombre_para_texto ?? $m->denominacion }}:</strong>
                        {{ $m->forma_reconstitucion }}
                    </p>
                @endif
            @endforeach
            @if (empty($ocultarExtraer) || !$ocultarExtraer)
                <p>
                    <strong>Extraer:</strong> Se retiran {{ $extraer_ml }} mL de la soluci&oacute;n
                </p>
            @endif

            @foreach ($lineasAgregarPdf as $linea)
                <p><strong>Agregar: </strong>{{ $linea }}</p>
            @endforeach

            <p>
                <strong>Leyenda de protección:</strong> {{ $legend_proteccion }}
            </p>
        </div>

        <table class="mx-2">
            <tr>
                <td>Recepci&oacute;n y validaci&oacute;n: {{ $valido_nombre ?? '—' }}{{ $fechaHoraValidacionTraza ? ' - ' . $fechaHoraValidacionTraza : '' }}</td>
            </tr>
            <tr>
                <td>Preparaci&oacute;n: {{ $preparo_nombre ?? '—' }}{{ $fechaHoraPreparacionTraza ? ' - ' . $fechaHoraPreparacionTraza : '' }}</td>
            </tr>
            <tr>
                <td>Inspecci&oacute;n: {{ $reviso_nombre ?? '—' }}{{ $fechaHoraInspeccionTraza ? ' - ' . $fechaHoraInspeccionTraza : '' }}</td>
            </tr>
            <tr>
                <td>Aprobaci&oacute;n: {{ $aprobo_nombre ?? '—' }}{{ $fechaHoraAprobacionTraza ? ' - ' . $fechaHoraAprobacionTraza : '' }}</td>
            </tr>
        </table>
    </div>
</body>


</html>
