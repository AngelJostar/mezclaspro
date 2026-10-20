<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionCredentialsListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2024_01_10_035338_create_hospitals_table.php',
            '2026_02_06_201859_create_clientes_table.php',
            '2026_02_06_214504_create_cliente_hospital_table.php',
            '2024_04_11_013606_create_permission_tables.php',
            '2024_05_21_124030_create_notifications_table.php',
            '2026_08_19_000007_add_credential_password_to_users_table.php',
            '2026_08_19_000008_add_training_credentials_to_users_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
        Schema::table('clientes', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
    }

    public function test_list_shows_existing_institution_credentials_under_their_linked_hospitals(): void
    {
        $manager = $this->manager();
        $institution = Institucion::create(['nombre' => 'Institucion principal']);
        $otherInstitution = Institucion::create(['nombre' => 'Otra institucion']);
        $hospital = Hospital::factory()->create(['name' => 'Hospital A', 'adress' => 'Direccion A']);
        $secondHospital = Hospital::factory()->create(['name' => 'Hospital B', 'adress' => 'Direccion B']);
        $otherHospital = Hospital::factory()->create(['name' => 'Hospital C', 'adress' => 'Direccion C']);
        $institution->hospitals()->attach([$hospital->id, $secondHospital->id]);
        $otherInstitution->hospitals()->attach($otherHospital);

        $first = $this->account($hospital, 'acceso-a', '<clave-A&>', 'Institucion');
        $second = $this->account($secondHospital, 'acceso-b', 'Clave-B', 'Cliente');
        $second->update(['is_active' => false]);
        $third = $this->account($otherHospital, 'acceso-c', 'Clave-C', 'Institucion');
        $this->account($hospital, 'personal-interno', 'Clave-interna', 'Admin');
        $this->account(null, 'sin-hospital', 'Clave-sin-hospital', 'Institucion');

        $response = $this->actingAs($manager)->get(route('admin.instituciones.index'));
        $response->assertOk()
            ->assertSeeInOrder(['Razon social', 'Usuario', 'Contraseña', 'Editar'])
            ->assertSeeText('acceso-a')->assertSeeText('acceso-b')->assertSeeText('acceso-c')
            ->assertSee('&lt;clave-A&amp;&gt;', false)->assertDontSee('<clave-A&>', false)
            ->assertSeeText('Clave-B')->assertSeeText('Clave-C')
            ->assertDontSeeText('personal-interno')->assertDontSeeText('Clave-interna')
            ->assertDontSeeText('sin-hospital')->assertDontSeeText('Clave-sin-hospital');

        $listed = $response->viewData('instituciones')->keyBy('id');
        $this->assertSame(
            [$first->id, $second->id],
            $listed[$institution->id]->hospitals->flatMap->users->pluck('id')->all()
        );
        $this->assertSame(
            [$third->id],
            $listed[$otherInstitution->id]->hospitals->flatMap->users->pluck('id')->all()
        );
        $this->assertNotSame('<clave-A&>', $first->getRawOriginal('credential_password'));
        $response->assertDontSee($first->getRawOriginal('password'), false);
    }

    public function test_missing_password_is_reported_without_substituting_training_credentials(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion sin clave']);
        $hospital = Hospital::factory()->create(['adress' => 'Direccion']);
        $institution->hospitals()->attach($hospital);
        $account = $this->account($hospital, 'acceso-sin-clave', null, 'Institucion');
        $account->update(['training_credential_password' => 'Solo-capacitacion']);
        Institucion::create(['nombre' => 'Institucion sin usuarios']);

        $this->actingAs($this->manager())->get(route('admin.instituciones.index'))
            ->assertOk()
            ->assertSeeText('acceso-sin-clave')
            ->assertSeeText('Sin contraseña registrada')
            ->assertSeeText('Sin usuario registrado')
            ->assertDontSeeText('Solo-capacitacion')
            ->assertDontSee($account->getRawOriginal('password'), false);
    }

    public function test_list_only_permission_does_not_load_or_expose_passwords(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion protegida']);
        $hospital = Hospital::factory()->create(['adress' => 'Direccion']);
        $institution->hospitals()->attach($hospital);
        $this->account($hospital, 'acceso-protegido', 'Clave-protegida', 'Institucion');

        $viewer = User::factory()->create(['hospital_id' => null]);
        $viewer->givePermissionTo(Permission::findOrCreate('menu.instituciones.list', 'web'));

        $response = $this->actingAs($viewer)->get(route('admin.instituciones.index'));
        $response->assertOk()
            ->assertSeeText('acceso-protegido')
            ->assertSee('aria-label="Contraseña protegida"', false)
            ->assertDontSee('Clave-protegida', false);

        $loadedUser = $response->viewData('instituciones')->first()->hospitals->first()->users->first();
        $this->assertArrayNotHasKey('credential_password', $loadedUser->getAttributes());

        $viewer->givePermissionTo(Permission::findOrCreate('usuarios', 'web'));
        $this->actingAs($viewer->fresh())->get(route('admin.instituciones.index'))
            ->assertOk()->assertSeeText('Clave-protegida');
    }

    private function manager(): User
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->givePermissionTo([
            Permission::findOrCreate('menu.instituciones.list', 'web'),
            Permission::findOrCreate('usuarios', 'web'),
        ]);

        return $user;
    }

    private function account(?Hospital $hospital, string $username, ?string $password, string $role): User
    {
        $user = User::factory()->create([
            'hospital_id' => $hospital?->id,
            'username' => $username,
            'credential_password' => $password,
        ]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }
}
