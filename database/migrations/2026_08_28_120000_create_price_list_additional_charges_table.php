<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_additional_charges', function (Blueprint $table) {
            $table->id();
            $table->string('price_list_type', 24);
            $table->unsignedBigInteger('price_list_id');
            $table->string('name');
            $table->enum('concept_type', ['Servicio', 'Insumo'])->default('Servicio');
            $table->decimal('amount', 12, 4)->default(0);
            $table->boolean('iva_included')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['price_list_type', 'price_list_id', 'is_active'], 'additional_charges_list_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_additional_charges');
    }
};
