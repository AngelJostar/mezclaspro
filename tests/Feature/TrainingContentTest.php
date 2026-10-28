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

    private function module(string $program = 'induccion', bool $withChapter = true): array
    {
        $module = $this->postJson($this->url($program.'/modulos'), ['stage' => 'Módulo nuevo', 'title' => 'Trazabilidad', 'description' => 'Práctica'])->assertCreated()->json();
        if ($withChapter) {
            $chapter = $this->postJson($this->url($program.'/modulos/'.$module['id'].'/capitulos'), ['title' => 'Técnica inicial', 'duration_seconds' => 60])->assertCreated()->json();
            $module['chapter_definitions'] = [$chapter]; $module['chapters'] = 1;
        }
        return $module;
    }

    private function exam(array $module): array
    {
        return ['id' => 'nuevo-examen', 'name' => 'Evaluación', 'moduleId' => $module['id'], 'chapterId' => $module['chapter_definitions'][0]['id'], 'module' => $module['title'],
            'minimum' => 80, 'duration' => 20, 'attempts' => 2, 'randomize' => true, 'status' => 'Publicado',
            'items' => [['text' => 'Elige la respuesta', 'type' => 'single', 'points' => 100, 'options' => ['Sí', 'No'], 'correct' => 0]]];
    }

    public function test_module_creation_and_rename_preserve_children_and_relations(): void
    {
        $module = $this->module();
        $this->assertSame(1, $module['chapters']);
        $this->postJson($this->url('induccion/tareas'), ['title' => 'Evidencia', 'instructions' => 'Adjunta el formato.', 'moduleId' => $module['id'], 'chapterId' => $module['chapter_definitions'][0]['id'], 'status' => 'Activa', 'requires_file' => true])->assertCreated()->assertJsonPath('due', 'Sin fecha');
        $this->putJson($this->url('induccion/examenes/nuevo-examen'), $this->exam($module))->assertOk();
        $this->patchJson($this->url('induccion/modulos/'.$module['id']), ['stage' => 'Módulo 5', 'title' => 'Nuevo título'])->assertOk()->assertJsonPath('id', $module['id']);
        $record = TrainingProgram::findOrFail('induccion');
        $this->assertCount(5, $record->module_definitions);
        $this->assertSame('Nuevo título', collect($record->exam_definitions)->firstWhere('id', 'nuevo-examen')['module']);
        $this->assertSame('Módulo 5', collect($record->task_definitions)->last()['stage']);
        $this->postJson($this->url('induccion/modulos'), ['stage' => 'Otra', 'title' => 'Nuevo título'])->assertUnprocessable();
        $this->patchJson($this->url('proveedores/modulos/'.$module['id']), ['stage' => 'Otra', 'title' => 'Ajeno'])->assertNotFound();
    }

    public function test_module_deletion_requires_confirmation_and_removes_only_its_content(): void
    {
        $module = $this->module();
        $chapter = $this->postJson($this->url('induccion/modulos/'.$module['id'].'/capitulos'), ['title' => 'Video', 'duration_seconds' => 60,
            'video_file' => UploadedFile::fake()->create('clase.mp4', 10, 'video/mp4')])->assertCreated()->json();
        $this->postJson($this->url('induccion/tareas'), ['title' => 'Evidencia', 'instructions' => 'Respuesta', 'moduleId' => $module['id'],
            'chapterId' => $module['chapter_definitions'][0]['id'], 'status' => 'Activa', 'requires_file' => false])->assertCreated();
        $this->putJson($this->url('induccion/examenes/nuevo-examen'), $this->exam($module))->assertOk();
        $before = TrainingProgram::findOrFail('induccion');
        $foreign = TrainingProgram::findOrFail('proveedores')->module_definitions;
        $url = $this->url('induccion/modulos/'.$module['id']);
        $this->deleteJson($url)->assertUnprocessable();
        $this->deleteJson($url, ['confirmed' => false])->assertUnprocessable();
        $this->deleteJson($this->url('proveedores/modulos/'.$module['id']), ['confirmed' => true])->assertNotFound();
        $this->assertSame($before->module_definitions, $before->fresh()->module_definitions);
        Storage::disk('local')->assertExists($chapter['video_path']);
        $this->deleteJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('deleted', $module['id']);
        $after = $before->fresh();
        $this->assertSame(collect($before->module_definitions)->reject(fn ($m) => $m['id'] === $module['id'])->values()->all(), $after->module_definitions);
        foreach (['task_definitions', 'exam_definitions'] as $field) {
            $this->assertSame(collect($before->$field)->reject(fn ($entry) => $entry['moduleId'] === $module['id'])->values()->all(), $after->$field);
        }
        $this->assertSame($foreign, TrainingProgram::find('proveedores')->module_definitions);
        Storage::disk('local')->assertMissing($chapter['video_path']);
        $this->deleteJson($url, ['confirmed' => true])->assertNotFound();
        $catalog = collect(TrainingAccess::catalog(auth()->user()))->firstWhere('id', 'induccion');
        $this->assertNotContains($module['id'], array_column($catalog['module_definitions'], 'id'));
    }

    public function test_module_deletion_is_restricted_to_administrators_and_supports_an_empty_program(): void
    {
        $admin = auth()->user();
        $this->postJson($this->url(''), ['title' => 'Programa vacío', 'owner' => 'Calidad', 'status' => 'Activo'])->assertCreated();
        $program = TrainingProgram::where('title', 'Programa vacío')->firstOrFail();
        $module = $this->module($program->id, false);
        $url = $this->url($program->id.'/modulos/'.$module['id']);
        $student = User::create(['name' => 'Alumno', 'lastname' => 'Prueba', 'username' => 'alumno', 'password' => bcrypt('test'), 'is_active' => true]);
        $this->actingAs($student)->deleteJson($url, ['confirmed' => true])->assertForbidden();
        $this->assertCount(1, $program->fresh()->module_definitions);
        $admin->syncRoles([Role::findOrCreate('Super Admin')]);
        $this->actingAs($admin)->deleteJson($url, ['confirmed' => true])->assertOk();
        $this->assertSame([], $program->fresh()->module_definitions);
        $this->assertSame(0, $program->fresh()->catalogEntry()['modules']);
    }

    public function test_chapters_persist_per_module_and_private_video_is_replaced_and_deleted(): void
    {
        $module = $this->module('induccion', false);
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
        $data = ['title' => 'Inspección', 'instructions' => 'Entregar checklist', 'moduleId' => $module['id'], 'chapterId' => $module['chapter_definitions'][0]['id'], 'due_date' => '2026-11-01', 'status' => 'Borrador', 'requires_file' => true];
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

    public function test_chapter_activities_reject_missing_or_foreign_chapters_and_delete_only_their_own_children(): void
    {
        $module = $this->module();
        $first = $module['chapter_definitions'][0]['id'];
        $second = $this->postJson($this->url('induccion/modulos/'.$module['id'].'/capitulos'), ['title' => 'Segundo', 'duration_seconds' => 60])->assertCreated()->json('id');
        $task = ['title' => 'Evidencia', 'instructions' => 'Respuesta', 'moduleId' => $module['id'], 'status' => 'Activa', 'requires_file' => false];
        $this->postJson($this->url('induccion/tareas'), $task)->assertUnprocessable();
        $this->postJson($this->url('induccion/tareas'), $task + ['chapterId' => 'c1'])->assertUnprocessable();
        $a = $this->postJson($this->url('induccion/tareas'), $task + ['chapterId' => $first])->assertCreated()->json('id');
        $b = $this->postJson($this->url('induccion/tareas'), $task + ['chapterId' => $second])->assertCreated()->json('id');
        $exam = $this->exam($module);
        $this->putJson($this->url('induccion/examenes/e1'), array_replace($exam, ['chapterId' => null]))->assertUnprocessable();
        $this->putJson($this->url('induccion/examenes/e1'), array_replace($exam, ['chapterId' => 'foreign']))->assertUnprocessable();
        $this->putJson($this->url('induccion/examenes/e1'), $exam)->assertOk()->assertJsonPath('chapterId', $first);
        $this->putJson($this->url('induccion/examenes/e2'), array_replace($exam, ['chapterId' => $second]))->assertOk();
        $this->deleteJson($this->url('induccion/modulos/'.$module['id'].'/capitulos/'.$first), ['confirmed' => true])->assertOk();
        $record = TrainingProgram::find('induccion');
        $this->assertNotContains($a, array_column($record->task_definitions, 'id'));
        $this->assertContains($b, array_column($record->task_definitions, 'id'));
        $this->assertNotContains('e1', array_column($record->exam_definitions, 'id'));
        $this->assertContains('e2', array_column($record->exam_definitions, 'id'));
        $this->assertNotEmpty(collect($record->exam_definitions)->filter(fn ($e) => empty($e['chapterId'])));
    }

    public function test_chapter_order_persists_without_changing_content_or_relations_and_rejects_stale_lists(): void
    {
        $module = $this->module();
        $first = $module['chapter_definitions'][0]['id'];
        $base = 'induccion/modulos/'.$module['id'].'/capitulos';
        $second = $this->postJson($this->url($base), ['title' => 'Segundo', 'duration_seconds' => 120])->assertCreated()->json('id');
        $third = $this->postJson($this->url($base), ['title' => 'Tercero', 'duration_seconds' => 180])->assertCreated()->json('id');
        $this->postJson($this->url('induccion/tareas'), ['title' => 'Evidencia', 'instructions' => 'Respuesta', 'moduleId' => $module['id'], 'chapterId' => $first, 'status' => 'Activa', 'requires_file' => false])->assertCreated();
        $this->putJson($this->url('induccion/examenes/e1'), $this->exam($module))->assertOk();
        $before = TrainingProgram::findOrFail('induccion');
        $oldOrder = [$first, $second, $third]; $newOrder = [$third, $first, $second];
        $url = $this->url($base.'/ordenar');
        foreach ([[$first, $first, $third], [$first], [$first, $second, 'foreign']] as $badOrder) {
            $this->patchJson($url, ['chapter_ids' => $badOrder, 'expected_order' => $oldOrder])->assertUnprocessable();
        }
        $this->patchJson($this->url('proveedores/modulos/'.$module['id'].'/capitulos/ordenar'), ['chapter_ids' => $newOrder, 'expected_order' => $oldOrder])->assertNotFound();
        $this->patchJson($url, ['chapter_ids' => $newOrder, 'expected_order' => $oldOrder])->assertOk()->assertJsonPath('chapter_ids', $newOrder);
        // A retry after a lost response is safe, but another stale change must not overwrite it.
        $this->patchJson($url, ['chapter_ids' => $newOrder, 'expected_order' => $oldOrder])->assertOk();
        $this->patchJson($url, ['chapter_ids' => [$second, $first, $third], 'expected_order' => $oldOrder])->assertStatus(409);
        $after = $before->fresh();
        $saved = collect($after->module_definitions)->firstWhere('id', $module['id']);
        $previous = collect($before->module_definitions)->firstWhere('id', $module['id']);
        $this->assertSame($newOrder, array_column($saved['chapter_definitions'], 'id'));
        $this->assertEquals(collect($previous['chapter_definitions'])->keyBy('id')->all(), collect($saved['chapter_definitions'])->keyBy('id')->all());
        $this->assertSame($previous['duration'], $saved['duration']);
        $this->assertSame($previous['chapters'], $saved['chapters']);
        $this->assertSame($before->task_definitions, $after->task_definitions);
        $this->assertSame($before->exam_definitions, $after->exam_definitions);
        $this->assertSame($before->module_definitions[0], $after->module_definitions[0]);
        $catalog = collect(TrainingAccess::catalog(auth()->user()))->firstWhere('id', 'induccion');
        $this->assertSame($newOrder, array_column(collect($catalog['module_definitions'])->firstWhere('id', $module['id'])['chapter_definitions'], 'id'));
    }

    public function test_only_the_administrative_creator_can_reorder_new_modules_and_legacy_modules_remain_manageable(): void
    {
        $creator = auth()->user();
        $module = $this->module(); $ids = array_column($module['chapter_definitions'], 'id');
        $this->assertSame($creator->id, $module['created_by']);
        $url = $this->url('induccion/modulos/'.$module['id'].'/capitulos/ordenar');
        foreach (['Admin', 'Super Admin', 'Capacitacion'] as $role) {
            $other = User::create(['name' => $role, 'lastname' => 'Prueba', 'username' => str_replace(' ', '', $role), 'password' => bcrypt('test'), 'is_active' => true]);
            $other->assignRole(Role::findOrCreate($role));
            $this->actingAs($other);
            $this->patchJson($url, ['chapter_ids' => $ids, 'expected_order' => $ids])->assertForbidden();
            $catalog = collect(TrainingAccess::catalog($other))->firstWhere('id', 'induccion');
            $this->assertFalse(collect($catalog['module_definitions'])->firstWhere('id', $module['id'])['can_reorder_chapters']);
        }
        $this->actingAs($creator);
        $this->patchJson($this->url('induccion/modulos/'.$module['id']), ['stage' => 'Editado', 'title' => 'Editado', 'created_by' => 999])->assertOk()->assertJsonPath('created_by', $creator->id);
        $this->patchJson($url, ['chapter_ids' => $ids, 'expected_order' => $ids])->assertOk();
        $creator->syncRoles([Role::findOrCreate('Super Admin')]);
        $superModule = $this->module('proveedores');
        $this->assertSame($creator->id, $superModule['created_by']);
        $superIds = array_column($superModule['chapter_definitions'], 'id');
        $this->patchJson($this->url('proveedores/modulos/'.$superModule['id'].'/capitulos/ordenar'), ['chapter_ids' => $superIds, 'expected_order' => $superIds])->assertOk();
        $legacy = TrainingProgram::find('induccion')->module_definitions[0];
        $legacyIds = array_column($legacy['chapter_definitions'], 'id');
        $this->patchJson($this->url('induccion/modulos/'.$legacy['id'].'/capitulos/ordenar'), ['chapter_ids' => array_reverse($legacyIds), 'expected_order' => $legacyIds])->assertOk();
    }
}
