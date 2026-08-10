<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_mixture_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('remote_request_id')->unique();
            $table->uuid('local_external_id')->unique();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('catalog_type', 20)->index();
            $table->string('catalog_version')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_mixture_requests');
    }
};
