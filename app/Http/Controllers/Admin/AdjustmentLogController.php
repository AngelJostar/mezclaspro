<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalReview;
use App\Models\Hospital;
use App\Models\MixtureAdjustment;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use App\Support\AdministrationNavigation;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;

class AdjustmentLogController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        abort_unless(AdministrationNavigation::canViewReports($request->user()), 403);
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'estado' => 'nullable|in:requested,authorized,approved,declined,rejected,cancelled',
        ]);
        $search = trim($filters['search'] ?? '');
        $state = $filters['estado'] ?? '';
        $adjustments = MixtureAdjustment::query()
            ->when($state, fn ($q) => $q->where('status', $state))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('target_id', $search)->orWhere('description', 'like', '%'.$search.'%');
            }))->latest('id')->paginate(15)->withQueryString();
        $hospitals = Hospital::with('instituciones:id,nombre')->whereIn('id', $adjustments->pluck('hospital_id'))->get()->keyBy('id');
        $users = User::whereIn('id', $adjustments->getCollection()->flatMap(fn ($a) => [$a->requested_by, $a->authorized_by, $a->approved_by, $a->cancelled_by])->filter()->unique())->get()->keyBy('id');
        $nutrition = Solicitud::with('solicitud_patient', 'solicitud_detail')->whereIn('id', $adjustments->where('kind', 'nutricionales')->pluck('target_id'))->get()->keyBy('id');
        $onco = Mezcla::with('solicitud')->whereIn('id', $adjustments->where('kind', '!=', 'nutricionales')->pluck('target_id'))->get()->keyBy('id');
        $reviews = ClinicalReview::whereIn('target_id', $adjustments->pluck('target_id'))->orderByDesc('created_at')->get();
        $rows = $adjustments->getCollection()->map(function ($adjustment) use ($hospitals, $nutrition, $onco, $reviews) {
            $target = ($adjustment->kind === 'nutricionales' ? $nutrition : $onco)->get($adjustment->target_id);
            $patient = $adjustment->kind === 'nutricionales'
                ? trim(($target?->solicitud_patient?->nombre_paciente ?? '').' '.($target?->solicitud_patient?->apellidos_paciente ?? ''))
                : $target?->solicitud?->nombre_paciente;
            // A preceding review is context only: no persisted link proves that it caused this adjustment.
            $review = $reviews->first(fn ($r) => $r->kind === $adjustment->kind && (int) $r->target_id === (int) $adjustment->target_id && $r->created_at <= $adjustment->created_at);
            $ai = null;
            $authorization = null;
            $unreadable = false;
            if ($review) {
                try {
                    $ai = $review->result;
                    $authorization = $review->medical_authorization;
                } catch (DecryptException $e) {
                    $unreadable = true;
                }
            }
            return compact('adjustment', 'target', 'patient', 'review', 'ai', 'authorization', 'unreadable') + ['hospital' => $hospitals->get($adjustment->hospital_id)];
        });
        return view('admin.instituciones.adjustment-log', compact('adjustments', 'rows', 'users', 'search', 'state'));
    }
}
