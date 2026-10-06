<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinical_sources', function (Blueprint $table) {
            $table->string('manual_type', 40)->nullable()->index();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_sha256', 64)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->json('manual_analysis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_sources', fn (Blueprint $table) => $table->dropColumn([
            'manual_type', 'file_path', 'file_name', 'file_sha256', 'uploaded_by', 'manual_analysis',
        ]));
    }
};
