<div class="operational-overview" aria-label="Información del agente">
    @php
        $isConciliation = $agent->integration_key === \App\Services\Agents\OperationalAgents::CONCILIATION;
        $frequency = $agent->configuration['frequency'] ?? 'monthly';
        $executionLabel = $isConciliation ? (['monthly' => 'Mensual', 'weekly' => 'Semanal', 'daily' => 'Diaria'][$frequency] ?? 'Mensual') : 'Bajo demanda';
        $previousPeriod = ['monthly' => 'mes anterior', 'weekly' => 'semana anterior', 'daily' => 'día anterior'][$frequency] ?? 'mes anterior';
        $steps = $isConciliation ? [
            ['Periodo de conciliación', 'Utiliza las remisiones del '.$previousPeriod.' según la programación guardada por el superadministrador.'],
            ['Hospitales destinatarios', 'Genera un periodo independiente por cada hospital seleccionado que tenga remisiones disponibles. Cada hospital recibe únicamente sus propias remisiones.'],
            ['Envío y notificaciones', 'Envía los periodos y, cuando los avisos por correo están activados, notifica al hospital y a su institución.'],
            ['Seguimiento de respuestas', 'Registra el folio, la fecha de envío y la respuesta de cada hospital. Muestra las aceptaciones, los cambios solicitados y los errores de envío.'],
        ] : [
            ['Identificación del paciente', 'Selecciona el hospital y el expediente del paciente, o captura su nombre completo y fecha de nacimiento.'],
            ['Historial de nutriciones', 'Consulta las nutriciones entregadas o finalizadas dentro del rango de fechas seleccionado.'],
            ['Comparativo diario', 'Compara el peso registrado, las cantidades prescritas y las concentraciones por fecha, conservando las unidades de cada nutriente.'],
            ['Informe y PDF', 'Presenta el análisis en el Centro de agentes y genera un PDF descargable con la tabla comparativa y el detalle de cada nutrición.'],
        ];
    @endphp
    <header class="overview-title">
        <h3>{{ $agent->name }}</h3>
        <div class="overview-state">
            <span class="overview-badge" data-active="{{ $agent->is_active ? 'true' : 'false' }}"><i class="agent-state-dot" data-active="{{ $agent->is_active ? 'true' : 'false' }}" aria-hidden="true"></i>{{ $agent->is_active ? 'Activo' : 'Inactivo' }}</span>
            @if ($isSuper)
                <button type="button" class="agent-switch" role="switch" aria-label="Estado de {{ $agent->name }}" aria-checked="{{ $agent->is_active ? 'true' : 'false' }}" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive">
                    <span class="agent-switch-text" aria-hidden="true">{{ $agent->is_active ? 'ON' : 'OFF' }}</span><span class="agent-switch-thumb" aria-hidden="true"></span>
                </button>
            @endif
        </div>
    </header>
    <div class="overview-card overview-summary">
        <div>{!! $overviewIcon('file-text') !!}<div><strong>Descripción</strong><p>{{ $agent->description ?: 'Sin descripción' }}</p></div></div>
        <div>{!! $overviewIcon('folder') !!}<div><strong>Área</strong><p>{{ $isConciliation ? 'Administración / Conciliación' : 'Nutriciones / Pacientes' }}</p></div></div>
        <div>{!! $overviewIcon('play') !!}<div><strong>Ejecución</strong><p>{{ $executionLabel }}</p></div></div>
        <div class="overview-authorizations">
            {!! $overviewIcon('shield-check') !!}
            <div><strong>Autorizaciones</strong>
                @if ($isSuper && $manageAccess)
                    <p>{{ $authorizedUsers->count() }} {{ $authorizedUsers->count() === 1 ? 'usuario autorizado' : 'usuarios autorizados' }}</p>
                    @if ($authorizedUsers->isNotEmpty())
                        <details><summary>Ver usuarios y permisos</summary><ul class="agent-authorized-users">
                            @foreach ($authorizedUsers as $authorizedUser)<li><strong>{{ $authorizedUser['name'] }}</strong><span>{{ collect(\App\Services\Agents\AgentAccess::LABELS)->filter(fn ($label, $key) => $authorizedUser['permissions'][$key])->implode(' · ') }}</span></li>@endforeach
                        </ul></details>
                    @else<p>Sin accesos delegados</p>@endif
                    <button class="overview-edit" type="button" wire:click="$parent.openAccess({{ $agent->id }})">Dar accesos a usuarios y roles</button>
                @else
                    <p>Tus permisos</p><p>{{ collect(\App\Services\Agents\AgentAccess::LABELS)->filter(fn ($label, $key) => $permissions[$key])->implode(' · ') }}</p>
                @endif
            </div>
        </div>
        @if ($canEdit)<button class="overview-edit" type="button" wire:click="editDescription('information')">{!! $overviewIcon('pencil') !!}Editar información</button>@endif
    </div>
    <div class="overview-columns">
        <section class="overview-card">
            <header class="overview-card-heading">{!! $overviewIcon('file-text') !!}<h4>Instrucciones del agente</h4>
                @if ($canEdit)<button class="overview-edit" type="button" wire:click="editDescription('instructions')">{!! $overviewIcon('pencil') !!}Editar instrucciones</button>@endif
            </header>
            <ol class="overview-steps">
                @foreach ($steps as [$title, $text])
                    <li><span class="overview-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div class="overview-step-copy"><strong>{{ $title }}</strong><p>{{ $text }}</p></div></li>
                @endforeach
            </ol>
            <details class="overview-full"><summary>Ver instrucciones completas del agente</summary><pre>{{ $agent->instructions ?: 'Sin instrucciones' }}</pre></details>
        </section>
        <div class="overview-side">
            <section class="overview-card">
                <header class="overview-card-heading">{!! $overviewIcon('mouse-pointer-2') !!}<h4>Cómo se ejecuta</h4></header>
                <div class="overview-methods">
                    @if ($isConciliation)
                        <div class="overview-method">{!! $overviewIcon('clock') !!}<div><strong>Programación automática</strong><p>{{ $executionLabel }} · {{ ucfirst($previousPeriod) }}.</p><p>El agente utiliza la fecha, la hora y los hospitales guardados por el superadministrador.</p></div></div>
                        <div class="overview-method">{!! $overviewIcon('play') !!}<div><strong>Envío manual</strong><p>La opción Enviar periodo anterior ahora utiliza la programación guardada y los hospitales autorizados para el usuario.</p></div></div>
                    @else
                        <div class="overview-method">{!! $overviewIcon('play') !!}<div><strong>Analizar nutriciones</strong><p>Captura los datos del paciente y las fechas; después inicia el análisis desde el formulario.</p></div></div>
                        <div class="overview-method">{!! $overviewIcon('file-text') !!}<div><strong>Consultar informe</strong><p>Revisa el comparativo en pantalla o descarga el PDF con todas las nutriciones del periodo.</p></div></div>
                    @endif
                </div>
                <div class="overview-confirm">{!! $overviewIcon('user-round') !!}<div><strong>{{ $isConciliation ? 'Programación definida por el superadministrador' : 'El usuario revisa los resultados del análisis' }}</strong><p>{{ $isConciliation ? 'La selección de hospitales y el estado de las notificaciones se aplican al guardar la programación.' : 'El informe describe los cambios registrados y queda disponible en el historial de este agente.' }}</p></div></div>
            </section>
            <section class="overview-card">
                <header class="overview-card-heading">{!! $overviewIcon('shield-check') !!}<h4>Datos utilizados</h4></header>
                <p>{{ $isConciliation ? 'Institución, hospital, fechas, remisiones, importes y respuestas a las solicitudes de conciliación.' : 'Hospital, identificación del paciente, fechas de entrega, peso registrado, cantidades prescritas, unidades y volumen de cada nutrición.' }}</p>
                <div class="overview-privacy">{!! $overviewIcon('lock-keyhole') !!}<div><strong>Acceso por hospital</strong><p>{{ $isConciliation ? 'Cada periodo conserva las remisiones del hospital correspondiente. Los avisos por correo incluyen el folio, el periodo y el enlace de acceso.' : 'Las consultas y los PDF respetan los hospitales autorizados para cada usuario.' }}</p></div></div>
            </section>
        </div>
    </div>
    <div class="overview-note">{!! $overviewIcon('info') !!}<span>{{ $isConciliation ? 'Activa el agente después de guardar la programación. Consulta los envíos y las respuestas de los hospitales en el seguimiento.' : 'El comparativo presenta los datos registrados de las nutriciones. La valoración clínica y los cambios de tratamiento corresponden al profesional responsable.' }}</span></div>
</div>
