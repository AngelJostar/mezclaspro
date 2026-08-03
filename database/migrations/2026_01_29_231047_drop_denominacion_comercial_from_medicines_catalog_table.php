<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines_catalog', function (Blueprint $table) {
            // Si la columna existe, la quitamos
            if (Schema::hasColumn('medicines_catalog', 'denominacion_comercial')) {
                $table->dropColumn('denominacion_comercial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicines_catalog', function (Blueprint $table) {
            // La restauramos por si haces rollback
            if (!Schema::hasColumn('medicines_catalog', 'denominacion_comercial')) {
                $table->string('denominacion_comercial')->after('denominacion');
            }
        });
    }
};
