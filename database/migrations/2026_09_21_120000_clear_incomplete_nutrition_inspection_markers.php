<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inspeccion_nutricionales')
            ->whereNotNull('inspection_completed_at')
            ->where(function ($query) {
                $query->whereNull('peso_mezcla')
                    ->orWhere('peso_mezcla', '<=', 0)
                    ->orWhereNull('aprobo_nombre')
                    ->orWhere('aprobo_nombre', '');
            })
            ->update(['inspection_completed_at' => null]);
    }

    public function down(): void
    {
        // No se restauran marcadores que representaban inspecciones incompletas.
    }
};
