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

class ClinicalReviewService
{
    public function __construct(private ClinicalPayload $payload, private ClinicalEvidence $evidence, private OpenAiClinicalAnalysis $openai) {}

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
        $result = ['status' => 'needs_review', 'summary' => 'Revision pendiente del profesional responsable.', 'findings' => [], 'coverage' => [], 'model' => null];
        if (!$agent?->is_active) $issues[] = 'El agente de soporte clinico esta inactivo. Solicita su configuracion al superadministrador.';
        elseif (count($sources) > 20 || strlen(json_encode($sources)) > 250000) $issues[] = 'Las fuentes exceden el limite de revision. Reduce su alcance antes de continuar.';
        else {
            try { $result = $this->openai->analyze($case, $sources, $agent->instructions ?? ''); }
            catch (\RuntimeException $e) { $issues[] = $e->getMessage(); }
        }
        if (!hash_equals($sourcesFingerprint, $this->evidence->fingerprint($kind))) {
            $issues[] = 'Las fuentes o la configuracion cambiaron durante la revision. Vuelve a validar.';
        }
        foreach ($case['local_blockers'] ?? [] as $item) $result['findings'][] = $item + ['severity' => 'blocking', 'source_ids' => ['SYSTEM']];
        // Missing evidence, incomplete responses and local checks cannot be overridden by the model.
        $complete = !$issues && empty($case['missing_context']) && count($result['coverage']) === 4
            && !collect($result['coverage'])->contains(fn ($c) => $c['state'] !== 'reviewed')
            && !collect($result['findings'])->contains(fn ($f) => !in_array($f['severity'], ['information', 'authorization', 'advisory'], true));
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
            elseif ($finding['severity'] === 'advisory') $finding['observation_type'] = 'sugerencia';
        }
        unset($finding);
        // Decide submission before filtering comments; hidden notes cannot clear a clinical block.
        $result['audit_findings'] = $result['findings'];
        $result['audit_summary'] = $result['summary'];
        $result['findings'] = array_values(array_filter($result['findings'], function ($finding) use ($case) {
            if (!$this->isParameterFinding($finding)) return false;
            if (preg_match('/^clinical_context\[(allergies|concomitant_medication|organ_function_labs|patient_factors)\]$/', $finding['field'], $match)
                && empty($case['clinical_context'][$match[1]])) return false;
            return true;
        }));
        $result['summary'] = match ($result['status']) {
            'blocked' => 'Corrige los parametros senalados y vuelve a validar. No se permite enviar esta formulacion.',
            'authorization_required' => 'La mezcla requiere autorizacion del area medica para enviarse con parametros fuera de los limites clinicos o quimicos recomendados. Registra los datos del medico y la autorizacion antes del envio.',
            'advisory' => 'La revision encontro sugerencias no bloqueantes. Puedes corregirlas y volver a validar o continuar bajo responsabilidad profesional despues de confirmar que las revisaste.',
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
        unset($result['audit_findings'], $result['audit_summary']);
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
