<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspeccion_mezclas', function (Blueprint $table) {
            $table->date('fecha_preparacion')->nullable()->after('hora_validacion');
            $table->time('hora_preparacion')->nullable()->after('fecha_preparacion');
        });
    }

    public function down(): void
    {
        Schema::table('inspeccion_mezclas', function (Blueprint $table) {
            $table->dropColumn(['fecha_preparacion', 'hora_preparacion']);
        });
    }
};
