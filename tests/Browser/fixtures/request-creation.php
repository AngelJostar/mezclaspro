<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
$requestType = $argv[1] ?? 'nutricionales';
$request = Illuminate\Http\Request::create('http://creation.test/create?request_popup=1&tipo_solicitud='.$requestType);
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
$errors = new Illuminate\Support\ViewErrorBag;
$inputs = collect();
foreach ([3 => 'Lípidos al 20%', 4 => 'Cloruro de sodio', 5 => 'Multivitamínico'] as $category => $description) {
    foreach ([1, 2] as $column) {
        $inputs->push((object) ['input_id' => $category * 10 + $column, 'category_id' => $category,
            'description' => $column === 1 ? $description : $description.' de referencia',
            'unidad' => 'ml', 'layout_column' => $column, 'orden_enum' => $column,
            'tipo_input' => 'ambos', 'mult' => 1, 'div' => 1, 'presentations_disponibles' => collect()]);
    }
}
$medicamentos = [];
$infoAdicional = [];
$view = $requestType === 'nutricionales' ? 'nutricionales' : 'oncologicos';
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/'.$view.'/solicitudes/create.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source.' @stack("js")', compact('requestType', 'errors', 'inputs', 'medicamentos', 'infoAdicional'));
