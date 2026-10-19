import '../css/clinical-review.css';

const conciseClinicalMessage = finding => {
    if (finding.category === 'compatibility_limitation') {
        return 'Compatibilidad y estabilidad no evaluadas: faltan condiciones opcionales de preparación o almacenamiento.';
    }
    return String(finding.message || '')
        .replace(/[;,.]?\s*(?:se\s+)?requiere\s+(?:valoraci[oó]n|evaluaci[oó]n|autorizaci[oó]n)\s+m[eé]dica(?:\s+[^.]+)?\.?/giu, '.')
        .replace(/\s+/g, ' ')
        .trim();
};

export function renderClinicalResult(container, data) {
    container.replaceChildren();
    const result = data.result;
    container.dataset.clinicalStatus = result.status;
    const paragraph = (text, className) => {
        const element = document.createElement('p'); element.textContent = text;
        if (className) element.className = className;
        return element;
    };
    const findings = result.findings || [];
    if (!findings.length) {
        if (result.status === 'no_blockers') container.append(paragraph('Sin errores ni advertencias.', 'clinical-status'));
        return;
    }
    const list = document.createElement('ul');
    for (const finding of findings) {
        const item = document.createElement('li'); item.dataset.severity = finding.severity;
        const isError = ['blocking', 'review'].includes(finding.severity);
        item.append(paragraph(isError ? 'Error:' : 'Advertencia:', 'clinical-status'));
        item.append(paragraph(conciseClinicalMessage(finding)));
        if (finding.calculation) item.append(paragraph(finding.calculation));
        if (finding.severity === 'blocking' && String(finding.suggestion || '').trim()) {
            item.append(paragraph(`Sugerencia: ${String(finding.suggestion).trim()}`, 'clinical-suggestion'));
        }
        list.append(item);
    }
    container.append(list);
}

const states = new WeakMap();
const fingerprint = form => JSON.stringify([...new FormData(form)].filter(([key]) => !['_token', 'clinical_review_token', 'clinical_acknowledged'].includes(key) && !key.startsWith('medical_authorization[')));

function markField(form, name, required = false, advisory = false) {
    const bracketName = name.replace(/\.([^.[\]]+)/g, '[$1]');
    let field = form.elements.namedItem(name) || form.elements.namedItem(bracketName);
    const match = /^mezclas\.(\d+)\.(?:medicamentos\.(\d+)\.)?(\w+)$/.exec(name);
    if (match) {
        const mixture = form.querySelectorAll('.oncology-mixture')[Number(match[1])];
        field = match[2] !== undefined ? mixture?.querySelectorAll(`[data-name="${match[3]}"]`)[Number(match[2])]
            : mixture?.querySelector(`[data-name="${match[3]}"]`);
    }
    if (!(field instanceof HTMLElement)) return;
    field.classList.add(required ? 'clinical-field-required-error' : (advisory ? 'clinical-field-advisory' : 'clinical-field-error'));
    field.setAttribute('aria-invalid', 'true');
    const details = field.closest('[data-clinical-context]');
    if (details) details.open = true;
    return field;
}
function bind(form) {
    if (states.has(form)) return states.get(form);
    const panel = form.querySelector('[data-clinical-review]'), button = form.querySelector('[data-clinical-submit]');
    if (!panel || !button) return null;
    const state = { panel, button, busy: false, snapshot: '', expires: 0, token: form.elements.clinical_review_token,
        ack: form.elements.clinical_acknowledged, ackText: panel.querySelector('[data-clinical-ack-text]'), error: panel.querySelector('[data-clinical-error]'), result: panel.querySelector('[data-clinical-result]'),
        authorization: panel.querySelector('[data-medical-authorization]'), authorizationFindings: panel.querySelector('[data-medical-authorization-findings]'),
        authorizationError: panel.querySelector('[data-medical-authorization-error]'), authorizationSubmit: panel.querySelector('[data-medical-authorization-submit]'),
        requiresAuthorization: false,
        requiresAcknowledgement: true, fieldError: false };
    const closeAuthorization = () => {
        if (!state.authorization) return;
        if (typeof state.authorization.close === 'function' && state.authorization.open) state.authorization.close();
        else state.authorization.removeAttribute('open');
        state.authorization.querySelectorAll('input, textarea').forEach(field => { field.disabled = true; });
    };
    const openAuthorization = () => {
        if (!state.authorization) return;
        state.authorizationError.hidden = true;
        state.authorization.querySelectorAll('input, textarea').forEach(field => { field.disabled = false; });
        if (typeof state.authorization.showModal === 'function') state.authorization.showModal();
        else state.authorization.setAttribute('open', '');
        state.authorization.querySelector('input')?.focus();
    };
    const reset = () => {
        state.token.value = ''; state.snapshot = ''; state.ack.checked = false;
        if (state.ackText) state.ackText.textContent = 'Confirmo que revisé las advertencias.';
        panel.querySelector('[data-clinical-ack]').hidden = true;
        state.requiresAuthorization = false;
        state.requiresAcknowledgement = true;
        if (state.authorization) {
            closeAuthorization(); state.authorization.disabled = true;
            state.authorizationFindings?.replaceChildren();
            state.authorization.querySelectorAll('input, textarea').forEach(field => {
                if (field.type === 'checkbox') field.checked = false; else field.value = '';
                field.disabled = true;
                field.classList.remove('clinical-field-required-error'); field.removeAttribute('aria-invalid');
            });
        }
        if (!state.busy) button.textContent = 'Validar y Continuar';
        form.querySelectorAll('.clinical-field-error, .clinical-field-advisory').forEach(el => {
            el.classList.remove('clinical-field-error', 'clinical-field-advisory'); el.removeAttribute('aria-invalid');
        });
    };
    const changed = event => {
        const field = event.target;
        if (field.validity?.valid) { field.classList.remove('clinical-field-required-error'); field.removeAttribute('aria-invalid'); }
        if (field !== state.ack && !field.closest('[data-medical-authorization]')) reset();
        if (field.name === 'npt') state.result.replaceChildren();
        if (state.fieldError && !form.querySelector(':invalid')) {
            state.error.hidden = true; state.fieldError = false;
        }
    };
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);
    form.addEventListener('invalid', event => {
        markField(form, event.target.name, true);
        state.fieldError = true;
        state.error.textContent = 'Completa o corrige los campos marcados en rojo antes de continuar.';
        state.error.hidden = false;
    }, true);
    state.authorization?.querySelectorAll('[data-medical-authorization-cancel]').forEach(cancel => cancel.addEventListener('click', closeAuthorization));
    state.authorization?.addEventListener('cancel', event => { event.preventDefault(); closeAuthorization(); });
    state.authorizationSubmit?.addEventListener('click', () => {
        const fields = [...state.authorization.querySelectorAll('input[required]')];
        const invalid = fields.find(field => !field.checkValidity());
        if (invalid) {
            invalid.reportValidity(); invalid.focus();
            state.authorizationError.textContent = invalid.type === 'checkbox'
                ? 'Debes confirmar que el medico conoce las advertencias y acepta continuar.'
                : 'Captura el nombre y la cedula profesional del medico que autoriza.';
            state.authorizationError.hidden = false;
            return;
        }
        state.ack.checked = true;
        state.busy = true; state.button.disabled = true; state.button.textContent = 'Enviando solicitud...';
        HTMLFormElement.prototype.submit.call(form);
    });
    state.openAuthorization = openAuthorization;
    state.reset = reset; states.set(form, state);
    return state;
}

window.validateClinicalRequest = async form => {
    const state = bind(form);
    if (!state || state.busy) return;
    const snapshot = fingerprint(form);
    if (state.token.value && state.snapshot === snapshot && state.expires > Date.now()) {
        if (state.requiresAuthorization) {
            state.openAuthorization();
            return;
        }
        if (!form.reportValidity()) return;
        if (state.requiresAcknowledgement && !state.ack.checked) {
            state.error.textContent = 'Confirma que revisaste los hallazgos antes de enviar.'; state.error.hidden = false; state.ack.focus(); return;
        }
        state.busy = true; state.button.disabled = true; state.button.textContent = 'Enviando solicitud...';
        HTMLFormElement.prototype.submit.call(form);
        return;
    }
    if (!form.reportValidity()) return;
    state.reset(); state.busy = true; state.button.disabled = true; state.button.textContent = 'Validando...';
    state.error.hidden = true; state.result.textContent = 'Revisando parametros y evidencia disponible...';
    const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 75000);
    try {
        const response = await fetch(state.panel.dataset.validationUrl, {
            method: 'POST', credentials: 'same-origin', cache: 'no-store', body: new FormData(form), signal: controller.signal,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token?.value || '' },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            let first;
            for (const name of Object.keys(data.errors || {})) {
                const field = markField(form, name, true);
                first ||= field;
            }
            first?.focus();
            throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo completar la validacion.');
        }
        if (fingerprint(form) !== snapshot) throw new Error('La solicitud cambio durante la revision. Vuelve a validar.');
        renderClinicalResult(state.result, data);
        for (const finding of data.result.findings || []) {
            if (finding.severity === 'information') continue;
            markField(form, finding.field, false, finding.severity === 'advisory');
        }
        state.requiresAuthorization = Boolean(data.requires_medical_authorization);
        state.requiresAcknowledgement = data.requires_acknowledgement !== false;
        if (state.ackText && data.result.status === 'advisory') state.ackText.textContent = 'Confirmo que revisé las advertencias.';
        if (state.requiresAuthorization && state.authorization) {
            state.authorization.disabled = false;
            state.authorizationFindings.replaceChildren();
            for (const finding of (data.result.findings || []).filter(finding => finding.severity === 'authorization')) {
                const item = document.createElement('li');
                const message = document.createElement('strong'); message.textContent = conciseClinicalMessage(finding);
                item.append(message);
                if (finding.calculation) {
                    const calculation = document.createElement('p'); calculation.textContent = finding.calculation;
                    item.append(calculation);
                }
                state.authorizationFindings.append(item);
            }
        }
        if (data.can_submit || (state.requiresAuthorization && state.authorization)) {
            state.token.value = data.review_id; state.snapshot = snapshot; state.expires = Date.parse(data.expires_at);
            state.ack.checked = !state.requiresAcknowledgement;
            state.panel.querySelector('[data-clinical-ack]').hidden = !state.requiresAcknowledgement || state.requiresAuthorization;
        }
        state.result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    } catch (failure) {
        state.result.replaceChildren(); state.error.hidden = false;
        state.error.textContent = failure.name === 'AbortError' ? 'La validacion tardo demasiado. No se habilito el envio; intenta nuevamente.' : failure.message;
    } finally {
        clearTimeout(timer); state.busy = false; state.button.disabled = false;
        state.button.textContent = state.token.value ? 'Enviar Solicitud' : 'Validar y Continuar';
    }
};

const initialize = () => document.querySelectorAll('form:has([data-clinical-review])').forEach(bind);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form:has([data-clinical-review])').forEach(form => {
        const state = bind(form); state.busy = false; state.reset(); state.button.disabled = false;
    });
});
