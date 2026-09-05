<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            if (! Schema::hasColumn('hospitals', 'fiscal_name')) {
                $table->string('fiscal_name')->nullable()->after('care_level');
            }

            if (! Schema::hasColumn('hospitals', 'fiscal_regime')) {
                $table->string('fiscal_regime')->nullable()->after('rfc');
            }

            if (! Schema::hasColumn('hospitals', 'cfdi_use')) {
                $table->string('cfdi_use')->nullable()->after('fiscal_regime');
            }

            if (! Schema::hasColumn('hospitals', 'billing_email')) {
                $table->string('billing_email', 150)->nullable()->after('cfdi_use');
            }

            if (! Schema::hasColumn('hospitals', 'billing_phone')) {
                $table->string('billing_phone', 30)->nullable()->after('billing_email');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'fiscal_name',
            'fiscal_regime',
            'cfdi_use',
            'billing_email',
            'billing_phone',
        ], fn (string $column) => Schema::hasColumn('hospitals', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('hospitals', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
