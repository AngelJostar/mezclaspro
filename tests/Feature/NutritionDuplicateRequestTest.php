<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\User;
use App\Services\NutritionDuplicateRequestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NutritionDuplicateRequestTest extends TestCase
{
    use DatabaseTransactions;

    private NutritionDuplicateRequestService $duplicates;
    private Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->duplicates = app(NutritionDuplicateRequestService::class);
        $this->hospital = Hospital::factory()->create(['adress' => 'Dirección de prueba']);
    }

    public function test_unique_request_has_no_creation_conflict(): void
    {
        $this->createRequest('REG-100', '2026-11-10 09:00:00');

        $match = $this->duplicates->findForCreation(
            $this->hospital->id,
            $this->patientData('REG-100'),
            '2026-11-11T09:00'
        );

        $this->assertNull($match);
    }

    public function test_same_hospital_patient_and_delivery_day_requires_creation_confirmation(): void
    {
        $previous = $this->createRequest('REG-200', '2026-11-10 08:00:00');

        $match = $this->duplicates->findForCreation(
            $this->hospital->id,
            $this->patientData('REG-200'),
            '2026-11-10T18:45'
        );

        $this->assertTrue($previous->is($match));
        $this->assertSame('10/11/2026 08:00', $this->duplicates->confirmationData($match)['delivery_at']);
    }

    public function test_confirmed_duplicate_is_allowed_to_continue(): void
    {
        $this->createRequest('', '2026-11-10 08:00:00');

        $match = $this->duplicates->findForCreation(
            $this->hospital->id,
            $this->patientData(''),
            '2026-11-10T19:00',
            true
        );

        $this->assertNull($match);
    }

    public function test_second_approval_requires_confirmation_and_excludes_current_request(): void
    {
        $approved = $this->createRequest('REG-300', '2026-11-12 07:30:00', 'aprobada');
        $current = $this->createRequest('REG-300', '2026-11-12 17:30:00', 'pendiente');

        $match = $this->duplicates->findApprovedForApproval(
            $this->hospital->id,
            $this->patientData('REG-300'),
            '2026-11-12T17:30',
            $current->id
        );

        $this->assertTrue($approved->is($match));
        $this->assertNull($this->duplicates->findApprovedForApproval(
            $this->hospital->id,
            $this->patientData('REG-300'),
            '2026-11-12T17:30',
            $current->id,
            true
        ));
    }

    private function createRequest(string $register, string $deliveryAt, string $status = 'pendiente'): Solicitud
    {
        $patient = SolicitudPatient::create(array_merge($this->patientData($register), [
            'servicio' => 'Nutrición',
            'peso' => 70,
        ]));
        $detail = SolicitudDetail::create([
            'via_administracion' => 'Central',
            'npt' => 'ADULT',
            'nombre_medico' => 'Médico Prueba',
            'cedula' => 'TEST-1',
            'fecha_hora_entrega' => $deliveryAt,
        ]);
        $user = User::factory()->create(['hospital_id' => $this->hospital->id]);

        return Solicitud::create([
            'hospital_id' => $this->hospital->id,
            'user_id' => $user->id,
            'solicitud_patient_id' => $patient->id,
            'solicitud_detail_id' => $detail->id,
            'estado' => $status,
            'is_active' => true,
            'lote' => $status === 'aprobada' ? 'L-PRUEBA' : null,
        ]);
    }

    private function patientData(string $register): array
    {
        return [
            'registro' => $register,
            'nombre_paciente' => 'María José',
            'apellidos_paciente' => 'López Pérez',
            'fecha_nacimiento' => '1985-04-21',
        ];
    }
}
