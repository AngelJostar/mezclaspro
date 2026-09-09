<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$mode = $argv[1] ?? 'dispensacion';
$request = Illuminate\Http\Request::create('http://mixture.test/edit?modo='.$mode.($mode === 'aprobacion' ? '&approval_popup=1' : ''));
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
$solicitud = (object) array_fill_keys([
    'nombre_paciente', 'servicio', 'registro_paciente', 'sexo', 'fecha_nacimiento', 'peso',
    'piso', 'cama', 'diagnostico', 'nombre_medico', 'cedula_medico', 'fecha_entrega', 'observaciones',
], null);
$solicitud->tipo_solicitud = $argv[4] ?? 'oncologicos';
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
    'id' => 1, 'estado' => $mode === 'aprobacion' ? 'pendiente' : 'aprobada', 'volumen_dilucion' => 250, 'tiempo_infusion' => 15,
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
    1 => [['id' => 501, 'presentacion' => 'Solucion A 250 ml', 'volume_ml' => 250, 'denominacion_comercial' => 'Marca A', 'lote' => 'DIL-250', 'caducidad' => '2027-04-30T00:00:00.000000Z'], ['id' => 502, 'presentacion' => 'Solucion A 500 ml', 'volume_ml' => 500, 'denominacion_comercial' => 'Marca A', 'lote' => 'DIL-500', 'caducidad' => '2027-06-30T00:00:00.000000Z']],
    2 => [['id' => 601, 'presentacion' => 'Solucion B 250 ml', 'volume_ml' => 250]],
];
$catalogIdPorMedicineOncoId = [101 => 10];
$presentacionesPorCatalogo = [];
if (in_array($argv[3] ?? '', ['remainders', 'proposal'], true)) {
    foreach ([10, 20] as $catalogId) {
        $amount = $catalogId === 10 ? 250 : 1000;
        $volume = $catalogId === 10 ? 10 : 4;
        $batches = [];
        foreach ([2, 0.1234, 0] as $index => $ml) {
            $batches[] = [
                'id' => 100 + $index, 'lote' => 'LOTE-'.($index + 1), 'caducidad' => '2027-12-31',
                'fecha_ingreso' => '2026-01-01', 'stock_inicial' => 10, 'stock_actual' => 5, 'stock_reservado' => 0,
                'remanente_ml' => $ml,
                'remanente_mg' => App\Models\Oncologicos\MedicinePresentation::remainderInMilligramsFrom($ml, $amount, $volume),
            ];
        }
        $presentacionesPorCatalogo[$catalogId] = [[
            'id' => $catalogId + 1, 'catalog_id' => $catalogId, 'presentacion' => "Frasco {$amount} mg/{$volume} mL",
            'marca' => 'Marca de prueba', 'cantidad_medicamento' => $amount, 'volumen_diluyente' => $volume,
            'batches' => $batches,
        ], [
            'id' => $catalogId + 2, 'catalog_id' => $catalogId, 'presentacion' => 'Sin concentracion configurada',
            'marca' => 'Marca de prueba', 'cantidad_medicamento' => null, 'volumen_diluyente' => null,
            'batches' => [array_merge($batches[0], ['id' => 200, 'remanente_mg' => null])],
        ]];
    }
}
if (($argv[3] ?? '') === 'proposal') {
    $mezcla->medicamentos[0]['dosis'] = 1800;
    $medicamentos[0]['charge_by'] = 'frasco';
    $medicamentos[1]['charge_by'] = 'frasco';
    foreach ([10, 20] as $catalogId) {
        $presentation = &$presentacionesPorCatalogo[$catalogId][0];
        $presentation['batches'] = [];
        foreach ([[0, 0, 98], [4, 4, 98], [80, 80, 0], [0, 4, 98], [0, 0, 2]] as $index => [$ml, $poolMl, $stock]) {
            $presentation['batches'][] = [
                'id' => 100 + $index, 'warehouse_id' => $index + 1, 'lote' => 'LOTE-'.($index + 1),
                'caducidad' => '2027-12-31', 'fecha_ingreso' => '2026-01-01',
                'stock_inicial' => 98, 'stock_actual' => $stock, 'stock_reservado' => 0,
                'remanente_ml' => $ml, 'remanente_disponible_ml' => $poolMl,
                'remanente_mg' => App\Models\Oncologicos\MedicinePresentation::remainderInMilligramsFrom($ml, $presentation['cantidad_medicamento'], $presentation['volumen_diluyente']),
            ];
        }
        $presentacionesPorCatalogo[$catalogId][] = array_merge($presentation, [
            'id' => $catalogId + 3, 'cantidad_medicamento' => 500, 'volumen_diluyente' => 10,
            'presentacion' => 'Frasco 500 mg/10 mL', 'marca' => 'Marca alternativa',
            'batches' => [array_merge($presentation['batches'][0], ['id' => 300])],
        ]);
        unset($presentation);
    }
}
if (($argv[3] ?? '') === 'combined') {
    $mezcla->medicamentos[0]['dosis'] = 1000;
    $medicamentos[0]['charge_by'] = 'frasco';
    $presentacionesPorCatalogo[10] = [];
    foreach ([[11, 100, 2, 'Marca A'], [14, 400, 3, 'Marca A'], [16, 600, 3, 'Marca B']] as [$id, $mg, $stock, $brand]) {
        $batches = [[
            'id' => $id * 10, 'warehouse_id' => 1, 'lote' => 'LOTE-'.$mg, 'caducidad' => '2027-12-31',
            'stock_actual' => $stock, 'stock_inicial' => $stock, 'stock_reservado' => 0,
            'remanente_ml' => 0, 'remanente_disponible_ml' => 0, 'remanente_mg' => 0,
        ]];
        if ($mg === 400) $batches[] = array_merge($batches[0], ['id' => 141, 'lote' => 'LOTE-LIMITADO', 'stock_actual' => 1]);
        $presentacionesPorCatalogo[10][] = [
            'id' => $id, 'catalog_id' => 10, 'presentacion' => "Frasco {$mg} mg", 'marca' => $brand,
            'cantidad_medicamento' => $mg, 'volumen_diluyente' => $mg / 20, 'batches' => $batches,
        ];
    }
}
$errors = new Illuminate\Support\ViewErrorBag();
// Render the actual view with synthetic records only, without the database-backed admin shell.
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/mezclas/edit.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, compact(
    'solicitud', 'mezcla', 'medicamentos', 'infoAdicional', 'diluentPresentationsPorDiluyente',
    'catalogIdPorMedicineOncoId', 'presentacionesPorCatalogo', 'errors',
));
