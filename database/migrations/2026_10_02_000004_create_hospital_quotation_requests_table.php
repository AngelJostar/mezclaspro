<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospital_quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->unique()->constrained('request_quotations')->nullOnDelete();
            $table->uuid('submission_key')->unique();
            $table->string('category', 30);
            $table->json('items');
            $table->string('patient_name')->nullable();
            $table->text('observations')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->index(['hospital_id', 'created_at']);
            $table->index(['seller_id', 'quotation_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('hospital_quotation_requests'); }
};
