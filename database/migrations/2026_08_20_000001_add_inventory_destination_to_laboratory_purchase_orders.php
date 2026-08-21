<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->string('inventory_destination', 32)
                ->nullable()
                ->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('inventory_destination');
        });
    }
};
