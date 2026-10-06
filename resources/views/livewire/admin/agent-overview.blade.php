<section class="agent-overview" aria-label="Información del agente">
    @php
        $isClinical = $agent->integration_key === \App\Services\Clinical\ClinicalEvidence::KEY;
        $configuration = array_replace($configOptions::defaults($agent), $agent->configuration ?? []);
        $ruleDefinitions = collect($configOptions::rules())->only($configuration['rules']);
        $activationLabel = $configOptions::ACTIVATIONS[$configuration['activation']] ?? 'Sin configurar';
        $sourceLabels = collect($configuration['sources'])->map(fn ($key) => $configOptions::SOURCES[$key] ?? $key)->implode(', ');
        $toolLabels = collect($configuration['tools'])->map(fn ($key) => $configOptions::TOOLS[$key] ?? $key)->implode(', ');
        $resultLabels = collect($configuration['results'])->map(fn ($key) => $configOptions::RESULTS[$key] ?? $key)->implode(', ');
        $overviewIcon = fn ($name) => '<span class="overview-icon" aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="'.$name.'"></i></span>';
    @endphp
    <header class="overview-title">
        <h3>{{ $agent->name }}</h3>
        <div class="overview-state">
            <span class="overview-badge" data-active="{{ $agent->is_active ? 'true' : 'false' }}"><i class="agent-state-dot" data-active="{{ $agent->is_active ? 'true' : 'false' }}" aria-hidden="true"></i>{{ $agent->is_active ? 'Activo' : 'Inactivo' }}</span>
            <button type="button" class="agent-switch" role="switch" aria-checked="{{ $agent->is_active ? 'true' : 'false' }}" aria-label="Estado de {{ $agent->name }}" wire:click="setAgentActive({{ $agent->id }}, {{ $agent->is_active ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="setAgentActive">
                <span class="agent-switch-text" aria-hidden="true">{{ $agent->is_active ? 'ON' : 'OFF' }}</span><span class="agent-switch-thumb" aria-hidden="true"></span>
            </button>
        </div>
    </header>
    <div class="overview-card overview-summary">
        <div>{!! $overviewIcon('file-text') !!}<div><strong>Descripción</strong><p>{{ $agent->description ?: 'Sin descripción' }}</p></div></div>
        <div>{!! $overviewIcon('folder') !!}<div><strong>Área</strong><p>{{ $isClinical ? 'Solicitudes y mensajes' : ($configuration['owner'] ?: 'Sin asignar') }}</p></div></div>
        <div>{!! $overviewIcon('play') !!}<div><strong>Ejecución</strong><p>{{ $isClinical ? 'Bajo demanda' : $activationLabel }}</p></div></div>
        <button class="overview-edit" type="button" wire:click="editAgent({{ $agent->id }})">{!! $overviewIcon('pencil') !!}Editar información</button>
    </div>
    <div class="overview-columns">
        <section class="overview-card">
            <header class="overview-card-heading">{!! $overviewIcon('file-text') !!}<h4>Instrucciones del agente</h4><button class="overview-edit" type="button" wire:click="editAgent({{ $agent->id }})">{!! $overviewIcon('pencil') !!}Editar instrucciones</button></header>
            <ol class="overview-steps">
                @if ($isClinical)
                <li><span class="overview-number">01</span><div><strong>Criterios de rechazo</strong><p>Muestra SOLICITUD RECHAZADA, explica cada causa y señala los campos que deben corregirse. Bloquea el envío hasta corregir y volver a validar. Si coexisten advertencias y rechazos, prevalece el rechazo.</p></div></li>
                <li><span class="overview-number">02</span><div><strong>Aplicación del manual</strong><p>Respeta la clasificación Advertencia/Rechazo del manual y los protocolos revisados. No inventes límites ni interpretes contradicciones como excepciones permitidas.</p></div></li>
                <li><span class="overview-number">03</span><div><strong>Falta de evidencia</strong><p>La falta de evidencia o una revisión incompleta mantienen el envío bloqueado.</p></div></li>
                <li><span class="overview-number">04</span><div><strong>Propuestas de corrección</strong><p>Indica el campo, valor actual, valor o condición requerida, unidades y fuente con sección. Prioriza el manual maestro vigente.</p></div></li>
                @else
                <li><span class="overview-number">01</span><div><strong>Objetivo</strong><p>{{ $configuration['objective'] ?: 'Sin definir' }}</p></div></li>
                <li><span class="overview-number">02</span><div><strong>Criterios de revisión</strong>
                    @forelse ($ruleDefinitions as $rule)
                        <p>{{ $rule['label'] }}</p>
                    @empty<p>Selecciona las reglas desde Editar instrucciones para configurar la revisión.</p>@endforelse
                </div></li>
                <li><span class="overview-number">03</span><div><strong>Alcance</strong>
                    <p>@if (!$agent->configuration) Pendiente de configurar @elseif ($configuration['scope_all']) Todo el sistema @elseif (!$configuration['institutions'] && !$configuration['laboratories'] && !$configuration['warehouses']) Sin alcance seleccionado @else Instituciones: {{ implode(', ', $configuration['institutions']) ?: 'Sin filtro' }} · Centrales: {{ implode(', ', $configuration['laboratories']) ?: 'Sin filtro' }} · Almacenes: {{ implode(', ', $configuration['warehouses']) ?: 'Sin filtro' }} @endif</p>
                    @if ($agent->integration_key === 'admin_conciliation')<p><a href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion']) }}">Administración · Conciliación</a></p>@endif
                </div></li>
                <li><span class="overview-number">04</span><div><strong>Resultado esperado</strong><p>{{ $resultLabels ?: 'Sin resultados configurados' }}</p></div></li>
                @endif
            </ol>
            <details class="overview-full"><summary>Ver instrucciones completas del agente</summary><pre>{{ $agent->instructions ?: 'Sin instrucciones' }}</pre></details>
        </section>
        <div class="overview-side">
            <section class="overview-card">
                <header class="overview-card-heading">{!! $overviewIcon('mouse-pointer-2') !!}<h4>Cómo se ejecuta</h4></header>
                <div class="overview-methods">
                    @if ($isClinical)
                    <div class="overview-method">{!! $overviewIcon('mouse-pointer-2') !!}<div><strong>Validar y Continuar</strong><p>Al pulsar se revisa la solicitud según las instrucciones del agente.</p></div></div>
                    <div class="overview-method">{!! $overviewIcon('message-circle') !!}<div><strong>Mensajes</strong><p>Como soporte clínico en la sección de mensajes.</p></div></div>
                    @else
                    <div class="overview-method">{!! $overviewIcon('play') !!}<div><strong>Ejecución manual</strong><p>Revisa los registros del alcance seleccionado con las reglas configuradas.</p>
                        <button type="button" class="overview-edit overview-run" wire:click="runAgent({{ $agent->id }})" wire:loading.attr="disabled" wire:target="runAgent" @disabled(!$agent->is_active)>
                            {!! $overviewIcon('play') !!}<span wire:loading.remove wire:target="runAgent({{ $agent->id }})">Ejecutar ahora</span><span wire:loading wire:target="runAgent({{ $agent->id }})">Ejecutando...</span>
                        </button>
                    </div></div>
                    <div class="overview-method">{!! $overviewIcon('clock') !!}<div><strong>Programación</strong><p>{{ $activationLabel }}</p>
                        @if ($configuration['activation'] !== 'manual')<p>Programador {{ $schedulerSeen ? 'en línea' : 'sin señal reciente' }}</p>@else<p>Pulsa Ejecutar ahora para iniciar una revisión.</p>@endif
                    </div></div>
                    @endif
                </div>
                @if ($isClinical)
                <div class="overview-confirm">{!! $overviewIcon('user-round') !!}<div><strong>El usuario revisa y confirma los cambios antes de enviar</strong><p>El agente propone correcciones; la decisión final es del usuario.</p></div></div>
                @else
                <div class="overview-confirm">{!! $overviewIcon('user-round') !!}<div><strong>Revisión y seguimiento</strong><p>Responsable: {{ $configuration['owner'] ?: 'Sin asignar' }}. Consulta la evidencia y actualiza el seguimiento de los hallazgos.</p></div></div>
                @endif
            </section>
            <section class="overview-card">
                <header class="overview-card-heading">{!! $overviewIcon('shield-check') !!}<h4>Datos utilizados</h4></header>
                @if ($isClinical)
                <p>Edad en días, peso, sexo, componentes del catálogo, cantidades, unidades, vías, cálculos y campos de revisión clínica capturados expresamente.</p>
                <div class="overview-privacy">{!! $overviewIcon('lock-keyhole') !!}<div><strong>Privacidad</strong><p>En la validación no se envían nombre, fecha de nacimiento, expediente, notas generales ni mensajes de las mezclas. No incluir identificadores dentro del contexto clínico.</p></div></div>
                <p>Los datos ausentes se indican como limitaciones, nunca como ausencia de riesgo.</p>
                @else
                <p>{{ $sourceLabels ?: 'Sin fuentes configuradas' }}</p>
                <div class="overview-privacy">{!! $overviewIcon('lock-keyhole') !!}<div><strong>Herramientas y permisos</strong><p>{{ $toolLabels ?: 'Ninguna herramienta seleccionada' }}</p><p>{{ $configuration['analysis'] === 'openai' ? 'Análisis con OpenAI' : 'Análisis local' }} · Sin modificaciones operativas.</p></div></div>
                @if ($ruleDefinitions->isNotEmpty())
                    <details class="overview-full"><summary>Límites de la revisión</summary>@foreach ($ruleDefinitions as $rule)<p>{{ $rule['limit'] }}</p>@endforeach</details>
                @endif
                @endif
            </section>
        </div>
    </div>
    <div class="overview-note">{!! $overviewIcon('info') !!}<span>{{ $isClinical ? 'La IA no autoriza ni cambia la mezcla. No modifica dosis, autorizaciones ni estados automáticamente. El envío no equivale a aprobar la preparación.' : 'El agente consulta y analiza los registros de su alcance. Los hallazgos y las acciones sugeridas requieren revisión del responsable.' }}</span></div>
</section>
