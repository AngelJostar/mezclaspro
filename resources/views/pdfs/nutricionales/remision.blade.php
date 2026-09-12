<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><title>Remisión nutricional</title>
    <style>
        @page { margin: 3mm; } * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; } td, th { padding: 4px 6px; vertical-align: middle; }
        .document { border: 1px solid #1f5b88; }
        .header td { height: 15mm; padding: 1px 5px; } .logo { width: 34mm; max-height: 13mm; object-fit: contain; }
        .title { color: #0d2c55; font-size: 15px; font-weight: bold; line-height: .95; }
        .blue-band, .section-title { padding: 3px; background: #07598c; color: white; text-align: center; font-size: 12px; font-weight: bold; }
        .meta td { padding: 2px 6px; font-size: 10px; }
        .grid th { border: 1px solid #356b91; background: #dceeff; text-align: center; font-size: 10px; }
        .grid td { border: 1px solid #356b91; text-align: center; }
        .detail th { background: #07598c; color: white; } .detail td, .detail th { border: 1px solid #356b91; text-align: center; }
        .mode-row { border: 1px solid #356b91; padding: 2px 5px; text-align: center; font-weight: bold; }
        .detail-note { padding: 5px 8px; line-height: 1.25; } .total { background: #dceeff; font-size: 13px; font-weight: bold; }
        .footer-row td { border: 1px solid #356b91; padding: 4px 8px; }
        .receipt { margin: 4px auto 0; width: 76%; line-height: 1.15; }
        .important { margin: 2px 8px; font-size: 7px; line-height: 1.05; }
        .page-break { page-break-after: always; } .text-left { text-align: left !important; } .text-right { text-align: right !important; }
        .muted { color: #5d6878; font-size: 9px; }
    </style>
</head>
<body>
@php
    $detail = $solicitud_detalles->solicitud_detail; $patient = $solicitud_detalles->solicitud_patient;
    $fmtDate = function ($value) { if (!$value) return '—'; try { return \Carbon\Carbon::parse($value)->format('d/m/Y'); } catch (\Throwable $e) { return '—'; } };
    $fmtDateTime = function ($value) { if (!$value) return '—'; try { return \Carbon\Carbon::parse($value)->format('d/m/Y H:i'); } catch (\Throwable $e) { return '—'; } };
    $number = fn ($value, $decimals = 3) => rtrim(rtrim(number_format((float) $value, $decimals, '.', ','), '0'), '.');
    $money = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
    $adjustUnit = function ($unit) use ($detail) { if (($detail?->npt ?? null) === 'ADULT') return ['g/Kg' => 'g/día', 'mEq/Kg' => 'mEq/día'][$unit] ?? $unit; return $unit; };
    $genericName = fn ($item) => $item?->presentation?->catalog?->denominacion_generica ?? $item?->input?->nutritionMedicineCatalog?->denominacion_generica ?? $item?->input?->description ?? 'Medicamento no disponible';
    $commercialName = fn ($item) => $item?->presentation?->denominacion_comercial ?? $item?->input?->nutritionMedicineCatalog?->presentations?->first()?->denominacion_comercial;
    $listItem = function ($item) { return $item?->presentation?->listItems?->first() ?? $item?->input?->nutritionMedicineCatalog?->presentations?->flatMap(fn ($presentation) => $presentation?->listItems ?? collect())->first(); };
    $documentName = function ($item) use ($genericName, $commercialName, $listItem, $imprimirMarcas) { $description = trim((string) ($listItem($item)?->descripcion_remision ?? '')); if ($description !== '') return $description; $generic = $genericName($item); $commercial = trim((string) $commercialName($item)); return $imprimirMarcas && $commercial !== '' && strcasecmp($generic, $commercial) !== 0 ? "$generic ($commercial)" : $generic; };
    $presentationName = fn ($item) => $item?->presentation?->presentacion ?? $item?->input?->nutritionMedicineCatalog?->presentations?->first()?->presentacion ?? '—';
    $medicationLines = collect($pricingSummary['lines'] ?? []);
    $billableInputs = collect($inputs_solicitud)->reject(function ($item) {
        $description = mb_strtolower(trim((string) ($item?->input?->description ?? '')));
        return str_contains($description, 'servicio de mezclado')
            || str_contains($description, 'preparación para npt')
            || str_contains($description, 'preparacion para npt');
    })->values();
    $remissionMode = $medicationLines->contains(fn ($line) => strtolower((string) ($line['unit_label'] ?? '')) === 'frasco') ? 'frasco' : 'ml';
    $logoFile = public_path('img/logo-cbta.jpg'); $prodifemLogo = file_exists($logoFile) ? 'file://' . $logoFile : null;
    $distributorLogo = null; if (!empty($distributor?->logo_path)) { $file = public_path('storage/' . $distributor->logo_path); $distributorLogo = file_exists($file) ? 'file://' . $file : null; }
    $distributorName = $distributor->nombre ?? 'Distribuidor';
    $distributorInformation = $distributor->informacion_adicional ?? $distributor->direccion ?? null;
@endphp
@include('pdfs.nutricionales.partials.remision-page', [
    'issuerName' => !empty($distributor) ? $distributorName : 'CMP Prodifem',
    'issuerLogo' => $prodifemLogo,
    'issuerInformation' => !empty($distributor) ? $distributorInformation : null,
])
</body></html>
