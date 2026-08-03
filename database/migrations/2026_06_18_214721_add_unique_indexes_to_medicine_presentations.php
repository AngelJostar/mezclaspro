<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_presentations', function (Blueprint $table) {
            $table->unique(
                ['catalog_id', 'presentacion', 'marca'],
                'uq_onco_catalog_presentacion_marca'
            );
        });

        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->unique(
                ['nutrition_medicine_catalog_id', 'denominacion_comercial'],
                'uq_nutri_catalog_denominacion_comercial'
            );
        });
    }

    public function down(): void
    {
        Schema::table('medicine_presentations', function (Blueprint $table) {
            $table->dropUnique('uq_onco_catalog_presentacion_marca');
        });

        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->dropUnique('uq_nutri_catalog_denominacion_comercial');
        });
    }
};
