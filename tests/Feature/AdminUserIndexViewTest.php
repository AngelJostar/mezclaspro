<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserIndexViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_users_without_an_assigned_hospital(): void
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::query()->create([
            'name' => 'Administrador',
            'lastname' => 'Local',
            'username' => 'admin.without.hospital',
            'password' => Hash::make('secret'),
            'is_active' => true,
            'hospital_id' => null,
        ]);
        $admin->assignRole($role);

        $users = User::query()->with(['roles:name', 'hospital:id,name'])->paginate(10);

        $this->actingAs($admin)
            ->view('admin.users.index', compact('users'))
            ->assertSee('admin.without.hospital')
            ->assertSee('Sin hospital asignado');
    }
}
