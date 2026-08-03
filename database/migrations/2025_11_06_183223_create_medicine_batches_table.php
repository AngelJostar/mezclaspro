<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();

            // Laboratorio / centro de mezclas
            $table->foreignId('laboratory_id')
                ->constrained('laboratories')
                ->cascadeOnDelete();

            // Presentación exacta del medicamento
            $table->foreignId('medicine_presentation_id')
                ->constrained('medicine_presentations')
                ->cascadeOnDelete();

            // Datos del lote
            $table->string('lote', 100);
            $table->date('caducidad');

            // Fecha de ingreso del lote al inventario
            $table->date('fecha_ingreso')->nullable();

            // Stock real
            $table->integer('stock_inicial')->default(0);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_reservado')->default(0);

            // Costo por unidad física (frasco/ampolleta/pieza)
            $table->decimal('costo_unitario', 12, 4)->nullable();

            // Estado del lote
            $table->boolean('is_current')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Evitar duplicar el mismo lote para la misma presentación en el mismo laboratorio
            $table->unique(
                ['laboratory_id', 'medicine_presentation_id', 'lote'],
                'uq_batch_per_lab_presentation'
            );

            // Índices útiles
            $table->index(
                ['laboratory_id', 'medicine_presentation_id', 'is_current'],
                'idx_batch_lab_pres_current'
            );

            $table->index(
                ['laboratory_id', 'caducidad'],
                'idx_batch_lab_exp'
            );

            $table->index(
                ['caducidad'],
                'idx_batch_exp'
            );

            $table->index(
                ['laboratory_id', 'medicine_presentation_id', 'is_active'],
                'idx_batch_lab_pres_active'
            );

            $table->index(
                ['laboratory_id', 'stock_actual'],
                'idx_batch_lab_stock'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
