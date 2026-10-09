<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClinicalManualLibrary;
use App\Models\AiAgent;
use App\Models\ClinicalSource;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Fixtures\AgentCenter;
use Tests\TestCase;

class ClinicalManualLibraryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
        }
        AgentCenter::seed();
        (require database_path('migrations/2026_09_29_000001_create_clinical_support.php'))->up();
        (require database_path('migrations/2026_09_30_000007_version_clinical_manual_sources.php'))->up();
        (require database_path('migrations/2026_10_05_000001_add_clinical_manual_library.php'))->up();
        Storage::fake('local');
        Http::preventStrayRequests();
    }

    private function saveManual(string $type, string $version)
    {
        return Livewire::test(ClinicalManualLibrary::class)->set('type', $type)->set('version', $version)
            ->set('reference', 'Referencia sintética de pruebas')
            ->set('content', str_repeat('1. Criterio de prueba documental. ', 8))->call('save')->assertHasNoErrors();
    }

    public function test_versions_are_separate_by_population_and_previous_versions_are_preserved(): void
    {
        $this->saveManual('npt_adulto', '1');
        $this->saveManual('npt_pediatrico', '1');
        $this->saveManual('npt_adulto', '2');
        $this->assertSame(3, ClinicalSource::count());
        $this->assertNotNull(ClinicalSource::where('manual_type', 'npt_adulto')->where('manual_version', '1')->first()->superseded_at);
        $this->assertNull(ClinicalSource::where('manual_type', 'npt_pediatrico')->first()->superseded_at);
        $this->assertCount(0, app(ClinicalEvidence::class)->sources('nutricionales'));
        Livewire::test(ClinicalManualLibrary::class)->set('type', 'npt_adulto')->set('version', '2')
            ->set('reference', 'Referencia')->set('content', str_repeat('Texto sintético ', 20))->call('save')->assertHasErrors('version');
        $this->assertSame(3, ClinicalSource::count());
    }

    public function test_upload_extracts_text_and_stores_a_private_download_and_analysis(): void
    {
        $component = Livewire::test(ClinicalManualLibrary::class)->set('file', UploadedFile::fake()->createWithContent('manual.txt', str_repeat('1. Sección de prueba. ', 20)))
            ->set('type', 'oncologicos')->set('version', '1')->set('reference', 'Prueba documental')->call('save')->assertHasNoErrors();
        $source = ClinicalSource::firstOrFail();
        Storage::disk('local')->assertExists($source->file_path);
        $component->call('download', $source->id)->assertFileDownloaded('manual.txt');
        $component->call('analyze', $source->id)->assertHasNoErrors();
        $this->assertNotEmpty($source->fresh()->manual_analysis['sections']);
    }

    public function test_agent_analysis_uses_only_the_selected_manual_and_does_not_approve_it(): void
    {
        $this->saveManual('oncologicos', '1'); $this->saveManual('npt_adulto', '1');
        AiAgent::forceCreate(['name' => ClinicalEvidence::NAME, 'integration_key' => ClinicalEvidence::KEY, 'is_active' => true, 'instructions' => 'Perfil sintético']);
        config(['services.openai.api_key' => 'test-key', 'services.openai.model' => 'test-model']);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['answer' => 'Hallazgo documental [S1].', 'citations' => [['source_id' => 'S1', 'section' => '1']]])]]],
        ]])]);
        Livewire::test(ClinicalManualLibrary::class)->call('analyzeWithAgent', 1)->assertHasNoErrors()->assertSee('Hallazgo documental');
        Http::assertSent(fn ($request) => count(json_decode($request['input'], true)['sources']) === 1 && json_decode($request['input'], true)['sources'][0]['id'] === 'S1');
        $this->assertNull(ClinicalSource::find(1)->approved_at);
    }

    public function test_non_superadministrator_cannot_access_the_library(): void
    {
        auth()->user()->syncRoles([]);
        Livewire::test(ClinicalManualLibrary::class)->assertForbidden();
    }

    public function test_card_actions_open_the_correct_dialog_and_cancel_discards_unsaved_changes(): void
    {
        $this->saveManual('oncologicos', '1');
        Livewire::test(ClinicalManualLibrary::class)
            ->call('openForm', 'npt_pediatrico')->assertSet('type', 'npt_pediatrico')->assertSet('modal', 'form')
            ->assertSee('role="dialog"', false)->assertSee('ml-modal-overlay')->assertSee('Nutrición Parenteral Pediátrico')
            ->set('content', 'Contenido sin guardar')->call('closeModal')->assertSet('content', '')->assertSet('modal', '')
            ->call('viewManual', 1)->assertSet('viewingId', 1)->assertSee('Texto para consulta')
            ->call('showHistory', 'oncologicos')->assertSet('modal', 'history')->assertSee('Versión 1');
        $this->assertSame(1, ClinicalSource::count());
    }

    public function test_antibiotic_manuals_are_used_only_for_antibiotic_mixtures(): void
    {
        $this->saveManual('antibioticos', '1');
        $manual = ClinicalSource::where('manual_type', 'antibioticos')->firstOrFail();
        $this->assertSame('antibioticos', $manual->category);
        $evidence = app(ClinicalEvidence::class);
        $this->assertCount(0, $evidence->sources('antibioticos'));
        $this->assertNotEmpty($evidence->limitations('antibioticos', []));
        $manual->update(['clinical_reviewer' => 'Responsable', 'approved_at' => now(), 'approved_by' => 1, 'valid_until' => now()->addYear()]);
        $this->assertCount(1, $evidence->sources('antibioticos'));
        $this->assertCount(0, $evidence->sources('nutricionales'));
        Livewire::test(ClinicalManualLibrary::class)->assertSee('Antibióticos');
    }

    public function test_nutrition_manual_is_selected_by_the_users_population_choice(): void
    {
        $this->saveManual('npt_adulto', '1');
        $this->saveManual('npt_pediatrico', '1');
        ClinicalSource::query()->update(['clinical_reviewer' => 'Responsable', 'approved_at' => now(), 'approved_by' => 1, 'valid_until' => now()->addYear()]);
        $evidence = app(ClinicalEvidence::class);
        $adult = $evidence->sources('nutricionales', 'ADULT');
        $infant = $evidence->sources('nutricionales', 'INF');
        $this->assertCount(1, $adult);
        $this->assertCount(1, $infant);
        $this->assertSame('npt_adulto', $adult[0]['manual_type']);
        $this->assertSame('npt_pediatrico', $infant[0]['manual_type']);
        $this->assertCount(0, $evidence->sources('nutricionales', ''));
    }

    public function test_current_manual_can_be_approved_and_revoked_from_the_view_dialog(): void
    {
        $this->saveManual('npt_adulto', '1');
        $component = Livewire::test(ClinicalManualLibrary::class)->call('viewManual', 1)
            ->assertSee('Revisión sanitaria')->set('clinicalReviewer', 'QFB Responsable')
            ->set('reviewConfirmed', true)->call('approveManual', 1)->assertHasNoErrors()->assertSee('Manual revisado y aprobado');
        $manual = ClinicalSource::findOrFail(1);
        $this->assertTrue($manual->isReviewed());
        $this->assertNull($manual->valid_until);
        $this->assertSame(auth()->id(), $manual->approved_by);
        $this->assertCount(1, app(ClinicalEvidence::class)->sources('nutricionales', 'ADULT'));
        $component->call('revokeManualApproval', 1)->assertHasNoErrors();
        $this->assertFalse($manual->fresh()->isReviewed());
        $this->assertCount(0, app(ClinicalEvidence::class)->sources('nutricionales', 'ADULT'));
    }

    public function test_historical_manual_cannot_be_approved(): void
    {
        $this->saveManual('npt_adulto', '1');
        $this->saveManual('npt_adulto', '2');
        Livewire::test(ClinicalManualLibrary::class)->call('viewManual', 1)
            ->set('clinicalReviewer', 'QFB Responsable')->set('reviewConfirmed', true)
            ->call('approveManual', 1)->assertHasErrors('manualReview');
        $this->assertFalse(ClinicalSource::findOrFail(1)->isReviewed());
    }

    public function test_updating_the_legacy_nutritional_manual_preserves_it_in_history(): void
    {
        $legacy = ClinicalSource::create(['title' => 'Manual anterior', 'category' => 'nutricionales', 'reference' => 'Revisión 3',
            'content' => str_repeat('Texto de prueba ', 20), 'sha256' => str_repeat('a', 64), 'is_manual' => true]);
        $this->saveManual('npt_adulto', '4');
        $this->assertNotNull($legacy->fresh()->superseded_at);
        $this->assertSame(2, ClinicalSource::count());
        Livewire::test(ClinicalManualLibrary::class)->call('showHistory', 'npt_adulto')->assertSee('Manual anterior')->assertSee('Versión 4');
    }
}

