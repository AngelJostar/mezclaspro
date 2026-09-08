<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_service_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_service_id')
                ->constrained('maintenance_services')
                ->cascadeOnDelete();
            $table->string('supplier_name');
            $table->decimal('price', 14, 2);
            $table->timestamps();

            $table->index(['maintenance_service_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_service_quotes');
    }
};
