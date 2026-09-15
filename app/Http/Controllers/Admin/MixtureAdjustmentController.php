<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController;
use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Models\MixtureAdjustment;
use App\Models\Nutricionales\Solicitud;
use App\Services\MixtureAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MixtureAdjustmentController extends Controller
{
    public function show(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        $target = $adjustment->target();
        abort_unless($service->canView($request->user(), $target), 403);
        $isHospital = $request->user()->hasAnyRole(['Cliente', 'Institucion']);
        abort_if($isHospital && (int) $request->user()->hospital_id !== (int) $adjustment->hospital_id, 403);

        return view('admin.solicitudes.adjustment', compact('adjustment', 'target', 'isHospital'));
    }

    public function hospitalAuthorize(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        $request->validate(['consent' => 'accepted', 'hospital_response' => 'nullable|string|max:2000']);
        $service->authorize($adjustment, $request->user(), $request->input('hospital_response'));

        return $this->completed($request, $adjustment, 'Ajuste autorizado. Pendiente de aprobacion final de la central.');
    }

    public function cancel(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        $service->cancel($adjustment, $request->user());

        return $this->completed($request, $adjustment, 'Solicitud de ajuste cancelada.');
    }

    public function decline(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        $request->validate(['hospital_response' => 'required|string|min:5|max:2000']);
        $service->authorize($adjustment, $request->user(), $request->input('hospital_response'), false);
        return $this->completed($request, $adjustment, 'Respuesta enviada a la central. Ajuste no autorizado.');
    }

    public function reject(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        $request->validate(['central_response' => 'required|string|min:5|max:2000']);
        $service->reject($adjustment, $request->user(), $request->input('central_response'));
        return $this->completed($request, $adjustment, 'Mezcla rechazada.');
    }

    public function approve(Request $request, MixtureAdjustment $adjustment, MixtureAdjustmentService $service)
    {
        return DB::transaction(function () use ($request, $adjustment, $service) {
            $target = $adjustment->target();
            $target = $target->newQuery()->lockForUpdate()->findOrFail($target->id);
            $current = MixtureAdjustment::lockForUpdate()->findOrFail($adjustment->id);
            $service->assertCentral($request->user(), $target);
            abort_unless($current->status === 'authorized' && (int) $target->adjustment_id === (int) $current->id, 409);

            // Ignore posted clinical values. Apply only the hospital-authorized proposal.
            $approval = $request->duplicate(null, array_merge($current->proposal, [
                'accion' => 'aprobar', 'return_to' => route('admin.solicitudes.index'),
            ]));
            $approval->attributes->set('authorized_adjustment_id', $current->id);
            $service->assertWritable($target, $approval);
            $response = $target instanceof Solicitud
                ? app(SolicitudController::class)->update($approval, $target)
                : app(MezclaController::class)->update($approval, $target->id);

            return $current->fresh()->status === 'approved'
                ? $this->completed($request, $current, 'Mezcla aprobada con ajuste.')
                : $response;
        });
    }

    private function completed(Request $request, MixtureAdjustment $adjustment, string $message)
    {
        return redirect()->route('admin.solicitudes.ajustes.show', [
            'adjustment' => $adjustment, 'approval_popup' => $request->boolean('approval_popup') ? 1 : null,
        ])->with('success', $message)
            ->with('approval_popup_done', $request->boolean('approval_popup'))
            ->with('approval_popup_return_to', route('admin.solicitudes.index'));
    }
}
