<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('nutrition_medicine_catalog_id');

            $table->foreign('nutrition_medicine_catalog_id', 'nmp_catalog_fk')
                ->references('id')
                ->on('nutrition_medicines_catalog')
                ->cascadeOnDelete();

            $table->string('denominacion_comercial');
            $table->string('fabricante')->nullable();
            $table->string('presentacion')->nullable();
            $table->decimal('presentacion_ml', 12, 4)->nullable();

            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index('denominacion_comercial');
            $table->unique(
                ['nutrition_medicine_catalog_id', 'denominacion_comercial'],
                'nutri_med_present_unique_catalog_comercial'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_medicine_presentations');
    }
};
