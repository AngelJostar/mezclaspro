<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogProductController;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\DiluentCreation;
use Tests\TestCase;

class DiluentCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DiluentCreation::seed();
    }

    private function request(array $overrides = []): Request
    {
        $request = Request::create('/admin/catalogo-listas/insumos/productos', 'POST', array_merge([
            'generic_description' => 'Diluyente de prueba nuevo', 'commercial_name' => 'Marca nueva',
            'concentration' => 500, 'presentation' => 'Bolsa 500 mL', 'stability_hours' => 24,
            'manufacturer' => 'Fabricante de prueba', 'laboratory_id' => 1, 'warehouse_id' => 1,
        ], $overrides), [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setLaravelSession(session()->driver());
        return $request;
    }

    public function test_modal_has_all_reference_fields_and_only_active_locations(): void
    {
        $view = app(CatalogProductController::class)->create(Request::create('/create', 'GET', ['modal' => 1]), 'insumos');
        $this->assertSame('admin.catalogo-listas.partials.diluent-form', $view->name());
        $html = $view->render();
        foreach (['generic_description', 'commercial_name', 'concentration', 'presentation', 'stability_hours',
            'manufacturer', 'laboratory_id', 'warehouse_id'] as $name) $this->assertStringContainsString('name="'.$name.'"', $html);
        $this->assertStringContainsString('Guardar diluyente', $html);
        $this->assertStringContainsString('Asignación a almacén', $html);
        $this->assertStringNotContainsString('Central inactiva', $html);
        $this->assertStringNotContainsString('Almacen inactivo', $html);
        $this->assertStringContainsString('data-diluent-locations', $html);
    }

    public function test_direct_create_link_opens_the_catalog_with_the_modal(): void
    {
        $response = app(CatalogProductController::class)->create(Request::create('/create'), 'insumos');
        $this->assertSame(route('admin.catalogo-listas.catalog', ['category' => 'insumos', 'nuevo_diluyente' => 1]), $response->getTargetUrl());
    }

    public function test_saves_all_fields_including_stability_and_returns_to_diluents(): void
    {
        $response = app(CatalogProductController::class)->store($this->request(), 'insumos');
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(route('admin.catalogo-listas.catalog', ['category' => 'insumos']), $response->getData(true)['redirect']);
        $presentation = DiluentPresentation::latest('id')->first();
        $this->assertSame(24, $presentation->stability_hours);
        $this->assertSame(500.0, $presentation->volume_ml);
        $this->assertSame('Marca nueva', $presentation->denominacion_comercial);
        $this->assertSame('Fabricante de prueba', $presentation->fabricante);
        $this->assertEquals(1, $presentation->laboratory_id);
        $this->assertEquals(1, $presentation->warehouse_id);
        $this->assertSame(0.0, $presentation->stock_actual);
        $this->assertSame('Diluyente de prueba nuevo', $presentation->diluent->denominacion_generica);
        $this->assertNull(DiluentPresentation::find(1)->stability_hours);
    }

    public function test_invalid_fields_and_location_pairs_do_not_write_any_records(): void
    {
        foreach ([['generic_description' => '  '], ['concentration' => 0], ['stability_hours' => 0],
            ['stability_hours' => 2.5], ['stability_hours' => 8761], ['warehouse_id' => 2],
            ['warehouse_id' => 3], ['laboratory_id' => 4, 'warehouse_id' => 4]] as $changes) {
            try {
                app(CatalogProductController::class)->store($this->request($changes), 'insumos');
                $this->fail('Invalid diluent was saved.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
                $this->assertSame(2, Diluent::count());
                $this->assertSame(2, DiluentPresentation::count());
            }
        }
    }

    public function test_duplicate_presentations_roll_back_and_optional_manufacturer_can_be_empty(): void
    {
        $controller = app(CatalogProductController::class);
        $controller->store($this->request(['manufacturer' => '', 'generic_description' => '  Nuevo  ']), 'insumos');
        $this->assertNull(DiluentPresentation::latest('id')->first()->fabricante);
        $this->assertSame('Nuevo', Diluent::latest('id')->first()->denominacion_generica);
        try {
            $controller->store($this->request(['generic_description' => 'Nuevo']), 'insumos');
            $this->fail('Duplicate accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('presentation', $e->errors());
            $this->assertSame(3, DiluentPresentation::count());
        }
    }

    public function test_location_names_are_escaped_and_empty_centrals_disable_saving(): void
    {
        DB::table('warehouses')->where('id', 1)->update(['name' => '</script><script>alert(1)</script>']);
        $view = app(CatalogProductController::class)->create(Request::create('/create', 'GET', ['modal' => 1]), 'insumos');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $view->render());
        DB::table('laboratories')->update(['activo' => false]);
        $empty = app(CatalogProductController::class)->create(Request::create('/create', 'GET', ['modal' => 1]), 'insumos')->render();
        $this->assertMatchesRegularExpression('/data-save-diluent\s+disabled/', $empty);
        $this->assertStringContainsString('No hay subalmacenes activos', $empty);
    }
}
