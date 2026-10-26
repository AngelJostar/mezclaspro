<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_completions', function (Blueprint $table) {
            $table->id();
            $table->string('student_key', 100);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_name');
            $table->json('student_snapshot');
            $table->string('program_id', 191);
            $table->string('program_name');
            $table->date('start_date');
            $table->timestamp('completed_at');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('completed_by_name');
            $table->timestamps();
            $table->unique(['student_key', 'program_id', 'start_date'], 'training_completions_course_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_completions');
    }
};
