<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class CatalogAvailabilityData
{
    public static function user(string $role = 'Super Admin'): User
    {
        $user = new User(['name' => 'Catalog tester', 'is_active' => true]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));
        return $user;
    }

    public static function renderList(string $category, int $list): string
    {
        $data = app(\App\Http\Controllers\Admin\CatalogoListasController::class)->showList($category, $list)->getData();
        $data['errors'] = new \Illuminate\Support\ViewErrorBag();
        $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], ['', ''],
            file_get_contents(resource_path('views/admin/catalogo-listas/show-list.blade.php')));

        return \Illuminate\Support\Facades\Blade::render($source, $data);
    }

    public static function seed(): void
    {
        CatalogExportData::seed();
        foreach (['medicine_presentations', 'nutrition_medicine_presentations'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->timestamps());
        }
        Schema::table('nutrition_medicines_catalog', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        $tables = [
            'inputs' => ['name'],
            'medicine_lists' => ['name', 'description', 'catalog_category', 'active_brands', 'charge_by',
                'show_label_lot_expiry', 'has_mixing_service', 'mixing_service_price', 'has_contract', 'contract_number', 'contract_information'],
            'medicine_list_presentation' => ['medicine_list_id', 'medicine_presentation_id', 'charge_by',
                'precio', 'precio_mg_override', 'precio_ml_override', 'iva_desglosado', 'descripcion_remision'],
            'distributors' => ['medicine_list_id'],
            'nutri_medicine_lists' => ['name', 'description', 'is_active', 'active_brands', 'has_contract', 'contract_number', 'contract_information'],
            'nutri_medicine_list_items' => ['nutri_medicine_list_id', 'nutrition_medicine_presentation_id', 'precio_ml', 'charge_by', 'descripcion_remision'],
            'nutri_distributors' => ['nutri_medicine_list_id'],
            'price_list_additional_charges' => ['price_list_type', 'price_list_id', 'name'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        (require database_path('migrations/2026_09_22_000003_add_product_status_to_price_lists.php'))->up();
        foreach ([1 => 'oncologicos', 2 => 'antibioticos'] as $id => $category) {
            foreach ([$id, $id + 2] as $listId) {
                DB::table('medicine_lists')->insert(['id' => $listId, 'name' => 'List '.$listId, 'catalog_category' => $category]);
                DB::table('medicine_list_presentation')->insert(['medicine_list_id' => $listId,
                    'medicine_presentation_id' => $id, 'precio' => 123.45, 'charge_by' => 'frasco',
                    'iva_desglosado' => 1, 'descripcion_remision' => 'Original '.$listId]);
            }
        }
        foreach ([1, 2] as $id) {
            DB::table('nutri_medicine_lists')->insert(['id' => $id, 'name' => 'Nutrition '.$id, 'is_active' => true]);
            DB::table('nutri_medicine_list_items')->insert(['nutri_medicine_list_id' => $id,
                'nutrition_medicine_presentation_id' => 1, 'precio_ml' => 12.34, 'charge_by' => 'ml', 'descripcion_remision' => 'Original '.$id]);
        }
    }
}
