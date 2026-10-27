<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingAssignment;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingTaskSubmission;
use App\Support\TrainingLearningProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainingLearningController extends Controller
{
    private function writable(Request $request, TrainingAssignment $assignment): TrainingAssignment
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);
        $assignment = TrainingAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
        abort_unless($assignment->status === 'in_progress', 409, 'Inicia la capacitación o verifica que no haya sido concluida.');
        return $assignment;
    }

    private function module(TrainingAssignment $assignment, string $id): array
    {
        $module = collect(TrainingLearningProgress::describe($assignment)['modules'])->firstWhere('id', $id);
        abort_unless($module, 404);
        abort_if($module['state'] === 'locked', 422, 'Completa el módulo anterior antes de continuar.');
        return $module;
    }

    private function response(TrainingAssignment $assignment)
    {
        return response()->json(['assignment' => app(TrainingAssignmentController::class)->entry($assignment->fresh())])
            ->header('Cache-Control', 'no-store, private');
    }

    public function chapter(Request $request, TrainingAssignment $assignment, string $module, string $chapter)
    {
        DB::transaction(function () use ($request, $assignment, $module, $chapter) {
            $assignment = $this->writable($request, $assignment);
            $state = $this->module($assignment, $module);
            $chapters = collect($state['chapter_definitions'] ?? []);
            $index = $chapters->search(fn ($entry) => $entry['id'] === $chapter);
            abort_if($index === false, 404);
            abort_unless($state['chapter_states'][$chapter]['available'], 422, 'Completa las tareas y los exámenes del capítulo anterior antes de continuar.');
            abort_if($chapters->take($index)->contains(fn ($entry) => !in_array($entry['id'], $state['completed_chapters'], true)), 422, 'Revisa primero los capítulos anteriores.');
            $progress = $assignment->learning_progress ?? [];
            $progress['chapters'][$module] = array_values(array_unique([...($progress['chapters'][$module] ?? []), $chapter]));
            $progress['last_module_id'] = $module;
            $assignment->update(['learning_progress' => $progress]);
            TrainingLearningProgress::syncCompletion($assignment);
        });
        return $this->response($assignment);
    }

    public function task(Request $request, TrainingAssignment $assignment, string $module, string $task)
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);
        $data = $request->validate(['response' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt', 'max:10240']]);
        $path = null; $oldPath = null;
        try {
            DB::transaction(function () use ($request, $assignment, $module, $task, $data, &$path, &$oldPath) {
                $assignment = $this->writable($request, $assignment);
                $state = $this->module($assignment, $module);
                $definition = collect($state['tasks'])->firstWhere('id', $task);
                abort_unless($definition, 404);
                abort_unless($definition['available'], 422, 'Revisa el capítulo y completa las actividades anteriores antes de entregar la tarea.');
                $submission = TrainingTaskSubmission::firstOrNew(['assignment_id' => $assignment->id, 'task_id' => $task]);
                abort_if(in_array($submission->status, ['approved', 'submitted']), 409, 'Esta tarea ya fue entregada.');
                if (($definition['requires_file'] ?? false) && !$request->hasFile('file') && !$submission->file_path) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Adjunta la evidencia solicitada.']);
                }
                if (!trim($data['response'] ?? '') && !$request->hasFile('file') && !$submission->file_path) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['response' => 'Escribe tu respuesta o adjunta una evidencia.']);
                }
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('training-submissions/'.$assignment->id, 'local');
                    $oldPath = $submission->file_path;
                    $submission->file_path = $path;
                    $submission->file_name = $request->file('file')->getClientOriginalName();
                }
                $submission->fill(['module_id' => $module, 'response' => $data['response'] ?? '', 'status' => 'submitted',
                    'feedback' => null, 'reviewed_at' => null, 'reviewed_by' => null])->save();
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return $this->response($assignment);
    }

    public function review(Request $request, TrainingAssignment $assignment, TrainingTaskSubmission $submission)
    {
        abort_unless($request->user()->hasAnyRole(['Admin', 'Super Admin']), 403);
        abort_unless($submission->assignment_id === $assignment->id, 404);
        $data = $request->validate(['status' => ['required', 'in:approved,returned'], 'feedback' => ['nullable', 'string', 'max:5000', 'required_if:status,returned']]);
        DB::transaction(function () use ($request, $assignment, $submission, $data) {
            $assignment = TrainingAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_if($assignment->status === 'completed', 409, 'La capacitación está concluida.');
            $submission->refresh();
            abort_unless($submission->status === 'submitted', 409, 'Esta entrega ya fue revisada.');
            $submission->update($data + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            TrainingLearningProgress::syncCompletion($assignment);
        });
        return $this->response($assignment);
    }

    public function file(Request $request, TrainingAssignment $assignment, TrainingTaskSubmission $submission)
    {
        abort_unless($assignment->user_id === $request->user()->id || $request->user()->hasAnyRole(['Admin', 'Super Admin']), 403);
        abort_unless($submission->assignment_id === $assignment->id && $submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);
        return Storage::disk('local')->download($submission->file_path, $submission->file_name, ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function exam(Request $request, TrainingAssignment $assignment, string $module, string $exam)
    {
        $attempt = DB::transaction(function () use ($request, $assignment, $module, $exam) {
            $assignment = $this->writable($request, $assignment);
            $state = $this->module($assignment, $module);
            $definition = collect($state['exams'])->firstWhere('id', $exam);
            abort_unless($definition && count($definition['items'] ?? []), 404);
            abort_unless($definition['available'], 422, 'Completa el capítulo y espera la aprobación de sus tareas.');
            $attempts = TrainingExamAttempt::where('assignment_id', $assignment->id)->where('exam_id', $exam)->orderByDesc('id')->get();
            abort_if($attempts->contains('passed', true), 409, 'Ya aprobaste este examen.');
            $open = $attempts->first(fn ($entry) => !$entry->submitted_at);
            if ($open && $open->expires_at->isFuture()) return $open;
            if ($open) $open->update(['submitted_at' => now(), 'score' => 0, 'answers' => []]);
            // Commit the expired attempt even when no retry remains.
            if ($attempts->count() >= ($definition['attempts'] ?? 1)) return null;
            if ($definition['randomize'] ?? false) shuffle($definition['items']);
            return TrainingExamAttempt::create(['assignment_id' => $assignment->id, 'module_id' => $module, 'exam_id' => $exam,
                'exam_snapshot' => $definition, 'expires_at' => now()->addMinutes($definition['duration'] ?? 30)]);
        });
        abort_unless($attempt, 422, 'Agotaste los intentos disponibles para este examen.');
        $exam = $attempt->exam_snapshot;
        return response()->json(['attempt' => ['id' => $attempt->id, 'name' => $exam['name'], 'instructions' => $exam['instructions'] ?? '',
            'expires_at' => $attempt->expires_at->toISOString(), 'minimum' => $exam['minimum'],
            'submit_url' => route('admin.capacitaciones.asignaciones.exam.submit', [$assignment, $attempt]),
            'items' => array_map(fn ($item) => Arr::only($item, ['text', 'type', 'options', 'points']), $exam['items'])]])->header('Cache-Control', 'no-store, private');
    }

    public function submitExam(Request $request, TrainingAssignment $assignment, TrainingExamAttempt $attempt)
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);
        abort_unless($attempt->assignment_id === $assignment->id, 404);
        $data = $request->validate(['answers' => ['present', 'array', 'max:100'], 'answers.*' => ['present', 'array', 'max:10'], 'answers.*.*' => ['integer', 'min:0', 'max:9']]);
        DB::transaction(function () use ($request, $assignment, $attempt, $data) {
            // Repeat submissions return the already stored result, including after automatic course completion.
            $assignment = TrainingAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            $attempt->refresh();
            if ($attempt->submitted_at) return;
            abort_unless($assignment->status === 'in_progress', 409, 'La capacitación está concluida.');
            $definition = $attempt->exam_snapshot;
            $earned = 0; $total = 0;
            foreach ($definition['items'] as $index => $item) {
                $points = $item['points'] ?? 1;
                $correct = is_array($item['correct']) ? $item['correct'] : [$item['correct']];
                $answer = array_values(array_unique($data['answers'][$index] ?? []));
                sort($correct); sort($answer);
                if ($answer === $correct) $earned += $points;
                $total += $points;
            }
            $score = $attempt->expires_at->isPast() || !$total ? 0 : (int) floor(100 * $earned / $total);
            $attempt->update(['answers' => $data['answers'], 'score' => $score, 'passed' => $score >= $definition['minimum'], 'submitted_at' => now()]);
            TrainingLearningProgress::syncCompletion($assignment);
        });
        return $this->response($assignment);
    }
}
