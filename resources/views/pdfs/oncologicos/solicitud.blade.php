<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Solicitud Completa</title>

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

        .font-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center
        }
    </style>

</head>

<body>
    @php
        use Carbon\Carbon;

        // Helpers de formato seguros
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
        $fmtDateTime = function ($v) {
            if (!$v) {
                return '—';
            }
            try {
                return Carbon::parse($v)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return '—';
            }
        };
        $safe = function ($v, $fallback = '—') {
            return isset($v) && $v !== '' ? $v : $fallback;
        };

        // ✅ Nombre del medicamento "inmutable" (snapshot primero, fallback después)
        $nombreMedicamentoDoc = function ($med) {
            // 1) snapshot (nuevo)
            $den = trim((string) ($med->denominacion_snapshot ?? ''));
            $mar = trim((string) ($med->marca_snapshot ?? ''));

            if ($den !== '') {
                return $mar !== '' ? "{$den} ({$mar})" : $den;
            }

            // 2) fallback a relación viva (para datos viejos)
            $denLive = optional(optional($med->medicamentoOnco)->catalog)->denominacion;
            if (!empty($denLive)) {
                return $denLive;
            }

            // 3) fallback a lo que se guardó en nombre_medicamento
            if (!empty($med->nombre_medicamento)) {
                return $med->nombre_medicamento;
            }

            return '—';
        };
    @endphp

    <div class="contenedor border-1">
        <!-- Contenedor principal con borde negro -->
        <div class="introduccion">
            <table style="padding-top: 0.5rem">
                <tr>
                    <td style="width: 20%">
                        <img style="width: 10rem;" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
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
                <td style="text-align: center;">SOLICITUD <br> MEZCLAS ESTÉRILES ONCOLÓGICAS</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-r-1 px-1">Paciente Nombre(s): {{ $safe($solicitud->nombre_paciente) }}</td>
                <td class="border-x-1 px-1">Servicio: {{ $safe($solicitud->servicio) }}</td>
                <td class="border-l-1 px-1">Registro: {{ $safe($solicitud->registro_paciente) }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 border-l-0 px-1">Sexo: {{ $safe($solicitud->sexo) }}</td>
                <td class="border-1 border-l-0 px-1">Alergias: {{ $safe($solicitud->alergias) }}</td>
                <td class="border-1 px-1">
                    Fecha de Nacimiento: {{ $fmtDate($solicitud->fecha_nacimiento ?? null) }}
                </td>
                <td class="border-1 px-1">Peso: {{ $safe($solicitud->peso) }}</td>
                <td class="border-1 px-1">Piso: {{ $safe($solicitud->piso) }}</td>
                <td class="border-1 border-r-0 px-1">Cama: {{ $safe($solicitud->cama) }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-x-1 border-l-0 px-1">Diagnóstico: {{ $safe($solicitud->diagnostico) }}</td>
                <td class="border-x-1 border-r-0 px-1">Nombre del Médico: {{ $safe($solicitud->nombre_medico) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="border-1 border-x-0 px-1">Cédula del Médico: {{ $safe($solicitud->cedula_medico) }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-b-1 px-1">Observaciones: {!! $safe($solicitud->observaciones, '&nbsp;') !!}</td>
            </tr>
        </table>

        {{-- Mezclas --}}
        @foreach ($solicitud->mezclas as $index => $mezcla)
            <div class="mt-2">
                <table>
                    <tr>
                        <td class="border-t-1 border-b-1 px-1 text-center"
                            style="background: black; color: white; font-weight: bold; font-size: 14px">
                            Mezcla #{{ $index + 1 }} · Fecha de entrega:
                            {{ $fmtDate($mezcla->fecha_entrega ?? $solicitud->fecha_entrega ?? null) }}
                        </td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td class="border-l-0 px-1 font-bold">Medicamento</td>
                        <td class="border-x-1 px-1 font-bold">Dosis</td>
                        <td class="border-r-1 px-1 font-bold">Diluyente</td>
                        <td class="px-1 font-bold">Vía de administración</td>
                    </tr>

                    @foreach ($mezcla->medicamentos as $med)
                        <tr>
                            <td class="border-t-1 border-r-1 px-1">
                                {{ $nombreMedicamentoDoc($med) }}
                            </td>
                            <td class="border-t-1 border-r-1 px-1">
                                {{ isset($med->dosis) ? rtrim(rtrim(number_format($med->dosis, 2, '.', ''), '0'), '.') : '—' }}
                            </td>
                            <td class="border-t-1 border-r-1 px-1">
                                {{ $med->diluyente->denominacion_generica ?? '—' }}
                            </td>
                            <td class="border-t-1 px-1">
                                {{ $med->viaAdministracion->name ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </table>

                <table>
                    <tr>
                        <td class="border-r-1 border-t-1 px-1">
                            Volumen total de dilución (mL)*: {{ $safe($mezcla->volumen_dilucion) }}
                        </td>
                        <td class="border-t-1 px-1">
                            Tiempo de infusión (min)*: {{ $safe($mezcla->tiempo_infusion) }}
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
</body>



</html>
