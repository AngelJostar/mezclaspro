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
        Schema::create('solicitud_details', function (Blueprint $table) {
            $table->id();
            $table->enum('via_administracion', ['Central', 'Periférica']);
            $table->integer('tiempo_infusion_min')->nullable();
            $table->double('sobrellenado_ml')->nullable();
            $table->double('volumen_total')->nullable();
            $table->double('suma_volumen')->nullable();
            $table->double('volumen_total_final')->nullable();
            $table->double('suma_volumen_final')->nullable();
            $table->enum('npt', ['RNPT', 'LACT', 'INF', 'ADOL', 'ADULT']);
            $table->string('nombre_medico');
            $table->string('cedula');
            $table->dateTime('fecha_hora_entrega');
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_details');
    }
};
