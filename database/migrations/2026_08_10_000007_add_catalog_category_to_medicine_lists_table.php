<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->string('catalog_category', 24)
                ->default('oncologicos')
                ->after('description')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->dropIndex(['catalog_category']);
            $table->dropColumn('catalog_category');
        });
    }
};
