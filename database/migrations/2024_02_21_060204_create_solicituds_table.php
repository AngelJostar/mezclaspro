<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained();

            $table->foreignId('solicitud_detail_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('solicitud_patient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(1);

            // Estado operativo del flujo nutricional
            $table->string('estado')->default('pendiente');


            $table->dateTime('fecha_hora_preparacion')->nullable();
            $table->timestamp('fecha_hora_limite_uso')->nullable();

            // Identificadores operativos
            $table->string('lote')->nullable();
            $table->string('remision')->nullable();

            $table->timestamps();

            $table->index('estado');
            $table->index('lote');
            $table->index('remision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicituds');
    }
};
