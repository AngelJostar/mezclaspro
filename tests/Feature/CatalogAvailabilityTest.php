<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use App\Http\Controllers\Admin\Oncologicos\MedicineController;
use App\Http\Controllers\Admin\Nutricionales\NutriMedicineListController;
use App\Models\Nutricionales\NutriMedicineList;
use App\Services\PriceListDocumentConfigurationService;
use App\Services\PriceListWarehouseConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\CatalogAvailabilityData;
use Tests\Fixtures\SupplyCatalog;
use Tests\TestCase;

class CatalogAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CatalogAvailabilityData::seed();
        $this->actingAs(CatalogAvailabilityData::user());
    }

    public function test_status_is_global_reversible_and_preserves_every_list_price(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $table = $category === 'nutricionales' ? 'nutri_medicine_list_items' : 'medicine_list_presentation';
            $before = DB::table($table)->get()->toArray();
            foreach ([false, true] as $active) {
                $this->patchJson($this->url($category, $id), ['is_available' => $active])
                    ->assertOk()->assertJsonPath('is_available', $active);
                $catalogs = $this->invoke('editorCatalogs', $category);
                $this->assertSame($active, $catalogs->flatMap->presentations->contains('id', $id));
                foreach ($category === 'nutricionales' ? [1, 2] : [$id, $id + 2] as $listId) {
                    $items = $this->invoke('priceListItems', $category, $listId);
                    $this->assertCount(1, $items);
                    $this->assertSame($active, $items->first()->is_available);
                }
                $this->assertEquals($before, DB::table($table)->get()->toArray());
                $rows = app(CatalogoListasController::class)->catalog($category)->getData()['rows'];
                $this->assertSame($active, $rows->firstWhere('status_url', $this->url($category, $id))->is_available);
            }
        }
    }

    public function test_new_list_validation_rejects_a_product_disabled_after_opening_the_editor(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $this->patchJson($this->url($category, $id), ['is_available' => false])->assertOk();
            try {
                $this->invoke('validateUnifiedSelections', collect([$category => collect([['presentation_id' => $id]])]), 'New list');
                $this->fail('Inactive product was accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('category_items.'.$category, $exception->errors());
            }
        }
    }

    public function test_status_endpoint_checks_role_category_and_boolean_input(): void
    {
        $route = Route::getRoutes()->getByName('admin.catalogo-listas.products.status');
        $this->assertContains('role:Super Admin', $route->gatherMiddleware());
        $this->patchJson($this->url('oncologicos', 2), ['is_available' => false])->assertNotFound();
        $this->patchJson($this->url('insumos', 1), ['is_available' => false])->assertNotFound();
        $this->patchJson($this->url('oncologicos', 1), [])->assertUnprocessable();
        $this->patchJson($this->url('oncologicos', 1), ['is_available' => 'invalid'])->assertUnprocessable();
        $this->actingAs(CatalogAvailabilityData::user('Admin'));
        $this->patchJson($this->url('oncologicos', 1), ['is_available' => false])->assertForbidden();
        auth()->logout();
        $this->patchJson($this->url('oncologicos', 1), ['is_available' => false])->assertUnauthorized();
        $this->assertSame('1', DB::table('medicine_presentations')->where('id', 1)->value('is_available'));
    }

    public function test_catalog_has_state_before_product_for_all_three_categories(): void
    {
        foreach (['oncologicos', 'antibioticos', 'nutricionales'] as $category) {
            $html = SupplyCatalog::render(app(CatalogoListasController::class)->catalog($category)->getData());
            $this->assertStringContainsString('role="switch"', $html);
            $this->assertStringContainsString('aria-checked="true"', $html);
            $this->assertLessThan(strpos($html, '>Producto</span>'), strpos($html, '>Estado</span>'));
        }
    }

    public function test_editing_lists_with_only_inactive_products_keeps_the_original_links_and_prices(): void
    {
        $document = $this->mock(PriceListDocumentConfigurationService::class);
        $document->shouldReceive('syncSubdistributor')->andReturnNull();
        $warehouse = $this->mock(PriceListWarehouseConfigurationService::class);
        $warehouse->shouldReceive('resolve')->andReturn([]);

        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $this->patchJson($this->url($category, $id), ['is_available' => false])->assertOk();
            $table = $category === 'nutricionales' ? 'nutri_medicine_list_items' : 'medicine_list_presentation';
            $before = DB::table($table)->get()->toArray();
            $request = Request::create('/test', 'PUT', ['name' => 'Updated '.$category,
                'from_catalogo_listas' => $category, 'charge_by' => 'frasco']);
            $request->setLaravelSession(app('session.store'));
            app()->instance('request', $request);
            $response = $category === 'nutricionales'
                ? app(NutriMedicineListController::class)->update($request, NutriMedicineList::findOrFail($id), $document, $warehouse)
                : app(MedicineController::class)->update($request, (string) $id, $document, $warehouse);
            $this->assertFalse(session()->has('errors'), json_encode(session('errors')?->getBag('default')->all()));
            $this->assertSame(302, $response->getStatusCode());
            $this->assertEquals($before, DB::table($table)->get()->toArray());
        }
    }

    public function test_nutrition_legacy_store_and_update_reject_inactive_ids(): void
    {
        $this->patchJson($this->url('nutricionales', 1), ['is_available' => false])->assertOk();
        // Keep the same item count as the old editor while attempting to submit a disabled ID.
        DB::table('nutrition_medicine_presentations')->insert(['id' => 2, 'nutrition_medicine_catalog_id' => 1, 'is_available' => 1]);
        foreach (['store', 'update'] as $method) {
            $request = Request::create('/test', 'POST', ['name' => 'New nutrition', 'items' => [
                ['nutrition_medicine_presentation_id' => 1, 'precio_ml' => 15],
            ]]);
            try {
                $args = [$request];
                if ($method === 'update') $args[] = NutriMedicineList::findOrFail(1);
                $args[] = app(PriceListDocumentConfigurationService::class);
                $args[] = app(PriceListWarehouseConfigurationService::class);
                app(NutriMedicineListController::class)->{$method}(...$args);
                $this->fail('Inactive nutrition product was accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('items.0.nutrition_medicine_presentation_id', $exception->errors());
            }
        }
    }

    public function test_editing_active_prices_preserves_inactive_products_until_reactivation(): void
    {
        $document = $this->mock(PriceListDocumentConfigurationService::class);
        $document->shouldReceive('syncSubdistributor')->andReturnNull();
        $warehouse = $this->mock(PriceListWarehouseConfigurationService::class);
        $warehouse->shouldReceive('resolve')->andReturn([]);
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 1] as $category => $id) {
            $nutrition = $category === 'nutricionales';
            $presentationTable = $nutrition ? 'nutrition_medicine_presentations' : 'medicine_presentations';
            DB::table($presentationTable)->insert(['id' => 10 + $id,
                ($nutrition ? 'nutrition_medicine_catalog_id' : 'catalog_id') => $id, 'is_available' => 1]);
            $this->patchJson($this->url($category, $id), ['is_available' => false])->assertOk();
            $table = $nutrition ? 'nutri_medicine_list_items' : 'medicine_list_presentation';
            $listKey = $nutrition ? 'nutri_medicine_list_id' : 'medicine_list_id';
            $original = DB::table($table)->where($listKey, $id)->first();
            $data = ['name' => 'Mixed '.$category, 'from_catalogo_listas' => $category, 'charge_by' => 'frasco'];
            $data[$nutrition ? 'items' : 'medicamentos'] = [$nutrition
                ? ['nutrition_medicine_presentation_id' => 10 + $id, 'precio_ml' => 20, 'selected' => true]
                : ['catalog_id' => $id, 'presentation_id' => 10 + $id, 'precio' => 200, 'selected' => true]];
            $request = Request::create('/test', 'PUT', $data);
            $request->setLaravelSession(app('session.store'));
            app()->instance('request', $request);
            if ($nutrition) app(NutriMedicineListController::class)->update($request, NutriMedicineList::findOrFail($id), $document, $warehouse);
            else app(MedicineController::class)->update($request, (string) $id, $document, $warehouse);
            $this->assertFalse(session()->has('errors'), json_encode(session('errors')?->getBag('default')->all()));
            $this->assertSame(2, DB::table($table)->where($listKey, $id)->count());
            $this->assertEquals($original, DB::table($table)->where('id', $original->id)->first());
            $this->patchJson($this->url($category, $id), ['is_available' => true])->assertOk();
            $items = $this->invoke('priceListItems', $category, $id);
            $this->assertTrue($items->every(fn ($item) => $item->is_available));
        }
    }

    private function invoke(string $method, ...$args)
    {
        return (new \ReflectionMethod(CatalogoListasController::class, $method))
            ->invoke(app(CatalogoListasController::class), ...$args);
    }

    private function url(string $category, int $id): string
    {
        return route('admin.catalogo-listas.products.status', ['category' => $category, 'presentation' => $id]);
    }
}
