<?php

namespace Tests\Feature;

use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NutritionRequestFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approved_nutrition_request_can_be_prepared_reviewed_and_delivered(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::factory()->create(['hospital_id' => null, 'lastname' => 'Simulación']);
        $patient = SolicitudPatient::create(['nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'servicio' => 'Nutrición', 'peso' => 60, 'fecha_nacimiento' => '1990-01-01']);
        $detail = SolicitudDetail::create(['via_administracion' => 'Central', 'npt' => 'ADULT', 'nombre_medico' => 'Médico Prueba', 'cedula' => 'TEST-1', 'fecha_hora_entrega' => now()->addDay()]);
        $request = Solicitud::create(['user_id' => $user->id, 'solicitud_patient_id' => $patient->id, 'solicitud_detail_id' => $detail->id, 'estado' => 'aprobada', 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.nutricionales.solicitudes.preparar', $request))->assertRedirect();
        $this->assertSame('preparada', $request->fresh()->estado);
        $this->assertNotNull($request->fresh()->fecha_hora_preparacion);

        $this->actingAs($user)->post(route('admin.nutricionales.solicitudes.revisar', $request))->assertRedirect();
        $this->assertSame('revisada', $request->fresh()->estado);

        $this->actingAs($user)->post(route('admin.nutricionales.solicitudes.entregar', $request))->assertRedirect();
        $this->assertSame('entregada', $request->fresh()->estado);
        $this->assertNotNull($request->inspeccionNutricional()->first()?->preparo_nombre);
        $this->assertNotNull($request->inspeccionNutricional()->first()?->reviso_nombre);
        $this->assertNotNull($request->inspeccionNutricional()->first()?->libero_nombre);
    }

    public function test_nutrition_request_cannot_skip_preparation(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::factory()->create(['hospital_id' => null, 'lastname' => 'Simulación']);
        $patient = SolicitudPatient::create(['nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'servicio' => 'Nutrición', 'peso' => 60, 'fecha_nacimiento' => '1990-01-01']);
        $detail = SolicitudDetail::create(['via_administracion' => 'Central', 'npt' => 'ADULT', 'nombre_medico' => 'Médico Prueba', 'cedula' => 'TEST-2', 'fecha_hora_entrega' => now()->addDay()]);
        $request = Solicitud::create(['user_id' => $user->id, 'solicitud_patient_id' => $patient->id, 'solicitud_detail_id' => $detail->id, 'estado' => 'aprobada', 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.nutricionales.solicitudes.entregar', $request))->assertSessionHasErrors('error');
        $this->assertSame('aprobada', $request->fresh()->estado);
    }
}
