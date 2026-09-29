<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinical_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference', 1000);
            $table->string('category', 30);
            $table->longText('content');
            $table->string('sha256', 64);
            $table->boolean('is_manual')->default(false);
            $table->boolean('resolves_manual_ambiguities')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('clinical_reviewer')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
        Schema::create('clinical_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('kind', 30);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('purpose', 20);
            $table->string('payload_hash', 64);
            $table->string('context_hash', 64);
            $table->string('sources_hash', 64);
            $table->string('session_hash', 64);
            $table->longText('result');
            $table->boolean('can_submit')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamps();
            $table->index(['kind', 'target_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_reviews');
        Schema::dropIfExists('clinical_sources');
    }
};
