<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_laboratory_active_presentations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('laboratory_id');
            $table->unsignedBigInteger('nutrition_medicine_catalog_id');
            $table->unsignedBigInteger('nutrition_medicine_presentation_id');

            $table->date('selected_date');

            $table->timestamps();

            $table->foreign('laboratory_id', 'nlap_laboratory_fk')
                ->references('id')
                ->on('laboratories')
                ->cascadeOnDelete();

            $table->foreign('nutrition_medicine_catalog_id', 'nlap_catalog_fk')
                ->references('id')
                ->on('nutrition_medicines_catalog')
                ->cascadeOnDelete();

            $table->foreign('nutrition_medicine_presentation_id', 'nlap_presentation_fk')
                ->references('id')
                ->on('nutrition_medicine_presentations')
                ->cascadeOnDelete();

            $table->unique(
                ['laboratory_id', 'nutrition_medicine_catalog_id', 'selected_date'],
                'nlap_lab_catalog_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_laboratory_active_presentations');
    }
};
