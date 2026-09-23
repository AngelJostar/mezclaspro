<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->boolean('is_automatic')->default(false)->index();
            $table->string('automatic_open_key', 100)->nullable()->unique();
            $table->json('reorder_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['automatic_open_key']);
            $table->dropIndex(['is_automatic']);
            $table->dropColumn(['is_automatic', 'automatic_open_key', 'reorder_snapshot']);
        });
    }
};
