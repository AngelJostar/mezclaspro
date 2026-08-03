<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('medicine_list_presentation', function (Blueprint $table) {
            $table->id();

            // Lista a la que pertenece
            $table->foreignId('medicine_list_id')
                ->constrained()
                ->cascadeOnDelete();

            // Presentación a la que aplica el precio
            $table->foreignId('medicine_presentation_id')
                ->constrained()
                ->cascadeOnDelete();

            // Cómo se cobra esta presentación en ESTA lista
            $table->enum('charge_by', ['mg', 'frasco']);

            // Si la lista cobra por frasco → aquí va el precio
            $table->decimal('precio', 10, 2)->nullable();

            // Si la lista cobra por mg → aquí va el precio mg
            $table->decimal('precio_mg_override', 10, 4)->nullable();

            $table->timestamps();

            // Una presentación no debe repetirse dos veces en la misma lista
            $table->unique(['medicine_list_id', 'medicine_presentation_id'], 'mlp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_list_presentation');
    }
};
