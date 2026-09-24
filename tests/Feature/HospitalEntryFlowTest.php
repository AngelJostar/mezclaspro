<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenuAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class HospitalEntryFlowTest extends TestCase
{
    private User $hospital;
    private bool $previousBackButtonCache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousBackButtonCache = \Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache::$disableBackButtonCache;
        $this->hospital = UnifiedRequestExportData::seed();
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('training_username')->nullable();
            $table->string('training_password')->nullable();
            $table->string('remember_token')->nullable();
        });
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        $this->hospital->refresh()->forceFill(['password' => Hash::make('hospital-test-password')])->save();
        Auth::logout();
    }

    protected function tearDown(): void
    {
        \Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache::$disableBackButtonCache = $this->previousBackButtonCache;
        parent::tearDown();
    }

    public function test_hospital_login_opens_own_requests_instead_of_an_intended_internal_page(): void
    {
        $this->withSession(['url.intended' => route('admin.capacitaciones.personal')])
            ->post('/login', ['username' => $this->hospital->username, 'password' => 'hospital-test-password'])
            ->assertRedirect(route('admin.solicitudes.index'))
            ->assertSessionMissing('url.intended');
        $this->assertAuthenticatedAs($this->hospital);

        $this->get(route('admin.solicitudes.index'))->assertOk()
            ->assertSee('Lista de Solicitudes')->assertSee('Preparacion')->assertSee('Cotizacion')
            ->assertSee('Hospital de prueba')->assertDontSee('Hospital ajeno')
            ->assertDontSeeText('Personal y Capacitaciones')
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 5
                && $rows->every(fn ($row) => (int) $row['hospital_id'] === 1));
    }

    public function test_both_hospital_roles_redirect_existing_dashboard_sessions(): void
    {
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->hospital->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $this->actingAs($this->hospital)->get(route('admin.dashboard'))
                ->assertRedirect(route('admin.solicitudes.index'));
        }
    }

    public function test_hospital_cannot_access_internal_personnel_even_with_a_legacy_permission(): void
    {
        $this->hospital->givePermissionTo(Permission::firstOrCreate(['name' => 'usuarios', 'guard_name' => 'web']));
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->hospital->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            foreach (['menu.capacitaciones', 'menu.capacitaciones.personal', 'menu.capacitaciones.programas', 'menu.capacitaciones.alumnos'] as $menu) {
                $this->assertFalse(AdminMenuAccess::allows($this->hospital, $menu));
            }
            foreach (['admin.capacitaciones.index', 'admin.capacitaciones.programas', 'admin.capacitaciones.alumnos',
                'admin.capacitaciones.personal', 'admin.users.index'] as $route) {
                $this->actingAs($this->hospital)->get(route($route))->assertForbidden();
            }
            $this->post(route('admin.capacitaciones.personal.store'), [])->assertForbidden();
            $this->patch(route('admin.capacitaciones.personal.update', 1), [])->assertForbidden();
        }
    }

    public function test_inactive_hospital_still_cannot_log_in(): void
    {
        $this->hospital->update(['is_active' => false]);
        $this->post('/login', ['username' => $this->hospital->username, 'password' => 'hospital-test-password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_hospital_cannot_use_internal_training_credentials(): void
    {
        $this->hospital->update(['training_username' => 'hospital-training', 'training_password' => Hash::make('training-password')]);
        $this->post('/login', ['username' => 'hospital-training', 'password' => 'training-password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_internal_users_keep_their_login_destination_and_training_menu(): void
    {
        $this->hospital->syncRoles(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $this->withSession(['url.intended' => route('admin.capacitaciones.personal')])
            ->post('/login', ['username' => $this->hospital->username, 'password' => 'hospital-test-password'])
            ->assertRedirect(route('admin.capacitaciones.personal'));
        $this->assertTrue(AdminMenuAccess::allows($this->hospital, 'menu.capacitaciones'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Bienvenido al Sistema de Mezclas');
    }

    public function test_two_factor_redirect_and_json_contracts_are_preserved(): void
    {
        $request = Request::create('/two-factor-challenge', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $this->hospital);
        $request->session()->put('url.intended', route('admin.capacitaciones.personal'));
        $response = app(TwoFactorLoginResponse::class)->toResponse($request);
        $this->assertSame(route('admin.solicitudes.index'), $response->getTargetUrl());
        $this->assertFalse($request->session()->has('url.intended'));
        $jsonRequest = Request::create('/login', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $jsonRequest->setUserResolver(fn () => $this->hospital);
        $this->assertSame(204, app(TwoFactorLoginResponse::class)->toResponse($jsonRequest)->getStatusCode());
        $this->assertSame(['two_factor' => false], app(LoginResponse::class)->toResponse($jsonRequest)->getData(true));
    }

    public function test_training_and_billing_staff_keep_their_existing_entry_flow(): void
    {
        foreach (['Capacitacion' => 'admin.capacitaciones.index', 'Administracion y facturacion' => 'admin.instituciones.billing.index'] as $role => $route) {
            $this->hospital->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $this->actingAs($this->hospital)->get(route('admin.dashboard'))->assertRedirect(route($route));
        }
    }
}
