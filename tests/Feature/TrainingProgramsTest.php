<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\Oncologicos\Laboratory;
use App\Support\TrainingAccess;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingProgramsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            '2014_10_12_000000_create_users_table.php', '2024_01_10_035338_create_hospitals_table.php',
            '2024_02_05_235617_add_hospital_to_users_table.php', '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php', '2026_03_03_004410_add_laboratory_id_to_hospitals_table.php',
            '2026_08_19_000007_add_credential_password_to_users_table.php', '2026_08_19_000008_add_training_credentials_to_users_table.php',
            '2026_08_20_000006_create_personnel_profiles_table.php', '2026_10_02_000001_add_institutional_email_to_personnel_profiles.php',
            '2026_10_23_000001_create_training_program_catalog.php',
            '2026_10_23_000002_add_training_content.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();
        $manager = User::create(['name' => 'Admin', 'lastname' => 'Test', 'username' => 'admin-test', 'password' => bcrypt('test-password'), 'is_active' => true]);
        $manager->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($manager);
    }

    private function payload(): array
    {
        $lab = Laboratory::create(['nombre' => 'Central prueba', 'activo' => true]);
        return ['first_name' => 'Nueva', 'paternal_surname' => 'Persona', 'personal_email' => 'nueva@example.test',
            'laboratory_id' => $lab->id, 'department' => 'Calidad', 'hire_date' => '2025-01-10', 'positions' => ['Verificador'],
            'username' => 'nueva.persona', 'password' => 'test-password', 'employment_status' => 'hired'];
    }

    public function test_deletion_requires_confirmation_and_matching_name_and_cannot_be_reimported(): void
    {
        $program = TrainingProgram::findOrFail('induccion');
        $url = route('admin.capacitaciones.programas.catalogo.destroy', $program->id);
        $this->deleteJson($url, [])->assertUnprocessable();
        $this->deleteJson($url, ['confirmed' => true, 'confirmation_name' => 'Otro'])->assertConflict();
        $this->assertDatabaseHas('training_programs', ['id' => $program->id]);
        DB::table('training_program_access')->insert(['user_id' => auth()->id(), 'training_program_id' => $program->id, 'module_ids' => '["module-1"]']);
        $this->deleteJson($url, ['confirmed' => true, 'confirmation_name' => $program->title])->assertOk();
        $this->assertDatabaseMissing('training_programs', ['id' => $program->id]);
        $this->assertDatabaseCount('training_program_access', 0);
        $this->postJson(route('admin.capacitaciones.programas.catalogo.import'), ['programs' => [$program->catalogEntry()]])->assertOk();
        $this->assertDatabaseMissing('training_programs', ['id' => $program->id]);
    }

    public function test_metadata_persists_and_stale_browser_cannot_overwrite_it(): void
    {
        $old = TrainingProgram::findOrFail('induccion')->catalogEntry();
        $this->patchJson(route('admin.capacitaciones.programas.catalogo.update', 'induccion'), array_replace($old, ['title' => 'Nuevo nombre']))->assertOk();
        $this->postJson(route('admin.capacitaciones.programas.catalogo.import'), ['programs' => [$old]])->assertOk();
        $this->assertDatabaseHas('training_programs', ['id' => 'induccion', 'title' => 'Nuevo nombre']);
        $created = $this->postJson(route('admin.capacitaciones.programas.catalogo.store'), ['title' => 'Programa nuevo', 'owner' => 'Calidad', 'status' => 'Borrador'])->assertCreated();
        $this->assertDatabaseHas('training_programs', ['id' => $created->json('id')]);
    }

    public function test_personnel_creation_saves_partial_and_whole_program_access(): void
    {
        $this->post(route('admin.capacitaciones.personal.store'), $this->payload() + [
            'training_all' => ['proveedores'], 'training_modules' => ['induccion' => ['module-2']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $person = User::where('username', 'nueva.persona')->firstOrFail();
        $this->assertTrue($person->personnelProfile->training_access_configured);
        $this->assertSame(2, $person->trainingPrograms()->count());
        $catalog = collect(TrainingAccess::catalog($person))->keyBy('id');
        $this->assertSame(['module-2'], array_column($catalog['induccion']['module_definitions'], 'id'));
        $this->assertCount(3, $catalog['proveedores']['module_definitions']);
        $this->assertFalse($catalog->has('almacenamiento'));
        $this->actingAs($person)->deleteJson(route('admin.capacitaciones.programas.catalogo.destroy', 'induccion'), [
            'confirmed' => true, 'confirmation_name' => 'Induccion y seguridad operativa',
        ])->assertForbidden();
    }

    public function test_stale_program_or_wrong_module_rejects_entire_personnel_creation(): void
    {
        $payload = $this->payload();
        foreach ([['training_all' => ['deleted-program']], ['training_modules' => ['induccion' => ['module-999']]]] as $selection) {
            $this->postJson(route('admin.capacitaciones.personal.store'), $payload + $selection)->assertUnprocessable()->assertJsonValidationErrors('training_modules');
            $this->assertDatabaseMissing('users', ['username' => 'nueva.persona']);
        }
    }

    public function test_no_selection_grants_no_programs(): void
    {
        $this->post(route('admin.capacitaciones.personal.store'), $this->payload())->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], TrainingAccess::catalog(User::where('username', 'nueva.persona')->firstOrFail()));
    }

    public function test_personnel_catalog_reflects_program_deletions_and_module_changes_without_exposing_content(): void
    {
        $url = route('admin.capacitaciones.personal.programs');
        $before = $this->getJson($url)->assertOk()->assertJsonCount(3, 'programs');
        $this->assertStringContainsString('no-store', $before->headers->get('Cache-Control'));
        $this->patchJson(route('admin.capacitaciones.programas.catalogo.modules.update', ['program' => 'induccion', 'module' => 'module-1']),
            ['stage' => 'Módulo 1 actualizado', 'title' => 'Bienvenida revisada'])->assertOk();
        $program = TrainingProgram::findOrFail('proveedores');
        $this->deleteJson(route('admin.capacitaciones.programas.catalogo.destroy', $program->id), ['confirmed' => true, 'confirmation_name' => $program->title])->assertOk();
        $catalog = $this->getJson($url)->assertOk()->assertJsonCount(2, 'programs')->json('programs');
        $this->assertNotContains('proveedores', array_column($catalog, 'id'));
        $module = collect($catalog)->firstWhere('id', 'induccion')['modules'][0];
        $this->assertEquals(['id' => 'module-1', 'stage' => 'Módulo 1 actualizado', 'title' => 'Bienvenida revisada'], $module);
        $this->assertArrayNotHasKey('chapter_definitions', $module);
        $person = User::create(['name' => 'Consulta', 'lastname' => 'Personal', 'username' => 'catalog-reader', 'password' => bcrypt('test'), 'is_active' => true]);
        $this->actingAs($person)->getJson($url)->assertForbidden();
        $person->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('menu.capacitaciones.personal'));
        $this->actingAs($person)->getJson($url)->assertOk();
    }
}
