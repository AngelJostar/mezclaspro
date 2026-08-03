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
        Schema::create('diluent_medicine_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diluent_id')->constrained('diluents')->onDelete('cascade');
            $table->foreignId('medicine_catalog_id')->constrained('medicines_catalog')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['diluent_id', 'medicine_catalog_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diluent_medicine_catalog');
    }
};
