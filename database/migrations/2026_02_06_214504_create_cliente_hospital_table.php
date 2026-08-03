<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_hospital', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('hospital_id');

            $table->timestamps();

            // Evita duplicar la misma relación cliente-hospital
            $table->unique(['cliente_id', 'hospital_id']);

            // FKs
            $table->foreign('cliente_id')
                ->references('id')->on('clientes')
                ->onDelete('cascade');

            $table->foreign('hospital_id')
                ->references('id')->on('hospitals')
                ->onDelete('cascade');

            // (Opcional pero útil)
            $table->index('cliente_id');
            $table->index('hospital_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_hospital');
    }
};
