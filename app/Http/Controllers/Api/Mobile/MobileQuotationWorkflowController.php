<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\RequestQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileQuotationWorkflowController extends Controller
{
    public function show(Request $request, RequestQuotation $quotation)
    {
        $user = $request->user();
        $hospitalAccess = $user->hasAnyRole(['Cliente', 'Institucion']) && $user->hospital_id
            && (int) $user->hospital_id === (int) $quotation->hospital_id && $quotation->status !== 'borrador';
        abort_unless($user->is_active && !$user->isBlockedByOrganization() && ($hospitalAccess || $quotation->canBeViewedBy($user)), 403);
        $quotation->load(['hospital.laboratory', 'institution', 'seller']);
        return response()->json([
            'id' => $quotation->id, 'folio' => $quotation->folio, 'status' => $quotation->status, 'status_label' => $quotation->status_label,
            'institution' => $quotation->institution?->nombre, 'hospital' => $quotation->hospital?->name,
            'seller' => $quotation->seller_name, 'central' => $quotation->hospital?->laboratory?->nombre,
            'request_id' => $quotation->request_id, 'category' => $quotation->category,
            'can_authorize' => $quotation->status === 'enviada' && $quotation->canBeAuthorizedBy($user),
            'can_prepare' => $quotation->canStartPreparationBy($user),
            'events' => DB::table('quotation_workflow_events')->where('request_quotation_id', $quotation->id)->orderBy('id')
                ->get(['previous_status', 'status', 'source', 'created_at'])->map(function ($event) {
                    $event->created_at = \Carbon\Carbon::parse($event->created_at)->toIso8601String();
                    return $event;
                }),
        ])->header('Cache-Control', 'private, no-store');
    }
}
