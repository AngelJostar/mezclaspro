<?php

namespace Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogExportData
{
    public static function seed(): void
    {
        SupplyCatalog::seed();
        $tables = [
            'medicines_catalog' => ['denominacion', 'catalog_category'],
            'medicine_presentations' => ['catalog_id', 'presentacion', 'marca', 'contenido_valor',
                'contenido_unidad', 'cantidad_medicamento', 'stability_hours', 'is_available'],
            'medicine_batches' => ['medicine_presentation_id', 'costo_unitario', 'fecha_ingreso', 'created_at'],
            'nutrition_medicines_catalog' => ['denominacion_generica', 'input_id'],
            'nutrition_medicine_presentations' => ['nutrition_medicine_catalog_id', 'presentacion',
                'presentacion_ml', 'denominacion_comercial', 'stability_hours', 'is_available'],
            'medicine_laboratory_stocks' => ['nutrition_medicine_presentation_id', 'fecha_ingreso', 'created_at'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        foreach ([1 => 'oncologicos', 2 => 'antibioticos'] as $id => $category) {
            DB::table('medicines_catalog')->insert(['id' => $id, 'denominacion' => 'Producto '.$category,
                'catalog_category' => $category]);
            DB::table('medicine_presentations')->insert(['id' => $id, 'catalog_id' => $id,
                'presentacion' => 'Frasco 250 mg', 'marca' => 'Marca de prueba', 'contenido_valor' => 250,
                'contenido_unidad' => 'mg', 'stability_hours' => 48, 'is_available' => 1]);
            DB::table('medicine_batches')->insert([
                ['medicine_presentation_id' => $id, 'costo_unitario' => 0, 'fecha_ingreso' => '2026-01-01'],
                ['medicine_presentation_id' => $id, 'costo_unitario' => 1234.56, 'fecha_ingreso' => '2026-02-01'],
            ]);
        }
        DB::table('medicine_presentations')->insert(['catalog_id' => 1, 'presentacion' => 'No disponible', 'is_available' => 0]);
        DB::table('nutrition_medicines_catalog')->insert(['id' => 1, 'denominacion_generica' => 'Producto nutricional']);
        DB::table('nutrition_medicine_presentations')->insert(['id' => 1, 'nutrition_medicine_catalog_id' => 1,
            'presentacion' => 'Frasco 500 ml', 'presentacion_ml' => 500, 'denominacion_comercial' => 'Marca nutricional', 'is_available' => 1]);
        DB::table('medicine_laboratory_stocks')->insert(['nutrition_medicine_presentation_id' => 1, 'fecha_ingreso' => '2026-03-01']);
        DB::table('diluent_presentations')->where('id', 1)->update(['lote' => '001234', 'stock_actual' => 0]);
    }
}
