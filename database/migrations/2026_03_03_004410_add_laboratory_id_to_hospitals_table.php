<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {

            // 🔹 Primero nullable para no romper producción
            $table->foreignId('laboratory_id')
                ->nullable()
                ->after('is_active')
                ->constrained('laboratories')
                ->nullOnDelete(); // mejor que cascade en este caso
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropForeign(['laboratory_id']);
            $table->dropColumn('laboratory_id');
        });
    }
};
