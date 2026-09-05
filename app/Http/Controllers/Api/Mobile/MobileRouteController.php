<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DistributionDeliveryConfirmation;
use App\Models\DistributionDeliverySchedule;
use App\Models\DistributionLocationUpdate;
use App\Models\DistributionRoute;
use App\Models\DistributionRouteRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileRouteController extends Controller
{
    public function assigned(Request $request): JsonResponse
    {
        /** @var User $messenger */
        $messenger = $request->user();
        $date = $request->date('date')?->toDateString() ?? now('America/Mexico_City')->toDateString();

        $routes = DistributionRoute::query()
            ->whereHas('messengers', fn ($query) => $query->whereKey($messenger->id))
            ->with([
                'hospitals:id,name,adress,short_name,latitude,longitude',
                'messengers:id,name,lastname',
            ])
            ->whereNotIn('status', [DistributionRoute::STATUS_COMPLETED])
            ->orderBy('schedule_start')
            ->get()
            ->map(fn (DistributionRoute $route) => $this->routeData($route, $messenger, $date))
            ->filter(fn (array $route) => $route['stops_count'] > 0)
            ->values();

        return response()->json([
            'date' => $date,
            'routes' => $routes,
        ]);
    }

    public function show(Request $request, DistributionRoute $distributionRoute): JsonResponse
    {
        /** @var User $messenger */
        $messenger = $request->user();
        $this->ensureMessengerAssigned($distributionRoute, $messenger);
        $date = $request->date('date')?->toDateString() ?? now('America/Mexico_City')->toDateString();

        $distributionRoute->loadMissing([
            'hospitals:id,name,adress,short_name,latitude,longitude',
            'messengers:id,name,lastname',
        ]);

        return response()->json(['route' => $this->routeData($distributionRoute, $messenger, $date)]);
    }

    public function start(Request $request, DistributionRoute $distributionRoute): JsonResponse
    {
        /** @var User $messenger */
        $messenger = $request->user();
        $this->ensureMessengerAssigned($distributionRoute, $messenger);
        $coordinates = $this->coordinates($request, true);

        $run = DB::transaction(function () use ($distributionRoute, $messenger, $coordinates): DistributionRouteRun {
            $run = DistributionRouteRun::query()
                ->where('distribution_route_id', $distributionRoute->id)
                ->where('messenger_id', $messenger->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first()
                ?? DistributionRouteRun::query()->create([
                    'distribution_route_id' => $distributionRoute->id,
                    'messenger_id' => $messenger->id,
                    'started_at' => now(),
                    'start_latitude' => $coordinates['latitude'] ?? null,
                    'start_longitude' => $coordinates['longitude'] ?? null,
                    'start_accuracy' => $coordinates['accuracy'] ?? null,
                ]);

            if ($distributionRoute->status !== DistributionRoute::STATUS_COMPLETED) {
                $distributionRoute->update(['status' => DistributionRoute::STATUS_IN_ROUTE]);
            }

            return $run;
        });

        return response()->json([
            'run' => $this->runData($run),
            'message' => 'Ruta iniciada correctamente.',
        ]);
    }

    public function location(Request $request, DistributionRoute $distributionRoute): JsonResponse
    {
        /** @var User $messenger */
        $messenger = $request->user();
        $this->ensureMessengerAssigned($distributionRoute, $messenger);
        $coordinates = $this->coordinates($request);
        $run = $this->activeRun($distributionRoute, $messenger);

        $location = DistributionLocationUpdate::query()->create([
            'distribution_route_run_id' => $run->id,
            ...$coordinates,
            'recorded_at' => $request->date('recorded_at') ?? now(),
        ]);

        return response()->json([
            'location_id' => $location->id,
            'recorded_at' => $location->recorded_at->toIso8601String(),
        ], 201);
    }

    public function confirmDelivery(
        Request $request,
        DistributionRoute $distributionRoute,
        DistributionDeliverySchedule $schedule
    ): JsonResponse {
        /** @var User $messenger */
        $messenger = $request->user();
        $this->ensureMessengerAssigned($distributionRoute, $messenger);
        abort_unless((int) $schedule->distribution_route_id === (int) $distributionRoute->id, 404);
        abort_unless($schedule->status === 'sent', 422, 'La entrega no está disponible para confirmar.');

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'delivered_at' => ['nullable', 'date'],
        ]);
        $run = $this->activeRun($distributionRoute, $messenger);

        $confirmation = DB::transaction(function () use ($schedule, $run, $messenger, $validated, $distributionRoute): DistributionDeliveryConfirmation {
            $existing = DistributionDeliveryConfirmation::query()
                ->where('distribution_delivery_schedule_id', $schedule->id)
                ->lockForUpdate()
                ->first();

            $confirmation = $existing ?? DistributionDeliveryConfirmation::query()->create([
                'distribution_delivery_schedule_id' => $schedule->id,
                'distribution_route_run_id' => $run->id,
                'messenger_id' => $messenger->id,
                'delivered_at' => $validated['delivered_at'] ?? now(),
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'accuracy' => $validated['accuracy'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $scheduleIds = DistributionDeliverySchedule::query()
                ->where('distribution_route_id', $distributionRoute->id)
                ->whereDate('scheduled_date', $schedule->scheduled_date)
                ->where('status', 'sent')
                ->lockForUpdate()
                ->pluck('id');

            $confirmedCount = DistributionDeliveryConfirmation::query()
                ->whereIn('distribution_delivery_schedule_id', $scheduleIds)
                ->count();

            if ($scheduleIds->isNotEmpty() && $confirmedCount === $scheduleIds->count()) {
                $run->update(['ended_at' => now()]);
                $distributionRoute->update(['status' => DistributionRoute::STATUS_COMPLETED]);
            } elseif ($distributionRoute->status !== DistributionRoute::STATUS_COMPLETED) {
                $distributionRoute->update(['status' => DistributionRoute::STATUS_IN_ROUTE]);
            }

            return $confirmation;
        });

        return response()->json([
            'delivery_id' => $confirmation->id,
            'schedule_id' => $schedule->id,
            'delivered_at' => $confirmation->delivered_at->toIso8601String(),
            'message' => 'Entrega confirmada correctamente.',
        ], 201);
    }

    private function ensureMessengerAssigned(DistributionRoute $route, User $messenger): void
    {
        abort_unless($route->messengers()->whereKey($messenger->id)->exists(), 403, 'No tienes asignada esta ruta.');
    }

    private function activeRun(DistributionRoute $route, User $messenger): DistributionRouteRun
    {
        $run = DistributionRouteRun::query()
            ->where('distribution_route_id', $route->id)
            ->where('messenger_id', $messenger->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        abort_unless($run, 422, 'Inicia la ruta antes de registrar ubicación o entregas.');

        return $run;
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: float|null}
     */
    private function coordinates(Request $request, bool $nullable = false): array
    {
        $required = $nullable ? 'nullable' : 'required';
        $validated = $request->validate([
            'latitude' => [$required, 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => [$required, 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        return [
            'latitude' => isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            'longitude' => isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeData(DistributionRoute $route, User $messenger, string $date): array
    {
        $schedules = DistributionDeliverySchedule::query()
            ->where('distribution_route_id', $route->id)
            ->whereDate('scheduled_date', $date)
            ->where('status', 'sent')
            ->with('hospital:id,name,adress,short_name,latitude,longitude')
            ->get()
            ->keyBy('hospital_id');
        $confirmations = DistributionDeliveryConfirmation::query()
            ->whereIn('distribution_delivery_schedule_id', $schedules->pluck('id'))
            ->get()
            ->keyBy('distribution_delivery_schedule_id');
        $run = DistributionRouteRun::query()
            ->where('distribution_route_id', $route->id)
            ->where('messenger_id', $messenger->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $stops = $route->hospitals
            ->filter(fn ($hospital) => $schedules->has($hospital->id))
            ->map(function ($hospital) use ($schedules, $confirmations): array {
                $schedule = $schedules->get($hospital->id);
                $confirmation = $confirmations->get($schedule->id);

                return [
                    'schedule_id' => $schedule->id,
                    'hospital_id' => $hospital->id,
                    'order' => (int) $hospital->pivot->stop_order,
                    'hospital' => $hospital->short_name ?: $hospital->name,
                    'address' => $hospital->adress,
                    'latitude' => $hospital->latitude,
                    'longitude' => $hospital->longitude,
                    'status' => $confirmation ? 'delivered' : 'pending',
                    'delivered_at' => $confirmation?->delivered_at?->toIso8601String(),
                ];
            })
            ->sortBy('order')
            ->values();

        return [
            'id' => $route->id,
            'code' => $route->code,
            'name' => $route->name,
            'date' => $date,
            'schedule_start' => Carbon::parse($route->schedule_start)->format('H:i'),
            'schedule_end' => Carbon::parse($route->schedule_end)->format('H:i'),
            'status' => $run ? 'in_route' : $route->status,
            'run' => $run ? $this->runData($run) : null,
            'stops_count' => $stops->count(),
            'completed_stops_count' => $stops->where('status', 'delivered')->count(),
            'stops' => $stops,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runData(DistributionRouteRun $run): array
    {
        return [
            'id' => $run->id,
            'started_at' => $run->started_at->toIso8601String(),
            'status' => $run->ended_at ? 'completed' : 'in_route',
        ];
    }
}
