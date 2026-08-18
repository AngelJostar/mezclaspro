<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CapacitacionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_capacitacion_user_can_only_open_training_section(): void
    {
        $role = Role::firstOrCreate(['name' => 'Capacitacion', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'lastname' => 'Temporal',
            'hospital_id' => null,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.capacitaciones.index'))
            ->assertOk()
            ->assertSee('Capacitaciones')
            ->assertDontSee('Nutricionales');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.capacitaciones.index'));
    }

    public function test_administration_billing_user_is_redirected_to_billing_from_other_sections(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'Administracion y facturacion',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create([
            'lastname' => 'Facturacion',
            'hospital_id' => null,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.instituciones.billing.index'));

        $this->actingAs($user)
            ->get(route('admin.capacitaciones.index'))
            ->assertRedirect(route('admin.instituciones.billing.index'));
    }
}
