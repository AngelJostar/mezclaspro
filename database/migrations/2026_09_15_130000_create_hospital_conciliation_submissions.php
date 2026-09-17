<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospital_conciliation_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_key')->unique();
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hospital_name');
            $table->string('sender_name');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->json('filters');
            $table->unsignedInteger('mixture_count');
            $table->unsignedInteger('conciliable_count');
            $table->longText('snapshot');
            $table->timestamps();
            $table->index(['hospital_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_conciliation_submissions');
    }
};
