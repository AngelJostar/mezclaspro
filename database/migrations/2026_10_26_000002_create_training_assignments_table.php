<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_id', 191);
            $table->foreign('program_id')->references('id')->on('training_programs')->cascadeOnDelete();
            $table->json('module_ids');
            $table->date('start_date');
            $table->string('status', 20)->default('assigned');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'program_id', 'start_date'], 'training_assignments_course_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
