<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$fixture = Tests\Fixtures\InstitutionHospitalFields::seed();
(require database_path('migrations/2026_10_21_000002_add_enabled_services_to_institutions_and_hospitals.php'))->up();
$fixture['institution']->hospitals()->attach($fixture['otherHospital']);
$fixture['otherHospital']->update(['enabled_services' => ['quotation_requests' => false]]);
$controller = app(App\Http\Controllers\Admin\EnabledServicesController::class);
$pages = app(App\Http\Controllers\Admin\InstitucionController::class);
$request = Illuminate\Http\Request::create('/');
$request->setUserResolver(fn () => $fixture['manager']);

echo json_encode([
    'hospitalHtml' => $pages->hospitales($request, $fixture['institution'])->render(),
    'institutionHtml' => $pages->index($request)->render(),
    'institution' => $controller->edit($fixture['institution'])->getData(true),
    'hospital' => $controller->edit($fixture['institution'], $fixture['hospital'])->getData(true),
], JSON_THROW_ON_ERROR);
