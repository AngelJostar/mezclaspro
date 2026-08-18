<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->boolean('iva_desglosado')
                ->default(false)
                ->after('precio_mg_override');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->dropColumn('iva_desglosado');
        });
    }
};
