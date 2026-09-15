<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\SolicitudValidations::boot();
Tests\Fixtures\RejectedSolicitudData::boot(($argv[2] ?? '') === 'rejected');
$request = Illuminate\Http\Request::create('/admin/solicitudes/validaciones', 'GET', ['tipo' => $argv[1] ?? 'todas']);
$request->setUserResolver(fn () => $user);
app()->instance('request', $request);
echo app(App\Http\Controllers\Admin\SolicitudValidationController::class)->index($request)->render();
