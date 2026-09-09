<?php

namespace Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiluentCreation
{
    public static function seed(): void
    {
        SupplyCatalog::seed();
        Schema::table('laboratories', fn (Blueprint $table) => $table->boolean('activo')->default(true));
        Schema::table('warehouses', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::table('diluents', fn (Blueprint $table) => $table->timestamps());
        Schema::table('diluent_presentations', function (Blueprint $table) {
            $table->decimal('stock_inicial', 12, 2)->default(0);
            $table->decimal('stock_reservado', 12, 2)->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_08_000004_add_stability_hours_to_diluent_presentations.php'))->up();
        DB::table('laboratories')->insert([
            ['id' => 2, 'nombre' => 'Central Sur', 'activo' => true],
            ['id' => 3, 'nombre' => 'Central sin almacenes', 'activo' => true],
            ['id' => 4, 'nombre' => 'Central inactiva', 'activo' => false],
        ]);
        DB::table('warehouses')->insert([
            ['id' => 2, 'laboratory_id' => 2, 'name' => 'Almacen Sur', 'is_active' => true],
            ['id' => 3, 'laboratory_id' => 1, 'name' => 'Almacen inactivo', 'is_active' => false],
            ['id' => 4, 'laboratory_id' => 4, 'name' => 'Almacen central inactiva', 'is_active' => true],
        ]);
    }
}
