<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><title>Remisión oncológica</title>
    <style>
        @page { margin: 3mm; } * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; } td, th { padding: 4px 6px; vertical-align: middle; }
        .document { border: 1px solid #1f5b88; }
        .header td { height: 19mm; padding: 2px 5px; } .logo { width: 38mm; max-height: 16mm; object-fit: contain; }
        .title { color: #0d2c55; font-size: 16px; font-weight: bold; line-height: 1; }
        .blue-band, .section-title { padding: 3px; background: #07598c; color: white; text-align: center; font-size: 12px; font-weight: bold; }
        .meta td { padding: 2px 6px; font-size: 10px; }
        .grid th { border: 1px solid #356b91; background: #dceeff; text-align: center; font-size: 10px; }
        .grid td { border: 1px solid #356b91; text-align: center; }
        .detail th { background: #07598c; color: white; } .detail td, .detail th { border: 1px solid #356b91; text-align: center; }
        .mode-row { border: 1px solid #356b91; padding: 2px 5px; text-align: center; font-weight: bold; }
        .detail-note { padding: 5px 8px; line-height: 1.25; }
        .total { background: #dceeff; text-align: center; font-size: 13px; font-weight: bold; }
        .footer-row td { border: 1px solid #356b91; padding: 4px 8px; }
        .observations td { height: 16mm; vertical-align: top; padding-top: 5px; }
        .signature { margin: 42px auto 2px; width: 45%; border-top: 1px solid #172033; text-align: center; padding-top: 5px; }
        .page-break { page-break-after: always; } .text-left { text-align: left !important; } .text-right { text-align: right !important; }
        .muted { color: #5d6878; font-size: 9px; }
    </style>
</head>
<body>
@php
    $fmtDate = function ($value) { if (!$value) return '—'; try { return \Carbon\Carbon::parse($value)->format('d/m/Y'); } catch (\Throwable $e) { return '—'; } };
    $fmtDateTime = function ($value) { if (!$value) return '—'; try { return \Carbon\Carbon::parse($value)->format('d/m/Y H:i'); } catch (\Throwable $e) { return '—'; } };
    $number = fn ($value, $decimals = 2) => rtrim(rtrim(number_format((float) $value, $decimals, '.', ','), '0'), '.');
    $money = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
    $patientName = $solicitud->nombre_paciente ?: '—';
    $birthDate = $fmtDate($solicitud->fecha_nacimiento ?? null);
    $age = $solicitud->edad ?? (($solicitud->fecha_nacimiento ?? null) ? \Carbon\Carbon::parse($solicitud->fecha_nacimiento)->age : '—');
    $gender = ($solicitud->sexo ?? null) === 'M' ? 'Masculino' : (($solicitud->sexo ?? null) === 'F' ? 'Femenino' : '—');
    $allergies = $solicitud->alergias ?: '—'; $diagnosis = $solicitud->diagnostico ?: '—';
    $service = $solicitud->servicio ?: '—'; $record = $solicitud->registro_paciente ?: 'S/D';
    $doctor = $solicitud->nombre_medico ?: '—'; $observations = $solicitud->observaciones ?: '—'; $mix = $mezclas->first();
    $units = $mezclas->flatMap(fn ($item) => $item->medicamentos->pluck('unidad_cobro'))->map(fn ($unit) => strtolower((string) $unit));
    $hasBottles = $units->contains('frasco');
    $hasMilligrams = $units->contains('mg');
    $hasLegacyMilliliters = $units->contains('ml');
    $remissionMode = $hasBottles && ($hasMilligrams || $hasLegacyMilliliters)
        ? 'mixed'
        : ($hasBottles ? 'frasco' : ($hasMilligrams ? 'mg' : 'ml'));
    $showPresentationColumn = in_array($remissionMode, ['frasco', 'mixed'], true);
    $prodifemLogoFile = public_path('img/logo-cbta.jpg');
    $prodifemLogo = file_exists($prodifemLogoFile) ? 'file://' . $prodifemLogoFile : null;
    $distributorName = $distributor->nombre ?? $distributor->name ?? 'Distribuidor';
    $distributorInformation = $distributor->informacion_adicional ?? $distributor->direccion ?? $distributor->address ?? null;
@endphp
@include('pdfs.oncologicos.partials.remision-page', [
    'issuerName' => !empty($distributor) ? $distributorName : 'CMP Prodifem',
    'issuerLogo' => $prodifemLogo,
    'issuerInformation' => !empty($distributor) ? $distributorInformation : null,
])
</body></html>
