<?php

namespace App\Http\Middleware;

use App\Models\PersonnelProfile;
use Closure;
use Illuminate\Http\Request;

class EnsureMobileSalesAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user?->is_active && $user->isSalesperson()
            && $user->personnelProfile?->employment_status === 'hired'
            && in_array(PersonnelProfile::POSITION_MOBILE, $user->personnelProfile->positions ?? [], true), 403);
        $request->validate([
            'no_commercial_relationship' => 'sometimes|declined',
            'items.*.unit_price_override' => 'prohibited',
        ]);
        $quotation = $request->route('quotation');
        if ($quotation instanceof \App\Models\RequestQuotation && $request->isMethod('POST')) {
            abort_unless(\App\Models\Hospital::whereKey($quotation->hospital_id)
                ->whereHas('salespeople', fn ($q) => $q->where('users.id', $user->id))->exists(), 403,
                'Solo puedes enviar cotizaciones a tus hospitales asignados.');
        }
        return $next($request);
    }
}
