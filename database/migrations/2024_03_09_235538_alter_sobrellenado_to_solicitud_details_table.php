<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitud_details', function (Blueprint $table) {
            $table->double('suma_volumen_sobrellenado')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_details', function (Blueprint $table) {
            $table->dropColumn('suma_volumen_sobrellenado');
        });
    }
};
