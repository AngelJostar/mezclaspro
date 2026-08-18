<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('laboratories')
            ->where('direccion', 'like', '%San Francisco%')
            ->update([
                'estado' => 'Ciudad de Mexico',
                'direccion' => 'San Francisco 524, Colonia del Valle, Alcaldia Benito Juarez, Ciudad de Mexico, C.P. 03100',
                'activo' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('laboratories')
            ->where('direccion', 'San Francisco 524, Colonia del Valle, Alcaldia Benito Juarez, Ciudad de Mexico, C.P. 03100')
            ->update([
                'direccion' => 'San Francisco 516',
                'updated_at' => now(),
            ]);
    }
};
