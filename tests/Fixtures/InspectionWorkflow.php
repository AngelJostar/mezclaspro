<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Models\Oncologicos\InspeccionMezcla;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class InspectionWorkflow
{
    public static function seed(): User
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Inspection fixtures require an in-memory database.');
        }
        foreach ([
            'users' => ['name', 'lastname', 'username', 'hospital_id', 'email', 'password'],
            'personnel_profiles' => ['user_id', 'positions'],
            'hospitals' => ['name', 'laboratory_id'],
            'laboratories' => ['nombre'], 'warehouses' => ['name'],
            'medicine_batches' => ['warehouse_id', 'stock_actual'],
            'clientes' => ['nombre'], 'cliente_hospital' => ['hospital_id', 'cliente_id'],
            'solicitud_oncos' => ['hospital_id', 'estado', 'tipo_solicitud'],
            'mezclas' => ['solicitud_id', 'estado', 'lote', 'volumen_dilucion', 'diluent_presentation_id', 'remision', 'fecha_entrega'],
            'mezcla_medicamentos' => ['mezcla_id', 'nombre_medicamento', 'denominacion_snapshot', 'dosis', 'dosis_ml', 'marca_snapshot'],
            'mezcla_medicamento_presentaciones' => ['mezcla_medicamento_id', 'medicine_batch_id', 'unidades_abiertas', 'unidades_usadas', 'volumen_usado_ml', 'lote_usado', 'presentacion_snapshot'],
            'diluent_presentations' => ['laboratory_id', 'warehouse_id', 'is_active', 'stock_actual', 'lote', 'caducidad', 'volume_ml'],
            'medicine_remainders' => ['opened_for_type', 'opened_for_id', 'current_ml', 'is_active', 'usable_until'],
            'medicine_batch_movements' => ['reference_type', 'reference_id', 'movement_type', 'quantity'],
            'medicine_remainder_movements' => ['medicine_remainder_id', 'reference_type', 'reference_id', 'movement_type', 'quantity_ml'],
            'diluent_stock_movements' => ['diluent_presentation_id', 'laboratory_id', 'warehouse_id', 'user_id', 'reference_type', 'reference_id', 'movement_type', 'quantity', 'stock_actual_before', 'stock_actual_after', 'notes'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        foreach ([
            '2024_04_11_013606_create_permission_tables.php',
            '2025_05_23_124210_create_inspeccion_mezclas_table.php',
            '2026_08_14_000001_add_validation_and_approval_trace_to_inspeccion_mezclas.php',
            '2026_09_01_000001_add_preparation_timestamp_to_inspeccion_mezclas_table.php',
            '2026_09_08_000001_create_inspection_wastes_table.php',
            '2026_09_08_000002_create_mixture_lot_sequences_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $user = User::forceCreate(['id' => 1, 'name' => 'Inspector', 'lastname' => 'Prueba', 'username' => 'gcortes']);
        $user->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'web']));
        auth()->login($user);
        DB::table('hospitals')->insert(['id' => 1, 'name' => 'Hospital de prueba', 'laboratory_id' => 1]);
        DB::table('laboratories')->insert(['id' => 1, 'nombre' => 'Central de prueba']);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Almacen de prueba']);
        DB::table('medicine_batches')->insert(['id' => 1, 'warehouse_id' => 1, 'stock_actual' => 8]);
        DB::table('solicitud_oncos')->insert(['id' => 1, 'hospital_id' => 1, 'estado' => 'preparada', 'tipo_solicitud' => 'oncologicos']);
        DB::table('mezclas')->insert(['id' => 1, 'solicitud_id' => 1, 'estado' => 'preparada', 'lote' => 'MEZCLA-01', 'volumen_dilucion' => 250, 'diluent_presentation_id' => 1]);
        DB::table('mezcla_medicamentos')->insert(['id' => 1, 'mezcla_id' => 1, 'nombre_medicamento' => 'Medicamento de prueba', 'denominacion_snapshot' => 'Medicamento de prueba', 'dosis' => 90, 'dosis_ml' => 45, 'marca_snapshot' => 'Marca de prueba']);
        DB::table('mezcla_medicamento_presentaciones')->insert(['id' => 1, 'mezcla_medicamento_id' => 1, 'medicine_batch_id' => 1, 'unidades_abiertas' => 2, 'unidades_usadas' => 2, 'volumen_usado_ml' => 45, 'lote_usado' => 'MED-01', 'presentacion_snapshot' => '50 mg / 25 mL']);
        DB::table('diluent_presentations')->insert(['id' => 1, 'laboratory_id' => 1, 'warehouse_id' => 1, 'is_active' => 1, 'stock_actual' => 9, 'lote' => 'DIL-01', 'caducidad' => '2030-01-01', 'volume_ml' => 250]);
        DB::table('medicine_remainders')->insert(['id' => 1, 'opened_for_type' => 'mezcla', 'opened_for_id' => 1, 'current_ml' => 5, 'is_active' => 1]);
        foreach (['medicine_batch_movements', 'diluent_stock_movements'] as $table) {
            DB::table($table)->insert(['id' => 1, 'reference_type' => 'mezcla', 'reference_id' => 1, 'movement_type' => 'salida', 'quantity' => 1]);
        }
        DB::table('medicine_remainder_movements')->insert(['id' => 1, 'medicine_remainder_id' => 1, 'reference_type' => 'mezcla', 'reference_id' => 1, 'movement_type' => 'consumo', 'quantity_ml' => 2]);
        InspeccionMezcla::create(['mezcla_id' => 1, 'fecha_inspeccion' => now()->toDateString(), 'hora_inspeccion' => '12:00:00', 'reviso_nombre' => '', 'aprobo_nombre' => '', 'preparo_nombre' => 'Preparador original', 'valido_nombre' => 'Validador original']);

        return $user;
    }
}
