<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RequestQuotationCaptureData
{
    public static function seed(): User
    {
        $user = RequestQuotationData::seed();
        foreach (['oncologicos_solicitudes_create', 'nutricionales_solicitudes_create', 'oncologicos_solicitudes_update'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        Schema::table('hospitals', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('onco_medicine_list_id')->nullable();
            $table->unsignedBigInteger('nutri_medicine_list_id')->nullable();
        });
        foreach ([
            'medicine_lists' => ['name', 'catalog_category', 'charge_by', 'has_mixing_service', 'mixing_service_price'],
            'medicine_presentations' => ['catalog_id', 'presentacion', 'marca', 'contenido_valor', 'contenido_unidad', 'cantidad_medicamento', 'volumen_diluyente', 'is_available'],
            'medicines_catalog' => ['denominacion', 'state', 'catalog_category'],
            'medicine_list_presentation' => ['medicine_list_id', 'medicine_presentation_id', 'charge_by', 'precio', 'precio_mg_override', 'precio_ml_override', 'iva_desglosado', 'descripcion_remision'],
            'diluents' => ['denominacion_generica'], 'diluent_medicine_catalog' => ['medicine_catalog_id', 'diluent_id'],
            'nutri_medicine_lists' => ['name', 'is_active'],
            'nutri_medicine_list_items' => ['nutri_medicine_list_id', 'nutrition_medicine_presentation_id', 'precio_ml', 'charge_by', 'descripcion_remision'],
            'nutrition_medicine_presentations' => ['nutrition_medicine_catalog_id', 'presentacion', 'presentacion_ml', 'denominacion_comercial', 'is_available'],
            'nutrition_medicines_catalog' => ['denominacion_generica', 'is_active', 'category_id', 'input_id'],
            'categories' => ['name'], 'inputs' => ['description'],
            'price_list_additional_charges' => ['price_list_type', 'price_list_id', 'name', 'amount', 'is_active', 'iva_included', 'concept_type'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $schema) use ($columns) {
                $schema->id();
                foreach ($columns as $column) $schema->string($column)->nullable();
                $schema->timestamps();
            });
        }
        (require database_path('migrations/2026_09_22_000003_add_product_status_to_price_lists.php'))->up();
        DB::table('hospitals')->where('id', 1)->update(['onco_medicine_list_id' => 1, 'nutri_medicine_list_id' => 1]);
        DB::table('medicine_lists')->insert(['id' => 1, 'name' => 'Lista onco de prueba', 'catalog_category' => 'oncologicos', 'charge_by' => 'mg', 'has_mixing_service' => 1, 'mixing_service_price' => 10]);
        DB::table('medicines_catalog')->insert(['id' => 1, 'denominacion' => 'Medicamento de prueba', 'state' => 1, 'catalog_category' => 'oncologicos']);
        foreach ([1 => 1, 2 => 0] as $id => $active) {
            DB::table('medicine_presentations')->insert(['id' => $id, 'catalog_id' => 1, 'presentacion' => 'Frasco 100 mg / 10 ml', 'marca' => 'Marca de prueba', 'contenido_valor' => 100, 'contenido_unidad' => 'mg', 'volumen_diluyente' => 10, 'is_available' => $active]);
            DB::table('medicine_list_presentation')->insert(['medicine_list_id' => 1, 'medicine_presentation_id' => $id, 'precio' => 200, 'charge_by' => 'mg', 'iva_desglosado' => 1]);
        }
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Solucion salina']);
        DB::table('diluent_medicine_catalog')->insert(['medicine_catalog_id' => 1, 'diluent_id' => 1]);
        DB::table('nutri_medicine_lists')->insert(['id' => 1, 'name' => 'Lista nutricional de prueba', 'is_active' => 1]);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Aminoacidos']);
        foreach ([1 => 'Aminoacidos de prueba', 2 => 'Componente inactivo', 3 => 'Servicio de mezclado', 4 => 'Bolsa EVA', 5 => 'Set de infusion'] as $id => $name) {
            DB::table('nutrition_medicines_catalog')->insert(['id' => $id, 'denominacion_generica' => $name, 'is_active' => $id === 2 ? 0 : 1, 'category_id' => 1]);
            DB::table('nutrition_medicine_presentations')->insert(['id' => $id, 'nutrition_medicine_catalog_id' => $id, 'presentacion' => 'Frasco 100 ml', 'presentacion_ml' => 100, 'denominacion_comercial' => 'Marca nutricional', 'is_available' => 1]);
            DB::table('nutri_medicine_list_items')->insert(['nutri_medicine_list_id' => 1, 'nutrition_medicine_presentation_id' => $id, 'precio_ml' => $id === 1 ? 2 : 10, 'charge_by' => 'ml']);
        }
        foreach (['oncologicos', 'nutricionales'] as $type) DB::table('price_list_additional_charges')->insert(['price_list_type' => $type, 'price_list_id' => 1, 'name' => 'Cargo de prueba', 'amount' => 5, 'is_active' => 1]);
        \App\Models\RequestQuotation::find(1)->forceFill(app(\App\Services\RequestQuotationCaptureService::class)->capture($user, self::payload()))->save();
        return $user->refresh();
    }

    public static function payload(string $category = 'oncologicos'): array
    {
        $data = ['category' => $category, 'hospital_id' => 1, 'institution_id' => 1, 'submission_key' => (string) Str::uuid(), 'action' => 'save',
            'scheduled_date' => '2026-09-22', 'service' => 'Servicio de prueba', 'patient_name' => 'Paciente de prueba de captura',
            'sex' => 'F', 'birth_date' => '1990-01-01', 'weight_kg' => 60, 'diagnosis' => 'Diagnostico de prueba',
            'doctor_name' => 'Medico de prueba', 'doctor_license' => 'TEST-123'];
        if ($category === 'oncologicos') return $data + ['height_cm' => 160, 'rows' => [[
            'presentation_id' => 1, 'dose_mg' => 50, 'diluent_id' => 1, 'dilution_ml' => 100,
            'boluses_per_day' => 2, 'infusion_minutes' => 60, 'deliveries' => ['2026-09-23T09:00', '2026-09-24T09:00'],
        ]]];
        return $data + ['administration_route' => 'Central', 'infusion_hours' => 24, 'overfill_ml' => 10,
            'npt' => 'ADULT', 'delivery_at' => '2026-09-23T10:00', 'components' => [['presentation_id' => 1, 'volume_ml' => 50]]];
    }
}
