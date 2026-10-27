<?php

namespace App\Support;

use App\Models\TrainingAssignment;
use App\Models\TrainingCompletion;

class TrainingLearningProgress
{
    public static function describe(TrainingAssignment $assignment): array
    {
        $assignment->loadMissing(['program', 'submissions', 'attempts']);
        $progress = $assignment->learning_progress ?? [];
        $total = 0; $done = 0; $previousComplete = true; $next = null;
        $modules = [];
        foreach ($assignment->modules() as $module) {
            $id = $module['id'];
            $completed = $progress['chapters'][$id] ?? [];
            $chapters = $module['chapter_definitions'] ?? [];
            $chapterIds = array_column($chapters, 'id');
            $chapterDone = count(array_intersect(array_column($chapters, 'id'), $completed));
            $chaptersComplete = $chapterDone === count($chapters);
            $hasChapter = fn ($activity) => empty($activity['chapterId']) || in_array($activity['chapterId'], $chapterIds, true);
            $tasks = collect($assignment->program->task_definitions ?? [])->filter(fn ($task) => ($task['moduleId'] ?? '') === $id && ($task['status'] ?? '') === 'Activa' && $hasChapter($task))->values();
            $exams = collect($assignment->program->exam_definitions ?? [])->filter(fn ($exam) => ($exam['moduleId'] ?? '') === $id && ($exam['status'] ?? '') === 'Publicado' && $hasChapter($exam))->values();
            $taskComplete = fn ($task) => $assignment->submissions->contains(fn ($submission) => $submission->task_id === $task['id'] && $submission->status === 'approved');
            $examComplete = fn ($exam) => $assignment->attempts->contains(fn ($attempt) => $attempt->exam_id === $exam['id'] && $attempt->passed);
            $taskDone = $tasks->filter($taskComplete)->count();
            $examDone = $exams->filter($examComplete)->count();
            $unlocked = $previousComplete;
            $chapterStates = []; $previousChapterComplete = $unlocked;
            foreach ($chapters as $chapter) {
                $chapterId = $chapter['id'];
                $read = in_array($chapterId, $completed, true);
                $chapterTasks = $tasks->where('chapterId', $chapterId);
                $chapterExams = $exams->where('chapterId', $chapterId);
                $tasksComplete = $chapterTasks->every($taskComplete);
                $examsComplete = $chapterExams->every($examComplete);
                $chapterComplete = $read && $tasksComplete && $examsComplete;
                $chapterStates[$chapterId] = ['available' => $previousChapterComplete, 'complete' => $chapterComplete,
                    'content_complete' => $read, 'tasks_complete' => $tasksComplete,
                    'task_count' => $chapterTasks->count(), 'exam_count' => $chapterExams->count()];
                if (!$next && $previousChapterComplete && !$chapterComplete) {
                    $kind = !$read ? 'chapter' : (!$tasksComplete ? 'task' : 'exam');
                    $next = ['module_id' => $id, 'chapter_id' => $chapterId, 'kind' => $kind,
                        'label' => $module['stage'].' · '.$chapter['title'].($kind === 'chapter' ? '' : ($kind === 'task' ? ' · Tareas pendientes' : ' · Exámenes pendientes'))];
                }
                $previousChapterComplete = $previousChapterComplete && $chapterComplete;
            }
            $legacyTasksComplete = $tasks->filter(fn ($task) => empty($task['chapterId']))->every($taskComplete);
            $tasks = $tasks->map(function ($task) use ($chapterStates, $previousChapterComplete) {
                $state = $chapterStates[$task['chapterId'] ?? ''] ?? null;
                return array_replace($task, ['available' => $state ? $state['available'] && $state['content_complete'] : $previousChapterComplete]);
            });
            $exams = $exams->map(function ($exam) use ($chapterStates, $previousChapterComplete, $legacyTasksComplete) {
                $state = $chapterStates[$exam['chapterId'] ?? ''] ?? null;
                return array_replace($exam, ['available' => $state ? $state['available'] && $state['content_complete'] && $state['tasks_complete'] : $previousChapterComplete && $legacyTasksComplete]);
            });
            $moduleTotal = count($chapters) + $tasks->count() + $exams->count();
            $moduleDone = $chapterDone + $taskDone + $examDone;
            $complete = $moduleTotal > 0 && $moduleDone === $moduleTotal;
            $previousComplete = $previousComplete && $complete;
            $state = $complete ? 'completed' : ($unlocked ? 'current' : 'locked');
            $module += ['description' => ''];
            $module['state'] = $state;
            $module['progress'] = $moduleTotal ? (int) floor(100 * $moduleDone / $moduleTotal) : 0;
            $module['chapters_complete'] = $chaptersComplete;
            $module['tasks_complete'] = $taskDone === $tasks->count();
            $module['tasks'] = $tasks->all();
            $module['exams'] = $exams->all();
            $module['completed_chapters'] = $completed;
            $module['chapter_states'] = $chapterStates;
            $modules[] = $module;
            if (!$next && !$complete && $unlocked) {
                $next = ['module_id' => $id, 'chapter_id' => null, 'kind' => !$module['tasks_complete'] ? 'task' : 'exam',
                    'label' => $module['stage'].' · '.(!$module['tasks_complete'] ? 'Tareas pendientes' : ($exams->isNotEmpty() ? 'Exámenes pendientes' : 'Contenido por publicar'))];
            }
            $total += $moduleTotal; $done += $moduleDone;
        }
        return ['modules' => $modules, 'progress' => $total ? (int) floor(100 * $done / $total) : 0,
            'completed_steps' => $done, 'total_steps' => $total, 'next' => $next,
            'complete' => count($modules) > 0 && $previousComplete, 'last_module_id' => $progress['last_module_id'] ?? null];
    }

    public static function syncCompletion(TrainingAssignment $assignment): void
    {
        $assignment->unsetRelation('submissions')->unsetRelation('attempts');
        if ($assignment->status === 'completed' || !self::describe($assignment)['complete']) return;
        $assignment->update(['status' => 'completed', 'completed_at' => now()]);
        $student = TrainingAssignedStudents::person($assignment->user);
        TrainingCompletion::firstOrCreate(['student_key' => 'user-'.$assignment->user_id, 'program_id' => $assignment->program_id,
            'start_date' => $assignment->start_date->format('Y-m-d')], [
            'user_id' => $assignment->user_id, 'student_name' => $student['name'], 'student_snapshot' => $student,
            'program_name' => $assignment->program->title, 'completed_at' => now(), 'completed_by' => $assignment->user_id,
            'completed_by_name' => $student['name'], 'completion_type' => 'earned',
        ]);
    }
}
