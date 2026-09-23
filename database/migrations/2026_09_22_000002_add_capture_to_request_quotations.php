<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_quotations', function (Blueprint $table) {
            $table->json('clinical_data')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->string('attachment_path')->nullable();
            $table->uuid('submission_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('request_quotations', function (Blueprint $table) {
            $table->dropUnique(['submission_key']);
            $table->dropColumn(['clinical_data', 'pricing_snapshot', 'attachment_path', 'submission_key']);
        });
    }
};
