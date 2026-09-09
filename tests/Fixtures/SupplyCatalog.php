<?php

namespace Tests\Fixtures;

use App\Http\Controllers\Admin\CatalogoListasController;
use App\Models\ConsumableItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplyCatalog
{
    public static function seed(): void
    {
        // Both PHP and browser tests use a disposable database, never live inventory.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
        DB::purge('sqlite');
        $tables = [
            'laboratories' => ['nombre', 'estado'],
            'warehouses' => ['name', 'laboratory_id'],
            'diluents' => ['denominacion_generica'],
            'diluent_presentations' => ['diluent_id', 'laboratory_id', 'warehouse_id', 'presentacion', 'volume_ml',
                'denominacion_comercial', 'fabricante', 'lote', 'caducidad', 'fecha_ingreso', 'stock_actual', 'is_active'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        foreach (['000001_create_consumable_inventory_tables', '000004_create_consumable_catalog_presentations_table'] as $migration) {
            (require database_path('migrations/2026_09_04_'.$migration.'.php'))->up();
        }
        DB::table('laboratories')->insert(['id' => 1, 'nombre' => 'Central de prueba', 'estado' => 'CDMX']);
        DB::table('warehouses')->insert(['id' => 1, 'laboratory_id' => 1, 'name' => 'Almacen de prueba']);
        foreach ([1 => 'CLORURO DE SODIO 0.9%', 2 => 'GLUCOSA 5%'] as $id => $name) {
            DB::table('diluents')->insert(['id' => $id, 'denominacion_generica' => $name]);
            DB::table('diluent_presentations')->insert([
                'id' => $id, 'diluent_id' => $id, 'laboratory_id' => 1, 'warehouse_id' => 1,
                'presentacion' => 'Bolsa 500 ml', 'volume_ml' => 500, 'denominacion_comercial' => 'Marca de prueba',
                'fabricante' => 'Fabricante de prueba', 'lote' => 'LOTE-'.$id, 'caducidad' => '2027-12-31',
                'fecha_ingreso' => '2026-01-01', 'stock_actual' => 100, 'is_active' => 1,
            ]);
        }
        $item = ConsumableItem::create(['name' => 'Jeringa', 'unit' => 'pieza', 'is_active' => true]);
        $item->catalogPresentations()->createMany([
            ['presentation' => 'Jeringa 10 ml', 'commercial_name' => 'Marca A', 'manufacturer' => 'Fabricante A'],
            ['presentation' => 'Jeringa 20 ml', 'commercial_name' => 'Marca B', 'manufacturer' => 'Fabricante B'],
        ]);
        ConsumableItem::create(['name' => 'Guante', 'unit' => 'par', 'is_active' => true]);
        ConsumableItem::create(['name' => 'Consumible inactivo', 'unit' => 'pieza', 'is_active' => false]);
    }

    public static function viewData(?string $section = null): array
    {
        $request = Request::create('/admin/catalogo-listas/insumos/catalogo', 'GET',
            $section === null ? [] : ['tipo_insumo' => $section]);
        app()->instance('request', $request);

        return app(CatalogoListasController::class)->catalog('insumos')->getData();
    }

    public static function render(array $data): string
    {
        $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], ['', '@stack("js")'],
            file_get_contents(resource_path('views/admin/catalogo-listas/catalog.blade.php')));

        return Blade::render($source, $data);
    }
}
