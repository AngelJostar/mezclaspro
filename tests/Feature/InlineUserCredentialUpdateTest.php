<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InlineUserCredentialUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_update_a_username_from_the_table(): void
    {
        $manager = $this->createUserManager('gestor-usuarios');
        $target = User::factory()->create([
            'lastname' => 'Destino',
            'username' => 'usuario-anterior',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.username.update', $target), [
                'username' => 'usuario-nuevo',
            ])
            ->assertOk()
            ->assertJsonPath('username', 'usuario-nuevo');

        $this->assertSame('usuario-nuevo', $target->fresh()->username);
    }

    public function test_software_username_is_saved_in_lowercase(): void
    {
        $manager = $this->createUserManager('gestor-minusculas-software');
        $target = User::factory()->create([
            'username' => 'usuario-inicial',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.username.update', $target), [
                'username' => 'Usuario.MEZCLAS_26',
            ])
            ->assertOk()
            ->assertJsonPath('username', 'usuario.mezclas_26');

        $this->assertSame('usuario.mezclas_26', $target->fresh()->username);
    }

    public function test_inline_username_update_rejects_duplicates(): void
    {
        $manager = $this->createUserManager('gestor-duplicados');
        $existing = User::factory()->create([
            'lastname' => 'Existente',
            'username' => 'usuario-ocupado',
            'hospital_id' => null,
        ]);
        $target = User::factory()->create([
            'lastname' => 'Destino',
            'username' => 'usuario-libre',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.username.update', $target), [
                'username' => $existing->username,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->assertSame('usuario-libre', $target->fresh()->username);
    }

    public function test_software_username_cannot_duplicate_a_training_username(): void
    {
        $manager = $this->createUserManager('gestor-duplicados-capacitacion');
        $existing = User::factory()->create([
            'username' => 'usuario-software-existente',
            'training_username' => 'usuario-capacitacion-ocupado',
            'hospital_id' => null,
        ]);
        $target = User::factory()->create([
            'username' => 'usuario-software-libre',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.username.update', $target), [
                'username' => $existing->training_username,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->assertSame('usuario-software-libre', $target->fresh()->username);
    }

    public function test_authorized_user_can_update_a_password_from_the_table(): void
    {
        $manager = $this->createUserManager('gestor-contrasenas');
        $target = User::factory()->create([
            'lastname' => 'Destino',
            'username' => 'usuario-clave',
            'hospital_id' => null,
            'credential_password' => 'clave-anterior',
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.password.update', $target), [
                'password' => 'clave-nueva-26',
            ])
            ->assertOk();

        $updatedUser = $target->fresh();

        $this->assertTrue(Hash::check('clave-nueva-26', $updatedUser->password));
        $this->assertSame('clave-nueva-26', $updatedUser->credential_password);
    }

    public function test_authorized_user_can_update_training_credentials_independently(): void
    {
        $manager = $this->createUserManager('gestor-capacitacion');
        $target = User::factory()->create([
            'lastname' => 'Destino',
            'username' => 'usuario-software',
            'credential_password' => 'software-anterior',
            'training_username' => 'cap-anterior',
            'training_credential_password' => 'capacitacion-anterior',
            'hospital_id' => null,
        ]);
        $softwarePasswordHash = $target->password;

        $this->actingAs($manager)
            ->patchJson(route('admin.users.training-username.update', $target), [
                'training_username' => 'cap-nuevo',
            ])
            ->assertOk()
            ->assertJsonPath('value', 'cap-nuevo');

        $this->actingAs($manager)
            ->patchJson(route('admin.users.training-password.update', $target), [
                'training_password' => 'clave-capacitacion-26',
            ])
            ->assertOk();

        $updatedUser = $target->fresh();

        $this->assertSame('usuario-software', $updatedUser->username);
        $this->assertSame($softwarePasswordHash, $updatedUser->password);
        $this->assertSame('software-anterior', $updatedUser->credential_password);
        $this->assertSame('cap-nuevo', $updatedUser->training_username);
        $this->assertTrue(Hash::check('clave-capacitacion-26', $updatedUser->training_password));
        $this->assertSame('clave-capacitacion-26', $updatedUser->training_credential_password);
    }

    public function test_training_username_is_saved_in_lowercase(): void
    {
        $manager = $this->createUserManager('gestor-minusculas-capacitacion');
        $target = User::factory()->create([
            'username' => 'usuario-software-minusculas',
            'training_username' => 'cap-inicial',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.training-username.update', $target), [
                'training_username' => 'Cap.Usuario_26',
            ])
            ->assertOk()
            ->assertJsonPath('value', 'cap.usuario_26');

        $this->assertSame('cap.usuario_26', $target->fresh()->training_username);
    }

    public function test_training_username_cannot_duplicate_a_software_username(): void
    {
        $manager = $this->createUserManager('gestor-validacion-capacitacion');
        $existing = User::factory()->create([
            'username' => 'usuario-software-ocupado',
            'hospital_id' => null,
        ]);
        $target = User::factory()->create([
            'username' => 'usuario-destino',
            'training_username' => 'cap-destino',
            'hospital_id' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.training-username.update', $target), [
                'training_username' => $existing->username,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('training_username');

        $this->assertSame('cap-destino', $target->fresh()->training_username);
    }

    public function test_authorized_user_can_block_and_reactivate_another_user(): void
    {
        $manager = $this->createUserManager('gestor-bloqueos');
        $target = User::factory()->create([
            'username' => 'usuario-bloqueable',
            'hospital_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.status.update', $target), ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($manager)
            ->patchJson(route('admin.users.status.update', $target), ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_manager_cannot_block_their_own_access(): void
    {
        $manager = $this->createUserManager('gestor-autobloqueo');

        $this->actingAs($manager)
            ->patchJson(route('admin.users.status.update', $manager), ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->assertTrue($manager->fresh()->is_active);
    }

    private function createUserManager(string $username): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'usuarios',
            'guard_name' => 'web',
        ]);
        $manager = User::factory()->create([
            'lastname' => 'Administrador',
            'username' => $username,
            'hospital_id' => null,
        ]);
        $manager->givePermissionTo($permission);

        return $manager;
    }
}
