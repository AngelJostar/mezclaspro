<fieldset class="agent-config-section">
    <legend>Objetivo</legend>
    <label for="agent-objective">Resultado que debe conseguir</label>
    <textarea id="agent-objective" wire:model="agentConfig.objective" rows="2" maxlength="2000"></textarea>
</fieldset>
<fieldset class="agent-config-section">
    <legend>Alcance</legend>
    <label class="agent-check"><input type="checkbox" wire:model.live="agentConfig.scope_all"> Todo el sistema autorizado al superadministrador</label>
    @if (! ($agentConfig['scope_all'] ?? false))
        <div class="agent-config-columns">
            @foreach (['institutions' => ['Instituciones', $institutions, 'nombre'], 'laboratories' => ['Centrales', $laboratories, 'nombre'], 'warehouses' => ['Almacenes', $warehouses, 'name']] as $key => [$label, $options, $name])
                <div>
                    <h4>{{ $label }}</h4>
                    <div class="agent-check-list" role="group" aria-label="{{ $label }} autorizados">
                        @forelse ($options as $option)
                            <label class="agent-check"><input type="checkbox" value="{{ $option->id }}" wire:model="agentConfig.{{ $key }}"> {{ $option->{$name} }} @if ($key === 'warehouses')<small>Central #{{ $option->laboratory_id }}</small>@endif</label>
                        @empty
                            <span class="agent-muted">Sin registros</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</fieldset>
<fieldset class="agent-config-section">
    <legend>Reglas de revisión</legend>
    <div class="agent-check-list agent-rule-list">
        @foreach ($configOptions::rules() as $key => $rule)
            <label class="agent-check"><input type="checkbox" value="{{ $key }}" wire:model="agentConfig.rules"> {{ $rule['label'] }}</label>
        @endforeach
    </div>
    <div class="agent-config-columns">
        <div><label for="agent-expiry">Caducidad: anticipación (días)</label><input id="agent-expiry" type="number" min="0" max="365" wire:model="agentConfig.thresholds.expiry_days"></div>
        <div><label for="agent-delivery">Entrega: anticipación (minutos)</label><input id="agent-delivery" type="number" min="0" max="10080" wire:model="agentConfig.thresholds.delivery_minutes"></div>
        <div><label for="agent-collection">Cobranza: antigüedad (días)</label><input id="agent-collection" type="number" min="1" max="3650" wire:model="agentConfig.thresholds.collection_days"></div>
    </div>
    <label for="agent-priority">Prioridad de las alertas</label>
    <select id="agent-priority" wire:model="agentConfig.priority"><option value="high">Alta</option><option value="medium">Media</option><option value="low">Baja</option></select>
</fieldset>
<fieldset class="agent-config-section">
    <legend>Datos autorizados</legend>
    <div class="agent-check-list agent-rule-list">
        @foreach ($configOptions::SOURCES as $key => $label)
            <label class="agent-check"><input type="checkbox" value="{{ $key }}" wire:model="agentConfig.sources"> {{ $label }}</label>
        @endforeach
    </div>
</fieldset>
<fieldset class="agent-config-section">
    <legend>Herramientas y permisos</legend>
    @foreach ($configOptions::TOOLS as $key => $label)
        <label class="agent-check"><input type="checkbox" value="{{ $key }}" wire:model="agentConfig.tools"> {{ $label }}</label>
    @endforeach
    <p class="agent-muted">Cambios operativos: no autorizados</p>
    <label for="agent-analysis">Motor de análisis</label>
    <select id="agent-analysis" wire:model="agentConfig.analysis"><option value="rules">Reglas del sistema</option><option value="openai">Reglas + interpretación con OpenAI</option></select>
</fieldset>
<fieldset class="agent-config-section">
    <legend>Activación</legend>
    <label for="agent-activation">Cuándo revisar</label>
    <select id="agent-activation" wire:model="agentConfig.activation">
        @foreach ($configOptions::ACTIVATIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
    </select>
    <p class="agent-muted">Programador: {{ $schedulerSeen ? 'en línea' : 'sin señal reciente' }}</p>
</fieldset>
<fieldset class="agent-config-section">
    <legend>Resultado y seguimiento</legend>
    @foreach ($configOptions::RESULTS as $key => $label)
        <label class="agent-check"><input type="checkbox" value="{{ $key }}" wire:model="agentConfig.results"> {{ $label }}</label>
    @endforeach
    <label for="agent-owner">Responsable de seguimiento (persona o área)</label>
    <input id="agent-owner" type="text" maxlength="160" wire:model="agentConfig.owner">
    @error('agentConfig')<p class="agent-error" role="alert">{{ $message }}</p>@enderror
</fieldset>
