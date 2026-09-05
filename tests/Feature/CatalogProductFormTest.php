<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CatalogProductFormTest extends TestCase
{
    public function test_new_supply_renders_warehouse_options_grouped_by_laboratory(): void
    {
        $name = 'Almacen "Norte" </script> & almacen';
        $laboratories = collect([
            (object) ['id' => 4, 'nombre' => 'Central Norte', 'warehouses' => collect([
                (object) ['id' => 7, 'name' => $name],
                (object) ['id' => 8, 'name' => 'Segundo almacen'],
            ])],
            (object) ['id' => 9, 'nombre' => 'Central Sur', 'warehouses' => collect([
                (object) ['id' => 12, 'name' => 'Almacen Sur'],
            ])],
        ]);

        $html = $this->renderForm('insumos', $laboratories);

        $this->assertStringContainsString('Nuevo insumo', $html);
        $this->assertStringContainsString('Guardar insumo', $html);
        $this->assertStringContainsString('id="supply_laboratory_id"', $html);
        $this->assertStringContainsString('id="supply_warehouse_id"', $html);
        $this->assertSame([
            4 => [['id' => 7, 'name' => $name], ['id' => 8, 'name' => 'Segundo almacen']],
            9 => [['id' => 12, 'name' => 'Almacen Sur']],
        ], $this->warehouseOptions($html));
        $this->assertStringNotContainsString($name, $html);
    }

    public function test_new_supply_renders_without_registered_laboratories(): void
    {
        $html = $this->renderForm('insumos');

        $this->assertSame([], $this->warehouseOptions($html));
        $this->assertMatchesRegularExpression('/id="supply_laboratory_id"[^>]*disabled/s', $html);
        $this->assertStringContainsString('Guardar insumo', $html);
    }

    public function test_other_product_categories_still_render_without_supply_selectors(): void
    {
        foreach (['oncologicos', 'nutricionales', 'antibioticos'] as $category) {
            $html = $this->renderForm($category);

            $this->assertStringContainsString('Guardar producto', $html);
            $this->assertStringContainsString('name="generic_description"', $html);
            $this->assertStringNotContainsString('id="supply_laboratory_id"', $html);
            $this->assertStringNotContainsString('const warehousesByLaboratory', $html);
        }
    }

    private function renderForm(string $category, ?Collection $laboratories = null): string
    {
        // Exercise the actual form and its script without the database-backed admin shell.
        $source = str_replace(
            ['<x-admin-layout>', '</x-admin-layout>'],
            ['', "@stack('js')"],
            file_get_contents(resource_path('views/admin/catalogo-listas/product-form.blade.php'))
        );

        return Blade::render($source, [
            'category' => $category,
            'categories' => CatalogoListasController::CATEGORIES,
            'mode' => 'catalogo',
            'concentrationUnit' => 'ml',
            'laboratories' => $laboratories ?? collect(),
            'diluents' => collect(),
            'routes' => collect(),
            'errors' => new ViewErrorBag(),
        ]);
    }

    private function warehouseOptions(string $html): array
    {
        $this->assertSame(1, preg_match('/const warehousesByLaboratory = (.*?);/s', $html, $matches));

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }
}
