<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->foreignId('delivery_laboratory_id')
                ->nullable()
                ->after('laboratory_id')
                ->constrained('laboratories')
                ->nullOnDelete();
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('delivery_laboratory_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('delivery_laboratory_id');
        });
    }
};
