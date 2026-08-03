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
        Schema::create('solicitud_patients', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_paciente');
            $table->string('apellidos_paciente');
            $table->string('servicio');
            $table->string('cama')->nullable();
            $table->string('piso')->nullable();
            $table->string('registro')->nullable();
            $table->string('diagnostico')->nullable();
            $table->date('fecha_nacimiento');
            $table->string('edad')->nullable();
            $table->decimal('peso')->nullable();
            $table->enum('sexo', ['Femenino', 'Masculino'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_patients');
    }
};
