<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_agent_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 16);
            $table->string('subject_key', 160);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_run')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['ai_agent_id', 'subject_type', 'subject_key'], 'agent_access_subject');
            $table->index(['subject_type', 'subject_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_access_grants');
    }
};
