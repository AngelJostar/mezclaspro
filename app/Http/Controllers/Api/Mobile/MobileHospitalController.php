<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Solicitud as NutritionRequest;
use App\Models\Oncologicos\SolicitudOnco;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHospitalController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('hospital:id,name,short_name,is_active,access_is_active');

        abort_unless($this->isHospitalUser($user), 403, 'Acceso hospitalario requerido.');

        $hospitalId = (int) $user->hospital_id;
        $oncoPending = SolicitudOnco::query()
            ->where('hospital_id', $hospitalId)
            ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos'])
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', 'pendiente');
            })
            ->count();

        $nutritionPending = NutritionRequest::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', 'pendiente');
            })
            ->count();

        return response()->json([
            'hospital' => [
                'id' => $user->hospital->id,
                'name' => $user->hospital->name,
                'short_name' => $user->hospital->short_name,
            ],
            'requests' => [
                'oncology_pending' => $oncoPending,
                'nutrition_pending' => $nutritionPending,
                'pending_total' => $oncoPending + $nutritionPending,
            ],
        ]);
    }

    private function isHospitalUser(User $user): bool
    {
        return (bool) $user->hospital_id
            && $user->hasAnyRole(['Cliente', 'Institucion'])
            && $user->hospital
            && $user->hospital->is_active
            && $user->hospital->access_is_active;
    }
}
