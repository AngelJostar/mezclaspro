<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE mezcla_medicamentos MODIFY dosis DECIMAL(12,4) NULL');
        }
        foreach (['solicituds', 'solicitud_oncos'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('request_quotation_id')->nullable()->unique()
                    ->constrained('request_quotations')->restrictOnDelete();
            });
        }
        Schema::table('solicituds', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->restrictOnDelete();
            $table->json('quotation_pricing_snapshot')->nullable();
        });
        Schema::table('mezclas', function (Blueprint $table) {
            $table->json('quotation_pricing_snapshot')->nullable();
        });
        \Illuminate\Support\Facades\DB::table('solicituds')->update(['hospital_id' =>
            \Illuminate\Support\Facades\DB::raw('(SELECT hospital_id FROM users WHERE users.id = solicituds.user_id)')]);
    }

    public function down(): void
    {
        // Keep the wider dose precision on rollback; narrowing it would round clinical data.
        Schema::table('mezclas', fn (Blueprint $table) => $table->dropColumn('quotation_pricing_snapshot'));
        Schema::table('solicituds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
            $table->dropColumn('quotation_pricing_snapshot');
        });
        foreach (['solicituds', 'solicitud_oncos'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropForeign(['request_quotation_id']);
                $table->dropUnique($name.'_request_quotation_id_unique');
                $table->dropColumn('request_quotation_id');
            });
        }
    }
};
