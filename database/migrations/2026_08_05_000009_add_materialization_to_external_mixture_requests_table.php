<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_mixture_requests', function (Blueprint $table): void {
            $table->string('materialized_type', 30)->nullable()->after('status');
            $table->unsignedBigInteger('materialized_id')->nullable()->after('materialized_type');
            $table->timestamp('materialized_at')->nullable()->after('received_at');
            $table->text('last_error')->nullable()->after('payload');
            $table->index(['materialized_type', 'materialized_id'], 'external_mixture_materialized_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_mixture_requests', function (Blueprint $table): void {
            $table->dropIndex('external_mixture_materialized_index');
            $table->dropColumn(['materialized_type', 'materialized_id', 'materialized_at', 'last_error']);
        });
    }
};
