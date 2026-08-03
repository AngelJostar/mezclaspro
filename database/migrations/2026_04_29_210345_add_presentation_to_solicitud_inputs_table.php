<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_inputs', function (Blueprint $table) {
            $table->foreignId('nutrition_medicine_presentation_id')
                ->nullable()
                ->after('input_id')
                ->constrained('nutrition_medicine_presentations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_inputs', function (Blueprint $table) {
            $table->dropForeign(['nutrition_medicine_presentation_id']);
            $table->dropColumn('nutrition_medicine_presentation_id');
        });
    }
};
