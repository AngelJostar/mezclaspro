<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->json('configuration')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->string('source_fingerprint', 64)->nullable();
        });
        Schema::create('ai_agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger', 20);
            $table->string('status', 20);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('configuration');
            $table->json('result')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_agent_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_run_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('rule', 40);
            $table->string('priority', 20);
            $table->string('status', 20)->default('new');
            $table->json('evidence');
            $table->text('owner');
            $table->text('resolution')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['ai_agent_id', 'fingerprint']);
        });
        Schema::create('ai_agent_finding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->text('justification');
            $table->timestamp('created_at');
        });
        Schema::create('ai_agent_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->text('api_key')->nullable();
            $table->string('model', 100)->default('gpt-4.1-mini');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_provider_settings');
        Schema::dropIfExists('ai_agent_finding_events');
        Schema::dropIfExists('ai_agent_findings');
        Schema::dropIfExists('ai_agent_runs');
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('configured_by');
            $table->dropColumn(['configuration', 'next_run_at', 'source_fingerprint']);
        });
    }
};
