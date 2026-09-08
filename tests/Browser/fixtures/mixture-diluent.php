<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$mode = $argv[1] ?? 'dispensacion';
$request = Illuminate\Http\Request::create('http://mixture.test/edit?modo='.$mode);
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
$solicitud = (object) array_fill_keys([
    'nombre_paciente', 'servicio', 'registro_paciente', 'sexo', 'fecha_nacimiento', 'peso',
    'piso', 'cama', 'diagnostico', 'nombre_medico', 'cedula_medico', 'fecha_entrega', 'observaciones',
], null);
$solicitud->tipo_solicitud = 'oncologicos';
$solicitud->hospital = new App\Models\Hospital(['name' => 'Hospital de Prueba']);
$solicitud->hospital->setRelation('instituciones', collect([new App\Models\Institucion(['nombre' => 'Institucion de Prueba'])]));
foreach ([
    'nombre_paciente' => 'Paciente de Prueba', 'registro_paciente' => 'REG-123', 'sexo' => 'F',
    'fecha_nacimiento' => '1986-09-10', 'peso' => '57.00', 'servicio' => 'HOSPITALIZACION',
    'piso' => '2', 'cama' => '201', 'fecha_entrega' => '2026-09-04 18:21:00',
    'diagnostico' => 'Diagnostico de prueba', 'nombre_medico' => 'Medico de Prueba',
    'cedula_medico' => '123456', 'observaciones' => 'Observaciones de prueba',
] as $field => $value) {
    $solicitud->$field = $value;
}
if (($argv[2] ?? '') === 'old-input') {
    session()->flashInput(['paciente_nombre' => 'Nombre actualizado', 'registro' => 'REG-456', 'sexo' => 'M']);
}
$mezcla = (object) [
    'id' => 1, 'estado' => 'aprobada', 'volumen_dilucion' => 250, 'tiempo_infusion' => 15,
    'diluent_presentation_id' => 501,
    'medicamentos' => [
        ['medicamento_id' => 101, 'dosis' => 90, 'diluyente_id' => 1, 'via_administracion_id' => 1],
    ],
];
$medicamentos = [
    ['id' => 10, 'denominacion' => 'Medicamento Uno', 'charge_by' => 'mg'],
    ['id' => 20, 'denominacion' => 'Medicamento Dos', 'charge_by' => 'mg'],
];
$infoAdicional = [
    10 => ['diluyentes' => [['id' => 1, 'name' => 'Solucion A'], ['id' => 2, 'name' => 'Solucion B']], 'vias' => [['id' => 1, 'name' => 'INTRAVENOSA']]],
    20 => ['diluyentes' => [['id' => 1, 'name' => 'Solucion A']], 'vias' => [['id' => 1, 'name' => 'INTRAVENOSA']]],
];
$diluentPresentationsPorDiluyente = [
    1 => [['id' => 501, 'presentacion' => 'Solucion A 250 ml', 'volume_ml' => 250], ['id' => 502, 'presentacion' => 'Solucion A 500 ml', 'volume_ml' => 500]],
    2 => [['id' => 601, 'presentacion' => 'Solucion B 250 ml', 'volume_ml' => 250]],
];
$catalogIdPorMedicineOncoId = [101 => 10];
$errors = new Illuminate\Support\ViewErrorBag();
// Render the actual view with synthetic records only, without the database-backed admin shell.
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/mezclas/edit.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, compact(
    'solicitud', 'mezcla', 'medicamentos', 'infoAdicional', 'diluentPresentationsPorDiluyente',
    'catalogIdPorMedicineOncoId', 'errors',
));
