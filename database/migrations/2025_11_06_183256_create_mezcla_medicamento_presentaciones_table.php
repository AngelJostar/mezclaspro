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
        Schema::create('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            $table->id();

            // Cabecera del renglón en la mezcla
            $table->foreignId('mezcla_medicamento_id')
                ->constrained('mezcla_medicamentos')
                ->cascadeOnDelete();

            // Batch/lote usado
            $table->foreignId('medicine_batch_id')
                ->constrained('medicine_batches')
                ->restrictOnDelete();

            // Unidades usadas
            $table->integer('unidades_usadas')->default(1);

            // =========================
            // ✅ SNAPSHOTS INMUTABLES
            // =========================
            $table->string('presentacion_snapshot')->nullable();          // ej. "500 mg / 10 mL"
            $table->decimal('cantidad_medicamento_snapshot', 10, 4)->nullable(); // mg, UI, etc
            $table->decimal('volumen_diluyente_snapshot', 10, 4)->nullable();    // mL
            $table->text('legend_snapshot')->nullable();                  // leyenda de protección

            // Datos del lote usados en ese momento
            $table->string('lote_usado', 100);
            $table->date('caducidad_usada');

            // Costos congelados
            $table->decimal('precio_frasco_snapshot', 12, 4)->nullable();
            $table->decimal('subtotal', 12, 4)->nullable();

            $table->timestamps();

            // Índices
            $table->index(['mezcla_medicamento_id']);
            $table->index(['medicine_batch_id']);
            $table->index(['lote_usado']);
            $table->index(['caducidad_usada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mezcla_medicamento_presentaciones');
    }
};
