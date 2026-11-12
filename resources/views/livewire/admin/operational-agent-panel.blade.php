<section class="agent-overview operational-agent" aria-label="{{ $agent->name }}"
    x-data @agent-access-saved.window="if (Number($event.detail.id) === {{ $agent->id }}) $wire.$refresh()">
    @php($overviewIcon = fn ($name) => '<span class="overview-icon" aria-hidden="true" wire:ignore x-init="$nextTick(() => window.refreshAgentIcons?.($el))"><i data-agent-icon="'.$name.'"></i></span>')
    @include('livewire.admin.operational-agent-overview')
    @if ($editingDescription && $canEdit)
        <div class="agent-modal-overlay" wire:key="operational-editor-{{ $agent->id }}-{{ $editingSection }}">
            <section class="agent-modal" role="dialog" aria-modal="true" aria-labelledby="operational-editor-title-{{ $agent->id }}"
                x-data x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.editField.focus())"
                @keydown.escape.prevent.stop="$wire.$set('editingDescription', false)">
                <header class="agent-modal-header"><h3 id="operational-editor-title-{{ $agent->id }}">{{ $editingSection === 'instructions' ? 'Editar instrucciones' : 'Editar información' }}</h3>
                    <button type="button" class="agent-edit" wire:click="$set('editingDescription', false)" aria-label="Cerrar edición del agente">{!! $overviewIcon('x') !!}</button>
                </header>
                <form wire:submit="saveDescription">
                    <div class="agent-form-fields">
                        @if ($editingSection === 'instructions')
                            <div><label for="operational-instructions-{{ $agent->id }}">Instrucciones</label><textarea id="operational-instructions-{{ $agent->id }}" wire:model="instructions" maxlength="20000" rows="8" x-ref="editField"></textarea>@error('instructions')<p class="agent-error" role="alert">{{ $message }}</p>@enderror</div>
                        @else
                            <div><label for="operational-description-{{ $agent->id }}">Descripción</label><textarea id="operational-description-{{ $agent->id }}" wire:model="description" maxlength="1000" rows="4" x-ref="editField"></textarea>@error('description')<p class="agent-error" role="alert">{{ $message }}</p>@enderror</div>
                        @endif
                    </div>
                    <footer class="agent-modal-actions"><button class="agent-cancel" type="button" wire:click="$set('editingDescription', false)">Cancelar</button><button class="agent-save" type="submit" wire:loading.attr="disabled">Guardar</button></footer>
                </form>
            </section>
        </div>
    @endif
    @if ($status)<p class="agent-status" role="status">{{ $status }}</p>@endif
    @if ($errors->any())<div class="agent-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if ($agent->integration_key === \App\Services\Agents\OperationalAgents::CONCILIATION)
        @if ($isSuper)
        <form wire:submit="saveSettings" class="overview-card operational-settings">
            <h4>Programación de conciliaciones</h4><p>Se envía un periodo por hospital con sus remisiones nuevas del periodo anterior. Los periodos sin remisiones se omiten.</p>
            <div class="operational-fields">
                <label>Frecuencia<select wire:model.live="settings.frequency"><option value="monthly">Mensual · mes anterior</option><option value="weekly">Semanal · semana anterior</option><option value="daily">Diaria · día anterior</option></select></label>
                @if ($settings['frequency'] === 'monthly')<label>Día del mes<input type="number" min="1" max="28" wire:model="settings.day"></label>@endif
                @if ($settings['frequency'] === 'weekly')<label>Día de la semana<select wire:model="settings.weekday">@foreach ([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $key=>$day)<option value="{{ $key }}">{{ $day }}</option>@endforeach</select></label>@endif
                <label>Hora de envío<input type="time" wire:model="settings.time" required><small>Hora de Ciudad de México</small></label>
            </div>
            <div class="operational-hospital-header"><h4>Hospitales destinatarios <span class="agent-count">{{ count($settings['hospital_ids']) }} seleccionados</span></h4>
                <label><input type="checkbox" data-select-all-hospitals wire:change="selectAllHospitals($event.target.checked)" @checked($hospitals->where('is_active', true)->where('access_is_active', true)->filter(fn($h) => $h->instituciones->isNotEmpty())->count() > 0 && !array_diff($hospitals->where('is_active', true)->where('access_is_active', true)->filter(fn($h) => $h->instituciones->isNotEmpty())->pluck('id')->all(), $settings['hospital_ids']))> Seleccionar todos los hospitales activos</label>
                <input type="search" wire:model.live.debounce.250ms="hospitalSearch" placeholder="Buscar hospital o institución" aria-label="Buscar hospitales destinatarios">
            </div>
            <div class="operational-hospital-list">
            @foreach ($hospitals->filter(fn($hospital) => !$hospitalSearch || \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($hospital->name.' '.$hospital->instituciones->pluck('nombre')->implode(' '))), \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($hospitalSearch)))) as $hospital)
                <label wire:key="recipient-{{ $hospital->id }}"><input type="checkbox" wire:model.live="settings.hospital_ids" value="{{ $hospital->id }}" @disabled(!$hospital->is_active || !$hospital->access_is_active || $hospital->instituciones->isEmpty())><span><strong>{{ $hospital->name }}</strong><small>{{ $hospital->instituciones->pluck('nombre')->implode(' · ') ?: 'Sin institución' }}{{ !$hospital->is_active || !$hospital->access_is_active ? ' · Inactivo' : '' }}</small></span></label>
            @endforeach
            </div>
            <div class="operational-email-toggle"><div><h4>Notificaciones por correo</h4><p>Avisar al hospital y a su institución después de cada envío.</p></div><button type="button" class="agent-switch" role="switch" aria-label="Notificaciones por correo" aria-checked="{{ $settings['email_enabled'] ? 'true' : 'false' }}" wire:click="$toggle('settings.email_enabled')"><span class="agent-switch-text">{{ $settings['email_enabled'] ? 'ON' : 'OFF' }}</span><span class="agent-switch-thumb"></span></button></div>
            @if ($settings['email_enabled'])
                <div class="operational-fields">
                @foreach ($hospitals->whereIn('id', $settings['hospital_ids']) as $hospital)<label wire:key="mail-hospital-{{ $hospital->id }}">Correo del hospital · {{ $hospital->name }}<input type="email" required wire:model="settings.hospital_emails.{{ $hospital->id }}"></label>@endforeach
                @foreach ($hospitals->whereIn('id', $settings['hospital_ids'])->flatMap->instituciones->unique('id') as $institution)<label wire:key="mail-institution-{{ $institution->id }}">Correo de la institución · {{ $institution->nombre }}<input type="email" required wire:model="settings.institution_emails.{{ $institution->id }}"></label>@endforeach
                </div>
            @endif
            <div class="operational-actions"><button class="agent-save" type="submit" wire:loading.attr="disabled">Guardar programación</button><span>Próximo envío: {{ $agent->next_run_at?->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? 'Guarda la programación' }} · {{ $schedulerSeen ? 'Programador en línea' : 'Programador sin señal reciente' }}</span></div>
        </form>
        @endif
        @if ($canRun)<div class="operational-actions"><button class="agent-command" type="button" wire:click="sendPreviousPeriod" wire:confirm="Se crearán y enviarán conciliaciones del periodo anterior a los hospitales autorizados de la configuración guardada. ¿Continuar?" wire:loading.attr="disabled" @disabled(!$agent->is_active)>Enviar periodo anterior ahora</button><span>Utiliza la programación guardada por el superadministrador y los hospitales de tu alcance.</span></div>@endif
        <section class="overview-card" wire:poll.30s><h4>Envíos y respuestas de hospitales</h4>
            <div class="operational-table-scroll"><table class="operational-table"><thead><tr><th>Hospital</th><th>Periodo</th><th>Envío</th><th>Respuesta del hospital</th><th>Notificación por correo</th></tr></thead><tbody>
            @forelse ($deliveries as $delivery)
                @php($reply = \App\Services\ClientConciliationReview::response($delivery->submission))
                <tr><td>{{ $delivery->submission->hospital_name }}</td><td>{{ $delivery->submission->periodLabel() }}</td><td>{{ $delivery->submission->folio() }}<small>{{ $delivery->submission->created_at->format('d/m/Y H:i') }}</small></td>
                    <td>@if ($reply)<strong>{{ ($reply->filters['response_type'] ?? '') === 'accepted' ? 'Aceptada' : 'Con cambios' }}</strong><small>{{ $reply->sender_name }} · {{ $reply->created_at->format('d/m/Y H:i') }}</small><details><summary>Ver respuesta</summary>@foreach ($reply->snapshot as $item)@if (!empty($item['reason']) || ($item['change_requested'] ?? false))<p>{{ $item['remision'] ?? $item['id'] }}: {{ $item['reason'] ?? 'Cambio solicitado' }}</p>@endif @endforeach</details>@else Pendiente de respuesta @endif</td>
                    <td>@forelse ($delivery->emails as $mail)<p>{{ $mail->recipient }}<small>{{ ['sent'=>'Enviado','pending'=>'Pendiente','failed'=>'Error de envío'][$mail->status] ?? $mail->status }}{{ $mail->sent_at ? ' · '.$mail->sent_at->format('d/m/Y H:i') : '' }}</small>@if ($mail->error)<small class="agent-error">{{ $mail->error }}</small>@endif</p>@if ($isSuper && $mail->status !== 'sent' && $mail->error)<button type="button" class="agent-command" wire:click="retryEmail({{ $mail->id }})" wire:loading.attr="disabled">Reintentar correo</button>@endif @empty Desactivadas en este envío @endforelse</td>
                </tr>
            @empty<tr><td colspan="5">Todavía no hay conciliaciones enviadas por este agente.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section class="overview-card"><h4>Historial de ejecuciones</h4>@forelse ($runs as $run)<details class="operational-history"><summary>#{{ $run->id }} · {{ $run->started_at->format('d/m/Y H:i') }} · {{ \App\Models\AiAgentRun::statusLabels()[$run->status] }}</summary>@foreach ($run->result['deliveries'] ?? [] as $result)<p>{{ $result['hospital'] }} · {{ $result['status'] }} {{ $result['folio'] ?? '' }}</p>@endforeach @foreach ($run->result['issues'] ?? [] as $issue)<p class="agent-error">{{ $issue }}</p>@endforeach</details>@empty<p>Sin ejecuciones.</p>@endforelse</section>
    @else
        @if ($canRun)
        <form wire:submit="analyzePatient" class="overview-card operational-settings"><h4>Buscar paciente</h4><p>Selecciona el hospital y captura el expediente. Si no tiene expediente, usa nombre completo y fecha de nacimiento.</p>
            <div class="operational-fields">
                <label>Hospital<select wire:model="patient.hospital_id" aria-label="Hospital" required><option value="">Selecciona un hospital</option>@foreach ($hospitals as $hospital)<option value="{{ $hospital->id }}">{{ $hospital->name }}</option>@endforeach</select></label>
                <label>Expediente / registro<input wire:model="patient.registry" maxlength="100" placeholder="Número de expediente"></label>
                <label>Nombre(s)<input wire:model="patient.first_name" maxlength="150"></label><label>Apellidos<input wire:model="patient.last_name" maxlength="150"></label><label>Fecha de nacimiento<input type="date" wire:model="patient.birth_date"></label>
                <label>Desde<input type="date" wire:model="patient.from" required></label><label>Hasta<input type="date" wire:model="patient.to" required></label>
            </div><button class="agent-save" type="submit" wire:loading.attr="disabled" @disabled(!$agent->is_active)><span wire:loading.remove wire:target="analyzePatient">Analizar nutriciones</span><span wire:loading wire:target="analyzePatient">Analizando…</span></button>
            @if (!$agent->is_active)<p>El superadministrador debe activar el agente para realizar el análisis.</p>@endif
        </form>
        @endif
        @if ($report)
        <section class="overview-card"><div class="operational-actions"><h4>Análisis de {{ $report['patient'] }}</h4><a class="agent-save" href="{{ route('admin.agents.nutrition-report', ['run'=>$reportId,'download'=>1]) }}">Descargar PDF</a><a class="agent-command" href="{{ route('admin.agents.nutrition-report', $reportId) }}" target="_blank" rel="noopener">Ver PDF</a></div>
            <p>{{ $report['hospital'] }} · {{ $report['from'] }} al {{ $report['to'] }}</p>
            @include('livewire.admin.nutrition-comparison-result')
        </section>
        @endif
        <section class="overview-card"><h4>Informes disponibles</h4>@forelse ($runs as $run)@if ($run->result['nutrition_report'] ?? null)<div class="operational-history"><button type="button" class="agent-command" wire:click="openReport({{ $run->id }})">{{ $run->result['nutrition_report']['patient'] }} · {{ $run->started_at->format('d/m/Y H:i') }}</button><small>{{ $run->result['nutrition_report']['hospital'] }}</small></div>@endif @empty<p>Los análisis aparecerán aquí al terminar.</p>@endforelse</section>
    @endif
    @if ($runs->count() >= $historyLimit || $deliveries->count() >= $historyLimit)<button class="agent-command" wire:click="$set('historyLimit', {{ min(500, $historyLimit + 20) }})" @disabled($historyLimit >= 500)>Mostrar más registros</button>@endif
</section>
