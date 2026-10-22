<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstitutionMixturesController extends Controller
{
    private const TYPES = ['oncology' => 'Oncológicos', 'antibiotics' => 'Antibióticos', 'nutrition' => 'Nutricionales'];

    public function edit(Institucion $institucion)
    {
        $hospitals = $institucion->hospitals()->orderBy('hospitals.id')->get();

        return response()->json([
            'title' => 'Mezclas habilitadas', 'scope' => 'institution', 'name' => $institucion->nombre,
            'can_save' => $hospitals->isNotEmpty(), 'empty_message' => 'Esta institución no tiene hospitales asignados.',
            'services' => collect(self::TYPES)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label,
                'value' => $hospitals->isNotEmpty() && $hospitals->every(fn ($hospital) => $hospital->offersService($key)),
            ])->values(),
            'hospitals' => $hospitals->map(fn ($hospital) => [
                'id' => $hospital->id, 'name' => $hospital->name,
                'services' => collect(self::TYPES)->map(fn ($label, $key) => $hospital->offersService($key)),
            ])->values(),
            'version' => $this->version($hospitals),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Institucion $institucion)
    {
        $rules = [
            'version' => ['required', 'string', 'size:64'],
            'hospital_services' => ['required', 'array', 'min:1'],
            'hospital_services.*' => ['array:id,services'],
            'hospital_services.*.id' => ['required', 'integer', 'distinct'],
            'hospital_services.*.services' => ['required', 'array:'.implode(',', array_keys(self::TYPES))],
        ];
        foreach (array_keys(self::TYPES) as $key) $rules['hospital_services.*.services.'.$key] = ['required', 'boolean'];
        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $institucion) {
            $institution = Institucion::whereKey($institucion->id)->lockForUpdate()->firstOrFail();
            $hospitals = $institution->hospitals()->orderBy('hospitals.id')->lockForUpdate()->get();
            $ids = collect($data['hospital_services'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            abort_unless($ids === $hospitals->modelKeys(), 422,
                'La lista debe incluir todos los hospitales de esta institución. Cierra y vuelve a abrir la ventana.');
            abort_unless(hash_equals($this->version($hospitals), $data['version']), 409,
                'Las mezclas habilitadas cambiaron. Cierra y vuelve a abrir la ventana para cargar los valores actuales.');
            $rows = collect($data['hospital_services'])->keyBy('id');
            foreach ($hospitals as $hospital) {
                foreach (array_keys(self::TYPES) as $key) {
                    $hospital->setAttribute('service_'.$key, (bool) $rows[$hospital->id]['services'][$key]);
                }
                $hospital->service_lines_configured = true;
                $hospital->save();
            }
        }, 3);

        return response()->json(['message' => 'Mezclas habilitadas actualizadas.']);
    }

    private function version(Collection $hospitals): string
    {
        return hash('sha256', json_encode($hospitals->map(fn ($hospital) => [
            $hospital->id, $hospital->service_lines_configured,
            $hospital->service_oncology, $hospital->service_antibiotics, $hospital->service_nutrition,
            $hospital->onco_medicine_list_id, $hospital->nutri_medicine_list_id,
        ])->all(), JSON_THROW_ON_ERROR));
    }
}
