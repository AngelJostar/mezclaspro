<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
[$central, $hospital] = Tests\Fixtures\MixtureAdjustments::seed();
$service = app(App\Services\MixtureAdjustmentService::class);
$adjustment = $service->requestAdjustment(App\Models\Oncologicos\Mezcla::find(1), Tests\Fixtures\MixtureAdjustments::request($central));
$state = $argv[1] ?? 'requested';
$adjustment->forceFill(['status' => $state]);
if ($state !== 'requested') $adjustment->forceFill(['authorized_at' => now(), 'authorized_by' => $hospital->id, 'hospital_response' => 'Autorizado por el hospital.']);
if ($state === 'approved') $adjustment->forceFill(['approved_at' => now(), 'approved_by' => $central->id]);
$isHospital = ($argv[2] ?? '') === 'hospital';
$request = Illuminate\Http\Request::create('http://localhost/admin/solicitudes/ajustes/1', 'GET', ['approval_popup' => 1, 'decision' => ($argv[3] ?? '') === 'decision' ? 1 : 0]);
$request->setLaravelSession(app('session.store'));
$app->instance('request', $request);
auth()->setUser($isHospital ? $hospital : $central);
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/solicitudes/adjustment.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, ['adjustment' => $adjustment, 'target' => App\Models\Oncologicos\Mezcla::find(1), 'isHospital' => $isHospital, 'errors' => new Illuminate\Support\ViewErrorBag()]);
