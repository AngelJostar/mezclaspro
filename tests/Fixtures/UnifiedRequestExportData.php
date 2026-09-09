<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UnifiedRequestExportData
{
    public static function seed(): User
    {
        $user = InspectionWorkflow::seed();
        $user->forceFill(['hospital_id' => 1])->save();
        $user->syncRoles(Role::create(['name' => 'Institucion', 'guard_name' => 'web']));
        foreach (['nutricionales_solicitudes_index', 'oncologicos_solicitudes_index'] as $permission) {
            $user->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }
        User::forceCreate(['id' => 2, 'name' => 'Otro usuario', 'hospital_id' => 2]);
        DB::table('hospitals')->insert(['id' => 2, 'name' => 'Hospital ajeno']);

        foreach ([
            'solicituds' => ['user_id', 'solicitud_patient_id', 'solicitud_detail_id', 'estado', 'lote', 'remision'],
            'solicitud_patients' => ['nombre_paciente', 'apellidos_paciente'],
            'solicitud_details' => ['fecha_hora_entrega'],
            'distribution_delivery_schedules' => ['hospital_id', 'scheduled_date', 'status'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        Schema::table('solicitud_oncos', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nombre_paciente')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
        });

        foreach ([1, 2] as $id) {
            DB::table('solicitud_patients')->insert(['id' => $id, 'nombre_paciente' => 'Paciente '.$id, 'apellidos_paciente' => 'Nutricion']);
            DB::table('solicitud_details')->insert(['id' => $id, 'fecha_hora_entrega' => '2026-09-09 15:00:00']);
            DB::table('solicituds')->insert(['id' => 10 + $id, 'user_id' => $id, 'solicitud_patient_id' => $id,
                'solicitud_detail_id' => $id, 'estado' => 'aprobada', 'lote' => '00042', 'created_at' => '2026-09-08 09:30:00']);
        }
        foreach ([
            [1, 1, 'oncologicos', 'preparada', '2026-09-09 15:00:00'],
            [2, 1, 'antibioticos', 'revisada', '2026-09-10 16:00:00'],
            [3, 2, 'oncologicos', 'preparada', '2026-09-09 15:00:00'],
            [4, 1, 'antibioticos', 'pendiente', '2026-09-11 17:00:00'],
        ] as [$id, $hospital, $type, $status, $delivery]) {
            DB::table('solicitud_oncos')->updateOrInsert(['id' => $id], ['hospital_id' => $hospital, 'user_id' => $hospital,
                'tipo_solicitud' => $type, 'estado' => $status, 'nombre_paciente' => 'Paciente '.$id,
                'created_at' => '2026-09-08 10:00:00', 'fecha_entrega' => $delivery]);
            if ($id !== 4) {
                DB::table('mezclas')->updateOrInsert(['id' => $id], ['solicitud_id' => $id, 'estado' => $status,
                    'lote' => 'LOTE-'.$id, 'fecha_entrega' => $delivery, 'production_attempt' => 1]);
            }
        }
        DB::table('mezclas')->insert(['id' => 4, 'solicitud_id' => 1, 'estado' => 'entregada',
            'lote' => 'LOTE-4', 'fecha_entrega' => '2026-09-09 15:00:00', 'production_attempt' => 1]);
        DB::table('distribution_delivery_schedules')->insert(['hospital_id' => 1, 'scheduled_date' => '2026-09-10', 'status' => 'sent']);

        return $user;
    }
}
