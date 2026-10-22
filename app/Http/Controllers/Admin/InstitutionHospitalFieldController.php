<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstitutionHospitalFieldController extends Controller
{
    private const SERVICES = ['oncology' => 'Oncológicos', 'antibiotics' => 'Antibióticos', 'nutrition' => 'Nutricionales'];

    public function edit(Institucion $institucion, Hospital $hospital, string $field): JsonResponse
    {
        $this->checkHospital($institucion, $hospital);
        $selected = $this->selected($hospital, $field);
        $options = match ($field) {
            'services' => collect(self::SERVICES)->map(fn ($label, $id) => compact('id', 'label'))->values(),
            'routes' => DistributionRoute::orderBy('name')->get(['id', 'name', 'code'])
                ->map(fn ($route) => ['id' => (string) $route->id, 'label' => $route->name.' · '.$route->code]),
            'sellers' => User::where(fn ($query) => $query->activeSalespeople()->orWhereIn('id', $selected))
                ->orderBy('name')->get(['id', 'name', 'lastname', 'is_active'])
                ->map(fn ($user) => ['id' => (string) $user->id, 'label' => trim($user->name.' '.$user->lastname)
                    .($user->is_active ? '' : ' (Inactivo)')]),
        };

        return response()->json([
            'hospital' => $hospital->name,
            'title' => match ($field) {
                'services' => 'Editar mezclas habilitadas',
                'routes' => 'Editar ruta asignada',
                'sellers' => 'Editar vendedor asignado',
            },
            'options' => $options, 'selected' => $selected,
            'version' => $this->version($selected),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Institucion $institucion, Hospital $hospital, string $field): JsonResponse
    {
        $this->checkHospital($institucion, $hospital);
        $data = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'values' => ['present', 'array', 'max:100'],
            'values.*' => $field === 'services'
                ? ['string', 'distinct', Rule::in(array_keys(self::SERVICES))]
                : ['integer', 'distinct', 'min:1', Rule::exists($field === 'routes' ? 'distribution_routes' : 'users', 'id')],
        ]);

        DB::transaction(function () use ($hospital, $field, $data) {
            $hospital = Hospital::whereKey($hospital->id)->lockForUpdate()->firstOrFail();
            $current = $this->selected($hospital, $field);
            abort_unless(hash_equals($this->version($current), $data['version']), 409,
                'Este dato cambió mientras lo editabas. Cierra y vuelve a abrir la ventana para cargarlo.');

            if ($field === 'services') {
                foreach (array_keys(self::SERVICES) as $service) {
                    $hospital->setAttribute('service_'.$service, in_array($service, $data['values'], true));
                }
                $hospital->service_lines_configured = true;
                $hospital->save();
            } elseif ($field === 'sellers') {
                $newIds = array_diff($data['values'], $current);
                if (User::activeSalespeople()->whereIn('id', $newIds)->count() !== count($newIds)) {
                    throw ValidationException::withMessages(['values' => 'Selecciona vendedores activos.']);
                }
                $hospital->salespeople()->sync($data['values']);
            } else {
                // Keep existing stop order/completion; append new hospitals at the end of each route.
                $routes = DistributionRoute::whereIn('id', array_unique([...$current, ...$data['values']]))
                    ->orderBy('id')->lockForUpdate()->get();
                $assignments = [];
                foreach ($routes as $route) {
                    if (! in_array((string) $route->id, array_map('strval', $data['values']), true)) {
                        continue;
                    }
                    $assignments[$route->id] = in_array((string) $route->id, $current, true) ? [] : [
                        'stop_order' => (int) DB::table('distribution_route_hospital')
                            ->where('distribution_route_id', $route->id)->max('stop_order') + 1,
                    ];
                }
                $hospital->distributionRoutes()->sync($assignments);
            }
        });

        return response()->json(['message' => 'Cambios guardados correctamente.']);
    }

    private function checkHospital(Institucion $institucion, Hospital $hospital): void
    {
        abort_unless($institucion->hospitals()->whereKey($hospital->id)->exists(), 404);
    }

    private function selected(Hospital $hospital, string $field): array
    {
        return match ($field) {
            'services' => array_values(array_filter(array_keys(self::SERVICES), fn ($service) => $hospital->offersService($service))),
            'routes' => $hospital->distributionRoutes()->pluck('distribution_routes.id')->map(fn ($id) => (string) $id)->all(),
            'sellers' => $hospital->salespeople()->pluck('users.id')->map(fn ($id) => (string) $id)->all(),
        };
    }

    private function version(array $selected): string
    {
        sort($selected);

        return hash('sha256', json_encode($selected, JSON_THROW_ON_ERROR));
    }
}
