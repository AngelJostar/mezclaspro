<?php

namespace Tests\Feature;

use App\Models\TrainingAssignment;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingProgram;
use App\Models\TrainingTaskSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingLearningTest extends TestCase
{
    private User $student;
    private User $manager;
    private TrainingAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']); DB::purge('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
            '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php',
            '2026_10_26_000001_create_training_completions_table.php', '2026_10_26_000002_create_training_assignments_table.php',
            '2026_10_26_000003_add_training_learning_progress.php'] as $file) (require database_path('migrations/'.$file))->up();
        foreach (['student' => 'Capacitacion', 'manager' => 'Admin'] as $property => $role) {
            $this->$property = User::create(['name' => $property, 'lastname' => 'Prueba', 'username' => $property, 'password' => bcrypt('test'), 'is_active' => true]);
            $this->$property->assignRole(Role::findOrCreate($role));
        }
        TrainingProgram::create(['id' => 'real', 'title' => 'Programa real', 'owner' => 'Calidad', 'status' => 'Activo',
            'module_definitions' => [
                ['id' => 'm1', 'stage' => 'Módulo 1', 'title' => 'Primer módulo', 'chapter_definitions' => [
                    ['id' => 'c1', 'title' => 'Primer capítulo'], ['id' => 'c2', 'title' => 'Segundo capítulo']]],
                ['id' => 'm2', 'stage' => 'Módulo 2', 'title' => 'Segundo módulo', 'chapter_definitions' => [['id' => 'c1', 'title' => 'Último capítulo']]],
            ], 'task_definitions' => [['id' => 't1', 'moduleId' => 'm1', 'title' => 'Evidencia', 'instructions' => 'Adjunta el documento.', 'status' => 'Activa', 'requires_file' => true]],
            'exam_definitions' => [['id' => 'e1', 'moduleId' => 'm1', 'name' => 'Examen real', 'status' => 'Publicado', 'minimum' => 80, 'duration' => 10, 'attempts' => 2,
                'items' => [['text' => 'Elige dos', 'type' => 'multiple', 'points' => 2, 'options' => ['A', 'B', 'C'], 'correct' => [0, 2]],
                    ['text' => 'Es verdadero', 'type' => 'boolean', 'points' => 1, 'options' => ['Verdadero', 'Falso'], 'correct' => 0]]]],
        ]);
        $this->assignment = TrainingAssignment::create(['user_id' => $this->student->id, 'program_id' => 'real', 'module_ids' => ['m1', 'm2'], 'start_date' => '2026-10-26', 'assigned_by' => $this->manager->id, 'status' => 'in_progress', 'started_at' => now()]);
        $this->actingAs($this->student); Storage::fake('local');
    }

    private function chapter(string $module, string $chapter)
    {
        return $this->postJson(route('admin.capacitaciones.asignaciones.chapter', [$this->assignment, $module, $chapter]));
    }

    private function readyForExam(): void
    {
        $this->chapter('m1', 'c1')->assertOk(); $this->chapter('m1', 'c2')->assertOk();
        $this->post(route('admin.capacitaciones.asignaciones.task', [$this->assignment, 'm1', 't1']), [
            'response' => 'Mi evidencia', 'file' => UploadedFile::fake()->create('evidencia.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->actingAs($this->manager);
        $this->postJson(route('admin.capacitaciones.asignaciones.task.review', [$this->assignment, TrainingTaskSubmission::sole()]), ['status' => 'approved'])->assertOk();
        $this->actingAs($this->student);
    }

    public function test_reordered_chapters_reach_the_learner_without_losing_existing_progress(): void
    {
        $this->chapter('m1', 'c1')->assertOk();
        $before = $this->assignment->fresh()->learning_progress;
        $this->actingAs($this->manager);
        $this->patchJson(route('admin.capacitaciones.programas.catalogo.chapters.reorder', ['real', 'm1']),
            ['chapter_ids' => ['c2', 'c1'], 'expected_order' => ['c1', 'c2']])->assertOk();
        $this->actingAs($this->student);
        $entry = $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertOk()
            ->assertJsonPath('assignments.0.next.chapter_id', 'c2');
        $this->assertSame(['c2', 'c1'], array_column($entry->json('assignments.0.modules.0.chapters'), 'id'));
        $this->assertSame($before, $this->assignment->fresh()->learning_progress);
        $this->chapter('m1', 'c2')->assertOk();
        $this->assertSame(['c1', 'c2'], $this->assignment->fresh()->learning_progress['chapters']['m1']);
    }

    public function test_persistent_syllabus_enforces_chapters_tasks_exams_and_moves_to_next_module(): void
    {
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonPath('assignments.0.next.chapter_id', 'c1');
        $this->chapter('m2', 'c1')->assertUnprocessable(); $this->chapter('m1', 'c2')->assertUnprocessable();
        $this->chapter('m1', 'not-assigned')->assertNotFound();
        $this->postJson(route('admin.capacitaciones.asignaciones.exam', [$this->assignment, 'm1', 'e1']))->assertUnprocessable();
        $this->readyForExam();
        $entry = $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonPath('assignments.0.progress', 60);
        $this->assertStringNotContainsString('"correct"', $entry->getContent());
        $attempt = $this->postJson(route('admin.capacitaciones.asignaciones.exam', [$this->assignment, 'm1', 'e1']))->assertOk();
        $this->assertStringNotContainsString('"correct"', $attempt->getContent());
        $url = $attempt->json('attempt.submit_url');
        $this->postJson($url, ['answers' => [[2, 0], [0]]])->assertOk()->assertJsonPath('assignment.progress', 80)->assertJsonPath('assignment.next.module_id', 'm2');
        $this->postJson($url, ['answers' => [[], []]])->assertOk();
        $this->assertSame(100, TrainingExamAttempt::sole()->score);
        $this->chapter('m2', 'c1')->assertOk()->assertJsonPath('assignment.progress', 100)->assertJsonPath('assignment.status', 'completed');
        $this->assertDatabaseHas('training_completions', ['user_id' => $this->student->id, 'completion_type' => 'earned']);
        $this->assertSame(['c1', 'c2'], $this->assignment->fresh()->learning_progress['chapters']['m1']);
    }

    public function test_private_evidence_review_and_selected_user_are_authorized(): void
    {
        $this->chapter('m1', 'c1'); $this->chapter('m1', 'c2');
        $taskUrl = route('admin.capacitaciones.asignaciones.task', [$this->assignment, 'm1', 't1']);
        $this->postJson($taskUrl, ['response' => 'Sin evidencia'])->assertUnprocessable();
        $entry = $this->post($taskUrl, ['file' => UploadedFile::fake()->create('evidencia.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $fileUrl = $entry->json('assignment.modules.0.tasks.0.submission.file_url');
        $review = $entry->json('assignment.modules.0.tasks.0.submission.review_url');
        $this->get($fileUrl)->assertOk();
        $this->postJson($review, ['status' => 'approved'])->assertForbidden();
        $this->getJson(route('admin.capacitaciones.asignaciones.index', ['user_id' => $this->manager->id]))->assertForbidden();
        $this->actingAs($this->manager);
        $this->getJson(route('admin.capacitaciones.asignaciones.index', ['user_id' => $this->student->id]))->assertOk()->assertJsonPath('read_only', true)->assertJsonPath('can_review', true);
        $this->chapter('m1', 'c1')->assertForbidden();
        $this->postJson($review, ['status' => 'returned'])->assertUnprocessable();
        $this->postJson($review, ['status' => 'returned', 'feedback' => 'Completa el documento'])->assertOk();
        $this->actingAs($this->student);
        $this->postJson($taskUrl, ['response' => 'Documento corregido'])->assertOk();
        $this->assertSame('submitted', TrainingTaskSubmission::sole()->status);
    }

    public function test_exam_attempts_are_resumable_timed_and_not_overwritten_by_catalog_changes(): void
    {
        $this->readyForExam();
        $begin = route('admin.capacitaciones.asignaciones.exam', [$this->assignment, 'm1', 'e1']);
        $first = $this->postJson($begin)->assertOk();
        $this->postJson($begin)->assertOk()->assertJsonPath('attempt.id', $first->json('attempt.id'));
        $program = TrainingProgram::find('real'); $exams = $program->exam_definitions; $exams[0]['items'][0]['correct'] = [1]; $program->update(['exam_definitions' => $exams]);
        $this->travel(11)->minutes();
        $this->postJson($first->json('attempt.submit_url'), ['answers' => [[0, 2], [0]]])->assertOk();
        $this->assertSame(0, TrainingExamAttempt::first()->score);
        $second = $this->postJson($begin)->assertOk();
        $this->travel(11)->minutes();
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonPath('assignments.0.modules.0.exams.0.results.0.score', 0);
        $this->postJson($begin)->assertUnprocessable();
        $this->assertNotNull(TrainingExamAttempt::latest('id')->first()->submitted_at);
        $this->assertDatabaseCount('training_exam_attempts', 2);
        $this->assertSame([0, 2], TrainingExamAttempt::first()->exam_snapshot['items'][0]['correct']);
    }

    public function test_each_chapter_requires_its_own_task_and_exam_before_the_next_chapter(): void
    {
        $program = TrainingProgram::find('real');
        $tasks = $program->task_definitions; $tasks[0]['chapterId'] = 'c1';
        $exams = $program->exam_definitions; $exams[0]['chapterId'] = 'c1';
        $program->update(['task_definitions' => $tasks, 'exam_definitions' => $exams]);
        $taskUrl = route('admin.capacitaciones.asignaciones.task', [$this->assignment, 'm1', 't1']);
        $examUrl = route('admin.capacitaciones.asignaciones.exam', [$this->assignment, 'm1', 'e1']);
        $this->postJson($taskUrl, ['response' => 'Prematura'])->assertUnprocessable();
        $this->chapter('m1', 'c1')->assertOk()->assertJsonPath('assignment.next.chapter_id', 'c1')->assertJsonPath('assignment.next.kind', 'task');
        $this->chapter('m1', 'c2')->assertUnprocessable();
        $this->postJson($examUrl)->assertUnprocessable();
        $this->post($taskUrl, ['file' => UploadedFile::fake()->create('evidencia.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $this->actingAs($this->manager);
        $this->postJson(route('admin.capacitaciones.asignaciones.task.review', [$this->assignment, TrainingTaskSubmission::sole()]), ['status' => 'approved'])->assertOk()->assertJsonPath('assignment.next.kind', 'exam');
        $this->actingAs($this->student);
        $this->chapter('m1', 'c2')->assertUnprocessable();
        $attempt = $this->postJson($examUrl)->assertOk();
        $this->postJson($attempt->json('attempt.submit_url'), ['answers' => [[0, 2], [0]]])->assertOk()->assertJsonPath('assignment.next.chapter_id', 'c2');
        $this->chapter('m1', 'c2')->assertOk()->assertJsonPath('assignment.next.module_id', 'm2');
    }
}
