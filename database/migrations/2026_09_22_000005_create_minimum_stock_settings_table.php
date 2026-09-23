<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minimum_stock_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->string('product_type', 24);
            $table->unsignedBigInteger('presentation_id');
            $table->unsignedInteger('minimum_stock')->nullable();
            $table->unsignedInteger('maximum_stock')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['laboratory_id', 'product_type', 'presentation_id'], 'minimum_stock_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minimum_stock_settings');
    }
};
