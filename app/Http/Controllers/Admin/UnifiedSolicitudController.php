<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributionDeliverySchedule;
use App\Models\Nutricionales\Solicitud as NutritionSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\SolicitudOnco;
use App\Support\SolicitudStatusFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UnifiedSolicitudController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canViewNutrition = $user->can('nutricionales_solicitudes_index');
        $canViewOncology = $user->can('oncologicos_solicitudes_index');

        abort_unless($canViewNutrition || $canViewOncology, 403);

        $role = $user->roles->first()?->name;
        $requests = collect();
        $statusFilter = SolicitudStatusFilter::normalize($request->query('estado'));

        if ($canViewNutrition) {
            $nutritionQuery = NutritionSolicitud::query()
                ->with(['user.hospital', 'solicitud_detail', 'solicitud_patient']);

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $nutritionQuery->where('user_id', $user->id);
            }

            $requests = $requests->concat(
                $nutritionQuery->get()->map(function (NutritionSolicitud $solicitud) {
                    $patient = trim(implode(' ', array_filter([
                        $solicitud->solicitud_patient?->nombre_paciente,
                        $solicitud->solicitud_patient?->apellidos_paciente,
                    ])));

                    return [
                        'type' => 'nutricionales',
                        'type_label' => 'Nutricional',
                        'model' => $solicitud,
                        'mixture' => null,
                        'id' => $solicitud->id,
                        'request_id' => $solicitud->id,
                        'hospital_id' => $solicitud->user?->hospital_id,
                        'hospital' => $solicitud->user?->hospital?->name ?? 'Sin hospital',
                        'patient' => $patient ?: 'Sin paciente',
                        'requested_at' => $solicitud->created_at,
                        'delivery_at' => $this->parseDate($solicitud->solicitud_detail?->fecha_hora_entrega),
                        'status' => $solicitud->estado ?: 'Pendiente',
                        'remission' => $solicitud->remision,
                        'lot' => $solicitud->lote,
                        'url' => route('admin.nutricionales.solicitudes.edit', $solicitud),
                    ];
                })
            );
        }

        if ($canViewOncology) {
            $oncologyQuery = SolicitudOnco::query()
                ->with([
                    'hospital',
                    'user',
                    'mezclas' => fn ($query) => $query
                        ->select('id', 'solicitud_id', 'lote', 'estado', 'remision', 'fecha_entrega')
                        ->orderBy('id'),
                ])
                ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos']);

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $oncologyQuery->where('hospital_id', $user->hospital_id);
            }

            $requests = $requests->concat(
                $oncologyQuery->get()->flatMap(function (SolicitudOnco $solicitud) {
                    $type = $solicitud->tipo_solicitud === 'antibioticos'
                        ? 'antibioticos'
                        : 'oncologicos';

                    $mixtures = $solicitud->mezclas->isNotEmpty()
                        ? $solicitud->mezclas
                        : collect([null]);

                    $solicitud->mezclas->each(
                        fn (Mezcla $mezcla) => $mezcla->setRelation('solicitud', $solicitud)
                    );

                    return $mixtures->map(fn (?Mezcla $mezcla) => [
                        'type' => $type,
                        'type_label' => $type === 'antibioticos' ? 'Antibiotico' : 'Oncologica',
                        'model' => $solicitud,
                        'mixture' => $mezcla,
                        'id' => $mezcla?->id,
                        'request_id' => $solicitud->id,
                        'hospital_id' => $solicitud->hospital_id,
                        'hospital' => $solicitud->hospital?->name ?? 'Sin hospital',
                        'patient' => $solicitud->nombre_paciente ?: 'Sin paciente',
                        'requested_at' => $solicitud->created_at,
                        'delivery_at' => $mezcla?->fecha_entrega ?? $solicitud->fecha_entrega,
                        'status' => $mezcla?->operational_status ?? ($solicitud->estado ?: 'pendiente'),
                        'remission' => $mezcla?->remision,
                        'lot' => $mezcla?->lote,
                        'url' => $mezcla
                            ? route('admin.oncologicos.mezclas.show', $mezcla)
                            : route('admin.oncologicos.solicitudes.edit', $solicitud),
                    ]);
                })
            );
        }

        $pendingApprovalCount = $requests
            ->filter(fn (array $row) => SolicitudStatusFilter::matches(
                SolicitudStatusFilter::PENDING,
                $row['status']
            ))
            ->count();

        $routeScheduleKeys = $this->routeScheduleKeys($requests);
        $routePendingCount = $requests
            ->filter(function (array $row) use ($routeScheduleKeys) {
                $routeKey = $this->routeKey($row['hospital_id'], $row['delivery_at']);

                return SolicitudStatusFilter::matches(
                    SolicitudStatusFilter::PREPARATION,
                    $row['status'],
                    $routeKey !== null && $routeScheduleKeys->has($routeKey)
                );
            })
            ->count();
        $deliveryPendingCount = $requests
            ->filter(function (array $row) use ($routeScheduleKeys) {
                $routeKey = $this->routeKey($row['hospital_id'], $row['delivery_at']);

                return SolicitudStatusFilter::matches(
                    SolicitudStatusFilter::IN_ROUTE,
                    $row['status'],
                    $routeKey !== null && $routeScheduleKeys->has($routeKey)
                );
            })
            ->count();

        if ($statusFilter !== SolicitudStatusFilter::ALL) {
            $requests = $requests->filter(function (array $row) use ($routeScheduleKeys, $statusFilter) {
                $routeKey = $this->routeKey($row['hospital_id'], $row['delivery_at']);

                return SolicitudStatusFilter::matches(
                    $statusFilter,
                    $row['status'],
                    $routeKey !== null && $routeScheduleKeys->has($routeKey)
                );
            });
        }

        $requests = $requests
            ->sortByDesc(fn (array $row) => $row['requested_at']?->getTimestamp() ?? 0)
            ->values();

        return view('admin.solicitudes.index', compact(
            'requests',
            'statusFilter',
            'pendingApprovalCount',
            'routePendingCount',
            'deliveryPendingCount',
            'canViewNutrition',
            'canViewOncology'
        ));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        return filled($value) ? Carbon::parse($value) : null;
    }

    private function routeScheduleKeys($requests)
    {
        $hospitalIds = $requests->pluck('hospital_id')->filter()->unique()->values();
        $deliveryDates = $requests
            ->pluck('delivery_at')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($hospitalIds->isEmpty() || $deliveryDates->isEmpty()) {
            return collect();
        }

        return DistributionDeliverySchedule::query()
            ->where('status', 'sent')
            ->whereIn('hospital_id', $hospitalIds)
            ->whereIn('scheduled_date', $deliveryDates)
            ->get(['hospital_id', 'scheduled_date'])
            ->mapWithKeys(fn (DistributionDeliverySchedule $schedule) => [
                $this->routeKey($schedule->hospital_id, $schedule->scheduled_date) => true,
            ]);
    }

    private function routeKey(mixed $hospitalId, mixed $deliveryAt): ?string
    {
        if (! $hospitalId || ! $deliveryAt) {
            return null;
        }

        return (int) $hospitalId.'|'.Carbon::parse($deliveryAt)->toDateString();
    }
}
