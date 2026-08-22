<?php

namespace App\View\Components;

use App\Models\Nutricionales\Solicitud as NutritionSolicitud;
use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\Oncologicos\SolicitudOnco;
use App\Services\InstitutionBillingPendingSummaryService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /**
     * @var array{yellow: int, red: int}
     */
    public array $billingDueCounts;

    public int $pendingSolicitudesCount;

    public int $myPurchaseOrdersCount;

    /**
     * Create a new component instance.
     */
    public function __construct(InstitutionBillingPendingSummaryService $pendingBilling)
    {
        $user = auth()->user();

        $this->billingDueCounts = auth()->user()?->hasAnyRole(['Super Admin', 'Administracion y facturacion'])
            ? $pendingBilling->counts()
            : ['yellow' => 0, 'red' => 0];

        $this->pendingSolicitudesCount = $this->countPendingSolicitudes($user);
        $this->myPurchaseOrdersCount = $user?->can('oncologicos_laboratory_index')
            ? LaboratoryPurchaseOrder::query()->where('created_by', $user->id)->count()
            : 0;
    }

    private function countPendingSolicitudes(mixed $user): int
    {
        if (! $user) {
            return 0;
        }

        $role = $user->roles->first()?->name;
        $total = 0;

        if ($user->can('nutricionales_solicitudes_index')) {
            $nutritionQuery = NutritionSolicitud::query()
                ->where(function ($query) {
                    $query->where('estado', 'pendiente')
                        ->orWhereNull('estado')
                        ->orWhere('estado', '');
                });

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $nutritionQuery->where('user_id', $user->id);
            }

            $total += $nutritionQuery->count();
        }

        if ($user->can('oncologicos_solicitudes_index')) {
            $oncologyQuery = SolicitudOnco::query()
                ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos'])
                ->where(function ($query) {
                    $query->where('estado', 'pendiente')
                        ->orWhereNull('estado')
                        ->orWhere('estado', '');
                });

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $oncologyQuery->where('hospital_id', $user->hospital_id);
            }

            $total += $oncologyQuery->count();
        }

        return $total;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.admin');
    }
}
