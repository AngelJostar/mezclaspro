<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController as NutritionController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologyController;
use App\Models\RequestQuotation;
use App\Services\OncologyMixtureDeliveryScheduleService;
use App\Services\QuotationPreparationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationPreparationController extends Controller
{
    public function create(Request $request, RequestQuotation $quotation, QuotationPreparationService $service)
    {
        abort_unless($quotation->canPrepareBy($request->user()), 403);
        if ($quotation->request_id) return $this->completed($quotation);
        $this->context($request, $quotation);
        $items = $service->items($quotation);
        $response = $quotation->category === 'nutricionales'
            ? app(NutritionController::class)->create($request) : app(OncologyController::class)->create($request);
        if ($response instanceof \Illuminate\Contracts\View\View) {
            $response->with(['preparationQuotation' => $quotation, 'quotedItems' => $items,
                'quotedMedications' => $service->medicationGroups($items),
                'quotedMixtures' => collect($service->groupItems($items))->map(fn ($group) => $service->medicationGroups($group->all()))->all(),
                'quotationDefaults' => $service->defaults($quotation, $items)]);
        }
        return $response;
    }

    public function store(Request $request, RequestQuotation $quotation, QuotationPreparationService $service)
    {
        abort_unless($quotation->canPrepareBy($request->user()), 403);
        $level = DB::transactionLevel();
        DB::beginTransaction();
        try {
            $quotation = RequestQuotation::lockForUpdate()->findOrFail($quotation->id);
            abort_unless($quotation->canPrepareBy($request->user()), 403);
            if ($quotation->request_id) {
                DB::commit();
                return $this->completed($quotation);
            }
            $this->context($request, $quotation);
            if ($quotation->category === 'nutricionales') {
                $response = app(NutritionController::class)->store($request);
            } else {
                $service->validateOncology($quotation, $request);
                $response = app(OncologyController::class)->store($request, app(OncologyMixtureDeliveryScheduleService::class));
            }
            if (!$quotation->refresh()->request_id) return $response;
            DB::commit();
            return $this->completed($quotation)->with('success', 'Solicitud enviada a preparacion desde '.$quotation->folio.'.');
        } finally {
            // The reused legacy controllers can return validation redirects inside a transaction.
            while (DB::transactionLevel() > $level) DB::rollBack();
        }
    }

    private function context(Request $request, RequestQuotation $quotation): void
    {
        if ($quotation->status !== 'autorizada') throw ValidationException::withMessages(['quotation' => 'Solo se pueden enviar cotizaciones autorizadas.']);
        if (!$quotation->hospital?->is_active || !$quotation->hospital?->laboratory_id) {
            throw ValidationException::withMessages(['quotation' => 'El hospital debe estar activo y tener una central de mezclas asignada.']);
        }
        // Trusted server-only context. Neither hospital nor prices are taken from submitted fields.
        $request->attributes->set('preparationQuotation', $quotation);
    }

    private function completed(RequestQuotation $quotation)
    {
        return redirect()->route('admin.solicitudes.index', ['tipo' => $quotation->category]);
    }
}
