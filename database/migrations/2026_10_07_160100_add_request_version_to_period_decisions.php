<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conciliation_period_decisions', fn (Blueprint $table) => $table->string('request_version', 64)->nullable());
    }

    public function down(): void
    {
        Schema::table('conciliation_period_decisions', fn (Blueprint $table) => $table->dropColumn('request_version'));
    }
};
