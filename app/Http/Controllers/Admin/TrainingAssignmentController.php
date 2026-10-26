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
        $assignments = TrainingAssignment::with('program')->where('user_id', $request->user()->id)->latest('id')->get();
        return response()->json(['assignments' => $assignments->map(fn ($assignment) => $this->entry($assignment))->values()])
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
        abort_unless($assignment->user_id === $request->user()->id && $assignment->status === 'in_progress', 403);
        $selected = collect($assignment->modules())->firstWhere('id', $module);
        abort_unless($selected, 403);
        $entry = collect($selected['chapter_definitions'] ?? [])->firstWhere('id', $chapter);
        $path = $entry['video_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    private function entry(TrainingAssignment $assignment): array
    {
        return [
            'id' => $assignment->id, 'program_id' => $assignment->program_id, 'title' => $assignment->program->title,
            'description' => $assignment->program->description, 'start_date' => $assignment->start_date->format('Y-m-d'),
            'status' => $assignment->status, 'started_at' => $assignment->started_at?->toISOString(),
            'start_url' => route('admin.capacitaciones.asignaciones.start', $assignment),
            'modules' => array_map(fn ($module) => [
                'id' => $module['id'], 'stage' => $module['stage'], 'title' => $module['title'], 'description' => $module['description'] ?? '',
                'chapters' => array_map(fn ($chapter) => [
                    'id' => $chapter['id'], 'title' => $chapter['title'], 'objective' => $chapter['objective'] ?? '', 'duration' => $chapter['duration'] ?? '',
                    'video_url' => preg_match('~^https?://~i', $chapter['video'] ?? '') ? $chapter['video'] : null,
                    'file_url' => empty($chapter['video_path']) ? null : route('admin.capacitaciones.asignaciones.video', [
                        'assignment' => $assignment->id, 'module' => $module['id'], 'chapter' => $chapter['id'],
                    ]),
                ], $module['chapter_definitions'] ?? []),
            ], $assignment->modules()),
        ];
    }
}
