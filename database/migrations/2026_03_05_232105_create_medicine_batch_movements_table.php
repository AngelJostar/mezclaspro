<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batch_movements', function (Blueprint $table) {
            $table->id();

            // Lote afectado
            $table->foreignId('medicine_batch_id')
                ->constrained('medicine_batches')
                ->cascadeOnDelete();

            // Redundante pero útil para consultas/reportes rápidos
            $table->foreignId('laboratory_id')
                ->constrained('laboratories')
                ->cascadeOnDelete();

            // Usuario que hizo el movimiento
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /**
             * Tipos sugeridos:
             * entrada
             * salida
             * ajuste_positivo
             * ajuste_negativo
             * reserva
             * liberacion_reserva
             * merma
             * cancelacion_salida
             */
            $table->enum('movement_type', [
                'entrada',
                'salida',
                'ajuste_positivo',
                'ajuste_negativo',
                'reserva',
                'liberacion_reserva',
                'merma',
                'cancelacion_salida',
            ]);

            // Cantidad del movimiento
            $table->integer('quantity');

            // Snapshots del stock al momento del movimiento
            $table->integer('stock_actual_before')->default(0);
            $table->integer('stock_actual_after')->default(0);
            $table->integer('stock_reservado_before')->default(0);
            $table->integer('stock_reservado_after')->default(0);

            /**
             * Referencia opcional al origen del movimiento
             * Ejemplos:
             * reference_type = mezcla
             * reference_id   = 25
             *
             * o también:
             * reference_type = ajuste_manual
             * reference_id   = null
             */
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Observaciones
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['medicine_batch_id', 'created_at'], 'idx_mbm_batch_date');
            $table->index(['laboratory_id', 'created_at'], 'idx_mbm_lab_date');
            $table->index(['movement_type'], 'idx_mbm_type');
            $table->index(['reference_type', 'reference_id'], 'idx_mbm_reference');
            $table->index(['user_id'], 'idx_mbm_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batch_movements');
    }
};
