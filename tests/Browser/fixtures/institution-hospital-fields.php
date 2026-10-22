<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$fixture = Tests\Fixtures\InstitutionHospitalFields::seed();
$controller = app(App\Http\Controllers\Admin\InstitutionHospitalFieldController::class);
$editors = [];
foreach (['services', 'routes', 'sellers'] as $field) {
    $editors[$field] = $controller->edit($fixture['institution'], $fixture['hospital'], $field)->getData(true);
}
echo json_encode([
    'html' => app(App\Http\Controllers\Admin\InstitucionController::class)
        ->hospitales(Illuminate\Http\Request::create('/'), $fixture['institution'])->render(),
    'editors' => $editors,
], JSON_THROW_ON_ERROR);
