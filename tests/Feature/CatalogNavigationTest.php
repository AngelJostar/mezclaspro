<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use Tests\TestCase;

class CatalogNavigationTest extends TestCase
{
    public function test_consumables_and_diluents_menus_are_absent_from_all_catalog_navigation_modes(): void
    {
        $categories = ['todos' => ['label' => 'Todos']] + CatalogoListasController::CATEGORIES;

        foreach (array_keys($categories) as $category) {
            foreach ([null, 'catalogo', 'listas'] as $mode) {
                $html = view('admin.catalogo-listas.partials.section-nav', compact('categories', 'category', 'mode'))->render();

                $this->assertStringNotContainsString('Consumibles', $html);
                $this->assertStringNotContainsString('Diluyentes', $html);
                foreach (['Todos', 'Oncologicos', 'Nutricionales', 'Antibioticos', 'Insumos'] as $label) {
                    $this->assertStringContainsString($label, $html);
                }
            }
        }
    }

    public function test_embedded_catalog_navigation_also_omits_consumables_and_diluents(): void
    {
        $html = view('admin.catalogo-listas.partials.section-nav', [
            'categories' => CatalogoListasController::CATEGORIES,
            'category' => 'oncologicos',
            'embedded' => true,
        ])->render();

        $this->assertStringNotContainsString('Consumibles', $html);
        $this->assertStringNotContainsString(route('admin.catalogo-listas.catalog', ['category' => 'consumibles']), $html);
        $this->assertStringNotContainsString('Diluyentes', $html);
        $this->assertStringNotContainsString(route('admin.catalogo-listas.catalog', ['category' => 'diluyentes']), $html);
    }

    public function test_catalog_action_is_omitted_only_for_supplies(): void
    {
        $categories = ['todos' => ['label' => 'Todos']] + CatalogoListasController::CATEGORIES;

        foreach (array_keys($categories) as $category) {
            $html = view('admin.catalogo-listas.partials.section-nav', [
                'categories' => $categories, 'category' => $category, 'mode' => 'catalogo',
            ])->render();

            if ($category === 'insumos') {
                $this->assertStringNotContainsString('<span>Catalogo</span>', $html);
                $this->assertStringNotContainsString('<span>Listas de precios</span>', $html);
            } else {
                $this->assertStringContainsString('<span>Catalogo</span>', $html);
            }
        }
    }
}
