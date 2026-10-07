    <div class="conciliation-agent-toolbar" data-conciliation-agent>
        @include('admin.instituciones.partials.administration-carousel', ['administrationSection' => 'conciliacion'])
        <button type="button" class="ca-primary ca-launch" data-agent-open aria-haspopup="dialog"
            data-url="{{ route('admin.instituciones.conciliaciones.agent', request()->only(['institucion_id', 'hospital_id', 'search'])) }}"><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="M20 2v4m-2-2h4M4 17v5m-2-2.5h4"/></svg><span>Agente de IA</span></button>
        <dialog class="ca-dialog" data-agent-dialog aria-labelledby="conciliation-agent-title">
            <header class="ca-heading"><span class="ca-symbol"><i data-ca-icon="sparkles" aria-hidden="true"></i></span><div><h2 id="conciliation-agent-title" tabindex="-1">Agente de conciliación</h2><p>Administración · Conciliación</p></div><button class="ca-icon" type="button" data-agent-close aria-label="Cerrar agente" title="Cerrar"><i data-ca-icon="x" aria-hidden="true"></i></button></header>
            <div class="ca-body"><p data-agent-loading role="status" hidden>Cargando agente...</p><div data-agent-content></div><p data-agent-error role="alert" hidden></p><button type="button" class="ca-secondary" data-agent-retry hidden>Reintentar</button></div>
        </dialog>
    </div>
