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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion_comercial');//Nombre comercial
            $table->string('denominacion_generica');
            $table->double('precio_ml');
            $table->string('presentacion');
            $table->double('presentacion_ml')->nullable();
            $table->boolean('is_active')->default(1);
            $table->double('osmolaridad')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('input_id')->constrained();//Era la descripcion
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
