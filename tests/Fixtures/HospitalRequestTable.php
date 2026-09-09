<?php

namespace Tests\Fixtures;

use App\Models\Hospital;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\SolicitudOnco;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;

class HospitalRequestTable
{
    public static function render(string $category, string $role, bool $empty = false): string
    {
        // Render real views with unsaved models; never change hospital or request records.
        $hospital = (new Hospital())->forceFill(['id' => 1, 'name' => 'Hospital de prueba']);
        $hospital->setRelation('instituciones', new Collection());
        $user = (new User())->forceFill(['id' => 1, 'name' => 'Usuario de prueba', 'hospital_id' => 1]);
        $user->setRelation('hospital', $hospital);
        $user->setRelation('roles', new Collection([(new Role())->forceFill(['name' => $role, 'guard_name' => 'web'])]));
        auth()->setUser($user);

        $nutrition = (new Solicitud())->forceFill([
            'id' => 17, 'estado' => 'aprobada', 'lote' => 'NUT-001', 'created_at' => now(),
        ])->setRelation('user', $user)
            ->setRelation('solicitud_patient', new SolicitudPatient(['nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba']))
            ->setRelation('solicitud_detail', new SolicitudDetail(['fecha_hora_entrega' => now()->addDay()]));
        $mixtures = collect(['oncologicos', 'antibioticos'])->mapWithKeys(function ($type, $index) use ($hospital, $user) {
            $request = (new SolicitudOnco())->forceFill([
                'id' => 24 + $index, 'tipo_solicitud' => $type, 'estado' => 'preparada',
                'nombre_paciente' => 'Paciente de '.$type, 'created_at' => now(), 'fecha_entrega' => now()->addDay(),
            ])->setRelation('hospital', $hospital)->setRelation('user', $user);

            return [$type => (new Mezcla())->forceFill([
                'id' => 38 + $index, 'solicitud_id' => $request->id, 'estado' => 'preparada',
                'lote' => 'LOTE-'.$type, 'fecha_entrega' => now()->addDay(), 'production_attempt' => 1,
            ])->setRelation('solicitud', $request)];
        });

        if ($category === 'todas') {
            $requests = collect(['nutricionales', 'oncologicos', 'antibioticos'])->map(function ($type) use ($nutrition, $mixtures, $hospital) {
                $mixture = $mixtures->get($type);
                $model = $mixture?->solicitud ?? $nutrition;

                return [
                    'type' => $type, 'model' => $model, 'mixture' => $mixture,
                    'id' => $mixture?->id ?? $model->id, 'request_id' => $model->id,
                    'hospital' => $hospital->name, 'patient' => 'Paciente de '.$type,
                    'status' => $model->estado, 'lot' => $mixture?->lote ?? $model->lote,
                    'requested_at' => now(), 'delivery_at' => now()->addDay(),
                ];
            });
            $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '',
                file_get_contents(resource_path('views/admin/solicitudes/index.blade.php')));

            return Blade::render($source, [
                'requests' => $empty ? collect() : $requests, 'statusFilter' => 'todas',
                'pendingApprovalCount' => 0, 'routePendingCount' => 0, 'deliveryPendingCount' => 0,
                'canViewNutrition' => false, 'canViewOncology' => false,
            ]);
        }

        $records = $empty ? [] : [$category === 'nutricionales' ? $nutrition : $mixtures[$category]];
        $viewCategory = $category === 'nutricionales' ? 'nutricionales' : 'oncologicos';

        return view('livewire.'.$viewCategory.'.solicitudes-table', [
            $category === 'nutricionales' ? 'solicitudes' : 'mezclas' => new LengthAwarePaginator($records, count($records), 50),
            'sortField' => 'id', 'sortDirection' => 'asc',
        ])->render();
    }
}
