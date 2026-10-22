<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', fn (Blueprint $table) => $table->json('enabled_services')->nullable());
        Schema::table('hospitals', fn (Blueprint $table) => $table->json('enabled_services')->nullable());
    }

    public function down(): void
    {
        Schema::table('hospitals', fn (Blueprint $table) => $table->dropColumn('enabled_services'));
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn('enabled_services'));
    }
};
