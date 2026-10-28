<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\HospitalConciliationSubmission;
use App\Services\HospitalConciliationSummary;
use App\Services\ClientConciliationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientToolsController extends Controller
{
    public function detail(Request $request, HospitalConciliationSubmission $submission)
    {
        abort_unless($request->user()->canConsultHospital($submission->hospital_id), 404);

        $detail = app(ClientConciliationReview::class)->presentation($submission);
        if ($request->user()->isInstitutionAccount()) $detail['canRespond'] = false;
        return response()->json($detail + ['html' => view('admin.herramientas._conciliation-detail', $detail)->render(),
            'accept_url' => route('admin.herramientas.conciliaciones.accept', $submission),
        ])->header('Cache-Control', 'no-store');
    }

    public function accept(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeChangeRequest($request, $submission);
        $data = $request->validate([
            'submission_key' => ['required', 'uuid'], 'version' => ['required', 'string', 'size:64'],
            'choices' => ['required', 'array', 'min:1', 'max:10000'], 'choices.*' => ['required', 'boolean'],
            'reasons' => ['present', 'array', 'max:10000'], 'reasons.*' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['choices'] = array_map(fn ($value) => (bool) $value, $data['choices']);
        $data['reasons'] = array_map(fn ($value) => trim($value ?? ''), $data['reasons']);
        return DB::transaction(function () use ($request, $submission, $data) {
            Hospital::whereKey($submission->hospital_id)->lockForUpdate()->firstOrFail();
            $hash = HospitalConciliationSummary::signature(['client_review', $request->user()->id, $submission->id, $data['version'], $data['choices'], $data['reasons']]);
            $existing = HospitalConciliationSubmission::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash ?? '', $hash), 409, 'Esta respuesta ya se guardó con otros datos. Vuelve a abrir el detalle.');
                return $this->acceptedResponse($existing);
            }
            $submission->refresh();
            $this->authorizeChangeRequest($request, $submission);
            abort_unless(hash_equals(ClientConciliationReview::version($submission), $data['version']), 409, 'La conciliación cambió. Vuelve a abrir el detalle.');
            abort_if(ClientConciliationReview::response($submission), 409, 'Este periodo ya tiene una respuesta del hospital. Vuelve a abrir el detalle.');
            \App\Models\Nutricionales\Solicitud::where('hospital_id', $submission->hospital_id)->orderBy('id')->lockForUpdate()->get(['id']);
            \App\Models\Oncologicos\Mezcla::whereHas('solicitud', fn ($q) => $q->where('hospital_id', $submission->hospital_id))->orderBy('id')->lockForUpdate()->get(['id']);
            \App\Models\InstitutionBilling::where('hospital_id', $submission->hospital_id)->orderBy('id')->lockForUpdate()->get(['id']);
            $current = app(ClientConciliationReview::class)->currentGroup($submission);
            abort_unless($current && !$current['missing_prices'], 409, 'El periodo fue actualizado por PROMESA Prodifem. Solicita el envío de la conciliación actualizada.');
            $keys = collect($submission->snapshot)->map(fn ($row) => $row['kind'].'-'.$row['id'])->all();
            abort_unless(count($data['choices']) === count($keys) && !array_diff(array_keys($data['choices']), $keys)
                && !array_diff(array_keys($data['reasons']), $keys), 422, 'La selección debe contener únicamente todas las remisiones de este periodo.');
            $hasChanges = false;
            $snapshot = collect($submission->snapshot)->map(function ($row) use ($data, &$hasChanges) {
                $key = $row['kind'].'-'.$row['id'];
                $yes = $row['conciliable'] ?? (($row['cells']['conciliable'] ?? 'Sí') !== 'No');
                $changed = $data['choices'][$key] !== $yes;
                $reason = $data['reasons'][$key] ?? '';
                abort_if($changed && $reason === '', 422, 'Indica el motivo de cada cambio solicitado.');
                $requested = $changed || $reason !== '';
                $hasChanges = $hasChanges || $requested;
                $row['conciliable'] = $data['choices'][$key];
                $row['cells']['conciliable'] = $row['conciliable'] ? 'Sí' : 'No';
                $row['change_requested'] = $requested;
                $row['requested_conciliable'] = $changed ? $row['conciliable'] : null;
                $row['reason'] = $reason ?: null;
                return $row;
            })->all();
            $response = HospitalConciliationSubmission::create([
                'submission_key' => $data['submission_key'], 'request_hash' => $hash, 'direction' => 'received',
                'hospital_id' => $submission->hospital_id, 'hospital_name' => $submission->hospital_name,
                'submitted_by' => $request->user()->id, 'sender_name' => $request->user()->name,
                'period_from' => $submission->period_from, 'period_to' => $submission->period_to,
                'filters' => array_intersect_key($submission->filters, array_flip(['institucion_id', 'period_id', 'period_content_hash', 'modalidad']))
                    + ['source_submission_id' => $submission->id, 'response_type' => $hasChanges ? 'changes_requested' : 'accepted'],
                'mixture_count' => count($snapshot), 'conciliable_count' => collect($snapshot)->where('conciliable', true)->count(), 'snapshot' => $snapshot,
            ]);
            return $this->acceptedResponse($response, 201);
        });
    }

    private function acceptedResponse(HospitalConciliationSubmission $response, int $status = 200)
    {
        $accepted = ($response->filters['response_type'] ?? null) === 'accepted';
        return response()->json(['folio' => $response->folio(), 'status' => $accepted ? 'Aceptada' : 'Con cambios',
            'summary' => $response->summary(), 'source_id' => $response->filters['source_submission_id'],
            'message' => $accepted ? 'Conciliación aceptada. La aceptación quedó registrada para PROMESA Prodifem.'
                : 'Respuesta enviada con cambios. PROMESA Prodifem revisará y autorizará la solicitud.',
        ], $status);
    }

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
        abort_unless($request->user()->canConsultHospital($submission->hospital_id), 404);

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
            $user = $request->user();
            abort_unless($user->hospital_id || $user->isInstitutionAccount(), 403);
            $institutionName = $user->isInstitutionAccount() ? $user->institution?->nombre
                : $user->hospital->instituciones->pluck('nombre')->filter()->unique()->sort()->implode(', ');
            $submissions = HospitalConciliationSubmission::whereIn('hospital_id', $user->portalHospitalIds())
                ->orderByDesc('id')->paginate(15)->withQueryString();
            $responses = HospitalConciliationSubmission::whereIn('hospital_id', $user->portalHospitalIds())->where('direction', 'received')
                ->whereIn('filters->source_submission_id', $submissions->pluck('id'))
                ->whereIn('filters->response_type', ['accepted', 'changes_requested'])->latest('id')->get()
                ->unique(fn ($row) => $row->filters['source_submission_id'])->keyBy(fn ($row) => $row->filters['source_submission_id']);
            foreach ($submissions as $submission) $submission->setRelation('clientReview', $responses->get($submission->id));
        }

        return view('admin.herramientas.index', compact('submissions', 'institutionName', 'billingRecords'));
    }
}
