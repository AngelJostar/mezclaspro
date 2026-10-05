<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\PersonnelProfile;
use App\Models\RequestQuotation;
use Illuminate\Http\Request;

class MobileSalesController extends Controller
{
    private function authorizeSales(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->is_active && $user->isSalesperson()
            && $user->personnelProfile?->employment_status === 'hired'
            && in_array(PersonnelProfile::POSITION_MOBILE, $user->personnelProfile->positions ?? [], true), 403);
    }

    public function clients(Request $request)
    {
        $this->authorizeSales($request);
        $filters = $request->validate(['search' => 'nullable|string|max:150', 'assigned' => 'sometimes|boolean']);
        $search = trim($filters['search'] ?? '');
        return response()->json(Hospital::with('instituciones:id,nombre')
            ->where('access_is_active', true)
            ->whereHas('salespeople', fn ($s) => $s->where('users.id', $request->user()->id))
            ->where('is_active', true)->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(20, ['id', 'name', 'short_name'])
            ->through(fn ($h) => ['id' => $h->id, 'name' => $h->name,
                'institution' => $h->instituciones->pluck('nombre')->implode(', ')]));
    }

    public function deliveries(Request $request)
    {
        $this->authorizeSales($request);
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        return response()->json(\App\Models\DistributionDeliverySchedule::query()
            ->whereIn('status', ['scheduled', 'sent'])
            ->whereHas('hospital.salespeople', fn ($q) => $q->where('users.id', $request->user()->id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_date', $request->input('date')))
            ->with(['hospital:id,name', 'route:id,name', 'confirmation'])
            ->orderByDesc('scheduled_date')->orderByDesc('id')->paginate(20)
            ->through(fn ($s) => ['id' => $s->id, 'hospital' => $s->hospital?->name,
                'date' => $s->scheduled_date->toDateString(), 'route' => $s->route?->name,
                'status' => $s->confirmation ? 'Entregada' : ($s->status === 'scheduled' ? 'Programada' : 'Pendiente'),
                'delivered_at' => $s->confirmation?->delivered_at?->toIso8601String()]));
    }

    public function quotations(Request $request)
    {
        $this->authorizeSales($request);
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'hospital_id' => 'nullable|integer',
            'institution_id' => 'nullable|integer', 'status' => 'nullable|in:borrador,enviada,autorizada,preparacion',
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'tab' => 'nullable|in:all,new,history']);
        $base = RequestQuotation::forSalesperson($request->user());
        $options = (clone $base)->with(['hospital:id,name', 'institution:id,nombre'])->distinct()->get(['hospital_id', 'institution_id']);
        $assigned = Hospital::whereHas('salespeople', fn ($q) => $q->where('users.id', $request->user()->id))->pluck('id');
        $query = clone $base;
        foreach (['hospital_id', 'institution_id', 'status'] as $key) {
            if ($request->filled($key)) $query->where($key, $filters[$key]);
        }
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $filters['from']);
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $filters['to']);
        if (($filters['tab'] ?? 'all') === 'new') $query->whereIn('status', ['borrador', 'enviada']);
        if (($filters['tab'] ?? 'all') === 'history') $query->whereIn('status', ['autorizada', 'preparacion']);
        $search = trim($filters['search'] ?? '');
        if ($search !== '') $query->where(fn ($q) => $q->where('patient_name', 'like', '%'.$search.'%')
            ->when(preg_match('/^(?:COT-)?0*(\d+)$/i', $search, $match), fn ($s) => $s->orWhere('id', (int) $match[1]))
            ->orWhereHas('seller', fn ($s) => $s->where('name', 'like', '%'.$search.'%')->orWhere('lastname', 'like', '%'.$search.'%')));
        $page = $query->with(['hospital:id,name', 'institution:id,nombre', 'seller:id,name,lastname'])
            ->latest('id')->paginate(20)->through(fn ($q) => [
                'id' => $q->id, 'folio' => $q->folio, 'hospital' => $q->hospital?->name,
                'category' => $q->category, 'status' => $q->status, 'total' => $q->total,
                'institution' => $q->institution?->nombre, 'patient' => $q->patient_name ?: null,
                'seller' => $q->seller ? trim($q->seller->name.' '.$q->seller->lastname) : null,
                'date' => $q->created_at?->toDateString(), 'status_label' => $q->status_label,
                'can_send' => $assigned->contains($q->hospital_id),
            ]);
        return response()->json($page->toArray() + ['filters' => [
            'hospitals' => $options->pluck('hospital')->filter()->unique('id')->values()->map->only(['id', 'name'])->all(),
            'institutions' => $options->pluck('institution')->filter()->unique('id')->values()->map(fn ($i) => ['id' => $i->id, 'name' => $i->nombre])->all(),
        ]]);
    }

    public function hospitalRequests(Request $request)
    {
        $this->authorizeSales($request);
        return response()->json(\App\Models\HospitalQuotationRequest::forSeller($request->user())
            ->with(['hospital', 'seller', 'quotation'])->latest('id')->paginate(20)->through(fn ($r) => $r->summary()));
    }

    public function requestAttachment(Request $request, \App\Models\HospitalQuotationRequest $hospitalRequest)
    {
        $this->authorizeSales($request);
        abort_unless($hospitalRequest->canQuote($request->user()), 403);
        abort_unless($hospitalRequest->attachment_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($hospitalRequest->attachment_path), 404);
        return \Illuminate\Support\Facades\Storage::disk('local')->response($hospitalRequest->attachment_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function show(Request $request, RequestQuotation $quotation)
    {
        $this->authorizeSales($request);
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        return response()->json(['folio' => $quotation->folio, 'category' => $quotation->category,
            'id' => $quotation->id, 'hospital' => $quotation->hospital?->name,
            'clinical_data' => $quotation->clinical_data ?? [],
            'pricing_snapshot' => array_replace(['lines' => [], 'total' => (float) $quotation->total], $quotation->pricing_snapshot ?? []),
            'editable' => $quotation->canBeEditedBy($request->user()),
            'status' => $quotation->status, 'total' => $quotation->total,
            'document' => \App\Support\QuotationDocument::metadata($quotation->pricing_snapshot ?? [], $quotation->created_at)]);
    }
}
