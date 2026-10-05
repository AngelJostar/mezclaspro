<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HospitalQuotationRequest;
use App\Models\RequestQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HospitalQuotationRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasAnyRole(['Cliente', 'Institucion']), 403);
        $types = array_filter(['oncologicos', 'nutricionales', 'antibioticos'], fn ($type) => RequestQuotation::canCreate($user, $type));
        abort_unless(count($types), 403);
        $requests = HospitalQuotationRequest::whereIn('category', $types)
            ->when($user->hasSalesOnlyAccess(), fn ($q) => $q->forSeller($user))
            ->with(['hospital', 'seller', 'quotation'])->latest('id')->paginate(20);
        return view('admin.solicitudes.quotations.hospital-requests', compact('requests'));
    }

    public function attachment(Request $request, HospitalQuotationRequest $hospitalRequest)
    {
        abort_unless($hospitalRequest->canQuote($request->user()), 403);
        abort_unless($hospitalRequest->attachment_path && Storage::disk('local')->exists($hospitalRequest->attachment_path), 404);
        return Storage::disk('local')->response($hospitalRequest->attachment_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
