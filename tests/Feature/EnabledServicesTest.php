<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EnabledServicesController;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\RequestQuotation;
use App\Support\EnabledServices;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\InstitutionHospitalFields;
use Tests\TestCase;

class EnabledServicesTest extends TestCase
{
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = InstitutionHospitalFields::seed();
        (require database_path('migrations/2026_10_21_000002_add_enabled_services_to_institutions_and_hospitals.php'))->up();
        \Illuminate\Support\Facades\Schema::table('clientes', fn ($table) => $table->boolean('is_active')->default(true));
        $this->fixture['institution']->hospitals()->attach($this->fixture['otherHospital']);
        $this->fixture['client']->update(['hospital_id' => $this->fixture['hospital']->id]);
        $this->actingAs($this->fixture['manager']);
    }

    private function url(?Hospital $hospital = null): string
    {
        return $hospital
            ? route('admin.instituciones.hospitals.services.hospital.edit', [$this->fixture['institution'], $hospital])
            : route('admin.instituciones.hospitals.services.institution.edit', $this->fixture['institution']);
    }

    private function save(?Hospital $hospital, ?bool $enabled, bool $all = false)
    {
        $url = $this->url($hospital);
        $state = $this->getJson($url)->assertOk()->json();
        $payload = ['version' => $state['version'], 'services' => ['quotation_requests' => $enabled]];
        if (! $hospital) $payload['apply_to_all'] = $all;

        return $this->patchJson($url, $payload);
    }

    public function test_institution_defaults_and_hospital_overrides_allow_mixed_access(): void
    {
        ['hospital' => $first, 'otherHospital' => $second] = $this->fixture;
        $this->getJson($this->url())->assertJsonPath('services.0.enabled_count', 2);
        $this->save(null, false)->assertOk();
        $this->assertFalse(EnabledServices::hospital($first->fresh()));
        $this->assertFalse(EnabledServices::hospital($second->fresh()));
        $this->save($first, true)->assertOk();
        $this->assertTrue(EnabledServices::hospital($first->fresh()));
        $this->assertFalse(EnabledServices::hospital($second->fresh()));
        $this->getJson($this->url())->assertJsonPath('services.0.enabled_count', 1);
        $this->save(null, true)->assertOk();
        $this->save($second, false)->assertOk();
        $this->assertTrue(EnabledServices::hospital($first->fresh()));
        $this->assertFalse(EnabledServices::hospital($second->fresh()));
        $this->save($second, null)->assertOk();
        $this->getJson($this->url($second))->assertJsonPath('services.0.value', null)->assertJsonPath('services.0.inherited', true);
        $this->assertTrue(EnabledServices::hospital($second->fresh()));
    }

    public function test_apply_to_all_covers_entire_institution_and_preserves_unrelated_data(): void
    {
        $institution = $this->fixture['institution'];
        $extra = Hospital::factory()->count(27)->create(['adress' => 'Dirección', 'is_active' => true]);
        $institution->hospitals()->attach($extra->modelKeys());
        $outside = Hospital::factory()->create(['adress' => 'Fuera de institución', 'is_active' => true]);
        $first = $this->fixture['hospital'];
        $first->update(['enabled_services' => ['quotation_requests' => true, 'future_service' => false]]);
        $this->save(null, false, true)->assertOk();
        foreach ($institution->hospitals()->get() as $hospital) $this->assertFalse(EnabledServices::hospital($hospital));
        $this->assertTrue(EnabledServices::hospital($outside->fresh()));
        $this->assertFalse($first->fresh()->enabled_services['future_service']);
        $this->assertSame(14, $first->fresh()->onco_medicine_list_id);
        $this->assertSame([$this->fixture['route']->id], $first->distributionRoutes()->get()->modelKeys());
        $this->save(null, true, true)->assertOk();
        $this->getJson($this->url())->assertJsonPath('services.0.enabled_count', 29);
        $newHospital = Hospital::factory()->create(['adress' => 'Nuevo hospital']);
        $institution->hospitals()->attach($newHospital);
        $this->assertTrue(EnabledServices::hospital($newHospital->fresh()));
    }

    public function test_shared_hospital_can_override_restrictive_institution_default(): void
    {
        $secondInstitution = Institucion::create(['nombre' => 'Otra institución', 'enabled_services' => ['quotation_requests' => false]]);
        $hospital = $this->fixture['hospital'];
        $secondInstitution->hospitals()->attach($hospital);
        $this->assertFalse(EnabledServices::hospital($hospital->fresh()));
        $this->save($hospital, true)->assertOk();
        $this->assertTrue(EnabledServices::hospital($hospital->fresh()));
    }

    public function test_institution_list_summarizes_effective_enabled_services_and_keeps_the_edit_pencil(): void
    {
        $url = route('admin.instituciones.index');
        $this->get($url)->assertOk()->assertSee('Solicitud de cotización (2/2)')
            ->assertSee('Editar servicios habilitados de Institución de prueba');
        $this->save(null, false)->assertOk();
        $this->save($this->fixture['hospital'], true)->assertOk();
        $this->get($url)->assertOk()->assertSee('Solicitud de cotización (1/2)')->assertDontSee('Solicitud de cotización (2/2)');
        $this->save($this->fixture['hospital'], false)->assertOk();
        $this->get($url)->assertOk()->assertSee('Sin servicios habilitados')->assertDontSee('Solicitud de cotización (1/2)');

        $this->save(null, true, true)->assertOk();
        $this->save($this->fixture['hospital'], null)->assertOk();
        $restrictive = Institucion::create(['nombre' => 'Institución compartida', 'enabled_services' => ['quotation_requests' => false]]);
        $restrictive->hospitals()->attach($this->fixture['hospital']);
        Institucion::create(['nombre' => 'Institución sin hospitales']);
        $this->get($url)->assertOk()->assertSee('Solicitud de cotización (1/2)')->assertSee('Sin hospitales');
    }

    public function test_institution_table_saves_each_hospital_and_bulk_selection(): void
    {
        $state = $this->getJson($this->url())->assertOk()->json();
        $this->assertCount(2, $state['hospitals']);
        $rows = array_map(fn ($row, $index) => [
            'id' => $row['id'], 'services' => ['quotation_requests' => $index === 0],
        ], $state['hospitals'], array_keys($state['hospitals']));
        $this->patchJson($this->url(), [
            'version' => $state['version'], 'services' => ['quotation_requests' => true], 'hospital_services' => $rows,
        ])->assertOk();
        $this->assertTrue(EnabledServices::hospital($this->fixture['hospital']->fresh()));
        $this->assertFalse(EnabledServices::hospital($this->fixture['otherHospital']->fresh()));
        foreach ([false, true] as $enabled) {
            $state = $this->getJson($this->url())->assertOk()->json();
            $this->patchJson($this->url(), [
                'version' => $state['version'], 'services' => ['quotation_requests' => $enabled],
                'hospital_services' => array_map(fn ($row) => [
                    'id' => $row['id'], 'services' => ['quotation_requests' => $enabled],
                ], $state['hospitals']),
            ])->assertOk();
            foreach ($this->fixture['institution']->hospitals()->get() as $hospital) {
                $this->assertSame($enabled, EnabledServices::hospital($hospital));
            }
            $this->assertSame($enabled, EnabledServices::institution($this->fixture['institution']->fresh()));
        }
    }

    public function test_invalid_hospital_table_cannot_partially_save_or_modify_another_institution(): void
    {
        $state = $this->getJson($this->url())->assertOk()->json();
        $rows = array_map(fn ($row) => ['id' => $row['id'], 'services' => ['quotation_requests' => false]], $state['hospitals']);
        $outside = Hospital::factory()->create(['adress' => 'Fuera de institución']);
        foreach ([
            array_slice($rows, 0, 1),
            [$rows[0], $rows[0]],
            [$rows[0], ['id' => $outside->id, 'services' => ['quotation_requests' => false]]],
            [$rows[0], ['id' => $rows[1]['id'], 'services' => ['quotation_requests' => false, 'unknown' => true]]],
        ] as $invalid) {
            $this->patchJson($this->url(), [
                'version' => $state['version'], 'services' => ['quotation_requests' => false], 'hospital_services' => $invalid,
            ])->assertUnprocessable();
            $this->assertNull($this->fixture['institution']->fresh()->enabled_services);
            $this->assertNull($this->fixture['hospital']->fresh()->enabled_services);
            $this->assertNull($outside->fresh()->enabled_services);
        }
        $this->save($this->fixture['otherHospital'], false)->assertOk();
        $this->patchJson($this->url(), [
            'version' => $state['version'], 'services' => ['quotation_requests' => false], 'hospital_services' => $rows,
        ])->assertConflict();
        $this->assertNull($this->fixture['hospital']->fresh()->enabled_services);
    }

    public function test_institution_managers_can_use_services_without_granting_other_hospital_permissions(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'Usuario general', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::create(['name' => 'menu.instituciones.list', 'guard_name' => 'web']);
        $manager = $this->fixture['manager'];
        $manager->syncRoles(['Usuario general']);
        $manager->givePermissionTo('menu.instituciones.list');
        $this->actingAs($manager->fresh());
        $this->save(null, false)->assertOk();
        $this->save($this->fixture['hospital'], true)->assertOk();
        $this->getJson(route('admin.instituciones.hospitals', $this->fixture['institution']))->assertForbidden();
    }

    public function test_empty_institution_can_set_the_default_for_future_hospitals(): void
    {
        $this->fixture['institution']->hospitals()->detach();
        $state = $this->getJson($this->url())->assertOk()->json();
        $this->assertSame([], $state['hospitals']);
        $this->patchJson($this->url(), [
            'version' => $state['version'], 'services' => ['quotation_requests' => false], 'hospital_services' => [],
        ])->assertOk();
        $this->fixture['institution']->hospitals()->attach($this->fixture['hospital']);
        $this->assertFalse(EnabledServices::hospital($this->fixture['hospital']->fresh()));
    }

    public function test_invalid_or_stale_settings_cannot_overwrite_newer_configuration(): void
    {
        $hospital = $this->fixture['hospital'];
        $version = $this->getJson($this->url($hospital))->json('version');
        foreach ([[], ['unknown' => true], ['quotation_requests' => 'wrong']] as $values) {
            $this->patchJson($this->url($hospital), ['version' => $version, 'services' => $values])->assertUnprocessable();
        }
        $this->save(null, false)->assertOk();
        $this->patchJson($this->url($hospital), ['version' => $version, 'services' => ['quotation_requests' => true]])->assertConflict();
        $this->assertNull($hospital->fresh()->enabled_services);
    }

    public function test_clients_cannot_configure_services_and_hospital_must_belong_to_institution(): void
    {
        $unrelated = Hospital::factory()->create(['adress' => 'Otro hospital']);
        $this->getJson($this->url($unrelated))->assertNotFound();
        $this->patchJson($this->url($unrelated), [])->assertNotFound();
        $this->actingAs($this->fixture['client']);
        foreach ([null, $this->fixture['hospital']] as $hospital) {
            $this->getJson($this->url($hospital))->assertForbidden();
            $this->patchJson($this->url($hospital), [])->assertForbidden();
        }
    }

    public function test_disabled_service_blocks_web_and_mobile_requests_but_does_not_block_staff(): void
    {
        $client = $this->fixture['client'];
        $this->save(null, false)->assertOk();
        $this->assertFalse($client->canUseQuotationRequests());
        $this->assertFalse(RequestQuotation::canCreate($client, 'oncologicos'));
        $this->assertTrue($this->fixture['manager']->canUseQuotationRequests());
        $this->actingAs($client);
        foreach (['index', 'options', 'export'] as $action) {
            $this->getJson(route('admin.solicitudes.cotizacion.'.$action))->assertForbidden()
                ->assertJsonPath('message', 'El servicio Solicitud de cotización está inhabilitado para tu hospital.');
        }
        foreach (['store', 'preview'] as $action) $this->postJson(route('admin.solicitudes.cotizacion.'.$action), [])->assertForbidden();
        Sanctum::actingAs($client);
        $this->postJson('/api/mobile/hospital/quotation-requests', [])->assertForbidden();
        $this->postJson('/api/mobile/hospital/quotation-wizard/requests', [])->assertForbidden();
        $this->getJson('/api/mobile/hospital/quotation-wizard/catalog')->assertForbidden();
        $this->getJson('/api/mobile/hospital/quotations')->assertForbidden();
    }

    public function test_reenabled_hospital_regains_access_and_current_settings_ignore_stale_loaded_relations(): void
    {
        $client = $this->fixture['client'];
        $client->load('hospital:id,name');
        $this->assertTrue($client->canUseQuotationRequests());
        $this->save(null, false)->assertOk();
        $this->assertFalse($client->canUseQuotationRequests());
        $this->save($this->fixture['hospital'], true)->assertOk();
        $this->assertTrue($client->canUseQuotationRequests());
        $this->actingAs($client)->postJson(route('admin.solicitudes.cotizacion.store'), [])->assertStatus(403);
        // The normal quotation permissions still apply: enabling a service does not grant new roles.
    }

    public function test_hospital_sidebar_hides_and_shows_quotations_after_service_changes(): void
    {
        \Spatie\Permission\Models\Permission::create(['name' => 'nutricionales_solicitudes_index', 'guard_name' => 'web']);
        $client = $this->fixture['client'];
        $client->givePermissionTo('nutricionales_solicitudes_index');
        $render = function () use ($client) {
            $this->actingAs($client);
            return view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 0])->render();
        };
        $quotationLink = 'href="'.route('admin.solicitudes.cotizacion.index').'"';
        $preparationLink = 'href="'.route('admin.solicitudes.index').'"';
        $this->assertStringContainsString($quotationLink, $render());
        $this->actingAs($this->fixture['manager']);
        $this->save(null, false, true)->assertOk();
        $html = $render();
        $this->assertStringNotContainsString($quotationLink, $html);
        $this->assertStringContainsString($preparationLink, $html);
        $this->actingAs($this->fixture['manager']);
        $this->save($this->fixture['hospital'], true)->assertOk();
        $this->assertStringContainsString($quotationLink, $render());
    }
}
