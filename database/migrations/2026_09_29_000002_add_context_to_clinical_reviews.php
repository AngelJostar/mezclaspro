<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinical_reviews', function (Blueprint $table) {
            $table->longText('clinical_context')->nullable();
            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::table('clinical_reviews', function (Blueprint $table) {
            $table->dropIndex(['record_type', 'record_id']);
            $table->dropColumn('clinical_context');
        });
    }
};
