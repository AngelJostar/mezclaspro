<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->boolean('has_mixing_service')->default(false)->after('show_label_lot_expiry');
            $table->decimal('mixing_service_price', 12, 4)->default(0)->after('has_mixing_service');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->dropColumn(['has_mixing_service', 'mixing_service_price']);
        });
    }
};
