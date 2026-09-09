<?php

namespace Tests\Fixtures;

use App\Http\Controllers\Admin\Oncologicos\InventoryController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemainderInventory
{
    public static function seed(): void
    {
        // Isolated connection: these fixtures must never write to the application database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $tables = [
            'laboratories' => ['nombre', 'estado', 'activo'],
            'warehouses' => ['laboratory_id', 'name', 'is_active'],
            'medicines_catalog' => ['denominacion', 'state', 'requires_infusor', 'conc_min', 'conc_max', 'catalog_category'],
            'medicine_presentations' => ['catalog_id', 'presentacion', 'contenido_valor', 'contenido_unidad',
                'cantidad_medicamento', 'volumen_diluyente', 'marca', 'fabricante', 'precio_frasco', 'legend',
                'temp_min_c', 'temp_max_c', 'stability_hours', 'is_available'],
            'medicine_batches' => ['medicine_presentation_id', 'laboratory_id', 'warehouse_id', 'lote', 'caducidad',
                'fecha_ingreso', 'stock_inicial', 'stock_actual', 'stock_reservado', 'is_active'],
            'medicine_remainders' => ['domain', 'medicine_presentation_id', 'medicine_batch_id', 'laboratory_id',
                'warehouse_id', 'current_ml', 'is_active', 'usable_until'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }

        DB::table('laboratories')->insert(['id' => 1, 'nombre' => 'Central de prueba', 'activo' => 1]);
        DB::table('warehouses')->insert(['id' => 1, 'laboratory_id' => 1, 'name' => 'Almacen de prueba', 'is_active' => 1]);
        foreach ([10 => 'oncologicos', 20 => 'antibioticos'] as $id => $category) {
            DB::table('medicines_catalog')->insert([
                'id' => $id, 'denominacion' => 'Medicamento de prueba', 'state' => 1, 'catalog_category' => $category,
            ]);
            foreach ([$id, $id + 1, $id + 2] as $presentationId) {
                DB::table('medicine_presentations')->insert([
                    'id' => $presentationId, 'catalog_id' => $id, 'presentacion' => 'Presentacion '.$presentationId,
                    'contenido_valor' => $id === 10 ? 250 : 1000, 'contenido_unidad' => 'mg',
                    'cantidad_medicamento' => $presentationId === $id + 1 ? null : ($id === 10 ? 250 : 1000),
                    'volumen_diluyente' => $id === 10 ? 10 : 4, 'marca' => 'Marca de prueba', 'is_available' => 1,
                ]);
            }
            foreach ([0, 1, 2, 3] as $index) {
                $batchId = $id * 10 + $index;
                DB::table('medicine_batches')->insert([
                    'id' => $batchId, 'medicine_presentation_id' => $index === 3 ? $id + 1 : $id,
                    'laboratory_id' => 1, 'warehouse_id' => 1, 'lote' => 'LOTE-'.$batchId,
                    'caducidad' => '2027-12-31', 'fecha_ingreso' => '2026-01-01',
                    'stock_actual' => 5, 'stock_inicial' => 10, 'stock_reservado' => 0, 'is_active' => 1,
                ]);
                if ($index !== 2) {
                    DB::table('medicine_remainders')->insert([
                        'domain' => 'oncologico', 'medicine_presentation_id' => $index === 3 ? $id + 1 : $id,
                        'medicine_batch_id' => $batchId, 'laboratory_id' => 1, 'warehouse_id' => 1,
                        'current_ml' => $index === 1 ? 0.1234 : 2, 'is_active' => 1, 'usable_until' => now()->addDay(),
                    ]);
                }
            }
            foreach ([[0.5, 1, now()->addDay()], [10, 1, now()->subDay()], [20, 0, now()->addDay()]] as [$ml, $active, $until]) {
                DB::table('medicine_remainders')->insert([
                    'domain' => 'oncologico', 'medicine_presentation_id' => $id, 'medicine_batch_id' => $id * 10,
                    'laboratory_id' => 1, 'warehouse_id' => 1, 'current_ml' => $ml, 'is_active' => $active, 'usable_until' => $until,
                ]);
            }
        }
    }

    public static function viewData(string $category): array
    {
        $request = Request::create('/inventory', 'GET', ['laboratory_id' => 1, 'warehouse_id' => 1, 'category' => $category]);

        return app(InventoryController::class)->index($request)->getData();
    }
}
