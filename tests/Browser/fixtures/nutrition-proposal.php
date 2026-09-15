<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$request = Illuminate\Http\Request::create('http://mixture.test/edit?approval_popup=1');
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
$hospital = new App\Models\Hospital(['name' => 'Hospital de Prueba']);
$hospital->setRelation('instituciones', collect());
$user = (new App\Models\User)->setRelation('hospital', $hospital);
$patient = new App\Models\Nutricionales\SolicitudPatient(['nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'servicio' => 'Nutricion', 'fecha_nacimiento' => '1990-01-01', 'peso' => 60]);
$detail = new App\Models\Nutricionales\SolicitudDetail(['volumen_total' => 250, 'volumen_total_final' => 250,
    'via_administracion' => 'Central', 'tiempo_infusion_min' => 12, 'npt' => 'ADULT',
    'nombre_medico' => 'Medico de prueba', 'cedula' => '12345', 'fecha_hora_entrega' => '2030-01-01T10:00']);
$solicitud = (new App\Models\Nutricionales\Solicitud)->forceFill(['id' => 1, 'estado' => 'pendiente'])
    ->setRelation('user', $user)->setRelation('solicitud_patient', $patient)->setRelation('solicitud_detail', $detail);
$input = (new App\Models\Nutricionales\Input)->forceFill(['id' => 1, 'input_id' => 1, 'category_id' => 1, 'description' => 'Aminoacidos', 'unidad' => 'g']);
$inputs = collect([$input]);
$inputs_solicitud = collect([(new App\Models\Nutricionales\SolicitudInput)->forceFill(['input_id' => 1, 'valor' => 25])->setRelation('input', $input)]);
$inventarioPorInput = [];
$errors = new Illuminate\Support\ViewErrorBag;
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/nutricionales/solicitudes/edit.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source.' @stack("js")', compact('solicitud', 'inputs', 'inputs_solicitud', 'inventarioPorInput', 'errors'));
