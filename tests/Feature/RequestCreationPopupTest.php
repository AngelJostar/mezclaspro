<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Nutricionales\SolicitudController as NutritionController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologyController;
use App\Models\User;
use App\Support\RequestCreationPopup;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class RequestCreationPopupTest extends TestCase
{
    private function popupRequest(string $type): Request
    {
        $routeName = $type === 'nutricionales' ? 'admin.nutricionales.solicitudes.create' : 'admin.oncologicos.solicitudes.create';
        $request = Request::create(route($routeName), 'GET', ['request_popup' => 1, 'tipo_solicitud' => $type]);
        $request->setRouteResolver(fn () => (new Route('GET', '/', fn () => null))->name($routeName));
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);
        return $request;
    }

    public function test_saved_requests_return_to_a_confirmation_inside_each_format(): void
    {
        foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $type) {
            $request = $this->popupRequest($type);
            $this->assertFalse(RequestCreationPopup::completed($request));
            $response = RequestCreationPopup::saved($type);
            $this->assertStringContainsString('request_popup=1', $response->getTargetUrl());
            $this->assertStringContainsString('tipo_solicitud='.$type, $response->getTargetUrl());
            $this->assertTrue(RequestCreationPopup::completed($request));
            $controller = $type === 'nutricionales' ? NutritionController::class : OncologyController::class;
            $view = app($controller)->create($request);
            $this->assertSame('admin.solicitudes._creation-result', $view->name());
            $this->assertNull($view->getData()['creationError']);
            $html = $view->render();
            $this->assertStringContainsString('Solicitud registrada correctamente', $html);
            $this->assertStringContainsString('"requestCreated":true', $html);
            $this->assertStringNotContainsString('id="logo-sidebar"', $html);
            session()->flush();
        }
    }

    public function test_incomplete_hospital_configuration_stays_inside_the_popup(): void
    {
        $user = (new User)->setRelation('hospital', null);
        $this->actingAs($user);
        foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $type) {
            $request = $this->popupRequest($type);
            $view = app($type === 'nutricionales' ? NutritionController::class : OncologyController::class)->create($request);
            $this->assertSame('admin.solicitudes._creation-result', $view->name());
            $this->assertNotEmpty($view->getData()['creationError']);
            $this->assertFalse(RequestCreationPopup::completed($request));
        }
    }

    public function test_confirmation_cannot_be_applied_to_another_mixture_type_or_a_regular_page(): void
    {
        RequestCreationPopup::saved('antibioticos');
        $this->assertFalse(RequestCreationPopup::completed($this->popupRequest('nutricionales')));
        $this->assertFalse(RequestCreationPopup::completed($this->popupRequest('oncologicos')));
        $request = $this->popupRequest('antibioticos');
        $request->query->remove('request_popup');
        $this->assertFalse(RequestCreationPopup::completed($request));
    }
}
