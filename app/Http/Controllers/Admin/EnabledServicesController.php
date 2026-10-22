<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Support\EnabledServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnabledServicesController extends Controller
{
    public function edit(Institucion $institucion, ?Hospital $hospital = null): JsonResponse
    {
        $this->checkHospital($institucion, $hospital);

        return response()->json($this->state($institucion, $hospital))->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Institucion $institucion, ?Hospital $hospital = null): JsonResponse
    {
        $this->checkHospital($institucion, $hospital);
        $data = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'services' => ['required', 'array:'.implode(',', array_keys(EnabledServices::LABELS))],
            'services.quotation_requests' => $hospital ? ['present', 'nullable', 'boolean'] : ['required', 'boolean'],
            'apply_to_all' => $hospital ? ['prohibited'] : ['sometimes', 'boolean'],
            'hospital_services' => $hospital ? ['prohibited'] : ['sometimes', 'array'],
            'hospital_services.*' => ['array:id,services'],
            'hospital_services.*.id' => ['required', 'integer', 'distinct'],
            'hospital_services.*.services' => ['required', 'array:'.implode(',', array_keys(EnabledServices::LABELS))],
            'hospital_services.*.services.quotation_requests' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $institucion, $hospital) {
            $institution = Institucion::whereKey($institucion->id)->lockForUpdate()->firstOrFail();
            $target = $hospital ? Hospital::whereKey($hospital->id)->lockForUpdate()->firstOrFail() : $institution;
            $this->checkHospital($institution, $hospital ? $target : null);
            $hospitals = $hospital ? collect() : $institution->hospitals()->orderBy('hospitals.id')->lockForUpdate()->get();
            if (array_key_exists('hospital_services', $data)) {
                $ids = collect($data['hospital_services'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                abort_unless($ids === $hospitals->modelKeys(), 422,
                    'La lista debe incluir todos los hospitales de esta institución. Cierra y vuelve a abrir la ventana.');
            }
            abort_unless(hash_equals($this->state($institution, $hospital ? $target : null)['version'], $data['version']),
                409, 'La configuración cambió. Cierra y vuelve a abrir la ventana para cargar los valores actuales.');

            $settings = $target->enabled_services ?? [];
            foreach ($data['services'] as $service => $enabled) {
                if ($enabled === null) unset($settings[$service]);
                else $settings[$service] = (bool) $enabled;
            }
            $target->update(['enabled_services' => $settings]);

            if (! $hospital && array_key_exists('hospital_services', $data)) {
                $rows = collect($data['hospital_services'])->keyBy('id');
                foreach ($hospitals as $linkedHospital) {
                    $overrides = $linkedHospital->enabled_services ?? [];
                    foreach ($rows[$linkedHospital->id]['services'] as $service => $enabled) {
                        $overrides[$service] = (bool) $enabled;
                    }
                    $linkedHospital->update(['enabled_services' => $overrides]);
                }
            } elseif (! $hospital && ($data['apply_to_all'] ?? false)) {
                foreach ($hospitals as $linkedHospital) {
                    // Applying to all is explicit, including hospitals hidden by filters or pagination.
                    $overrides = $linkedHospital->enabled_services ?? [];
                    foreach ($data['services'] as $service => $enabled) {
                        $overrides[$service] = (bool) $enabled;
                    }
                    $linkedHospital->update(['enabled_services' => $overrides]);
                }
            }
        }, 3);

        return response()->json(['message' => 'Servicios actualizados correctamente.']);
    }

    private function checkHospital(Institucion $institution, ?Hospital $hospital): void
    {
        abort_if($hospital && ! $institution->hospitals()->whereKey($hospital->id)->exists(), 404);
    }

    private function state(Institucion $institution, ?Hospital $hospital): array
    {
        $linked = $hospital
            ? $hospital->instituciones()->orderBy('clientes.id')->get()
            : $institution->hospitals()->with('instituciones')->orderBy('hospitals.id')->get();
        if ($hospital) $hospital->setRelation('instituciones', $linked);

        return [
            'scope' => $hospital ? 'hospital' : 'institution',
            'name' => $hospital?->name ?? $institution->nombre,
            'hospital_count' => $hospital ? null : $linked->count(),
            'hospitals' => ($hospital ? collect([$hospital]) : $linked)->map(fn ($item) => [
                'id' => $item->id, 'name' => $item->name,
                'services' => collect(EnabledServices::LABELS)->map(fn ($label, $key) => EnabledServices::hospital($item, $key)),
            ])->values(),
            'services' => collect(EnabledServices::LABELS)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label,
                'value' => $hospital ? ($hospital->enabled_services[$key] ?? null) : EnabledServices::institution($institution, $key),
                'inherited' => $hospital ? EnabledServices::inherited($hospital, $key) : null,
                'enabled_count' => $hospital ? null : $linked->filter(fn ($item) => EnabledServices::hospital($item, $key))->count(),
            ])->values(),
            'version' => hash('sha256', json_encode([
                ($hospital ?? $institution)->enabled_services,
                $linked->map(fn ($item) => [$item->id, $item->enabled_services])->all(),
            ], JSON_THROW_ON_ERROR)),
        ];
    }
}
