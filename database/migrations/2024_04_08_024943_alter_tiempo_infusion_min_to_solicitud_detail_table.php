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
            $table->integer('tiempo_infusion_min')->nullable()->default(24)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_details', function (Blueprint $table) {
            $table->integer('tiempo_infusion_min')->nullable()->change();
        });
    }
};
