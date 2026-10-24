<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->json('exam_definitions')->nullable();
            $table->json('task_definitions')->nullable();
            $table->boolean('exams_imported')->default(false);
        });
        foreach (DB::table('training_programs')->get() as $program) {
            $modules = json_decode($program->module_definitions, true) ?: [];
            foreach ($modules as &$module) {
                $module['chapter_definitions'] = array_map(fn ($chapter, $index) => $chapter + [
                    'id' => 'chapter-'.($index + 1), 'duration_seconds' => (int) $chapter['duration'] * 60,
                    'video_path' => null, 'video_name' => null,
                ], \App\Support\TrainingContentDefaults::chapters(), [0, 1]);
                $module['chapters'] = count($module['chapter_definitions']);
                $module['duration'] = '21 min';
            }
            unset($module);
            $exams = collect(\App\Support\TrainingContentDefaults::exams())->where('projectId', $program->id)->map(function ($exam) use ($modules) {
                $module = collect($modules)->firstWhere('title', $exam['module']);
                return $exam + ['moduleId' => $module['id'] ?? '', 'name' => 'Evaluación de '.$exam['module'],
                    'instructions' => '', 'duration' => 30, 'attempts' => 3, 'randomize' => false];
            })->values()->all();
            $tasks = collect(\App\Support\TrainingContentDefaults::programTasks())->where('projectId', $program->id)->values()->map(function ($task, $index) use ($modules) {
                $module = collect($modules)->firstWhere('stage', $task['stage']);
                return $task + ['id' => 'task-'.($index + 1), 'moduleId' => $module['id'] ?? '', 'instructions' => '',
                    'due_date' => $task['due'] === '22 ago 2026' ? '2026-08-22' : ($task['due'] === '25 ago 2026' ? '2026-08-25' : null), 'requires_file' => true];
            })->all();
            DB::table('training_programs')->where('id', $program->id)->update([
                'module_definitions' => json_encode($modules, JSON_UNESCAPED_UNICODE),
                'exam_definitions' => json_encode($exams, JSON_UNESCAPED_UNICODE),
                'task_definitions' => json_encode($tasks, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('training_programs', fn (Blueprint $table) => $table->dropColumn(['exam_definitions', 'task_definitions', 'exams_imported']));
    }
};
