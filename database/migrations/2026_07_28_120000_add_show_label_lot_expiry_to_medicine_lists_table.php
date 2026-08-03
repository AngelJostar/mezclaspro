<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('medicine_lists', 'show_label_lot_expiry')) {
                $table->boolean('show_label_lot_expiry')
                    ->default(false)
                    ->after('charge_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            if (Schema::hasColumn('medicine_lists', 'show_label_lot_expiry')) {
                $table->dropColumn('show_label_lot_expiry');
            }
        });
    }
};
