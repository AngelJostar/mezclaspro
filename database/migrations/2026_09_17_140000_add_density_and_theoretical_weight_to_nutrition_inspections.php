<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspeccion_nutricionales', function (Blueprint $table) {
            $table->decimal('densidad', 8, 4)->nullable()->after('peso_mezcla');
            $table->decimal('peso_teorico', 10, 2)->nullable()->after('densidad');
        });
    }

    public function down(): void
    {
        Schema::table('inspeccion_nutricionales', function (Blueprint $table) {
            $table->dropColumn(['densidad', 'peso_teorico']);
        });
    }
};
