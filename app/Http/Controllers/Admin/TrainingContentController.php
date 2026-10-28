<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Support\TrainingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingContentController extends Controller
{
    private function moduleIndex(TrainingProgram $program, string $module): int
    {
        $index = collect($program->module_definitions)->search(fn ($entry) => $entry['id'] === $module);
        abort_if($index === false, 404, 'El módulo ya no está disponible.');
        return $index;
    }

    private function validateChapter(array $module, ?string $chapter, bool $allowLegacy = false): ?string
    {
        if (!$chapter && $allowLegacy) return null;
        if (!$chapter || !collect($module['chapter_definitions'] ?? [])->contains('id', $chapter)) {
            throw ValidationException::withMessages(['chapterId' => 'Selecciona un capítulo de este módulo.']);
        }
        return $chapter;
    }

    public function saveModule(Request $request, string $program, ?string $module = null)
    {
        $data = $request->validate(['stage' => ['required', 'string', 'max:60'], 'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000']]);
        return DB::transaction(function () use ($request, $program, $module, $data) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $modules = $record->module_definitions ?? [];
            if (collect($modules)->contains(fn ($entry) => $entry['id'] !== $module && mb_strtolower($entry['title']) === mb_strtolower($data['title']))) {
                throw ValidationException::withMessages(['title' => 'Ya existe un módulo con ese nombre en este programa.']);
            }
            $index = $module ? $this->moduleIndex($record, $module) : count($modules);
            $modules[$index] = array_replace($modules[$index] ?? ['id' => 'module-'.Str::uuid(), 'project' => $program,
                'chapter_definitions' => [], 'chapters' => 0, 'duration' => '0 min', 'created_by' => $request->user()->id], $data);
            $saved = $modules[$index];
            $exams = collect($record->exam_definitions ?? [])->map(fn ($exam) => ($exam['moduleId'] ?? '') === $saved['id'] ? array_replace($exam, ['module' => $saved['title']]) : $exam)->all();
            $tasks = collect($record->task_definitions ?? [])->map(fn ($task) => ($task['moduleId'] ?? '') === $saved['id'] ? array_replace($task, ['stage' => $saved['stage']]) : $task)->all();
            $record->update(['module_definitions' => $modules, 'exam_definitions' => $exams, 'task_definitions' => $tasks]);
            return response()->json($saved, $module ? 200 : 201);
        });
    }

    public function deleteModule(Request $request, string $program, string $module)
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $paths = DB::transaction(function () use ($program, $module) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $modules = $record->module_definitions;
            $index = $this->moduleIndex($record, $module);
            $paths = collect($modules[$index]['chapter_definitions'] ?? [])->pluck('video_path')->filter()->all();
            unset($modules[$index]);
            $belongsToModule = fn ($activity) => ($activity['moduleId'] ?? '') === $module;
            $record->update(['module_definitions' => array_values($modules),
                'task_definitions' => collect($record->task_definitions ?? [])->reject($belongsToModule)->values()->all(),
                'exam_definitions' => collect($record->exam_definitions ?? [])->reject($belongsToModule)->values()->all()]);
            return $paths;
        });
        if ($paths) Storage::disk('local')->delete($paths);
        return response()->json(['deleted' => $module]);
    }

    public function saveChapter(Request $request, string $program, string $module, ?string $chapter = null)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'objective' => ['nullable', 'string', 'max:2000'],
            'video' => ['nullable', 'url:http,https', 'max:2000'], 'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:51200'], 'remove_video' => ['sometimes', 'boolean']]);
        $newPath = null;
        $oldPath = null;
        try {
            $saved = DB::transaction(function () use ($request, $program, $module, $chapter, $data, &$newPath, &$oldPath) {
                $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
                $modules = $record->module_definitions;
                $index = $this->moduleIndex($record, $module);
                $chapters = $modules[$index]['chapter_definitions'] ?? [];
                $chapterIndex = $chapter ? collect($chapters)->search(fn ($entry) => $entry['id'] === $chapter) : count($chapters);
                abort_if($chapterIndex === false, 404, 'El capítulo ya no está disponible.');
                $existing = $chapters[$chapterIndex] ?? ['id' => 'chapter-'.Str::uuid(), 'video_path' => null, 'video_name' => null];
                $saved = array_replace($existing, Arr::only($data, ['title', 'objective', 'video', 'duration_seconds']));
                if ($request->hasFile('video_file') || $request->boolean('remove_video')) {
                    $oldPath = $existing['video_path'] ?? null;
                    $newPath = $request->file('video_file')?->store('training-videos/'.$program, 'local');
                    $saved['video_path'] = $newPath;
                    $saved['video_name'] = $request->file('video_file')?->getClientOriginalName();
                }
                $saved['duration'] = (int) ceil($data['duration_seconds'] / 60).' min';
                $chapters[$chapterIndex] = $saved;
                $modules[$index]['chapter_definitions'] = array_values($chapters);
                $modules[$index]['chapters'] = count($chapters);
                $modules[$index]['duration'] = (int) ceil(array_sum(array_column($chapters, 'duration_seconds')) / 60).' min';
                $record->update(['module_definitions' => $modules]);
                return $saved;
            });
        } catch (\Throwable $error) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            throw $error;
        }
        if ($oldPath && $oldPath !== $newPath) Storage::disk('local')->delete($oldPath);
        return response()->json($saved, $chapter ? 200 : 201);
    }

    public function reorderChapters(Request $request, string $program, string $module)
    {
        $data = $request->validate([
            'chapter_ids' => ['required', 'array', 'min:1'],
            'chapter_ids.*' => ['required', 'string', 'max:191', 'distinct:strict'],
            'expected_order' => ['required', 'array', 'min:1'],
            'expected_order.*' => ['required', 'string', 'max:191', 'distinct:strict'],
        ]);
        return DB::transaction(function () use ($request, $program, $module, $data) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $modules = $record->module_definitions;
            $index = $this->moduleIndex($record, $module);
            abort_unless(TrainingAccess::canReorderChapters($request->user(), $modules[$index]), 403,
                'Solo el administrador que creó el módulo puede reordenar sus capítulos.');
            $chapters = $modules[$index]['chapter_definitions'] ?? [];
            $current = array_column($chapters, 'id');
            $order = array_values($data['chapter_ids']);
            if (count($order) !== count($current) || array_diff($order, $current)) {
                throw ValidationException::withMessages(['chapter_ids' => 'La lista de capítulos cambió. Actualiza la página antes de reordenarla.']);
            }
            // Do not overwrite another administrator's reorder from a stale page.
            abort_unless($current === array_values($data['expected_order']) || $current === $order, 409,
                'El orden cambió en otra ventana. Actualiza la página antes de reordenar.');
            $byId = collect($chapters)->keyBy('id');
            $modules[$index]['chapter_definitions'] = array_map(fn ($id) => $byId[$id], $order);
            $record->update(['module_definitions' => $modules]);
            return response()->json(['chapter_ids' => $order]);
        });
    }

    public function deleteChapter(Request $request, string $program, string $module, string $chapter)
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $path = DB::transaction(function () use ($program, $module, $chapter) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $modules = $record->module_definitions;
            $index = $this->moduleIndex($record, $module);
            $chapters = collect($modules[$index]['chapter_definitions'] ?? []);
            $entry = $chapters->firstWhere('id', $chapter);
            abort_unless($entry, 404);
            $chapters = $chapters->reject(fn ($entry) => $entry['id'] === $chapter)->values()->all();
            $modules[$index]['chapter_definitions'] = $chapters;
            $modules[$index]['chapters'] = count($chapters);
            $modules[$index]['duration'] = (int) ceil(array_sum(array_column($chapters, 'duration_seconds')) / 60).' min';
            $belongsToChapter = fn ($activity) => ($activity['moduleId'] ?? '') === $module && ($activity['chapterId'] ?? '') === $chapter;
            $record->update(['module_definitions' => $modules,
                'task_definitions' => collect($record->task_definitions ?? [])->reject($belongsToChapter)->values()->all(),
                'exam_definitions' => collect($record->exam_definitions ?? [])->reject($belongsToChapter)->values()->all()]);
            return $entry['video_path'] ?? null;
        });
        if ($path) Storage::disk('local')->delete($path);
        return response()->json(['deleted' => $chapter]);
    }

    public function video(Request $request, string $program, string $module, string $chapter)
    {
        $allowed = collect(TrainingAccess::catalog($request->user()))->firstWhere('id', $program);
        $selectedModule = collect($allowed['module_definitions'] ?? [])->firstWhere('id', $module);
        abort_unless($selectedModule, 403);
        $entry = collect($selectedModule['chapter_definitions'] ?? [])->firstWhere('id', $chapter);
        $path = $entry['video_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function saveTask(Request $request, string $program, ?string $task = null)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'instructions' => ['required', 'string', 'max:5000'],
            'moduleId' => ['required', 'string'], 'chapterId' => ['nullable', 'string', 'max:191'], 'due_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['Activa', 'Borrador', 'Inactiva'])], 'requires_file' => ['required', 'boolean']]);
        $data['requires_file'] = $request->boolean('requires_file');
        return DB::transaction(function () use ($program, $task, $data) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $module = $record->module_definitions[$this->moduleIndex($record, $data['moduleId'])];
            $tasks = $record->task_definitions ?? [];
            $index = $task ? collect($tasks)->search(fn ($entry) => $entry['id'] === $task) : count($tasks);
            abort_if($index === false, 404, 'La tarea ya no está disponible.');
            $data['chapterId'] = $this->validateChapter($module, $data['chapterId'] ?? null, $task && empty($tasks[$index]['chapterId']));
            $saved = $data + ['id' => $task ?: 'task-'.Str::uuid(), 'projectId' => $program, 'project' => $record->title,
                'stage' => $module['stage'], 'due' => !empty($data['due_date']) ? \Carbon\Carbon::parse($data['due_date'])->format('d/m/Y') : 'Sin fecha',
                'tone' => ['Activa' => 'active', 'Borrador' => 'draft', 'Inactiva' => 'inactive'][$data['status']]];
            $tasks[$index] = $saved;
            $record->update(['task_definitions' => $tasks]);
            return response()->json($saved, $task ? 200 : 201);
        });
    }

    private function validateExam(array $input, TrainingProgram $program, bool $allowLegacy = false): array
    {
        $data = validator($input, ['id' => ['required', 'string', 'max:191', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:150'], 'module' => ['required', 'string', 'max:150'],
            'moduleId' => ['nullable', 'string'], 'chapterId' => ['nullable', 'string', 'max:191'], 'instructions' => ['nullable', 'string', 'max:5000'],
            'minimum' => ['required', 'integer', 'between:1,100'], 'duration' => ['required', 'integer', 'between:1,1440'],
            'attempts' => ['required', 'integer', 'between:1,100'], 'randomize' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['Borrador', 'Publicado'])], 'items' => ['present', 'array', 'max:100'],
            'items.*.text' => ['nullable', 'string', 'max:500'], 'items.*.type' => ['required', Rule::in(['single', 'boolean', 'multiple'])],
            'items.*.points' => ['required', 'integer', 'between:1,1000'], 'items.*.options' => ['required', 'array', 'min:2', 'max:10'],
            'items.*.options.*' => ['nullable', 'string', 'max:500'], 'items.*.correct' => ['present'],
        ])->validate();
        $module = collect($program->module_definitions)->first(fn ($entry) => !empty($data['moduleId']) ? $entry['id'] === $data['moduleId'] : $entry['title'] === $data['module']);
        if (!$module) throw ValidationException::withMessages(['module' => 'Selecciona un módulo de este programa.']);
        $data['chapterId'] = $this->validateChapter($module, $data['chapterId'] ?? null, $allowLegacy);
        if ($data['status'] === 'Publicado' && !$data['items']) throw ValidationException::withMessages(['items' => 'Agrega al menos una pregunta antes de publicar.']);
        foreach ($data['items'] as $index => $item) {
            $answers = $item['type'] === 'multiple' ? $item['correct'] : [$item['correct']];
            if (!is_array($answers) || !$answers || collect($answers)->contains(fn ($answer) => !is_int($answer) || $answer < 0 || $answer >= count($item['options']))) {
                throw ValidationException::withMessages(['items' => 'Selecciona una respuesta correcta válida en la pregunta '.($index + 1).'.']);
            }
            if ($item['type'] === 'boolean' && count($item['options']) !== 2) throw ValidationException::withMessages(['items' => 'Las preguntas de verdadero/falso requieren dos opciones.']);
            if ($data['status'] === 'Publicado' && (!trim($item['text'] ?? '') || collect($item['options'])->contains(fn ($value) => !trim($value ?? '')))) {
                throw ValidationException::withMessages(['items' => 'Completa el texto y las opciones de la pregunta '.($index + 1).'.']);
            }
        }
        return array_replace($data, ['projectId' => $program->id, 'project' => $program->title, 'moduleId' => $module['id'],
            'module' => $module['title'], 'questions' => count($data['items']), 'tone' => $data['status'] === 'Publicado' ? 'active' : 'draft']);
    }

    public function saveExam(Request $request, string $program, string $exam)
    {
        return DB::transaction(function () use ($request, $program, $exam) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $existing = collect($record->exam_definitions ?? [])->firstWhere('id', $exam);
            $saved = $this->validateExam(array_replace($request->all(), ['id' => $exam]), $record, $existing && empty($existing['chapterId']));
            $exams = collect($record->exam_definitions ?? [])->keyBy('id');
            $exams[$exam] = $saved;
            $record->update(['exam_definitions' => $exams->values()->all(), 'exams_imported' => true]);
            return response()->json($saved);
        });
    }

    public function importExams(Request $request, string $program)
    {
        $input = $request->validate(['exams' => ['present', 'array', 'max:200']]);
        return DB::transaction(function () use ($input, $program) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            if (!$record->exams_imported) {
                $exams = collect($record->exam_definitions ?? [])->keyBy('id');
                foreach ($input['exams'] as $exam) {
                    $saved = $this->validateExam($exam, $record, true);
                    $exams[$saved['id']] = $saved;
                }
                $record->update(['exam_definitions' => $exams->values()->all(), 'exams_imported' => true]);
            }
            return response()->json(['exams' => $record->exam_definitions ?? []]);
        });
    }
}
