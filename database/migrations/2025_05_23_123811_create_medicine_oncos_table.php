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
        Schema::create('medicine_oncos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('medicines_catalog');
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_mg', 12, 4)->nullable(); // ajusta posición según tu tabla
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_oncos');
    }
};
