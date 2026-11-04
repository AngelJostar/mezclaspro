<x-admin-layout>
    <section class="agent-center agent-portal">
        <header class="agent-center-heading"><h1>Centro de agentes</h1><span class="agent-count">{{ $agents->count() }} disponibles</span></header>
        <p class="agent-portal-intro">Consulta tus agentes y utiliza las opciones que te autorizó el superadministrador.</p>
        @if (session('status'))<p class="agent-portal-notice" role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<div class="agent-error" role="alert">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
        @if ($agents->isEmpty())
            <div class="agent-results-panel"><h2>No tienes agentes autorizados</h2><p>Cuando el superadministrador te conceda acceso, los agentes aparecerán aquí.</p></div>
        @else
            <nav class="agent-carousel-track" aria-label="Agentes autorizados">
                @foreach ($agents as $item)
                    <a class="agent-choice" href="{{ route('admin.agents.show', $item) }}" aria-current="{{ $agent->id === $item->id ? 'page' : 'false' }}">
                        <span class="agent-choice-icon" aria-hidden="true">{{ mb_substr($item->name, 0, 1) }}</span>
                        <span class="agent-choice-name">{{ $item->name }}</span>
                    </a>
                @endforeach
            </nav>
            <article class="agent-overview" x-data="{ editing: {{ $errors->hasAny(['name', 'description', 'instructions']) ? 'true' : 'false' }} }">
                <header class="overview-title">
                    <div><h2>{{ $agent->name }}</h2><p class="agent-access-help">Tus permisos: {{ collect(\App\Services\Agents\AgentAccess::LABELS)->filter(fn ($label, $key) => $permissions[$key])->implode(' · ') }}</p></div>
                    <span class="overview-badge" data-active="{{ $agent->is_active ? 'true' : 'false' }}">{{ $agent->is_active ? 'Activo' : 'Inactivo' }}</span>
                </header>
                @if ($hospitalScoped)<p class="agent-portal-notice">La consulta y ejecución incluyen únicamente información de tus hospitales. Se omiten fuentes que no puedan separarse por hospital.</p>@endif
                <div class="overview-card">
                    <header class="overview-card-heading"><h3>Información del agente</h3>
                        @if ($permissions['can_edit'])<button type="button" class="overview-edit" @click="editing = true">Editar agente</button>@endif
                    </header>
                    <p>{{ $agent->description ?: 'Sin descripción' }}</p>
                    <dl class="agent-portal-facts"><div><dt>Área</dt><dd>{{ $configuration['owner'] ?: 'Sin asignar' }}</dd></div><div><dt>Ejecución</dt><dd>{{ \App\Services\Agents\AgentConfiguration::ACTIVATIONS[$configuration['activation']] ?? 'Manual' }}</dd></div></dl>
                </div>
                <div class="overview-columns agent-portal-details">
                    <section class="overview-card"><h3>Instrucciones del agente</h3><p class="agent-portal-instructions">{{ $agent->instructions ?: 'Sin instrucciones registradas' }}</p>
                        <h4>Objetivo</h4><p>{{ $configuration['objective'] ?: 'Sin definir' }}</p>
                    </section>
                    <section class="overview-card"><h3>Cómo se ejecuta</h3>
                        @if ($agent->integration_key === \App\Services\Clinical\ClinicalEvidence::KEY)
                            <p>Este agente se utiliza al validar una solicitud o desde Mensajes de una mezcla, según tus permisos de acceso a solicitudes.</p>
                        @elseif ($permissions['can_run'])
                            <p>Ejecuta una revisión de los registros disponibles en el alcance del agente.</p>
                            <form method="POST" action="{{ route('admin.agents.run', $agent) }}" x-data="{ running: false }" @submit="running = true">
                                @csrf
                                <button type="submit" class="overview-edit overview-run" @disabled(!$agent->is_active) :disabled="running || {{ $agent->is_active ? 'false' : 'true' }}" x-text="running ? 'Ejecutando…' : 'Ejecutar ahora'">Ejecutar ahora</button>
                            </form>
                            @if (!$agent->is_active)<p class="agent-access-help">El superadministrador debe activar el agente para poder ejecutarlo.</p>@endif
                        @else<p>Tienes acceso a los resultados. La ejecución requiere autorización del superadministrador.</p>@endif
                    </section>
                </div>
                @if ($permissions['can_edit'])
                <div class="agent-modal-overlay" x-show="editing" x-cloak @keydown.escape.prevent.stop="editing = false">
                    <section class="agent-modal" role="dialog" aria-modal="true" aria-labelledby="edit-agent-title" x-trap.inert.noscroll="editing">
                        <header class="agent-modal-header"><h3 id="edit-agent-title">Editar agente</h3><button type="button" @click="editing = false" class="agent-edit" aria-label="Cerrar edición">×</button></header>
                        <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
                            @csrf @method('PUT')
                            <div class="agent-form-fields">
                                <p>Actualiza la información e instrucciones. El alcance, la activación y las autorizaciones se administran desde Superadministrador.</p>
                                <div><label for="agent-name">Nombre</label><input id="agent-name" name="name" value="{{ old('name', $agent->name) }}" required maxlength="120"></div>
                                <div><label for="agent-description">Descripción</label><textarea id="agent-description" name="description" rows="3" maxlength="1000">{{ old('description', $agent->description) }}</textarea></div>
                                <div><label for="agent-instructions">Instrucciones</label><textarea id="agent-instructions" name="instructions" rows="8" maxlength="20000">{{ old('instructions', $agent->instructions) }}</textarea></div>
                            </div>
                            <footer class="agent-modal-actions"><button type="button" class="agent-cancel" @click="editing = false">Cancelar</button><button type="submit" class="agent-save">Guardar cambios</button></footer>
                        </form>
                    </section>
                </div>
                @endif
            </article>
            <section class="agent-results-panel"><h2>Resultados de ejecuciones</h2>
                @forelse ($runs as $run)
                    <details class="agent-portal-run"><summary>{{ $run->started_at?->format('d/m/Y H:i') }} · {{ \App\Models\AiAgentRun::statusLabels()[$run->status] ?? $run->status }} · {{ count($run->result['findings'] ?? []) }} hallazgos</summary>
                        @foreach ($run->result['issues'] ?? [] as $issue)<p class="agent-error">{{ $issue }}</p>@endforeach
                        @if (!empty($run->result['analysis']['summary']))<p>{{ $run->result['analysis']['summary'] }}</p>@endif
                        @forelse ($run->result['findings'] ?? [] as $finding)
                            <div class="agent-portal-finding"><strong>{{ $finding['title'] ?? 'Hallazgo' }}</strong><p>{{ $finding['reason'] ?? '' }}</p><p>{{ $finding['action'] ?? '' }}</p></div>
                        @empty<p>No se encontraron hallazgos en esta ejecución.</p>@endforelse
                    </details>
                @empty<p>No hay ejecuciones disponibles para tu alcance.</p>@endforelse
            </section>
            <section class="agent-results-panel"><h2>Hallazgos y seguimiento</h2>
                @forelse ($findings as $finding)
                    <article class="agent-portal-finding"><header><h3>{{ $finding->evidence['title'] ?? 'Hallazgo' }}</h3><span>{{ \App\Models\AiAgentFinding::statusLabels()[$finding->status] ?? $finding->status }}</span></header>
                        <p>{{ $finding->evidence['reason'] ?? '' }}</p><p>{{ $finding->evidence['action'] ?? '' }}</p>
                        <small>{{ $finding->last_seen_at?->format('d/m/Y H:i') }} · {{ $finding->owner }}</small>
                    </article>
                @empty<p>No hay hallazgos registrados para tu alcance.</p>@endforelse
                {{ $findings->links() }}
            </section>
        @endif
    </section>
</x-admin-layout>
