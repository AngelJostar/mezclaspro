<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\User;
use Tests\TestCase;

class NutritionLabelSafetyTest extends TestCase
{
    public function test_label_renders_when_time_and_infusion_speed_are_zero(): void
    {
        $detail = new SolicitudDetail([
            'volumen_total' => 500,
            'suma_volumen' => 500,
            'sobrellenado_ml' => 0,
            'velocidad_infusion' => 0,
            'tiempo_infusion_min' => 0,
            'nombre_medico' => 'Médico Prueba',
            'cedula' => 'TEST-1',
            'npt' => 'ADULT',
        ]);
        $patient = new SolicitudPatient([
            'nombre_paciente' => 'Paciente',
            'apellidos_paciente' => 'Prueba',
            'fecha_nacimiento' => '1990-01-01',
            'registro' => 'TEST',
            'cama' => '1',
            'peso' => 60,
            'servicio' => 'Nutrición',
        ]);
        $user = new User();
        $user->setRelation('hospital', new Hospital(['name' => 'Hospital de prueba']));

        $solicitud = new Solicitud(['lote' => 'LOTE-TEST']);
        $solicitud->setRelation('solicitud_detail', $detail);
        $solicitud->setRelation('solicitud_patient', $patient);
        $solicitud->setRelation('user', $user);

        $html = view('pdfs.nutricionales.etiqueta', [
            'solicitud_detalles' => $solicitud,
            'inputs_solicitud' => collect(),
            'qrImage' => null,
        ])->render();

        $this->assertStringContainsString('No especificado', $html);
        $this->assertStringContainsString('No especificada', $html);
    }
}
