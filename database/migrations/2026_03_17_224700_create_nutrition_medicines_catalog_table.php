<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->id();

            $table->string('denominacion_generica');

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('input_id')
                ->constrained('inputs')
                ->cascadeOnDelete();

            $table->decimal('osmolaridad', 12, 4)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // 🔥 CLAVE: evitar duplicados por input
            $table->unique('input_id', 'nmc_input_unique');

            $table->index('denominacion_generica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_medicines_catalog');
    }
};
