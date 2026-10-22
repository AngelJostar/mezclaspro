<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$fixture = Tests\Fixtures\InstitutionHospitalFields::seed();
(require database_path('migrations/2026_10_21_000002_add_enabled_services_to_institutions_and_hospitals.php'))->up();
$institution = $fixture['institution'];
$institution->update(['razon_social' => 'Razón social de prueba']);
$institution->hospitals()->attach($fixture['otherHospital']);
$fixture['otherHospital']->update(['service_antibiotics' => true]);
Tests\Fixtures\HospitalCredentialList::account($fixture['hospital'], 'hospital.uno');
Tests\Fixtures\HospitalCredentialList::account($fixture['hospital'], 'hospital.turno');
Tests\Fixtures\HospitalCredentialList::account($fixture['otherHospital'], 'hospital.dos');
$empty = App\Models\Institucion::create(['nombre' => 'Institución vacía']);
$controller = app(App\Http\Controllers\Admin\InstitutionFieldController::class);
$fields = [];
foreach (['nombre', 'razon_social'] as $field) $fields[$field] = $controller->edit($institution, $field)->getData(true);
$request = Illuminate\Http\Request::create('/');
$request->setUserResolver(fn () => $fixture['manager']);
echo json_encode([
    'html' => app(App\Http\Controllers\Admin\InstitucionController::class)->index($request)->render(),
    'fields' => $fields,
    'mixtures' => app(App\Http\Controllers\Admin\InstitutionMixturesController::class)->edit($institution)->getData(true),
    'empty' => app(App\Http\Controllers\Admin\InstitutionMixturesController::class)->edit($empty)->getData(true),
], JSON_THROW_ON_ERROR);
