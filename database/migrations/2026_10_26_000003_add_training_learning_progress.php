<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('training_assignments', fn (Blueprint $table) => $table->json('learning_progress')->nullable());
        Schema::table('training_completions', fn (Blueprint $table) => $table->string('completion_type', 20)->default('administrative'));
        Schema::create('training_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->string('module_id', 191);
            $table->string('task_id', 191);
            $table->text('response')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'task_id']);
        });
        Schema::create('training_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->string('module_id', 191);
            $table->string('exam_id', 191);
            $table->json('exam_snapshot');
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['assignment_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_exam_attempts');
        Schema::dropIfExists('training_task_submissions');
        Schema::table('training_assignments', fn (Blueprint $table) => $table->dropColumn('learning_progress'));
        Schema::table('training_completions', fn (Blueprint $table) => $table->dropColumn('completion_type'));
    }
};
