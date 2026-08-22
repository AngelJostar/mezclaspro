<?php

namespace Tests\Feature;

use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DistributionCourierCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_catalog_only_lists_active_personnel_with_the_courier_position(): void
    {
        $courier = $this->personnel('Mensajero Disponible', [PersonnelProfile::POSITION_COURIER]);
        $this->personnel('Personal Administrativo', ['Capturista administrativo']);
        User::factory()->create([
            'name' => 'Usuario Sin Perfil',
            'lastname' => 'Excluido',
            'hospital_id' => null,
        ]);

        $inactiveCourier = $this->personnel('Mensajero Inactivo', [PersonnelProfile::POSITION_COURIER], false);

        $response = $this->actingAs($this->superadministrator())
            ->get(route('admin.distribution.couriers'));

        $response
            ->assertOk()
            ->assertSee('Mensajero Disponible')
            ->assertDontSee('Personal Administrativo')
            ->assertDontSee('Usuario Sin Perfil')
            ->assertDontSee('Mensajero Inactivo')
            ->assertViewHas('messengers', fn ($messengers) => $messengers->getCollection()->pluck('id')->all() === [$courier->id]);

        $this->assertFalse($response->viewData('messengers')->getCollection()->contains($inactiveCourier));
    }

    public function test_create_form_uses_two_sections_and_lists_hospitals_in_the_route_table(): void
    {
        $hospital = Hospital::query()->create([
            'name' => 'Hospital Vertical',
            'adress' => 'Avenida Prueba 123',
            'is_active' => true,
            'municipality' => 'Benito Juarez',
            'postal_code' => '03100',
            'state' => 'Ciudad de Mexico',
            'street_number' => '123',
            'neighborhood' => 'Del Valle',
        ]);

        $courier = $this->personnel('Mensajero Disponible', [PersonnelProfile::POSITION_COURIER]);

        $route = DistributionRoute::query()->create([
            'name' => 'Ruta Centro',
            'code' => 'RUT-CDMX-TEST',
            'schedule_start' => '08:00',
            'schedule_end' => '16:00',
            'status' => DistributionRoute::STATUS_PENDING,
        ]);
        $route->hospitals()->attach($hospital->id, ['stop_order' => 1]);

        $response = $this->actingAs($this->superadministrator())
            ->get(route('admin.distribution.create'));

        $response
            ->assertOk()
            ->assertSee('Información de la ruta y mensajeros')
            ->assertSee('Hospitales cubiertos')
            ->assertSee('Nombre del hospital')
            ->assertSee('Institución')
            ->assertSee('Alcaldía o municipio')
            ->assertSee('CP')
            ->assertSee('Estado')
            ->assertSee('Dirección')
            ->assertSee('Disponibilidad')
            ->assertSee('Ruta asignada')
            ->assertSee('Acción')
            ->assertSee('Hospital Vertical')
            ->assertSee('Ruta Centro')
            ->assertSee($courier->name)
            ->assertViewHas('hospitalRouteAssignments', fn ($assignments) =>
                (int) data_get($assignments->get($hospital->id), 'id') === $route->id
            );
    }

    private function personnel(string $name, array $positions, bool $active = true): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'lastname' => 'Prueba',
            'hospital_id' => null,
            'is_active' => $active,
        ]);

        $user->personnelProfile()->create([
            'paternal_surname' => 'Prueba',
            'positions' => $positions,
            'department' => 'Logistica',
            'hire_date' => '2026-08-20',
            'employment_status' => $active ? 'hired' : 'inactive',
        ]);

        return $user;
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
