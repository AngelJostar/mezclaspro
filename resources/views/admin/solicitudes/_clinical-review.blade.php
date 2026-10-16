<section class="clinical-review" data-clinical-review
    data-validation-url="{{ route('admin.solicitudes.clinical.validate', ['kind' => $clinicalKind]) }}" aria-label="Observaciones de validacion clinica">
    @if (isset($preparationQuotation))<input type="hidden" name="clinical_quotation_id" value="{{ $preparationQuotation->id }}">@endif
    <input type="hidden" name="clinical_review_token" value="">
    <details class="clinical-context" data-clinical-context>
        <summary>Datos para la revision clinica (opcionales)</summary>
        <p class="clinical-notice">Puedes dejarlos vacíos si no cuentas con esta información.</p>
        <div class="clinical-context-fields">
            @foreach (\App\Services\Clinical\ClinicalPayload::CONTEXT_FIELDS as $key => $label)
                <label for="clinical-context-{{ $key }}">{{ $label }}
                    <textarea id="clinical-context-{{ $key }}" name="clinical_context[{{ $key }}]" rows="2" maxlength="1500">{{ old('clinical_context.'.$key) }}</textarea>
                </label>
            @endforeach
        </div>
    </details>
    <div data-clinical-result aria-live="polite"></div>
    <p data-clinical-error role="alert" hidden></p>
    <dialog class="clinical-authorization-modal" data-medical-authorization disabled aria-labelledby="clinical-authorization-title">
        <div class="clinical-authorization-modal__header">
            <h3 id="clinical-authorization-title">Advertencia: envio bajo autorizacion medica</h3>
            <button type="button" data-medical-authorization-cancel aria-label="Cerrar">&times;</button>
        </div>
        <div class="clinical-authorization-modal__body">
            <p>Advertencias detectadas:</p>
            <ul class="clinical-authorization-findings" data-medical-authorization-findings></ul>
            <div class="clinical-context-fields">
                <label>Nombre completo del medico que autoriza*
                    <input name="medical_authorization[doctor_name]" type="text" required minlength="3" maxlength="255" autocomplete="off" disabled>
                </label>
                <label>Cedula profesional*
                    <input name="medical_authorization[doctor_license]" type="text" required minlength="3" maxlength="50" autocomplete="off" disabled>
                </label>
            </div>
            <label class="clinical-authorization-confirmation">
                <input name="medical_authorization[risk_accepted]" type="checkbox" value="1" required disabled>
                <span>Confirmo que el médico conoce estas advertencias y autoriza el envío.</span>
            </label>
            <p class="clinical-error" data-medical-authorization-error role="alert" hidden></p>
        </div>
        <div class="clinical-authorization-modal__actions">
            <button type="button" data-medical-authorization-cancel>Cancelar</button>
            <button type="button" data-medical-authorization-submit>Confirmar autorizacion y enviar</button>
        </div>
    </dialog>
    <label data-clinical-ack hidden><input type="checkbox" name="clinical_acknowledged" value="1"> <span data-clinical-ack-text>Confirmo que revisé las advertencias.</span></label>
    @error('clinical_review')<p class="clinical-error" role="alert">{{ $message }}</p>@enderror
</section>
