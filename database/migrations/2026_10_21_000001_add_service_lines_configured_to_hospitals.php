<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->boolean('service_lines_configured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', fn (Blueprint $table) => $table->dropColumn('service_lines_configured'));
    }
};
