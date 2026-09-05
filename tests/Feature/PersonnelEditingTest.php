<?php

namespace Tests\Feature;

use App\Models\Oncologicos\Laboratory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelEditingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolate this workflow from unrelated inventory migrations requiring MySQL.
        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2024_01_10_035338_create_hospitals_table.php',
            '2024_02_05_235617_add_hospital_to_users_table.php',
            '2024_04_11_013606_create_permission_tables.php',
            '2025_11_06_183220_create_laboratories_table.php',
            '2026_03_03_004410_add_laboratory_id_to_hospitals_table.php',
            '2026_08_19_000007_add_credential_password_to_users_table.php',
            '2026_08_19_000008_add_training_credentials_to_users_table.php',
            '2026_08_20_000006_create_personnel_profiles_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        $manager = $this->createUser();
        $manager->assignRole(Role::create(['name' => 'Usuario general', 'guard_name' => 'web']));
        $manager->givePermissionTo(Permission::create(['name' => 'menu.capacitaciones.personal', 'guard_name' => 'web']));
        $this->actingAs($manager);
    }

    public function test_edit_returns_general_information_without_exposing_credentials(): void
    {
        $user = $this->createUser();
        $this->createProfile($user);

        $response = $this->getJson(route('admin.capacitaciones.personal.edit', $user))
            ->assertOk()
            ->assertJsonPath('fields.first_name', 'Persona')
            ->assertJsonPath('fields.paternal_surname', 'Perez')
            ->assertJsonPath('fields.hire_date', '2025-01-10')
            ->assertJsonPath('fields.positions.0', 'Verificador');

        $response->assertDontSee('password', false)->assertDontSee('username', false);
    }

    public function test_update_saves_general_information_and_preserves_access_and_primary_position(): void
    {
        $user = $this->createUser();
        $user->assignRole(Role::create(['name' => 'Capacitacion', 'guard_name' => 'web']));
        $profile = $this->createProfile($user);
        $originalUser = $user->fresh()->getRawOriginal();
        $payload = $this->payload($profile->laboratory_id);
        $payload['first_name'] = '  MARIA   ELENA ';
        $payload['positions'] = ['Preparador de mezclas', 'Verificador'];
        $payload['password'] = 'must-not-change';
        $payload['roles'] = ['Super Admin'];
        $payload['is_active'] = false;
        $payload['employment_status'] = 'inactive';

        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $payload)
            ->assertOk()->assertJsonPath('name', 'Maria Elena Perez Lopez');

        $user->refresh();
        $profile->refresh();
        $this->assertSame('Maria Elena', $user->name);
        $this->assertSame('Perez Lopez', $user->lastname);
        $this->assertSame(['Verificador', 'Preparador de mezclas'], $profile->positions);
        $this->assertSame('5551234567', $profile->phone);
        $this->assertSame('Calidad', $profile->department);
        $this->assertSame('hired', $profile->employment_status);
        $this->assertSame('Experiencia actualizada', $profile->prior_experience);
        $this->assertSame(['Capacitacion'], $user->getRoleNames()->all());
        foreach (['username', 'password', 'training_username', 'training_password', 'credential_password', 'training_credential_password', 'is_active'] as $field) {
            $this->assertSame($originalUser[$field], $user->getRawOriginal($field), $field);
        }
    }

    public function test_legacy_personnel_can_complete_a_profile_without_creating_another_account(): void
    {
        $user = $this->createUser();
        $laboratory = Laboratory::create(['nombre' => 'Central de prueba', 'activo' => true]);
        $userCount = User::count();

        $this->getJson(route('admin.capacitaciones.personal.edit', $user))
            ->assertOk()->assertJsonPath('fields.paternal_surname', 'Perez Lopez')
            ->assertJsonPath('fields.hire_date', null);
        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $this->payload($laboratory->id))->assertOk();

        $this->assertSame($userCount, User::count());
        $this->assertSame(1, $user->personnelProfile()->count());
        $this->assertSame($laboratory->id, $user->fresh()->personnelProfile->laboratory_id);
    }

    public function test_validation_preserves_saved_information_and_rejects_duplicate_email(): void
    {
        $user = $this->createUser();
        $profile = $this->createProfile($user);
        $other = $this->createUser();
        $this->createProfile($other);
        $original = $profile->fresh()->getRawOriginal();

        foreach ([
            ['personal_email' => strtoupper($other->personnelProfile->personal_email)],
            ['positions' => ['Puesto inventado']],
            ['laboratory_id' => 99999],
            ['first_name' => ['valor invalido']],
            ['hire_date' => now()->addDay()->toDateString()],
            ['department' => 'Inexistente'],
        ] as $invalid) {
            $this->patchJson(route('admin.capacitaciones.personal.update', $user), array_replace($this->payload($profile->laboratory_id), $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid)[0] === 'positions' ? 'positions.0' : array_keys($invalid));
            $this->assertSame($original, $profile->fresh()->getRawOriginal());
        }
    }

    public function test_current_email_and_inactive_central_can_be_retained_but_another_inactive_central_cannot_be_selected(): void
    {
        $user = $this->createUser();
        $profile = $this->createProfile($user);
        $profile->laboratory->update(['activo' => false]);
        $other = Laboratory::create(['nombre' => 'Central inactiva', 'activo' => false]);
        $payload = $this->payload($profile->laboratory_id);
        $payload['personal_email'] = $profile->personal_email;

        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $payload)->assertOk();
        $payload['laboratory_id'] = $other->id;
        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('laboratory_id');
    }

    public function test_cv_is_preserved_unless_a_valid_replacement_is_uploaded(): void
    {
        Storage::fake('local');
        $user = $this->createUser();
        $profile = $this->createProfile($user);
        Storage::disk('local')->put('personnel/cv/original.pdf', 'original');
        $profile->update(['cv_path' => 'personnel/cv/original.pdf', 'cv_original_name' => 'original.pdf']);
        $payload = $this->payload($profile->laboratory_id);

        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $payload)->assertOk();
        Storage::disk('local')->assertExists('personnel/cv/original.pdf');
        $this->assertSame('original.pdf', $profile->fresh()->cv_original_name);

        $payload['cv'] = UploadedFile::fake()->create('actualizado.pdf', 20, 'application/pdf');
        $this->patchJson(route('admin.capacitaciones.personal.update', $user), $payload)->assertOk();
        $profile->refresh();
        $this->assertSame('actualizado.pdf', $profile->cv_original_name);
        Storage::disk('local')->assertExists($profile->cv_path);
        Storage::disk('local')->assertMissing('personnel/cv/original.pdf');
    }

    public function test_unauthorized_users_cannot_read_or_change_personnel(): void
    {
        $user = $this->createUser();
        $this->actingAs($this->createUser());
        $this->getJson(route('admin.capacitaciones.personal.edit', $user))->assertForbidden();
        $this->patchJson(route('admin.capacitaciones.personal.update', $user), [])->assertForbidden();
        $this->assertNull($user->fresh()->personnelProfile);
    }

    public function test_customer_accounts_are_not_editable_as_personnel(): void
    {
        $user = $this->createUser();
        $user->assignRole(Role::create(['name' => 'Cliente', 'guard_name' => 'web']));
        $this->getJson(route('admin.capacitaciones.personal.edit', $user))->assertNotFound();
        $this->patchJson(route('admin.capacitaciones.personal.update', $user), [])->assertNotFound();
    }

    private function createUser(): User
    {
        $suffix = User::count() + 1;
        return User::create([
            'name' => 'Persona', 'lastname' => 'Perez Lopez', 'username' => 'persona'.$suffix,
            'password' => Hash::make('prueba123'), 'credential_password' => 'prueba123',
            'training_username' => 'cappersona'.$suffix, 'training_password' => Hash::make('prueba123'),
            'training_credential_password' => 'prueba123', 'is_active' => true,
        ]);
    }

    private function createProfile(User $user)
    {
        $laboratory = Laboratory::create(['nombre' => 'Central de prueba', 'activo' => true]);
        return $user->personnelProfile()->create([
            'laboratory_id' => $laboratory->id, 'paternal_surname' => 'Perez', 'maternal_surname' => 'Lopez',
            'personal_email' => 'persona'.$user->id.'@example.test', 'phone' => '5550000000',
            'positions' => ['Verificador'], 'hire_date' => '2025-01-10', 'department' => 'Operaciones',
            'employment_status' => 'hired',
        ]);
    }

    private function payload(int $laboratoryId): array
    {
        return [
            'first_name' => 'Persona', 'paternal_surname' => 'Perez', 'maternal_surname' => 'Lopez',
            'phone' => '5551234567', 'personal_email' => 'actualizado@example.test',
            'laboratory_id' => $laboratoryId, 'department' => 'Calidad', 'hire_date' => '2025-01-10',
            'positions' => ['Verificador'], 'prior_experience' => 'Experiencia actualizada',
            'additional_information' => 'Observaciones actualizadas',
        ];
    }
}
