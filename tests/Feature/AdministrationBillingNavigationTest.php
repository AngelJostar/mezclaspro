<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InstitucionBillingController;
use App\Models\User;
use App\Support\AdministrationNavigation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\BillingNavigation;
use Tests\Fixtures\SolicitudValidations;
use Tests\TestCase;

class AdministrationBillingNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(SolicitudValidations::boot());
        $controller = Mockery::mock(InstitucionBillingController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('renderIndex')->andReturnUsing(fn (Request $request, string $section) => BillingNavigation::view($section));
        $controller->shouldReceive('movementLog')->andReturnUsing(fn () => BillingNavigation::view('movements'));
        $this->app->instance(InstitucionBillingController::class, $controller);
    }

    public function test_all_existing_billing_screens_are_inside_reports_with_two_levels_of_navigation(): void
    {
        foreach (AdministrationNavigation::billingSections(auth()->user()) as $section => $meta) {
            $url = route($meta['route']);
            $this->assertStringContainsString('/instituciones-reportes/facturacion', $url);
            $response = $this->get($url)->assertOk()->assertSee('Facturación');
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $headings = $xpath->query('//h1');
            $this->assertCount(1, $headings);
            $this->assertSame('Panel Administrativo', trim($headings[0]->textContent));
            $parent = $xpath->query('//nav[@aria-label="Secciones de administración"]//a[@aria-current="page"]');
            $this->assertCount(1, $parent);
            $this->assertSame('Facturación', trim($parent[0]->textContent));
            $tabs = $xpath->query('//nav[@aria-label="Secciones de facturación"]//a');
            $this->assertCount(4, $tabs);
            $panel = $xpath->query('//*[@data-billing-section="'.$section.'"]');
            $this->assertCount(1, $panel);
            $this->assertCount(1, $xpath->query('./nav[@aria-label="Secciones de facturación"]', $panel[0]));
            $this->assertCount(0, $xpath->query('.//nav[@aria-label="Secciones de administración"]', $panel[0]));
            $selected = $xpath->query('//nav[@aria-label="Secciones de facturación"]//a[@aria-current="page"]');
            $this->assertCount(1, $selected);
            $this->assertSame($url, $selected[0]->getAttribute('href'));
            if ($section === 'movements') {
                $response->assertSee('movement-search')->assertSee('Movimiento');
            } else {
                $response->assertSee('billing-requests-table-'.$section)->assertSee('Exportar a Excel')
                    ->assertSee(route('admin.instituciones.billing.expanded-export'));
            }
        }
    }

    public function test_old_links_redirect_to_the_same_section_and_keep_filters(): void
    {
        $query = ['search' => 'REM-25', 'date_from' => '2026-09-01', 'hospital_id' => 12, 'page' => 2];
        foreach (AdministrationNavigation::billingSections(auth()->user()) as $meta) {
            $this->get(route($meta['route'].'.legacy', $query))->assertRedirect(route($meta['route'], $query));
        }
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'facturacion', 'search' => 'REM-25']))
            ->assertRedirect(route('admin.instituciones.billing.index', ['search' => 'REM-25']));
    }

    public function test_sidebar_links_directly_to_reports_without_an_administration_submenu(): void
    {
        $this->get(route('admin.instituciones.billing.receivable'))->assertOk();
        $html = $this->sidebar();
        $this->assertStringNotContainsString("openMenu === 'facturacion'", $html);
        $this->assertStringNotContainsString("openMenu === 'administracion'", $html);
        $this->assertAdministrationLink($html, route('admin.instituciones.reportes'));
    }

    public function test_billing_only_user_keeps_access_without_gaining_report_or_other_billing_permissions(): void
    {
        $user = $this->generalUser(['menu.facturacion.receivable']);
        $this->actingAs($user);
        $this->assertFalse(AdministrationNavigation::canViewReports($user));
        $this->assertSame(['receivable'], array_keys(AdministrationNavigation::billingSections($user)));
        $response = $this->get(route('admin.instituciones.billing.receivable'))->assertOk();
        $this->assertNavigationLinks($response->getContent(), 1, 1);
        $html = $this->sidebar();
        $this->assertAdministrationLink($html, route('admin.instituciones.billing.receivable'));
        foreach (['index', 'history', 'movements'] as $section) {
            $this->get(route('admin.instituciones.billing.'.$section))->assertForbidden();
            $this->get(route('admin.instituciones.billing.'.$section.'.legacy'))->assertForbidden();
        }
        $this->get(route('admin.instituciones.billing.receivable.legacy'))->assertRedirect(route('admin.instituciones.billing.receivable'));
        $this->get(route('admin.instituciones.reportes'))->assertForbidden();
    }

    public function test_reports_only_user_does_not_gain_billing_access(): void
    {
        $this->actingAs($this->generalUser(['menu.administracion.reports']));
        $this->assertSame([], AdministrationNavigation::billingSections(auth()->user()));
        $response = $this->get(route('admin.instituciones.reportes', ['seccion' => 'pagos']))->assertOk();
        $this->assertNavigationLinks($response->getContent(), 3, 0);
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'facturacion']))->assertForbidden();
        $this->get(route('admin.instituciones.billing.index'))->assertForbidden();
    }

    public function test_billing_tabs_keep_relevant_filters_and_reset_pagination(): void
    {
        $query = ['search' => 'REM-25', 'date_from' => '2026-09-01', 'institucion_id' => 4, 'page' => 2];
        $response = $this->get(route('admin.instituciones.billing.index', $query))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $links = (new \DOMXPath($dom))->query('//nav[@aria-label="Secciones de facturación"]//a');
        foreach ($links as $index => $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $actual);
            $this->assertSame('REM-25', $actual['search']);
            $this->assertSame('2026-09-01', $actual['date_from']);
            $this->assertArrayNotHasKey('page', $actual);
            if ($index === 3) $this->assertArrayNotHasKey('institucion_id', $actual);
            else $this->assertSame('4', $actual['institucion_id']);
        }
    }

    private function generalUser(array $permissions): User
    {
        $user = (new User)->forceFill(['id' => 2, 'name' => 'Usuario de prueba', 'is_active' => true]);
        $user->setRelation('roles', new Collection([Role::firstOrCreate(['name' => 'Usuario general', 'guard_name' => 'web'])]));
        $user->setRelation('permissions', new Collection(array_map(
            fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']), $permissions
        )));

        return $user;
    }

    private function assertNavigationLinks(string $html, int $administrationCount, int $billingCount): void
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertCount($administrationCount, $xpath->query('//nav[@aria-label="Secciones de administración"]//a'));
        $this->assertCount($billingCount, $xpath->query('//nav[@aria-label="Secciones de facturación"]//a'));
    }

    private function assertAdministrationLink(string $html, string $url): void
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//aside//a[normalize-space(.)="Administracion"]');
        $this->assertCount(1, $links);
        $this->assertSame($url, $links[0]->getAttribute('href'));
        $this->assertSame('page', $links[0]->getAttribute('aria-current'));
        $this->assertSame('open = false', $links[0]->getAttribute('x-on:click'));
        $this->assertCount(0, $xpath->query('./ul', $links[0]->parentNode));
        $this->assertCount(0, $xpath->query('//aside//a[normalize-space(.)="Reportes"]'));
    }

    private function sidebar(): string
    {
        return view('layouts.includes.admin.aside', [
            'pendingSolicitudesCount' => 8, 'myPurchaseOrdersCount' => 0,
            'billingDueCounts' => ['yellow' => 3, 'red' => 2],
        ])->render();
    }
}
