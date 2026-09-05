<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('nutri_medicine_list_items', 'charge_by')) {
            Schema::table('nutri_medicine_list_items', function (Blueprint $table) {
                $table->string('charge_by', 20)
                    ->default('ml')
                    ->after('precio_ml');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('nutri_medicine_list_items', 'charge_by')) {
            Schema::table('nutri_medicine_list_items', function (Blueprint $table) {
                $table->dropColumn('charge_by');
            });
        }
    }
};
