<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Support\AdminMenuAccess;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\InstitutionHospitalFields;
use Tests\TestCase;

class InstitutionFieldEditingTest extends TestCase
{
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = InstitutionHospitalFields::seed();
        (require database_path('migrations/2026_10_21_000002_add_enabled_services_to_institutions_and_hospitals.php'))->up();
        (require database_path('migrations/2026_07_29_120000_add_rfc_and_telefono_to_clientes_table.php'))->up();
        $this->fixture['institution']->hospitals()->attach($this->fixture['otherHospital']);
        $this->fixture['client']->update(['hospital_id' => $this->fixture['hospital']->id]);
        $this->actingAs($this->fixture['manager']);
    }

    private function fieldUrl(string $field): string
    {
        return route('admin.instituciones.fields.edit', [$this->fixture['institution'], $field]);
    }

    private function mixturesUrl(): string
    {
        return route('admin.instituciones.mixtures.edit', $this->fixture['institution']);
    }

    private function credentialUrl(string $field, $user = null): string
    {
        return route('admin.instituciones.credentials.update', [$this->fixture['institution'], $user ?? $this->fixture['client'], $field]);
    }

    public function test_each_institution_field_saves_only_that_field_and_rejects_stale_edits(): void
    {
        $institution = $this->fixture['institution'];
        $institution->update(['razon_social' => 'Razón original', 'rfc' => 'RFC-ORIGINAL', 'telefono' => '12345']);
        foreach (['nombre' => 'Nombre actualizado', 'razon_social' => 'Razón actualizada'] as $field => $value) {
            $url = $this->fieldUrl($field);
            $state = $this->getJson($url)->assertOk()->json();
            $this->patchJson($url, ['value' => '', 'version' => $state['version']])->assertUnprocessable();
            $this->patchJson($url, ['value' => $value, 'version' => $state['version'], 'rfc' => 'OTRO'])->assertOk();
            $this->assertSame($value, $institution->fresh()->{$field});
            $this->assertSame('RFC-ORIGINAL', $institution->fresh()->rfc);
            $this->assertSame('12345', $institution->fresh()->telefono);
            $this->patchJson($url, ['value' => 'Edición vieja', 'version' => $state['version']])->assertConflict();
        }
        $this->getJson($this->fieldUrl('rfc'))->assertNotFound();
        $this->patchJson($this->fieldUrl('rfc'), ['value' => 'OTRO'])->assertNotFound();
    }

    public function test_account_pencils_update_the_matching_account_and_preserve_training_access(): void
    {
        $user = $this->fixture['client'];
        $user->update(['training_password' => Hash::make('curso-original'), 'training_credential_password' => 'curso-original']);
        $this->patchJson($this->credentialUrl('username'), ['username' => 'hospital.actualizado'])->assertOk();
        $this->assertSame('hospital.actualizado', $user->fresh()->username);
        $this->patchJson($this->credentialUrl('username'), ['username' => 'seller.new'])->assertUnprocessable();
        $this->patchJson($this->credentialUrl('password'), ['password' => 'Nueva-Prueba-2026'])->assertOk();
        $saved = $user->fresh();
        $this->assertTrue(Hash::check('Nueva-Prueba-2026', $saved->password));
        $this->assertSame('Nueva-Prueba-2026', $saved->credential_password);
        $this->assertNotSame('Nueva-Prueba-2026', $saved->getRawOriginal('credential_password'));
        $this->assertTrue(Hash::check('curso-original', $saved->training_password));
        $this->assertSame('curso-original', $saved->training_credential_password);
        $this->patchJson($this->credentialUrl('password', $this->fixture['nextSeller']), ['password' => 'no-cambiar'])->assertNotFound();
        $outside = Hospital::factory()->create(['adress' => 'Fuera de institución']);
        $user->update(['hospital_id' => $outside->id]);
        $this->patchJson($this->credentialUrl('password'), ['password' => 'no-cambiar'])->assertNotFound();
        $this->assertTrue(Hash::check('Nueva-Prueba-2026', $user->fresh()->password));
    }

    public function test_institution_managers_need_user_permission_for_account_pencils(): void
    {
        Role::create(['name' => AdminMenuAccess::GENERAL_ROLE, 'guard_name' => 'web']);
        Permission::create(['name' => 'menu.instituciones.list', 'guard_name' => 'web']);
        Permission::create(['name' => 'usuarios', 'guard_name' => 'web']);
        $manager = $this->fixture['manager'];
        $manager->syncRoles([AdminMenuAccess::GENERAL_ROLE]);
        $manager->givePermissionTo('menu.instituciones.list');
        $this->actingAs($manager->fresh());
        $this->getJson($this->fieldUrl('nombre'))->assertOk();
        $this->getJson($this->mixturesUrl())->assertOk();
        $this->patchJson($this->credentialUrl('username'), ['username' => 'nuevo'])->assertForbidden();
        $this->patchJson($this->credentialUrl('password'), ['password' => 'nueva'])->assertForbidden();
        $manager->givePermissionTo('usuarios');
        $this->actingAs($manager->fresh())->patchJson($this->credentialUrl('username'), ['username' => 'nuevo'])->assertOk();
    }

    public function test_mixtures_are_saved_per_hospital_without_changing_price_lists_or_other_assignments(): void
    {
        $url = $this->mixturesUrl();
        $state = $this->getJson($url)->assertOk()->assertJsonPath('title', 'Mezclas habilitadas')->json();
        $this->assertSame(['oncology' => true, 'antibiotics' => false, 'nutrition' => true], $state['hospitals'][0]['services']);
        $outside = Hospital::factory()->create(['adress' => 'Hospital ajeno', 'service_antibiotics' => true]);
        $rows = array_map(fn ($row, $index) => [
            'id' => $row['id'], 'services' => ['oncology' => false, 'antibiotics' => true, 'nutrition' => $index === 1],
        ], $state['hospitals'], array_keys($state['hospitals']));
        $this->patchJson($url, ['version' => $state['version'], 'hospital_services' => $rows])->assertOk();
        $first = $this->fixture['hospital']->fresh();
        $second = $this->fixture['otherHospital']->fresh();
        $this->assertFalse($first->offersService('oncology'));
        $this->assertTrue($first->offersService('antibiotics'));
        $this->assertFalse($first->offersService('nutrition'));
        $this->assertTrue($second->offersService('nutrition'));
        $this->assertSame(14, $first->onco_medicine_list_id);
        $this->assertSame(25, $first->nutri_medicine_list_id);
        $this->assertTrue($outside->fresh()->service_antibiotics);
        $this->assertSame([$this->fixture['route']->id], $first->distributionRoutes()->get()->modelKeys());
        $this->get(route('admin.instituciones.index'))->assertOk()->assertSee('Antibióticos (2/2)')->assertSee('Nutricionales (1/2)')->assertDontSee('Oncológicos (1/2)');
        $this->patchJson($url, ['version' => $state['version'], 'hospital_services' => $rows])->assertConflict();
    }

    public function test_invalid_mixture_rows_cannot_partially_update_hospitals(): void
    {
        $url = $this->mixturesUrl();
        $state = $this->getJson($url)->assertOk()->json();
        $rows = array_map(fn ($row) => ['id' => $row['id'], 'services' => ['oncology' => false, 'antibiotics' => false, 'nutrition' => false]], $state['hospitals']);
        foreach ([[$rows[0]], [$rows[0], $rows[0]], [$rows[0], ['id' => 9999, 'services' => $rows[1]['services']]],
            [$rows[0], ['id' => $rows[1]['id'], 'services' => ['oncology' => 'invalid', 'antibiotics' => true, 'nutrition' => false]]]] as $invalid) {
            $this->patchJson($url, ['version' => $state['version'], 'hospital_services' => $invalid])->assertUnprocessable();
            $this->assertTrue($this->fixture['hospital']->fresh()->offersService('oncology'));
        }
        $this->fixture['institution']->hospitals()->detach();
        $this->getJson($url)->assertOk()->assertJsonPath('can_save', false)->assertJsonCount(0, 'hospitals');
        $this->patchJson($url, ['version' => $state['version'], 'hospital_services' => []])->assertUnprocessable();
    }
}
