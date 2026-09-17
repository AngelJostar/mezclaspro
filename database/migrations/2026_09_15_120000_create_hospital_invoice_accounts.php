<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospital_invoice_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('invoice_key', 64)->unique();
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->foreignId('institucion_id')->constrained('clientes');
            $table->string('clarification_status')->default('none');
            $table->text('clarification_notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->timestamps();
        });
        Schema::create('hospital_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('hospital_invoice_accounts');
            $table->uuid('submission_key')->unique();
            $table->decimal('amount', 14, 2);
            $table->date('paid_at');
            $table->string('reference', 150);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_invoice_payments');
        Schema::dropIfExists('hospital_invoice_accounts');
    }
};
