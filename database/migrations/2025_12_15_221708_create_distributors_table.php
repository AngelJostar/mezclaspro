<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();

            // 1 a 1 con medicine_lists (una lista solo puede tener un distribuidor)
            $table->foreignId('medicine_list_id')
                ->constrained('medicine_lists')
                ->cascadeOnDelete()
                ->unique();

            $table->string('nombre');
            $table->text('direccion')->nullable();

            // ruta del logo (storage public)
            $table->string('logo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};