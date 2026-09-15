<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'hashing.bcrypt.rounds' => 4]);
Illuminate\Support\Facades\DB::purge('sqlite');
$manager = Tests\Fixtures\HospitalCredentialList::seed();
$manager->syncRoles(($argv[1] ?? '') === 'manager' ? 'Admin' : 'Super Admin');
$hospital = Tests\Fixtures\HospitalCredentialList::hospital('Hospital Uno');
Tests\Fixtures\HospitalCredentialList::account($hospital, 'hospital.uno', 'Clave-Prueba-001');
Tests\Fixtures\HospitalCredentialList::account($hospital, 'hospital.turno', 'Clave-Prueba-002', 'Cliente');
Tests\Fixtures\HospitalCredentialList::account(
    Tests\Fixtures\HospitalCredentialList::hospital('Hospital Dos'), 'hospital.dos'
)->update(['credential_password' => null]);
Tests\Fixtures\HospitalCredentialList::hospital('Hospital sin acceso');
$request = Illuminate\Http\Request::create('/admin/hospitals');
$request->setUserResolver(fn () => $manager);
echo json_encode(['html' => app(App\Http\Controllers\Admin\HospitalController::class)->index($request)->getContent()], JSON_THROW_ON_ERROR);
