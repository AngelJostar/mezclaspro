<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->foreignId('onco_medicine_list_id')
                ->nullable()
                ->constrained('medicine_lists')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropForeign(['onco_medicine_list_id']);
            $table->dropColumn('onco_medicine_list_id');
        });
    }
};
