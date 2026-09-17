<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HospitalConciliationExport;
use App\Http\Controllers\Controller;
use App\Models\HospitalConciliationSubmission;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Support\AdministrationNavigation;
use App\Support\ConciliationInboxFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ConciliationSubmissionController extends Controller
{
    private function authorizeInternal(Request $request): void
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        abort_unless(AdministrationNavigation::canViewReports($request->user()), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeInternal($request);
        $filters = ConciliationInboxFilters::validate($request->query());
        $search = trim($filters['search'] ?? '');
        $institutionId = !empty($filters['institucion_id']) ? (int) $filters['institucion_id'] : null;
        $hospitalId = !empty($filters['hospital_id']) ? (int) $filters['hospital_id'] : null;
        $institutions = Institucion::orderBy('nombre')->get(['id', 'nombre']);
        $hospitals = Hospital::with('instituciones:id,nombre')->orderBy('name')->get(['id', 'name']);
        $institutionNames = $hospitals->mapWithKeys(fn ($hospital) => [
            $hospital->id => $hospital->instituciones->pluck('nombre')->filter()->unique()->sort()->implode(', '),
        ]);
        $submissions = HospitalConciliationSubmission::query()->select([
            'id', 'hospital_id', 'hospital_name', 'sender_name', 'period_from', 'period_to', 'mixture_count', 'conciliable_count', 'created_at',
        ]);
        $submissions = ConciliationInboxFilters::apply($submissions, $filters)->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.instituciones.conciliacion.index', compact('submissions', 'search', 'institutions', 'hospitals', 'institutionId', 'hospitalId', 'institutionNames'));
    }

    public function show(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeInternal($request);
        $filters = $request->validate([
            'estado' => ['sometimes', Rule::in(['todas', 'si', 'no'])],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $state = $filters['estado'] ?? 'todas';
        $search = trim($filters['search'] ?? '');
        $summary = $submission->summary();
        $all = collect($submission->snapshot)->map(function ($row) {
            $row['conciliable'] = $row['conciliable'] ?? $row['cells']['conciliable'] !== 'No';
            $row['url'] = in_array($row['kind'], ['nutricionales', 'oncologicos', 'antibioticos'], true) && $row['id'] > 0
                ? route($row['kind'] === 'nutricionales' ? 'admin.nutricionales.solicitudes.show' : 'admin.oncologicos.mezclas.show', $row['id']) : null;
            return $row;
        });
        $institution = $all->pluck('cells.institution')->filter()->unique()->implode(', ') ?: 'Sin institución';
        $needle = Str::lower(Str::ascii($search));
        $rows = $all->filter(fn ($row) => ($state === 'todas' || $row['conciliable'] === ($state === 'si'))
            && ($needle === '' || str_contains(Str::lower(Str::ascii(implode(' ', [
                $row['cells']['request_id'], $row['cells']['id'], $row['cells']['patient'],
            ]))), $needle)))->values();
        $data = compact('submission', 'rows', 'summary', 'institution', 'state', 'search');
        if ($request->expectsJson()) return response()->json([
            'title' => 'Resumen de conciliación · '.$submission->folio(),
            'html' => view('admin.instituciones.conciliacion._summary', $data)->render(),
        ])->header('Cache-Control', 'no-store');
        return view('admin.instituciones.conciliacion.show', $data);
    }

    public function download(Request $request, HospitalConciliationSubmission $submission)
    {
        $this->authorizeInternal($request);
        return Excel::download(new HospitalConciliationExport(collect($submission->snapshot), true), $submission->folio().'.xlsx');
    }
}
