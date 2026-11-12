<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Comparativo de nutriciones</title><style>
@page { margin: 30px 30px 36px; } body { font-family: DejaVu Sans,sans-serif; color:#17365d; font-size:9px; line-height:1.4; }
h1 {font-size:20px;margin:0 0 5px;} h2 {font-size:13px;margin:15px 0 7px;color:#007e78;} h3 {font-size:11px;margin:10px 0 4px;}
p {margin:4px 0;} table {border-collapse:collapse;width:100%;table-layout:fixed;} th,td {border:1px solid #d4dfec;padding:6px;vertical-align:top;overflow-wrap:break-word;}
th {background:#eaf2fb;text-align:left;font-weight:bold;} tr {page-break-inside:avoid;} thead {display:table-header-group;} small {display:block;color:#60738d;font-size:8px;}
.meta {margin:12px 0;} .muted {color:#60738d;} .page {page-break-before:always;} .note {background:#f3f7fb;padding:9px;margin:12px 0;}
.footer {position:fixed;bottom:-22px;left:0;right:0;border-top:1px solid #d4dfec;font-size:8px;padding-top:5px;color:#60738d;} .footer .page-number:after {content:counter(page);}
</style></head><body>
@php($format = fn($value) => \App\Services\Agents\PatientNutritionComparison::display($value))
<div class="footer">PROMESA · Informe #{{ $run->id }} · Página <span class="page-number"></span></div>
<h1>Comparativo de nutriciones por paciente</h1><p>{{ $report['hospital'] }}</p>
<table class="meta"><tr><th>Paciente</th><td>{{ $report['patient'] }}</td><th>Expediente</th><td>{{ $report['registry'] ?: 'Sin registro' }}</td></tr><tr><th>Periodo de consulta</th><td>{{ $report['from'] }} - {{ $report['to'] }}</td><th>Informe generado</th><td>{{ $run->started_at->format('d/m/Y H:i') }}</td></tr></table>
<h2>Resumen de cambios registrados</h2>
<table><tr><th>Nutriciones / días</th><th>Peso inicial / final</th><th>Cambio de peso</th><th>Cambios de cantidades prescritas</th></tr><tr><td>{{ $report['summary']['count'] }} nutriciones / {{ $report['summary']['days'] }} días</td><td>{{ $format($report['summary']['first_weight']) }} / {{ $format($report['summary']['last_weight']) }} kg</td><td>{{ $format($report['summary']['weight_change']) }} kg ({{ $format($report['summary']['weight_percent']) }} %)</td><td>{{ $report['summary']['changes'] }}</td></tr></table>
<div class="note">@foreach ($report['notes'] as $note)<p>{{ $note }}</p>@endforeach</div>
@foreach (array_chunk($report['columns'], 4, true) as $columns)
<div class="page"><h2>Tabla comparativa · día vs. nutrientes ({{ $loop->iteration }}/{{ $loop->count }})</h2><p class="muted">Δ: cambio respecto de la nutrición anterior. Las concentraciones se expresan por 100 mL.</p>
<table><thead><tr><th style="width:15%">Fecha / nutrición</th><th style="width:13%">Peso / volumen</th>@foreach ($columns as $column)<th>{{ $column['label'] }}<small>{{ $column['unit'] }}</small></th>@endforeach</tr></thead><tbody>
@foreach ($report['rows'] as $row)<tr><td>{{ $row['date'] }}<small>#{{ $row['id'] }} · {{ $row['remission'] ?: 'Sin remisión' }}</small></td><td>{{ $format($row['weight']) }} kg<small>Δ {{ $format($row['weight_change']) }} kg</small><small>{{ $format($row['volume']) }} mL</small></td>
@foreach ($columns as $key=>$column)@php($component = $row['components'][$key] ?? null)<td>@if ($component)<strong>{{ $format($component['quantity']) }}</strong><small>Δ {{ $format($component['change']) }} {{ $column['unit'] }}</small><small>{{ $format($component['concentration']) }} {{ $column['concentration_unit'] }}</small><small>Δ {{ $format($component['concentration_change']) }} {{ $column['concentration_unit'] }}</small>@else Sin dato @endif</td>@endforeach
</tr>@endforeach</tbody></table></div>@endforeach
@foreach ($report['rows'] as $row)
<div class="page"><h2>Detalle de nutrición #{{ $row['id'] }}</h2>
<table class="meta"><tr><th>Fecha de entrega</th><td>{{ $row['date'] }}</td><th>Remisión / lote</th><td>{{ $row['remission'] ?: 'Sin remisión' }} / {{ $row['lot'] ?: 'Sin lote' }}</td></tr><tr><th>Médico prescriptor</th><td>{{ $row['doctor'] ?: 'Sin dato' }}</td><th>Tipo / vía</th><td>{{ $row['mode'] ?: 'Sin dato' }} / {{ $row['route'] ?: 'Sin dato' }}</td></tr><tr><th>Peso / volumen prescrito</th><td>{{ $format($row['weight']) }} kg / {{ $format($row['volume']) }} mL</td><th>Infusión</th><td>{{ $row['infusion_hours'] ?: 'Sin dato' }} horas</td></tr></table>
<table><thead><tr><th>Componente</th><th>Cantidad prescrita</th><th>Volumen registrado del componente</th><th>Concentración calculada</th></tr></thead><tbody>@foreach ($row['components'] as $key=>$component)@php($column = $report['columns'][$key])<tr><td>{{ $column['label'] }}</td><td>{{ $format($component['quantity']) }} {{ $column['unit'] }}</td><td>{{ $format($component['volume_ml']) }} mL</td><td>{{ $format($component['concentration']) }} {{ $column['concentration_unit'] }}</td></tr>@endforeach</tbody></table>
</div>@endforeach
</body></html>
