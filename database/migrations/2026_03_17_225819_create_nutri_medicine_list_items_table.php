<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('nutri_medicine_list_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nutri_medicine_list_id')
                ->constrained('nutri_medicine_lists')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('nutrition_medicine_presentation_id');

            $table->foreign(
                'nutrition_medicine_presentation_id',
                'nml_items_presentation_fk' // 👈 nombre corto y único
            )->references('id')
                ->on('nutrition_medicine_presentations')
                ->cascadeOnDelete();

            $table->decimal('precio_ml', 12, 4);

            $table->timestamps();

            $table->unique(
                ['nutri_medicine_list_id', 'nutrition_medicine_presentation_id'],
                'nutri_list_item_unique_presentation'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_medicine_list_items');
    }
};
