<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('infusors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_generico')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('lote', 100)->nullable();
            $table->date('caducidad')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('infusors');
    }
};
