<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Oncologicos\Laboratory;
use App\Models\User;
use App\Support\HospitalMapCoordinates;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DistributionRouteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $allowedStatuses = ['all', 'pending', 'active', 'completed', 'paused', 'cancelled'];
        $status = (string) $request->query('status', 'all');
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $allowedSorts = ['id', 'name', 'route_type', 'code', 'schedule', 'status', 'hospitals', 'stops'];
        $sort = (string) $request->query('sort', 'id');
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'id';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $selectedLaboratory = $request->integer('laboratory_id') > 0
            ? $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            : null;

        $routeCountsByLaboratory = DB::table('distribution_route_hospital')
            ->join('hospitals', 'hospitals.id', '=', 'distribution_route_hospital.hospital_id')
            ->whereIn('hospitals.laboratory_id', $laboratories->pluck('id'))
            ->select(
                'hospitals.laboratory_id',
                DB::raw('COUNT(DISTINCT distribution_route_hospital.distribution_route_id) as routes_count')
            )
            ->groupBy('hospitals.laboratory_id')
            ->pluck('routes_count', 'laboratory_id');

        $laboratories->each(function (Laboratory $laboratory) use ($routeCountsByLaboratory): void {
            $laboratory->setAttribute('routes_count', (int) $routeCountsByLaboratory->get($laboratory->id, 0));
        });

        $query = DistributionRoute::query()
            ->with(['hospitals', 'messengers'])
            ->withCount('hospitals');

        if ($selectedLaboratory) {
            $query->whereHas(
                'hospitals',
                fn ($hospitalQuery) => $hospitalQuery->where('laboratory_id', $selectedLaboratory->id)
            );
        }

        if ($search !== '') {
            $query->where(function ($routeQuery) use ($search) {
                $routeQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('hospitals', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('messengers', function ($messengerQuery) use ($search) {
                        $messengerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        match ($sort) {
            'name' => $query->orderBy('name', $direction),
            'route_type' => $query->orderBy('route_type', $direction),
            'code' => $query->orderBy('code', $direction),
            'schedule' => $query->orderBy('schedule_start', $direction),
            'status' => $query->orderBy('status', $direction),
            'hospitals', 'stops' => $query->orderBy('hospitals_count', $direction),
            default => $query->orderBy('id', $direction),
        };

        $distributionRoutes = $query
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $editingRouteId = (int) ($request->old('route_id') ?: $request->query('edit'));
        $editingRoute = $editingRouteId > 0
            ? DistributionRoute::query()->with('hospitals')->find($editingRouteId)
            : null;

        $routeHospitals = Hospital::query()
            ->with('distributionRoutes')
            ->orderBy('name')
            ->get()
            ->map(function (Hospital $hospital): array {
                $assignedRoute = $hospital->distributionRoutes->sortByDesc('id')->first();
                $coordinates = HospitalMapCoordinates::resolve($hospital);
                $address = trim((string) ($hospital->getAttribute('adress') ?: collect([
                    $hospital->getAttribute('neighborhood'),
                    $hospital->getAttribute('municipality'),
                    $hospital->getAttribute('state'),
                    $hospital->getAttribute('postal_code'),
                ])->filter()->join(', ')));

                return [
                    'id' => $hospital->id,
                    'name' => $hospital->name,
                    'address' => $address,
                    'latitude' => $coordinates['latitude'],
                    'longitude' => $coordinates['longitude'],
                    'estimated' => $coordinates['estimated'],
                    'has_coordinates' => HospitalMapCoordinates::hasStoredCoordinates($hospital),
                    'available' => $assignedRoute === null,
                    'assigned_route_id' => $assignedRoute?->id,
                    'assigned_route' => $assignedRoute?->name,
                ];
            })
            ->values();

        return view('admin.distribution.routes.index', compact(
            'distributionRoutes',
            'search',
            'status',
            'sort',
            'direction',
            'laboratories',
            'selectedLaboratory',
            'routeHospitals',
            'editingRoute'
        ));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.distribution.routes.index', ['create' => 1]);
    }

    public function edit(DistributionRoute $distributionRoute): RedirectResponse
    {
        return redirect()->route('admin.distribution.routes.index', ['edit' => $distributionRoute->id]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'route_type' => Str::lower(trim((string) $request->input('route_type', 'vehicular'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'route_type' => ['required', 'string', 'in:vehicular,dron'],
            'hospital_ids' => ['required', 'array', 'min:1'],
            'hospital_ids.*' => ['integer', 'distinct', 'exists:hospitals,id'],
        ]);

        $hospitalIds = collect($validated['hospital_ids'])->map(fn ($id) => (int) $id)->values();

        $distributionRoute = DB::transaction(function () use ($request, $validated, $hospitalIds) {
            $hospitals = Hospital::query()
                ->whereIn('id', $hospitalIds)
                ->lockForUpdate()
                ->get(['id', 'name', 'latitude', 'longitude']);

            $this->validateDroneHospitalCoordinates($validated['route_type'], $hospitals);

            $assignedHospitalNames = DB::table('distribution_route_hospital')
                ->join('hospitals', 'hospitals.id', '=', 'distribution_route_hospital.hospital_id')
                ->whereIn('distribution_route_hospital.hospital_id', $hospitalIds)
                ->distinct()
                ->orderBy('hospitals.name')
                ->pluck('hospitals.name');

            if ($assignedHospitalNames->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'hospital_ids' => 'Estos hospitales ya pertenecen a otra ruta: '.$assignedHospitalNames->join(', ').'.',
                ]);
            }

            $distributionRoute = DistributionRoute::query()->create([
                'name' => $validated['name'],
                'code' => $this->nextRouteCode(),
                'route_type' => $validated['route_type'],
                'schedule_start' => '07:00',
                'schedule_end' => '18:00',
                'status' => 'pending',
                'qr_token' => (string) Str::uuid(),
                'created_by' => $request->user()->id,
            ]);

            $hospitalAssignments = $hospitalIds
                ->values()
                ->mapWithKeys(fn ($hospitalId, $index) => [
                    (int) $hospitalId => ['stop_order' => $index + 1],
                ]);

            $distributionRoute->hospitals()->sync($hospitalAssignments);

            return $distributionRoute;
        });

        return redirect()
            ->route('admin.distribution.routes.index')
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Ruta creada',
                'text' => 'La nueva ruta quedó disponible en el catálogo.',
            ]);
    }

    public function update(Request $request, DistributionRoute $distributionRoute): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'route_type' => Str::lower(trim((string) $request->input('route_type', $distributionRoute->route_type ?: 'vehicular'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'route_type' => ['required', 'string', 'in:vehicular,dron'],
            'hospital_ids' => ['required', 'array', 'min:1'],
            'hospital_ids.*' => ['integer', 'distinct', 'exists:hospitals,id'],
        ]);

        $hospitalIds = collect($validated['hospital_ids'])->map(fn ($id) => (int) $id)->values();

        DB::transaction(function () use ($distributionRoute, $validated, $hospitalIds) {
            $currentHospitalIds = $distributionRoute->hospitals()
                ->pluck('hospitals.id')
                ->map(fn ($id) => (int) $id);

            $hospitals = Hospital::query()
                ->whereIn('id', $hospitalIds->merge($currentHospitalIds)->unique())
                ->lockForUpdate()
                ->get(['id', 'name', 'latitude', 'longitude']);

            $selectedHospitals = $hospitals->whereIn('id', $hospitalIds)->values();
            $this->validateDroneHospitalCoordinates($validated['route_type'], $selectedHospitals);

            $assignedHospitalNames = DB::table('distribution_route_hospital')
                ->join('hospitals', 'hospitals.id', '=', 'distribution_route_hospital.hospital_id')
                ->whereIn('distribution_route_hospital.hospital_id', $hospitalIds)
                ->where('distribution_route_hospital.distribution_route_id', '!=', $distributionRoute->id)
                ->distinct()
                ->orderBy('hospitals.name')
                ->pluck('hospitals.name');

            if ($assignedHospitalNames->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'hospital_ids' => 'Estos hospitales ya pertenecen a otra ruta: '.$assignedHospitalNames->join(', ').'.',
                ]);
            }

            $distributionRoute->update([
                'name' => $validated['name'],
                'route_type' => $validated['route_type'],
            ]);

            $hospitalAssignments = $hospitalIds
                ->values()
                ->mapWithKeys(fn ($hospitalId, $index) => [
                    (int) $hospitalId => ['stop_order' => $index + 1],
                ]);

            $distributionRoute->hospitals()->sync($hospitalAssignments);
        });

        return redirect()
            ->route('admin.distribution.routes.index')
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Ruta actualizada',
                'text' => 'La información de la ruta se guardó correctamente.',
            ]);
    }

    private function nextRouteCode(): string
    {
        $sequence = ((int) DistributionRoute::query()->lockForUpdate()->max('id')) + 1;

        do {
            $code = sprintf('R-CDMX-%03d', $sequence);
            $sequence++;
        } while (DistributionRoute::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @param  Collection<int, Hospital>  $hospitals
     */
    private function validateDroneHospitalCoordinates(string $routeType, Collection $hospitals): void
    {
        if ($routeType !== 'dron') {
            return;
        }

        $hospitalsWithoutCoordinates = $hospitals
            ->reject(fn (Hospital $hospital) => HospitalMapCoordinates::hasStoredCoordinates($hospital))
            ->pluck('name')
            ->filter()
            ->sort()
            ->values();

        if ($hospitalsWithoutCoordinates->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'hospital_ids' => 'Para crear una ruta de dron, todos los hospitales deben tener coordenadas registradas. Faltan coordenadas en: '
                .$hospitalsWithoutCoordinates->join(', ').'.',
        ]);
    }

    public function messengers(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $messengers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Mensajero'))
            ->with(['warehouse:id,name,laboratory_id', 'warehouse.laboratory:id,nombre'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($messengerQuery) use ($search) {
                    $messengerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('lastname')
            ->paginate(10)
            ->withQueryString();

        return view('admin.distribution.messengers.index', compact('messengers', 'search'));
    }

    public function qr(DistributionRoute $distributionRoute): Response
    {
        $renderer = new ImageRenderer(
            new RendererStyle(280, 2),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString("distribution-route:{$distributionRoute->qr_token}");

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'inline; filename="ruta-'.$distributionRoute->code.'.svg"');
    }
}
