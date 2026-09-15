<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class MixtureAdjustments
{
    public static function seed(): array
    {
        PreparationWorkflow::seed();
        Schema::table('users', fn (Blueprint $t) => $t->integer('notification')->default(0));
        foreach ([
            'solicituds' => ['user_id', 'solicitud_patient_id', 'solicitud_detail_id', 'estado', 'lote'],
            'solicitud_patients' => ['nombre_paciente', 'apellidos_paciente', 'peso', 'servicio', 'fecha_nacimiento'],
            'solicitud_details' => ['volumen_total', 'npt'],
            'solicitud_inputs' => ['solicitud_id', 'input_id', 'valor', 'nutrition_medicine_presentation_id', 'lote', 'caducidad'],
            'inputs' => ['description', 'unidad'],
            'medicines_catalog' => ['denominacion', 'charge_by', 'conc_min', 'conc_max', 'requires_infusor'],
            'diluents' => ['denominacion_generica'],
            'administration_routes' => ['name'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        foreach ([
            'mezclas' => ['tiempo_infusion', 'set_infusion', 'infusor_id'],
            'solicitud_oncos' => ['nombre_paciente', 'servicio', 'registro_paciente', 'sexo', 'fecha_nacimiento', 'peso', 'piso', 'cama', 'diagnostico', 'nombre_medico', 'cedula_medico', 'fecha_entrega', 'observaciones'],
            'mezcla_medicamentos' => ['medicamento_id', 'diluyente_id', 'via_administracion_id', 'requires_infusor_snapshot', 'conc_min_snapshot', 'conc_max_snapshot', 'charge_by', 'precio_mg_snapshot', 'precio_ml_snapshot'],
        ] as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) $table->string($column)->nullable();
            });
        }
        (require database_path('migrations/2026_09_14_000006_create_mixture_adjustments.php'))->up();
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable');
            $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        $central = User::findOrFail(1);
        $hospital = User::forceCreate(['name' => 'Hospital', 'hospital_id' => 1, 'is_active' => true]);
        $hospital->assignRole(Role::create(['name' => 'Institucion', 'guard_name' => 'web']));
        DB::table('solicitud_oncos')->where('id', 1)->update(['estado' => 'pendiente', 'nombre_paciente' => 'Paciente de prueba',
            'servicio' => 'Servicio', 'registro_paciente' => 'R1']);
        DB::table('mezclas')->where('id', 1)->update(['estado' => 'pendiente', 'tiempo_infusion' => '60', 'production_attempt' => 1,
            'lote' => null, 'diluent_presentation_id' => null, 'fecha_entrega' => now()->addDay()->format('Y-m-d H:i:00')]);
        DB::table('medicines_catalog')->insert(['id' => 1, 'denominacion' => 'Medicamento de prueba', 'charge_by' => 'mg']);
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Diluyente de prueba']);
        DB::table('administration_routes')->insert(['id' => 1, 'name' => 'Via de prueba']);
        DB::table('mezcla_medicamentos')->where('id', 1)->update(['medicamento_id' => 1, 'diluyente_id' => 1, 'via_administracion_id' => 1]);
        foreach (['mezcla_medicamento_presentaciones', 'medicine_batch_movements', 'medicine_remainder_movements', 'diluent_stock_movements', 'medicine_remainders'] as $table) {
            DB::table($table)->delete();
        }
        DB::table('solicituds')->insert(['id' => 1, 'user_id' => $hospital->id, 'solicitud_patient_id' => 1, 'solicitud_detail_id' => 1, 'estado' => 'pendiente']);
        DB::table('solicitud_patients')->insert(['id' => 1, 'nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'peso' => 60]);
        DB::table('solicitud_details')->insert(['id' => 1, 'volumen_total' => 100, 'npt' => 'ADULT']);
        DB::table('inputs')->insert(['id' => 1, 'description' => 'Insumo de prueba', 'unidad' => 'ml']);
        DB::table('solicitud_inputs')->insert(['id' => 1, 'solicitud_id' => 1, 'input_id' => 1, 'valor' => 20]);
        return [$central, $hospital];
    }

    public static function request(User $user, string $type = 'oncologicos'): Request
    {
        $data = $type === 'nutricionales'
            ? ['nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'peso' => '60', 'volumen_total' => '150', 'npt' => 'ADULT', 'i_1' => '25',
                'servicio' => 'Servicio', 'fecha_nacimiento' => '1990-01-01', 'via_administracion' => 'Central', 'nombre_medico' => 'Medico de prueba',
                'cedula' => '12345', 'bolsa_eva' => '38', 'fecha_hora_entrega' => now()->addDay()->format('Y-m-d\\TH:i')]
            : ['paciente_nombre' => 'Paciente de prueba', 'servicio' => 'Servicio', 'registro' => 'R1',
                'fecha_entrega' => now()->addDay()->format('Y-m-d\\TH:i'), 'mezcla_json' => json_encode([
                    'volumen_dilucion' => 300, 'tiempo_infusion' => '60', 'set_infusion' => false, 'infusor_id' => null,
                    'medicamentos' => [['medicamento_id' => 1, 'nombre' => 'Nombre falso', 'dosis' => 100, 'diluyente_id' => 1, 'via_administracion_id' => 1]],
                ])];
        $request = Request::create('/admin/ajuste', 'PUT', $data + ['accion' => 'ajustar', 'adjustment_description' => 'Cambio solicitado para prueba.']);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));
        return $request;
    }

    public static function nutritionSchema(): void
    {
        foreach ([
            'solicitud_patients' => (new \App\Models\Nutricionales\SolicitudPatient())->getFillable(),
            'solicitud_details' => (new \App\Models\Nutricionales\SolicitudDetail())->getFillable(),
            'solicitud_inputs' => (new \App\Models\Nutricionales\SolicitudInput())->getFillable(),
            'solicituds' => ['remision'], 'hospitals' => ['nutri_medicine_list_id'], 'inputs' => ['category_id', 'mult', 'div'],
        ] as $name => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($name, $column)) {
                    Schema::table($name, fn (Blueprint $table) => $table->string($column)->nullable());
                }
            }
        }
        Schema::create('nutrition_laboratory_active_presentations', function (Blueprint $table) {
            $table->id(); $table->integer('laboratory_id'); $table->date('selected_date');
        });
        Schema::create('inspeccion_nutricionales', function (Blueprint $table) {
            $table->id();
            foreach ((new \App\Models\Nutricionales\InspeccionNutricional())->getFillable() as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        DB::table('inputs')->where('id', 1)->update(['category_id' => 1, 'mult' => 1, 'div' => 1]);
        DB::table('inputs')->insert(['id' => 38, 'description' => 'Bolsa de prueba', 'category_id' => 6]);
        DB::table('hospitals')->where('id', 1)->update(['nutri_medicine_list_id' => 1]);
    }
}
