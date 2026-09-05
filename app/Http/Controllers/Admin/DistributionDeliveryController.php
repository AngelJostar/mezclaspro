<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributionDeliverySchedule;
use App\Models\DistributionRoute;
use App\Models\Nutricionales\Solicitud as NutritionSolicitud;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\Mezcla;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DistributionDeliveryController extends Controller
{
    private const NUTRITION_ELIGIBLE_STATES = ['aprobada', 'preparada', 'revisada'];
    private const ONCOLOGY_ELIGIBLE_STATES = ['dispensada', 'preparada', 'revisada'];

    public function index(Request $request): View
    {
        $today = now('America/Mexico_City')->toDateString();
        $legacyDeliveryDate = $this->validDate((string) $request->query('date'));
        $deliveryDateFrom = $this->validDate((string) $request->query('date_from'))
            ?: $legacyDeliveryDate
            ?: $today;
        $deliveryDateTo = $this->validDate((string) $request->query('date_to'))
            ?: $legacyDeliveryDate
            ?: $deliveryDateFrom;

        if ($deliveryDateTo < $deliveryDateFrom) {
            [$deliveryDateFrom, $deliveryDateTo] = [$deliveryDateTo, $deliveryDateFrom];
        }

        $search = trim((string) $request->query('search', ''));
        $allowedStatuses = ['all', 'pending', 'ready', 'scheduled', 'sent'];
        $status = (string) $request->query('status', 'all');
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->with([
                'warehouses' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('id'),
            ])
            ->orderBy('nombre')
            ->get();
        $selectedLaboratory = $request->integer('laboratory_id') > 0
            ? $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            : null;

        $routes = DistributionRoute::query()
            ->with('hospitals:id,laboratory_id')
            ->orderBy('schedule_start')
            ->orderBy('id')
            ->get();

        $laboratories->each(function (Laboratory $laboratory) use ($routes): void {
            $routesCount = $routes->filter(
                fn (DistributionRoute $route) => $route->hospitals
                    ->contains('laboratory_id', $laboratory->id)
            )->count();

            $laboratory->setAttribute('routes_count', $routesCount);
        });

        $routesByHospital = $this->routesByHospital($routes);
        $mixtures = $this->deliveryMixtures($deliveryDateFrom, $deliveryDateTo)
            ->whereIn('laboratory_id', $laboratories->pluck('id'))
            ->values();

        if ($selectedLaboratory) {
            $mixtures = $mixtures
                ->where('laboratory_id', $selectedLaboratory->id)
                ->values();
        }

        $hospitalIds = $mixtures->pluck('hospital_id')->filter()->unique()->values();
        $warehouseIdsByLaboratory = $laboratories
            ->mapWithKeys(fn (Laboratory $laboratory) => [
                $laboratory->id => $laboratory->warehouses->first()?->id,
            ])
            ->filter();

        $scheduleQuery = DistributionDeliverySchedule::query()
            ->with(['route', 'warehouse:id,laboratory_id'])
            ->whereBetween('scheduled_date', [$deliveryDateFrom, $deliveryDateTo])
            ->whereIn('hospital_id', $hospitalIds);

        if ($selectedLaboratory) {
            $scheduleQuery->whereHas(
                'warehouse',
                fn ($query) => $query->where('laboratory_id', $selectedLaboratory->id)
            );
        }

        $schedulePriority = ['sent' => 0, 'scheduled' => 1, 'pending' => 2];
        $schedules = $scheduleQuery
            ->get()
            ->sortBy(fn (DistributionDeliverySchedule $schedule) => $schedulePriority[$schedule->status] ?? 3)
            ->unique(fn (DistributionDeliverySchedule $schedule) => $this->deliveryGroupKey(
                (int) $schedule->hospital_id,
                $schedule->scheduled_date->toDateString()
            ))
            ->keyBy(fn (DistributionDeliverySchedule $schedule) => $this->deliveryGroupKey(
                (int) $schedule->hospital_id,
                $schedule->scheduled_date->toDateString()
            ));

        $hospitals = $mixtures
            ->filter(fn (array $mixture) => filled($mixture['hospital_id']))
            ->groupBy(fn (array $mixture) => $this->deliveryGroupKey(
                (int) $mixture['hospital_id'],
                $mixture['delivery_date']
            ))
            ->map(function (Collection $hospitalMixtures) use (
                $routesByHospital,
                $schedules,
                $warehouseIdsByLaboratory
            ) {
                $firstMixture = $hospitalMixtures->first();
                $hospitalId = (int) $firstMixture['hospital_id'];
                $deliveryDate = $firstMixture['delivery_date'];
                $schedule = $schedules->get($this->deliveryGroupKey($hospitalId, $deliveryDate));
                $laboratoryId = (int) ($firstMixture['laboratory_id'] ?? 0);

                if ($schedule && (int) $schedule->warehouse?->laboratory_id !== $laboratoryId) {
                    $schedule = null;
                }

                $catalogRoute = $routesByHospital->get((int) $hospitalId);
                $route = $schedule?->route ?? $catalogRoute;
                $operationalStatus = match ($schedule?->status) {
                    'sent' => 'sent',
                    'scheduled' => 'scheduled',
                    default => $route ? 'ready' : 'pending',
                };

                return [
                    'hospital_id' => (int) $hospitalId,
                    'laboratory_id' => $laboratoryId ?: null,
                    'warehouse_id' => $schedule?->warehouse_id
                        ?? $warehouseIdsByLaboratory->get($laboratoryId),
                    'hospital' => $firstMixture['hospital'],
                    'delivery_date' => $deliveryDate,
                    'route_id' => $route?->id,
                    'route_code' => $route?->code ?? 'Sin ruta',
                    'mixtures_count' => $hospitalMixtures->count(),
                    'status' => $operationalStatus,
                    'modal' => [
                        'hospital' => $firstMixture['hospital'],
                        'route' => $route?->code ?? 'Sin ruta',
                        'date' => Carbon::parse($deliveryDate)->format('d/m/Y'),
                        'count' => $hospitalMixtures->count(),
                        'mixtures' => $hospitalMixtures->values(),
                    ],
                ];
            })
            ->values();

        if ($status !== 'all') {
            $hospitals = $hospitals->where('status', $status)->values();
        }

        if ($search !== '') {
            $normalizedSearch = Str::lower(Str::ascii($search));
            $hospitals = $hospitals->filter(function (array $hospital) use ($normalizedSearch) {
                $haystack = Str::lower(Str::ascii(implode(' ', [
                    $hospital['hospital'],
                    $hospital['route_code'],
                    $hospital['mixtures_count'],
                    $hospital['delivery_date'],
                ])));

                return str_contains($haystack, $normalizedSearch);
            })->values();
        }

        $hospitals = $hospitals
            ->sortBy(fn (array $hospital) => implode('|', [
                $hospital['delivery_date'],
                Str::lower(Str::ascii($hospital['hospital'])),
            ]))
            ->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $distributionHospitals = new LengthAwarePaginator(
            $hospitals->forPage($page, $perPage)->values(),
            $hospitals->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.distribution.deliveries.index', compact(
            'distributionHospitals',
            'laboratories',
            'selectedLaboratory',
            'deliveryDateFrom',
            'deliveryDateTo',
            'search',
            'status',
            'routes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $legacyDate = $this->validDate((string) $request->input('date'));
        $usesLegacyDate = ! $request->filled('date_from') && $legacyDate !== '';

        $request->merge([
            'date_from' => $request->input('date_from') ?: $legacyDate,
            'date_to' => $request->input('date_to') ?: $request->input('date_from') ?: $legacyDate,
        ]);

        $validated = $request->validate([
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $validated['legacy_date'] = $usesLegacyDate;

        $mixtures = $this->deliveryMixtures($validated['date_from'], $validated['date_to']);

        if (filled($validated['laboratory_id'] ?? null)) {
            $mixtures = $mixtures
                ->where('laboratory_id', (int) $validated['laboratory_id'])
                ->values();
        }

        $mixturesByHospitalAndDate = $mixtures
            ->filter(fn (array $mixture) => filled($mixture['hospital_id']))
            ->groupBy(fn (array $mixture) => $this->deliveryGroupKey(
                (int) $mixture['hospital_id'],
                $mixture['delivery_date']
            ));

        if ($mixturesByHospitalAndDate->isEmpty()) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'info',
                    'title' => 'Sin mezclas por programar',
                    'text' => 'No hay mezclas aprobadas o preparadas para el rango seleccionado.',
                ]);
        }

        $routesByHospital = $this->routesByHospital(
            DistributionRoute::query()->with('hospitals:id')->orderBy('schedule_start')->get()
        );

        $laboratoryIds = $mixtures
            ->pluck('laboratory_id')
            ->filter()
            ->unique()
            ->values();
        $warehouseIdsByLaboratory = Warehouse::query()
            ->where('is_active', true)
            ->whereIn('laboratory_id', $laboratoryIds)
            ->orderBy('id')
            ->get(['id', 'laboratory_id'])
            ->groupBy('laboratory_id')
            ->map(fn (Collection $warehouses) => $warehouses->first()->id);
        $scheduledCount = 0;

        DB::transaction(function () use (

            $mixturesByHospitalAndDate,
            $routesByHospital,
            $warehouseIdsByLaboratory,
            $request,
            &$scheduledCount
        ) {
            foreach ($mixturesByHospitalAndDate as $hospitalMixtures) {
                $hospitalId = (int) $hospitalMixtures->first()['hospital_id'];
                $deliveryDate = $hospitalMixtures->first()['delivery_date'];
                $laboratoryId = (int) ($hospitalMixtures->first()['laboratory_id'] ?? 0);
                $warehouseId = $warehouseIdsByLaboratory->get($laboratoryId);

                if (! $warehouseId) {
                    continue;
                }

                $route = $routesByHospital->get((int) $hospitalId);
                $schedule = $this->scheduleForLaboratory(
                    $laboratoryId,
                    $hospitalId,
                    $deliveryDate,
                    (int) $warehouseId
                );

                if (! $schedule->exists) {
                    $schedule->created_by = $request->user()->id;
                }

                if ($schedule->status !== 'sent') {
                    $schedule->distribution_route_id = $route?->id;
                    $schedule->status = $route ? 'scheduled' : 'pending';
                }

                $schedule->save();
                $scheduledCount++;
            }
        });

        if ($scheduledCount === 0) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'warning',
                    'title' => 'Central sin almacen activo',
                    'text' => 'No hay un almacen activo para surtir las mezclas de la central seleccionada.',
                ]);
        }

        return $this->scheduleRedirect($validated)
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Programacion creada',
                'text' => 'Los hospitales con mezclas disponibles fueron agregados a la programacion.',
            ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $usesLegacyDate = ! $request->filled('date_from');
        $validated = $request->validate([
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $validated['date_from'] = $validated['date_from'] ?? $validated['date'];
        $validated['date_to'] = $validated['date_to'] ?? $validated['date_from'];
        $validated['legacy_date'] = $usesLegacyDate;

        $hospitalMixtures = $this->deliveryMixtures($validated['date'], $validated['date'])
            ->filter(fn (array $mixture) => (int) $mixture['hospital_id'] === (int) $validated['hospital_id'])
            ->values();

        if ($hospitalMixtures->isEmpty()) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Sin mezclas disponibles',
                    'text' => 'El hospital ya no tiene mezclas disponibles para esta fecha.',
                ]);
        }

        $laboratoryId = (int) ($hospitalMixtures->first()['laboratory_id'] ?? 0);

        if (
            filled($validated['laboratory_id'] ?? null)
            && (int) $validated['laboratory_id'] !== $laboratoryId
        ) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Central incorrecta',
                    'text' => 'El hospital no pertenece a la central seleccionada.',
                ]);
        }

        $warehouseId = Warehouse::query()
            ->where('laboratory_id', $laboratoryId)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if (! $warehouseId) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'warning',
                    'title' => 'Central sin almacen activo',
                    'text' => 'No hay un almacen activo para surtir las mezclas de este hospital.',
                ]);
        }

        $route = $this->routesByHospital(
            DistributionRoute::query()->with('hospitals:id')->orderBy('schedule_start')->get()
        )->get((int) $validated['hospital_id']);

        if (! $route) {
            return $this->scheduleRedirect($validated)
                ->with('swal', [
                    'icon' => 'warning',
                    'title' => 'Hospital sin ruta',
                    'text' => 'Asigna el hospital a una ruta desde el Catalogo de Rutas.',
                ]);
        }

        $schedule = $this->scheduleForLaboratory(
            $laboratoryId,
            (int) $validated['hospital_id'],
            $validated['date'],
            (int) $warehouseId
        );
        $schedule->fill([
            'distribution_route_id' => $route->id,
            'status' => 'sent',
            'sent_at' => now(),
            'sent_by' => $request->user()->id,
        ]);
        $schedule->created_by ??= $request->user()->id;
        $schedule->save();

        return $this->scheduleRedirect($validated)
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Hospital enviado a ruta',
                'text' => 'La entrega quedo disponible para iniciar su recorrido.',
            ]);
    }

    private function deliveryMixtures(string $dateFrom, string $dateTo): Collection
    {
        $rangeStart = Carbon::createFromFormat('Y-m-d', $dateFrom, 'America/Mexico_City')->startOfDay();
        $rangeEnd = Carbon::createFromFormat('Y-m-d', $dateTo, 'America/Mexico_City')->endOfDay();

        $nutrition = NutritionSolicitud::query()
            ->whereIn('estado', self::NUTRITION_ELIGIBLE_STATES)
            ->whereHas(
                'solicitud_detail',
                fn ($query) => $query->whereBetween('fecha_hora_entrega', [$rangeStart, $rangeEnd])
            )
            ->with([
                'user:id,hospital_id',
                'user.hospital:id,name,laboratory_id',
                'solicitud_detail:id,fecha_hora_entrega',
                'solicitud_patient:id,nombre_paciente,apellidos_paciente,servicio',
            ])
            ->get()
            ->map(function (NutritionSolicitud $request) {
                $patient = trim(implode(' ', array_filter([
                    $request->solicitud_patient?->nombre_paciente,
                    $request->solicitud_patient?->apellidos_paciente,
                ])));
                $deliveryAt = Carbon::parse($request->solicitud_detail->fecha_hora_entrega);

                return [
                    'folio' => 'NPT-'.$deliveryAt->format('Y').'-'.str_pad((string) $request->id, 5, '0', STR_PAD_LEFT),
                    'patient' => $patient ?: 'Sin paciente',
                    'mixture' => 'Nutricion Parenteral',
                    'service' => $request->solicitud_patient?->servicio ?: 'Sin servicio',
                    'delivery_date' => $deliveryAt->toDateString(),
                    'time' => $deliveryAt->format('H:i'),
                    'status' => Str::headline($request->estado),
                    'hospital_id' => $request->user?->hospital_id,
                    'laboratory_id' => $request->user?->hospital?->laboratory_id,
                    'hospital' => $request->user?->hospital?->name ?? 'Sin hospital',
                    'url' => route('admin.nutricionales.solicitudes.edit', $request),
                ];
            });

        $oncology = Mezcla::query()
            ->whereIn('estado', self::ONCOLOGY_ELIGIBLE_STATES)
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereBetween('mezclas.fecha_entrega', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($legacyQuery) use ($rangeStart, $rangeEnd) {
                        $legacyQuery
                            ->whereNull('mezclas.fecha_entrega')
                            ->whereHas(
                                'solicitud',
                                fn ($requestQuery) => $requestQuery
                                    ->whereBetween('fecha_entrega', [$rangeStart, $rangeEnd])
                            );
                    });
            })
            ->whereHas('solicitud', function ($query) {
                $query->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos']);
            })
            ->with([
                'solicitud:id,hospital_id,tipo_solicitud,nombre_paciente,servicio,fecha_entrega',
                'solicitud.hospital:id,name,laboratory_id',
                'medicamentos:id,mezcla_id,nombre_medicamento',
            ])
            ->get()
            ->map(function (Mezcla $mixture) {
                $request = $mixture->solicitud;
                $deliveryAt = Carbon::parse($mixture->fecha_entrega ?? $request->fecha_entrega);
                $medicineNames = $mixture->medicamentos
                    ->pluck('nombre_medicamento')
                    ->filter()
                    ->unique()
                    ->join(', ');

                return [
                    'folio' => 'MZ-'.$deliveryAt->format('Y').'-'.str_pad((string) $mixture->id, 5, '0', STR_PAD_LEFT),
                    'patient' => $request->nombre_paciente ?: 'Sin paciente',
                    'mixture' => $medicineNames ?: ($request->tipo_solicitud === 'antibioticos' ? 'Mezcla antibiotica' : 'Mezcla oncologica'),
                    'service' => $request->servicio ?: 'Sin servicio',
                    'delivery_date' => $deliveryAt->toDateString(),
                    'time' => $deliveryAt->format('H:i'),
                    'status' => Str::headline($mixture->estado),
                    'hospital_id' => $request->hospital_id,
                    'laboratory_id' => $request->hospital?->laboratory_id,
                    'hospital' => $request->hospital?->name ?? 'Sin hospital',
                    'url' => route('admin.oncologicos.mezclas.edit', $mixture),
                ];
            });

        return $nutrition
            ->concat($oncology)
            ->sortBy(fn (array $mixture) => $mixture['delivery_date'].' '.$mixture['time'])
            ->values();
    }

    private function routesByHospital(Collection $routes): Collection
    {
        $routesByHospital = collect();
        $priority = [
            'active' => 0,
            'pending' => 1,
            'paused' => 2,
            'completed' => 3,
            'cancelled' => 4,
        ];

        $routes
            ->sortBy(fn (DistributionRoute $route) => $priority[$route->status] ?? 5)
            ->each(function (DistributionRoute $route) use ($routesByHospital) {
                foreach ($route->hospitals as $hospital) {
                    if (! $routesByHospital->has($hospital->id)) {
                        $routesByHospital->put($hospital->id, $route);
                    }
                }
            });

        return $routesByHospital;
    }

    private function scheduleForLaboratory(
        int $laboratoryId,
        int $hospitalId,
        string $date,
        int $warehouseId
    ): DistributionDeliverySchedule {
        return DistributionDeliverySchedule::query()
            ->where('hospital_id', $hospitalId)
            ->whereDate('scheduled_date', $date)
            ->whereHas('warehouse', fn ($query) => $query->where('laboratory_id', $laboratoryId))
            ->orderByDesc('sent_at')
            ->orderByDesc('updated_at')
            ->first()
            ?? new DistributionDeliverySchedule([
                'warehouse_id' => $warehouseId,
                'hospital_id' => $hospitalId,
                'scheduled_date' => $date,
            ]);
    }

    private function scheduleRedirect(array $values): RedirectResponse
    {
        $dateFrom = $this->validDate((string) ($values['date_from'] ?? ''))
            ?: $this->validDate((string) ($values['date'] ?? ''))
            ?: now('America/Mexico_City')->toDateString();
        $dateTo = $this->validDate((string) ($values['date_to'] ?? '')) ?: $dateFrom;

        $parameters = ($values['legacy_date'] ?? false) && $dateFrom === $dateTo
            ? ['date' => $dateFrom]
            : ['date_from' => $dateFrom, 'date_to' => $dateTo];

        if (filled($values['laboratory_id'] ?? null)) {
            $parameters['laboratory_id'] = $values['laboratory_id'];
        }

        return redirect()->route('admin.distribution.deliveries.index', $parameters);
    }

    private function deliveryGroupKey(int $hospitalId, string $date): string
    {
        return $date.'|'.$hospitalId;
    }

    private function validDate(string $date): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return '';
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $date : '';
    }
}
