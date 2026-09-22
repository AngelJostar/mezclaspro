<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspeccion_nutricionales', function (Blueprint $table) {
            $table->timestamp('inspection_completed_at')->nullable()->after('hora_inspeccion');
        });

        DB::table('inspeccion_nutricionales')
            ->where('peso_mezcla', '>', 0)
            ->whereNotNull('aprobo_nombre')
            ->where('aprobo_nombre', '<>', '')
            ->orderBy('id')
            ->get(['id', 'fecha_inspeccion', 'hora_inspeccion'])
            ->each(function ($inspection) {
                DB::table('inspeccion_nutricionales')
                    ->where('id', $inspection->id)
                    ->update([
                        'inspection_completed_at' => trim($inspection->fecha_inspeccion.' '.$inspection->hora_inspeccion),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('inspeccion_nutricionales', function (Blueprint $table) {
            $table->dropColumn('inspection_completed_at');
        });
    }
};
