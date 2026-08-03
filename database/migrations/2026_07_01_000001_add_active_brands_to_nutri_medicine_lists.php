<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('nutri_medicine_lists', 'active_brands')) {
            return;
        }

        Schema::table('nutri_medicine_lists', function (Blueprint $table) {
            $table->boolean('active_brands')
                ->default(false)
                ->after('is_active');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('nutri_medicine_lists', 'active_brands')) {
            return;
        }

        Schema::table('nutri_medicine_lists', function (Blueprint $table) {
            $table->dropColumn('active_brands');
        });
    }
};
