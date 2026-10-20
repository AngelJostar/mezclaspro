<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\HospitalConciliationSubmission;
use App\Services\HospitalConciliationSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientToolsController extends Controller
{
    private function authorizeChangeRequest(Request $request, HospitalConciliationSubmission $submission): void
    {
        abort_unless($request->user()->hasAnyRole(['Cliente', 'Institucion']) && $request->user()->hospital_id, 403);
        abort_unless($submission->hospital_id === (int) $request->user()->hospital_id && $submission->direction === 'sent', 404);
    }

    public function changeRequest(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeChangeRequest($request, $submission);
        return response()->json(['html' => view('admin.herramientas._conciliation-change-request', compact('submission'))->render()])
            ->header('Cache-Control', 'no-store');
    }

    public function requestChanges(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeChangeRequest($request, $submission);
        $data = $request->validate([
            'submission_key' => ['required', 'uuid'], 'requests' => ['required', 'array', 'min:1', 'max:10000'],
            'requests.*' => ['required', 'array:conciliable,reason'],
            'requests.*.conciliable' => ['present', 'nullable', 'boolean'],
            'requests.*.reason' => ['required', 'string', 'max:2000'],
        ], ['requests.required' => 'Indica al menos un cambio y su motivo.', 'requests.*.reason.required' => 'Indica el motivo de cada cambio solicitado.']);
        foreach ($data['requests'] as &$change) {
            $change['reason'] = trim($change['reason']);
            abort_if($change['reason'] === '', 422, 'Indica el motivo de cada cambio solicitado.');
            if ($change['conciliable'] !== null) $change['conciliable'] = (bool) $change['conciliable'];
        }
        unset($change);
        return DB::transaction(function () use ($request, $submission, $data) {
            Hospital::whereKey($submission->hospital_id)->lockForUpdate()->firstOrFail();
            $hash = HospitalConciliationSummary::signature([$request->user()->id, $submission->id, $data['requests']]);
            $existing = HospitalConciliationSubmission::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash ?? '', $hash), 409, 'Esta solicitud ya se envió con otros datos. Vuelve a abrir Solicitar cambios.');
                return response()->json(['folio' => $existing->folio(), 'message' => 'Solicitud '.$existing->folio().' enviada a PROMESA Prodifem. Pendiente de autorización.']);
            }
            $rows = collect($submission->snapshot);
            $keys = $rows->map(fn ($row) => $row['kind'].'-'.$row['id'])->all();
            abort_if(array_diff(array_keys($data['requests']), $keys), 422, 'Los cambios deben corresponder a remisiones de este periodo.');
            // Only the requested remittances enter this exchange; other pending requests remain visible.
            $snapshot = $rows->filter(fn ($row) => isset($data['requests'][$row['kind'].'-'.$row['id']]))->map(function ($row) use ($data) {
                $change = $data['requests'][$row['kind'].'-'.$row['id']] ?? null;
                $row['change_requested'] = $change !== null;
                $row['requested_conciliable'] = $change['conciliable'] ?? null;
                $row['reason'] = $change['reason'] ?? null;
                $row['conciliable'] = $change['conciliable'] ?? ($row['conciliable'] ?? ($row['cells']['conciliable'] !== 'No'));
                $row['cells']['conciliable'] = $row['conciliable'] ? 'Sí' : 'No';
                return $row;
            })->values()->all();
            $received = HospitalConciliationSubmission::create([
                'submission_key' => $data['submission_key'], 'request_hash' => $hash, 'direction' => 'received',
                'hospital_id' => $submission->hospital_id, 'hospital_name' => $submission->hospital_name,
                'submitted_by' => $request->user()->id, 'sender_name' => $request->user()->name,
                'period_from' => $submission->period_from, 'period_to' => $submission->period_to,
                'filters' => ['source_submission_id' => $submission->id, 'institucion_id' => $submission->filters['institucion_id'] ?? null],
                'mixture_count' => count($snapshot), 'conciliable_count' => collect($snapshot)->where('conciliable', true)->count(),
                'snapshot' => $snapshot,
            ]);
            return response()->json(['folio' => $received->folio(), 'message' => 'Solicitud '.$received->folio().' enviada a PROMESA Prodifem. Pendiente de autorización.'], 201);
        });
    }

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
