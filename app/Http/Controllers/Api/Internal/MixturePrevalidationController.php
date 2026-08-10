<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\PrevalidateMixtureRequest;
use App\Models\Hospital;
use App\Services\Integrations\DrSam\MixturePrevalidationService;
use Illuminate\Http\JsonResponse;

class MixturePrevalidationController extends Controller
{
    public function __invoke(PrevalidateMixtureRequest $request, MixturePrevalidationService $service): JsonResponse
    {
        $payload = $request->validated();
        $hospital = Hospital::query()
            ->with(['nutriMedicineList', 'oncoMedicineList'])
            ->where('external_code', $payload['medical_unit_code'])
            ->where('is_active', true)
            ->firstOrFail();

        $result = $service->validate($hospital, $payload);

        return response()->json([
            'data' => $result,
            'meta' => ['mutated_inventory' => false, 'created_request' => false],
        ]);
    }
}
