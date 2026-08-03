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
        Schema::create('medicine_stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medicine_laboratory_stock_id')
                ->constrained('medicine_laboratory_stocks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('tipo', ['entrada', 'salida', 'merma', 'ajuste']);

            $table->decimal('cantidad_ml', 12, 2);
            $table->decimal('stock_antes', 12, 2);
            $table->decimal('stock_despues', 12, 2);

            $table->decimal('cantidad_frascos', 12, 2)->nullable();
            $table->decimal('frascos_antes', 12, 2)->nullable();
            $table->decimal('frascos_despues', 12, 2)->nullable();

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'medicine_stock_movements_reference_index');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_stock_movements');
    }
};
