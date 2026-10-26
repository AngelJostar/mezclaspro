<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TrainingPersonnelController;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingAssignmentTest extends TestCase
{
    private User $manager;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2024_01_10_035338_create_hospitals_table.php',
            '2024_02_05_235617_add_hospital_to_users_table.php', '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
            '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php',
            '2026_10_26_000001_create_training_completions_table.php', '2026_10_26_000002_create_training_assignments_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->manager = $this->account('manager', 'Admin');
        $this->student = $this->account('student', 'Capacitacion');
        $this->actingAs($this->manager);
    }

    private function account(string $username, string $role): User
    {
        // Equal names must never cause assignment leakage between accounts.
        $user = User::create(['name' => 'Usuario', 'lastname' => 'Prueba', 'username' => $username, 'password' => bcrypt('test'), 'is_active' => true]);
        $user->assignRole(Role::findOrCreate($role));
        return $user;
    }

    private function assign(array $overrides = [])
    {
        return $this->postJson(route('admin.capacitaciones.alumnos.assign'), array_replace([
            'user_id' => $this->student->id, 'start_date' => '2026-10-26',
            'programs' => [['program_id' => 'induccion', 'module_ids' => ['module-2']],
                ['program_id' => 'proveedores', 'module_ids' => ['module-1', 'module-3']]],
        ], $overrides));
    }

    public function test_assignment_is_persisted_in_staff_progress_and_only_selected_user_can_start_it(): void
    {
        $this->assign()->assertOk();
        $this->assign()->assertOk();
        $this->assertDatabaseCount('training_assignments', 2);
        $record = TrainingAssignment::where('program_id', 'induccion')->sole();
        $this->assertSame($this->manager->id, $record->assigned_by);
        $this->assertSame(['module-2'], $record->module_ids);
        $rows = app(TrainingPersonnelController::class)->students()->getData()['studentPersonnel'];
        $person = collect($rows)->firstWhere('studentKey', 'user-'.$this->student->id);
        $this->assertCount(2, $person['currentPrograms']);
        $this->assertSame('Por iniciar', $person['currentPrograms'][0]['statusLabel']);
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonCount(0, 'assignments');
        $this->postJson(route('admin.capacitaciones.asignaciones.start', $record))->assertForbidden();

        $this->actingAs($this->student);
        $response = $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertOk()->assertJsonCount(2, 'assignments');
        $entry = collect($response->json('assignments'))->firstWhere('program_id', 'induccion');
        $this->assertSame(['module-2'], array_column($entry['modules'], 'id'));
        $this->assertNotEmpty($entry['modules'][0]['chapters']);
        $this->assertStringNotContainsString('video_path', $response->getContent());
        $this->assertStringNotContainsString('correctAnswer', $response->getContent());
        $this->postJson($entry['start_url'])->assertOk()->assertJsonPath('assignment.status', 'in_progress');
        $startedAt = $record->fresh()->started_at->toISOString();
        $this->travel(5)->minutes();
        $this->postJson($entry['start_url'])->assertOk();
        $this->assertSame($startedAt, $record->fresh()->started_at->toISOString());
        $this->assertDatabaseCount('training_program_access', 0);
        $this->actingAs($this->account('other', 'Capacitacion'));
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonCount(0, 'assignments');
        $this->postJson($entry['start_url'])->assertForbidden();
    }

    public function test_invalid_modules_roles_and_students_cannot_assign_and_batch_is_atomic(): void
    {
        $this->assign(['programs' => [['program_id' => 'induccion', 'module_ids' => ['module-1']],
            ['program_id' => 'proveedores', 'module_ids' => ['missing-module']]]])->assertUnprocessable();
        $this->assertDatabaseCount('training_assignments', 0);
        $this->assign(['user_id' => 99999])->assertUnprocessable();
        $client = $this->account('client', 'Cliente');
        $this->assign(['user_id' => $client->id])->assertNotFound();
        $this->actingAs($this->student);
        $this->assign()->assertForbidden();
        $this->assertDatabaseCount('training_assignments', 0);
        $this->manager->syncRoles([Role::findOrCreate('Super Admin')]);
        $this->actingAs($this->manager);
        $this->assign()->assertOk();
    }

    public function test_concluding_a_saved_assignment_moves_it_to_completed_and_prevents_restart(): void
    {
        $this->assign()->assertOk();
        $record = TrainingAssignment::where('program_id', 'induccion')->sole();
        $this->postJson(route('admin.capacitaciones.alumnos.conclude'), ['student_key' => 'user-'.$this->student->id,
            'program_id' => 'induccion', 'start_date' => '2026-10-26'])->assertOk();
        $this->assertSame('completed', $record->fresh()->status);
        $this->assertNotNull($record->fresh()->completed_at);
        $person = collect(app(TrainingPersonnelController::class)->students()->getData()['studentPersonnel'])->firstWhere('studentKey', 'user-'.$this->student->id);
        $this->assertCount(1, $person['currentPrograms']);
        $this->assertSame('proveedores', $person['currentPrograms'][0]['id']);
        $this->assertContains('Induccion y seguridad operativa', $person['completedPrograms']);
        $this->assign()->assertUnprocessable();
        $this->actingAs($this->student);
        $this->postJson(route('admin.capacitaciones.asignaciones.start', $record))->assertStatus(409);
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertJsonPath('assignments.1.status', 'completed');
    }

    public function test_content_tracks_catalog_changes_and_private_videos_require_own_selected_module(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('training/test.mp4', 'test-video');
        $program = TrainingProgram::findOrFail('induccion');
        $modules = $program->module_definitions;
        $modules[1]['chapter_definitions'][0]['video_path'] = 'training/test.mp4';
        $modules[1]['title'] = 'Módulo actualizado';
        $program->update(['module_definitions' => $modules]);
        $this->assign()->assertOk();
        $record = TrainingAssignment::where('program_id', 'induccion')->sole();
        $this->actingAs($this->student);
        $entry = collect($this->getJson(route('admin.capacitaciones.asignaciones.index'))->json('assignments'))->firstWhere('program_id', 'induccion');
        $this->assertSame('Módulo actualizado', $entry['modules'][0]['title']);
        $url = $entry['modules'][0]['chapters'][0]['file_url'];
        $this->get($url)->assertForbidden();
        $this->postJson($entry['start_url'])->assertOk();
        $this->get($url)->assertOk();
        $this->get(str_replace('/module-2/', '/module-1/', $url))->assertForbidden();
        $this->actingAs($this->manager);
        $this->get($url)->assertForbidden();
        $program->delete();
        $this->assertDatabaseMissing('training_assignments', ['id' => $record->id]);
    }

    public function test_assignment_allows_a_general_employee_to_use_own_training_without_administrative_access(): void
    {
        $this->student->syncRoles([Role::findOrCreate(\App\Support\AdminMenuAccess::GENERAL_ROLE)]);
        $this->assign()->assertOk();
        $this->actingAs($this->student);
        $this->getJson(route('admin.capacitaciones.asignaciones.index'))->assertOk()->assertJsonCount(2, 'assignments');
        $this->assertTrue(\App\Support\AdminMenuAccess::allows($this->student, 'menu.capacitaciones.programas'));
        $this->assertFalse(\App\Support\AdminMenuAccess::allows($this->student, 'menu.capacitaciones.alumnos'));
        $this->get(route('admin.capacitaciones.alumnos'))->assertForbidden();
        $this->assign()->assertForbidden();
    }
}
