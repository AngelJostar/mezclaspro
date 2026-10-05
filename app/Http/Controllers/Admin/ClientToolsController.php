<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\HospitalConciliationSubmission;
use Illuminate\Http\Request;

class ClientToolsController extends Controller
{
    public function download(Request $request, HospitalConciliationSubmission $submission)
    {
        abort_unless($request->user()->hospital_id, 403);
        abort_unless($submission->hospital_id === (int) $request->user()->hospital_id, 404);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\HospitalConciliationExport(collect($submission->snapshot), true),
            $submission->folio().'.xlsx'
        );
    }

    public function index(Request $request)
    {
        $submissions = null;
        $institutionName = '';
        $billingRecords = null;
        if ($request->query('seccion') === 'facturacion') {
            $billingRecords = app(InstitucionBillingController::class)->clientRecords($request);
        }
        if ($request->query('seccion') === 'conciliacion') {
            abort_unless($request->user()->hospital_id, 403);
            $hospital = Hospital::with('instituciones:id,nombre')->findOrFail($request->user()->hospital_id);
            $institutionName = $hospital->instituciones->pluck('nombre')->filter()->unique()->sort()->implode(', ');
            $submissions = HospitalConciliationSubmission::where('hospital_id', $hospital->id)
                ->orderByDesc('id')->paginate(15)->withQueryString();
        }

        return view('admin.herramientas.index', compact('submissions', 'institutionName', 'billingRecords'));
    }
}
