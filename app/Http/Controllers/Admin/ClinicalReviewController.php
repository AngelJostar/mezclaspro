<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalReview;
use App\Models\RequestQuotation;
use App\Services\Clinical\ClinicalEvidence;
use App\Services\Clinical\ClinicalPayload;
use App\Services\Clinical\ClinicalReviewService;
use App\Services\MixtureMessagingService;
use App\Services\ValidationRules\ClinicalRuleEvaluator;
use Illuminate\Http\Request;

class ClinicalReviewController extends Controller
{
    public function validateRules(Request $request, string $kind, ClinicalPayload $payload, ClinicalRuleEvaluator $rules)
    {
        abort_unless($kind === 'nutricionales', 404);
        abort_unless($request->user()->can('nutricionales_solicitudes_update'), 403);
        $data = $payload->withNutritionUnits($request->all());
        $payload->validatePatient($kind, $data);
        $result = $rules->evaluate($payload->normalize($kind, $data));
        $blocking = collect($result['findings'])->where('severity', 'blocking')->count();

        return response()->json([
            'status' => $blocking > 0 ? 'blocked' : (count($result['findings']) > 0 ? 'warnings' : 'passed'),
            'blocking_count' => $blocking,
            'findings' => $result['findings'],
            'evaluations' => $result['evaluations'],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function validateRequest(Request $request, string $kind, ClinicalPayload $payload, ClinicalReviewService $service)
    {
        abort_unless(in_array($kind, ['nutricionales', 'oncologicos', 'antibioticos']), 404);
        abort_if($request->user()->is_active === false, 403);
        $quotationId = $request->validate(['clinical_quotation_id' => 'nullable|integer|min:1'])['clinical_quotation_id'] ?? null;
        if ($quotationId) {
            $quotation = RequestQuotation::findOrFail($quotationId);
            abort_unless($quotation->category === $kind && $quotation->canStartPreparationBy($request->user()), 403);
        } else {
            abort_unless($request->user()->can(($kind === 'nutricionales' ? 'nutricionales' : 'oncologicos').'_solicitudes_store'), 403);
        }
        abort_unless($service->installed(), 503, 'El soporte clinico requiere instalacion y configuracion.');
        $review = $service->evaluate($request, $kind, $payload->normalize($kind, $request->all()), 'submission');
        return response()->json($service->response($review, $request->user()))->header('Cache-Control', 'no-store, private');
    }

    public function conversation(Request $request, string $kind, int $target, MixtureMessagingService $messages,
        ClinicalPayload $payload, ClinicalReviewService $service, ClinicalEvidence $evidence)
    {
        $record = $messages->resolve($request->user(), $kind, $target);
        abort_unless($service->installed(), 503, 'El soporte clinico requiere instalacion y configuracion.');
        $case = $payload->fromTarget($record);
        $cached = ClinicalReview::where('user_id', $request->user()->id)->where('kind', $kind)->where('target_id', $target)
            ->where('purpose', 'conversation')->where('context_hash', $service->fingerprint($case))
            ->where('sources_hash', $evidence->fingerprint($kind))->where('expires_at', '>', now())->latest()->first();
        $review = $cached ?: $service->evaluate($request, $kind, $case, 'conversation', $target);
        return response()->json($service->response($review, $request->user()))->header('Cache-Control', 'no-store, private');
    }
}
