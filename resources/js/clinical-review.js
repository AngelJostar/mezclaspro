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

const paragraph = (text, className) => {
    const element = document.createElement('p');
    element.textContent = text;
    if (className) element.className = className;
    return element;
};

export function renderClinicalResult(container, data) {
    container.replaceChildren();
    const result = data.result;
    container.dataset.clinicalStatus = result.status;
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
        if (String(finding.rule_code || '').trim()) {
            item.append(paragraph(`Regla que falló: ${String(finding.rule_code).trim()}`, 'clinical-rule-code'));
        }
        if (String(finding.composition_code || '').trim()) {
            item.append(paragraph(`Combinación detectada: ${String(finding.composition_code).trim()}`, 'clinical-composition-code'));
        }
        item.append(paragraph(conciseClinicalMessage(finding)));
        if (finding.calculation) item.append(paragraph(finding.calculation));
        if (['blocking', 'authorization', 'review'].includes(finding.severity) && String(finding.suggestion || '').trim()) {
            item.append(paragraph(`Sugerencia: ${String(finding.suggestion).trim()}`, 'clinical-suggestion'));
        }
        list.append(item);
    }
    container.append(list);
}

const states = new WeakMap();
const fingerprint = form => JSON.stringify([...new FormData(form)].filter(([key]) => !['_token', 'clinical_review_token', 'clinical_acknowledged'].includes(key) && !key.startsWith('medical_authorization[')));

const cleanEvidenceLabel = element => {
    if (!element) return '';
    const clone = element.cloneNode(true);
    clone.querySelectorAll('input, select, textarea, option, svg, button, small, p').forEach(child => child.remove());
    return clone.textContent.replace(/\s+/g, ' ').replace(/[\s:*]+$/g, '').trim();
};

const evidenceLabel = field => {
    const component = field.closest('[data-nutrition-component]');
    if (component) return cleanEvidenceLabel(component.querySelector('label')) || field.name;

    if (field.name.startsWith('nutrition_diluents[')) {
        const card = field.closest('.rounded-md');
        const product = cleanEvidenceLabel(card?.querySelector('h3'));
        const property = cleanEvidenceLabel(field.closest('label'));
        return [product, property].filter(Boolean).join(' · ') || field.name;
    }

    const explicit = field.id ? document.querySelector(`label[for="${CSS.escape(field.id)}"]`) : null;
    const nearby = explicit || field.closest('label') || field.closest('.mb-4')?.querySelector('label');
    return field.getAttribute('aria-label') || cleanEvidenceLabel(nearby)
        || field.name.replace(/\[|\]|_/g, ' ').replace(/\s+/g, ' ').trim();
};

const evidenceSection = field => {
    const component = field.closest('[data-nutrition-component]');
    if (component) {
        return ({ 1: 'Macronutrientes', 2: 'Macronutrientes', 3: 'Macronutrientes', 4: 'Electrolitos', 5: 'Aditivos', 8: 'Macronutrientes' })[Number(component.dataset.categoryId)] || 'Componentes';
    }
    if (field.name.startsWith('nutrition_diluents[')) return 'Diluyentes o vehículos';
    if (field.name.startsWith('clinical_context[')) return 'Datos para revisión clínica';
    if (field.name === 'observaciones') return 'Observaciones';
    return 'Datos generales';
};

const evidenceValue = field => {
    if (field instanceof HTMLSelectElement) return field.selectedOptions[0]?.textContent?.trim() || field.value;
    if (field.type === 'checkbox' || field.type === 'radio') return field.checked ? 'Sí' : '';
    return String(field.value || '').trim();
};

const collectEvidenceFields = form => {
    const excluded = new Set(['_token', 'clinical_review_token', 'clinical_acknowledged', 'request_popup']);
    return [...form.elements].flatMap(field => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return [];
        if (!field.name || field.type === 'hidden' || field.disabled || excluded.has(field.name) || field.name.startsWith('medical_authorization[')) return [];
        const value = evidenceValue(field);
        if (value === '') return [];
        if ((/^i_\d+/.test(field.name) || field.name.startsWith('nutrition_diluents[')) && Number.parseFloat(value) === 0) return [];
        return [{ section: evidenceSection(field), label: evidenceLabel(field), value }];
    });
};

const collectEvidenceFindings = form => {
    const findings = [...form.querySelectorAll('[data-clinical-result] li')].map(item => {
        const paragraphs = [...item.querySelectorAll(':scope > p')];
        const status = item.querySelector('.clinical-status')?.textContent?.trim() || 'Hallazgo';
        const rule = item.querySelector('.clinical-rule-code')?.textContent?.trim() || '';
        const composition = item.querySelector('.clinical-composition-code')?.textContent?.trim() || '';
        const suggestion = item.querySelector('.clinical-suggestion')?.textContent?.replace(/^Sugerencia:\s*/i, '').trim() || '';
        const body = paragraphs.filter(p => !p.matches('.clinical-status, .clinical-rule-code, .clinical-composition-code, .clinical-suggestion'));
        return {
            severity: item.dataset.severity || '', title: status,
            rule_code: rule.replace(/^Regla que falló:\s*/i, ''),
            composition_code: composition.replace(/^Combinación detectada:\s*/i, ''),
            message: body[0]?.textContent?.trim() || item.textContent.trim(),
            calculation: body[1]?.textContent?.trim() || '', suggestion,
        };
    });
    const volumeError = form.querySelector('#volume-components-error:not(.hidden)');
    if (volumeError?.textContent.trim()) {
        findings.unshift({ severity: 'blocking', title: 'Error:', rule_code: '', composition_code: '', message: volumeError.textContent.trim(), calculation: '', suggestion: '' });
    }
    return findings;
};

const initializeEvidenceExports = () => document.querySelectorAll('[data-export-validation-evidence]').forEach(button => {
    if (button.dataset.bound === 'true') return;
    button.dataset.bound = 'true';
    button.addEventListener('click', async () => {
        const form = document.getElementById(button.dataset.formId || '');
        if (!form || button.disabled) return;
        const original = button.innerHTML;
        button.disabled = true;
        button.querySelector('span').textContent = 'Generando PDF…';
        try {
            const response = await fetch(button.dataset.exportUrl, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/json', Accept: 'application/pdf', 'X-CSRF-TOKEN': form.elements._token?.value || '' },
                body: JSON.stringify({ fields: collectEvidenceFields(form), findings: collectEvidenceFindings(form) }),
            });
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo generar el PDF de evidencia.');
            }
            const blob = await response.blob();
            const disposition = response.headers.get('Content-Disposition') || '';
            const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] || 'evidencia-validacion-nutricional.pdf';
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url; link.download = filename; document.body.append(link); link.click(); link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (failure) {
            window.alert(failure.message);
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    });
});

function markField(form, name, required = false, advisory = false, live = false) {
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
    if (live) field.classList.add('clinical-live-field');
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
        rulesUrl: panel.dataset.rulesValidationUrl || '', liveStatus: panel.querySelector('[data-clinical-live-status]'),
        liveTimer: null, liveController: null, liveSnapshot: '', requiresAuthorization: false,
        requiresAcknowledgement: true, fieldError: false };

    const clearLiveMarks = () => {
        form.querySelectorAll('.clinical-live-field').forEach(field => {
            field.classList.remove('clinical-live-field', 'clinical-field-error', 'clinical-field-advisory');
            if (!field.classList.contains('clinical-field-required-error')) field.removeAttribute('aria-invalid');
        });
    };
    const relevantForLiveRules = field => {
        const name = String(field?.name || '');
        return ['peso', 'fecha_nacimiento', 'sexo', 'npt', 'volumen_total', 'via_administracion', 'tiempo_infusion_min', 'velocidad_infusion'].includes(name)
            || /^i_\d+/.test(name)
            || /^nutrition_diluents\[\d+\]\[volume_ml\]$/.test(name);
    };
    const hasLiveMinimum = () => {
        const positiveComponent = [...form.querySelectorAll('input[name^="i_"], input[name^="nutrition_diluents"][name$="[volume_ml]"]')]
            .some(field => !field.disabled && Number.parseFloat(field.value || '0') > 0);
        return Number.parseFloat(form.elements.peso?.value || '0') > 0
            && Boolean(form.elements.fecha_nacimiento?.value)
            && ['ADULT', 'INF'].includes(form.elements.npt?.value)
            && Number.parseFloat(form.elements.volumen_total?.value || '0') > 0
            && Boolean(form.elements.via_administracion?.value)
            && positiveComponent;
    };
    const setLiveStatus = (message = '', status = '') => {
        if (!state.liveStatus) return;
        state.liveStatus.textContent = message;
        state.liveStatus.dataset.state = status;
        state.liveStatus.hidden = message === '';
    };
    const runLiveRules = async () => {
        if (!state.rulesUrl || state.busy || !hasLiveMinimum()) {
            setLiveStatus();
            return;
        }
        const currentSnapshot = fingerprint(form);
        if (currentSnapshot === state.liveSnapshot) return;
        state.liveController?.abort();
        state.liveController = new AbortController();
        setLiveStatus('Actualizando reglas matemáticas…', 'checking');
        try {
            const response = await fetch(state.rulesUrl, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store', body: new FormData(form),
                signal: state.liveController.signal,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token?.value || '' },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (response.status === 422) { setLiveStatus(); return; }
                throw new Error(data.message || 'No fue posible actualizar las reglas.');
            }
            if (fingerprint(form) !== currentSnapshot) return;
            state.liveSnapshot = currentSnapshot;
            clearLiveMarks();
            renderClinicalResult(state.result, {
                result: {
                    status: data.status === 'passed' ? 'no_blockers' : (data.status === 'blocked' ? 'blocked' : 'authorization_required'),
                    findings: data.findings || [],
                },
            });
            for (const finding of data.findings || []) {
                if (finding.severity === 'information') continue;
                markField(form, finding.field, false, finding.severity === 'advisory', true);
            }
            setLiveStatus('Reglas matemáticas actualizadas.', 'updated');
        } catch (failure) {
            if (failure.name !== 'AbortError') setLiveStatus(failure.message, 'error');
        }
    };
    const scheduleLiveRules = field => {
        if (!state.rulesUrl || !relevantForLiveRules(field)) return;
        clearTimeout(state.liveTimer);
        state.liveController?.abort();
        state.liveSnapshot = '';
        setLiveStatus(hasLiveMinimum() ? 'Esperando cambios…' : '', 'waiting');
        state.liveTimer = setTimeout(runLiveRules, 800);
    };
    state.cancelLive = () => {
        clearTimeout(state.liveTimer);
        state.liveController?.abort();
        setLiveStatus();
    };
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
            el.classList.remove('clinical-field-error', 'clinical-field-advisory', 'clinical-live-field'); el.removeAttribute('aria-invalid');
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
        scheduleLiveRules(field);
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
    state.cancelLive?.();
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
                if (String(finding.rule_code || '').trim()) {
                    item.append(paragraph(`Regla que falló: ${String(finding.rule_code).trim()}`, 'clinical-rule-code'));
                }
                if (String(finding.composition_code || '').trim()) {
                    item.append(paragraph(`Combinación detectada: ${String(finding.composition_code).trim()}`, 'clinical-composition-code'));
                }
                const message = document.createElement('strong'); message.textContent = conciseClinicalMessage(finding);
                item.append(message);
                if (finding.calculation) {
                    const calculation = document.createElement('p'); calculation.textContent = finding.calculation;
                    item.append(calculation);
                }
                if (String(finding.suggestion || '').trim()) {
                    const suggestion = document.createElement('p');
                    suggestion.className = 'clinical-suggestion';
                    suggestion.textContent = `Sugerencia: ${String(finding.suggestion).trim()}`;
                    item.append(suggestion);
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
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => { initialize(); initializeEvidenceExports(); }, { once: true });
else { initialize(); initializeEvidenceExports(); }
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form:has([data-clinical-review])').forEach(form => {
        const state = bind(form); state.busy = false; state.reset(); state.button.disabled = false;
    });
});
