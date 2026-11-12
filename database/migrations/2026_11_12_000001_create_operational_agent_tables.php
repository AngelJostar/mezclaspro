<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_agent_user_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('agent_ids');
            $table->timestamps();
        });
        Schema::create('ai_agent_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_run_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('hospital_id');
            $table->date('period_from');
            $table->date('period_to');
            $table->foreignId('conciliation_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('submission_id')->constrained('hospital_conciliation_submissions')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['ai_agent_id', 'institution_id', 'hospital_id', 'period_from', 'period_to'], 'agent_delivery_cycle_unique');
        });
        Schema::create('ai_agent_delivery_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_delivery_id')->constrained()->cascadeOnDelete();
            $table->string('recipient');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
            $table->unique(['ai_agent_delivery_id', 'recipient'], 'agent_delivery_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_delivery_emails');
        Schema::dropIfExists('ai_agent_deliveries');
        Schema::dropIfExists('ai_agent_user_orders');
    }
};
