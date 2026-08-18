<?php

use App\Services\SolicitudOperativeStatusService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE solicitud_oncos MODIFY estado VARCHAR(30) NULL');
        } else {
            Schema::table('solicitud_oncos', function (Blueprint $table) {
                $table->string('estado', 30)->nullable()->change();
            });
        }

        DB::table('solicitud_oncos')
            ->select('id', 'estado')
            ->orderBy('id')
            ->chunkById(100, function ($solicitudes) {
                foreach ($solicitudes as $solicitud) {
                    if (in_array($solicitud->estado, ['cancelada', 'no_aprobada', 'no-aprobada'], true)) {
                        $estado = $solicitud->estado === 'no-aprobada' ? 'no_aprobada' : $solicitud->estado;
                    } else {
                        $estadosMezclas = DB::table('mezclas')
                            ->where('solicitud_id', $solicitud->id)
                            ->pluck('estado');

                        $estado = SolicitudOperativeStatusService::resolve($estadosMezclas);
                    }

                    DB::table('solicitud_oncos')
                        ->where('id', $solicitud->id)
                        ->update(['estado' => $estado]);
                }
            });
    }

    public function down(): void
    {
        DB::table('solicitud_oncos')->whereIn('estado', ['aprobada', 'preparada', 'revisada'])->update([
            'estado' => 'enproceso',
        ]);
        DB::table('solicitud_oncos')->where('estado', 'entregada')->update(['estado' => 'finalizada']);
        DB::table('solicitud_oncos')->where('estado', 'no_aprobada')->update(['estado' => 'no-aprobada']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE solicitud_oncos MODIFY estado ENUM('pendiente','enproceso','finalizada','cancelada','no-aprobada') NULL");
        }
    }
};
