<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conciliation_periods', function (Blueprint $table) {
            $table->uuid('creation_batch_key')->nullable();
            $table->unique(['creation_batch_key', 'hospital_id'], 'conciliation_periods_batch_hospital_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conciliation_periods', function (Blueprint $table) {
            $table->dropUnique('conciliation_periods_batch_hospital_unique');
            $table->dropColumn('creation_batch_key');
        });
    }
};
