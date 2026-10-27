<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingAssignment;
use App\Models\TrainingCompletion;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TrainingAssignmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['Super Admin', 'Admin']), 403);
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'programs' => ['required', 'array', 'min:1', 'max:100'],
            'programs.*.program_id' => ['required', 'string', 'distinct', 'exists:training_programs,id'],
            'programs.*.module_ids' => ['required', 'array', 'min:1', 'max:200'],
            'programs.*.module_ids.*' => ['required', 'string', 'max:191'],
        ]);
        $student = User::whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['Cliente', 'Institucion']))
            ->findOrFail($data['user_id']);
        DB::transaction(function () use ($data, $student, $request) {
            // Serialize concurrent submissions for this employee, including retries after a lost response.
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            foreach ($data['programs'] as $entry) {
                $program = TrainingProgram::whereKey($entry['program_id'])->lockForUpdate()->firstOrFail();
                $ids = array_values(array_unique($entry['module_ids']));
                if (array_diff($ids, array_column($program->module_definitions ?? [], 'id'))) {
                    throw ValidationException::withMessages(['programs' => 'Los módulos cambiaron. Actualiza la página y vuelve a seleccionarlos.']);
                }
                $key = ['user_id' => $student->id, 'program_id' => $program->id, 'start_date' => $data['start_date']];
                $assignment = TrainingAssignment::firstOrNew($key);
                if ($assignment->status === 'completed' || TrainingCompletion::where('student_key', 'user-'.$student->id)
                    ->where('program_id', $program->id)->whereDate('start_date', $data['start_date'])->exists()) {
                    throw ValidationException::withMessages(['start_date' => 'Esta capacitación ya está concluida. Elige otra fecha para una nueva capacitación.']);
                }
                $assignment->module_ids = array_values(array_unique(array_merge($assignment->module_ids ?? [], $ids)));
                if (! $assignment->exists) {
                    $assignment->assigned_by = $request->user()->id;
                    $assignment->status = 'assigned';
                }
                $assignment->save();
            }
        });
        return response()->json(['message' => 'Capacitación asignada al alumno.',
            'redirect' => route('admin.capacitaciones.alumnos', ['training_view' => 'in_progress'])]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id' => ['sometimes', 'integer', 'exists:users,id']]);
        $userId = (int) ($data['user_id'] ?? $request->user()->id);
        $readOnly = $userId !== $request->user()->id;
        $canReview = $request->user()->hasAnyRole(['Super Admin', 'Admin']);
        abort_if($readOnly && !$canReview, 403);
        $student = User::findOrFail($userId);
        $assignments = TrainingAssignment::with(['program', 'submissions', 'attempts'])->where('user_id', $userId)->orderByDesc('start_date')->latest('id')->get();
        return response()->json(['student' => ['id' => $student->id, 'name' => trim($student->name.' '.$student->lastname)],
            'read_only' => $readOnly, 'can_review' => $canReview,
            'assignments' => $assignments->map(fn ($assignment) => $this->entry($assignment))->values()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function start(Request $request, TrainingAssignment $assignment): JsonResponse
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);
        DB::transaction(function () use (&$assignment) {
            $assignment = TrainingAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_if($assignment->status === 'completed', 409, 'La capacitación ya está concluida.');
            abort_unless(count($assignment->modules()), 422, 'Los módulos de esta capacitación ya no están disponibles.');
            if (! $assignment->started_at) {
                $assignment->update(['status' => 'in_progress', 'started_at' => now()]);
            }
        });
        return response()->json(['assignment' => $this->entry($assignment)])->header('Cache-Control', 'no-store, private');
    }

    public function video(Request $request, TrainingAssignment $assignment, string $module, string $chapter)
    {
        $canReview = $request->user()->hasAnyRole(['Super Admin', 'Admin']);
        abort_unless($canReview || ($assignment->user_id === $request->user()->id && $assignment->started_at), 403);
        $state = collect(\App\Support\TrainingLearningProgress::describe($assignment)['modules'])->firstWhere('id', $module);
        abort_unless($state && ($canReview || $state['state'] !== 'locked'), 403);
        abort_unless($canReview || ($state['chapter_states'][$chapter]['available'] ?? false), 403);
        $selected = collect($assignment->modules())->firstWhere('id', $module);
        abort_unless($selected, 403);
        $entry = collect($selected['chapter_definitions'] ?? [])->firstWhere('id', $chapter);
        $path = $entry['video_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function entry(TrainingAssignment $assignment): array
    {
        $learning = \App\Support\TrainingLearningProgress::describe($assignment);
        return [
            'id' => $assignment->id, 'program_id' => $assignment->program_id, 'title' => $assignment->program->title,
            'description' => $assignment->program->description, 'start_date' => $assignment->start_date->format('Y-m-d'),
            'status' => $assignment->status, 'started_at' => $assignment->started_at?->toISOString(),
            'start_url' => route('admin.capacitaciones.asignaciones.start', $assignment),
            'progress' => $learning['progress'], 'next' => $learning['next'], 'completed_steps' => $learning['completed_steps'],
            'total_steps' => $learning['total_steps'], 'last_module_id' => $learning['last_module_id'],
            'modules' => array_map(fn ($module) => [
                'id' => $module['id'], 'stage' => $module['stage'], 'title' => $module['title'], 'description' => $module['description'] ?? '',
                'state' => $module['state'], 'progress' => $module['progress'], 'duration' => $module['duration'] ?? '',
                'tasks' => array_map(function ($task) use ($assignment, $module) {
                    $submission = $assignment->submissions->firstWhere('task_id', $task['id']);
                    return \Illuminate\Support\Arr::only($task, ['id', 'title', 'instructions', 'due_date', 'requires_file', 'chapterId', 'available']) + [
                        'chapter_title' => collect($module['chapter_definitions'] ?? [])->firstWhere('id', $task['chapterId'] ?? '')['title'] ?? null,
                        'submit_url' => route('admin.capacitaciones.asignaciones.task', [$assignment, $module['id'], $task['id']]),
                        'submission' => $submission ? ['id' => $submission->id, 'status' => $submission->status, 'response' => $submission->response,
                            'file_name' => $submission->file_name, 'feedback' => $submission->feedback, 'submitted_at' => $submission->updated_at->toISOString(),
                            'file_url' => $submission->file_path ? route('admin.capacitaciones.asignaciones.task.file', [$assignment, $submission]) : null,
                            'review_url' => route('admin.capacitaciones.asignaciones.task.review', [$assignment, $submission])] : null,
                    ];
                }, $module['tasks']),
                'exams' => array_map(function ($exam) use ($assignment, $module) {
                    $attempts = $assignment->attempts->where('exam_id', $exam['id'])->sortByDesc('id')->values();
                    return \Illuminate\Support\Arr::only($exam, ['id', 'name', 'instructions', 'minimum', 'duration', 'attempts', 'chapterId', 'available']) + [
                        'chapter_title' => collect($module['chapter_definitions'] ?? [])->firstWhere('id', $exam['chapterId'] ?? '')['title'] ?? null,
                        'question_count' => count($exam['items'] ?? []), 'passed' => $attempts->contains('passed', true),
                        'begin_url' => route('admin.capacitaciones.asignaciones.exam', [$assignment, $module['id'], $exam['id']]),
                        'results' => $attempts->map(function ($attempt) {
                            $expired = !$attempt->submitted_at && $attempt->expires_at->isPast();
                            return ['id' => $attempt->id, 'score' => $expired ? 0 : $attempt->score, 'passed' => $attempt->passed,
                                'submitted_at' => ($attempt->submitted_at ?? ($expired ? $attempt->expires_at : null))?->toISOString(),
                                'expires_at' => $attempt->expires_at->toISOString()];
                        })->all(),
                    ];
                }, $module['exams']),
                'chapters' => array_map(fn ($chapter) => [
                    'id' => $chapter['id'], 'title' => $chapter['title'], 'objective' => $chapter['objective'] ?? '', 'duration' => $chapter['duration'] ?? '',
                    'completed' => in_array($chapter['id'], $module['completed_chapters'], true),
                    'activity_state' => $module['chapter_states'][$chapter['id']],
                    'complete_url' => route('admin.capacitaciones.asignaciones.chapter', [$assignment, $module['id'], $chapter['id']]),
                    'video_url' => preg_match('~^https?://~i', $chapter['video'] ?? '') ? $chapter['video'] : null,
                    'file_url' => empty($chapter['video_path']) ? null : route('admin.capacitaciones.asignaciones.video', [
                        'assignment' => $assignment->id, 'module' => $module['id'], 'chapter' => $chapter['id'],
                    ]),
                ], $module['chapter_definitions'] ?? []),
            ], $learning['modules']),
        ];
    }
}
