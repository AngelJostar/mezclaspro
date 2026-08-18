<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->text('informacion_adicional')->nullable()->after('contacto');
        });

        Schema::table('nutri_distributors', function (Blueprint $table) {
            $table->text('informacion_adicional')->nullable()->after('contacto');
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn('informacion_adicional');
        });

        Schema::table('nutri_distributors', function (Blueprint $table) {
            $table->dropColumn('informacion_adicional');
        });
    }
};
