<div class="agent-run-content">
    @unless ($providerConfigured)<p class="agent-error">Conexion OpenAI pendiente: configura la clave API del proyecto. Sin conexion, la validacion no habilita el envio.</p>@endunless
    <livewire:admin.clinical-manual-library />
    <livewire:admin.clinical-agent-chat :agent-id="$agent->id" :active="$agent->is_active" :configured="$providerConfigured" :key="'clinical-agent-chat-'.$agent->id" />
</div>
