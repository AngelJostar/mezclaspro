<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_report_templates', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('is_custom');
            $table->timestamp('published_at')->nullable()->after('created_by');
            $table->index(
                ['is_custom', 'is_published', 'published_at'],
                'institution_report_templates_publication_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('institution_report_templates', function (Blueprint $table) {
            $table->dropIndex('institution_report_templates_publication_idx');
            $table->dropColumn(['is_published', 'published_at']);
        });
    }
};
