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
        Schema::create('medicine_presentations', function (Blueprint $table) {
            $table->id();
            // FK al catálogo de medicamentos (tipo: Paracetamol, etc.)
            $table->foreignId('catalog_id')
                ->constrained('medicines_catalog')
                ->cascadeOnDelete();

            // Datos de la presentación
            $table->string('presentacion', 255);         // ej. "frasco 500 mg", "vial 300 mg"
            $table->string('marca', 255)->nullable();
            $table->string('fabricante', 255)->nullable();
            $table->decimal('contenido_valor', 10, 2);   // ej. 500, 300
            $table->enum('contenido_unidad', ['mg', 'g', 'ml', 'UI', 'smg'])->default('mg');
            $table->decimal('cantidad_medicamento', 10, 2)
            ->nullable(); // mg
            $table->decimal('volumen_diluyente', 10, 2)->nullable();
            // Disponibilidad simulada (sin inventario real)
            $table->boolean('is_available')->default(true);
            $table->integer('virtual_stock')->nullable(); // opcional: límite blando (si no lo usarás, puedes quitarlo)
            $table->decimal('precio_frasco', 12, 4)->nullable();
            $table->text('legend')->nullable();
            $table->text('forma_reconstitucion')->nullable();
            $table->unsignedSmallInteger('temp_min_c')->nullable();
            $table->unsignedSmallInteger('temp_max_c')->nullable();
            $table->unsignedSmallInteger('stability_hours')->nullable();
            $table->timestamps();

            // Índices útiles
            $table->index(['catalog_id']);
            $table->index(['is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_presentations');
    }
};
