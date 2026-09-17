@php($local = ($agent->configuration['analysis'] ?? 'rules') !== 'openai')
<form action="{{ route('admin.instituciones.conciliaciones.agent.run') }}" method="POST" data-agent-run>
    @csrf
    @foreach($filters as $key => $value)<input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">@endforeach
    <div class="ca-meta"><div><span class="ca-state {{ $agent->is_active ? 'is-active' : '' }}"><span aria-hidden="true"></span>{{ $agent->is_active ? 'Activo' : 'Inactivo' }}</span><strong>{{ $agent->name }}</strong></div><div><span>Tipo de ejecución</span><strong>Manual</strong></div></div>
    <h3>Descripción</h3><p>{{ $agent->description }}</p>
    <section class="ca-scope"><h3><i data-ca-icon="file-text" aria-hidden="true"></i> Información que revisará</h3><dl><div><dt>Institución</dt><dd>{{ $institution }}</dd></div><div><dt>Hospital</dt><dd>{{ $hospital }}</dd></div><div><dt>Periodo</dt><dd>Todos los periodos recibidos</dd></div><div><dt>Origen</dt><dd>Filtros de institución, hospital y búsqueda</dd></div>@if($filters['search'] ?? '')<div><dt>Búsqueda</dt><dd>{{ $filters['search'] }}</dd></div>@endif</dl>
        @if(!($agent->configuration['scope_all'] ?? false))<p class="ca-note">Se aplicará también el alcance autorizado en el Centro de agentes.</p>@endif
    </section>
    <h3>Proceso del agente</h3><ol class="ca-steps"><li>Consultar las conciliaciones recibidas del alcance seleccionado.</li><li>Identificar registros no conciliables e información faltante.</li><li>Preparar el resumen y los hallazgos con sus folios.</li></ol>
    <div class="ca-expected"><i data-ca-icon="chart-no-axes-column-increasing" aria-hidden="true"></i><p><strong>Resultado esperado:</strong> Resumen con hallazgos y registros relacionados.</p></div>
    <label class="ca-instructions">Instrucciones adicionales (opcional)<textarea name="instructions" rows="3" maxlength="2000" placeholder="Indica qué deseas destacar en el reporte..."></textarea></label>
    <p class="ca-note">{{ $local ? 'Motor: análisis local. Las instrucciones adicionales se conservan en el historial; su interpretación requiere OpenAI.' : 'Motor: OpenAI. Se envían hallazgos calculados e instrucciones; no incluyas datos de pacientes en las instrucciones.' }}</p>
    @if(!$compatible)<p class="ca-error">La configuración contiene otras fuentes o reglas. Revísala en el Centro de agentes antes de ejecutar.</p>@endif
    <footer class="ca-footer"><span data-agent-progress role="status">{{ !$agent->is_active ? 'Agente desactivado' : ($compatible ? 'Listo para ejecutar' : 'Configuración pendiente') }}</span><button type="button" class="ca-secondary" data-agent-close>Cancelar</button><button type="submit" class="ca-primary" @disabled(!$agent->is_active || !$compatible)><i data-ca-icon="play" aria-hidden="true"></i> Correr proceso</button></footer>
    @if(auth()->user()->hasRole('Super Admin'))<a class="ca-center-link" href="{{ route('admin.superadministrator.index') }}#agent-center-title">Ver en el Centro de agentes</a>@endif
</form>
