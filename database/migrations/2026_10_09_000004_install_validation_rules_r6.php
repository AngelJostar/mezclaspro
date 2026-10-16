<?php

use Database\Seeders\ClinicalValidationRulesR6Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('validation_rules')) return;

        // The seeder is idempotent: existing rules, approvals and activation
        // states remain untouched. New installations receive review drafts.
        (new ClinicalValidationRulesR6Seeder)->run();
    }

    public function down(): void
    {
        // Rules can already be approved or referenced by review history.
        // They are intentionally preserved on rollback.
    }
};
