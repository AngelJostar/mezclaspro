<?php

namespace App\Services\Clinical;

use App\Models\ClinicalReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Services\ValidationRules\ClinicalRuleEvaluator;

class ClinicalReviewService
{
    public function __construct(
        private ClinicalPayload $payload,
        private ClinicalEvidence $evidence,
        private OpenAiClinicalAnalysis $openai,
        private ClinicalRuleEvaluator $rules,
    ) {}

    public function installed(): bool
    {
        return Schema::hasTable('clinical_reviews') && $this->evidence->agent() !== null;
    }

    public function fingerprint(array $data): string
    {
        foreach (['_token', 'clinical_review_token', 'clinical_acknowledged', 'medical_authorization'] as $key) unset($data[$key]);
        $sort = function ($value) use (&$sort) {
            if (!is_array($value)) return $value === null ? '' : (string) $value;
            ksort($value);
            return array_map($sort, $value);
        };
        return hash_hmac('sha256', json_encode($sort($data), JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function evaluate(Request $request, string $kind, array $case, string $purpose, ?int $target = null): ClinicalReview
    {
        $agent = $this->evidence->agent();
        $mode = $kind === 'nutricionales' ? ($case['mixtures'][0]['mode'] ?? '') : null;
        $sources = $this->evidence->sources($kind, $mode);
        // Track the full category to invalidate receipts if either population's library changes.
        $sourcesFingerprint = $this->evidence->fingerprint($kind, null, $agent);
        $issues = $this->evidence->limitations($kind, $sources, $mode);
        $ruleEvaluation = $kind === 'nutricionales'
            ? $this->rules->evaluate($case)
            : ['findings' => [], 'evaluations' => []];
        $analysisCase = $case;
        $analysisCase['deterministic_validation'] = $ruleEvaluation['evaluations'];
        $result = ['status' => 'needs_review', 'summary' => 'Revision pendiente del profesional responsable.', 'findings' => [], 'coverage' => [], 'model' => null];
        if (!$agent?->is_active) $issues[] = 'El agente de soporte clinico esta inactivo. Solicita su configuracion al superadministrador.';
        elseif (count($sources) > 20 || strlen(json_encode($sources)) > 250000) $issues[] = 'Las fuentes exceden el limite de revision. Reduce su alcance antes de continuar.';
        else {
            try { $result = $this->openai->analyze($analysisCase, $sources, $agent->instructions ?? ''); }
            catch (\RuntimeException $e) { $issues[] = $e->getMessage(); }
        }
        if (!hash_equals($sourcesFingerprint, $this->evidence->fingerprint($kind))) {
            $issues[] = 'Las fuentes o la configuracion cambiaron durante la revision. Vuelve a validar.';
        }
        $aiFindings = array_map(
            fn (array $finding) => $this->replaceInternalIdentifiers($finding, $case),
            $result['findings'] ?? []
        );
        $ruleEvaluation['findings'] = $this->enrichDeterministicFindings(
            $ruleEvaluation['findings'],
            $aiFindings
        );
        $result['findings'] = $this->withoutDeterministicDuplicates(
            $aiFindings,
            $ruleEvaluation['findings']
        );
        foreach ($case['local_blockers'] ?? [] as $item) $result['findings'][] = $item + ['severity' => 'blocking', 'source_ids' => ['SYSTEM']];
        foreach ($ruleEvaluation['findings'] as $item) $result['findings'][] = $item;
        $result['findings'] = array_map(
            fn (array $finding) => $this->replaceInternalIdentifiers($finding, $case),
            $result['findings']
        );
        $result['deterministic_validation'] = $ruleEvaluation['evaluations'];
        $result['audit_coverage'] = $result['coverage'];
        $hasSubstantiveFinding = collect($result['findings'])->contains(
            fn ($finding) => !in_array($finding['category'] ?? '', ['missing_clinical_context', 'information', 'internal_comment'], true)
        );
        $reviewedSourceIds = collect($sources)->filter(fn ($source) => $source['reviewed'] ?? false)->pluck('id');
        $optionalCompatibilityMissing = false;
        $optionalCompatibilitySourceIds = [];
        // The provider may interpret blank optional context as incomplete coverage. Structured required
        // fields are validated locally, so only context-dependent domains with reviewed citations can
        // be normalized, and never when a substantive finding or technical issue exists.
        if (!$issues && !empty($case['missing_context']) && !$hasSubstantiveFinding) {
            foreach ($result['coverage'] as &$coverage) {
                if (in_array($coverage['domain'] ?? '', ['completeness', 'compatibility_stability', 'clinical_risks'], true)
                    && ($coverage['state'] ?? '') === 'missing'
                    && !empty($coverage['source_ids'])
                    && collect($coverage['source_ids'])->every(fn ($id) => $reviewedSourceIds->contains($id))) {
                    if (($coverage['domain'] ?? '') === 'compatibility_stability') {
                        $optionalCompatibilityMissing = true;
                        $optionalCompatibilitySourceIds = $coverage['source_ids'];
                    }
                    $coverage['state'] = 'reviewed';
                }
            }
            unset($coverage);
        }
        if ($optionalCompatibilityMissing) {
            $result['findings'][] = [
                'field' => 'clinical_context[preparation_storage]',
                'severity' => 'advisory',
                'category' => 'compatibility_limitation',
                'message' => 'La compatibilidad y estabilidad no pudieron evaluarse completamente porque no se capturaron las condiciones opcionales de preparación, almacenamiento, diluyente o tiempo hasta la administración. Esto no constituye un rechazo de la formulación.',
                'calculation' => '',
                'suggestion' => 'Captura las condiciones de preparación y almacenamiento si deseas complementar la evaluación cualitativa; también puedes continuar después de confirmar la revisión profesional.',
                'source_ids' => $optionalCompatibilitySourceIds,
            ];
        }
        // Optional clinical context does not block a structurally complete mixture review.
        // Missing evidence, incomplete model responses and local checks still cannot be overridden.
        $complete = !$issues && count($result['coverage']) === 4
            && !collect($result['coverage'])->contains(fn ($c) => $c['state'] !== 'reviewed')
            && !collect($result['findings'])->contains(fn ($f) => ($f['category'] ?? '') !== 'missing_clinical_context'
                && !in_array($f['severity'], ['information', 'authorization', 'advisory'], true));
        $onlyOptionalContextIsMissing = !empty($case['missing_context'])
            && !collect($result['findings'])->contains(fn ($f) => !in_array($f['category'] ?? '', [
                'missing_clinical_context', 'compatibility_limitation', 'information', 'internal_comment',
            ], true));
        if ($complete && $onlyOptionalContextIsMissing && $result['status'] === 'needs_review') {
            $result['status'] = 'no_blockers';
        }
        $hasException = collect($result['findings'])->contains(fn ($f) => $f['severity'] === 'authorization');
        $hasAdvisory = collect($result['findings'])->contains(fn ($f) => $f['severity'] === 'advisory');
        $requiresAuthorization = $complete && $hasException && $result['status'] !== 'blocked';
        $canSubmit = $complete && !$hasException && ($result['status'] === 'no_blockers' || ($hasAdvisory && $result['status'] !== 'blocked'));
        if (!$canSubmit && $result['status'] === 'no_blockers') $result['status'] = 'needs_review';
        if (collect($result['findings'])->contains(fn ($f) => $f['severity'] === 'blocking')) $result['status'] = 'blocked';
        if ($requiresAuthorization) $result['status'] = 'authorization_required';
        elseif ($canSubmit && $hasAdvisory) $result['status'] = 'advisory';
        foreach ($result['findings'] as &$finding) {
            if ($finding['severity'] === 'blocking') $finding['observation_type'] = 'rechazo';
            elseif ($finding['severity'] === 'authorization') $finding['observation_type'] = 'advertencia';
            elseif (($finding['category'] ?? '') === 'compatibility_limitation') $finding['observation_type'] = 'advertencia informativa';
            elseif ($finding['severity'] === 'advisory') $finding['observation_type'] = 'sugerencia';
        }
        unset($finding);
        if ($result['status'] === 'needs_review'
            && !collect($result['findings'])->contains(fn ($finding) => $this->isParameterFinding($finding))) {
            $missingDomains = collect($result['coverage'])
                ->where('state', '!=', 'reviewed')
                ->pluck('domain')
                ->map(fn ($domain) => [
                    'completeness' => 'integridad de los datos',
                    'calculations' => 'cálculos',
                    'compatibility_stability' => 'compatibilidad y estabilidad',
                    'clinical_risks' => 'riesgos clínicos',
                ][$domain] ?? $domain)
                ->implode(', ');
            $reason = $issues[0] ?? ($missingDomains
                ? 'La revisión no cubrió completamente: '.$missingDomains.'.'
                : 'La respuesta de revisión no fue suficiente para emitir un resultado completo.');
            $result['findings'][] = [
                'field' => 'observaciones',
                'severity' => 'review',
                'category' => 'review_limitation',
                'message' => $reason,
                'calculation' => '',
                'suggestion' => 'Verifica la configuración y las fuentes indicadas, y vuelve a validar la solicitud.',
                'source_ids' => ['SYSTEM'],
                'observation_type' => 'advertencia',
            ];
        }
        // Decide submission before filtering comments; hidden notes cannot clear a clinical block.
        $result['audit_findings'] = $result['findings'];
        $result['audit_summary'] = $result['summary'];
        $result['findings'] = array_values(array_filter($result['findings'], function ($finding) use ($case) {
            if (!$this->isParameterFinding($finding)) return false;
            if (($finding['category'] ?? '') === 'missing_clinical_context'
                && preg_match('/^clinical_context\[(allergies|concomitant_medication|organ_function_labs|patient_factors|preparation_storage)\]$/', $finding['field'], $match)
                && empty($case['clinical_context'][$match[1]])) return false;
            return true;
        }));
        $result['summary'] = match ($result['status']) {
            'blocked' => 'Corrige los parametros senalados y vuelve a validar. No se permite enviar esta formulacion.',
            'authorization_required' => 'La mezcla requiere autorizacion del area medica para enviarse con parametros fuera de los limites clinicos o quimicos recomendados. Registra los datos del medico y la autorizacion antes del envio.',
            'advisory' => $optionalCompatibilityMissing
                ? 'La formulacion no presenta rechazos deterministas. La compatibilidad y estabilidad quedaron parcialmente evaluadas por falta de datos opcionales; puedes completar la informacion o continuar despues de confirmar la revision profesional.'
                : 'La revision encontro sugerencias no bloqueantes. Puedes corregirlas y volver a validar o continuar bajo responsabilidad profesional despues de confirmar que las revisaste.',
            'no_blockers' => 'La revision no detecto bloqueos. Confirma la revision antes de enviar; la preparacion requiere su aprobacion habitual.',
            default => 'La revision no esta completa. No se habilito el envio ni se considera validada la seguridad de la mezcla.',
        };
        $result['technical_issues'] = $issues;
        $result['requires_medical_authorization'] = $requiresAuthorization && $purpose === 'submission';
        $result['requires_risk_acknowledgement'] = $canSubmit && $hasAdvisory && $purpose === 'submission';
        $result['sources'] = array_map(fn ($s) => array_diff_key($s, array_flip(['content'])), $sources);
        if ($kind === 'nutricionales') $result['manual_selection'] = NutritionManual::selection($mode);
        $result['calculations'] = $case['calculations'];
        $result['limitations'] = [];
        $result['notice'] = 'Soporte de IA. No es una autorizacion de preparacion ni sustituye la revision del profesional responsable.';
        return ClinicalReview::create(['id' => (string) Str::uuid(), 'user_id' => $request->user()->id,
            'kind' => $kind, 'purpose' => $purpose, 'target_id' => $target,
            'payload_hash' => $this->fingerprint($request->all()), 'context_hash' => $this->fingerprint($case),
            'sources_hash' => $sourcesFingerprint, 'session_hash' => $this->sessionHash($request),
            'clinical_context' => $case['clinical_context'] ?? [],
            'can_submit' => $canSubmit && $purpose === 'submission', 'result' => $result, 'expires_at' => now()->addMinutes(15)]);
    }

    private function withoutDeterministicDuplicates(array $findings, array $deterministicFindings): array
    {
        return array_values(array_filter($findings, function (array $finding) use ($deterministicFindings): bool {
            foreach ($deterministicFindings as $deterministic) {
                if ($this->duplicatesDeterministicFinding($finding, $deterministic)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function enrichDeterministicFindings(array $deterministicFindings, array $aiFindings): array
    {
        foreach ($deterministicFindings as &$deterministic) {
            if (trim((string) ($deterministic['suggestion'] ?? '')) !== '') continue;

            foreach ($aiFindings as $finding) {
                if (!$this->duplicatesDeterministicFinding($finding, $deterministic)) continue;

                $suggestion = trim((string) ($finding['suggestion'] ?? ''));
                if ($suggestion !== '') {
                    $deterministic['suggestion'] = $suggestion;
                    break;
                }
            }
        }
        unset($deterministic);

        return $deterministicFindings;
    }

    private function duplicatesDeterministicFinding(array $finding, array $deterministic): bool
    {
        $field = (string) ($finding['field'] ?? '');
        $text = Str::lower(implode(' ', array_filter([
            $finding['message'] ?? null,
            $finding['calculation'] ?? null,
        ])));
        $ruleCode = Str::lower((string) ($deterministic['rule_code'] ?? ''));
        $ruleName = Str::lower(Str::before((string) ($deterministic['message'] ?? ''), ':'));
        $sameField = $field !== '' && $field === (string) ($deterministic['field'] ?? '');
        $plainText = Str::lower(Str::ascii($text));
        $duplicatesCompositionCatalog = str_contains($ruleCode, '.comp.')
            && str_contains($plainText, 'catalog')
            && (str_contains($plainText, 'composicion') || str_contains($plainText, 'combinacion'));

        return ($ruleCode !== '' && str_contains($text, $ruleCode))
            || ($sameField && $ruleName !== '' && str_contains($text, $ruleName))
            || $duplicatesCompositionCatalog;
    }

    private function replaceInternalIdentifiers(array $finding, array $case): array
    {
        $aliases = [];
        foreach ($case['mixtures'] ?? [] as $mixture) {
            foreach ($mixture['components'] ?? [] as $component) {
                $field = trim((string) ($component['field'] ?? ''));
                if ($field === '') continue;

                $group = (string) ($component['composition_group'] ?? '');
                $label = [
                    'amino_acids' => 'aminoácidos',
                    'dextrose' => 'dextrosa',
                    'lipids' => 'lípidos',
                    'electrolytes' => 'electrolitos',
                    'trace_elements' => 'elementos traza',
                    'vitamins' => 'vitaminas',
                    'water' => 'agua inyectable',
                    'saline' => 'solución salina',
                    'medications' => 'medicamento',
                    'additives' => 'aditivo',
                ][$group] ?? trim((string) ($component['medicine'] ?? 'componente'));
                $aliases[$field.'/Kg'] = $label;
                $aliases[$field.'/kg'] = $label;
                $aliases[$field] = $label;
            }
        }

        uksort($aliases, fn (string $left, string $right) => strlen($right) <=> strlen($left));
        foreach (['message', 'calculation', 'suggestion'] as $key) {
            if (!isset($finding[$key]) || !is_string($finding[$key])) continue;
            $finding[$key] = str_ireplace(array_keys($aliases), array_values($aliases), $finding[$key]);
            $finding[$key] = preg_replace('/\b(?:i|c)_\d+(?:_[A-Za-z]+)?(?:\/Kg)?\b/i', 'componente correspondiente', $finding[$key]);
        }

        return $finding;
    }

    public function response(ClinicalReview $review, User $viewer): array
    {
        $result = $this->resultForDisplay($review);
        $canViewInternal = !$viewer->hasAnyRole(['Cliente', 'Institucion']);
        if (!$canViewInternal) {
            // Only actionable review data leaves the server for hospital users, including cached reviews.
            $result = Arr::only($result, ['status', 'summary', 'findings', 'requires_medical_authorization', 'requires_risk_acknowledgement']);
            $result['findings'] = array_map(fn ($finding) => Arr::only($finding,
                ['field', 'severity', 'observation_type', 'message', 'calculation', 'suggestion']), $result['findings']);
        }
        return ['review_id' => $review->id, 'can_submit' => $review->can_submit, 'expires_at' => $review->expires_at->toIso8601String(),
            'can_view_internal' => $canViewInternal,
            'requires_medical_authorization' => (bool) ($result['requires_medical_authorization'] ?? false),
            'requires_risk_acknowledgement' => (bool) ($result['requires_risk_acknowledgement'] ?? false), 'result' => $result];
    }

    public function resultForDisplay(ClinicalReview $review): array
    {
        $result = $review->result;
        unset($result['audit_findings'], $result['audit_summary'], $result['audit_coverage']);
        $result['findings'] = array_values(array_filter($result['findings'], fn ($finding) => $this->isParameterFinding($finding)));
        $sources = collect($result['sources'] ?? [])->keyBy('id');
        // Hide old generic fallbacks without rewriting the encrypted review history or its decision.
        foreach ($result['findings'] as &$finding) {
            $finding['message'] = preg_replace('/^(?:Hallazgo pendiente de evidencia revisada:\s*)+/i', '', $finding['message']);
            $ids = $finding['source_ids'] ?? [];
            if ($ids !== ['SYSTEM'] && (!$ids || collect($ids)->contains(fn ($id) => !($sources->get($id)['reviewed'] ?? false)))) {
                $finding['suggestion'] = '';
            }
        }
        unset($finding);
        return $result;
    }

    private function isParameterFinding(array $finding): bool
    {
        return !in_array($finding['category'] ?? '', ['missing_clinical_context', 'information', 'internal_comment'], true);
    }

    public function requireSubmission(Request $request, string $kind): void
    {
        $this->payload->validatePatient($kind, $request->all());
        if (!$this->installed()) return;
        if ((int) $request->input('clinical_quotation_id') !== (int) $request->attributes->get('preparationQuotation')?->id) {
            throw ValidationException::withMessages(['clinical_review' => 'La validacion no corresponde al origen de esta solicitud.']);
        }
        $review = ClinicalReview::whereKey((string) $request->input('clinical_review_token'))->where('user_id', $request->user()->id)->first();
        $requiresAuthorization = (bool) ($review?->result['requires_medical_authorization'] ?? false);
        if (!$review || $review->purpose !== 'submission' || (!$review->can_submit && !$requiresAuthorization) || $review->kind !== $kind
            || $review->used_at || $review->expires_at->isPast() || !$request->boolean('clinical_acknowledged')
            || !hash_equals($review->session_hash, $this->sessionHash($request))
            || !hash_equals($review->payload_hash, $this->fingerprint($request->all()))
            || !hash_equals($review->sources_hash, $this->evidence->fingerprint($kind))
            || !hash_equals($review->context_hash, $this->fingerprint($this->payload->normalize($kind, $request->all())))) {
            throw ValidationException::withMessages(['clinical_review' => 'Valida nuevamente la solicitud y revisa los hallazgos antes de enviarla. No se registro la mezcla.']);
        }
        if ($requiresAuthorization) {
            $data = Validator::make($request->all(), [
                'medical_authorization.doctor_name' => 'required|string|min:3|max:255',
                'medical_authorization.doctor_license' => 'required|string|min:3|max:50',
            ], ['required' => 'Captura :attribute para registrar la autorizacion medica.'], [
                'medical_authorization.doctor_name' => 'nombre del medico', 'medical_authorization.doctor_license' => 'cedula profesional',
            ])->validate()['medical_authorization'];
            $request->attributes->set('medicalAuthorization', $data + ['recorded_by' => $request->user()->id,
                'recorded_at' => now()->toIso8601String(), 'review_id' => $review->id, 'capture_version' => 2,
                'findings' => array_values(array_filter($review->result['findings'], fn ($f) => $f['severity'] === 'authorization'))]);
        }
        $request->attributes->set('clinicalReview', $review);
    }

    public function consume(Request $request, string $recordType, int $recordId): void
    {
        $review = $request->attributes->get('clinicalReview');
        if (!$review) return;
        if ($review->expires_at->isPast() || !hash_equals($review->sources_hash, $this->evidence->fingerprint($review->kind))) {
            throw ValidationException::withMessages(['clinical_review' => 'La revision caduco o cambiaron las fuentes. Valida nuevamente.']);
        }
        // Called inside the same transaction as the request; rollback preserves the receipt on failed saves.
        $values = ['used_at' => now(), 'record_type' => $recordType, 'record_id' => $recordId];
        if ($review->result['requires_medical_authorization'] ?? false) {
            $authorization = $request->attributes->get('medicalAuthorization');
            if (!$authorization || $authorization['review_id'] !== $review->id) {
                throw ValidationException::withMessages(['clinical_review' => 'Falta registrar la autorizacion medica de esta revision.']);
            }
            $review->medical_authorization = $authorization;
            $values['medical_authorization'] = $review->getAttributes()['medical_authorization'];
        }
        $updated = ClinicalReview::whereKey($review->id)->whereNull('used_at')->update($values);
        if (!$updated) throw ValidationException::withMessages(['clinical_review' => 'Esta validacion ya fue utilizada.']);
    }

    private function sessionHash(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), config('app.key'));
    }
}
