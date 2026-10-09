<?php

namespace Tests\Feature;

use App\Livewire\Admin\AgentCenter;
use App\Models\AiAgent;
use App\Models\ClinicalReview;
use App\Models\ClinicalSource;
use App\Models\User;
use App\Services\Clinical\ClinicalEvidence;
use App\Services\Clinical\ClinicalPayload;
use App\Services\Clinical\ClinicalReviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class ClinicalSupportTest extends TestCase
{
    private User $user;
    private AiAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AgentFixture::seed();
        (require database_path('migrations/2026_09_29_000001_create_clinical_support.php'))->up();
        (require database_path('migrations/2026_09_30_000007_version_clinical_manual_sources.php'))->up();
        (require database_path('migrations/2026_10_05_000001_add_clinical_manual_library.php'))->up();
        (require database_path('migrations/2026_09_29_000002_add_context_to_clinical_reviews.php'))->up();
        (require database_path('migrations/2026_09_29_000003_create_clinical_agent_conversations.php'))->up();
        Schema::create('solicitud_oncos', fn (Blueprint $t) => $t->id());
        (require database_path('migrations/2026_09_30_000001_add_medical_authorization_and_anthropometry.php'))->up();
        (require database_path('migrations/2026_09_30_000002_update_clinical_observation_policy.php'))->up();
        Schema::create('inputs', function (Blueprint $t) {
            $t->id(); $t->string('description'); $t->string('unidad'); $t->integer('category_id');
            $t->decimal('mult'); $t->decimal('div'); $t->timestamps();
        });
        DB::table('inputs')->insert(['id' => 4, 'description' => 'Componente de prueba', 'unidad' => 'g/Kg', 'category_id' => 1, 'mult' => 100, 'div' => 10]);
        $this->agent = AiAgent::forceCreate(['name' => ClinicalEvidence::NAME, 'integration_key' => ClinicalEvidence::KEY,
            'instructions' => ClinicalEvidence::INSTRUCTIONS, 'is_active' => true]);
        config(['services.openai.api_key' => 'sk-test-not-a-real-credential', 'services.openai.model' => 'test-model']);
        Http::preventStrayRequests();
        $this->source(true);
        $this->source(false);
    }

    private function source(bool $manual): ClinicalSource
    {
        return ClinicalSource::create(['title' => $manual ? 'Manual de prueba' : 'Protocolo simulado', 'reference' => 'Prueba aislada, no clinica',
            'category' => 'nutricionales', 'content' => str_repeat('Evidencia sintetica de prueba. ', 5), 'sha256' => str_repeat($manual ? 'a' : 'b', 64),
            'is_manual' => $manual, 'resolves_manual_ambiguities' => !$manual, 'approved_by' => $manual ? null : $this->user->id,
            'approved_at' => $manual ? null : now(), 'clinical_reviewer' => $manual ? null : 'Profesional de prueba', 'valid_until' => now()->addYear()]);
    }

    private function data(): array
    {
        return ['peso' => '50', 'fecha_nacimiento' => '1990-01-01', 'talla' => '165', 'superficie_corporal' => '1.52', 'sexo' => 'Femenino', 'npt' => 'ADULT',
            'volumen_total' => '1000', 'via_administracion' => 'Central', 'tiempo_infusion_min' => '24', 'i_4_g/Kg' => '50',
            'nombre_paciente' => 'IDENTIDAD_PRIVADA', 'diagnostico' => 'DIAGNOSTICO_PRIVADO', 'observaciones' => 'NOTA_PRIVADA',
            'clinical_context' => array_fill_keys(array_keys(ClinicalPayload::CONTEXT_FIELDS), 'Contexto sintetico para pruebas de software, sin significado clinico.')];
    }

    private function request(array $data): Request
    {
        $request = Request::create('/clinical-test', 'POST', $data);
        $request->setUserResolver(fn () => $this->user);
        $request->setLaravelSession(app('session.store'));
        return $request;
    }

    private function answer(string $status = 'no_blockers'): array
    {
        return ['status' => $status, 'summary' => 'Resultado sintetico.', 'findings' => [],
            'coverage' => array_map(fn ($domain) => ['domain' => $domain, 'state' => 'reviewed', 'source_ids' => ['S2']],
                ['completeness', 'calculations', 'compatibility_stability', 'clinical_risks'])];
    }

    private function fake(?array $result = null): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        if ($result) foreach ($result['findings'] as &$finding) $finding += ['category' => 'safety', 'suggestion' => 'Correccion sintetica para revision profesional, sin indicacion clinica.'];
        unset($finding);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($result ?? $this->answer())]]],
        ]])]);
    }

    private function review(?array $data = null): ClinicalReview
    {
        $data ??= $this->data();
        return app(ClinicalReviewService::class)->evaluate($this->request($data), 'nutricionales', app(ClinicalPayload::class)->normalize('nutricionales', $data), 'submission');
    }

    public function test_npt_choice_routes_validation_to_its_reviewed_manual_without_mixing_populations(): void
    {
        $adult = ClinicalSource::findOrFail(1);
        $adult->update(['manual_type' => 'npt_adulto', 'manual_version' => '1', 'approved_by' => $this->user->id,
            'approved_at' => now(), 'clinical_reviewer' => 'Responsable de pruebas']);
        ClinicalSource::findOrFail(2)->update(['resolved_manual_sha256' => $adult->sha256]);
        $pediatric = $adult->replicate();
        $pediatric->fill(['title' => 'Manual pediátrico de pruebas', 'manual_type' => 'npt_pediatrico', 'manual_version' => '4', 'sha256' => str_repeat('c', 64)])->save();

        foreach (['ADULT' => [$adult, ['S1', 'S2']], 'INF' => [$pediatric, ['S'.$pediatric->id]]] as $mode => [$manual, $sourceIds]) {
            $answer = $this->answer();
            foreach ($answer['coverage'] as &$coverage) $coverage['source_ids'] = ['S'.$manual->id];
            unset($coverage);
            $this->fake($answer);
            // Keep the same birth date: the selected NPT, not inferred age, routes the manual.
            $data = array_replace($this->data(), ['npt' => $mode, 'peso' => '5', 'i_4_g/Kg' => '2']);
            $review = $this->review($data);
            $this->assertTrue($review->can_submit);
            $this->assertSame($manual->manual_type, $review->result['manual_selection']['manual_type']);
            Http::assertSent(function ($request) use ($mode, $manual, $sourceIds) {
                $input = json_decode($request['input'], true);
                $this->assertSame($sourceIds, array_column($input['sources'], 'id'));
                $this->assertSame($mode, $input['case']['mixtures'][0]['mode']);
                $this->assertSame($manual->manual_type, $input['case']['manual_selection']['manual_type']);
                $this->assertStringContainsString(ClinicalEvidence::NPT_SELECTION_POLICY, $request['instructions']);
                return true;
            });
        }

        $pediatric->update(['valid_until' => now()->subDay()]);
        $this->fake();
        $review = $this->review(array_replace($this->data(), ['npt' => 'INF', 'peso' => '5', 'i_4_g/Kg' => '2']));
        $this->assertFalse($review->can_submit);
        $this->assertStringContainsString('Nutrición Parenteral Pediátrico', implode(' ', $review->result['technical_issues']));
        $this->assertSame([], $review->result['sources']);
    }

    public function test_npt_population_must_be_explicit_and_cannot_be_supplied_as_manual_metadata(): void
    {
        foreach (['', 'unknown'] as $mode) {
            try {
                app(ClinicalPayload::class)->normalize('nutricionales', array_replace($this->data(), [
                    'npt' => $mode, 'manual_selection' => ['manual_type' => 'npt_adulto'],
                ]));
                $this->fail('An unspecified NPT was accepted');
            } catch (ValidationException $e) { $this->assertArrayHasKey('npt', $e->errors()); }
        }
    }

    public function test_numeric_payload_omits_identifiers_and_preserves_adult_and_infant_units(): void
    {
        $this->fake(); $review = $this->review();
        $this->assertTrue($review->can_submit);
        Http::assertSent(function ($request) {
            $input = json_decode($request['input'], true);
            $this->assertFalse($request['store']);
            foreach (['IDENTIDAD_PRIVADA', 'DIAGNOSTICO_PRIVADO', 'NOTA_PRIVADA', '1990-01-01'] as $private) $this->assertStringNotContainsString($private, $request['input']);
            $this->assertSame('g/dia', $input['case']['mixtures'][0]['components'][0]['unit']);
            $this->assertEquals(500, $input['case']['calculations'][0]['result']);
            return true;
        });
        $case = app(ClinicalPayload::class)->normalize('nutricionales', array_replace($this->data(), ['peso' => '5', 'npt' => 'INF', 'i_4_g/Kg' => '2']));
        $this->assertEquals(100, $case['calculations'][0]['result']);
        $this->assertSame('g/Kg', $case['mixtures'][0]['components'][0]['unit']);
        $this->assertStringNotContainsString('Resultado sintetico', DB::table('clinical_reviews')->value('result'));
        $this->assertStringNotContainsString('Contexto sintetico', DB::table('clinical_reviews')->value('clinical_context'));
    }

    public function test_local_volume_blocker_cannot_be_overridden_by_model(): void
    {
        $this->fake();
        $review = $this->review(array_replace($this->data(), ['volumen_total' => '100']));
        $this->assertFalse($review->can_submit);
        $this->assertSame('blocked', $review->result['status']);
        $this->assertSame('volumen_total', $review->result['findings'][0]['field']);
    }

    public function test_volume_suggestion_explains_the_actual_difference_without_changing_or_clearing_the_mixture(): void
    {
        $this->fake();
        $data = array_replace($this->data(), ['i_4_g/Kg' => '120']);
        $case = app(ClinicalPayload::class)->normalize('nutricionales', $data);
        $this->assertSame(1000.0, $case['mixtures'][0]['volume_ml']);
        $this->assertSame(120.0, $case['mixtures'][0]['components'][0]['quantity']);
        $review = $this->review($data);
        $finding = $review->result['findings'][0];
        $this->assertSame('1200 mL de componentes - 1000 mL de volumen total = 200 mL de diferencia.', $finding['calculation']);
        $this->assertStringContainsString('al menos 1200 mL para esta comprobacion aritmetica', $finding['suggestion']);
        $this->assertStringContainsString('Si deben mantenerse los 1000 mL prescritos', $finding['suggestion']);
        $this->assertStringContainsString('no reduzcas dosis ni agregues agua automaticamente', $finding['suggestion']);
        $this->assertStringNotContainsString('Resolver esta diferencia no garantiza', $finding['suggestion']);
        $this->assertStringNotContainsString('vuelve a validar', $finding['suggestion']);
        $this->assertSame('blocked', $review->result['status']);
        $this->assertFalse($review->can_submit);
        $this->assertFalse($review->result['requires_medical_authorization']);
        $this->assertSame($finding['suggestion'], app(ClinicalReviewService::class)->response($review, $this->user)['result']['findings'][0]['suggestion']);
        $this->assertStringNotContainsString($finding['suggestion'], DB::table('clinical_reviews')->value('result'));

        // Correcting this arithmetic mismatch clears the request even when optional context is absent.
        $data['volumen_total'] = '1200'; unset($data['clinical_context']);
        $this->assertEmpty(app(ClinicalPayload::class)->normalize('nutricionales', $data)['local_blockers']);
        $this->assertTrue($this->review($data)->can_submit);
    }

    public function test_response_schema_constrains_source_ids_and_fields_to_the_supplied_case(): void
    {
        $this->fake();
        $this->review();
        Http::assertSent(function ($request) {
            $schema = $request['text']['format']['schema']['properties'];
            foreach (['findings', 'coverage'] as $section) {
                $ids = $schema[$section]['items']['properties']['source_ids'];
                $this->assertSame(['S1', 'S2'], $ids['items']['enum']);
                $this->assertSame(20, $ids['maxItems']);
            }
            $case = json_decode($request['input'], true)['case'];
            $this->assertSame($case['fields'], $schema['findings']['items']['properties']['field']['enum']);
            $this->assertContains('suggestion', $schema['findings']['items']['required']);
            $this->assertSame('string', $schema['findings']['items']['properties']['suggestion']['type']);
            $this->assertStringContainsString(ClinicalEvidence::SUGGESTION_POLICY, $request['instructions']);
            $this->assertStringContainsString(ClinicalEvidence::PARAMETER_SCOPE_POLICY, $request['instructions']);
            $this->assertContains('internal_comment', $schema['findings']['items']['properties']['category']['enum']);
            return true;
        });
    }

    public function test_reviewed_suggestion_is_preserved_in_results_and_history_without_removing_rejection(): void
    {
        $answer = $this->answer('blocked');
        $suggestion = 'Componente de prueba: expresar la cantidad diaria en g/dia en lugar de g/Kg, segun S2, seccion de unidades del caso ficticio.';
        $answer['findings'][] = ['field' => 'i_4_g/Kg', 'severity' => 'blocking', 'message' => 'Inconsistencia sintetica.',
            'calculation' => '', 'suggestion' => $suggestion, 'source_ids' => ['S2']];
        $this->fake($answer); $review = $this->review();
        $this->assertSame($suggestion, $review->result['findings'][0]['suggestion']);
        $this->assertSame($suggestion, $review->result['audit_findings'][0]['suggestion']);
        $this->assertFalse($review->can_submit);
        $this->assertStringNotContainsString($suggestion, DB::table('clinical_reviews')->value('result'));
        $this->assertSame($suggestion, app(ClinicalReviewService::class)->resultForDisplay($review)['findings'][0]['suggestion']);
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)->assertDontSee('Revisiones recientes');
    }

    public function test_unreviewed_or_missing_sources_cannot_supply_numeric_correction_suggestions(): void
    {
        foreach ([['S1'], [], ['S1', 'S2']] as $sources) {
            $answer = $this->answer('needs_review');
            $answer['findings'][] = ['field' => 'i_4_g/Kg', 'severity' => 'review', 'message' => 'Hallazgo sintetico pendiente.',
                'calculation' => '', 'suggestion' => 'VALOR_NO_VERIFICADO 999', 'source_ids' => $sources];
            $this->fake($answer); $review = $this->review();
            $this->assertStringNotContainsString('VALOR_NO_VERIFICADO', json_encode(app(ClinicalReviewService::class)->response($review, $this->user)));
            $this->assertSame('', $review->result['findings'][0]['suggestion']);
            $this->assertSame('Hallazgo sintetico pendiente.', $review->result['findings'][0]['message']);
            $this->assertSame('test-model', $review->result['model']);
            $this->assertFalse($review->can_submit);
        }
    }

    public function test_missing_or_invalid_correction_suggestion_fields_fail_closed(): void
    {
        foreach (['missing', null, ['invalid'], str_repeat('x', 3001)] as $suggestion) {
            $answer = $this->answer('needs_review');
            $answer['findings'][] = ['field' => 'volumen_total', 'severity' => 'review', 'category' => 'safety',
                'message' => 'Hallazgo sintetico.', 'calculation' => '', 'source_ids' => ['S2']];
            if ($suggestion !== 'missing') $answer['findings'][0]['suggestion'] = $suggestion;
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::preventStrayRequests();
            Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($answer)]]],
            ]])]);
            $review = $this->review();
            $this->assertFalse($review->can_submit);
            $this->assertNull($review->result['model']);
            $this->assertSame('needs_review', $review->result['status']);
        }
    }

    public function test_empty_suggestion_preserves_the_finding_and_its_submission_block(): void
    {
        foreach (['review' => 'needs_review', 'blocking' => 'blocked'] as $severity => $status) {
            $answer = $this->answer();
            $answer['findings'][] = ['field' => 'volumen_total', 'severity' => $severity, 'category' => 'safety',
                'message' => 'Hallazgo sin ajuste sustentado.', 'calculation' => '', 'suggestion' => '   ', 'source_ids' => ['S2']];
            $this->fake($answer); $review = $this->review();
            $this->assertSame('test-model', $review->result['model']);
            $this->assertSame('', $review->result['findings'][0]['suggestion']);
            $this->assertSame('Hallazgo sin ajuste sustentado.', $review->result['findings'][0]['message']);
            $this->assertSame($severity, $review->result['findings'][0]['severity']);
            $this->assertSame($status, $review->result['status']);
            $this->assertFalse($review->can_submit);
            $this->assertFalse($review->result['requires_medical_authorization']);
        }
    }

    public function test_old_generic_suggestions_are_hidden_without_modifying_review_history_or_decision(): void
    {
        $this->fake(); $review = $this->review();
        $original = $review->result;
        $original['status'] = 'needs_review';
        $legacy = 'Solicita al profesional responsable confirmar el parametro observado con una fuente vigente y revisada antes de proponer un valor de correccion.';
        $original['findings'] = [['field' => 'volumen_total', 'severity' => 'review', 'message' => 'Hallazgo pendiente de evidencia revisada: Hallazgo historico de prueba.',
            'calculation' => '', 'suggestion' => $legacy, 'source_ids' => ['S1']]];
        $original['audit_findings'] = $original['findings'];
        $review->update(['result' => $original, 'can_submit' => false]);
        $response = app(ClinicalReviewService::class)->response($review, $this->user);
        $this->assertStringNotContainsString($legacy, json_encode($response));
        $this->assertStringNotContainsString('Hallazgo pendiente de evidencia revisada', json_encode($response));
        $this->assertSame('Hallazgo historico de prueba.', $response['result']['findings'][0]['message']);
        $this->assertSame('review', $response['result']['findings'][0]['severity']);
        $this->assertSame(['S1'], $response['result']['findings'][0]['source_ids']);
        $this->assertSame($original['sources'], $response['result']['sources']);
        $this->assertSame('needs_review', $response['result']['status']);
        $this->assertFalse($response['can_submit']);
        $this->assertSame($original, $review->fresh()->result);
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertDontSee($legacy)->assertDontSee('Hallazgo pendiente de evidencia revisada')->assertDontSee('Revisiones recientes');
    }

    public function test_hospital_responses_exclude_internal_data_but_keep_observations_suggestions_and_decisions(): void
    {
        $this->fake(); $review = $this->review(array_replace($this->data(), ['volumen_total' => '100']));
        $original = $review->result;
        $original['technical_issues'][] = 'INTERNAL_SERVICE_DIAGNOSTIC';
        $original['extra_internal_metadata'] = 'INTERNAL_FUTURE_FIELD';
        $original['findings'][0]['internal_debug'] = 'INTERNAL_FINDING_METADATA';
        $review->update(['result' => $original]);
        $service = app(ClinicalReviewService::class);
        $internal = $service->response($review, $this->user);
        $this->assertTrue($internal['can_view_internal']);
        foreach (['calculations', 'technical_issues', 'sources', 'notice', 'coverage', 'model', 'limitations'] as $key) {
            $this->assertSame($original[$key], $internal['result'][$key]);
        }
        foreach (['Cliente', 'Institucion'] as $role) {
            $client = User::forceCreate(['name' => 'Cliente de prueba']);
            $client->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            // A mixed profile still follows the hospital-side visibility convention.
            foreach ([false, true] as $mixed) {
                if ($mixed) $client->assignRole('Super Admin');
                $response = $service->response($review, $client);
                $this->assertFalse($response['can_view_internal']);
                $this->assertFalse($response['can_submit']);
                $this->assertSame($internal['requires_medical_authorization'], $response['requires_medical_authorization']);
                $this->assertSame('blocked', $response['result']['status']);
                $this->assertSame($original['summary'], $response['result']['summary']);
                foreach (['field', 'severity', 'observation_type', 'message', 'calculation', 'suggestion'] as $key) {
                    $this->assertSame($original['findings'][0][$key], $response['result']['findings'][0][$key]);
                }
                foreach (['calculations', 'technical_issues', 'sources', 'notice', 'coverage', 'model', 'limitations', 'audit_findings', 'audit_summary', 'extra_internal_metadata'] as $key) {
                    $this->assertArrayNotHasKey($key, $response['result']);
                }
                $this->assertArrayNotHasKey('source_ids', $response['result']['findings'][0]);
                $this->assertArrayNotHasKey('internal_debug', $response['result']['findings'][0]);
                $this->assertStringNotContainsString('INTERNAL_', json_encode($response));
            }
        }
        $this->assertSame($original, $review->fresh()->result);
    }

    public function test_validation_endpoint_uses_the_authenticated_viewer_not_client_supplied_visibility(): void
    {
        $this->fake();
        $client = User::forceCreate(['name' => 'Usuario de hospital']);
        $client->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'Cliente', 'guard_name' => 'web']));
        $client->givePermissionTo(\Spatie\Permission\Models\Permission::create(['name' => 'nutricionales_solicitudes_store', 'guard_name' => 'web']));
        $request = $this->request($this->data() + ['can_view_internal' => true, 'viewer_role' => 'Super Admin']);
        $request->setUserResolver(fn () => $client);
        $response = app(\App\Http\Controllers\Admin\ClinicalReviewController::class)->validateRequest($request, 'nutricionales',
            app(ClinicalPayload::class), app(ClinicalReviewService::class));
        $data = $response->getData(true);
        $this->assertFalse($data['can_view_internal']);
        $this->assertArrayNotHasKey('sources', $data['result']);
        $this->assertArrayNotHasKey('technical_issues', $data['result']);
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    public function test_cached_conversation_review_is_redacted_for_the_hospital_without_another_provider_call(): void
    {
        $this->fake();
        $client = User::forceCreate(['name' => 'Usuario institucional']);
        $client->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'Institucion', 'guard_name' => 'web']));
        $request = $this->request(array_replace($this->data(), ['volumen_total' => '100']));
        $request->setUserResolver(fn () => $client);
        $case = app(ClinicalPayload::class)->normalize('nutricionales', $request->all());
        $service = app(ClinicalReviewService::class);
        $review = $service->evaluate($request, 'nutricionales', $case, 'conversation', 17);
        $target = ['kind' => 'nutricionales', 'id' => 17];
        $messages = \Mockery::mock(\App\Services\MixtureMessagingService::class);
        $messages->shouldReceive('resolve')->once()->with($client, 'nutricionales', 17)->andReturn($target);
        $payload = \Mockery::mock(ClinicalPayload::class);
        $payload->shouldReceive('fromTarget')->once()->with($target)->andReturn($case);
        $response = app(\App\Http\Controllers\Admin\ClinicalReviewController::class)->conversation($request, 'nutricionales', 17,
            $messages, $payload, $service, app(ClinicalEvidence::class))->getData(true);
        $this->assertSame($review->id, $response['review_id']);
        $this->assertFalse($response['can_view_internal']);
        $this->assertSame('blocked', $response['result']['status']);
        $this->assertNotEmpty($response['result']['findings'][0]['suggestion']);
        foreach (['sources', 'calculations', 'technical_issues', 'notice', 'model', 'coverage'] as $key) {
            $this->assertArrayNotHasKey($key, $response['result']);
        }
        Http::assertSentCount(1);
    }

    public function test_no_sources_requires_empty_citations_and_keeps_submission_blocked(): void
    {
        ClinicalSource::query()->delete();
        $answer = $this->answer('needs_review');
        foreach ($answer['coverage'] as &$coverage) {
            $coverage['source_ids'] = [];
            $coverage['state'] = 'missing';
        }
        unset($coverage);
        $this->fake($answer);
        $review = $this->review();
        $this->assertSame('test-model', $review->result['model']);
        $this->assertFalse($review->can_submit);
        Http::assertSent(function ($request) {
            $schema = $request['text']['format']['schema']['properties'];
            foreach (['findings', 'coverage'] as $section) {
                $ids = $schema[$section]['items']['properties']['source_ids'];
                $this->assertSame(0, $ids['maxItems']);
                $this->assertArrayNotHasKey('enum', $ids['items']);
            }
            return true;
        });
    }

    public function test_source_ids_with_section_names_are_not_silently_accepted(): void
    {
        $answer = $this->answer();
        $answer['coverage'][0]['source_ids'] = ['S1 - Seccion 1'];
        $this->fake($answer);
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        $this->assertNull($review->result['model']);
        $this->assertStringContainsString('No se obtuvo una revision completa', json_encode($review->result));
    }

    public function test_real_form_missing_clinical_context_is_optional_when_mixture_review_is_complete(): void
    {
        $answer = $this->answer('needs_review');
        foreach ($answer['coverage'] as &$coverage) {
            if (in_array($coverage['domain'], ['completeness', 'clinical_risks'], true)) $coverage['state'] = 'missing';
        }
        unset($coverage);
        $this->fake($answer);
        $data = $this->data(); unset($data['clinical_context']);
        $case = (new ClinicalPayload)->normalize('nutricionales', $data);
        $review = app(ClinicalReviewService::class)->evaluate($this->request($data), 'nutricionales', $case, 'submission');
        $this->assertNotEmpty($case['missing_context']);
        $this->assertTrue($review->can_submit);
        $this->assertSame('no_blockers', $review->result['status']);
        $this->assertNotContains('missing', array_column($review->result['coverage'], 'state'));
        $this->assertContains('missing', array_column($review->result['audit_coverage'], 'state'));
        $this->assertEmpty($review->result['limitations']);
        $this->assertEmpty($review->result['findings']);
        $this->assertStringContainsString('no detecto bloqueos', $review->result['summary']);
    }

    public function test_missing_optional_context_finding_is_audited_without_blocking_submission(): void
    {
        $answer = $this->answer('needs_review');
        $answer['findings'] = [[
            'field' => 'observaciones',
            'severity' => 'review',
            'category' => 'missing_clinical_context',
            'message' => 'Contexto clinico opcional no capturado.',
            'calculation' => '',
            'suggestion' => '',
            'source_ids' => ['S2'],
        ]];
        $this->fake($answer);
        $data = $this->data(); unset($data['clinical_context']);
        $review = $this->review($data);

        $this->assertTrue($review->can_submit);
        $this->assertSame('no_blockers', $review->result['status']);
        $this->assertEmpty($review->result['findings']);
        $this->assertCount(1, $review->result['audit_findings']);
    }

    public function test_missing_optional_compatibility_context_is_a_visible_confirmable_advisory(): void
    {
        $answer = $this->answer('needs_review');
        foreach ($answer['coverage'] as &$coverage) {
            if ($coverage['domain'] === 'compatibility_stability') $coverage['state'] = 'missing';
        }
        unset($coverage);
        $this->fake($answer);
        $data = $this->data(); unset($data['clinical_context']);
        $review = $this->review($data);

        $this->assertTrue($review->can_submit);
        $this->assertSame('advisory', $review->result['status']);
        $this->assertTrue($review->result['requires_risk_acknowledgement']);
        $this->assertSame('compatibility_limitation', $review->result['findings'][0]['category']);
        $this->assertSame('advertencia informativa', $review->result['findings'][0]['observation_type']);
        $this->assertStringContainsString('no constituye un rechazo', $review->result['findings'][0]['message']);
        $this->assertSame('reviewed', collect($review->result['coverage'])->firstWhere('domain', 'compatibility_stability')['state']);
        $this->assertSame('missing', collect($review->result['audit_coverage'])->firstWhere('domain', 'compatibility_stability')['state']);
    }

    public function test_oncology_and_antibiotic_payloads_use_catalog_names_and_calculate_concentration(): void
    {
        foreach (['medicines_catalog', 'diluents', 'administration_routes'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string(match ($table) { 'medicines_catalog' => 'denominacion', 'diluents' => 'denominacion_generica', default => 'name' });
                if ($table === 'medicines_catalog') $t->string('catalog_category');
            });
        }
        DB::table('medicines_catalog')->insert([
            ['id' => 1, 'denominacion' => 'Medicamento de prueba A', 'catalog_category' => 'oncologicos'],
            ['id' => 2, 'denominacion' => 'Medicamento de prueba B', 'catalog_category' => 'antibioticos'],
        ]);
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Diluyente de prueba']);
        DB::table('administration_routes')->insert(['id' => 1, 'name' => 'Via de prueba']);
        foreach (['oncologicos' => 1, 'antibioticos' => 2] as $kind => $id) {
            $data = $this->data();
            $data['mezclas'] = json_encode([['volumen_dilucion' => 100, 'tiempo_infusion' => 60,
                'medicamentos' => [['medicamento_id' => $id, 'nombre' => 'NOMBRE_NO_CONFIABLE', 'dosis' => 50, 'diluyente_id' => 1, 'via_administracion_id' => 1]]]]);
            $case = app(ClinicalPayload::class)->normalize($kind, $data);
            $this->assertSame(0.5, $case['calculations'][0]['result']);
            $this->assertSame('mg/mL', $case['calculations'][0]['unit']);
            $this->assertSame(60.0, $case['mixtures'][0]['infusion_minutes']);
            $this->assertSame('Via de prueba', $case['mixtures'][0]['components'][0]['route']);
            $this->assertSame(165.0, $case['patient']['height_cm']);
            $this->assertSame(1.52, $case['patient']['body_surface_m2']);
            $this->assertStringNotContainsString('NOMBRE_NO_CONFIABLE', json_encode($case));
        }
        $this->expectException(ValidationException::class);
        app(ClinicalPayload::class)->normalize('oncologicos', $data);
    }

    public function test_unapproved_or_expired_sources_and_manual_ambiguities_never_clear_request(): void
    {
        $this->fake();
        ClinicalSource::whereKey(2)->update(['approved_at' => null]);
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        $this->assertStringContainsString('mEq/mL', json_encode($review->result, JSON_UNESCAPED_SLASHES));
        ClinicalSource::whereKey(2)->update(['approved_at' => now(), 'valid_until' => now()->subDay()]);
        $this->assertFalse($this->review()->can_submit);
    }

    public function test_missing_key_inactive_agent_and_provider_failures_fail_closed(): void
    {
        config(['services.openai.api_key' => null]);
        $this->assertFalse($this->review()->can_submit);
        Http::assertNothingSent();
        $this->agent->update(['is_active' => false]);
        $this->assertFalse($this->review()->can_submit);
        Http::assertNothingSent();
        $this->agent->update(['is_active' => true]);
        config(['services.openai.api_key' => 'sk-test-not-real']);
        Http::fake(['*' => Http::response('SECRET_PROVIDER_BODY', 500)]);
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        $this->assertStringNotContainsString('SECRET_PROVIDER_BODY', json_encode($review->result));
    }

    public function test_unknown_citations_incomplete_coverage_and_refusals_never_clear_request(): void
    {
        $answer = $this->answer(); $answer['coverage'][0]['source_ids'] = ['INVENTED'];
        $this->fake($answer); $this->assertFalse($this->review()->can_submit);
        $answer = $this->answer(); array_pop($answer['coverage']);
        $this->fake($answer); $this->assertFalse($this->review()->can_submit);
        $answer = $this->answer(); $answer['coverage'][0]['state'] = 'missing';
        $this->fake($answer); $this->assertFalse($this->review()->can_submit);
        $answer = $this->answer(); $answer['findings'][] = ['field' => 'observaciones', 'severity' => 'warning',
            'message' => 'Afirmacion sin evidencia', 'calculation' => '', 'source_ids' => []];
        $this->fake($answer); $this->assertFalse($this->review()->can_submit);
        Http::fake(['*' => Http::response(['status' => 'incomplete', 'output' => []])]);
        $this->assertFalse($this->review()->can_submit);
    }

    public function test_receipt_is_bound_to_payload_user_session_sources_expiry_and_human_acknowledgement(): void
    {
        $this->fake(); $review = $this->review(); $service = app(ClinicalReviewService::class);
        $data = $this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1'];
        $service->requireSubmission($this->request($data), 'nutricionales');
        $otherRequest = $this->request($data);
        $otherRequest->setUserResolver(fn () => User::forceCreate(['name' => 'Otro usuario']));
        try { $service->requireSubmission($otherRequest, 'nutricionales'); $this->fail('Other user receipt accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('clinical_review', $e->errors()); }
        foreach ([['npt' => 'INF'], ['peso' => '51'], ['clinical_acknowledged' => '0'], ['clinical_review_token' => 'forged'], ['observaciones' => 'changed']] as $change) {
            try { $service->requireSubmission($this->request(array_replace($data, $change)), 'nutricionales'); $this->fail('Untrusted receipt accepted'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('clinical_review', $e->errors()); }
        }
        $review->update(['expires_at' => now()->subSecond()]);
        try { $service->requireSubmission($this->request($data), 'nutricionales'); $this->fail('Expired receipt accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('clinical_review', $e->errors()); }
        $review->update(['expires_at' => now()->addMinutes(10), 'session_hash' => str_repeat('0', 64)]);
        $this->expectException(ValidationException::class);
        $service->requireSubmission($this->request($data), 'nutricionales');
    }

    public function test_changed_catalog_or_revoked_source_invalidates_receipt(): void
    {
        $this->fake(); $review = $this->review();
        $data = $this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1'];
        DB::table('inputs')->where('id', 4)->update(['div' => 20]);
        try { app(ClinicalReviewService::class)->requireSubmission($this->request($data), 'nutricionales'); $this->fail('Changed catalog accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('clinical_review', $e->errors()); }
        DB::table('inputs')->where('id', 4)->update(['div' => 10]);
        ClinicalSource::whereKey(2)->update(['approved_at' => null]);
        $this->expectException(ValidationException::class);
        app(ClinicalReviewService::class)->requireSubmission($this->request($data), 'nutricionales');
    }

    public function test_sources_changed_while_provider_is_running_invalidate_its_response(): void
    {
        Http::fake(['*' => function () {
            ClinicalSource::whereKey(2)->update(['approved_at' => null]);
            return Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [
                ['type' => 'output_text', 'text' => json_encode($this->answer())],
            ]]]]);
        }]);
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        $this->assertStringContainsString('cambiaron durante la revision', json_encode($review->result));
    }

    public function test_conversation_uses_the_recorded_encrypted_context_and_current_components(): void
    {
        $this->fake(); $review = $this->review();
        $review->update(['record_type' => 'solicituds', 'record_id' => 123, 'used_at' => now()]);
        $patient = (new \App\Models\Nutricionales\SolicitudPatient)->forceFill($this->data());
        $detail = (new \App\Models\Nutricionales\SolicitudDetail)->forceFill($this->data());
        $row = (new \App\Models\Nutricionales\SolicitudInput)->forceFill(['input_id' => 4, 'valor' => 40]);
        $row->setRelation('input', \App\Models\Nutricionales\Input::find(4));
        $model = (new \App\Models\Nutricionales\Solicitud)->forceFill(['id' => 123]);
        $model->setRelation('solicitud_patient', $patient)->setRelation('solicitud_detail', $detail)
            ->setRelation('input', new \Illuminate\Database\Eloquent\Collection([$row]));
        $case = app(ClinicalPayload::class)->fromTarget(['kind' => 'nutricionales', 'model' => $model]);
        $this->assertSame($this->data()['clinical_context'], $case['clinical_context']);
        $this->assertSame(40.0, $case['mixtures'][0]['components'][0]['quantity']);
        $this->assertNotNull($case['context_recorded_at']);
        $this->assertStringNotContainsString('IDENTIDAD_PRIVADA', json_encode($case));
        $detail->npt = 'INF';
        $case = app(ClinicalPayload::class)->fromTarget(['kind' => 'nutricionales', 'model' => $model]);
        $this->assertSame('INF', $case['mixtures'][0]['mode']);
        $this->assertSame('npt_pediatrico', $case['manual_selection']['manual_type']);
    }

    public function test_receipt_consumption_is_single_use_and_rolls_back_with_request(): void
    {
        $this->fake(); $review = $this->review(); $service = app(ClinicalReviewService::class);
        $request = $this->request($this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1']);
        $service->requireSubmission($request, 'nutricionales');
        DB::beginTransaction(); $service->consume($request, 'solicituds', 123); DB::rollBack();
        $this->assertNull($review->fresh()->used_at);
        DB::transaction(fn () => $service->consume($request, 'solicituds', 123));
        $this->assertSame(123, $review->fresh()->record_id);
        $this->expectException(ValidationException::class);
        $service->consume($request, 'solicituds', 123);
    }

    public function test_raw_submissions_are_blocked_before_legacy_controllers_write_any_record(): void
    {
        $request = $this->request($this->data());
        foreach ([\App\Http\Controllers\Admin\Nutricionales\SolicitudController::class, \App\Http\Controllers\Admin\Oncologicos\SolicitudController::class] as $controller) {
            try {
                app($controller)->store($request, app(\App\Services\OncologyMixtureDeliveryScheduleService::class));
                $this->fail('Submission was not guarded');
            } catch (ValidationException $e) { $this->assertArrayHasKey('clinical_review', $e->errors()); }
        }
        $this->assertDatabaseCount('clinical_reviews', 0);
    }

    public function test_source_management_is_superadmin_only_and_retains_audit(): void
    {
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)->assertDontSee('Fuentes y protocolos')
            ->call('revokeClinicalSource', 2)->assertHasNoErrors();
        $this->assertFalse(ClinicalSource::find(2)->isReviewed());
        $other = User::forceCreate(['name' => 'Sin permiso']);
        $component = Livewire::test(AgentCenter::class);
        $this->actingAs($other);
        $component->call('revokeClinicalSource', 1)->assertForbidden();
    }

    public function test_v4_install_replaces_active_manual_preserves_history_and_custom_agent_settings(): void
    {
        $this->fake();
        $review = $this->review();
        $oldManual = ClinicalSource::find(1);
        $this->agent->update(['instructions' => 'Instruccion institucional personalizada.', 'is_active' => false]);
        $install = require database_path('migrations/2026_09_30_000008_install_clinical_manual_v4.php');
        $install->up();
        $install->up();
        $manual = ClinicalSource::current()->where('is_manual', true)->sole();
        $this->assertSame('4', $manual->manual_version);
        $this->assertSame(ClinicalEvidence::MANUAL_TITLE, $manual->title);
        $this->assertStringContainsString('SUPUESTO 1.- RECHAZO', $manual->content);
        $this->assertStringContainsString('SUPUESTO 2.- SUGERENCIA', $manual->content);
        $this->assertStringContainsString('60%', $manual->content);
        $this->assertStringContainsString('45 mEq', $manual->content);
        $this->assertSame(hash('sha256', $manual->content), $manual->sha256);
        $this->assertFalse($manual->isReviewed());
        $this->assertNotNull($oldManual->fresh()->superseded_at);
        $this->assertSame($oldManual->content, $oldManual->fresh()->content);
        $this->assertTrue(ClinicalSource::find(2)->isReviewed());
        $this->assertFalse($this->agent->fresh()->is_active);
        $this->assertStringContainsString('Instruccion institucional personalizada.', $this->agent->fresh()->instructions);
        $this->assertSame(1, substr_count($this->agent->fresh()->instructions, ClinicalEvidence::MANUAL_POLICY));
        $this->assertNotSame($review->sources_hash, app(ClinicalEvidence::class)->fingerprint('nutricionales'));
        $this->assertDatabaseCount('clinical_sources', 3);
        $this->artisan('clinical:import-manual', ['path' => resource_path(ClinicalEvidence::MANUAL_FILE)])->assertSuccessful();
        $this->assertSame($manual->id, ClinicalSource::current()->where('is_manual', true)->sole()->id);
        $this->assertDatabaseCount('clinical_sources', 3);
        $this->artisan('clinical:import-manual', ['path' => resource_path('clinical/manual-revision3.docx')])->assertSuccessful();
        $this->assertSame($manual->id, ClinicalSource::current()->where('is_manual', true)->sole()->id);
    }

    public function test_v4_needs_its_own_professional_clarification_and_download_is_superadmin_only(): void
    {
        (require database_path('migrations/2026_09_30_000008_install_clinical_manual_v4.php'))->up();
        $evidence = app(ClinicalEvidence::class);
        $manual = ClinicalSource::current()->where('is_manual', true)->sole();
        $this->assertContains(ClinicalEvidence::MANUAL_LIMITATIONS, $evidence->limitations('nutricionales', $evidence->sources('nutricionales')));
        ClinicalSource::find(2)->update(['resolved_manual_sha256' => $manual->sha256]);
        $this->assertSame([], $evidence->limitations('nutricionales', $evidence->sources('nutricionales')));
        $this->assertFalse($manual->isReviewed());
        $component = Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertSee(ClinicalEvidence::MANUAL_TITLE)->assertSee('Historial de versiones')
            ->call('downloadClinicalManual', $manual->id)->assertFileDownloaded('Manual Maestro de Validacion V4.docx');
        $this->actingAs(User::forceCreate(['name' => 'Sin permiso']));
        $component->call('downloadClinicalManual', $manual->id)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_v4_sources_and_instructions_reach_both_validation_and_chat_without_old_manual(): void
    {
        (require database_path('migrations/2026_09_30_000008_install_clinical_manual_v4.php'))->up();
        $manual = ClinicalSource::current()->where('is_manual', true)->sole();
        $this->fake();
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        Http::assertSent(function ($request) use ($manual) {
            $input = json_decode($request['input'], true);
            $this->assertStringContainsString(ClinicalEvidence::MANUAL_TITLE, $request['instructions']);
            $this->assertStringContainsString('Supuesto 1', $request['instructions']);
            $this->assertStringNotContainsString('Evidencia sintetica de prueba.', collect($input['sources'])->firstWhere('is_manual', true)['content']);
            $this->assertSame(['S2', 'S'.$manual->id], array_column($input['sources'], 'id'));
            return true;
        });
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['answer' => 'Respuesta sintetica.', 'citations' => []])]]],
        ]])]);
        app(\App\Services\Clinical\OpenAiClinicalChat::class)->reply($this->agent->fresh(), [], 'Pregunta tecnica de prueba sin pacientes.', $this->user->id);
        Http::assertSent(fn ($request) => isset(json_decode($request['input'], true)['question'])
            && array_column(json_decode($request['input'], true)['sources'], 'id') === ['S2', 'S'.$manual->id]
            && str_contains($request['instructions'], ClinicalEvidence::MANUAL_TITLE));
    }

    public function test_v4_import_rejects_a_different_file_without_superseding_existing_sources(): void
    {
        $this->artisan('clinical:import-manual', ['path' => resource_path('clinical/manual-revision3.docx'), '--manual-version' => '4'])->assertFailed();
        $this->assertDatabaseCount('clinical_sources', 2);
        $this->assertNull(ClinicalSource::find(1)->superseded_at);
        $this->artisan('clinical:import-manual', ['path' => resource_path(ClinicalEvidence::MANUAL_FILE), '--manual-version' => '3'])->assertFailed();
        $this->assertDatabaseCount('clinical_sources', 2);
        $this->assertNull(ClinicalSource::find(1)->superseded_at);
        Http::assertNothingSent();
    }

    public function test_import_registers_the_protected_integration_key_and_is_idempotent(): void
    {
        $this->agent->delete();
        $path = tempnam(sys_get_temp_dir(), 'clinical-manual-');
        try {
            $zip = new \ZipArchive();
            $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.str_repeat('Documento sintetico de prueba. ', 10).'</w:t></w:r></w:p></w:body></w:document>');
            $zip->close();
            $this->artisan('clinical:import-manual', ['path' => $path])->assertSuccessful();
            $this->artisan('clinical:import-manual', ['path' => $path])->assertSuccessful();
            $this->assertSame(1, AiAgent::where('integration_key', ClinicalEvidence::KEY)->count());
            $agent = AiAgent::where('integration_key', ClinicalEvidence::KEY)->firstOrFail();
            $this->assertTrue($agent->is_active);
            $this->assertSame('manual', $agent->configuration['activation']);
            $this->assertSame(ClinicalEvidence::INSTRUCTIONS, $agent->instructions);
            $this->assertDatabaseCount('clinical_sources', 3);
            $this->assertFalse(ClinicalSource::latest('id')->first()->isReviewed());
            Http::assertNothingSent();
        } finally { if (is_file($path)) unlink($path); }
    }

    public function test_required_patient_fields_fail_before_ai_and_are_specific_to_category(): void
    {
        foreach (['nutricionales' => ['peso', 'fecha_nacimiento'], 'oncologicos' => ['talla', 'superficie_corporal'], 'antibioticos' => ['talla', 'superficie_corporal']] as $kind => $fields) {
            $data = $this->data();
            foreach ($fields as $field) unset($data[$field]);
            try { app(ClinicalPayload::class)->normalize($kind, $data); $this->fail('Missing patient data accepted'); }
            catch (ValidationException $e) { foreach ($fields as $field) $this->assertArrayHasKey($field, $e->errors()); }
        }
        $data = $this->data(); unset($data['talla'], $data['superficie_corporal']);
        $this->assertArrayNotHasKey('height_cm', app(ClinicalPayload::class)->normalize('nutricionales', $data)['patient']);
        Http::assertNothingSent();
    }

    public function test_structured_patient_data_does_not_require_duplicate_anthropometry_in_free_text(): void
    {
        $data = $this->data(); unset($data['clinical_context']['patient_factors']);
        $this->fake();
        $this->assertTrue($this->review($data)->can_submit);
    }

    public function test_absent_history_notices_are_not_exposed_but_known_safety_risks_are_retained(): void
    {
        $answer = $this->answer('needs_review');
        $answer['summary'] = 'Faltan alergias y laboratorios';
        $answer['findings'] = [
            ['field' => 'observaciones', 'severity' => 'review', 'category' => 'missing_clinical_context', 'message' => 'Faltan alergias y laboratorios', 'calculation' => '', 'source_ids' => ['S2']],
            ['field' => 'i_4_g/Kg', 'severity' => 'blocking', 'category' => 'safety', 'message' => 'Riesgo conocido de prueba', 'calculation' => '', 'source_ids' => ['S2']],
        ];
        $this->fake($answer); $review = $this->review();
        $public = app(ClinicalReviewService::class)->response($review, $this->user);
        $this->assertStringNotContainsString('Faltan alergias', json_encode($public));
        $this->assertStringNotContainsString('Requiere aclaracion profesional', json_encode($public));
        $this->assertSame('Riesgo conocido de prueba', $public['result']['findings'][0]['message']);
        $this->assertCount(2, $review->result['audit_findings']);
        $this->assertFalse($review->can_submit);
    }

    public function test_submission_and_conversation_show_only_parameter_findings_and_keep_known_risks(): void
    {
        $answer = $this->answer('blocked');
        $answer['summary'] = 'COMENTARIO_GENERAL_RESUMEN';
        $answer['findings'] = [
            ['field' => 'observaciones', 'severity' => 'information', 'category' => 'information',
                'message' => 'COMENTARIO_GENERAL: gracias por utilizar el servicio.', 'calculation' => '', 'suggestion' => 'CONSEJO_GENERAL', 'source_ids' => ['S2']],
            ['field' => 'observaciones', 'severity' => 'review', 'category' => 'internal_comment',
                'message' => 'COMENTARIO_INTERNO: revisar la configuracion del centro.', 'calculation' => '', 'suggestion' => 'CONSEJO_INTERNO', 'source_ids' => ['S2']],
            ['field' => 'i_4_g/Kg', 'severity' => 'blocking', 'category' => 'safety',
                'message' => 'Componente de prueba: inconsistencia en la unidad capturada.', 'calculation' => '',
                'suggestion' => 'Componente de prueba: usar la unidad indicada en la seccion de unidades de S2 para esta solicitud ficticia.', 'source_ids' => ['S2']],
        ];
        $case = app(ClinicalPayload::class)->normalize('nutricionales', $this->data());
        $service = app(ClinicalReviewService::class);
        foreach (['submission', 'conversation'] as $purpose) {
            $this->fake($answer);
            $review = $service->evaluate($this->request($this->data()), 'nutricionales', $case, $purpose, $purpose === 'conversation' ? 17 : null);
            $response = $service->response($review, $this->user);
            $this->assertCount(1, $response['result']['findings']);
            $this->assertSame('i_4_g/Kg', $response['result']['findings'][0]['field']);
            $this->assertSame($answer['findings'][2]['suggestion'], $response['result']['findings'][0]['suggestion']);
            $this->assertStringNotContainsString('COMENTARIO_', json_encode($response));
            $this->assertStringNotContainsString('CONSEJO_', json_encode($response));
            $this->assertSame('blocked', $response['result']['status']);
            $this->assertFalse($response['can_submit']);
            $this->assertFalse($response['requires_medical_authorization']);
            $this->assertCount(3, $review->result['audit_findings']);
            $this->assertSame('COMENTARIO_GENERAL_RESUMEN', $review->result['audit_summary']);
        }
    }

    public function test_corrections_for_components_or_mixtures_outside_the_request_are_not_accepted(): void
    {
        foreach (['i_999_g/Kg', 'mezclas.9.medicamentos.0.dosis'] as $field) {
            $answer = $this->answer('blocked');
            $answer['findings'] = [['field' => $field, 'severity' => 'blocking', 'category' => 'safety',
                'message' => 'OTRA_MEZCLA_NO_CAPTURADA', 'calculation' => '', 'suggestion' => 'AJUSTE_AJENO', 'source_ids' => ['S2']]];
            $this->fake($answer); $review = $this->review();
            $response = app(ClinicalReviewService::class)->response($review, $this->user);
            $this->assertCount(1, $response['result']['findings']);
            $this->assertSame('review_limitation', $response['result']['findings'][0]['category']);
            $this->assertSame('needs_review', $response['result']['status']);
            $this->assertFalse($response['can_submit']);
            $this->assertNull($review->result['model']);
            $this->assertStringNotContainsString('OTRA_MEZCLA', json_encode($response));
            $this->assertStringNotContainsString('AJUSTE_AJENO', json_encode($response));
        }
    }

    public function test_hiding_non_parameter_comments_never_clears_a_block_or_incomplete_review(): void
    {
        $service = app(ClinicalReviewService::class);
        foreach (['information' => 'no_blockers', 'review' => 'needs_review', 'blocking' => 'blocked'] as $severity => $status) {
            $answer = $this->answer();
            $answer['findings'] = [['field' => 'observaciones', 'severity' => $severity, 'category' => 'internal_comment',
                'message' => 'Nota interna de prueba.', 'calculation' => '', 'suggestion' => '', 'source_ids' => ['S2']]];
            $this->fake($answer); $review = $this->review();
            $response = $service->response($review, $this->user);
            if ($status === 'needs_review') {
                $this->assertCount(1, $response['result']['findings']);
                $this->assertSame('review_limitation', $response['result']['findings'][0]['category']);
            } else {
                $this->assertEmpty($response['result']['findings']);
            }
            $this->assertSame($status, $response['result']['status']);
            $this->assertSame($severity === 'information', $response['can_submit']);
            $this->assertFalse($response['requires_medical_authorization']);
            $this->assertCount($status === 'needs_review' ? 2 : 1, $review->result['audit_findings']);
        }
    }

    public function test_historical_general_comments_are_hidden_without_rewriting_review_or_losing_parameter_warning(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $this->fake($this->exceptionAnswer()); $review = $this->review();
        $original = $review->result;
        foreach (['information', 'internal_comment', 'missing_clinical_context'] as $category) {
            $original['findings'][] = ['field' => 'observaciones', 'severity' => 'information', 'category' => $category,
                'message' => 'COMENTARIO_HISTORICO_'.$category, 'calculation' => '', 'suggestion' => '', 'source_ids' => ['S2']];
        }
        $review->update(['result' => $original]);
        $response = app(ClinicalReviewService::class)->response($review, $this->user);
        $this->assertCount(1, $response['result']['findings']);
        $this->assertSame('advertencia', $response['result']['findings'][0]['observation_type']);
        $this->assertTrue($response['requires_medical_authorization']);
        $this->assertFalse($response['can_submit']);
        $this->assertStringNotContainsString('COMENTARIO_HISTORICO_', json_encode($response));
        $this->assertSame($original, $review->fresh()->result);
    }

    private function exceptionAnswer(): array
    {
        $answer = $this->answer('needs_review');
        $answer['findings'] = [['field' => 'i_4_g/Kg', 'severity' => 'authorization', 'category' => 'dose_recommendation',
            'message' => 'Desviacion sintetica, no criterio clinico.', 'calculation' => '', 'source_ids' => ['S2']]];
        return $answer;
    }

    public function test_warning_never_allows_direct_submission_even_when_model_says_no_blockers(): void
    {
        $answer = $this->exceptionAnswer();
        $answer['status'] = 'no_blockers';
        $answer['findings'][0]['severity'] = 'warning';
        $this->fake($answer);
        $unapproved = $this->review();
        $this->assertFalse($unapproved->can_submit);
        $this->assertFalse($unapproved->result['requires_medical_authorization']);
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $this->fake($answer);
        $review = $this->review();
        $this->assertFalse($review->can_submit);
        $this->assertTrue($review->result['requires_medical_authorization']);
        $this->assertSame('authorization_required', $review->result['status']);
        $this->assertSame('advertencia', $review->result['findings'][0]['observation_type']);
        $this->assertStringContainsString('autorizacion del area medica', $review->result['summary']);
        Http::assertSent(fn ($request) => str_contains($request['instructions'], ClinicalEvidence::OBSERVATION_POLICY)
            && str_contains($request['instructions'], 'Toda ADVERTENCIA impide el envio sin autorizacion medica registrada.'));
        $this->expectException(ValidationException::class);
        app(ClinicalReviewService::class)->requireSubmission($this->request($this->data() + [
            'clinical_review_token' => $review->id, 'clinical_acknowledged' => '1',
        ]), 'nutricionales');
    }

    public function test_chemical_warning_requires_its_own_reviewed_permission_and_not_an_existing_dose_permission(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $answer = $this->exceptionAnswer();
        $answer['findings'][0]['severity'] = 'warning';
        $answer['findings'][0]['category'] = 'chemical_recommendation';
        $answer['findings'][0]['field'] = 'volumen_total';
        $this->fake($answer);
        $this->assertFalse($this->review()->result['requires_medical_authorization']);
        ClinicalSource::whereKey(2)->update(['allows_chemical_medical_authorization' => true]);
        $review = $this->review();
        $this->assertTrue($review->result['requires_medical_authorization']);
        $this->assertFalse($review->can_submit);
        $this->assertFalse($this->review(array_replace($this->data(), ['volumen_total' => '1']))->result['requires_medical_authorization']);
        foreach (['field' => 'observaciones', 'category' => 'safety', 'source_ids' => ['S1']] as $key => $value) {
            $changed = $answer; $changed['findings'][0][$key] = $value;
            $this->fake($changed);
            $this->assertFalse($this->review()->result['requires_medical_authorization']);
        }
    }

    public function test_rejection_wins_over_warnings_and_cannot_be_overridden_by_a_medical_authorization(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $answer = $this->exceptionAnswer();
        $answer['status'] = 'no_blockers';
        $answer['findings'][] = ['field' => 'volumen_total', 'severity' => 'blocking', 'category' => 'safety',
            'message' => 'Rechazo sintetico: corregir el parametro.', 'calculation' => '', 'source_ids' => ['S2']];
        $this->fake($answer); $review = $this->review();
        $this->assertSame('blocked', $review->result['status']);
        $this->assertFalse($review->can_submit);
        $this->assertFalse($review->result['requires_medical_authorization']);
        $this->assertSame('rechazo', $review->result['findings'][1]['observation_type']);
        $this->assertSame('volumen_total', $review->result['findings'][1]['field']);
        $this->expectException(ValidationException::class);
        app(ClinicalReviewService::class)->requireSubmission($this->request($this->data() + [
            'clinical_review_token' => $review->id, 'clinical_acknowledged' => '1', 'medical_authorization' => $this->medicalAuthorization(),
        ]), 'nutricionales');
    }

    public function test_blocked_response_is_not_downgraded_by_later_warning_or_unreviewed_finding(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        foreach (['S2', 'S1'] as $sourceId) {
            $answer = $this->exceptionAnswer(); $answer['status'] = 'blocked';
            $answer['findings'][0]['source_ids'] = [$sourceId];
            $this->fake($answer); $review = $this->review();
            $this->assertSame('blocked', $review->result['status']);
            $this->assertFalse($review->result['requires_medical_authorization']);
            $this->assertFalse($review->can_submit);
        }
    }

    public function test_information_severity_cannot_silence_a_safety_or_recommendation_category(): void
    {
        $answer = $this->answer();
        $answer['findings'] = [['field' => 'observaciones', 'severity' => 'information', 'category' => 'information',
            'message' => 'Nota informativa sin desviaciones, de prueba.', 'calculation' => '', 'source_ids' => ['S2']]];
        $this->fake($answer); $this->assertTrue($this->review()->can_submit);
        foreach (['safety', 'dose_recommendation', 'chemical_recommendation'] as $category) {
            $answer['findings'][0]['category'] = $category;
            $this->fake($answer); $this->assertFalse($this->review()->can_submit);
        }
    }

    public function test_reviewed_non_blocking_suggestion_allows_submission_after_risk_acknowledgement(): void
    {
        $answer = $this->answer('needs_review');
        $answer['findings'] = [[
            'field' => 'i_4_g/Kg',
            'severity' => 'advisory',
            'category' => 'dose_recommendation',
            'message' => 'La fuente revisada presenta una alternativa opcional para este parámetro.',
            'calculation' => '',
            'suggestion' => 'Componente de prueba: considerar 45 g/Kg conforme a S2, sección de recomendaciones opcionales.',
            'source_ids' => ['S2'],
        ]];
        $this->fake($answer);
        $review = $this->review();
        $response = app(ClinicalReviewService::class)->response($review, $this->user);

        $this->assertTrue($review->can_submit);
        $this->assertSame('advisory', $review->result['status']);
        $this->assertSame('sugerencia', $review->result['findings'][0]['observation_type']);
        $this->assertTrue($response['requires_risk_acknowledgement']);
        $this->assertFalse($response['requires_medical_authorization']);

        $data = $this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1'];
        app(ClinicalReviewService::class)->requireSubmission($this->request($data), 'nutricionales');
        $this->assertTrue(true);
    }

    public function test_advisory_cannot_bypass_safety_or_unreviewed_evidence(): void
    {
        foreach ([['category' => 'safety', 'source_ids' => ['S2']], ['category' => 'dose_recommendation', 'source_ids' => ['S1']]] as $invalid) {
            $answer = $this->answer('needs_review');
            $answer['findings'] = [[
                'field' => 'i_4_g/Kg',
                'severity' => 'advisory',
                'category' => $invalid['category'],
                'message' => 'Hallazgo sintetico que no debe habilitar el envio.',
                'calculation' => '',
                'suggestion' => 'Ajuste sintetico de prueba.',
                'source_ids' => $invalid['source_ids'],
            ]];
            $this->fake($answer);
            $review = $this->review();

            $this->assertFalse($review->can_submit);
            $this->assertSame('needs_review', $review->result['status']);
            $this->assertSame('review', $review->result['findings'][0]['severity']);
            $this->assertFalse($review->result['requires_risk_acknowledgement']);
        }
    }

    public function test_policy_migration_updates_visible_instructions_without_resetting_profile_or_approving_sources(): void
    {
        $this->agent->update(['instructions' => 'Perfil personalizado de prueba.', 'is_active' => false]);
        $other = AiAgent::forceCreate(['name' => 'Otro agente', 'instructions' => 'Conservar este perfil.']);
        $migration = require database_path('migrations/2026_09_30_000002_update_clinical_observation_policy.php');
        $migration->up(); $migration->up();
        $agent = $this->agent->fresh();
        $this->assertSame('Perfil personalizado de prueba.'."\n\n".ClinicalEvidence::OBSERVATION_POLICY, $agent->instructions);
        $this->assertFalse($agent->is_active);
        $this->assertSame('Conservar este perfil.', $other->fresh()->instructions);
        $this->assertFalse(ClinicalSource::find(1)->isReviewed());
        $this->assertFalse(ClinicalSource::find(2)->allows_chemical_medical_authorization);
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $agent->id)
            ->assertSee('ADVERTENCIA:')->assertSee('RECHAZO:')->assertSee('Si coexisten advertencias y rechazos, prevalece el rechazo.');
        Http::assertNothingSent();
    }

    private function medicalAuthorization(): array
    {
        return ['doctor_name' => 'MEDICO FICTICIO PRIVADO', 'doctor_license' => 'CEDULA-PRUEBA'];
    }

    public function test_suggestion_policy_migration_preserves_custom_profile_activation_and_sources(): void
    {
        $this->agent->update(['instructions' => 'Perfil personalizado.', 'is_active' => false]);
        $other = AiAgent::forceCreate(['name' => 'Otro agente', 'instructions' => 'Perfil distinto.']);
        $migration = require database_path('migrations/2026_09_30_000004_add_clinical_correction_suggestions.php');
        $migration->up(); $migration->up();
        $this->assertSame('Perfil personalizado.'."\n\n".ClinicalEvidence::SUGGESTION_POLICY, $this->agent->fresh()->instructions);
        $this->assertFalse($this->agent->fresh()->is_active);
        $this->assertSame('Perfil distinto.', $other->fresh()->instructions);
        $this->assertFalse(ClinicalSource::find(1)->isReviewed());
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)->assertSee('Sugerencias para corregir parametros:');
        Http::assertNothingSent();
    }

    public function test_refined_suggestion_policy_replaces_old_guidance_and_keeps_custom_instructions(): void
    {
        $migration = require database_path('migrations/2026_09_30_000005_refine_clinical_suggestions.php');
        $previous = $migration::PREVIOUS_POLICY;
        $this->agent->update(['instructions' => 'Perfil personalizado.'."\n\n".$previous."\n\nRegla institucional propia.", 'is_active' => false]);
        $other = AiAgent::forceCreate(['name' => 'Otro agente', 'instructions' => $previous]);
        $migration->up(); $migration->up();
        $this->assertSame('Perfil personalizado.'."\n\n".ClinicalEvidence::SUGGESTION_POLICY."\n\nRegla institucional propia.", $this->agent->fresh()->instructions);
        $this->assertFalse($this->agent->fresh()->is_active);
        $this->assertSame($previous, $other->fresh()->instructions);
        $this->assertFalse(ClinicalSource::find(1)->isReviewed());
        $this->assertFalse(ClinicalSource::find(2)->allows_medical_authorization);
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertSee('Muestra unicamente cambios concretos')->assertDontSee('pide al profesional confirmar el parametro y la fuente faltante');
        Http::assertNothingSent();
    }

    public function test_parameter_scope_policy_is_visible_and_migration_preserves_custom_profile_and_sources(): void
    {
        $this->agent->update(['instructions' => 'Perfil personalizado.', 'is_active' => false]);
        $other = AiAgent::forceCreate(['name' => 'Otro agente', 'instructions' => 'Conservar perfil.']);
        $sources = ClinicalSource::all()->toArray();
        $before = app(ClinicalEvidence::class)->fingerprint('nutricionales');
        $migration = require database_path('migrations/2026_09_30_000006_scope_clinical_feedback_to_mixture.php');
        $migration->up(); $migration->up();
        $this->assertSame('Perfil personalizado.'."\n\n".ClinicalEvidence::PARAMETER_SCOPE_POLICY, $this->agent->fresh()->instructions);
        $this->assertFalse($this->agent->fresh()->is_active);
        $this->assertSame('Conservar perfil.', $other->fresh()->instructions);
        $this->assertSame($sources, ClinicalSource::all()->toArray());
        $this->assertNotSame($before, app(ClinicalEvidence::class)->fingerprint('nutricionales'));
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertSee('Alcance de las observaciones y sugerencias de validacion:')
            ->assertSee('comenta exclusivamente los parametros de esa solicitud');
        Http::assertNothingSent();
    }

    public function test_medical_exception_requires_reviewed_policy_and_cannot_override_hard_blockers(): void
    {
        $this->fake($this->exceptionAnswer());
        $this->assertFalse($this->review()->result['requires_medical_authorization']);
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $eligible = $this->review();
        $this->assertTrue($eligible->result['requires_medical_authorization'], json_encode($eligible->result));
        $this->assertFalse($this->review(array_replace($this->data(), ['volumen_total' => '1']))->result['requires_medical_authorization']);
        $withoutOptionalContext = $this->data(); unset($withoutOptionalContext['clinical_context']);
        $this->assertTrue($this->review($withoutOptionalContext)->result['requires_medical_authorization']);
        foreach (['category' => 'safety', 'field' => 'volumen_total', 'source_ids' => ['S1']] as $key => $value) {
            $answer = $this->exceptionAnswer(); $answer['findings'][0][$key] = $value;
            $this->fake($answer);
            $this->assertFalse($this->review()->result['requires_medical_authorization'], $key);
        }
        ClinicalSource::find(2)->update(['approved_at' => null]);
        $this->fake($this->exceptionAnswer());
        $this->assertFalse($this->review()->result['requires_medical_authorization']);
    }

    public function test_authorization_is_required_encrypted_audited_and_bound_to_unchanged_review(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $this->fake($this->exceptionAnswer()); $review = $this->review(); $service = app(ClinicalReviewService::class);
        $this->assertFalse($review->can_submit);
        $this->assertTrue($service->response($review, $this->user)['requires_medical_authorization'], json_encode($review->result));
        $data = $this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1'];
        foreach ([[], ['doctor_name' => 'Medico de prueba'], ['doctor_license' => 'CEDULA-PRUEBA'],
            array_replace($this->medicalAuthorization(), ['doctor_name' => 'AB']),
            array_replace($this->medicalAuthorization(), ['doctor_license' => ''])] as $authorization) {
            try { $service->requireSubmission($this->request($data + ['medical_authorization' => $authorization]), 'nutricionales'); $this->fail('Incomplete authorization accepted'); }
            catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $data['medical_authorization'] = $this->medicalAuthorization();
        $request = $this->request($data);
        $service->requireSubmission($request, 'nutricionales');
        $this->assertNull($review->fresh()->medical_authorization);
        DB::transaction(fn () => $service->consume($request, 'solicituds', 456));
        $this->assertSame($this->user->id, $review->fresh()->medical_authorization['recorded_by']);
        $this->assertSame(2, $review->fresh()->medical_authorization['capture_version']);
        foreach (['reference', 'reason', 'authorized_at', 'confirmed'] as $removed) {
            $this->assertArrayNotHasKey($removed, $review->fresh()->medical_authorization);
        }
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertDontSee('MEDICO FICTICIO PRIVADO')->assertDontSee('CEDULA-PRUEBA');
        $this->assertSame('MEDICO FICTICIO PRIVADO', $review->fresh()->medical_authorization['doctor_name']);
        $this->assertSame('CEDULA-PRUEBA', $review->fresh()->medical_authorization['doctor_license']);
        $this->assertSame(456, $review->fresh()->record_id);
        $this->assertStringNotContainsString('MEDICO FICTICIO', DB::table('clinical_reviews')->value('medical_authorization'));
        Http::assertSent(fn ($request) => !str_contains($request['input'], 'MEDICO FICTICIO'));

        $this->fake($this->exceptionAnswer()); $next = $this->review(); $data['clinical_review_token'] = $next->id;
        $data['peso'] = '51';
        $this->expectException(ValidationException::class);
        $service->requireSubmission($this->request($data), 'nutricionales');
    }

    public function test_removed_authorization_fields_and_client_audit_metadata_are_not_persisted(): void
    {
        ClinicalSource::whereKey(2)->update(['allows_medical_authorization' => true]);
        $this->fake($this->exceptionAnswer()); $review = $this->review();
        $request = $this->request($this->data() + ['clinical_review_token' => $review->id, 'clinical_acknowledged' => '1',
            'medical_authorization' => $this->medicalAuthorization() + [
                'reference' => 'OLD-FORM', 'reason' => 'Old explanation', 'authorized_at' => '2000-01-01', 'confirmed' => '0',
                'recorded_by' => 999999, 'recorded_at' => '2000-01-01', 'review_id' => 'forged', 'capture_version' => 99,
            ]]);
        $service = app(ClinicalReviewService::class);
        $service->requireSubmission($request, 'nutricionales');
        DB::transaction(fn () => $service->consume($request, 'solicituds', 789));
        $stored = $review->fresh()->medical_authorization;
        $this->assertSame($this->user->id, $stored['recorded_by']);
        $this->assertSame($review->id, $stored['review_id']);
        $this->assertSame(2, $stored['capture_version']);
        $this->assertNotSame('2000-01-01', $stored['recorded_at']);
        foreach (['reference', 'reason', 'authorized_at', 'confirmed'] as $removed) $this->assertArrayNotHasKey($removed, $stored);

        $review->update(['medical_authorization' => $stored + ['reference' => 'LEGACY-REF', 'reason' => 'LEGACY-REASON']]);
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $this->agent->id)
            ->assertDontSee('LEGACY-REF')->assertDontSee('LEGACY-REASON');
        $this->assertSame('LEGACY-REF', $review->fresh()->medical_authorization['reference']);
        $this->assertSame('LEGACY-REASON', $review->fresh()->medical_authorization['reason']);
    }

    public function test_capture_migration_only_updates_the_obsolete_instruction_and_preserves_agent_settings(): void
    {
        $previous = 'No basta aceptar el aviso: antes del envio el usuario debe registrar nombre del medico, cedula profesional, justificacion, fecha y referencia de la autorizacion.';
        $this->agent->update(['instructions' => 'Perfil personalizado. '.$previous, 'is_active' => false]);
        $other = AiAgent::forceCreate(['name' => 'Otro agente', 'instructions' => $previous]);
        $migration = require database_path('migrations/2026_09_30_000003_simplify_clinical_authorization_capture.php');
        $migration->up(); $first = $this->agent->fresh()->instructions; $migration->up();
        $this->assertSame($first, $this->agent->fresh()->instructions);
        $this->assertStringStartsWith('Perfil personalizado. ', $first);
        $this->assertStringNotContainsString($previous, $first);
        $this->assertStringContainsString('registrar unicamente el nombre del medico que autoriza y su cedula profesional', $first);
        $this->assertStringContainsString(substr($first, strlen('Perfil personalizado. ')), ClinicalEvidence::OBSERVATION_POLICY);
        $this->assertFalse($this->agent->fresh()->is_active);
        $this->assertSame($previous, $other->fresh()->instructions);
        $this->assertFalse(ClinicalSource::find(1)->isReviewed());
    }
}
