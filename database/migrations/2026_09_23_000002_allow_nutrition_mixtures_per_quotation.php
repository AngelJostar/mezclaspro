<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('solicituds', fn (Blueprint $table) => $table->index('request_quotation_id', 'solicituds_quotation_index'));
        Schema::table('solicituds', fn (Blueprint $table) => $table->dropUnique('solicituds_request_quotation_id_unique'));
    }

    public function down(): void
    {
        if (DB::table('solicituds')->whereNotNull('request_quotation_id')->groupBy('request_quotation_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir: existen cotizaciones con varias mezclas nutricionales.');
        }
        Schema::table('solicituds', fn (Blueprint $table) => $table->unique('request_quotation_id'));
        Schema::table('solicituds', fn (Blueprint $table) => $table->dropIndex('solicituds_quotation_index'));
    }
};
