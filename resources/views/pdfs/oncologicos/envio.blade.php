<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Envio</title>

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


@php
    use Carbon\Carbon;

    // ---- Datos base de solicitud_oncos ----
    $pacienteNombre = $solicitud->nombre_paciente ?? '—';
    $fechaNac = $solicitud->fecha_nacimiento ? Carbon::parse($solicitud->fecha_nacimiento) : null;

    $edad = '—';
    if ($fechaNac) {
        $ahora = Carbon::now();
        $años = $fechaNac->diffInYears($ahora);
        $meses = $fechaNac->diffInMonths($ahora);
        $dias = $fechaNac->diffInDays($ahora);

        if ($años > 0) {
            $edad = $años . ' años';
        } elseif ($meses > 0) {
            $edad = $meses . ' meses';
        } else {
            $edad = $dias . ' días';
        }
    }

    // Sexo en solicitud_oncos es enum('M','F')
    $sexo = match ($solicitud->sexo) {
        'M' => 'Masculino',
        'F' => 'Femenino',
        default => '—',
    };

    $alergias = $solicitud->alergias ?? '—';
    $diagnostico = $solicitud->diagnostico ?? '—';
    $servicio = $solicitud->servicio ?? '—';
    $expediente = $solicitud->registro_paciente ?? '—';
    $medico = $solicitud->nombre_medico ?? '—';
    $observaciones = $solicitud->observaciones ?? '—';

    // ✅ Domicilio cliente receptor (snapshot del hospital)
    // (fallback a user->hospital si son datos viejos)
    $domicilioHospital =
        optional($solicitud->hospital)->adress ?? (optional(optional($solicitud->user)->hospital)->adress ?? '—');

    // Contador filas de medicamentos
    $contador = 1;

    // ✅ Nombre medicamento inmutable (snapshot primero)
    $nombreMedicamentoDoc = function ($med) {
        $den = trim((string) ($med->denominacion_snapshot ?? ''));
        $mar = trim((string) ($med->marca_snapshot ?? ''));

        if ($den !== '') {
            return $mar !== '' ? "{$den} ({$mar})" : $den;
        }

        // fallback a relación viva (para registros viejos)
        $denLive = optional(optional($med->medicamentoOnco)->catalog)->denominacion;
        if (!empty($denLive)) {
            return $denLive;
        }

        // fallback a lo guardado
        if (!empty($med->nombre_medicamento)) {
            return $med->nombre_medicamento;
        }

        return '—';
    };
@endphp

<body>

    <div class="contenedor border-1">
        <!-- Encabezado -->
        <div class="introduccion">
            <table style="padding-top: 0.5rem">
                <tr>
                    <td style="width: 20%">
                        <img style="width: 10rem;" src="{{ asset('img/logo-cbta.jpg') }}" alt="">
                    </td>
                    <td style="width: 60%; margin: 0 auto; text-align: center; font-size: 13px">
                        <strong>ORDEN DE ENVÍO<br> MEZCLAS ESTÉRILES ONCOLÓGICAS</strong>
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
                <td class="px-1">Fecha de envío:
                    <strong>
                        {{ $fechaEnvio
                            ? Carbon::createFromFormat('d/m/Y H:i', $fechaEnvio)->format('d/m/Y H:i')
                            : now()->format('d/m/Y H:i') }}
                    </strong>
                </td>
                <td class="px-1 text-right">DOMICILIO INSTITUCION RECEPTORA:
                    <strong>{{ $domicilioHospital }}</strong>
                </td>
            </tr>
        </table>

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
                <td class="border-1 px-1 bg-cbta text-center font-bold">Género</td>
                <td class="border-1 border-r-0 px-1 bg-cbta text-center font-bold">Alergias</td>
            </tr>
            <tr>
                <td class="border-x-1 border-l-0 px-1 text-center">{{ $pacienteNombre }}</td>
                <td class="border-x-1 px-1 text-center">{{ $fechaNac ? $fechaNac->format('d/m/Y') : '—' }}</td>
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
                <td class="border-b-1 px-1 bg-cbta text-center font-bold">Comentarios:</td>
            </tr>
            <tr>
                <td class="border-b-1 border-t-0 px-1">{{ $observaciones }}</td>
            </tr>
        </table>

        <!-- Datos de las mezclas -->
        <table>
            <tr>
                <td class="px-1 bg-cbta text-center font-bold">DATOS DE LAS MEZCLAS</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="border-1 border-l-0 px-1 text-center">No</td>
                <td class="border-1 px-1 text-center">Medicamento</td>
                <td class="border-1 px-1 text-center">Dosis</td>
                <td class="border-1 px-1 text-center">Diluyente</td>
                <td class="border-1 px-1 text-center">Volumen</td>
                <td class="border-1 px-1 text-center">Lote de la mezcla</td>
                <td class="border-1 px-1 text-center">No. Remisión</td>
                <td class="border-1 px-1 text-center">Fecha/Hora de <br> preparación</td>
                <td class="border-1 border-r-0 px-1 text-center">Fecha/Hora de <br> límite de uso</td>
            </tr>

            @php $contador = $contador ?? 1; @endphp

            @foreach ($mezclas as $mezcla)
                @php
                    $prep = $mezcla->created_at ? Carbon::parse($mezcla->created_at) : null;
                    $limite = $prep ? $prep->copy()->addHours(48) : null;

                    $prepFmt = $prep ? $prep->format('d/m/Y H:i') : '—';
                    $limiteFmt = $limite ? $limite->format('d/m/Y H:i') : '—';

                    $loteMezcla = $mezcla->lote ?? '—';
                    $remision = $mezcla->remision ?? '—';

                    $volumenDilucion = $mezcla->volumen_dilucion ?? null;
                    $volumenFmt = is_numeric($volumenDilucion)
                        ? rtrim(rtrim(number_format($volumenDilucion, 2, '.', ''), '0'), '.') . ' mL'
                        : ($volumenDilucion ?:
                        '—');
                @endphp

                @forelse($mezcla->medicamentos as $med)
                    @php
                        // ✅ medicamento (snapshot -> fallback)
                        $denom = $nombreMedicamentoDoc($med);

                        $dosis = isset($med->dosis)
                            ? (is_numeric($med->dosis)
                                ? rtrim(rtrim(number_format($med->dosis, 2, '.', ''), '0'), '.')
                                : $med->dosis)
                            : '—';

                        // diluyente (aún vivo; si luego haces snapshot también aquí lo cambiamos)
                        $diluyente = optional($med->diluyente)->denominacion_generica ?? '—';
                    @endphp

                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center">{{ $contador++ }}</td>
                        <td class="border-1 px-1 text-center">{{ $denom }}</td>
                        <td class="border-1 px-1 text-center">{{ $dosis }}</td>
                        <td class="border-1 px-1 text-center">{{ $diluyente }}</td>
                        <td class="border-1 px-1 text-center">{{ $volumenFmt }}</td>
                        <td class="border-1 px-1 text-center">{{ $loteMezcla }}</td>
                        <td class="border-1 px-1 text-center">{{ $remision }}</td>
                        <td class="border-1 px-1 text-center">{{ $prepFmt }}</td>
                        <td class="border-1 border-r-0 px-1 text-center">{{ $limiteFmt }}</td>
                    </tr>

                    <tr>
                        <td class="border-1 border-l-0 px-1" colspan="5">
                            <strong>Leyenda de protección:</strong>
                            {{ $med->legend_proteccion_doc !== '' ? $med->legend_proteccion_doc : '—' }}
                        </td>
                        <td class="border-1 border-r-0 px-1" colspan="4">&nbsp;</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-1 border-l-0 px-1 text-center" colspan="9">
                            Sin medicamentos en esta mezcla.
                        </td>
                    </tr>
                @endforelse
            @endforeach
        </table>

        <table>
            <tr>
                <td class="text-right border-b-1">
                    Cantidad total de mezclas: <strong>{{ $mezclas->count() }}</strong>
                </td>
            </tr>
        </table>

        <table class="mt-4">
            <tr>
                <td class="text-center">Recepcion Institucion</td>
            </tr>
        </table>

        <table class="mb-4">
            <tr>
                <td style="width: 30%"></td>
                <td style="width: 20%" class="text-right">Fecha:</td>
                <td style="width: 20%" class="border-b-1"></td>
                <td style="width: 30%"></td>
            </tr>
            <tr>
                <td style="width: 30%"></td>
                <td style="width: 20%" class="text-right">Hora de recibido:</td>
                <td style="width: 20%" class="border-b-1"></td>
                <td style="width: 30%"></td>
            </tr>
            <tr>
                <td style="width: 30%"></td>
                <td style="width: 20%" class="text-right">Temperatura:</td>
                <td style="width: 20%" class="border-b-1"></td>
                <td style="width: 30%"></td>
            </tr>
        </table>

    </div>
</body>


</html>

