<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinical_sources', function (Blueprint $table) {
            $table->string('manual_version', 20)->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->string('resolved_manual_sha256', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_sources', fn (Blueprint $table) => $table->dropColumn(['manual_version', 'superseded_at', 'resolved_manual_sha256']));
    }
};
