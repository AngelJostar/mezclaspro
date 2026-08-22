<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de solicitud nutricional</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #1f2937; margin: 0; padding: 24px; }
        .card { max-width: 980px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 28px; }
        .muted { color: #6b7280; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .item { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; }
        .item strong { display: block; font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; vertical-align: top; }
        th { background: #f8fafc; color: #334155; font-size: 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Solicitud nutricional</h1>
        <p class="muted">Detalle generado desde el código QR de la etiqueta.</p>

        <div class="grid">
            <div class="item"><strong>Hospital</strong>{{ $hospital?->name ?? 'Sin hospital' }}</div>
            <div class="item"><strong>Paciente</strong>{{ trim(($paciente?->nombre_paciente ?? '') . ' ' . ($paciente?->apellidos_paciente ?? '')) ?: 'Sin paciente' }}</div>
            <div class="item"><strong>Lote</strong>{{ $solicitud->lote ?? 'Sin lote' }}</div>
            <div class="item"><strong>Remisión</strong>{{ $solicitud->remision ?? 'Sin remisión' }}</div>
            <div class="item"><strong>Estado</strong><span class="badge">{{ ucfirst((string) ($solicitud->estado ?? 'sin estado')) }}</span></div>
            <div class="item"><strong>Médico</strong>{{ $detalle?->nombre_medico ?? 'Sin médico' }}</div>
        </div>

        <h2 style="font-size:18px; margin: 20px 0 8px;">Insumos</h2>
        <table>
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Dosis</th>
                    <th>Presentación</th>
                    <th>Marca</th>
                    <th>Lote</th>
                    <th>Caducidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($insumos as $insumo)
                    <tr>
                        <td>{{ $insumo['nombre'] ?: 'Insumo' }}</td>
                        <td>
                            @if (is_numeric($insumo['dosis']))
                                {{ rtrim(rtrim(number_format((float) $insumo['dosis'], 4, '.', ''), '0'), '.') }} {{ $insumo['unidad'] ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $insumo['presentacion'] ?: '—' }}</td>
                        <td>{{ $insumo['marca'] ?: '—' }}</td>
                        <td>{{ $insumo['lote'] ?: '—' }}</td>
                        <td>{{ $insumo['caducidad'] ? \Carbon\Carbon::parse($insumo['caducidad'])->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay insumos registrados para esta solicitud.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
