<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TrainingPersonnelController;
use App\Models\TrainingCompletion;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\TrainingStudentCompletions;
use App\Support\TrainingStudentDefaults;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingCompletionTest extends TestCase
{
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2024_01_10_035338_create_hospitals_table.php',
            '2024_02_05_235617_add_hospital_to_users_table.php', '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
            '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php',
            '2026_10_26_000001_create_training_completions_table.php', '2026_10_26_000002_create_training_assignments_table.php',
            '2026_10_26_000003_add_training_learning_progress.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->manager = User::create(['name' => 'Admin', 'lastname' => 'Prueba', 'username' => 'admin', 'password' => bcrypt('test'), 'is_active' => true]);
        $this->manager->assignRole(Role::findOrCreate('Admin'));
        $this->actingAs($this->manager);
    }

    private function finish(array $changes = [])
    {
        return $this->postJson(route('admin.capacitaciones.alumnos.conclude'), array_replace([
            'student_key' => 'preview-ana-torres', 'program_id' => 'induccion', 'start_date' => '2026-08-01',
        ], $changes));
    }

    public function test_early_completion_is_persistent_audited_idempotent_and_preserves_exams_and_other_students(): void
    {
        $before = TrainingProgram::findOrFail('induccion')->getAttributes();
        $this->finish()->assertOk()->assertJsonPath('redirect', route('admin.capacitaciones.alumnos', ['training_view' => 'completed']));
        $completion = TrainingCompletion::sole();
        $this->assertSame($this->manager->id, $completion->completed_by);
        $this->assertSame('Admin Prueba', $completion->completed_by_name);
        $this->assertNotNull($completion->completed_at);
        $originalTime = $completion->completed_at->toISOString();
        $this->travel(10)->minutes();
        $this->finish()->assertOk();
        $this->assertDatabaseCount('training_completions', 1);
        $this->assertSame($originalTime, $completion->fresh()->completed_at->toISOString());
        $data = app(TrainingPersonnelController::class)->students()->getData();
        $ana = collect($data['studentPersonnel'])->firstWhere('studentKey', 'preview-ana-torres');
        $carlos = collect($data['studentPersonnel'])->firstWhere('studentKey', 'preview-carlos-hernandez');
        $this->assertEmpty($ana['currentPrograms']);
        $this->assertContains('Induccion y seguridad operativa', $ana['completedPrograms']);
        $this->assertCount(1, $carlos['currentPrograms']);
        $this->assertSame($before, TrainingProgram::findOrFail('induccion')->getAttributes());
        $this->assertSame('2026-08-01', $data['trainingCompletions'][0]['startDate']);
    }

    public function test_only_the_selected_course_and_start_date_are_concluded(): void
    {
        $this->finish()->assertOk();
        $students = TrainingStudentDefaults::records();
        $students[0]['currentPrograms'][] = ['id' => 'proveedores', 'name' => 'Proveedores', 'progress' => 20, 'startDate' => '15/08/2026'];
        $students[0]['currentPrograms'][] = ['id' => 'induccion', 'name' => 'Otra edición', 'progress' => 0, 'startDate' => '01/09/2026'];
        $students = TrainingStudentCompletions::apply($students, TrainingCompletion::all());
        $this->assertCount(2, $students[0]['currentPrograms']);
        $this->assertSame(['proveedores', 'induccion'], array_column($students[0]['currentPrograms'], 'id'));
        $this->finish(['student_key' => 'preview-fernanda-ortiz', 'program_id' => 'citotoxicos', 'start_date' => '2026-08-14'])->assertOk();
        $this->assertDatabaseHas('training_completions', ['program_id' => 'citotoxicos', 'program_name' => 'Manejo seguro de citotoxicos']);
    }

    public function test_admin_and_superadmin_can_conclude_but_other_roles_cannot_forge_requests(): void
    {
        foreach (['Capacitacion', 'Usuario general', 'Cliente'] as $role) {
            $this->manager->syncRoles([Role::findOrCreate($role)]);
            $this->finish()->assertForbidden();
        }
        $this->assertDatabaseCount('training_completions', 0);
        $this->manager->syncRoles([Role::findOrCreate('Super Admin')]);
        $this->finish()->assertOk();
        $this->assertDatabaseCount('training_completions', 1);
    }

    public function test_invalid_students_courses_and_dates_cannot_create_completion_records(): void
    {
        $this->finish(['student_key' => 'missing'])->assertNotFound();
        $this->finish(['student_key' => 'user-99999'])->assertNotFound();
        $this->finish(['program_id' => 'not-registered'])->assertUnprocessable();
        $this->finish(['program_id' => 'proveedores'])->assertUnprocessable();
        $this->finish(['start_date' => ''])->assertUnprocessable();
        $this->finish(['start_date' => '31/02/2026'])->assertUnprocessable();
        $this->assertDatabaseCount('training_completions', 0);
    }

    public function test_closing_a_base_course_preserves_other_browser_assignments_and_they_can_be_closed_later(): void
    {
        $this->finish(['browser_assignments' => [['programId' => 'proveedores', 'startDate' => '2026-10-01', 'progress' => 10]]])->assertOk();
        $ana = collect(app(TrainingPersonnelController::class)->students()->getData()['studentPersonnel'])->firstWhere('studentKey', 'preview-ana-torres');
        $this->assertSame(['proveedores'], array_column($ana['currentPrograms'], 'id'));
        $this->assertSame(10, $ana['currentPrograms'][0]['progress']);
        $this->finish(['program_id' => 'proveedores', 'start_date' => '2026-10-01'])->assertOk();
        $ana = collect(app(TrainingPersonnelController::class)->students()->getData()['studentPersonnel'])->firstWhere('studentKey', 'preview-ana-torres');
        $this->assertEmpty($ana['currentPrograms']);
        $this->assertCount(2, $ana['completedPrograms']);
    }
}
