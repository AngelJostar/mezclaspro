<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->dateTime('fecha_entrega')->nullable()->index();
        });

        DB::table('mezclas')
            ->select('id', 'solicitud_id')
            ->orderBy('id')
            ->chunkById(500, function ($mixtures): void {
                $requestDates = DB::table('solicitud_oncos')
                    ->whereIn('id', $mixtures->pluck('solicitud_id')->unique()->all())
                    ->pluck('fecha_entrega', 'id');

                foreach ($mixtures as $mixture) {
                    DB::table('mezclas')
                        ->where('id', $mixture->id)
                        ->update([
                            'fecha_entrega' => $requestDates[$mixture->solicitud_id] ?? null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->dropIndex(['fecha_entrega']);
            $table->dropColumn('fecha_entrega');
        });
    }
};
