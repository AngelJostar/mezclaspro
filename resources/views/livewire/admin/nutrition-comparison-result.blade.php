@php($format = fn($value) => \App\Services\Agents\PatientNutritionComparison::display($value))
<div class="nutrition-summary">
    <div><span>Nutriciones entregadas</span><strong>{{ $report['summary']['count'] }}</strong><small>{{ $report['summary']['days'] }} días con registros</small></div>
    <div><span>Peso inicial / final registrado</span><strong>{{ $format($report['summary']['first_weight']) }} / {{ $format($report['summary']['last_weight']) }} kg</strong></div>
    <div><span>Cambio de peso registrado</span><strong>{{ $format($report['summary']['weight_change']) }} kg</strong><small>{{ $format($report['summary']['weight_percent']) }} %</small></div>
    <div><span>Cambios en cantidades prescritas</span><strong>{{ $report['summary']['changes'] }}</strong><small>Respecto de la nutrición anterior</small></div>
</div>
<h4>Comparativo por día y nutriente</h4><p>Cantidad prescrita y concentración calculada por 100 mL. Δ indica la diferencia contra la nutrición anterior.</p>
<div class="operational-table-scroll"><table class="operational-table nutrition-matrix"><thead><tr><th>Fecha / nutrición</th><th>Peso / volumen</th>@foreach ($report['columns'] as $column)<th>{{ $column['label'] }}<small>{{ $column['unit'] }}</small></th>@endforeach</tr></thead><tbody>
@foreach ($report['rows'] as $row)<tr><td>{{ $row['date'] }}<small>#{{ $row['id'] }} · {{ $row['remission'] ?: 'Sin remisión' }}</small></td><td>{{ $format($row['weight']) }} kg<small>Δ {{ $format($row['weight_change']) }} kg</small><small>{{ $format($row['volume']) }} mL</small></td>
    @foreach ($report['columns'] as $key=>$column)@php($component = $row['components'][$key] ?? null)<td>@if ($component)<strong>{{ $format($component['quantity']) }}</strong><small>Δ {{ $format($component['change']) }} {{ $column['unit'] }}</small><small>{{ $format($component['concentration']) }} {{ $column['concentration_unit'] }}</small><small>Δ {{ $format($component['concentration_change']) }} {{ $column['concentration_unit'] }}</small>@else Sin dato @endif</td>@endforeach
</tr>@endforeach
</tbody></table></div>
<details class="operational-history"><summary>Cómo se calculó el informe</summary>@foreach ($report['notes'] as $note)<p>{{ $note }}</p>@endforeach</details>
