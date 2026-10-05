<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DistributionDeliverySchedule;
use App\Models\PersonnelProfile;
use App\Services\MobileDeliveryTracking;
use Illuminate\Http\Request;

class MobileDeliveryTrackingController extends Controller
{
    private function visible(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->is_active && !$user->isBlockedByOrganization(), 403);
        $query = DistributionDeliverySchedule::whereIn('status', ['scheduled', 'sent']);
        if (str_contains($request->path(), '/sales/')) {
            abort_unless($user->isSalesperson() && $user->personnelProfile?->employment_status === 'hired'
                && in_array(PersonnelProfile::POSITION_MOBILE, $user->personnelProfile->positions ?? [], true), 403);
            return $query->whereHas('hospital.salespeople', fn ($q) => $q->where('users.id', $user->id));
        }
        abort_unless($user->hasAnyRole(['Cliente', 'Institucion']) && $user->hospital_id, 403);
        return $query->where('hospital_id', $user->hospital_id);
    }

    private function inRoute($query)
    {
        return $query->whereDoesntHave('confirmation')->whereExists(fn ($run) => $run->selectRaw('1')->from('distribution_route_runs')
            ->whereColumn('distribution_route_runs.distribution_route_id', 'distribution_delivery_schedules.distribution_route_id')
            ->whereRaw('DATE(distribution_route_runs.started_at) = DATE(distribution_delivery_schedules.scheduled_date)')->whereNull('ended_at'));
    }

    public function index(Request $request, MobileDeliveryTracking $tracking)
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d', 'state' => 'nullable|in:in_route,delivered,all']);
        $base = $this->visible($request)->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_date', $data['date']));
        $counts = ['all' => (clone $base)->count(), 'delivered' => (clone $base)->whereHas('confirmation')->count(),
            'in_route' => $this->inRoute(clone $base)->count()];
        $query = clone $base;
        if (($data['state'] ?? 'all') === 'in_route') $this->inRoute($query);
        if (($data['state'] ?? 'all') === 'delivered') $query->whereHas('confirmation');
        $page = $query->with(['hospital', 'route', 'confirmation.messenger.personnelProfile', 'warehouse'])->latest('scheduled_date')->latest('id')->paginate(20)
            ->through(function ($s) use ($tracking) { $data = $tracking->data($s); $data['mixtures_count'] = count($tracking->mixtures($s)); return $data; });
        return response()->json(['deliveries' => $page, 'counts' => $counts]);
    }

    public function show(Request $request, int $schedule, MobileDeliveryTracking $tracking)
    {
        $delivery = $this->visible($request)->whereKey($schedule)->first();
        abort_unless($delivery, 403);
        return response()->json($tracking->data($delivery, true));
    }
}
