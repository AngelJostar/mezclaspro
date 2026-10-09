<section class="clinical-review" data-clinical-review
    data-validation-url="{{ route('admin.solicitudes.clinical.validate', ['kind' => $clinicalKind]) }}" aria-label="Observaciones de validacion clinica">
    @if (isset($preparationQuotation))<input type="hidden" name="clinical_quotation_id" value="{{ $preparationQuotation->id }}">@endif
    <input type="hidden" name="clinical_review_token" value="">
    <details class="clinical-context" data-clinical-context>
        <summary>Datos para la revision clinica (opcionales)</summary>
        <p class="clinical-notice">Estos campos son opcionales y se envian a OpenAI cuando se capturan. Pueden mejorar la revision, pero dejarlos vacios no bloquea la solicitud. No incluyas nombres, expedientes ni identificadores, ni afirmes ausencia de riesgo sin verificar.</p>
        <div class="clinical-context-fields">
            @foreach (\App\Services\Clinical\ClinicalPayload::CONTEXT_FIELDS as $key => $label)
                <label for="clinical-context-{{ $key }}">{{ $label }}
                    <textarea id="clinical-context-{{ $key }}" name="clinical_context[{{ $key }}]" rows="2" maxlength="1500">{{ old('clinical_context.'.$key) }}</textarea>
                </label>
            @endforeach
        </div>
    </details>
    <h3>Observaciones de validacion clinica</h3>
    <p class="clinical-notice">Soporte de IA sujeto a revision profesional. No autoriza la preparacion. Se consultan parametros de la mezcla y los datos de revision clinica; las notas generales y mensajes permanecen en PROMESA.</p>
    <div data-clinical-result aria-live="polite"></div>
    <p data-clinical-error role="alert" hidden></p>
    <fieldset class="clinical-authorization" data-medical-authorization hidden disabled>
        <legend>Autorizacion medica para envio con excepcion</legend>
        <p>Advertencia: la mezcla requiere autorizacion del area medica para enviarse con parametros fuera de los limites clinicos o quimicos recomendados. Registra la autorizacion recibida; no sustituye la aprobacion de preparacion ni permite omitir un rechazo.</p>
        <div class="clinical-context-fields">
            <label>Nombre del medico*<input name="medical_authorization[doctor_name]" type="text" required minlength="3" maxlength="255" autocomplete="off"></label>
            <label>Cedula profesional*<input name="medical_authorization[doctor_license]" type="text" required minlength="3" maxlength="50" autocomplete="off"></label>
        </div>
        <p class="clinical-notice">Los datos de autorizacion quedan en PROMESA. No se envian a OpenAI. Esta captura no verifica automaticamente la identidad ni la cedula del medico.</p>
    </fieldset>
    <label data-clinical-ack hidden><input type="checkbox" name="clinical_acknowledged" value="1"> <span data-clinical-ack-text>He revisado las observaciones de la IA y confirmo los cambios que capture. Enviar la solicitud no autoriza su preparacion.</span></label>
    @error('clinical_review')<p class="clinical-error" role="alert">{{ $message }}</p>@enderror
</section>
