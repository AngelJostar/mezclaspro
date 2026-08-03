<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->string('origen_tipo', 50);
            $table->unsignedBigInteger('origen_id');
            $table->string('precio_total')->nullable();
            $table->string('conciliable')->nullable();
            $table->string('folio_factura_uuid')->nullable();
            $table->string('folio_interno')->nullable();
            $table->string('fecha_facturacion')->nullable();
            $table->string('estatus_facturacion')->nullable();
            $table->string('numero_carta_factura')->nullable();
            $table->string('fecha_carta_factura')->nullable();
            $table->timestamps();

            $table->unique(['origen_tipo', 'origen_id'], 'institution_billings_origen_unique');
            $table->index(['institucion_id', 'hospital_id'], 'institution_billings_inst_hosp_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_billings');
    }
};
