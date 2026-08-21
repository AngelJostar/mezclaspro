<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Oncologicos\Laboratory;
use App\Models\PersonnelProfile;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $messengerId = $request->integer('messenger_id') ?: null;
        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado', 'direccion', 'activo']);
        $selectedLaboratory = $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            ?? $laboratories->first();
        $routeCountsByLaboratory = $laboratories->isEmpty()
            ? collect()
            : DB::table('distribution_route_hospital as route_hospitals')
                ->join('hospitals', 'hospitals.id', '=', 'route_hospitals.hospital_id')
                ->whereIn('hospitals.laboratory_id', $laboratories->pluck('id'))
                ->groupBy('hospitals.laboratory_id')
                ->selectRaw('hospitals.laboratory_id, COUNT(DISTINCT route_hospitals.distribution_route_id) as aggregate')
                ->pluck('aggregate', 'hospitals.laboratory_id');

        $routes = DistributionRoute::query()
            ->with([
                'hospitals:id,name,short_name',
                'messengers:id,name,lastname',
            ])
            ->matching($search)
            ->when($selectedLaboratory, fn (Builder $query) => $query->whereHas(
                'hospitals',
                fn (Builder $hospitalQuery) => $hospitalQuery->where('hospitals.laboratory_id', $selectedLaboratory->id)
            ))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($messengerId, fn (Builder $query) => $query->whereHas('messengers', fn (Builder $messengerQuery) => $messengerQuery->whereKey($messengerId)))
            ->orderByRaw("CASE status WHEN 'in_route' THEN 1 WHEN 'delayed' THEN 2 WHEN 'pending' THEN 3 ELSE 4 END")
            ->orderBy('schedule_start')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.distribution.index', [
            'routes' => $routes,
            'messengers' => $this->messengerQuery()->get(['users.id', 'users.name', 'users.lastname']),
            'statuses' => DistributionRoute::statuses(),
            'filters' => compact('search', 'status', 'messengerId'),
            'laboratories' => $laboratories,
            'selectedLaboratory' => $selectedLaboratory,
            'routeCountsByLaboratory' => $routeCountsByLaboratory,
        ]);
    }

    public function create(): View
    {
        return view('admin.distribution.create', $this->formData(new DistributionRoute()));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $route = DB::transaction(function () use ($validated, $request) {
            $route = DistributionRoute::create([
                ...Arr::except($validated, ['hospital_ids', 'messenger_ids']),
                'code' => $validated['code'] ?: $this->nextRouteCode(),
                'created_by' => $request->user()?->id,
            ]);

            $this->syncAssignments($route, $validated);

            return $route;
        });

        return redirect()
            ->route('admin.distribution.show', $route)
            ->with('success', 'Ruta de distribución creada correctamente.');
    }

    public function show(DistributionRoute $distributionRoute): View
    {
        $distributionRoute->load([
            'hospitals:id,name,short_name,adress,google_maps_url',
            'messengers:id,name,lastname,username',
            'creator:id,name,lastname',
        ]);

        return view('admin.distribution.show', [
            'distributionRoute' => $distributionRoute,
            'statuses' => DistributionRoute::statuses(),
        ]);
    }

    public function edit(DistributionRoute $distributionRoute): View
    {
        $distributionRoute->load(['hospitals:id', 'messengers:id']);

        return view('admin.distribution.edit', $this->formData($distributionRoute));
    }

    public function update(Request $request, DistributionRoute $distributionRoute): RedirectResponse
    {
        $validated = $this->validatedData($request, $distributionRoute);

        DB::transaction(function () use ($distributionRoute, $validated) {
            $distributionRoute->update(Arr::except($validated, ['hospital_ids', 'messenger_ids']));
            $this->syncAssignments($distributionRoute, $validated);
        });

        return redirect()
            ->route('admin.distribution.show', $distributionRoute)
            ->with('success', 'Ruta de distribución actualizada correctamente.');
    }

    public function updateStatus(Request $request, DistributionRoute $distributionRoute): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(DistributionRoute::statuses()))],
        ]);

        $distributionRoute->update($validated);

        return back()->with('success', 'Estatus de la ruta actualizado.');
    }

    public function couriers(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $messengers = $this->messengerQuery()
            ->with(['personnelProfile.laboratory:id,nombre'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.lastname', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%")
                        ->orWhereHas('personnelProfile', function (Builder $profileQuery) use ($search) {
                            $profileQuery
                                ->where('phone', 'like', "%{$search}%")
                                ->orWhere('department', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('users.name')
            ->orderBy('users.lastname')
            ->paginate(100)
            ->withQueryString();

        return view('admin.distribution.couriers', compact('messengers', 'search'));
    }

    public function qr(Request $request, DistributionRoute $distributionRoute): Response
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString(
            route('admin.distribution.show', $distributionRoute).'?token='.$distributionRoute->qr_token
        );

        $headers = ['Content-Type' => 'image/svg+xml; charset=UTF-8'];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="'.$distributionRoute->code.'.svg"';
        }

        return response($svg, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(DistributionRoute $distributionRoute): array
    {
        $hospitals = Hospital::query()
            ->with(['instituciones:id,nombre'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'short_name',
                'municipality',
                'postal_code',
                'state',
                'adress',
                'street_number',
                'neighborhood',
            ]);

        $hospitalRouteAssignments = $hospitals->isEmpty()
            ? collect()
            : DB::table('distribution_route_hospital as assignments')
                ->join('distribution_routes as routes', 'routes.id', '=', 'assignments.distribution_route_id')
                ->whereIn('assignments.hospital_id', $hospitals->pluck('id'))
                ->where('routes.status', '!=', DistributionRoute::STATUS_COMPLETED)
                ->when(
                    $distributionRoute->exists,
                    fn ($query) => $query->where('routes.id', '!=', $distributionRoute->id)
                )
                ->orderBy('routes.name')
                ->get([
                    'assignments.hospital_id',
                    'routes.id',
                    'routes.name',
                    'routes.code',
                ])
                ->unique('hospital_id')
                ->keyBy('hospital_id');

        return [
            'distributionRoute' => $distributionRoute,
            'hospitals' => $hospitals,
            'hospitalRouteAssignments' => $hospitalRouteAssignments,
            'messengers' => $this->messengerQuery()->get([
                'users.id',
                'users.name',
                'users.lastname',
                'users.username',
            ]),
            'statuses' => DistributionRoute::statuses(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?DistributionRoute $distributionRoute = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('distribution_routes', 'code')->ignore($distributionRoute),
            ],
            'schedule_start' => ['required', 'date_format:H:i'],
            'schedule_end' => ['required', 'date_format:H:i', 'after:schedule_start'],
            'status' => ['required', Rule::in(array_keys(DistributionRoute::statuses()))],
            'hospital_ids' => ['required', 'array', 'min:1'],
            'hospital_ids.*' => ['integer', 'distinct', Rule::exists('hospitals', 'id')->where('is_active', true)],
            'messenger_ids' => ['required', 'array', 'min:1'],
            'messenger_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_active', true)],
        ], [
            'hospital_ids.required' => 'Selecciona al menos un hospital para la ruta.',
            'messenger_ids.required' => 'Selecciona al menos un mensajero para la ruta.',
            'schedule_end.after' => 'La hora de término debe ser posterior a la hora de inicio.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncAssignments(DistributionRoute $route, array $validated): void
    {
        $hospitalAssignments = collect($validated['hospital_ids'])
            ->values()
            ->mapWithKeys(fn ($hospitalId, $index) => [
                $hospitalId => ['stop_order' => $index + 1],
            ])
            ->all();

        $route->hospitals()->sync($hospitalAssignments);
        $route->messengers()->sync($validated['messenger_ids']);
    }

    private function nextRouteCode(): string
    {
        $nextNumber = (int) DistributionRoute::query()->max('id') + 1;

        do {
            $code = 'RUT-CDMX-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (DistributionRoute::query()->where('code', $code)->exists());

        return $code;
    }

    private function messengerQuery(): Builder
    {
        return User::query()
            ->where('users.is_active', true)
            ->whereHas('personnelProfile', fn (Builder $query) => $query
                ->where('employment_status', 'hired')
                ->whereJsonContains('positions', PersonnelProfile::POSITION_COURIER))
            ->whereDoesntHave('roles', fn (Builder $query) => $query->whereIn('name', ['Cliente', 'Institucion']));
    }
}
