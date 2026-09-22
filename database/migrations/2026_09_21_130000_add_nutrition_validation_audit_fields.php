<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicituds', function (Blueprint $table) {
            $table->string('validated_by')->nullable()->after('estado');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
        });

        DB::table('inspeccion_nutricionales as inspections')
            ->join('solicituds as requests', 'requests.id', '=', 'inspections.solicitud_id')
            ->whereNull('inspections.inspection_completed_at')
            ->whereNotNull('inspections.aprobo_nombre')
            ->where('inspections.aprobo_nombre', '<>', '')
            ->select([
                'requests.id',
                'inspections.aprobo_nombre',
                'inspections.fecha_inspeccion',
                'inspections.hora_inspeccion',
            ])
            ->orderBy('requests.id')
            ->get()
            ->each(function ($row) {
                DB::table('solicituds')->where('id', $row->id)->update([
                    'validated_by' => $row->aprobo_nombre,
                    'validated_at' => trim($row->fecha_inspeccion.' '.$row->hora_inspeccion),
                ]);

                DB::table('inspeccion_nutricionales')
                    ->where('solicitud_id', $row->id)
                    ->update(['aprobo_nombre' => null]);
            });
    }

    public function down(): void
    {
        Schema::table('solicituds', function (Blueprint $table) {
            $table->dropColumn(['validated_by', 'validated_at']);
        });
    }
};
