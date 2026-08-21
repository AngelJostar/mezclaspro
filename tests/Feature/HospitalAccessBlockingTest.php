<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HospitalAccessBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hospital_block_prevents_its_users_from_logging_in_and_can_be_reactivated(): void
    {
        $manager = $this->createUserManager();
        [$hospital, $hospitalUser] = $this->createHospitalUser();

        $this->actingAs($manager)
            ->patchJson(route('admin.users.hospitals.status.update', $hospital), [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($hospital->fresh()->access_is_active);
        $this->assertTrue($hospitalUser->fresh()->is_active);
        $this->assertTrue($hospitalUser->fresh()->isBlockedByHospital());

        $this->post('/logout');

        $this->from('/login')
            ->post('/login', [
                'username' => 'hospital-bloqueado',
                'password' => 'clave-hospital',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'username' => User::INSTITUTION_BLOCKED_MESSAGE,
            ]);

        $this->assertGuest();

        $this->actingAs($manager)
            ->patchJson(route('admin.users.hospitals.status.update', $hospital), [
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->post('/logout');

        $this->post('/login', [
            'username' => 'hospital-bloqueado',
            'password' => 'clave-hospital',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($hospitalUser);
    }

    public function test_active_session_is_closed_when_its_hospital_is_blocked(): void
    {
        [$hospital, $hospitalUser] = $this->createHospitalUser();
        $hospital->update(['access_is_active' => false]);

        $this->actingAs($hospitalUser)
            ->get('/admin/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => User::INSTITUTION_BLOCKED_MESSAGE,
            ]);

        $this->assertGuest();
    }

    private function createUserManager(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'usuarios',
            'guard_name' => 'web',
        ]);
        $manager = User::factory()->create([
            'username' => 'gestor-hospitales',
            'hospital_id' => null,
            'is_active' => true,
        ]);
        $manager->givePermissionTo($permission);

        return $manager;
    }

    private function createHospitalUser(): array
    {
        $institution = Institucion::create([
            'nombre' => 'Institucion con hospital',
            'razon_social' => 'Institucion con hospital, S.A.',
            'is_active' => true,
        ]);
        $hospital = Hospital::factory()->create([
            'is_active' => true,
            'access_is_active' => true,
        ]);
        $institution->hospitals()->attach($hospital);

        $role = Role::firstOrCreate([
            'name' => 'Institucion',
            'guard_name' => 'web',
        ]);
        $hospitalUser = User::factory()->create([
            'username' => 'hospital-bloqueado',
            'password' => Hash::make('clave-hospital'),
            'credential_password' => 'clave-hospital',
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);
        $hospitalUser->assignRole($role);

        return [$hospital, $hospitalUser];
    }
}
