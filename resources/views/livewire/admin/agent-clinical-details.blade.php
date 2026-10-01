<div class="agent-run-content">
    <p>Ejecucion bajo demanda al pulsar Validar y Continuar, y soporte clinico dentro de Mensajes. El mismo usuario revisa y confirma los cambios capturados antes de enviar. No cambia dosis, autorizaciones ni estados de preparacion automaticamente.</p>
    @unless ($providerConfigured)<p class="agent-error">Conexion OpenAI pendiente: configura la clave API del proyecto. Sin conexion, la validacion no habilita el envio.</p>@endunless
    <p class="agent-muted">En la validacion de solicitudes se transmiten edad en dias, peso, sexo, componentes del catalogo, cantidades, unidades, vias, calculos y los campos de revision clinica capturados expresamente. No se envian los campos de nombre, fecha de nacimiento, expediente, notas generales ni mensajes de las mezclas. No incluir identificadores dentro del contexto clinico. Los datos ausentes se indican como limitaciones, nunca como ausencia de riesgo.</p>
    <h4>Fuentes y protocolos</h4>
    @foreach (\App\Models\ClinicalSource::current()->orderByDesc('is_manual')->orderBy('id')->get() as $source)
        <details class="agent-run" wire:key="clinical-source-{{ $source->id }}">
            <summary>S{{ $source->id }} · {{ $source->title }} · {{ $source->isReviewed() ? 'Revisada' : 'Pendiente de revision o vencida' }}</summary>
            <div class="agent-run-content">
                <p>{{ $source->reference }} · {{ $source->category }}</p>
                @if ($source->is_manual && $source->manual_version === '4')
                    <button type="button" class="agent-command" wire:click="downloadClinicalManual({{ $source->id }})" wire:loading.attr="disabled">Descargar Manual V4</button>
                @endif
                <p>Responsable: {{ $source->clinical_reviewer ?: 'Sin asignar' }} · Vigencia: {{ $source->valid_until?->format('d/m/Y') ?: 'Sin definir' }}</p>
                <pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:300px;overflow:auto">{{ $source->content }}</pre>
                @if ($source->isReviewed())<button type="button" class="agent-command" wire:click="revokeClinicalSource({{ $source->id }})">Retirar aprobacion de fuente</button>@endif
            </div>
        </details>
    @endforeach
    @if (\App\Models\ClinicalSource::current()->where('is_manual', true)->where('manual_version', '4')->exists())
        <p class="agent-error">{{ \App\Services\Clinical\ClinicalEvidence::MANUAL_LIMITATIONS }}</p>
    @endif
    @if (\App\Models\ClinicalSource::whereNotNull('superseded_at')->exists())
        <details class="agent-run">
            <summary>Manuales anteriores (historico)</summary>
            <div class="agent-run-content">
                @foreach (\App\Models\ClinicalSource::whereNotNull('superseded_at')->orderByDesc('id')->get() as $previousSource)
                    <details><summary>S{{ $previousSource->id }} · {{ $previousSource->title }} · Sustituido</summary>
                        <pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:300px;overflow:auto">{{ $previousSource->content }}</pre>
                    </details>
                @endforeach
            </div>
        </details>
    @endif
    <details class="agent-run">
        <summary>Agregar protocolo o ficha tecnica revisada</summary>
        <form wire:submit="saveClinicalSource">
            <div class="agent-form-fields">
                <label>Titulo<input type="text" wire:model="clinicalSource.title" required maxlength="255"></label>
                <label>Referencia, version, seccion y enlace o identificador documental<input type="text" wire:model="clinicalSource.reference" required maxlength="1000"></label>
                <label>Categoria<select wire:model="clinicalSource.category"><option value="nutricionales">Nutricionales</option><option value="oncologicos">Oncologicos</option><option value="antibioticos">Antibioticos</option></select></label>
                <label>Extracto aplicable, incluyendo condiciones y limites<textarea wire:model="clinicalSource.content" rows="8" required minlength="100" maxlength="30000"></textarea></label>
                <label>Profesional que reviso la fuente y cedula<input type="text" wire:model="clinicalSource.clinical_reviewer" required maxlength="255"></label>
                <label>Vigente hasta<input type="date" wire:model="clinicalSource.valid_until" required></label>
                <label><input type="checkbox" wire:model="clinicalSource.resolves_manual_ambiguities"> Esta fuente aclara expresamente las discrepancias del manual actual, identificando version, supuesto, criterio, unidades y limites aplicables.</label>
                <label><input type="checkbox" wire:model="clinicalSource.allows_medical_authorization"> El protocolo documenta excepciones a recomendaciones de dosis mediante autorizacion medica, sin omitir limites de seguridad ni compatibilidad.</label>
                <label><input type="checkbox" wire:model="clinicalSource.allows_chemical_medical_authorization"> El protocolo permite expresamente autorizar advertencias sobre recomendaciones quimicas. No permite omitir rechazos por incompatibilidad, inestabilidad ni limites absolutos de seguridad.</label>
                <label><input type="checkbox" wire:model="clinicalSource.confirmed" required> Confirmo la revision por el profesional indicado, su aplicabilidad y que no contiene datos de pacientes.</label>
                @foreach ($errors->getMessages() as $field => $messages) @if (str_starts_with($field, 'clinicalSource.'))<p class="agent-error" role="alert">{{ implode(' ', $messages) }}</p>@endif @endforeach
            </div>
            <footer class="agent-modal-actions"><button class="agent-save" type="submit" wire:loading.attr="disabled" wire:target="saveClinicalSource">Guardar fuente revisada</button></footer>
        </form>
    </details>
    <h4>Revisiones recientes</h4>
    @forelse (\App\Models\ClinicalReview::latest()->limit(20)->get() as $review)
        @php($displayResult = app(\App\Services\Clinical\ClinicalReviewService::class)->resultForDisplay($review))
        <details class="agent-run"><summary>{{ $review->created_at->format('d/m/Y H:i') }} · {{ $review->kind }} · {{ $review->purpose === 'conversation' ? 'Mensajes' : 'Solicitud' }} · {{ $review->can_submit ? 'Sin bloqueos detectados' : 'Requiere revision' }}</summary>
            <div class="agent-run-content"><p>{{ $review->result['summary'] }}</p>
                @if ($review->medical_authorization)
                    <p>Autorizacion medica registrada: {{ $review->medical_authorization['doctor_name'] }} · Cedula: {{ $review->medical_authorization['doctor_license'] }}</p>
                    @if (!empty($review->medical_authorization['reference']))<p>Referencia: {{ $review->medical_authorization['reference'] }}</p>@endif
                    @if (!empty($review->medical_authorization['reason']))<p>Motivo: {{ $review->medical_authorization['reason'] }}</p>@endif
                    <p>Registrada por usuario {{ $review->medical_authorization['recorded_by'] }} · {{ $review->medical_authorization['recorded_at'] }}. No equivale a aprobacion de preparacion.</p>
                @endif
                @foreach ($displayResult['findings'] as $finding)
                    <p>{{ $finding['message'] }} {{ $finding['calculation'] }}</p>
                    @if (!empty($finding['suggestion']))<p>Sugerencia: {{ $finding['suggestion'] }}</p>@endif
                @endforeach
                <p class="agent-muted">Registro: {{ $review->record_type ?: 'Sin enviar' }} {{ $review->record_id }} · Modelo: {{ $review->result['model'] ?: 'Sin respuesta de OpenAI' }}</p>
            </div>
        </details>
    @empty<p class="agent-muted">Sin revisiones.</p>@endforelse
    <livewire:admin.clinical-agent-chat :agent-id="$agent->id" :active="$agent->is_active" :configured="$providerConfigured" :key="'clinical-agent-chat-'.$agent->id" />
</div>
