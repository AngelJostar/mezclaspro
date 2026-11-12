<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evidencia de validación nutricional</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #17365d; font-family: DejaVu Sans, sans-serif; font-size: 9.5px; line-height: 1.35; }
        h1 { margin: 0 0 3px; font-size: 17px; color: #102f59; }
        h2 { margin: 18px 0 7px; padding: 6px 8px; background: #eaf3fb; border-left: 4px solid #167c80; font-size: 11px; }
        .meta { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .meta td { width: 50%; padding: 4px 6px; border: 1px solid #ced9e5; }
        .notice { margin: 10px 0 0; padding: 7px 9px; border: 1px solid #e5b84b; background: #fff9e8; color: #704900; }
        .fields { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .fields th, .fields td { padding: 5px 7px; border: 1px solid #d7e0ea; vertical-align: top; overflow-wrap: break-word; }
        .fields th { width: 40%; background: #f6f8fb; text-align: left; }
        .finding { margin: 0 0 10px; padding: 8px 10px; border: 1px solid #dfb4b4; border-left: 4px solid #b42318; page-break-inside: avoid; }
        .finding.warning { border-color: #e7c98d; border-left-color: #b7791f; background: #fffaf0; }
        .finding strong { display: block; margin-bottom: 4px; font-size: 10.5px; }
        .finding p { margin: 3px 0; }
        .code { color: #526a83; font-size: 8.5px; font-weight: bold; }
        .suggestion { margin-top: 6px !important; padding-left: 7px; border-left: 3px solid #147664; color: #174c43; }
        .empty { padding: 9px; border: 1px solid #b8ddc6; background: #f1fbf5; color: #136639; }
        .footer { margin-top: 18px; padding-top: 6px; border-top: 1px solid #bdcad8; color: #687c91; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <h1>EVIDENCIA TEMPORAL DE VALIDACIÓN</h1>
    <div>Solicitud de Nutrición Parenteral</div>
    <table class="meta">
        <tr><td><strong>Generada:</strong> {{ $generatedAt->format('d/m/Y H:i:s') }}</td><td><strong>Usuario:</strong> {{ $generatedBy }}</td></tr>
    </table>
    <div class="notice">Documento temporal para pruebas. No guarda, envía ni autoriza una solicitud y no sustituye los documentos oficiales del sistema.</div>

    @foreach ($sections as $section => $fields)
        <h2>{{ $section }}</h2>
        <table class="fields">
            @foreach ($fields as $field)
                <tr><th>{{ $field['label'] }}</th><td>{{ $field['value'] }}</td></tr>
            @endforeach
        </table>
    @endforeach

    <h2>Errores y advertencias mostrados</h2>
    @forelse ($findings as $finding)
        <div class="finding {{ in_array($finding['severity'] ?? '', ['authorization', 'advisory', 'review']) ? 'warning' : '' }}">
            <strong>{{ $finding['title'] }}</strong>
            @if (!empty($finding['rule_code']))<div class="code">{{ $finding['rule_code'] }}</div>@endif
            @if (!empty($finding['composition_code']))<div class="code">{{ $finding['composition_code'] }}</div>@endif
            <p>{{ $finding['message'] }}</p>
            @if (!empty($finding['calculation']))<p>{{ $finding['calculation'] }}</p>@endif
            @if (!empty($finding['suggestion']))<p class="suggestion">{{ $finding['suggestion'] }}</p>@endif
        </div>
    @empty
        <div class="empty">No había errores ni advertencias visibles al generar esta evidencia.</div>
    @endforelse

    <div class="footer">PROMESA · Evidencia temporal generada desde el formulario de captura</div>
</body>
</html>
