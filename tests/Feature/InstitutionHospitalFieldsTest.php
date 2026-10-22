<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InstitucionController;
use App\Models\Hospital;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Tests\Fixtures\InstitutionHospitalFields;
use Tests\TestCase;

class InstitutionHospitalFieldsTest extends TestCase
{
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = InstitutionHospitalFields::seed();
        $this->actingAs($this->fixture['manager']);
    }

    private function url(string $field): string
    {
        return route('admin.instituciones.hospitals.fields.edit', [$this->fixture['institution'], $this->fixture['hospital'], $field]);
    }

    private function save(string $field, array $values, array $extra = [])
    {
        $version = $this->getJson($this->url($field))->assertOk()->json('version');

        return $this->patchJson($this->url($field), ['values' => $values, 'version' => $version, ...$extra]);
    }

    public function test_editors_load_only_the_corresponding_values_and_available_options(): void
    {
        $this->getJson($this->url('services'))->assertOk()
            ->assertJsonPath('title', 'Editar mezclas habilitadas')
            ->assertJsonPath('selected', ['oncology', 'nutrition'])
            ->assertJsonCount(3, 'options');
        $this->getJson($this->url('routes'))->assertOk()
            ->assertJsonPath('selected', [(string) $this->fixture['route']->id]);
        $this->getJson($this->url('sellers'))->assertOk()
            ->assertJsonPath('selected', [(string) $this->fixture['seller']->id])
            ->assertJsonCount(2, 'options')->assertDontSee('Cliente de prueba');
    }

    public function test_service_edits_override_legacy_defaults_without_removing_price_lists_or_other_data(): void
    {
        $this->save('services', ['antibiotics'], ['name' => 'Unwanted name'])->assertOk();
        $hospital = $this->fixture['hospital']->fresh();
        $this->assertFalse($hospital->offersService('oncology'));
        $this->assertFalse($hospital->offersService('nutrition'));
        $this->assertTrue($hospital->offersService('antibiotics'));
        $this->assertSame(14, $hospital->onco_medicine_list_id);
        $this->assertSame(25, $hospital->nutri_medicine_list_id);
        $this->assertSame('Hospital de prueba', $hospital->name);
        $this->assertFalse(Hospital::offeringService('oncology')->whereKey($hospital)->exists());
        $this->assertTrue(Hospital::offeringService('antibiotics')->whereKey($hospital)->exists());
        $this->assertCount(1, $hospital->distributionRoutes);
        $this->assertCount(1, $hospital->salespeople);
        $this->save('services', [])->assertOk();
        $this->getJson($this->url('services'))->assertJsonPath('selected', []);
    }

    public function test_route_edits_preserve_other_stops_and_existing_completion(): void
    {
        ['hospital' => $hospital, 'route' => $route, 'nextRoute' => $nextRoute, 'otherHospital' => $other] = $this->fixture;
        $this->save('routes', [$route->id, $nextRoute->id])->assertOk();
        $this->assertDatabaseHas('distribution_route_hospital', [
            'hospital_id' => $hospital->id, 'distribution_route_id' => $route->id,
            'stop_order' => 4, 'completed_at' => '2026-10-20 12:00:00',
        ]);
        $this->assertDatabaseHas('distribution_route_hospital', [
            'hospital_id' => $hospital->id, 'distribution_route_id' => $nextRoute->id, 'stop_order' => 8,
        ]);
        $this->save('routes', [])->assertOk();
        $this->assertCount(0, $hospital->distributionRoutes()->get());
        $this->assertDatabaseHas('distribution_route_hospital', [
            'hospital_id' => $other->id, 'distribution_route_id' => $nextRoute->id, 'stop_order' => 7,
        ]);
    }

    public function test_sellers_can_be_replaced_or_cleared_but_non_sales_users_cannot_be_assigned(): void
    {
        ['hospital' => $hospital, 'seller' => $seller, 'nextSeller' => $next, 'client' => $client] = $this->fixture;
        $this->save('sellers', [$seller->id, $next->id])->assertOk();
        $this->save('sellers', [$client->id])->assertUnprocessable()->assertJsonValidationErrors('values');
        $this->assertCount(2, $hospital->salespeople()->get());
        $this->save('sellers', [$next->id])->assertOk();
        $this->assertSame([$next->id], $hospital->salespeople()->get()->modelKeys());
        $this->save('sellers', [$seller->id])->assertUnprocessable();
        $this->save('sellers', [])->assertOk();
        $this->assertCount(0, $hospital->salespeople()->get());
    }

    public function test_invalid_and_stale_submissions_do_not_overwrite_data(): void
    {
        $version = $this->getJson($this->url('services'))->json('version');
        $this->save('services', ['unrecognized'])->assertUnprocessable();
        $this->save('services', ['oncology', 'oncology'])->assertUnprocessable();
        $this->save('routes', [999999])->assertUnprocessable();
        $this->patchJson($this->url('services'), ['version' => $version])->assertUnprocessable();
        $this->save('services', ['nutrition'])->assertOk();
        $this->patchJson($this->url('services'), ['values' => ['oncology'], 'version' => $version])->assertConflict();
        $this->getJson($this->url('services'))->assertJsonPath('selected', ['nutrition']);
    }

    public function test_permissions_and_institution_membership_are_enforced(): void
    {
        $other = Institucion::create(['nombre' => 'Otra institución']);
        $url = route('admin.instituciones.hospitals.fields.edit', [$other, $this->fixture['hospital'], 'services']);
        $this->getJson($url)->assertNotFound();
        $this->patchJson($url, ['values' => []])->assertNotFound();
        $this->actingAs($this->fixture['client']);
        foreach (['services', 'routes', 'sellers'] as $field) {
            $this->getJson($this->url($field))->assertForbidden();
            $this->patchJson($this->url($field), ['values' => []])->assertForbidden();
        }
    }

    public function test_page_renders_pencils_next_to_each_requested_column(): void
    {
        $view = app(InstitucionController::class)->hospitales(Request::create('/'), $this->fixture['institution']);
        $html = $view->render();
        $this->assertStringContainsString('>Mezclas habilitadas</th>', $html);
        $this->assertSame(3, substr_count($html, 'data-hospital-field-edit'));
        $this->assertStringContainsString('data-hospital-field-dialog', $html);
        $this->assertStringContainsString('Guardar cambios', $html);
    }
}
