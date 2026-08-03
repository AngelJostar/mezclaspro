<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->index('input_id', 'nmc_input_index');
            $table->dropUnique('nmc_input_unique');
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->unique('input_id', 'nmc_input_unique');
            $table->dropIndex('nmc_input_index');
        });
    }
};
