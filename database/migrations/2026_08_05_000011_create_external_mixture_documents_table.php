<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_mixture_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_mixture_request_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64);
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->unique(['external_mixture_request_id', 'type', 'sha256'], 'external_mixture_document_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_mixture_documents');
    }
};
