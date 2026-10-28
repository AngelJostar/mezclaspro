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
            '2026_08_10_000001_add_profile_fields_to_hospitals_table.php',
            '2026_10_21_000001_add_service_lines_configured_to_hospitals.php',
            '2026_10_21_000002_add_enabled_services_to_institutions_and_hospitals.php',
            '2026_08_26_000008_add_coordinates_to_hospitals_table.php',
            '2026_08_20_000010_create_distribution_routes_tables.php',
            '2026_10_02_000002_create_hospital_salesperson_table.php',
            '2026_10_28_120000_add_institution_account_to_users.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
        Schema::table('clientes', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::table('hospitals', function (Blueprint $table) {
            $table->unsignedBigInteger('onco_medicine_list_id')->nullable();
            $table->unsignedBigInteger('nutri_medicine_list_id')->nullable();
        });
    }

    public function test_credentials_are_only_listed_in_their_institutions_related_hospitals(): void
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
        $extra = $this->account($hospital, 'acceso-a-turno', 'Clave-turno', 'Cliente');
        $second = $this->account($secondHospital, 'acceso-b', 'Clave-B', 'Cliente');
        $second->update(['is_active' => false]);
        $third = $this->account($otherHospital, 'acceso-c', 'Clave-C', 'Institucion');
        $this->account($hospital, 'personal-interno', 'Clave-interna', 'Admin');
        $this->account(null, 'sin-hospital', 'Clave-sin-hospital', 'Institucion');

        $response = $this->actingAs($manager)->get(route('admin.instituciones.index'));
        $response->assertOk()
            ->assertSee(route('admin.instituciones.hospitals', $institution), false)
            ->assertSee(route('admin.instituciones.hospitals', $otherInstitution), false)
            ->assertDontSeeText('acceso-a')->assertDontSeeText('acceso-b')->assertDontSeeText('acceso-c')
            ->assertDontSeeText('Clave-B')->assertDontSeeText('Clave-C')
            ->assertDontSee('&lt;clave-A&amp;&gt;', false);
        foreach ($response->viewData('instituciones') as $listedInstitution) {
            foreach ($listedInstitution->hospitals as $listedHospital) {
                $this->assertFalse($listedHospital->relationLoaded('users'));
            }
        }

        $response = $this->get(route('admin.instituciones.hospitals', $institution));
        $response->assertOk()
            ->assertSeeInOrder(['Hospitales relacionados', 'Usuario', 'Contrase&ntilde;a'], false)
            ->assertSeeText('acceso-a')->assertSeeText('acceso-a-turno')->assertSeeText('acceso-b')
            ->assertSee('&lt;clave-A&amp;&gt;', false)->assertDontSee('<clave-A&>', false)
            ->assertSeeText('Clave-B')->assertSeeText('Clave-turno')
            ->assertDontSeeText('acceso-c')->assertDontSeeText('Clave-C')
            ->assertDontSeeText('personal-interno')->assertDontSeeText('Clave-interna')
            ->assertDontSeeText('sin-hospital')->assertDontSeeText('Clave-sin-hospital');

        $listed = $response->viewData('hospitals')->keyBy('id');
        $this->assertSame([$first->id, $extra->id], $listed[$hospital->id]->users->pluck('id')->all());
        $this->assertSame([$second->id], $listed[$secondHospital->id]->users->pluck('id')->all());
        $this->assertNotSame('<clave-A&>', $first->getRawOriginal('credential_password'));
        $response->assertDontSee($first->getRawOriginal('password'), false);

        $response = $this->get(route('admin.instituciones.hospitals', $otherInstitution));
        $response->assertOk()->assertSeeText('acceso-c')->assertSeeText('Clave-C')
            ->assertDontSeeText('acceso-a')->assertDontSeeText('Clave-B');
        $this->assertSame([$third->id], $response->viewData('hospitals')->first()->users->pluck('id')->all());
    }

    public function test_missing_password_is_reported_without_substituting_training_credentials(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion sin clave']);
        $hospital = Hospital::factory()->create(['adress' => 'Direccion']);
        $institution->hospitals()->attach($hospital);
        $account = $this->account($hospital, 'acceso-sin-clave', null, 'Institucion');
        $account->update(['training_credential_password' => 'Solo-capacitacion']);
        $emptyHospital = Hospital::factory()->create(['name' => 'Hospital sin usuarios', 'adress' => 'Direccion']);
        $institution->hospitals()->attach($emptyHospital);

        $this->actingAs($this->manager())->get(route('admin.instituciones.hospitals', $institution))
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
        $viewer->givePermissionTo(Permission::findOrCreate('menu.instituciones.hospitals', 'web'));

        $response = $this->actingAs($viewer)->get(route('admin.instituciones.hospitals', $institution));
        $response->assertOk()
            ->assertSeeText('acceso-protegido')
            ->assertSee('aria-label="Contrase&ntilde;a protegida"', false)
            ->assertDontSee('data-inline-credential-form data-field=', false)
            ->assertDontSee('Clave-protegida', false);

        $loadedUser = $response->viewData('hospitals')->first()->users->first();
        $this->assertArrayNotHasKey('credential_password', $loadedUser->getAttributes());
        $this->assertArrayNotHasKey('training_credential_password', $loadedUser->getAttributes());

        $viewer->givePermissionTo(Permission::findOrCreate('usuarios', 'web'));
        $this->actingAs($viewer->fresh())->get(route('admin.instituciones.hospitals', $institution))
            ->assertOk()->assertSeeText('Clave-protegida');
    }

    public function test_institution_columns_only_show_the_own_account_and_protect_its_password(): void
    {
        $institution = Institucion::create(['nombre' => 'Institución con acceso propio']);
        $account = User::factory()->create(['institution_id' => $institution->id, 'hospital_id' => null,
            'username' => 'acceso.institucion', 'credential_password' => 'Clave-institucional']);
        $viewer = User::factory()->create(['hospital_id' => null]);
        $viewer->givePermissionTo(Permission::findOrCreate('menu.instituciones.list', 'web'));
        $response = $this->actingAs($viewer)->get(route('admin.instituciones.index'));
        $response->assertOk()->assertSeeText('acceso.institucion')->assertDontSeeText('Clave-institucional')
            ->assertDontSee('data-institution-account-create', false);
        $this->assertArrayNotHasKey('credential_password', $response->viewData('instituciones')->first()->account->getAttributes());
        $this->actingAs($this->manager())->get(route('admin.instituciones.index'))->assertOk()
            ->assertSeeText('acceso.institucion')->assertSeeText('Clave-institucional')
            ->assertSee(route('admin.instituciones.account.update', [$institution, 'password']), false)
            ->assertDontSee($account->password, false);
    }

    private function manager(): User
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->givePermissionTo([
            Permission::findOrCreate('menu.instituciones.list', 'web'),
            Permission::findOrCreate('menu.instituciones.hospitals', 'web'),
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
