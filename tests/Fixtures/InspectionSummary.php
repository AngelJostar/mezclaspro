<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectionSummary
{
    public static function seed(): User
    {
        $user = InspectionWorkflow::seed();
        foreach ([
            'solicitud_oncos' => ['nombre_paciente', 'registro_paciente', 'sexo', 'edad', 'peso', 'servicio', 'piso', 'cama', 'nombre_medico'],
            'mezcla_medicamentos' => ['diluyente_id', 'via_administracion_id'],
            'diluent_presentations' => ['diluent_id'],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) $table->string($column)->nullable();
            });
        }
        foreach (['diluents' => 'denominacion_generica', 'administration_routes' => 'name'] as $table => $column) {
            Schema::create($table, function (Blueprint $table) use ($column) {
                $table->id();
                $table->string($column);
                $table->timestamps();
            });
        }
        DB::table('solicitud_oncos')->where('id', 1)->update([
            'nombre_paciente' => 'Paciente de prueba con nombre y apellidos extensos',
            'registro_paciente' => 'EXP-12345', 'sexo' => 'F', 'edad' => 42, 'peso' => 64.5,
            'servicio' => 'Servicio de prueba', 'piso' => '2', 'cama' => '205-B',
            'nombre_medico' => 'Nombre del medico de prueba',
        ]);
        DB::table('clientes')->insert(['id' => 1, 'nombre' => 'Institucion de prueba']);
        DB::table('cliente_hospital')->insert(['hospital_id' => 1, 'cliente_id' => 1]);
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Diluyente de prueba']);
        DB::table('administration_routes')->insert(['id' => 1, 'name' => 'INTRAVENOSA']);
        DB::table('diluent_presentations')->where('id', 1)->update(['diluent_id' => 1]);
        DB::table('mezcla_medicamentos')->where('id', 1)->update(['diluyente_id' => 1, 'via_administracion_id' => 1]);
        DB::table('mezcla_medicamentos')->insert([
            'id' => 2, 'mezcla_id' => 1, 'nombre_medicamento' => 'Segundo medicamento de prueba',
            'dosis' => 25, 'dosis_ml' => 12.5, 'via_administracion_id' => 1,
        ]);

        return $user;
    }
}
