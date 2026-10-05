<?php

namespace App\Services;

use App\Models\RequestQuotation;
use App\Models\User;
use App\Notifications\QuotationAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuotationWorkflowService
{
    public function record(RequestQuotation $quotation): void
    {
        if (!$quotation->wasRecentlyCreated && !$quotation->wasChanged(['status', 'seller_id'])) return;
        $previous = $quotation->wasRecentlyCreated ? null : $quotation->getRawOriginal('status');
        $eventKey = hash('sha256', implode(':', [$quotation->id, $previous, $quotation->status, $quotation->getRawOriginal('seller_id'), $quotation->seller_id]));
        if (!DB::table('quotation_workflow_events')->insertOrIgnore([
            'request_quotation_id' => $quotation->id, 'actor_id' => auth()->id(), 'event_key' => $eventKey,
            'previous_status' => $previous, 'status' => $quotation->status,
            'source' => request()->is('api/mobile/*') ? 'mobile' : 'web', 'created_at' => now(),
        ])) return;
        if ($quotation->status === 'borrador') return;
        $quotation->loadMissing('hospital');
        $type = $quotation->category === 'nutricionales' ? 'nutricionales' : 'oncologicos';
        $recipients = User::where('is_active', true)->where('hospital_id', $quotation->hospital_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Cliente', 'Institucion']))->get();
        if ($quotation->seller_id) {
            $seller = User::activeSalespeople()->find($quotation->seller_id);
            if ($seller) $recipients->push($seller);
        } elseif ($quotation->hospital) {
            $recipients = $recipients->concat($quotation->hospital->salespeople()->activeSalespeople()->get());
        }
        if ($quotation->hospital?->laboratory_id) {
            $central = User::where('is_active', true)->whereHas('personnelProfile', fn ($q) => $q->where('laboratory_id', $quotation->hospital->laboratory_id))
                ->with('personnelProfile')->get()->filter(fn ($u) => $u->personnelProfile?->employment_status === 'hired'
                    && !$u->isSalesperson() && !$u->hasAnyRole(['Cliente', 'Institucion']) && $u->can($type.'_solicitudes_index'));
            $recipients = $recipients->concat($central);
        }
        foreach ($recipients->unique('id') as $user) {
            if ($user->isBlockedByOrganization()) continue;
            $assignedSeller = $user->isSalesperson() && (int) $quotation->seller_id === (int) $user->id;
            if ($assignedSeller && $quotation->status === 'enviada' && (int) $quotation->created_by === (int) $user->id) continue;
            $user->notifications()->create([
                'id' => (string) Str::uuid(), 'type' => $assignedSeller && $quotation->status === 'enviada' ? QuotationAssigned::class : 'quotation_workflow',
                'data' => ['event_key' => $eventKey, 'quotation_id' => $quotation->id, 'status' => $quotation->status,
                    'kind' => 'quotation_updated', 'title' => 'Cotización '.$quotation->status_label,
                    'message' => $quotation->folio.' · '.$quotation->hospital?->name.' · '.$quotation->status_label,
                    'url' => route('admin.solicitudes.cotizacion.index', array_filter(['buscar' => $quotation->folio, 'estado' => $assignedSeller ? 'recibidas' : null]))],
            ]);
        }
    }
}
