<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_mixture_requests', function (Blueprint $table): void {
            $table->json('status_details')->nullable()->after('status');
            $table->timestamp('status_checked_at')->nullable()->after('materialized_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_mixture_requests', function (Blueprint $table): void {
            $table->dropColumn(['status_details', 'status_checked_at']);
        });
    }
};
