<?php

namespace Tests\Feature;

use App\Livewire\Admin\AgentCenter;
use App\Models\AiAgent;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Services\Agents\AgentAccess;
use App\Services\Agents\AgentConfiguration;
use App\Services\Agents\AgentData;
use App\Services\Agents\AgentRunner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\AgentAuditData;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class AgentAccessTest extends TestCase
{
    private User $super;
    private AiAgent $agent;
    private AgentAccess $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = AgentFixture::seed();
        AgentAuditData::seed();
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('institution_id')->nullable();
        });
        Schema::table('clientes', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        Schema::table('hospitals', fn (Blueprint $t) => $t->boolean('access_is_active')->default(true));
        Schema::create('training_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
        });
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        $this->super->refresh();
        $this->access = app(AgentAccess::class);
        $this->agent = AiAgent::forceCreate(['name' => 'Distribución y entregas', 'description' => 'Revisión de entregas', 'instructions' => 'Revisar evidencia.', 'is_active' => true]);
        $this->agent->configuration = array_replace(AgentConfiguration::defaults($this->agent), ['scope_all' => true, 'objective' => 'Revisar entregas']);
        $this->agent->save();
        Http::preventStrayRequests();
        $this->mock(\App\Services\InstitutionBillingPendingSummaryService::class, fn ($mock) => $mock->shouldReceive('counts')->andReturn(['yellow' => 0, 'red' => 0]));
    }

    private function user(string $role = 'Admin', array $extra = []): User
    {
        $user = User::forceCreate($extra + ['name' => 'Ana García', 'username' => 'ana'.User::count(), 'is_active' => true]);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        return $user;
    }

    private function grant(string $type, string|int $key, array $permissions = []): void
    {
        $this->agent->accessGrants()->updateOrCreate(['subject_type' => $type, 'subject_key' => (string) $key],
            $permissions + ['can_view' => true, 'can_run' => false, 'can_edit' => false, 'granted_by' => $this->super->id]);
    }

    public function test_role_and_position_permissions_combine_and_individual_permissions_override_them(): void
    {
        $user = $this->user();
        $profile = PersonnelProfile::create(['user_id' => $user->id, 'positions' => ['Responsable sanitario']]);
        $this->grant('role', $user->roles->first()->id, ['can_run' => true]);
        $this->grant('position', 'Responsable sanitario', ['can_edit' => true]);
        $this->assertSame(['can_view' => true, 'can_run' => true, 'can_edit' => true], $this->access->permissions($user, $this->agent));
        $this->grant('user', $user->id);
        $this->assertSame(['can_view' => true, 'can_run' => false, 'can_edit' => false], $this->access->permissions($user, $this->agent));
        $this->grant('user', $user->id, ['can_view' => false]);
        $this->assertSame(AgentAccess::NONE, $this->access->permissions($user, $this->agent));
        $this->agent->accessGrants()->where('subject_type', 'user')->delete();
        $user->syncRoles([]);
        $this->assertFalse($this->access->permissions($user, $this->agent)['can_run']);
        $profile->update(['positions' => []]);
        $this->assertSame(AgentAccess::NONE, $this->access->permissions($user, $this->agent));
    }

    public function test_super_administrator_can_save_cancel_and_revoke_grants_in_the_agent_dialog(): void
    {
        $user = $this->user();
        $component = Livewire::test(AgentCenter::class)->call('openAccess', $this->agent->id)->assertSee('Usuarios')->assertSee('Puestos');
        $index = collect($component->get('accessRows'))->search(fn ($row) => $row['subject_type'] === 'user' && $row['subject_key'] === (string) $user->id);
        $component->set("accessRows.$index.assigned", true)->set("accessRows.$index.can_view", true)->set("accessRows.$index.can_edit", true)
            ->call('saveAccess')->assertHasNoErrors()->assertSet('accessAgentId', null);
        $this->assertTrue($this->access->permissions($user, $this->agent)['can_edit']);
        $this->assertSame([$user->id], $this->access->effectiveUsers($this->agent)->pluck('id')->all());
        $component->call('openAccess', $this->agent->id)->set("accessRows.$index.assigned", false)->call('closeAccess');
        $this->assertTrue($this->access->permissions($user, $this->agent)['can_edit']);
        $component->call('openAccess', $this->agent->id)->set("accessRows.$index.assigned", false)->call('saveAccess')->assertHasNoErrors();
        $this->assertSame(AgentAccess::NONE, $this->access->permissions($user, $this->agent));
    }

    public function test_grant_save_checks_actor_targets_dependencies_and_concurrent_updates(): void
    {
        $user = $this->user();
        $component = Livewire::test(AgentCenter::class)->call('openAccess', $this->agent->id);
        $index = collect($component->get('accessRows'))->search(fn ($row) => $row['subject_type'] === 'user');
        $component->set("accessRows.$index.assigned", true)->set("accessRows.$index.can_run", true)->call('saveAccess')->assertHasErrors('accessRows');
        $this->grant('user', $user->id);
        $component->set("accessRows.$index.can_view", true)->call('saveAccess')->assertHasErrors('accessRows');
        $component->call('openAccess', $this->agent->id)->set("accessRows.$index.subject_key", '999999')->call('saveAccess')->assertHasErrors('accessRows');
        $this->actingAs($user);
        $component->call('saveAccess')->assertForbidden();
        Livewire::test(AgentCenter::class)->assertForbidden();
    }

    public function test_delegated_actions_are_independent_and_cannot_escalate_other_agent_permissions(): void
    {
        $user = $this->user();
        $other = AiAgent::create(['name' => 'Agente reservado']);
        $this->actingAs($user);
        $this->get(route('admin.agents.index'))->assertOk()->assertSee('No tienes agentes autorizados');
        $this->get(route('admin.agents.show', $this->agent))->assertForbidden();
        $this->grant('user', $user->id);
        $this->get(route('admin.agents.index'))->assertOk()->assertSee('Revisión de entregas')->assertDontSee('Agente reservado')->assertDontSee('Editar agente')->assertDontSee('Ejecutar ahora');
        $this->get(route('admin.agents.show', $other))->assertForbidden();
        $this->put(route('admin.agents.update', $this->agent), ['name' => 'Cambio'])->assertForbidden();
        $this->post(route('admin.agents.run', $this->agent))->assertForbidden();
        $this->grant('user', $user->id, ['can_edit' => true]);
        $this->put(route('admin.agents.update', $this->agent), ['name' => 'Agente actualizado', 'instructions' => 'Nuevas instrucciones', 'is_active' => false,
            'configuration' => ['scope_all' => false], 'accessGrants' => [['can_run' => true]]])->assertRedirect();
        $this->assertSame('Nuevas instrucciones', $this->agent->fresh()->instructions);
        $this->assertTrue($this->agent->fresh()->is_active);
        $this->assertTrue($this->agent->fresh()->configuration['scope_all']);
        $this->post(route('admin.agents.run', $this->agent))->assertForbidden();
        $this->grant('user', $user->id, ['can_run' => true]);
        $this->post(route('admin.agents.run', $this->agent))->assertRedirect();
        $this->assertSame($user->id, $this->agent->runs()->first()->user_id);
        $this->put(route('admin.agents.update', $this->agent), ['name' => 'Cambio'])->assertForbidden();
        $this->agent->accessGrants()->delete();
        $this->post(route('admin.agents.run', $this->agent))->assertForbidden();
    }

    public function test_every_non_super_role_gets_the_bottom_menu_and_only_assigned_agents(): void
    {
        foreach (['Admin', 'Responsable sanitario', 'Capacitacion', \App\Support\AdminMenuAccess::GENERAL_ROLE, 'Administracion y facturacion', 'Cliente', 'Institucion', 'Ventas'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user);
            $html = view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 0])->render();
            $this->assertStringContainsString('aria-label="Centro de agentes"', $html, $role);
            $this->assertStringNotContainsString('aria-label="Superadministrador"', $html, $role);
            $this->assertStringContainsString('Centro de agentes', substr($html, strrpos($html, '<li ')));
            $this->grant('user', $user->id);
            $this->get(route('admin.agents.show', $this->agent))->assertOk();
            $this->get(route('admin.superadministrator.index'))->assertStatus(in_array($role, ['Capacitacion', 'Administracion y facturacion', 'Ventas']) ? 302 : 403);
        }
    }

    public function test_hospital_execution_and_results_are_scoped_and_do_not_change_global_scheduler(): void
    {
        $user = $this->user('Cliente', ['hospital_id' => 1]);
        $this->grant('user', $user->id, ['can_run' => true]);
        foreach ([1, 2] as $hospitalId) DB::table('distribution_delivery_schedules')->insert(['hospital_id' => $hospitalId, 'warehouse_id' => $hospitalId, 'scheduled_date' => now()->subDays(2)->toDateString(), 'status' => 'pending']);
        $this->agent->source_fingerprint = 'global-fingerprint';
        $this->agent->next_run_at = now()->addHour();
        $this->agent->save();
        $nextRun = $this->agent->next_run_at->toDateTimeString();
        $run = app(AgentRunner::class)->run($this->agent, $user);
        $this->assertSame('completed', $run->status);
        $this->assertSame([1], array_column(array_column($run->result['findings'], 'record'), 'hospital_id'));
        $this->assertSame('global-fingerprint', $this->agent->fresh()->source_fingerprint);
        $this->assertSame($nextRun, $this->agent->fresh()->next_run_at->toDateTimeString());
        $this->agent->findings()->create(['ai_agent_run_id' => $run->id, 'owner' => 'Distribución', 'fingerprint' => 'foreign', 'rule' => 'distribution', 'priority' => 'high', 'evidence' => ['title' => 'Hallazgo de otro hospital', 'record' => ['hospital_id' => 2]], 'last_seen_at' => now()]);
        $this->actingAs($user)->get(route('admin.agents.show', $this->agent))->assertOk()->assertDontSee('Hallazgo de otro hospital')->assertSee('Entrega programada sin confirmación');
        $scoped = app(AgentData::class)->read('batches', $this->agent->configuration + ['access_hospital_ids' => [1]]);
        $this->assertSame([], $scoped['rows']);
        $this->assertNotEmpty($scoped['issues']);
    }

    public function test_institution_delegated_actions_require_grants_and_leave_other_writes_blocked(): void
    {
        $user = $this->user('Institucion', ['institution_id' => 1]);
        $this->actingAs($user)->put(route('admin.agents.update', $this->agent), ['name' => 'Cambio'])->assertForbidden();
        $this->grant('user', $user->id, ['can_edit' => true, 'can_run' => true]);
        $this->put(route('admin.agents.update', $this->agent), ['name' => 'Revisión institucional'])->assertRedirect();
        $this->post(route('admin.agents.run', $this->agent))->assertRedirect();
        $this->assertSame([1], $this->agent->runs()->first()->configuration['access_hospital_ids']);
        $this->post(route('admin.solicitudes.cotizacion.store'), [])->assertForbidden();
    }
}
