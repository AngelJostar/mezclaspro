<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InstitucionController;
use App\Services\InstitutionReportTemplateService;
use App\View\Components\AdminLayout;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Mockery;
use Tests\TestCase;
use Tests\Fixtures\SolicitudValidations;

class AdministrationCarouselTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(SolicitudValidations::boot());
        app()->bind(AdminLayout::class, fn () => new class extends Component {
            public function render(): string
            {
                return '<main>{{ $slot }}</main>';
            }
        });
        view()->share('errors', new ViewErrorBag);
    }

    public function test_new_sections_stay_inside_reports_without_loading_billing_or_report_data(): void
    {
        $templates = Mockery::mock(InstitutionReportTemplateService::class);
        $templates->shouldNotReceive('all');

        foreach (['conciliacion', 'pagos'] as $section) {
            $request = Request::create('/admin/instituciones-reportes', 'GET', ['seccion' => $section]);
            app()->instance('request', $request);
            $view = app(InstitucionController::class)->reportes($request, $templates);

            $this->assertSame('admin.instituciones.administration-section', $view->name());
            $this->assertSame($section, $view->getData()['administrationSection']);
            $html = $view->render();
            $this->assertStringContainsString('>Panel Administrativo</h1>', $html);
            $this->assertStringContainsString('Sección pendiente de configurar.', $html);
            $this->assertStringNotContainsString('instituciones-facturacion', $html);
            $this->assertSame(1, preg_match_all('/aria-current="page"\s+class=/', $html));
        }
    }

    public function test_default_and_unknown_sections_keep_existing_report_controls(): void
    {
        foreach ([
            '2024_01_10_035338_create_hospitals_table.php',
            '2026_02_06_201859_create_clientes_table.php',
            '2026_02_06_214504_create_cliente_hospital_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        $templates = Mockery::mock(InstitutionReportTemplateService::class);
        foreach (['all', 'customTemplates', 'publishedCustomTemplates', 'catalogParameters', 'customDataSources'] as $method) {
            $templates->shouldReceive($method)->andReturn([]);
        }

        foreach ([[], ['seccion' => 'desconocida'], ['seccion' => ['pagos']]] as $query) {
            $request = Request::create('/admin/instituciones-reportes', 'GET', $query);
            app()->instance('request', $request);
            $view = app(InstitucionController::class)->reportes($request, $templates);
            $this->assertSame('admin.instituciones.reportes', $view->name());
            $html = $view->render();
            $this->assertStringContainsString('>Panel Administrativo</h1>', $html);
            $this->assertStringNotContainsString('>Reportes de instituciones</h1>', $html);
            $this->assertStringContainsString('Crear nuevo reporte', $html);
            $this->assertStringContainsString('name="daily_from"', $html);
            $this->assertStringContainsString('name="search"', $html);
            $this->assertStringContainsString('administration-carousel', $html);
        }
    }

    public function test_carousel_keeps_report_filters_but_resets_pagination(): void
    {
        app()->instance('request', Request::create('/admin/instituciones-reportes', 'GET', [
            'search' => 'Institucion ejemplo',
            'daily_from' => '2026-09-01',
            'daily_to' => '2026-09-30',
            'seccion' => 'pagos',
            'page' => 8,
        ]));
        $html = view('admin.instituciones.partials.administration-carousel', [
            'administrationSection' => 'pagos',
        ])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $links = (new \DOMXPath($dom))->query('//nav//a');
        $this->assertCount(4, $links);
        foreach ($links as $index => $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertSame('Institucion ejemplo', $query['search']);
            if ($index === 2) {
                $this->assertStringContainsString('/instituciones-reportes/facturacion', $link->getAttribute('href'));
                $this->assertArrayNotHasKey('daily_from', $query);
            } else {
                $this->assertSame('2026-09-01', $query['daily_from']);
                $this->assertSame('2026-09-30', $query['daily_to']);
            }
            $this->assertArrayNotHasKey('page', $query);
            $this->assertSame($index === 3 ? 'page' : '', $link->getAttribute('aria-current'));
        }
    }
}
