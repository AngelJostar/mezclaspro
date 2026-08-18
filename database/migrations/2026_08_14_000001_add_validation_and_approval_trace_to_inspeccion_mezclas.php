<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspeccion_mezclas', function (Blueprint $table) {
            $table->string('valido_nombre')->nullable()->after('observaciones');
            $table->date('fecha_validacion')->nullable()->after('valido_nombre');
            $table->time('hora_validacion')->nullable()->after('fecha_validacion');
            $table->date('fecha_aprobacion')->nullable()->after('hora_validacion');
            $table->time('hora_aprobacion')->nullable()->after('fecha_aprobacion');
        });
    }

    public function down(): void
    {
        Schema::table('inspeccion_mezclas', function (Blueprint $table) {
            $table->dropColumn([
                'valido_nombre',
                'fecha_validacion',
                'hora_validacion',
                'fecha_aprobacion',
                'hora_aprobacion',
            ]);
        });
    }
};
