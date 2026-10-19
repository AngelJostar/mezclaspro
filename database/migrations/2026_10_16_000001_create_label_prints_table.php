<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_prints', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40);
            $table->unsignedBigInteger('document_id');
            $table->unsignedSmallInteger('copies')->default(1);
            $table->string('print_type', 20);
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('authorized_by')->nullable()->constrained('users');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_prints');
    }
};
