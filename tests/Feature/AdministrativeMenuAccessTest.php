<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Services\Agents\AgentAccess;
use App\Support\AdminMenuAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\AgentCenter;
use Tests\TestCase;

class AdministrativeMenuAccessTest extends TestCase
{
    private const MENU_ROUTES = [
        'admin.solicitudes.index', 'admin.catalogo-listas.index',
        'admin.warehouses.purchase-orders.index', 'admin.suppliers.index',
        'admin.distribution.routes.index', 'admin.distribution.deliveries.index',
        'admin.warehouses.index', 'admin.instituciones.index', 'admin.hospitals.index',
        'admin.instituciones.reportes', 'admin.capacitaciones.personal',
        'admin.capacitaciones.programas', 'admin.capacitaciones.alumnos',
        'admin.maintenance-qualifications.index', 'admin.roles.index',
        'admin.permissions.index', 'admin.agents.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        AgentCenter::seed();
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
        });
    }

    private function user(array $roles, array $positions = []): User
    {
        $user = User::forceCreate(['name' => 'Personal de prueba', 'is_active' => true]);
        $user->syncRoles(array_map(fn ($role) => Role::findOrCreate($role, 'web'), $roles));
        PersonnelProfile::create(['user_id' => $user->id, 'positions' => $positions]);

        return $user;
    }

    private function administrativeUsers(): array
    {
        return [
            $this->user(['Admin']),
            $this->user(['Responsable sanitario']),
            $this->user([AdminMenuAccess::GENERAL_ROLE], ['Responsable sanitario', 'Control documental']),
            $this->user(['Admin', 'Capacitacion', 'Administracion y facturacion']),
            $this->user(['Capacitacion', 'Administracion y facturacion'], ['Responsable sanitario']),
        ];
    }

    private function sidebar(User $user): string
    {
        $this->actingAs($user);

        return view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 0])->render();
    }

    /** Keep the production route and its middleware; unrelated page data is outside this test. */
    private function stubPage(string $name): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, $name);
        $action = $route->getAction();
        unset($action['controller']);
        $action['uses'] = fn () => response('Sección disponible');
        $route->setAction($action);
    }

    public function test_administrators_and_current_health_officers_see_all_menus_except_super_administrator(): void
    {
        foreach ($this->administrativeUsers() as $user) {
            $html = $this->sidebar($user);
            foreach (self::MENU_ROUTES as $route) {
                // Purchase links may include their selected section.
                $this->assertStringContainsString('href="'.route($route), $html, $route);
            }
            $this->assertStringNotContainsString('aria-label="Superadministrador"', $html);
            $this->assertStringNotContainsString(route('admin.superadministrator.index'), $html);
            $this->assertStringContainsString('aria-label="Centro de agentes"', substr($html, strrpos($html, '<li ')));
        }
    }

    public function test_menu_routes_open_for_both_profiles_without_permitting_the_super_administrator_panel(): void
    {
        foreach ([...self::MENU_ROUTES, 'admin.superadministrator.index'] as $route) $this->stubPage($route);

        foreach ($this->administrativeUsers() as $user) {
            $this->actingAs($user);
            foreach (self::MENU_ROUTES as $route) {
                $this->get(route($route))->assertOk()->assertSee('Sección disponible');
            }
            $this->get(route('admin.superadministrator.index'))->assertForbidden();
            $this->assertFalse($user->hasRole('Super Admin'));
        }
    }

    public function test_navigation_does_not_grant_agent_actions_or_role_management_writes(): void
    {
        $this->stubPage('admin.roles.index');
        $this->stubPage('admin.roles.store');
        $this->stubPage('admin.permissions.store');
        $agent = AiAgent::create(['name' => 'Agente reservado']);

        foreach ($this->administrativeUsers() as $user) {
            $this->actingAs($user);
            $this->get(route('admin.roles.index'))->assertOk();
            $this->post(route('admin.roles.store'), ['name' => 'Super Admin'])->assertForbidden();
            $this->post(route('admin.permissions.store'), ['name' => 'roles'])->assertForbidden();
            $this->assertSame(AgentAccess::NONE, app(AgentAccess::class)->permissions($user, $agent));
            $this->assertCount(0, $user->permissions);
        }
    }

    public function test_former_health_officers_and_other_profiles_do_not_receive_administrative_navigation(): void
    {
        $users = [
            $this->user([AdminMenuAccess::GENERAL_ROLE], ['Control documental', 'Responsable sanitario']),
            $this->user(['Capacitacion'], ['Auxiliar de responsable sanitario']),
            $this->user(['Cliente'], ['Responsable sanitario']),
            $this->user(['Institucion'], ['Responsable sanitario']),
        ];
        $institutionUser = $this->user(['Admin']);
        $institutionUser->institution_id = 1;
        $users[] = $institutionUser;
        $inactiveUser = $this->user(['Admin']);
        $inactiveUser->is_active = false;
        $users[] = $inactiveUser;
        foreach ($users as $user) $this->assertFalse(AdminMenuAccess::hasFullNavigation($user));

        $html = $this->sidebar($users[0]);
        $this->assertStringNotContainsString(route('admin.roles.index'), $html);
        $this->assertStringNotContainsString(route('admin.maintenance-qualifications.index'), $html);
        $this->assertStringContainsString('aria-label="Centro de agentes"', $html);
    }

    public function test_super_administrator_keeps_its_exclusive_menu_and_training_credentials_remain_restricted(): void
    {
        $super = $this->user(['Super Admin']);
        $this->assertStringContainsString('aria-label="Superadministrador"', $this->sidebar($super));
        $this->stubPage('admin.superadministrator.index');
        $this->get(route('admin.superadministrator.index'))->assertOk();

        $this->actingAs($this->user(['Admin', 'Capacitacion']));
        $this->withSession(['access_context' => 'training'])->get(route('admin.roles.index'))
            ->assertRedirect(route('admin.capacitaciones.index'));
    }
}
