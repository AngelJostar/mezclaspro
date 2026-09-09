<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogoListasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\Fixtures\CatalogExportData;
use Tests\Fixtures\SupplyCatalog;
use Tests\TestCase;

class CatalogExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CatalogExportData::seed();
    }

    public function test_each_catalog_downloads_its_own_real_excel_file(): void
    {
        foreach ([
            ['todos', null, 5, 'J', ['Oncologicos', 'Nutricionales', 'Antibioticos', 'Insumos', 'Insumos']],
            ['oncologicos', null, 1, 'I', ['Producto oncologicos']],
            ['nutricionales', null, 1, 'I', ['Producto nutricional']],
            ['antibioticos', null, 1, 'I', ['Producto antibioticos']],
            ['insumos', null, 2, 'K', ['CLORURO DE SODIO 0.9%', 'GLUCOSA 5%']],
            ['insumos', 'consumibles', 3, 'E', ['Guante', 'Jeringa', 'Jeringa']],
        ] as [$category, $section, $count, $column, $names]) {
            $book = $this->download($category, $section);
            $sheet = $book->getActiveSheet();
            $this->assertSame($count + 1, $sheet->getHighestRow());
            $this->assertSame($column, $sheet->getHighestColumn());
            $this->assertSame($names, array_column($sheet->rangeToArray('A2:A'.($count + 1)), 0));
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:'.$column.($count + 1), $sheet->getAutoFilter()->getRange());
            $this->assertNotContains('Editar', $sheet->rangeToArray('A1:'.$column.'1')[0]);
            $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
            $book->disconnectWorksheets();
        }
    }

    public function test_prices_dates_zero_stock_and_identifiers_preserve_their_types(): void
    {
        $book = $this->download('oncologicos');
        $sheet = $book->getActiveSheet();
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F2')->getDataType());
        $this->assertSame(0.0, (float) $sheet->getCell('F2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('H2')->getDataType());
        $this->assertSame(1234.56, (float) $sheet->getCell('H2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('G2')->getDataType());
        $this->assertSame('01/01/2026', $sheet->getCell('G2')->getFormattedValue());
        $this->assertSame('01/02/2026', $sheet->getCell('I2')->getFormattedValue());
        $this->assertSame('250 mg', $sheet->getCell('B2')->getValue());
        $this->assertSame('48 h', $sheet->getCell('E2')->getFormattedValue());
        $book->disconnectWorksheets();

        $book = $this->download('insumos');
        $sheet = $book->getActiveSheet();
        $this->assertSame('001234', $sheet->getCell('H2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H2')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('J2')->getDataType());
        $this->assertSame(0.0, (float) $sheet->getCell('J2')->getValue());
        $this->assertSame('31/12/2027', $sheet->getCell('I2')->getFormattedValue());
        $book->disconnectWorksheets();
    }

    public function test_catalog_text_is_never_interpreted_as_a_formula(): void
    {
        DB::table('diluents')->where('id', 1)->update(['denominacion_generica' => '=1+1']);
        DB::table('diluent_presentations')->where('id', 1)->update(['lote' => '+001234', 'fabricante' => '@SUM(A1:A2)']);
        $book = $this->download('insumos');
        $sheet = $book->getActiveSheet();
        foreach (['A2' => '=1+1', 'E2' => '@SUM(A1:A2)', 'H2' => '+001234'] as $cell => $value) {
            $this->assertSame($value, $sheet->getCell($cell)->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($cell)->getDataType());
        }
        $book->disconnectWorksheets();
    }

    public function test_empty_catalogs_still_download_headers(): void
    {
        DB::table('medicine_presentations')->delete();
        DB::table('nutrition_medicine_presentations')->delete();
        DB::table('diluent_presentations')->delete();
        DB::table('consumable_items')->update(['is_active' => false]);
        foreach (['todos', 'oncologicos', 'nutricionales', 'antibioticos', 'insumos', 'consumibles'] as $section) {
            $book = $this->download($section === 'consumibles' ? 'insumos' : $section, $section);
            $this->assertSame(1, $book->getActiveSheet()->getHighestRow());
            $this->assertNotEmpty($book->getActiveSheet()->getCell('A1')->getValue());
            $book->disconnectWorksheets();
        }
    }

    public function test_download_links_follow_the_selected_category_and_supply_switch(): void
    {
        foreach (['todos', 'oncologicos', 'nutricionales', 'antibioticos', 'insumos'] as $category) {
            foreach (['diluyentes', 'consumibles'] as $section) {
                app()->instance('request', Request::create('/', 'GET', ['tipo_insumo' => $section]));
                $data = app(CatalogoListasController::class)->catalog($category)->getData();
                $html = SupplyCatalog::render($data);
                $params = ['category' => $category, ...($category === 'insumos' ? ['tipo_insumo' => $section] : [])];
                $this->assertStringContainsString('href="'.e(route('admin.catalogo-listas.catalog.export', $params)).'"', $html);
                $this->assertStringContainsString('Descargar', $html);
            }
        }
    }

    public function test_download_requires_the_same_access_as_the_catalog(): void
    {
        $routes = Route::getRoutes();
        $this->assertSame($routes->getByName('admin.catalogo-listas.catalog')->gatherMiddleware(),
            $routes->getByName('admin.catalogo-listas.catalog.export')->gatherMiddleware());
        $this->get(route('admin.catalogo-listas.catalog.export', ['category' => 'insumos']))
            ->assertRedirect(route('login'));
    }

    public function test_unknown_categories_are_rejected_and_invalid_supply_section_defaults_to_diluents(): void
    {
        $this->withoutMiddleware()->get(route('admin.catalogo-listas.catalog.export', ['category' => 'desconocida']))->assertNotFound();
        $book = $this->download('insumos', 'desconocida');
        $this->assertSame('Catalogo Diluyentes', $book->getActiveSheet()->getTitle());
        $book->disconnectWorksheets();
    }

    private function download(string $category, ?string $section = null)
    {
        $response = $this->withoutMiddleware()->get(route('admin.catalogo-listas.catalog.export', [
            'category' => $category, ...($section ? ['tipo_insumo' => $section] : []),
        ]));
        $response->assertOk();
        $name = $category === 'insumos' ? ($section === 'consumibles' ? 'consumibles' : 'diluyentes') : $category;
        $this->assertMatchesRegularExpression('/attachment; filename=catalogo_'.$name.'_\d{8}_\d{6}\.xlsx/',
            $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $this->assertSame('PK', file_get_contents($path, false, null, 0, 2));
            // The reader must not inherit the exporter's text-only string binder.
            Cell::setValueBinder(new DefaultValueBinder());

            return (new Xlsx())->load($path);
        } finally {
            unlink($path);
        }
    }
}
