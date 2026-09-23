<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('category', 24);
            $table->string('patient_name');
            $table->unsignedBigInteger('price_list_id');
            $table->string('price_list_name');
            $table->decimal('total', 18, 2)->nullable();
            $table->string('status', 24)->default('borrador');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->unsignedBigInteger('request_id')->nullable();
            $table->timestamps();
            $table->index(['hospital_id', 'category', 'status']);
            $table->index(['institution_id', 'created_at']);
            $table->unique(['category', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_quotations');
    }
};
