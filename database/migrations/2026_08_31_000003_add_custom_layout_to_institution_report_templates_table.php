<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_report_templates', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('name');
            $table->string('data_source', 40)->nullable()->after('is_custom');
            $table->json('layout')->nullable()->after('free_fields');
            $table->foreignId('created_by')->nullable()->after('layout');

            $table->foreign('created_by', 'institution_report_templates_creator_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['is_custom', 'created_at'], 'institution_report_templates_custom_idx');
        });
    }

    public function down(): void
    {
        Schema::table('institution_report_templates', function (Blueprint $table) {
            $table->dropForeign('institution_report_templates_creator_fk');
            $table->dropIndex('institution_report_templates_custom_idx');
            $table->dropColumn(['is_custom', 'data_source', 'layout', 'created_by']);
        });
    }
};
