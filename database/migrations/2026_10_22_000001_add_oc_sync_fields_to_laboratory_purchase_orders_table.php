<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('proposed_delivery_at');
            $table->boolean('is_credit')->default(false)->after('due_date');
            $table->unsignedSmallInteger('credit_days')->nullable()->after('is_credit');
            $table->string('oc_sync_status', 20)->default('not_applicable')->after('status');
            $table->text('oc_sync_error')->nullable()->after('oc_sync_status');
            $table->timestamp('oc_synced_at')->nullable()->after('oc_sync_error');
            $table->unsignedBigInteger('oc_remote_id')->nullable()->after('oc_synced_at');
            $table->string('oc_remote_folio', 100)->nullable()->after('oc_remote_id');
            $table->unsignedInteger('oc_sync_attempts')->default(0)->after('oc_remote_folio');
            $table->timestamp('oc_last_sync_attempt_at')->nullable()->after('oc_sync_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'due_date', 'is_credit', 'credit_days', 'oc_sync_status', 'oc_sync_error',
                'oc_synced_at', 'oc_remote_id', 'oc_remote_folio', 'oc_sync_attempts', 'oc_last_sync_attempt_at',
            ]);
        });
    }
};
