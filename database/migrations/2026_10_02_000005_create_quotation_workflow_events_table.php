<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotation_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_quotation_id')->constrained('request_quotations')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_key', 64)->unique();
            $table->string('previous_status', 24)->nullable();
            $table->string('status', 24);
            $table->string('source', 16);
            $table->timestamp('created_at');
            $table->index(['request_quotation_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('quotation_workflow_events'); }
};
