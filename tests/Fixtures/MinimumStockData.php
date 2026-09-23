<?php

namespace Tests\Fixtures;

use App\Models\MinimumStockSetting;
use App\Models\Supplier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MinimumStockData
{
    public static function migrate(): void
    {
        foreach ([
            'medicines_catalog' => ['denominacion', 'catalog_category'],
            'medicine_presentations' => ['catalog_id', 'presentacion', 'marca', 'contenido_valor', 'contenido_unidad', 'cantidad_medicamento', 'is_available'],
            'nutrition_medicines_catalog' => ['denominacion_generica'],
            'nutrition_medicine_presentations' => ['nutrition_medicine_catalog_id', 'presentacion', 'presentacion_ml', 'denominacion_comercial', 'is_available'],
            'diluents' => ['denominacion_generica'],
            'diluent_catalog_presentations' => ['diluent_id', 'presentation', 'volume_ml', 'commercial_name', 'is_active'],
            'consumable_items' => ['name', 'is_active'],
            'consumable_catalog_presentations' => ['consumable_item_id', 'presentation', 'commercial_name', 'is_active'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        (require database_path('migrations/2026_09_22_000005_create_minimum_stock_settings_table.php'))->up();
        (require database_path('migrations/2026_09_22_000006_add_automatic_reorders_to_purchase_orders.php'))->up();
        foreach ([
            'medicine_batches' => ['medicine_presentation_id', 'stock_actual'],
            'medicine_laboratory_stocks' => ['nutrition_medicine_presentation_id', 'frascos_actuales'],
            'diluent_presentations' => ['catalog_presentation_id', 'stock_actual'],
            'consumable_lots' => ['catalog_presentation_id', 'stock_actual'],
        ] as $name => [$presentation, $quantity]) {
            Schema::create($name, function (Blueprint $table) use ($name, $presentation, $quantity) {
                $table->id();
                $table->unsignedBigInteger($presentation)->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                if ($name !== 'consumable_lots') $table->unsignedBigInteger('laboratory_id');
                $table->decimal($quantity, 14, 4)->default(0);
                $table->boolean('is_active')->default(true);
                if ($name === 'medicine_batches') {
                    $table->integer('stock_reservado')->default(0);
                    $table->boolean('is_current')->default(false);
                    $table->date('caducidad')->nullable();
                }
            });
        }
    }

    public static function seed(): void
    {
        DB::table('medicines_catalog')->insert([
            ['id' => 1, 'denominacion' => 'Acido folinico', 'catalog_category' => 'oncologicos'],
            ['id' => 2, 'denominacion' => 'Antibiotico de prueba', 'catalog_category' => 'antibioticos'],
        ]);
        DB::table('medicine_presentations')->insert([
            ['id' => 1, 'catalog_id' => 1, 'presentacion' => 'Ampolleta 50 mg/4 ml', 'marca' => 'Marca A', 'is_available' => 1],
            ['id' => 2, 'catalog_id' => 1, 'presentacion' => 'Frasco ampula 50 mg', 'marca' => 'Marca B', 'is_available' => 0],
            ['id' => 3, 'catalog_id' => 2, 'presentacion' => 'Frasco 100 mg', 'marca' => 'Marca C', 'is_available' => 1],
        ]);
        DB::table('nutrition_medicines_catalog')->insert(['id' => 1, 'denominacion_generica' => 'Aminoacidos']);
        DB::table('nutrition_medicine_presentations')->insert(['id' => 1, 'nutrition_medicine_catalog_id' => 1, 'presentacion' => 'Frasco 500 ml', 'presentacion_ml' => 500, 'denominacion_comercial' => 'Nutricion A', 'is_available' => 1]);
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Solucion salina']);
        DB::table('diluent_catalog_presentations')->insert(['id' => 1, 'diluent_id' => 1, 'presentation' => 'Bolsa 100 ml', 'volume_ml' => 100, 'commercial_name' => 'Solucion A', 'is_active' => 1]);
        DB::table('consumable_items')->insert(['id' => 1, 'name' => 'Jeringa', 'is_active' => 1]);
        DB::table('consumable_catalog_presentations')->insert(['id' => 1, 'consumable_item_id' => 1, 'presentation' => 'Pieza', 'commercial_name' => 'Insumo A', 'is_active' => 1]);
        Supplier::create(['id' => 1, 'name' => 'Proveedor A', 'email' => 'compras-a@example.test', 'status' => 'active']);
        Supplier::create(['id' => 2, 'name' => 'Proveedor B', 'email' => 'compras-b@example.test', 'status' => 'active']);
        Supplier::create(['id' => 3, 'name' => 'Proveedor inactivo', 'status' => 'inactive']);
        MinimumStockSetting::create(['laboratory_id' => 1, 'product_type' => 'medicine', 'presentation_id' => 1,
            'minimum_stock' => 10, 'maximum_stock' => 30, 'supplier_id' => 1]);
        DB::table('warehouses')->insert(['id' => 9, 'laboratory_id' => 1, 'name' => 'Segundo almacen', 'is_active' => true]);
        DB::table('medicine_batches')->insert([
            ['medicine_presentation_id' => 1, 'laboratory_id' => 1, 'warehouse_id' => 1, 'stock_actual' => 12, 'stock_reservado' => 3, 'is_active' => true, 'is_current' => true],
            ['medicine_presentation_id' => 1, 'laboratory_id' => 1, 'warehouse_id' => 9, 'stock_actual' => 5, 'stock_reservado' => 1, 'is_active' => true, 'is_current' => false],
            ['medicine_presentation_id' => 1, 'laboratory_id' => 1, 'warehouse_id' => 1, 'stock_actual' => 100, 'stock_reservado' => 0, 'is_active' => false, 'is_current' => false],
            ['medicine_presentation_id' => 1, 'laboratory_id' => 2, 'warehouse_id' => 2, 'stock_actual' => 80, 'stock_reservado' => 0, 'is_active' => true, 'is_current' => true],
            ['medicine_presentation_id' => 2, 'laboratory_id' => 1, 'warehouse_id' => 1, 'stock_actual' => 4, 'stock_reservado' => 0, 'is_active' => true, 'is_current' => true],
        ]);
        foreach ([
            'medicine_laboratory_stocks' => ['nutrition_medicine_presentation_id', 'frascos_actuales', 7.5, 2.25],
            'diluent_presentations' => ['catalog_presentation_id', 'stock_actual', 20, 3],
            'consumable_lots' => ['catalog_presentation_id', 'stock_actual', 50, 12],
        ] as $table => [$presentation, $quantity, $first, $second]) {
            foreach ([[1, 1, $first, true], [1, 9, $second, true], [1, 1, 100, false], [2, 2, 90, true]] as [$central, $warehouse, $amount, $active]) {
                $values = [$presentation => 1, 'warehouse_id' => $warehouse, $quantity => $amount, 'is_active' => $active];
                if ($table !== 'consumable_lots') $values['laboratory_id'] = $central;
                DB::table($table)->insert($values);
            }
        }
    }
}
