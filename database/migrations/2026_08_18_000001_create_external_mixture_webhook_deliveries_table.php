<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_mixture_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_mixture_request_id');
            $table->foreign('external_mixture_request_id', 'external_webhook_request_fk')
                ->references('id')
                ->on('external_mixture_requests')
                ->cascadeOnDelete();
            $table->uuid('event_id')->unique();
            $table->string('event_type', 80)->default('mixture.status_changed');
            $table->string('status', 30)->default('pending')->index();
            $table->json('payload');
            $table->char('payload_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['external_mixture_request_id', 'event_type', 'payload_hash'], 'external_webhook_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_mixture_webhook_deliveries');
    }
};
