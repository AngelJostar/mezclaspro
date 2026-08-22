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

class InstitutionAccessBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_block_prevents_hospital_login_and_can_be_reactivated(): void
    {
        $manager = $this->createUserManager();
        [$institution, $hospitalUser] = $this->createInstitutionHospitalUser();

        $this->actingAs($manager)
            ->patchJson(route('admin.users.institutions.status.update', $institution), [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($institution->fresh()->is_active);
        $this->assertTrue($hospitalUser->fresh()->is_active);
        $this->assertTrue($hospitalUser->fresh()->isBlockedByInstitution());

        $this->post('/logout');

        $this->from('/login')
            ->post('/login', [
                'username' => 'hospital-acceso',
                'password' => 'clave-hospital',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'username' => User::INSTITUTION_BLOCKED_MESSAGE,
            ]);

        $this->assertGuest();

        $this->actingAs($manager)
            ->patchJson(route('admin.users.institutions.status.update', $institution), [
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->post('/logout');

        $this->post('/login', [
            'username' => 'hospital-acceso',
            'password' => 'clave-hospital',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($hospitalUser);
    }

    public function test_active_session_is_closed_when_its_institution_is_blocked(): void
    {
        [$institution, $hospitalUser] = $this->createInstitutionHospitalUser();
        $institution->update(['is_active' => false]);

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
            'username' => 'gestor-instituciones',
            'hospital_id' => null,
            'is_active' => true,
        ]);
        $manager->givePermissionTo($permission);

        return $manager;
    }

    private function createInstitutionHospitalUser(): array
    {
        $institution = Institucion::create([
            'nombre' => 'Institucion de prueba',
            'razon_social' => 'Institucion de prueba, S.A.',
            'is_active' => true,
        ]);
        $hospital = Hospital::factory()->create(['is_active' => true]);
        $institution->hospitals()->attach($hospital);

        $role = Role::firstOrCreate([
            'name' => 'Institucion',
            'guard_name' => 'web',
        ]);
        $hospitalUser = User::factory()->create([
            'username' => 'hospital-acceso',
            'password' => Hash::make('clave-hospital'),
            'credential_password' => 'clave-hospital',
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);
        $hospitalUser->assignRole($role);

        return [$institution, $hospitalUser];
    }
}
