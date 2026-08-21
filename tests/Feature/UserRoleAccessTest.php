<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenuAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_superadministrator_can_assign_general_user_role_and_selected_menus(): void
    {
        $manager = $this->superadministrator();
        $target = User::factory()->create(['hospital_id' => null]);
        $target->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));

        Permission::firstOrCreate([
            'name' => 'oncologicos_laboratory_index',
            'guard_name' => 'web',
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.role-access.update', $target), [
                'role' => AdminMenuAccess::GENERAL_ROLE,
                'menu_permissions' => ['menu.compras.mine'],
            ])
            ->assertOk()
            ->assertJsonPath('role', AdminMenuAccess::GENERAL_ROLE)
            ->assertJsonPath('menu_permissions.0', 'menu.compras.mine')
            ->assertJsonPath('menu_permissions.1', 'menu.compras');

        $target->refresh();

        $this->assertTrue($target->hasRole(AdminMenuAccess::GENERAL_ROLE));
        $this->assertTrue($target->hasDirectPermission('menu.compras'));
        $this->assertTrue($target->hasDirectPermission('menu.compras.mine'));
        $this->assertTrue($target->hasDirectPermission('oncologicos_laboratory_index'));
        $this->assertFalse($target->can('menu.compras.paid'));
    }

    public function test_general_user_cannot_open_an_unselected_training_submenu(): void
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->assignRole(Role::firstOrCreate([
            'name' => AdminMenuAccess::GENERAL_ROLE,
            'guard_name' => 'web',
        ]));
        $user->givePermissionTo('menu.capacitaciones', 'menu.capacitaciones.personal');

        $this->actingAs($user)
            ->get(route('admin.capacitaciones.personal'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.capacitaciones.programas'))
            ->assertForbidden();
    }

    public function test_superadministrator_cannot_remove_their_own_role(): void
    {
        $manager = $this->superadministrator();

        $this->actingAs($manager)
            ->patchJson(route('admin.users.role-access.update', $manager), [
                'role' => 'Admin',
                'menu_permissions' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertTrue($manager->fresh()->hasRole('Super Admin'));
    }

    private function superadministrator(): User
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->assignRole(Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]));

        return $user;
    }
}
