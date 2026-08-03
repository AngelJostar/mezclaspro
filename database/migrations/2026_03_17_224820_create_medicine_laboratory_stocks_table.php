<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('medicine_laboratory_stocks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('nutrition_medicine_presentation_id');

            $table->foreign('nutrition_medicine_presentation_id', 'mls_presentation_fk')
                ->references('id')
                ->on('nutrition_medicine_presentations')
                ->cascadeOnDelete();

            $table->foreignId('laboratory_id')
                ->constrained('laboratories')
                ->cascadeOnDelete();

            $table->decimal('frascos_iniciales', 12, 2)->default(0);
            $table->decimal('frascos_actuales', 12, 2)->default(0);

            $table->decimal('stock_ml_inicial', 12, 2)->default(0);
            $table->decimal('stock_ml_actual', 12, 2)->default(0);

            $table->string('lote');
            $table->date('caducidad');

            $table->date('fecha_ingreso')->nullable();
            $table->string('numero_factura')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unique(
                ['nutrition_medicine_presentation_id', 'laboratory_id', 'lote'],
                'mls_presentation_lab_lote_unique'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_laboratory_stocks');
    }
};
