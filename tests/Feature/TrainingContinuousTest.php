<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingContinuousTest extends TestCase
{
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2024_01_10_035338_create_hospitals_table.php',
            '2024_02_05_235617_add_hospital_to_users_table.php', '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
            '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->employee = User::create(['name' => 'Persona', 'lastname' => 'Prueba', 'username' => 'persona', 'password' => bcrypt('test'), 'is_active' => true]);
        $this->employee->assignRole(Role::findOrCreate('Capacitacion'));
        $this->actingAs($this->employee);
        Storage::fake('local');
    }

    private function grant(string $program, array $modules = [], bool $all = false): void
    {
        DB::table('training_program_access')->insert(['user_id' => $this->employee->id, 'training_program_id' => $program,
            'module_ids' => json_encode($modules), 'all_modules' => $all]);
    }

    private function catalog()
    {
        return $this->getJson(route('admin.capacitaciones.continua.index'));
    }

    public function test_only_enabled_modules_are_exposed_without_tasks_exams_or_private_paths(): void
    {
        $this->grant('induccion', ['module-4']);
        $this->grant('almacenamiento', [], true); // Drafts are not published to employees.
        $program = TrainingProgram::findOrFail('induccion');
        $modules = $program->module_definitions;
        $modules[3]['chapter_definitions'][0]['video_path'] = 'training/private.mp4';
        $modules[3]['chapter_definitions'][0]['video'] = 'javascript:alert(1)';
        $program->update(['module_definitions' => $modules]);
        $before = $program->fresh()->getAttributes();
        $response = $this->catalog()->assertOk()->assertJsonCount(1, 'programs')
            ->assertJsonCount(1, 'programs.0.modules')->assertJsonPath('programs.0.modules.0.id', 'module-4')
            ->assertJsonPath('programs.0.modules.0.chapters.0.video_url', null);
        $this->assertStringContainsString('/continua/induccion/modulos/module-4/', $response->json('programs.0.modules.0.chapters.0.file_url'));
        foreach (['exam_definitions', 'task_definitions', 'video_path', 'training/private.mp4'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($before, $program->fresh()->getAttributes());
    }

    public function test_full_program_access_tracks_edits_new_modules_deletions_and_revocation(): void
    {
        $this->grant('proveedores', [], true);
        $program = TrainingProgram::findOrFail('proveedores');
        $this->catalog()->assertOk()->assertJsonCount(3, 'programs.0.modules');
        $modules = $program->module_definitions;
        $modules[0]['title'] = 'Documentación actualizada';
        $modules[0]['chapter_definitions'][0]['title'] = 'Revisión actualizada';
        $modules[] = ['id' => 'new-module', 'stage' => 'Módulo 4', 'title' => 'Nuevo', 'chapter_definitions' => []];
        $program->update(['title' => 'Proveedores actualizados', 'module_definitions' => $modules]);
        $this->catalog()->assertOk()->assertJsonPath('programs.0.title', 'Proveedores actualizados')
            ->assertJsonCount(4, 'programs.0.modules')->assertJsonPath('programs.0.modules.0.title', 'Documentación actualizada')
            ->assertJsonPath('programs.0.modules.0.chapters.0.title', 'Revisión actualizada');
        DB::table('training_program_access')->update(['all_modules' => false, 'module_ids' => '["module-1"]']);
        $program->update(['module_definitions' => array_values(array_filter($modules, fn ($m) => $m['id'] !== 'module-1'))]);
        $this->catalog()->assertOk()->assertJsonCount(0, 'programs.0.modules');
        $program->update(['status' => 'Inactivo']);
        $this->catalog()->assertOk()->assertExactJson(['programs' => []]);
        $program->update(['status' => 'Activo']);
        $program->delete();
        $this->catalog()->assertOk()->assertExactJson(['programs' => []]);
    }

    public function test_unconfigured_and_personnel_managers_still_need_explicit_grants_but_admin_can_preview(): void
    {
        $this->catalog()->assertOk()->assertExactJson(['programs' => []]);
        $this->employee->givePermissionTo(Permission::findOrCreate('menu.capacitaciones.personal'));
        $this->catalog()->assertOk()->assertExactJson(['programs' => []]);
        $this->employee->assignRole(Role::findOrCreate('Admin'));
        $this->catalog()->assertOk()->assertJsonCount(2, 'programs');
        $this->employee->syncRoles([Role::findOrCreate('Cliente')]);
        $this->catalog()->assertForbidden();
    }

    public function test_private_videos_recheck_access_for_sales_employees_and_never_unlock_other_modules(): void
    {
        $this->employee->assignRole(Role::findOrCreate('Vendedor'));
        $this->grant('induccion', ['module-4']);
        $program = TrainingProgram::findOrFail('induccion');
        $modules = $program->module_definitions;
        $modules[3]['chapter_definitions'][0]['video_path'] = 'training/test.mp4';
        Storage::disk('local')->put('training/test.mp4', 'private-video-test');
        $program->update(['module_definitions' => $modules]);
        $response = $this->catalog()->assertOk();
        $url = $response->json('programs.0.modules.0.chapters.0.file_url');
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('admin.capacitaciones.continua.video', ['program' => 'induccion', 'module' => 'module-1', 'chapter' => 'chapter-1']))->assertForbidden();
        $this->get(str_replace('chapter-1', 'missing-chapter', $url))->assertNotFound();
        DB::table('training_program_access')->delete();
        $this->get($url)->assertForbidden();
        $this->catalog()->assertOk()->assertExactJson(['programs' => []]);
    }
}
