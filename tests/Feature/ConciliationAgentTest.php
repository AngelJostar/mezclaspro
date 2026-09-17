<?php

namespace Tests\Feature;

use App\Livewire\Admin\AgentCenter;
use App\Models\AiAgent;
use App\Models\AiAgentRun;
use App\Models\HospitalConciliationSubmission;
use App\Services\Agents\AgentRunner;
use Database\Seeders\ConciliationAgentSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\AgentAuditData;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class ConciliationAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AgentFixture::seed();
        \Illuminate\Support\Facades\Schema::table('users', fn ($table) => $table->boolean('is_active')->default(true));
        $this->actingAs(auth()->user()->fresh());
        AgentAuditData::seed();
        $this->seed(ConciliationAgentSeeder::class);
        Http::preventStrayRequests();
        foreach ([1, 2] as $hospital) {
            HospitalConciliationSubmission::create([
                'submission_key' => (string) Str::uuid(), 'hospital_id' => $hospital, 'submitted_by' => auth()->id(),
                'hospital_name' => 'Hospital '.$hospital, 'sender_name' => 'Remitente '.$hospital,
                'mixture_count' => 1, 'conciliable_count' => 0, 'period_type' => 'dia', 'filters' => [],
                'snapshot' => [['kind' => 'oncologicos', 'id' => 10, 'conciliable' => false, 'amount_cents' => null, 'reason' => '',
                    'cells' => ['id' => 10, 'request_id' => 20, 'institution' => 'Institución', 'hospital' => 'Hospital', 'patient' => 'PACIENTE PRIVADO', 'date' => '2026-09-01', 'delivery_date' => '', 'approval' => 'Aprobada', 'status' => 'Aprobada']]],
            ]);
        }
    }

    private function url(array $filters = []): string { return route('admin.instituciones.conciliaciones.agent', $filters); }

    public function test_registered_agent_is_shared_with_center_and_seeding_preserves_configuration(): void
    {
        $agent = AiAgent::sole();
        $this->assertTrue($agent->is_active);
        $this->assertSame(['conciliation'], $agent->configuration['rules']);
        $agent->update(['name' => 'Conciliación personalizada', 'is_active' => false]);
        $this->seed(ConciliationAgentSeeder::class);
        $this->assertSame(1, AiAgent::count());
        $this->assertFalse($agent->fresh()->is_active);
        $response = $this->getJson($this->url(['hospital_id' => 1]))->assertOk();
        $this->assertStringContainsString('Conciliación personalizada', $response->json('html'));
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) $agent->id)->assertSee('Administración · Conciliación');
    }

    public function test_run_honors_inbox_filters_is_read_only_and_records_results_in_center(): void
    {
        $before = HospitalConciliationSubmission::all()->toJson();
        $result = $this->postJson($this->url(), ['filters' => ['institucion_id' => 1, 'hospital_id' => 1, 'search' => 'Remitente 1'], 'instructions' => '<script>test</script>'])->assertOk();
        $run = AiAgentRun::sole();
        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->result['coverage']['conciliations']);
        $this->assertCount(4, $run->result['findings']);
        $this->assertSame(1, $run->result['findings'][0]['record']['hospital_id']);
        $this->assertStringNotContainsString('PACIENTE PRIVADO', json_encode($run->result));
        $this->assertStringContainsString('Se revisaron 1 conciliaciones', $result->json('html'));
        $this->assertStringNotContainsString('<script>', $result->json('html'));
        $this->assertSame($before, HospitalConciliationSubmission::all()->toJson());
        Livewire::test(AgentCenter::class)->call('selectAgent', (string) AiAgent::sole()->id)->assertSee('Resumen de conciliación')->assertSee('Se revisaron 1 conciliaciones');
        Http::assertNothingSent();
    }

    public function test_configured_scope_is_intersected_with_filters_and_center_uses_same_engine(): void
    {
        $agent = AiAgent::sole();
        $agent->configuration = array_replace($agent->configuration, ['scope_all' => false, 'institutions' => [1]]);
        $agent->save();
        $this->postJson($this->url(), ['filters' => ['hospital_id' => 2]])->assertOk();
        $this->assertSame(0, AiAgentRun::sole()->result['coverage']['conciliations']);
        $run = app(AgentRunner::class)->run($agent, auth()->user());
        $this->assertSame(1, $run->result['coverage']['conciliations']);
    }

    public function test_literal_zero_search_does_not_widen_the_scope_and_openai_receives_additional_instructions(): void
    {
        $this->postJson($this->url(), ['filters' => ['search' => '0']])->assertOk();
        $this->assertSame(0, AiAgentRun::sole()->result['coverage']['conciliations']);
        $agent = AiAgent::sole();
        $agent->configuration = array_replace($agent->configuration, ['analysis' => 'openai']); $agent->save();
        $this->mock(\App\Services\Agents\OpenAiAgentAnalysis::class)->shouldReceive('analyze')->once()
            ->withArgs(fn ($profile, $findings, $issues, $coverage) => str_contains($profile->instructions, 'Destacar importes') && $coverage['conciliations'] === 1)
            ->andReturn(['summary' => 'Resumen de prueba', 'recommendations' => [], 'model' => 'mock', 'analyzed_findings' => 0, 'total_findings' => 4]);
        $this->postJson($this->url(), ['filters' => ['hospital_id' => 1], 'instructions' => 'Destacar importes'])->assertOk();
        $this->assertStringNotContainsString('Destacar importes', $agent->fresh()->instructions);
        Http::assertNothingSent();
    }

    public function test_authorization_inactive_validation_and_tampered_configuration(): void
    {
        $agent = AiAgent::sole();
        $agent->update(['is_active' => false]);
        $this->postJson($this->url(), ['filters' => []])->assertUnprocessable();
        $agent->update(['is_active' => true]);
        $this->postJson($this->url(), ['filters' => ['hospital_id' => 999]])->assertUnprocessable();
        $this->postJson($this->url(), ['filters' => [], 'instructions' => str_repeat('a', 2001)])->assertUnprocessable();
        $user = auth()->user();
        $user->syncRoles(Role::findOrCreate('Institucion', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('menu.administracion.reports', 'web'));
        $this->actingAs($user->fresh())->getJson($this->url())->assertForbidden();
        $this->postJson($this->url(), ['filters' => []])->assertForbidden();
        $user->syncRoles(Role::findOrCreate('Administracion y facturacion', 'web'));
        $this->actingAs($user->fresh())->postJson($this->url(), ['filters' => []])->assertOk();
        $agent->configuration = array_replace($agent->configuration, ['rules' => ['summary'], 'sources' => ['findings']]); $agent->save();
        $this->postJson($this->url(), ['filters' => []])->assertUnprocessable();
        $this->assertSame(1, AiAgentRun::count());
    }
}
