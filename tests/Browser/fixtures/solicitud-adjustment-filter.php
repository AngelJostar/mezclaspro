<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');

$user = Tests\Fixtures\SolicitudAdjustmentFilterData::seed();
$request = Illuminate\Http\Request::create('http://localhost/admin/solicitudes', 'GET', ['estado' => $argv[1] ?? 'todas']);
$request->setUserResolver(fn () => $user);
$app->instance('request', $request);
$data = app(App\Http\Controllers\Admin\UnifiedSolicitudController::class)->index($request)->getData();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '',
    file_get_contents(resource_path('views/admin/solicitudes/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, $data);
