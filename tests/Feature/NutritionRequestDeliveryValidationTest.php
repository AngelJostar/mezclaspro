<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Nutricionales\SolicitudController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NutritionRequestDeliveryValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_too_soon_delivery_keeps_input_and_exposes_the_validation_error(): void
    {
        Carbon::setTestNow('2026-09-17 15:08:00');

        $request = Request::create('/admin/nutricionales/solicitudes', 'POST', [
            'nombre_paciente' => 'Luis angel',
            'apellidos_paciente' => 'Rojas espinoza',
            'servicio' => 'Oncologico',
            'peso' => 55,
            'fecha_nacimiento' => '2026-09-07',
            'sexo' => 'Masculino',
            'via_administracion' => 'Central',
            'tiempo_infusion_min' => 24,
            'sobrellenado_ml' => 100,
            'volumen_total' => 2000,
            'npt' => 'INF',
            'fecha_hora_entrega' => '2026-09-17T15:12',
            'nombre_medico' => 'Dr. Carter Jimmy',
            'cedula' => '9108971',
        ], [], [], [
            'HTTP_REFERER' => url('/admin/nutricionales/solicitudes/create'),
        ]);

        $session = app('session')->driver();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        $response = app(SolicitudController::class)->store($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith(
            '/admin/nutricionales/solicitudes/create',
            $response->getTargetUrl()
        );
        $this->assertSame('Luis angel', $session->getOldInput('nombre_paciente'));
        $this->assertStringContainsString(
            '3 horas y 30 minutos',
            $session->get('errors')->first('fecha_hora_entrega')
        );

    }

    public function test_negative_component_quantity_is_rejected_before_database_writes(): void
    {
        Carbon::setTestNow('2026-09-17 15:08:00');
        $request = $this->request(array_merge($this->validPayload(), [
            'i_5_g/Kg' => -1,
        ]));

        try {
            app(SolicitudController::class)->store($request);
            $this->fail('The negative component quantity was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('i_5_g/Kg', $exception->errors());
        }
    }

    public function test_invalid_npt_and_future_birth_date_are_rejected(): void
    {
        Carbon::setTestNow('2026-09-17 15:08:00');
        $request = $this->request(array_merge($this->validPayload(), [
            'npt' => 'OTRO',
            'fecha_nacimiento' => '2026-09-18',
        ]));

        try {
            app(SolicitudController::class)->store($request);
            $this->fail('The invalid clinical fields were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('npt', $exception->errors());
            $this->assertArrayHasKey('fecha_nacimiento', $exception->errors());
        }
    }

    private function validPayload(): array
    {
        return [
            'nombre_paciente' => 'Paciente',
            'apellidos_paciente' => 'Prueba',
            'servicio' => 'Nutrición',
            'peso' => 55,
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'Masculino',
            'via_administracion' => 'Central',
            'tiempo_infusion_min' => 24,
            'sobrellenado_ml' => 100,
            'volumen_total' => 2000,
            'npt' => 'INF',
            'fecha_hora_entrega' => '2026-09-18T10:00',
            'nombre_medico' => 'Médico Prueba',
            'cedula' => 'TEST-1',
        ];
    }

    private function request(array $payload): Request
    {
        $request = Request::create('/admin/nutricionales/solicitudes', 'POST', $payload);
        $request->setLaravelSession(app('session')->driver());
        $this->app->instance('request', $request);

        return $request;
    }
}
