@php
    $configuration = $agent->configuration ?? $configOptions::defaults($agent);
    $runs = $agent->runs()->latest('id')->limit($historyLimit)->get();
    $findingsQuery = $agent->findings();
    if ($findingFilter === 'open') $findingsQuery->whereIn('status', ['new', 'review']);
    elseif (array_key_exists($findingFilter, \App\Models\AiAgentFinding::statusLabels())) $findingsQuery->where('status', $findingFilter);
    $findingCount = (clone $findingsQuery)->count();
    $findings = $findingsQuery->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")->latest('last_seen_at')->limit($findingLimit)->get();
    $priorityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
@endphp
<dl class="agent-criteria">
    <div><dt>Objetivo</dt><dd>{{ $configuration['objective'] ?: 'Sin definir' }}</dd></div>
    <div><dt>Alcance</dt><dd>@if (!$agent->configuration) Pendiente de configurar @elseif ($configuration['scope_all']) Todo el sistema @elseif (! $configuration['institutions'] && ! $configuration['laboratories'] && ! $configuration['warehouses']) Sin alcance seleccionado @else Instituciones: {{ implode(', ', $configuration['institutions']) ?: 'Sin filtro' }} · Centrales: {{ implode(', ', $configuration['laboratories']) ?: 'Sin filtro' }} · Almacenes: {{ implode(', ', $configuration['warehouses']) ?: 'Sin filtro' }} @endif</dd></div>
    <div><dt>Datos</dt><dd>{{ collect($configuration['sources'])->map(fn ($key) => $configOptions::SOURCES[$key] ?? $key)->implode(', ') ?: 'Sin fuentes' }}</dd></div>
    <div><dt>Herramientas</dt><dd>{{ collect($configuration['tools'])->map(fn ($key) => $configOptions::TOOLS[$key] ?? $key)->implode(', ') ?: 'Ninguna' }}</dd></div>
    <div><dt>Activación</dt><dd>{{ $configOptions::ACTIVATIONS[$configuration['activation']] }} @if ($configuration['activation'] !== 'manual') · Programador {{ $schedulerSeen ? 'en línea' : 'sin señal reciente' }} @endif</dd></div>
    <div><dt>Permisos</dt><dd>Sin modificaciones operativas · {{ $configuration['analysis'] === 'openai' ? 'Análisis con OpenAI' : 'Análisis local' }}</dd></div>
    <div><dt>Resultado</dt><dd>{{ collect($configuration['results'])->map(fn ($key) => $configOptions::RESULTS[$key] ?? $key)->implode(', ') }}</dd></div>
    <div><dt>Responsable</dt><dd>{{ $configuration['owner'] ?: 'Sin asignar' }}</dd></div>
</dl>
<details class="agent-run-history" open>
    <summary>Seguimiento <span class="agent-count">{{ $agent->runs()->count() }} ejecuciones</span></summary>
    @forelse ($runs as $run)
        <details class="agent-run" wire:key="agent-run-{{ $run->id }}" @if ($loop->first) open @endif>
            <summary>#{{ $run->id }} · {{ $run->started_at->format('d/m/Y H:i:s') }} · {{ \App\Models\AiAgentRun::statusLabels()[$run->status] }} · {{ count($run->result['findings'] ?? []) }} hallazgos</summary>
            <div class="agent-run-content">
                <p>Motor: reglas del sistema · {{ $run->trigger === 'manual' ? 'Manual' : 'Programada' }} · {{ array_sum($run->result['coverage'] ?? []) }} registros revisados · {{ $run->result['alerts'] ?? 0 }} alertas registradas/actualizadas</p>
                @foreach ($run->result['issues'] ?? [] as $issue)<p class="agent-error">{{ $issue }}</p>@endforeach
                <details><summary>Cobertura y límites</summary>
                    @foreach ($run->result['coverage'] ?? [] as $source => $count)<p>{{ $configOptions::SOURCES[$source] ?? $source }}: {{ $count }}</p>@endforeach
                    @foreach ($run->result['limits'] ?? [] as $limit)<p class="agent-muted">{{ $limit }}</p>@endforeach
                </details>
                @if (isset($run->result['analysis']))
                    <h4>Interpretación de OpenAI · {{ $run->result['analysis']['model'] }}</h4>
                    <p class="agent-muted">Requiere revisión humana · {{ $run->result['analysis']['analyzed_findings'] }} de {{ $run->result['analysis']['total_findings'] }} hallazgos analizados</p>
                    <p>{{ $run->result['analysis']['summary'] }}</p>
                    @foreach ($run->result['analysis']['recommendations'] as $recommendation)<p>Hallazgo {{ $recommendation['index'] + 1 }}: {{ $recommendation['explanation'] }}</p>@endforeach
                @endif
                <details><summary>Resultados de esta ejecución</summary>
                    @forelse ($run->result['findings'] ?? [] as $index => $evidence)
                        <div class="agent-evidence"><strong>{{ $index + 1 }}. {{ $evidence['title'] }}</strong><p>{{ $evidence['reason'] }}</p>
                            <p>{{ $evidence['record']['record_type'] }} #{{ $evidence['record']['id'] }}</p>
                            @if (in_array('impact', $run->configuration['results']))<p>Impacto: {{ $evidence['amount'] === null ? 'No calculable con los datos disponibles' : number_format($evidence['amount'], 2).' '.$evidence['unit'] }}</p>@endif
                            @if (in_array('action', $run->configuration['results']))<p>{{ $evidence['action'] }}</p>@endif
                        </div>
                    @empty<p class="agent-muted">Sin hallazgos en los registros revisados.</p>@endforelse
                </details>
            </div>
        </details>
    @empty<p class="agent-empty">Sin ejecuciones registradas.</p>@endforelse
    @if ($runs->count() === $historyLimit)<button class="agent-command" type="button" wire:click="loadMore('runs')">Más ejecuciones</button>@endif
</details>
<div class="agent-findings-heading">
    <h4>Hallazgos <span class="agent-count">{{ $findingCount }}</span></h4>
    <label>Estado <select wire:model.live="findingFilter"><option value="open">Abiertos</option><option value="all">Todos</option>@foreach (\App\Models\AiAgentFinding::statusLabels() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
</div>
@forelse ($findings as $finding)
    @php
        $evidence = $finding->evidence;
        $record = $evidence['record'];
        $recordUrl = \App\Models\AiAgentFinding::recordUrl($record);
    @endphp
    <details class="agent-finding" wire:key="finding-{{ $finding->id }}">
        <summary><span class="agent-priority" data-priority="{{ $finding->priority }}">{{ $priorityLabels[$finding->priority] }}</span> #{{ $finding->id }} {{ $evidence['title'] }} <span class="agent-muted">{{ \App\Models\AiAgentFinding::statusLabels()[$finding->status] }}</span></summary>
        <div class="agent-run-content">
            <p>{{ $evidence['reason'] }}</p>
            <p>Responsable: {{ $finding->owner }} · Última detección: {{ $finding->last_seen_at->format('d/m/Y H:i') }}</p>
            @if (in_array('impact', $configuration['results']))<p>Impacto: {{ $evidence['amount'] === null ? 'No calculable con los datos disponibles' : number_format($evidence['amount'], 2).' '.$evidence['unit'] }}</p>@endif
            @if (in_array('action', $configuration['results']))<p>{{ $evidence['action'] }}</p>@endif
            <details><summary>Evidencia: {{ $record['record_type'] }} #{{ $record['id'] }}</summary><pre>{{ json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
            @if ($recordUrl)<a class="agent-command" href="{{ $recordUrl }}" target="_blank" rel="noopener">Abrir registro o módulo</a>@endif
            @if ($finding->resolution)<p>Seguimiento: {{ $finding->resolution }}</p>@endif
            <button type="button" class="agent-command" wire:click="reviewFinding({{ $finding->id }})">Actualizar seguimiento</button>
        </div>
    </details>
@empty<p class="agent-empty">Sin hallazgos en este estado.</p>@endforelse
@if ($findingCount > $findingLimit)<button class="agent-command" type="button" wire:click="loadMore('findings')">Más hallazgos</button>@endif
