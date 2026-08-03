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
        Schema::create('solicitud_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('input_id')->constrained();
            $table->string('lote')->nullable();
            $table->date('caducidad')->nullable();
            $table->double('valor');
            $table->double('valor_sobrellenado')->nullable();
            $table->double('valor_ml')->nullable();
            $table->foreignId('solicitud_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_inputs');
    }
};
