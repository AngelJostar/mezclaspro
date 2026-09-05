<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class WorkflowModalViewTest extends TestCase
{
    public function test_workflow_pages_render_without_the_navigation_or_nested_modal(): void
    {
        foreach ([
            ['admin.oncologicos.mezclas.edit', 'approval_popup'],
            ['admin.oncologicos.mezclas.edit', 'dispensing_popup'],
            ['admin.nutricionales.solicitudes.edit', 'approval_popup'],
        ] as [$route, $flag]) {
            $html = $this->renderLayout($route, [$flag => 1]);
            $this->assertStringContainsString('workflow-page', $html);
            $this->assertStringNotContainsString('id="logo-sidebar"', $html);
            $this->assertStringNotContainsString('class="corporate-header', $html);
            $this->assertStringNotContainsString('data-workflow-modal aria-', $html);
            $this->assertStringNotContainsString('window.open(', $html);
            $this->assertTrue($this->pageConfig($html)['embedded']);
            $this->assertFalse($this->pageConfig($html)['completed']);
        }
    }

    public function test_completion_waits_for_confirmation_but_validation_errors_do_not_complete_it(): void
    {
        $this->withSession([
            'approval_popup_done' => true,
            'success' => 'Mezcla aprobada.',
            'approval_popup_return_to' => '/admin/solicitudes?estado=pendientes',
        ]);
        $config = $this->pageConfig($this->renderLayout('admin.oncologicos.mezclas.edit', ['approval_popup' => 1]));
        $this->assertTrue($config['completed']);
        $this->assertTrue($config['waitForConfirmation']);
        $this->assertSame('/admin/solicitudes?estado=pendientes', $config['returnTo']);

        session()->flush();
        $this->withSession(['error' => 'Revisa los datos del formulario.']);
        $config = $this->pageConfig($this->renderLayout('admin.oncologicos.mezclas.edit', ['approval_popup' => 1]));
        $this->assertFalse($config['completed']);
        $this->assertFalse($config['waitForConfirmation']);
    }

    public function test_modal_shell_has_an_accessible_close_button_and_contained_frame(): void
    {
        $html = view('layouts.includes.workflow-modal')->render();
        $this->assertStringContainsString('<dialog', $html);
        $this->assertStringContainsString('aria-labelledby="workflow-modal-title"', $html);
        $this->assertStringContainsString('aria-label="Cerrar ventana"', $html);
        $this->assertStringContainsString('src="about:blank"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    private function renderLayout(string $routeName, array $query): string
    {
        $request = Request::create('http://localhost/admin/workflow/edit', 'GET', $query);
        $route = (new Route('GET', '/admin/workflow/edit', fn () => null))->name($routeName);
        $request->setRouteResolver(fn () => $route);
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        return view('layouts.admin', ['slot' => new HtmlString('<h1 data-workflow-heading>Mezcla de prueba</h1>')])->render();
    }

    private function pageConfig(string $html): array
    {
        preg_match('/id="workflow-page-config">(.*?)<\/script>/s', $html, $matches);

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }
}
