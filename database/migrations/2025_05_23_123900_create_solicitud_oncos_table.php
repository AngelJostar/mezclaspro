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
        Schema::create('solicitud_oncos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->string('servicio');
            $table->string('nombre_paciente');
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->integer('edad')->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->string('cama')->nullable();
            $table->string('piso')->nullable();
            $table->text('alergias');
            $table->string('registro_paciente')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->text('diagnostico');
            $table->dateTime('fecha_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('nombre_medico');
            $table->string('cedula_medico', 50);
            $table->enum('estado', ['pendiente', 'enproceso', 'finalizada', 'cancelada','no-aprobada'])->nullable();
            $table->timestamps();
            $table->string('remision', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_oncos');
    }
};
