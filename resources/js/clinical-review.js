import '../css/clinical-review.css';

export function renderClinicalResult(container, data) {
    container.replaceChildren();
    const result = data.result;
    const canViewInternal = data.can_view_internal === true;
    container.dataset.clinicalStatus = result.status;
    const paragraph = (text, className) => {
        const element = document.createElement('p'); element.textContent = text;
        if (className) element.className = className;
        return element;
    };
    const titles = { blocked: 'SOLICITUD RECHAZADA', needs_review: 'Revision incompleta', no_blockers: 'Sin bloqueos detectados en la revision', authorization_required: 'Advertencia: requiere autorizacion medica', advisory: 'Sugerencias no bloqueantes' };
    const compatibilityWarning = (result.findings || []).some(finding => finding.category === 'compatibility_limitation');
    const title = result.status === 'advisory' && compatibilityWarning
        ? 'Advertencia informativa: revisión parcial de compatibilidad'
        : (titles[result.status] || 'Revision pendiente');
    container.append(paragraph(title, 'clinical-status'), paragraph(result.summary));
    const list = document.createElement('ul');
    for (const finding of result.findings || []) {
        const item = document.createElement('li'); item.dataset.severity = finding.severity;
        const observation = finding.observation_type || ({ blocking: 'rechazo', warning: 'advertencia', authorization: 'advertencia', advisory: 'sugerencia' })[finding.severity];
        if (observation) item.append(paragraph(observation === 'rechazo' ? 'Rechazo:' : (observation === 'sugerencia' ? 'Sugerencia no bloqueante:' : (observation === 'advertencia informativa' ? 'Advertencia informativa:' : 'Advertencia:')), 'clinical-status'));
        item.append(paragraph(finding.message));
        if (finding.calculation) item.append(paragraph(finding.calculation));
        if (finding.suggestion) item.append(paragraph(`Sugerencia: ${finding.suggestion}`, 'clinical-suggestion'));
        if (canViewInternal && finding.source_ids?.length) item.append(paragraph(`Fuente: ${finding.source_ids.map(id => id === 'SYSTEM' ? 'Control documental y aritmetico de PROMESA' : id).join(', ')}`, 'clinical-notice'));
        list.append(item);
    }
    container.append(list);
    if (!canViewInternal) return;
    for (const [label, values] of [
        ['Calculos y supuestos', (result.calculations || []).map(c => `${c.formula} = ${c.result} ${c.unit}. ${c.assumptions}`)],
        ['Estado de la evidencia y del servicio', result.technical_issues || []],
        ['Fuentes utilizadas', (result.sources || []).map(s => `${s.id}: ${s.title}. ${s.reference}. ${s.reviewed ? 'Revisada' : 'Pendiente de revision'}. SHA-256: ${s.sha256}`)],
    ]) {
        if (!values.length) continue;
        const details = document.createElement('details'), summary = document.createElement('summary');
        summary.textContent = label; details.append(summary);
        values.forEach(value => details.append(paragraph(value)));
        container.append(details);
    }
    container.append(paragraph(result.notice || 'Requiere revision profesional.', 'clinical-notice'));
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
        authorization: panel.querySelector('[data-medical-authorization]'), requiresAuthorization: false, fieldError: false };
    const reset = () => {
        state.token.value = ''; state.snapshot = ''; state.ack.checked = false;
        if (state.ackText) state.ackText.textContent = 'He revisado las observaciones de la IA y confirmo los cambios que capture. Enviar la solicitud no autoriza su preparacion.';
        panel.querySelector('[data-clinical-ack]').hidden = true;
        state.requiresAuthorization = false;
        if (state.authorization) {
            state.authorization.hidden = true; state.authorization.disabled = true;
            state.authorization.querySelectorAll('input, textarea').forEach(field => {
                if (field.type === 'checkbox') field.checked = false; else field.value = '';
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
    state.reset = reset; states.set(form, state);
    return state;
}

window.validateClinicalRequest = async form => {
    const state = bind(form);
    if (!state || state.busy || !form.reportValidity()) return;
    const snapshot = fingerprint(form);
    if (state.token.value && state.snapshot === snapshot && state.expires > Date.now()) {
        if (!state.ack.checked) {
            state.error.textContent = 'Confirma que revisaste los hallazgos antes de enviar.'; state.error.hidden = false; state.ack.focus(); return;
        }
        state.busy = true; state.button.disabled = true; state.button.textContent = 'Enviando solicitud...';
        HTMLFormElement.prototype.submit.call(form);
        return;
    }
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
        if (state.ackText && data.result.status === 'advisory') {
            const compatibilityWarning = (data.result.findings || []).some(finding => finding.category === 'compatibility_limitation');
            state.ackText.textContent = compatibilityWarning
                ? 'Confirmo que revise la advertencia informativa de compatibilidad y acepto continuar bajo responsabilidad profesional con los datos capturados. Enviar la solicitud no autoriza su preparacion.'
                : 'He revisado las sugerencias no bloqueantes y acepto continuar bajo responsabilidad profesional con los valores capturados. Enviar la solicitud no autoriza su preparacion.';
        }
        if (state.requiresAuthorization && state.authorization) {
            state.authorization.disabled = false; state.authorization.hidden = false;
        }
        if (data.can_submit || (state.requiresAuthorization && state.authorization)) {
            state.token.value = data.review_id; state.snapshot = snapshot; state.expires = Date.parse(data.expires_at);
            state.panel.querySelector('[data-clinical-ack]').hidden = false;
        }
        (state.requiresAuthorization ? state.authorization : state.result).scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
