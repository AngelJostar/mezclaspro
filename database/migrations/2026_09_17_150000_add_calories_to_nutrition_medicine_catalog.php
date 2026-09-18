<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->decimal('calorias', 12, 4)->nullable()->after('osmolaridad');
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->dropColumn('calorias');
        });
    }
};
