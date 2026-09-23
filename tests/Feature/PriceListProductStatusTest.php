<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use App\Http\Controllers\Admin\Nutricionales\NutriMedicineListController;
use App\Http\Controllers\Admin\Oncologicos\MedicineController;
use App\Models\Nutricionales\NutriMedicineList;
use App\Services\PriceListDocumentConfigurationService;
use App\Services\PriceListWarehouseConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\CatalogAvailabilityData as Fixture;
use Tests\TestCase;

class PriceListProductStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Fixture::seed();
        $this->actingAs(Fixture::user());
    }

    private function url(string $category, int $list, int $presentation): string
    {
        return route('admin.catalogo-listas.lists.products.status', compact('category', 'list', 'presentation'));
    }

    public function test_switch_is_independent_per_list_in_all_categories_and_preserves_prices(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $nutrition = $category === 'nutricionales';
            $table = $nutrition ? 'nutri_medicine_list_items' : 'medicine_list_presentation';
            $listKey = $nutrition ? 'nutri_medicine_list_id' : 'medicine_list_id';
            $catalogTable = $nutrition ? 'nutrition_medicine_presentations' : 'medicine_presentations';
            $before = (array) DB::table($table)->where($listKey, $id)->first();
            $other = DB::table($table)->where($listKey, $nutrition ? 2 : $id + 2)->first();
            foreach ([false, false, true] as $active) {
                $this->patchJson($this->url($category, $id, $id), ['is_active' => $active, 'is_available' => false, 'precio' => 0])
                    ->assertOk()->assertJsonPath('is_active', $active);
                $after = (array) DB::table($table)->where($listKey, $id)->first();
                $this->assertSame($active, (bool) $after['is_active']);
                $this->assertEquals(array_diff_key($before, array_flip(['is_active', 'updated_at'])),
                    array_diff_key($after, array_flip(['is_active', 'updated_at'])));
                $this->assertEquals($other, DB::table($table)->where('id', $other->id)->first());
                $this->assertTrue((bool) DB::table($catalogTable)->where('id', $id)->value('is_available'));
                $items = (new \ReflectionMethod(CatalogoListasController::class, 'priceListItems'))
                    ->invoke(app(CatalogoListasController::class), $category, $id);
                $this->assertSame($active, $items->first()->list_is_active);
                $catalogs = (new \ReflectionMethod(CatalogoListasController::class, 'editorCatalogs'))
                    ->invoke(app(CatalogoListasController::class), $category);
                $this->assertTrue($catalogs->flatMap->presentations->contains('id', $id));
            }
        }
    }

    public function test_catalog_block_cannot_be_bypassed_and_reactivation_keeps_each_list_preference(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $this->patchJson($this->url($category, $id, $id), ['is_active' => false])->assertOk();
            $globalUrl = route('admin.catalogo-listas.products.status', ['category' => $category, 'presentation' => $id]);
            $this->patchJson($globalUrl, ['is_available' => false])->assertOk();
            $this->patchJson($this->url($category, $id, $id), ['is_active' => true])->assertUnprocessable()->assertJsonValidationErrors('is_active');
            $html = Fixture::renderList($category, $id);
            $this->assertStringContainsString('disabled', $html);
            $this->assertStringContainsString('aria-checked="false"', $html);
            $this->patchJson($globalUrl, ['is_available' => true])->assertOk();
            $html = Fixture::renderList($category, $id);
            $this->assertStringContainsString('aria-checked="false"', $html);
            $this->assertStringContainsString('data-active="true"', $html);
            $this->assertStringContainsString('aria-checked="true"', Fixture::renderList($category, $category === 'nutricionales' ? 2 : $id + 2));
        }
    }

    public function test_endpoint_validates_access_membership_category_and_explicit_boolean(): void
    {
        $this->patchJson($this->url('oncologicos', 1, 1), [])->assertUnprocessable();
        $this->patchJson($this->url('oncologicos', 1, 1), ['is_active' => 'invalid'])->assertUnprocessable();
        foreach ([['oncologicos', 2, 1], ['oncologicos', 1, 2], ['oncologicos', 1, 999], ['nutricionales', 99, 1], ['insumos', 1, 1]] as $args) {
            $this->patchJson($this->url(...$args), ['is_active' => false])->assertNotFound();
        }
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->actingAs(Fixture::user($role));
            $this->patchJson($this->url('oncologicos', 1, 1), ['is_active' => false])->assertForbidden();
        }
        auth()->logout();
        $this->patchJson($this->url('oncologicos', 1, 1), ['is_active' => false])->assertUnauthorized();
    }

    public function test_catalog_column_is_readonly_and_list_switch_is_immediately_to_its_right(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $html = Fixture::renderList($category, $id);
            $this->assertLessThan(strpos($html, '>Estado en Lista de Precios</th>'), strpos($html, '>Estado de Catalogo</th>'));
            $this->assertLessThan(strpos($html, '>Producto</th>'), strpos($html, '>Estado en Lista de Precios</th>'));
            $this->assertStringContainsString('data-price-list-status-form', $html);
            $this->assertStringNotContainsString('data-catalog-status-form', $html);
            $this->assertStringNotContainsString('name="is_available"', $html);
        }
    }

    public function test_nutrition_request_validation_rejects_list_inactive_products(): void
    {
        $hospital = (object) ['nutri_medicine_list_id' => 1];
        $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::findOrFail(1);
        $controller = app(\App\Http\Controllers\Admin\Nutricionales\SolicitudController::class);
        $method = new \ReflectionMethod($controller, 'obtenerPrecioMlHospitalPorPresentacion');
        $this->assertSame(12.34, $method->invoke($controller, $hospital, $presentation));
        $this->patchJson($this->url('nutricionales', 1, 1), ['is_active' => false])->assertOk();
        $this->expectException(\Exception::class);
        $method->invoke($controller, $hospital, $presentation);
    }

    public function test_editing_list_prices_does_not_reactivate_inactive_list_items(): void
    {
        $document = $this->mock(PriceListDocumentConfigurationService::class);
        $document->shouldReceive('syncSubdistributor')->andReturnNull();
        $warehouse = $this->mock(PriceListWarehouseConfigurationService::class);
        $warehouse->shouldReceive('resolve')->andReturn([]);
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $this->patchJson($this->url($category, $id, $id), ['is_active' => false])->assertOk();
            $nutrition = $category === 'nutricionales';
            $data = ['name' => 'Edited list', 'from_catalogo_listas' => $category, 'charge_by' => 'frasco'];
            $data[$nutrition ? 'items' : 'medicamentos'] = [$nutrition
                ? ['nutrition_medicine_presentation_id' => $id, 'precio_ml' => 20, 'selected' => true]
                : ['catalog_id' => $id, 'presentation_id' => $id, 'precio' => 200, 'selected' => true]];
            $request = Request::create('/test', 'PUT', $data);
            $request->setLaravelSession(app('session.store'));
            app()->instance('request', $request);
            if ($nutrition) app(NutriMedicineListController::class)->update($request, NutriMedicineList::findOrFail($id), $document, $warehouse);
            else app(MedicineController::class)->update($request, (string) $id, $document, $warehouse);
            $this->assertFalse(session()->has('errors'), json_encode(session('errors')?->getBag('default')->all()));
            $item = DB::table($nutrition ? 'nutri_medicine_list_items' : 'medicine_list_presentation')
                ->where($nutrition ? 'nutri_medicine_list_id' : 'medicine_list_id', $id)->first();
            $this->assertFalse((bool) $item->is_active);
            $this->assertEquals($nutrition ? 20 : 200, $nutrition ? $item->precio_ml : $item->precio);
        }
    }
}
