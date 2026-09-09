<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use App\Http\Controllers\Admin\ConsumableCatalogController;
use App\Models\ConsumableItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Tests\Fixtures\SupplyCatalog;
use Tests\TestCase;

class SupplyCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SupplyCatalog::seed();
    }

    public function test_entry_and_invalid_sections_default_to_diluents_only(): void
    {
        foreach ([null, 'diluyentes', 'otra-seccion'] as $section) {
            $data = SupplyCatalog::viewData($section);
            $this->assertSame('insumos', $data['category']);
            $this->assertSame('diluyentes', $data['supplySection']);
            $this->assertCount(2, $data['rows']);
            $this->assertEmpty($data['consumables']);
            $html = SupplyCatalog::render($data);
            $this->assertStringContainsString('Nuevo diluyente', $html);
            $this->assertStringContainsString('Buscar diluyente...', $html);
            $this->assertStringContainsString('CLORURO DE SODIO', $html);
            $this->assertStringNotContainsString('Jeringa', $html);
            $this->assertStringNotContainsString('Catalogo - Insumos', $html);
            $this->assertStringNotContainsString('Consulta los insumos registrados', $html);
        }
    }

    public function test_consumables_show_their_presentations_without_mixing_diluent_inventory(): void
    {
        $data = SupplyCatalog::viewData('consumibles');
        $this->assertSame('insumos', $data['category']);
        $this->assertSame('consumibles', $data['supplySection']);
        $this->assertEmpty($data['rows']);
        $this->assertCount(2, $data['consumables']);
        $html = SupplyCatalog::render($data);
        foreach (['Nuevo consumible', 'Buscar consumible...', 'Jeringa 10 ml', 'Jeringa 20 ml', 'Guante', 'Sin presentaciones'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('CLORURO DE SODIO', $html);
        $this->assertStringNotContainsString('Consumible inactivo', $html);
        $this->assertStringContainsString(route('admin.consumables.catalog.create'), $html);
        $this->assertStringContainsString(route('admin.consumables.catalog.edit', 1), $html);
    }

    public function test_empty_sections_keep_the_switch_and_correct_creation_action(): void
    {
        DB::table('diluent_presentations')->delete();
        ConsumableItem::query()->update(['is_active' => false]);
        $this->assertStringContainsString('No hay diluyentes registrados', SupplyCatalog::render(SupplyCatalog::viewData()));
        $html = SupplyCatalog::render(SupplyCatalog::viewData('consumibles'));
        $this->assertStringContainsString('No hay consumibles registrados.', $html);
        $this->assertStringContainsString('Nuevo consumible', $html);
        $this->assertStringContainsString('aria-label="Tipo de insumo"', $html);
    }

    public function test_entry_link_resets_to_diluents_and_legacy_consumables_link_opens_the_switch(): void
    {
        $controller = app(CatalogoListasController::class);
        $this->assertSame(route('admin.catalogo-listas.catalog', ['category' => 'insumos']),
            $controller->index(Request::create('/', 'GET', ['category' => 'insumos']))->getTargetUrl());
        $this->assertSame($this->consumablesUrl(), $controller->catalog('consumibles')->getTargetUrl());
        SupplyCatalog::viewData('consumibles');
        $this->assertSame('diluyentes', SupplyCatalog::viewData()['supplySection']);
    }

    public function test_create_update_and_cancel_return_to_consumables(): void
    {
        $controller = app(ConsumableCatalogController::class);
        $request = Request::create('/', 'POST', [
            'name' => 'Equipo de prueba', 'unit' => 'pieza',
            'presentations' => [['presentation' => 'Caja de prueba', 'commercial_name' => 'Marca', 'manufacturer' => 'Fabricante']],
        ]);
        $this->assertSame($this->consumablesUrl(), $controller->store($request)->getTargetUrl());
        $item = ConsumableItem::where('name', 'Equipo de prueba')->firstOrFail();
        $this->assertCount(1, $item->catalogPresentations);
        $request->merge(['name' => 'Equipo actualizado']);
        $request->merge(['presentations' => [array_merge($request->input('presentations.0'), ['id' => $item->catalogPresentations->first()->id])]]);
        $this->assertSame($this->consumablesUrl(), $controller->update($request, $item)->getTargetUrl());
        $this->assertSame('Equipo actualizado', $item->fresh()->name);
        $this->assertCount(1, $item->fresh()->catalogPresentations);
        $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '',
            file_get_contents(resource_path('views/admin/catalogo-listas/consumable-form.blade.php')));
        $html = Blade::render($source, ['item' => $item, 'mode' => 'edit', 'errors' => new ViewErrorBag()]);
        $this->assertStringContainsString('href="'.e($this->consumablesUrl()).'"', $html);
    }

    public function test_other_categories_do_not_show_the_supply_switch(): void
    {
        $data = SupplyCatalog::viewData();
        $data['category'] = 'oncologicos';
        $data['rows'] = collect();
        $html = SupplyCatalog::render($data);
        $this->assertStringNotContainsString('aria-label="Tipo de insumo"', $html);
        $this->assertStringContainsString('Nuevo producto', $html);
    }

    private function consumablesUrl(): string
    {
        return route('admin.catalogo-listas.catalog', ['category' => 'insumos', 'tipo_insumo' => 'consumibles']);
    }
}
