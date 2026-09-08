<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consumable_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->default('pieza');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('consumable_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('presentation')->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('lot')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('received_at')->nullable();
            $table->decimal('stock_actual', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['consumable_item_id', 'warehouse_id', 'lot'], 'consumable_lot_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('consumable_lots');
        Schema::dropIfExists('consumable_items');
    }
};
