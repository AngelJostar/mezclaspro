<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion');
            $table->string('denominacion_comercial');
            // 👇 NUEVO: va en la migración base
            $table->boolean('requires_infusor')->default(false);
            $table->boolean('state')->default(true);
            $table->decimal('conc_min', 8, 2)->nullable();
            $table->decimal('conc_max', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines_catalog');
    }
};
