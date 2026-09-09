<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ConsumableCatalogController;
use App\Models\ConsumableItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\SupplyCatalog;
use Tests\TestCase;

class ConsumableCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SupplyCatalog::seed();
    }

    public function test_direct_creation_opens_the_modal_over_the_consumables_catalog(): void
    {
        $response = app(ConsumableCatalogController::class)->create(Request::create('/create'));
        $this->assertSame(route('admin.catalogo-listas.catalog', [
            'category' => 'insumos', 'tipo_insumo' => 'consumibles', 'nuevo_consumible' => 1,
        ]), $response->getTargetUrl());
        $html = SupplyCatalog::render(SupplyCatalog::viewData('consumibles'));
        $this->assertStringContainsString('data-new-consumable="true"', $html);
        $this->assertStringContainsString('data-consumable-modal', $html);
        $this->assertStringNotContainsString('data-diluent-modal', $html);
    }

    public function test_modal_returns_the_creation_form_without_a_second_page_layout(): void
    {
        $html = app(ConsumableCatalogController::class)->create(Request::create('/create', 'GET', ['modal' => 1]))->render();
        $this->assertStringContainsString('data-new-consumable-form', $html);
        $this->assertStringContainsString('value="pieza"', $html);
        $this->assertStringContainsString('data-consumable-presentation-template', $html);
        $this->assertStringContainsString('Guardar consumible', $html);
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('name="lote"', $html);
    }

    public function test_json_save_creates_all_presentations_and_returns_to_the_correct_catalog(): void
    {
        $response = $this->store([
            'name' => 'Equipo de prueba', 'unit' => 'pieza',
            'presentations' => [
                ['presentation' => 'Caja con 100 piezas', 'commercial_name' => 'Marca prueba', 'manufacturer' => 'Fabricante prueba'],
                ['presentation' => 'Caja con 50 piezas', 'commercial_name' => null, 'manufacturer' => null],
            ],
        ]);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(route('admin.catalogo-listas.catalog', ['category' => 'insumos', 'tipo_insumo' => 'consumibles']), $response->getData(true)['redirect']);
        $item = ConsumableItem::where('name', 'Equipo de prueba')->firstOrFail();
        $this->assertSame('pieza', $item->unit);
        $this->assertTrue((bool) $item->is_active);
        $this->assertCount(2, $item->catalogPresentations);
        $this->assertSame('Marca prueba', $item->catalogPresentations[0]->commercial_name);
        $this->assertNull($item->catalogPresentations[1]->manufacturer);
        $this->assertSame(0, DB::table('consumable_lots')->count());
        $this->assertSame('Consumible creado correctamente.', session('success'));
    }

    public function test_invalid_fields_or_duplicate_names_do_not_create_partial_records(): void
    {
        $cases = [
            ['name' => 'Jeringa', 'unit' => 'pieza', 'presentations' => [['presentation' => 'Caja']]],
            ['name' => 'Nuevo', 'unit' => 'pieza', 'presentations' => []],
            ['name' => 'Nuevo', 'unit' => 'pieza', 'presentations' => [['presentation' => 'Valida'], ['presentation' => '']]],
            ['name' => 'Nuevo', 'unit' => '', 'presentations' => [['presentation' => 'Caja']]],
        ];
        foreach ($cases as $case) {
            try {
                $this->store($case);
                $this->fail('Invalid consumable was accepted.');
            } catch (ValidationException $error) {
                $this->assertNotEmpty($error->errors());
            }
            $this->assertSame(3, ConsumableItem::count());
            $this->assertSame(2, DB::table('consumable_catalog_presentations')->count());
        }
    }

    private function store(array $fields)
    {
        $request = Request::create('/store', 'POST', $fields, [], [], ['HTTP_ACCEPT' => 'application/json']);
        return app(ConsumableCatalogController::class)->store($request);
    }
}
