<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\TrainingAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingContentTest extends TestCase
{
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
        $admin = User::create(['name' => 'Admin', 'lastname' => 'Prueba', 'username' => 'admin', 'password' => bcrypt('test'), 'is_active' => true]);
        $admin->assignRole(Role::findOrCreate('Admin'));
        $this->actingAs($admin);
        Storage::fake('local');
    }

    private function url(string $path): string { return '/admin/capacitaciones/programas/catalogo/'.$path; }

    private function module(string $program = 'induccion'): array
    {
        return $this->postJson($this->url($program.'/modulos'), ['stage' => 'Módulo nuevo', 'title' => 'Trazabilidad', 'description' => 'Práctica'])->assertCreated()->json();
    }

    private function exam(array $module): array
    {
        return ['id' => 'nuevo-examen', 'name' => 'Evaluación', 'moduleId' => $module['id'], 'module' => $module['title'],
            'minimum' => 80, 'duration' => 20, 'attempts' => 2, 'randomize' => true, 'status' => 'Publicado',
            'items' => [['text' => 'Elige la respuesta', 'type' => 'single', 'points' => 100, 'options' => ['Sí', 'No'], 'correct' => 0]]];
    }

    public function test_module_creation_and_rename_preserve_children_and_relations(): void
    {
        $module = $this->module();
        $this->assertSame(0, $module['chapters']);
        $this->postJson($this->url('induccion/tareas'), ['title' => 'Evidencia', 'instructions' => 'Adjunta el formato.', 'moduleId' => $module['id'], 'status' => 'Activa', 'requires_file' => true])->assertCreated()->assertJsonPath('due', 'Sin fecha');
        $this->putJson($this->url('induccion/examenes/nuevo-examen'), $this->exam($module))->assertOk();
        $this->patchJson($this->url('induccion/modulos/'.$module['id']), ['stage' => 'Módulo 5', 'title' => 'Nuevo título'])->assertOk()->assertJsonPath('id', $module['id']);
        $record = TrainingProgram::findOrFail('induccion');
        $this->assertCount(5, $record->module_definitions);
        $this->assertSame('Nuevo título', collect($record->exam_definitions)->firstWhere('id', 'nuevo-examen')['module']);
        $this->assertSame('Módulo 5', collect($record->task_definitions)->last()['stage']);
        $this->postJson($this->url('induccion/modulos'), ['stage' => 'Otra', 'title' => 'Nuevo título'])->assertUnprocessable();
        $this->patchJson($this->url('proveedores/modulos/'.$module['id']), ['stage' => 'Otra', 'title' => 'Ajeno'])->assertNotFound();
    }

    public function test_chapters_persist_per_module_and_private_video_is_replaced_and_deleted(): void
    {
        $module = $this->module();
        $base = 'induccion/modulos/'.$module['id'].'/capitulos';
        $data = ['title' => 'Técnica', 'objective' => 'Identificar', 'duration_seconds' => 125, 'video' => 'https://example.test/video'];
        $chapter = $this->postJson($this->url($base), $data + ['video_file' => UploadedFile::fake()->create('clase.mp4', 10, 'video/mp4')])->assertCreated()->json();
        Storage::disk('local')->assertExists($chapter['video_path']);
        $this->get(route('admin.capacitaciones.programas.video', ['program' => 'induccion', 'module' => $module['id'], 'chapter' => $chapter['id']]))->assertOk();
        $record = TrainingProgram::findOrFail('induccion');
        $saved = collect($record->module_definitions)->firstWhere('id', $module['id']);
        $this->assertSame(1, $saved['chapters']);
        $this->assertSame('3 min', $saved['duration']);
        $this->assertCount(2, $record->module_definitions[0]['chapter_definitions']);
        $updated = $this->postJson($this->url($base.'/'.$chapter['id']), array_replace($data, ['title' => 'Actualizada', 'duration_seconds' => 600, 'video_file' => UploadedFile::fake()->create('nuevo.webm', 10, 'video/webm')]))->assertOk()->json();
        Storage::disk('local')->assertMissing($chapter['video_path']);
        Storage::disk('local')->assertExists($updated['video_path']);
        $this->deleteJson($this->url($base.'/'.$chapter['id']))->assertUnprocessable();
        $this->deleteJson($this->url($base.'/'.$chapter['id']), ['confirmed' => true])->assertOk();
        Storage::disk('local')->assertMissing($updated['video_path']);
        $this->assertSame(0, collect(TrainingProgram::find('induccion')->module_definitions)->firstWhere('id', $module['id'])['chapters']);
        $this->postJson($this->url($base), array_replace($data, ['video' => 'javascript:alert(1)']))->assertUnprocessable();
        $this->postJson($this->url($base), $data + ['video_file' => UploadedFile::fake()->create('script.php', 1, 'text/plain')])->assertUnprocessable();
    }

    public function test_task_editing_validates_required_fields_and_program_module(): void
    {
        $module = $this->module();
        $data = ['title' => 'Inspección', 'instructions' => 'Entregar checklist', 'moduleId' => $module['id'], 'due_date' => '2026-11-01', 'status' => 'Borrador', 'requires_file' => true];
        $task = $this->postJson($this->url('induccion/tareas'), $data)->assertCreated()->json();
        $this->patchJson($this->url('induccion/tareas/'.$task['id']), array_replace($data, ['status' => 'Activa', 'requires_file' => '0']))->assertOk()->assertJsonPath('status', 'Activa')->assertJsonPath('requires_file', false);
        $this->postJson($this->url('proveedores/tareas'), $data)->assertNotFound();
        $this->postJson($this->url('induccion/tareas'), array_replace($data, ['instructions' => '']))->assertUnprocessable();
        $this->assertSame('01/11/2026', collect(TrainingProgram::find('induccion')->task_definitions)->last()['due']);
    }

    public function test_exams_save_drafts_and_publish_valid_questions_without_accepting_stale_imports(): void
    {
        $module = $this->module();
        $exam = $this->exam($module);
        $url = $this->url('induccion/examenes/nuevo-examen');
        $this->putJson($url, array_replace($exam, ['items' => []]))->assertUnprocessable();
        $this->putJson($url, array_replace($exam, ['items' => [], 'status' => 'Borrador']))->assertOk();
        $invalid = $exam; $invalid['items'][0]['correct'] = 7;
        $this->putJson($url, $invalid)->assertUnprocessable();
        $invalid = $exam; $invalid['items'][0]['options'][0] = '';
        $this->putJson($url, $invalid)->assertUnprocessable();
        $this->putJson($this->url('proveedores/examenes/nuevo-examen'), $exam)->assertUnprocessable();
        $this->putJson($url, $exam)->assertOk()->assertJsonPath('questions', 1);
        $this->postJson($this->url('induccion/examenes/importar'), ['exams' => [array_replace($exam, ['name' => 'Navegador antiguo'])]])->assertOk();
        $this->assertSame('Evaluación', collect(TrainingProgram::find('induccion')->exam_definitions)->firstWhere('id', 'nuevo-examen')['name']);
    }

    public function test_restricted_personnel_cannot_manage_content_or_access_unassigned_modules(): void
    {
        $module = $this->module();
        $exam = $this->exam($module);
        $this->putJson($this->url('induccion/examenes/nuevo-examen'), $exam)->assertOk();
        $person = User::create(['name' => 'Persona', 'lastname' => 'Prueba', 'username' => 'persona', 'password' => bcrypt('test'), 'is_active' => true]);
        $person->personnelProfile()->create(['paternal_surname' => 'Prueba', 'department' => 'Calidad', 'positions' => [], 'hire_date' => '2026-01-01', 'training_access_configured' => true]);
        DB::table('training_program_access')->insert(['user_id' => $person->id, 'training_program_id' => 'induccion', 'module_ids' => '["module-1"]', 'all_modules' => false]);
        $catalog = TrainingAccess::catalog($person);
        $this->assertCount(1, $catalog);
        $this->assertSame(['module-1'], array_column($catalog[0]['module_definitions'], 'id'));
        $this->assertNotContains('nuevo-examen', array_column($catalog[0]['exam_definitions'], 'id'));
        $this->actingAs($person);
        $this->postJson($this->url('induccion/modulos'), ['stage' => 'Módulo 9', 'title' => 'No autorizado'])->assertForbidden();
        $this->get(route('admin.capacitaciones.programas.video', ['program' => 'induccion', 'module' => $module['id'], 'chapter' => 'chapter-1']))->assertForbidden();
    }
}
