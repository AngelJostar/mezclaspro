<section class="ca-result" aria-labelledby="ca-result-title">
    <div class="ca-result-heading"><i data-ca-icon="clipboard-check" aria-hidden="true"></i><h3 id="ca-result-title">Ejecución #{{ $run->id }} · {{ \App\Models\AiAgentRun::statusLabels()[$run->status] }}</h3></div>
    <p class="ca-note">{{ $run->started_at->format('d/m/Y H:i') }} · {{ array_sum($run->result['coverage'] ?? []) }} conciliaciones revisadas · {{ count($run->result['findings'] ?? []) }} hallazgos</p>
    @foreach($run->result['issues'] ?? [] as $issue)<p class="ca-error">{{ $issue }}</p>@endforeach
    @if($run->result['draft'] ?? null)<h3>Resumen</h3><p>{{ $run->result['draft'] }}</p>@endif
    @if($run->result['analysis'] ?? null)
        <h3>Interpretación de OpenAI</h3><p>{{ $run->result['analysis']['summary'] }}</p>
        @foreach($run->result['analysis']['recommendations'] as $item)<p>Hallazgo {{ $item['index'] + 1 }}: {{ $item['explanation'] }}</p>@endforeach
    @endif
    @foreach($run->result['findings'] ?? [] as $index => $finding)
        <article class="ca-finding"><h4>{{ $index + 1 }}. {{ $finding['title'] }}</h4><p>{{ $finding['reason'] }}</p><p>{{ $finding['action'] }}</p><a href="{{ \App\Models\AiAgentFinding::recordUrl($finding['record']) }}" target="_blank" rel="noopener">Ver conciliación CON-{{ str_pad($finding['record']['id'], 6, '0', STR_PAD_LEFT) }}</a></article>
    @endforeach
    @foreach($run->result['limits'] ?? [] as $limit)<p class="ca-note">{{ $limit }}</p>@endforeach
    <footer class="ca-footer">@if(auth()->user()->hasRole('Super Admin'))<a href="{{ route('admin.superadministrator.index') }}#agent-center-title">Ver en el Centro de agentes</a>@endif<button type="button" class="ca-primary" data-agent-close>Cerrar</button></footer>
</section>
