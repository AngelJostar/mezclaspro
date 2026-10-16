<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_solicitud_diluents', function (Blueprint $table) {
            $table->unsignedBigInteger('diluent_presentation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // La presentación no puede volver a ser obligatoria porque las solicitudes
        // capturadas por clientes se guardan deliberadamente sin lote asignado.
    }
};
